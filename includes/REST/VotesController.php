<?php
/**
 * Votes rest route endpoint.
 *
 * @package wpRigel\Pollify
 */

declare(strict_types=1);

namespace wpRigel\Pollify\REST;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Controller;
use wpRigel\Pollify\FeedbackManager;
use wpRigel\Pollify\Model\Voter;

/**
 * VotesController class.
 *
 * @package wpRigel\Pollify\API
 */
class VotesController extends WP_REST_Controller {

	/**
	 * Namespace for the endpoint.
	 *
	 * @var string
	 */
	protected $namespace = 'pollify/v1';

	/**
	 * Base URL for endpoint.
	 *
	 * @var string
	 */
	protected $action = 'vote';

	/**
	 * Register Routes for custom request.
	 *
	 * Get challenge: '/wp-json/pollify/v1/vote'.
	 *
	 * @return void
	 */
	public function register_routes(): void {

		register_rest_route(
			$this->namespace,
			'/' . $this->action . '/(?P<client_id>[^/]+)/',
			[
				'args' => [
					'client_id' => [
						'description' => __( 'Unique identifier for the object for poll', 'poll-creator' ),
						'type'        => 'string',
					],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'do_vote' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
					'permission_callback' => '__return_true',
				],
			],
		);

		register_rest_route(
			$this->namespace,
			'/nonce',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_nonce' ],
					'permission_callback' => '__return_true',
				],
			],
		);
	}

	/**
	 * Return a fresh vote nonce.
	 *
	 * Pages served from a long-lived page cache can embed an expired nonce.
	 * Clients call this endpoint to refresh the nonce and retry the vote.
	 *
	 * @return WP_REST_Response
	 */
	public function get_nonce(): WP_REST_Response {
		$response = rest_ensure_response( [ 'nonce' => wp_create_nonce( 'pollify-vote' ) ] );
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * Do vote for specific poll id with options.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function do_vote( WP_REST_Request $request ) {
		$args = $request->get_params();

		if ( ! wp_verify_nonce( $args['nonce'], 'pollify-vote' ) ) {
			return new WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'poll-creator' ), [ 'status' => 403 ] );
		}

		$rate_limit = $this->check_rate_limit();

		if ( is_wp_error( $rate_limit ) ) {
			return $rate_limit;
		}

		if ( empty( $args['client_id'] ) ) {
			return new WP_Error( 'no-poll-id', __( 'Invalid poll', 'poll-creator' ), [ 'status' => 404 ] );
		}

		$feedback = FeedbackManager::get_instance()->get( $args['client_id'] );

		if ( is_wp_error( $feedback ) ) {
			return new WP_Error( 'no-poll', __( 'Invalid poll', 'poll-creator' ), [ 'status' => 404 ] );
		}

		// Serialize validate-then-insert per voter so concurrent requests
		// cannot both pass the duplicate-vote check before either inserts.
		$lock = $this->acquire_vote_lock( $args['client_id'] );

		if ( is_wp_error( $lock ) ) {
			return $lock;
		}

		try {
			$data = $feedback->vote( $args['options'] ?? [], $args );
		} finally {
			$this->release_vote_lock( $args['client_id'] );
		}

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Build the per-voter MySQL lock name for a poll.
	 *
	 * @param string $client_id Poll client ID.
	 *
	 * @return string
	 */
	private function get_vote_lock_name( string $client_id ): string {
		$voter    = new Voter();
		$identity = $voter->get_user_id() > 0 ? 'u' . $voter->get_user_id() : 'ip' . $voter->get_user_ip();

		// MySQL lock names are limited to 64 chars; SHA-256 hex is exactly 64 chars.
		return hash( 'sha256', 'pollify_vote_' . $client_id . '|' . $identity );
	}

	/**
	 * Acquire the per-voter vote lock.
	 *
	 * GET_LOCK is MySQL-specific and returns 1 when the lock is acquired,
	 * 0 on timeout (another request holds it), and NULL on error or when the
	 * backend does not support user-level locks (e.g. SQLite on WP Playground).
	 * Only an explicit 0 represents real contention and should block the vote;
	 * NULL degrades gracefully so voting still works without the lock.
	 *
	 * @param string $client_id Poll client ID.
	 *
	 * @return true|WP_Error True to proceed, WP_Error only on real contention.
	 */
	private function acquire_vote_lock( string $client_id ): bool|WP_Error {
		global $wpdb;

		$acquired = $wpdb->get_var(
			$wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $this->get_vote_lock_name( $client_id ) )
		);

		if ( '0' === (string) $acquired ) {
			return new WP_Error(
				'vote_in_progress',
				__( 'Another vote is being processed. Please try again.', 'poll-creator' ),
				[ 'status' => 429 ]
			);
		}

		return true;
	}

	/**
	 * Release the per-voter vote lock.
	 *
	 * @param string $client_id Poll client ID.
	 *
	 * @return void
	 */
	private function release_vote_lock( string $client_id ): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $this->get_vote_lock_name( $client_id ) )
		);
	}

	/**
	 * Enforce per-IP rate limiting on vote submissions.
	 *
	 * Allows up to 30 attempts per IP per minute. Uses REMOTE_ADDR (the actual
	 * connecting IP) so callers cannot bypass the limit via spoofed headers.
	 *
	 * @return bool|WP_Error True if allowed, WP_Error with 429 status if limited.
	 */
	private function check_rate_limit(): bool|WP_Error {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		$key = 'pollify_rl_' . hash( 'sha256', $ip . get_site_url() );

		$attempts = (int) get_transient( $key );

		if ( $attempts >= 30 ) {
			return new WP_Error(
				'rate_limited',
				__( 'Too many requests. Please try again later.', 'poll-creator' ),
				[ 'status' => 429 ]
			);
		}

		set_transient( $key, $attempts + 1, MINUTE_IN_SECONDS );

		return true;
	}

	/**
	 * Item schema
	 *
	 * @since DOKAN_LITE
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'Vote',
			'type'       => 'array',
			'properties' => [
				'id'          => [
					'description' => __( 'Unique identifier for the object.', 'poll-creator' ),
					'type'        => 'integer',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
				'options'     => [
					'required'    => true,
					'description' => __( 'Option IDs', 'poll-creator' ),
					'type'        => 'array',
					'context'     => [ 'view', 'edit' ],
				],
				'with_result' => [
					'description' => __( 'Return with result or not', 'poll-creator' ),
					'type'        => 'boolean',
					'context'     => [ 'view' ],
					'readonly'    => true,
				],
			],
		];

		return $this->add_additional_fields_schema( $schema );
	}
}
