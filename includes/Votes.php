<?php
/**
 * Vote class.
 *
 * Handle all vote CRUD operation in one class.
 *
 * @package UnderDev\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace UnderDev\Pollify;

use WP_Error;
use UnderDev\Pollify\Model\Voter;
use UnderDev\Pollify\Traits\Singleton;

/**
 * Class Votes.
 *
 * Handle all vote CRUD operation in one class.
 */
class Votes {
	use Singleton;

	/**
	 * Poll table name.
	 *
	 * @var string
	 */
	private string $table_name = 'pollify_vote';

	/**
	 * Set a vote for a poll.
	 *
	 * @param array $args
	 *
	 */
	public function vote( array $args = [] ) {
		global $wpdb;

		$defaults = [
			'client_id'  => 0,
			'option_ids' => [],
			'user_id'    => 0,
			'user_ip'    => '',
			'user_agent' => '',
			'created_at' => current_time( 'mysql' ),
		];

		$args = wp_parse_args( $args, $defaults );

		// Check if poll_id and option_id empty or not.
		if ( empty( $args['client_id'] ) || empty( $args['option_ids'] ) ) {
			return new WP_Error( 'empty_poll_id_or_option_id', __( 'Poll ID or Option ID is empty.', 'pollify' ) );
		}

		$poll = Polls::get_instance()->get( $args['client_id'] );

		// Checking if poll exist or not.
		if ( ! $poll || is_wp_error( $poll ) ) {
			return new WP_Error( 'poll_not_exist', __( 'Invalid poll data.', 'pollify' ) );
		}

		// Checking if valid poll option or not.
		if ( ! is_array( $args['option_ids'] ) || ! $poll->is_valid_poll_option( (array) $args['option_ids'] ) ) {
			return new WP_Error( 'invalid_poll_option', __( 'Invalid poll option.', 'pollify' ) );
		}

		// Get user data from Voter class.
		$voter = new Voter();

		// Set all user parameters.
		$args['user_id']       = $voter->get_user_id();
		$args['user_ip']       = $voter->get_user_ip();
		$args['user_agent']    = $voter->get_user_agent();
		$args['user_location'] = $voter->get_user_country();

		// Loop through all option ids and set vote for each option.
		foreach ( $args['option_ids'] ?? [] as $option_id ) {
			// Insert vote data into database.
			$inserted = $wpdb->insert(
				$wpdb->prefix . $this->table_name,
				[
					'client_id'     => $args['client_id'],
					'option_id'     => $option_id,
					'user_id'       => $args['user_id'],
					'user_ip'       => $args['user_ip'],
					'user_location' => $args['user_location'],
					'user_agent'    => $args['user_agent'],
					'created_at'    => $args['created_at'],
				],
				[
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
				]
			);

			if ( ! $inserted ) {
				return new WP_Error( 'vote_not_inserted', __( 'Sorry vote not accepted.', 'pollify' ) );
			}
		}

		$vote_data       = $args;
		$vote_data['id'] = $wpdb->insert_id;

		// Return success message.
		return $vote_data;
	}

