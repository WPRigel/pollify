<?php
/**
 * Vote class.
 *
 * Handle all vote CRUD operation in one class.
 *
 * @package wpRigel\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify;

use WP_Error;
use wpRigel\Pollify\Model\Feedback;
use wpRigel\Pollify\Model\Voter;
use wpRigel\Pollify\Traits\Singleton;

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
	 * @param array $args Arguments for setting a vote.
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
			return new WP_Error( 'empty_poll_id_or_option_id', __( 'Poll ID or Option ID is empty.', 'poll-creator' ) );
		}

		$poll = FeedbackManager::get_instance()->get( $args['client_id'] );

		// Checking if poll exist or not.
		if ( ! $poll || is_wp_error( $poll ) ) {
			return new WP_Error( 'poll_not_exist', __( 'Invalid poll data.', 'poll-creator' ) );
		}

		// Checking if valid poll option or not.
		if ( ! is_array( $args['option_ids'] ) || ! $poll->is_valid_poll_option( (array) $args['option_ids'] ) ) {
			return new WP_Error( 'invalid_poll_option', __( 'Invalid poll option.', 'poll-creator' ) );
		}

		// Get user data from Voter class.
		$voter = new Voter();

		// Check if anonymous voting is enabled.
		$settings     = $poll->get_settings();
		$is_anonymous = ! empty( $settings['anonymousVoting'] );

		// Apply filter to allow customization.
		$is_anonymous = apply_filters( 'pollify_is_anonymous_voting', $is_anonymous, $poll, $settings );

		// Set all user parameters.
		if ( $is_anonymous ) {
			// For anonymous voting, don't collect personal data.
			$args['user_id']       = $voter->get_user_id(); // Keep user_id if logged in.
			$args['user_ip']       = null;
			$args['user_agent']    = null;
			$args['user_location'] = null;
		} else {
			// Normal voting - collect all data.
			$args['user_id']       = $voter->get_user_id();
			$args['user_ip']       = $voter->get_user_ip();
			$args['user_agent']    = $voter->get_user_agent();
			$args['user_location'] = $voter->get_user_country();
		}

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
				return new WP_Error( 'vote_not_inserted', __( 'Sorry vote not accepted.', 'poll-creator' ) );
			}
		}

		$vote_data       = $args;
		$vote_data['id'] = $wpdb->insert_id;

		// Reset cache group for rendering the cache again.
		if ( wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( 'pollify_poll_cache' );
			wp_cache_flush_group( 'pollify_vote_cache' );
		}

		// Return success message.
		return $vote_data;
	}

	/**
	 * Get votes for specific poll.
	 * By default it will return latest 15 votes. Rest of the other things will be loaded via pagination.
	 *
	 * @param array $args Argument for getting votes.
	 *
	 * @return array|int
	 */
	public function get_votes( $args = [] ) {
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
		$where = $wpdb->prepare( 'WHERE 1=%d', 1 );

		// Check if client_id is empty or not.
		if ( ! empty( $args['client_id'] ) ) {
			$where .= $wpdb->prepare( ' AND v.client_id = %s', sanitize_text_field( $args['client_id'] ) );
		}

		// Check if location is available or not.
		if ( ! empty( $args['user_id'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_id = %d', sanitize_text_field( $args['user_id'] ) );
		}

		// Check if location is available or not.
		if ( ! empty( $args['location'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_location = %s', sanitize_text_field( $args['location'] ) );
		}

		// Check if ip is available or not.
		if ( ! empty( $args['ip'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_ip = %s', sanitize_text_field( $args['ip'] ) );
		}

		// Check if option is availble for filter.
		if ( ! empty( $args['option'] ) ) {
			$where .= $wpdb->prepare( ' AND o.option_id = %s', sanitize_text_field( $args['option'] ) );
		}

		// If search is set then add where condition for search.
		if ( ! empty( $args['search'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_ip LIKE %s', '%' . sanitize_text_field( $args['search'] ) . '%' );
		}

		// Filters for join, select, where.
		$join_sql   = '';
		$select_var = 'v.*, o.option, o.option_id';
		$where      = apply_filters( 'pollify_votes_where_sql', $where, $args );
		$join_sql   = apply_filters( 'pollify_votes_join_sql', $join_sql, $args );
		$select_var = apply_filters( 'pollify_votes_select_var', $select_var, $args );

		$offset   = ( $args['page'] - 1 ) * $args['per_page'];
		$order_by = sanitize_sql_orderby( "{$args['orderby']} {$args['order']}" );

		if ( ! empty( $args['count'] ) && $args['count'] ) {
			// Implement cache here for count param.
			$cache_count_key = 'pollify_votes_count_' . md5( maybe_serialize( $args ) );
			$votes           = wp_cache_get( $cache_count_key, 'pollify_vote_cache' );

			if ( false === $votes ) {
				// Get vote data.
				$votes = $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"SELECT COUNT(v.id) FROM %i v LEFT JOIN {$wpdb->prefix}pollify_poll_options o ON v.option_id = o.option_id {$join_sql} {$where}",
						$wpdb->prefix . $this->table_name
					)
				);

				wp_cache_set( $cache_count_key, $votes, 'pollify_vote_cache', 15 * MINUTE_IN_SECONDS );
			}

			return intval( $votes ) ?? 0;
		}

		// Implement cache for getting rows.
		$cache_key = 'pollify_votes_' . md5( maybe_serialize( $args ) );
		$votes     = wp_cache_get( $cache_key, 'pollify_vote_cache' );

		if ( false === $votes ) {
			// Prepare the sql query.
			$votes = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT {$select_var} FROM {$wpdb->prefix}{$this->table_name} v LEFT JOIN {$wpdb->prefix}pollify_poll_options o ON v.option_id = o.option_id {$join_sql} {$where} ORDER BY {$order_by} LIMIT %d OFFSET %d",
					$args['per_page'],
					$offset
				),
				ARRAY_A
			);

			wp_cache_set( $cache_key, $votes, 'pollify_vote_cache', 30 * MINUTE_IN_SECONDS );
		}

		return $votes ?? [];
	}

	/**
	 * Get results for a poll.
	 *
	 * @param string|object $feedback Feedback client ID or Feedback object.
	 *
	 * @return array
	 */
	public function get_results( string|object $feedback ): array {
		global $wpdb;

		// Check if feedback is object of Feedback or not.
		if ( is_object( $feedback ) && $feedback instanceof Feedback ) {
			$options = $feedback->get_options();
		} else {
			$feedback = FeedbackManager::get_instance()->get( $feedback );
			$options  = ! is_wp_error( $feedback ) ? $feedback->get_options() : [];
		}

		// If not $feedback object then return empty array.
		if ( is_wp_error( $feedback ) ) {
			return [];
		}

		// Get poll options.

		// Filters for join, select, where.
		$join_sql   = '';
		$select_var = 'option_id, COUNT(*) as votes, COUNT(DISTINCT user_ip) as unique_votes';
		$where      = $wpdb->prepare( 'WHERE v.client_id = %s', $feedback->get_client_id() );
		$where      = apply_filters( 'pollify_results_where_sql', $where, $feedback );
		$join_sql   = apply_filters( 'pollify_results_join_sql', $join_sql, $feedback );
		$select_var = apply_filters( 'pollify_results_select_var', $select_var, $feedback );

		// Implement caching.
		$cache_key = 'pollify_results_' . $feedback->get_client_id();
		$results   = wp_cache_get( $cache_key, 'pollify_vote_cache' );

		if ( false === $results ) {
			// Get vote data.
			$votes = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT {$select_var} FROM {$wpdb->prefix}{$this->table_name} v {$join_sql} {$where} GROUP BY option_id",
				ARRAY_A
			);

			$total_votes        = array_sum( wp_list_pluck( $votes, 'votes' ) );
			$total_unique_votes = array_sum( wp_list_pluck( $votes, 'unique_votes' ) );

			if ( ! empty( $options ) ) {
				// Loop through all options and set total votes for each option.
				foreach ( $options as $key => $option ) {
					$options[ $key ]['votes']        = 0;
					$options[ $key ]['unique_votes'] = 0;
					$options[ $key ]['percentage']   = 0;

					foreach ( $votes as $vote ) {
						if ( $option['option_id'] === $vote['option_id'] ) {
							$options[ $key ]['votes']        = (int) $vote['votes'];
							$options[ $key ]['unique_votes'] = (int) $vote['unique_votes'];

							// Calculate percentage.
							$options[ $key ]['percentage'] = (int) $vote['votes'] > 0 ? number_format_i18n( ( (int) $vote['votes'] / (int) $total_votes ) * 100, 2 ) : 0;
						}
					}
				}
			}

			$results = apply_filters(
				'pollify_get_feedback_results_data',
				[
					'total_votes'        => intval( $total_votes ),
					'total_unique_votes' => intval( $total_unique_votes ),
					'voter_counts'       => count( $votes ),
					'options'            => $options ?? [],
				],
				$feedback
			);

			wp_cache_set( $cache_key, $results, 'pollify_vote_cache', 30 * MINUTE_IN_SECONDS );
		}

		return $results ?? [];
	}

	/**
	 * Get votes group by IP.
	 *
	 * @param array $args Arguments for getting votes.
	 *
	 * @return array|int
	 */
	public function get_ip_votes( array $args ) {
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
		$where = $wpdb->prepare( 'WHERE 1=%d', 1 );

		// Check if client_id is empty or not.
		if ( ! empty( $args['client_id'] ) ) {
			$where .= $wpdb->prepare( ' AND v.client_id = %s', sanitize_text_field( $args['client_id'] ) );
		}

		// Check if client_id is empty or not.
		if ( ! empty( $args['location'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_location = %s', sanitize_text_field( $args['location'] ) );
		}

		// If search is set then add where condition for search.
		if ( ! empty( $args['search'] ) ) {
			$where .= $wpdb->prepare( ' AND v.user_ip LIKE %s', '%' . sanitize_text_field( $args['search'] ) . '%' );
		}

		// Add filter for where SQL, for extensibility.
		$where = apply_filters( 'pollify_ip_votes_where_sql', $where, $args );

		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// Table names.
		$vote_table = $wpdb->prefix . $this->table_name;

		// Dynamic join and select.
		$join_sql   = '';
		$select_var = 'v.user_ip as ip, v.user_location as location, COUNT(*) as votes';

		// Allow filtering join and select for future extension.
		$join_sql   = apply_filters( 'pollify_ip_votes_join_sql', $join_sql, $args );
		$select_var = apply_filters( 'pollify_ip_votes_select_var', $select_var, $args );

		$order_by = sanitize_sql_orderby( "{$args['orderby']} {$args['order']}" );

		// If count is exist then return the count.
		if ( ! empty( $args['count'] ) && $args['count'] ) {
			$cache_count_key = 'pollify_ip_votes_count_' . md5( maybe_serialize( $args ) );
			$votes           = wp_cache_get( $cache_count_key, 'pollify_vote_cache' );

			if ( false === $votes ) {
				// Get vote data.
				$votes = $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"SELECT COUNT(DISTINCT user_ip) FROM %i v {$join_sql} {$where}",
						$vote_table
					)
				);

				wp_cache_set( $cache_count_key, $votes, 'pollify_vote_cache', 15 * MINUTE_IN_SECONDS );
			}

			return intval( $votes ) ?? 0;
		}

		$cache_key = 'pollify_ip_votes_' . md5( maybe_serialize( $args ) );
		$votes     = wp_cache_get( $cache_key, 'pollify_vote_cache' );

		if ( false === $votes ) {
			$votes = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT {$select_var} FROM {$vote_table} v {$join_sql} {$where} GROUP BY v.user_ip ORDER BY {$order_by} LIMIT %d OFFSET %d",
					$args['per_page'],
					$offset
				),
				ARRAY_A
			);

			wp_cache_set( $cache_key, $votes, 'pollify_vote_cache', 30 * MINUTE_IN_SECONDS );
		}

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

		// Implement cache for getting rows.
		$cache_key = 'pollify_votes_location_' . $client_id;
		$locations = wp_cache_get( $cache_key, 'pollify_vote_cache' );

		if ( false !== $locations ) {
			return $locations;
		}

		// Get vote data.
		$locations = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT DISTINCT user_location as location FROM %i WHERE client_id = %s',
				$wpdb->prefix . $this->table_name,
				$client_id
			),
			ARRAY_A
		);

		wp_cache_set( $cache_key, $locations, 'pollify_vote_cache', 15 * MINUTE_IN_SECONDS );

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
				'%s',
			]
		);

		// Reset cache for the poll.
		if ( wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( 'pollify_poll_cache' );
		}

		return (bool) $deleted;
	}

	/**
	 * Remove entry from vote table depending on client ID and user IP.
	 *
	 * @param array $args Arguments for removing vote.
	 *
	 * @return bool|WP_Error
	 */
	public function remove_vote( array $args = [] ) {
		global $wpdb;

		$defaults = [
			'client_id' => '',
			'user_ip'   => '',
		];

		$args = wp_parse_args( $args, $defaults );

		if ( empty( $args['client_id'] ) || empty( $args['user_ip'] ) ) {
			return new WP_Error( 'empty_client_id_or_user_ip', __( 'Client ID or User IP is empty.', 'poll-creator' ) );
		}

		// Delete vote from database.
		$deleted = $wpdb->delete(
			$wpdb->prefix . $this->table_name,
			[
				'client_id' => $args['client_id'],
				'user_ip'   => $args['user_ip'],
			],
			[
				'%s',
				'%s',
			]
		);

		// Reset cache for the poll.
		if ( wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( 'pollify_poll_cache' );
		}

		return (bool) $deleted;
	}

	/**
	 * Delete a single vote row by ID.
	 *
	 * @param int $id Vote row ID.
	 *
	 * @return bool|WP_Error
	 */
	public function delete_vote_by_id( int $id = 0 ) {
		global $wpdb;

		if ( $id <= 0 ) {
			return new WP_Error( 'invalid_vote_id', __( 'Invalid vote ID.', 'poll-creator' ) );
		}

		$deleted = $wpdb->delete(
			$wpdb->prefix . $this->table_name,
			[
				'id' => $id,
			],
			[
				'%d',
			]
		);

		if ( wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( 'pollify_poll_cache' );
			wp_cache_flush_group( 'pollify_vote_cache' );
		}

		/**
		 * Fires after a single vote has been deleted.
		 *
		 * @param int  $id      Vote ID.
		 * @param bool $deleted Whether the delete was successful.
		 */
		do_action( 'pollify_vote_deleted', $id, (bool) $deleted );

		return (bool) $deleted;
	}
}
