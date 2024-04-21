<?php
/**
 * Voter model class.
 *
 * @package wpRigel\Pollify
 */

declare(strict_types=1);

namespace wpRigel\Pollify\Model;

use wpRigel\Pollify\Votes;

/**
 * Class Voter.
 *
 * Handle a single Voter object with all its data.
 */
class Voter {

	/**
	 * Voter data.
	 *
	 * @var array
	 */
	private array $data = [
		'user_id'    => 0,
		'user_ip'    => '',
		'user_agent' => '',
	];

	/**
	 * Voter constructor.
	 */
	public function __construct() {
		// Check if user is logged in or not.
		$this->data['user_id'] = $this->get_user_id();

		// Get user IP.
		$this->data['user_ip'] = $this->get_user_ip();

		// Get user agent.
		$this->data['user_agent'] = $this->get_user_agent();
	}

	/**
	 * Get user ID.
	 *
	 * @return int
	 */
	public function get_user_id(): int {
		return is_user_logged_in() ? get_current_user_id() : 0;
	}

	/**
	 * Get user IP.
	 *
	 * @return string
	 */
	public function get_user_ip(): string {
		// Get user IP address.
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			// Check IP from internet.
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// Check IP is passed from proxy.
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			// Get IP address.
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return $ip ?? '';
	}

	/**
	 * Get user country depending on IP.
	 *
	 * @return string
	 */
	public function get_user_country(): string {
		$url  = 'http://www.geoplugin.net/json.gp?ip=' . $this->get_user_ip();
		$data = wp_remote_get( $url );

		if ( ! is_wp_error( $data ) ) {
			// Get the body of the response.
			$body     = wp_remote_retrieve_body( $data );
			$response = json_decode( $body, true );
		}

		return $response['geoplugin_countryCode'] ?? '';
	}

	/**
	 * Get user agent.
	 *
	 * @return string
	 */
	public function get_user_agent(): string {
		return $_SERVER['HTTP_USER_AGENT'] ?? '';
	}

	/**
	 * Get user votes.
	 *
	 * @param string $client_id Poll client id.
	 *
	 * @return array
	 */
	public function get_votes( string $client_id ): array {
		return Votes::get_instance()->get_votes( [ 'client_id' => $client_id ] );
	}

	/**
	 * Is user already voted or not.
	 *
	 * @param string $client_id Poll client ID.
	 *
	 * @return boolean
	 */
	public function is_already_voted( string $client_id ): bool {
		$votes = Votes::get_instance()->get_votes(
			[
				'per_page'  => 1,
				'client_id' => $client_id,
				'user_id'   => $this->get_user_id(),
			]
		);

		if ( ! empty( $votes ) ) {
			return true;
		}

		$votes = Votes::get_instance()->get_ip_votes(
			[
				'per_page'  => 1,
				'client_id' => $client_id,
				'user_ip'   => $this->get_user_ip(),
			]
		);

		if ( ! empty( $votes ) ) {
			return true;
		}

		return false;
	}
}