	/**
	 * Get vote table name.
	 *
	 * @param string $client_id Poll client ID.
	 *
	 * @return array
	 */
	public function get_user_votes( string $client_id ): array|WP_Error {
		global $wpdb;

		// Get user data from Voter class.
		$voter = new Voter();

		// Get user ID.
		$user_id = $voter->get_user_id();

		// Get user IP.
		$user_ip = $voter->get_user_ip();

		// Get vote data.
		$votes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}{$this->table_name} WHERE client_id = %s AND (user_id = %d OR user_ip = %s) ORDER BY created_at DESC",
				$client_id,
				$user_id,
				$user_ip
			),
			ARRAY_A
		);

		return $votes ?? [];
	}

	/**
	 * Get votes for specific poll.
	 * By default it will return latest 15 votes. Rest of the other things will be loaded via pagination.
	 *
	 * @param array $args Argument for getting votes.
	 *
	 * @return array|int
	 */
	public function get_votes( $args = [] ) : array|int {
		global $wpdb;

		$default = [
			'client_id' => '',
			'per_page'  => 15,
			'page'      => 1,
			'orderby'   => 'created_at',
			'order'     => 'DESC',
		];

		$args = wp_parse_args( $args, $default );

		if ( empty( $args['client_id'] ) ) {
			return [];
		}

		// Create some where condition regarding status, type, search etc.
		$where = 'WHERE 1=1';

		// Check if client_id is empty or not.
		if ( ! empty( $args['client_id'] ) ) {
			$where .= $wpdb->prepare( " AND v.client_id = %s", $args['client_id'] );
		}

		// Check if location is avaialbe or not.
		if ( ! empty( $args['location'] ) ) {
			$where .= $wpdb->prepare( " AND v.user_location = %s", $args['location'] );
		}

		// Check if option is avaible for filter.
		if ( ! empty( $args['option'] ) ) {
			$where .= $wpdb->prepare( " AND o.option_id = %s", $args['option'] );
		}

		// If search is set then add where condition for search.
		if ( ! empty( $args['search'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_ip LIKE %s', '%' . $args['search'] . '%' );
		}

		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		if ( ! empty( $args['count'] ) && $args['count'] ) {
			// Get vote data.
			$votes = $wpdb->get_var(
				"SELECT COUNT(v.id), o.option, o.option_id FROM {$wpdb->prefix}{$this->table_name} v LEFT JOIN {$wpdb->prefix}pollify_poll_options o ON v.option_id = o.option_id {$where}",
			);

			return intval( $votes ) ?? 0;
		}

		// Prepare the sql query
		$votes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT v.*, o.option, o.option_id FROM {$wpdb->prefix}{$this->table_name} v LEFT JOIN {$wpdb->prefix}pollify_poll_options o ON v.option_id = o.option_id {$where} ORDER BY v.{$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
				$args['per_page'],
				$offset
			),
			ARRAY_A
		);

		return $votes ?? [];
	}

	/**
	 * Get results for a poll.
	 *
	 * @param string $client_id Poll client ID.
	 *
	 * @return array
	 */
	public function get_results( string $client_id ): array {
		global $wpdb;

		// Get poll options.
		$poll    = Polls::get_instance()->get( $client_id );
		$options = ! is_wp_error( $poll ) ? $poll->get_options() : [];

		// Get vote data.
		$votes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_id, COUNT(*) as votes FROM {$wpdb->prefix}{$this->table_name} WHERE client_id = %s GROUP BY option_id",
				$client_id
			),
			ARRAY_A
		);

		$total_votes = array_sum( wp_list_pluck( $votes, 'votes' ) );

		if ( ! empty( $options ) ) {
			// Loop through all options and set total votes for each option.
			foreach ( $options as $key => $option ) {
				$options[ $key ]['votes']      = 0;
				$options[ $key ]['percentage'] = 0;

				foreach ( $votes as $vote ) {
					if ( $option['option_id'] === $vote['option_id'] ) {
						$options[ $key ]['votes'] = (int) $vote['votes'];

						// Calculate percentage.
						$options[ $key ]['percentage'] = (int) $vote['votes'] > 0 ? number_format_i18n( ( (int) $vote['votes'] / (int) $total_votes ) * 100, 2 ) : 0;

					}
				}
			}
		}

		$results = [
			'total_votes'  => intval( $total_votes ),
			'voter_counts' => count( $votes ),
			'options'      => $options ?? [],
		];

		return $results ?? [];
	}

	/**
	 * Get votes group by location.
	 *
	 * @param string   $client_id Poll client ID.
	 * @param int|null $no_of_list Number of list.
	 *
	 * @return array
	 */
	public function get_votes_group_by_location( string $client_id, $no_of_list = null ): array {
		global $wpdb;

		// Check if no_of_list is empty or not.
		$limit = ! empty( $no_of_list ) ? 'LIMIT ' . $no_of_list : '';

		// Get vote data.
		$votes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_location as location, COUNT(*) as votes FROM {$wpdb->prefix}{$this->table_name} WHERE client_id = %d GROUP BY user_location ORDER BY votes DESC {$limit}",
				$client_id
			),
			ARRAY_A
		);

		return $votes ?? [];
	}

	/**
	 * Get votes group by IP.
	 *
	 * @param string $client_id Poll client ID.
	 *
	 * @return array|int
	 */
	public function get_ip_votes( array $args ): array|int {
		global $wpdb;

		$default = [
			'client_id' => '',
			'per_page'  => 15,
			'page'      => 1,
			'orderby'   => 'created_at',
			'order'     => 'DESC',
		];

		$args = wp_parse_args( $args, $default );

		if ( empty( $args['client_id'] ) ) {
			return [];
		}

		// Create some where condition regarding status, type, search etc.
		$where = 'WHERE 1=1';

		// Check if client_id is empty or not.
		if ( ! empty( $args['client_id'] ) ) {
			$where .= $wpdb->prepare( " AND v.client_id = %s", $args['client_id'] );
		}

		// Check if client_id is empty or not.
		if ( ! empty( $args['location'] ) ) {
			$where .= $wpdb->prepare( " AND v.user_location = %s", $args['location'] );
		}

		// If search is set then add where condition for search.
		if ( ! empty( $args['search'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_ip LIKE %s', '%' . $args['search'] . '%' );
		}

		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// If count is exist then return the count.
		if ( ! empty( $args['count'] ) && $args['count'] ) {
			// Get vote data.
			$votes = $wpdb->get_var(
				"SELECT COUNT(*) AS total_rows
				FROM (
					SELECT user_ip
					FROM {$wpdb->prefix}{$this->table_name} v
					{$where}
					GROUP BY user_ip
				) AS grouped_ips",
			);

			return intval( $votes ) ?? 0;
		}

		// Get vote data.
		$votes = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_ip as ip, user_location as location, COUNT(*) as votes FROM {$wpdb->prefix}{$this->table_name} v {$where} GROUP BY user_ip ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
				$args['per_page'],
				$offset
			),
			ARRAY_A
		);

		return $votes ?? [];
	}

	/**
	 * Get all vote locations for a poll.
	 *
	 * @param string $client_id Poll client ID.
	 *
	 * @return array
	 */
	public function get_votes_location( string $client_id ): array {
		global $wpdb;

		// Get vote data.
		$locations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT user_location as location FROM {$wpdb->prefix}{$this->table_name} WHERE client_id = %s",
				$client_id
			),
			ARRAY_A
		);

		return $locations ?? [];
	}

	/**
	 * Reset results for a poll.
	 *
	 * @param string $client_id Poll client ID.
	 *
	 * @return bool
	 */
	public function reset_results( string $client_id ): bool {
		global $wpdb;

		// Delete all votes for a poll.
		$deleted = $wpdb->delete(
			$wpdb->prefix . $this->table_name,
			[
				'client_id' => $client_id,
			],
			[
				'%d',
			]
		);

		return (bool) $deleted;
	}

}