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
	 * Function to check if an IP address is from localhost or a local network.
	 *
	 * @param string $ip The IP address to check.
	 *
	 * @return bool True if the IP is from localhost or a local network, false otherwise.
	 */
	public function is_local_ip( $ip ) {
		// Localhost.
		if ( '127.0.0.1' === $ip || '::1' === $ip ) {
			return true;
		}

		// Local network IP ranges.
		$local_ip_ranges = [
			'10.0.0.0|10.255.255.255',        // Class A private network.
			'172.16.0.0|172.31.255.255',      // Class B private network.
			'192.168.0.0|192.168.255.255',    // Class C private network.
			'169.254.0.0|169.254.255.255',    // Link-local address (APIPA).
		];

		$long_ip = ip2long( $ip );

		if ( false !== $long_ip ) {
			foreach ( $local_ip_ranges as $range ) {
				list( $start, $end ) = explode( '|', $range );

				if ( $long_ip >= ip2long( $start ) && $long_ip <= ip2long( $end ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get user IP.
	 *
	 * @return string
	 */
	public function get_user_ip(): string {
		$ip = '';

		if ( apply_filters( 'pollify_trust_proxy_headers', false ) ) {
			if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) && filter_var( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ), FILTER_VALIDATE_IP ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
			} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$forwarded_ips = explode( ',', wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );

				foreach ( $forwarded_ips as $forwarded_ip ) {
					$forwarded_ip = trim( $forwarded_ip );

					if ( filter_var( $forwarded_ip, FILTER_VALIDATE_IP ) ) {
						$ip = sanitize_text_field( $forwarded_ip );
						break;
					}
				}
			}
		}

		if ( empty( $ip ) && isset( $_SERVER['REMOTE_ADDR'] ) && filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		// If the IP is from localhost/private range, return as is.
		if ( $this->is_local_ip( $ip ) ) {
			return $ip;
		}

		// Validate IP to ensure it's not from a reserved range.
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return $ip;
		}

		return '127.0.0.1'; // Default to localhost IP if no valid IP found.
	}

	/**
	 * Get user country depending on IP.
	 *
	 * @return string
	 */
	public function get_user_country(): string {
		$ip = $this->get_user_ip();

		// If someone is using localhost or local network then don't need to pass the IP.
		// Geoplugin will get automatically IP location from the server.
		if ( $this->is_local_ip( $ip ) ) {
			$ip = '';
		}

		static $cache = [];

		if ( isset( $cache[ $ip ] ) ) {
			return $cache[ $ip ];
		}

		$url      = 'https://ipinfo.io/' . $ip . '/json';
		$data     = wp_remote_get( $url, [ 'timeout' => 3 ] );
		$response = [];

		if ( ! is_wp_error( $data ) ) {
			// Get the body of the response.
			$body     = wp_remote_retrieve_body( $data );
			$response = json_decode( $body, true ) ?? [];
		}

		$cache[ $ip ] = $response['country'] ?? '';

		return $cache[ $ip ];
	}

	/**
	 * Get user agent.
	 *
	 * @return string
	 */
	public function get_user_agent(): string {
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
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

		$vote_args = [
			'per_page'  => 1,
			'client_id' => $client_id,
		];

		// Check if user is logged in or not. If logged then check the user ID. If not then check the user IP.
		if ( $this->get_user_id() > 0 ) {
			$vote_args['user_id'] = $this->get_user_id();
		} else {
			$vote_args['ip'] = $this->get_user_ip();
		}

		$votes = Votes::get_instance()->get_votes( $vote_args );

		if ( ! empty( $votes ) ) {
			return true;
		}

		// For logged-in users, also check by IP to catch votes cast before login.
		if ( $this->get_user_id() > 0 ) {
			$votes = Votes::get_instance()->get_votes(
				[
					'per_page'  => 1,
					'client_id' => $client_id,
					'ip'        => $this->get_user_ip(),
				]
			);

			if ( ! empty( $votes ) ) {
				return true;
			}
		}

		return false;
	}
}
