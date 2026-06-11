<?php
/**
 * Feedback generic model class.
 *
 * @package wpRigel\Pollify
 */

declare(strict_types=1);

namespace wpRigel\Pollify\Model;

use WP_Error;
use wpRigel\Pollify\Votes;

/**
 * Class Poll.
 *
 * Handle a single feddback object with all its data.
 */
abstract class Feedback {

	/**
	 * Poll data.
	 *
	 * @var array
	 */
	private array $data = [
		'id'          => 0,
		'client_id'   => '',
		'title'       => '',
		'description' => '',
		'type'        => '',
		'status'      => '',
		'reference'   => '',
		'options'     => [],
		'created_at'  => '',
		'updated_at'  => '',
		'settings'    => [],
		'response'    => 0,
	];

	/**
	 * Poll constructor.
	 *
	 * @param array $args Poll arguments.
	 */
	public function __construct( array $args = [] ) {
		// Need to set $data array with $args array in such way like only $data array keys will be set
		// which are exists in $args array.
		$this->data = array_merge(
			$this->data,
			array_intersect_key(
				$args,
				$this->data
			)
		);

		if ( ! empty( $this->data['settings'] ) ) {
			$this->data['settings'] = json_decode( $this->data['settings'] ?? '', true, 512 );
		}
	}

	/**
	 * Get poll ID.
	 *
	 * @return int
	 */
	public function get_id(): int {
		return intval( $this->data['id'] );
	}

	/**
	 * Get poll client ID.
	 *
	 * @return string
	 */
	public function get_client_id(): string {
		return $this->data['client_id'];
	}

	/**
	 * Get poll title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return $this->data['title'];
	}

	/**
	 * Get poll description.
	 *
	 * @return string
	 */
	public function get_description(): string {
		return $this->data['description'];
	}

	/**
	 * Get poll type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->data['type'];
	}

	/**
	 * Get poll status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->data['status'];
	}

	/**
	 * Get poll reference.
	 *
	 * @return string
	 */
	public function get_reference(): string {
		return $this->data['reference'];
	}

	/**
	 * Get poll options.
	 *
	 * @return array
	 */
	public function get_options(): array {
		return $this->data['options'];
	}

	/**
	 * Get poll created at.
	 *
	 * @return string
	 */
	public function get_created_at(): string {
		return $this->data['created_at'];
	}

	/**
	 * Get all poll data.
	 *
	 * @return array
	 */
	public function get_data(): array {
		return $this->data;
	}

	/**
	 * Get poll settings.
	 *
	 * @return array
	 */
	public function get_settings(): array {
		return (array) ( $this->data['settings'] ?? [] );
	}

	/**
	 * Get poll response.
	 *
	 * @return int
	 */
	public function get_response(): int {
		return intval( $this->data['response'] );
	}

	/**
	 * Get poll icon.
	 *
	 * @param int    $size Icon size. Default 25.
	 * @param string $color Icon color. Default #50575e.
	 *
	 * @return string
	 */
	public function get_icon( $size = 25, $color = '#50575e' ): string {
		return '<svg width="' . $size . 'px" height="' . $size . 'px" viewBox="-32 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M448 432V80c0-26.5-21.5-48-48-48H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48zM112 192c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h128c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16H112zm0 96c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h224c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16H112zm0 96c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h64c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16h-64z" fill="' . $color . '"/></svg>';
	}

	/**
	 * Check all options is valid which is passed by arguments.
	 *
	 * @param array $options Options.
	 *
	 * @return bool
	 */
	public function is_valid_poll_option( array $options = [] ): bool {
		$valid = true;

		// Want to check each option id is valid or not.
		foreach ( $options as $option_id ) {
			$poll_option = array_filter(
				$this->get_options(),
				function ( $option ) use ( $option_id ) {
					return $option['option_id'] === $option_id;
				}
			);

			if ( empty( $poll_option ) ) {
				$valid = false;
				break;
			}
		}

		return $valid;
	}

	/**
	 * Check if poll is closed or not.
	 *
	 * @return bool
	 */
	public function is_poll_closed(): bool {
		$settings = $this->get_settings();

		$status = $settings['status'] ?? '';

		if ( 'draft' === $status ) {
			return true;
		}

		if ( 'schedule' === $status && ! empty( $settings['endDate'] ) ) {
			$end_date = strtotime( $settings['endDate'] );

			if ( $end_date < time() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get results.
	 *
	 * @return array
	 */
	public function get_results(): array {
		// Get the vote result.
		$result = Votes::get_instance()->get_results( $this );

		return $result;
	}

	/**
	 * Get the poll vote data.
	 *
	 * @param array $args Arguments.
	 *
	 * @return array|int
	 */
	public function get_votes( $args = [] ) {
		$default = [
			'client_id' => $this->get_client_id(),
		];

		$args = wp_parse_args( $args, $default );

		// Get the vote result.
		$result = Votes::get_instance()->get_votes( $args );

		return $result;
	}

	/**
	 * Get the votes by IP.
	 *
	 * @param array $args Arguments.
	 *
	 * @return array|int
	 */
	public function get_ip_votes( $args = [] ) {
		$default = [
			'client_id' => $this->get_client_id(),
		];

		$args = wp_parse_args( $args, $default );

		// Get the vote result.
		$result = Votes::get_instance()->get_ip_votes( $args );

		return $result;
	}

	/**
	 * Common validation for voting.
	 *
	 * @param array $options Vote options.
	 *
	 * @return true|WP_Error
	 */
	protected function validate_vote_request( array $options ) {
		$settings = $this->get_settings();

		if ( empty( $options ) ) {
			return new WP_Error( 'empty-options', __( 'Options are empty.', 'poll-creator' ), [ 'status' => 400 ] );
		}

		if ( $this->is_poll_closed() ) {
			return new WP_Error( 'poll-closed', wp_kses_post( $settings['closePollmessage'] ?? __( 'This poll is closed', 'poll-creator' ) ), [ 'status' => 400 ] );
		}

		$require_login = ! empty( $settings['requireLogin'] );

		if ( $require_login && ! is_user_logged_in() ) {
			return new WP_Error(
				'login-required',
				wp_kses_post( $settings['requireLoginMessage'] ?? __( 'Please log in to vote.', 'poll-creator' ) ),
				[ 'status' => 403 ]
			);
		}

		$voter = new Voter();

		// Check if anonymous voting is enabled.
		$is_anonymous = ! empty( $settings['anonymousVoting'] );

		// When requireLogin is on, always check server-side by user_id (even if anonymous).
		// When requireLogin is off, only check server-side if NOT anonymous.
		$should_check_server_side = ! empty( $settings['allowedPerComputerResponse'] )
			&& ( $require_login || ! $is_anonymous );

		if ( $should_check_server_side && $voter->is_already_voted( $this->get_client_id() ) ) {
			return new WP_Error( 'already-voted', __( 'You have already voted.', 'poll-creator' ), [ 'status' => 400 ] );
		}

		// Now introduce a filter to allow overriding the validation.
		return apply_filters(
			'pollify_feedback_validate_vote_request',
			true,
			$this,
			$options
		);
	}
}
