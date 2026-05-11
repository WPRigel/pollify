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

		$data = $feedback->vote( $args['options'] ?? [], $args );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Enforce per-IP rate limiting on vote submissions.
	 *
	 * Allows up to 10 attempts per IP per minute. Uses REMOTE_ADDR (the actual
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
