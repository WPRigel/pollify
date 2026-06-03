<?php
/**
 * Tests for Votes class.
 *
 * @package wpRigel\Pollify\Tests\Unit
 */

declare(strict_types=1);

namespace wpRigel\Pollify\Tests\Unit;

use Brain\Monkey\Functions;
use WP_Error;
use wpRigel\Pollify\Model\Poll;
use wpRigel\Pollify\Votes;

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

/**
 * Class VotesTest.
 */
class VotesTest extends AbstractTestCase {

	private const CLIENT_ID = 'aaaabbbb-cccc-dddd-eeee-ffffaaaabbbb';

	/** @var object */
	private object $wpdb_mock;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'maybe_serialize' )->alias(
			function ( $value ) {
				return ( is_array( $value ) || is_object( $value ) ) ? serialize( $value ) : $value;
			}
		);

		Functions\when( 'wp_parse_args' )->alias(
			function ( $args, $defaults = [] ) {
				return array_merge( (array) $defaults, (array) $args );
			}
		);

		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\when( 'wp_cache_set' )->justReturn( true );
		Functions\when( 'wp_cache_supports' )->justReturn( false );
		Functions\when( 'wp_cache_flush_group' )->justReturn( true );

		Functions\when( 'apply_filters' )->alias(
			function ( string $tag, $value ) {
				return $value;
			}
		);

		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'current_time' )->justReturn( '2024-01-01 00:00:00' );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'sanitize_sql_orderby' )->returnArg();
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'wp_remote_get' )->justReturn( new WP_Error( 'no-remote', 'No remote in tests' ) );

		Functions\when( 'wp_list_pluck' )->alias(
			function ( array $list, string $field ) {
				return array_column( $list, $field );
			}
		);

		Functions\when( 'number_format_i18n' )->alias(
			function ( $number, int $decimals = 0 ) {
				return number_format( (float) $number, $decimals );
			}
		);

		$this->wpdb_mock = $this->make_wpdb();

		global $wpdb;
		$wpdb = $this->wpdb_mock;
	}

	protected function tearDown(): void {
		global $wpdb;
		$wpdb = null;
		parent::tearDown();
	}

	// ----------------------------------------------------------------
	// Helpers
	// ----------------------------------------------------------------

	private function make_wpdb(): object {
		return new class {
			public string $prefix      = 'wp_';
			public int    $insert_id   = 0;
			public int    $get_row_idx = 0;
			public int    $get_res_idx = 0;
			public int    $get_res_calls = 0;

			/** @var list<array|null> */
			public array $get_row_seq = [];
			/** @var list<array> */
			public array $get_res_seq = [];

			public $get_var_val   = null;
			public $insert_val    = 1;
			public $delete_val    = 1;

			/** @var array|null Last data passed to insert() */
			public ?array $last_insert = null;

			public function prepare( string $sql, ...$args ): string {
				return $sql;
			}

			public function get_row( $sql, $output = null ): mixed {
				return $this->get_row_seq[ $this->get_row_idx++ ] ?? null;
			}

			public function get_results( $sql, $output = null ): array {
				$this->get_res_calls++;
				return $this->get_res_seq[ $this->get_res_idx++ ] ?? [];
			}

			public function get_var( $sql ): mixed {
				return $this->get_var_val;
			}

			public function insert( $table, $data, $format = null ): int|false {
				$this->last_insert = $data;
				return $this->insert_val;
			}

			public function delete( $table, $where, $format = null ): int|false {
				return $this->delete_val;
			}

			public function esc_like( string $text ): string {
				return $text;
			}
		};
	}

	/**
	 * Returns a raw poll data array suitable for constructing a Poll or caching.
	 */
	private function poll_data( array $settings = [] ): array {
		return [
			'id'          => 1,
			'client_id'   => self::CLIENT_ID,
			'title'       => 'Test Poll',
			'description' => '',
			'type'        => 'poll',
			'status'      => 'publish',
			'reference'   => '',
			'created_at'  => '2024-01-01 00:00:00',
			'updated_at'  => '2024-01-01 00:00:00',
			'settings'    => wp_json_encode( array_merge( [ 'anonymousVoting' => false ], $settings ) ),
			'response'    => 0,
			'options'     => [
				[ 'id' => 1, 'option_id' => 'opt1', 'type' => 'text', 'option' => 'Option 1' ],
				[ 'id' => 2, 'option_id' => 'opt2', 'type' => 'text', 'option' => 'Option 2' ],
			],
		];
	}

	/**
	 * Stubs wp_cache_get to return poll data for the poll cache key so
	 * FeedbackManager::get() succeeds without hitting the DB.
	 */
	private function mock_poll_cache( array $settings = [] ): void {
		$data = $this->poll_data( $settings );
		Functions\when( 'wp_cache_get' )->alias(
			function ( $key, $group = '' ) use ( $data ) {
				if ( 'poll_' . self::CLIENT_ID === $key && 'pollify_poll_cache' === $group ) {
					return $data;
				}
				return false;
			}
		);
	}

	/** Builds an actual Poll instance for use as a Feedback object. */
	private function build_poll( array $settings = [] ): Poll {
		return new Poll( $this->poll_data( $settings ) );
	}

	// ----------------------------------------------------------------
	// vote()
	// ----------------------------------------------------------------

	public function test_vote_returns_error_when_client_id_empty(): void {
		$result = Votes::get_instance()->vote( [ 'option_ids' => [ 'opt1' ] ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'empty_poll_id_or_option_id', $result->get_error_code() );
	}

	public function test_vote_returns_error_when_option_ids_empty(): void {
		$result = Votes::get_instance()->vote( [ 'client_id' => self::CLIENT_ID ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'empty_poll_id_or_option_id', $result->get_error_code() );
	}

	public function test_vote_returns_error_when_poll_not_found(): void {
		// Default: wp_cache_get returns false, $wpdb->get_row returns null.
		// FeedbackManager::get() returns WP_Error('not-found').
		$result = Votes::get_instance()->vote( [
			'client_id'  => self::CLIENT_ID,
			'option_ids' => [ 'opt1' ],
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'poll_not_exist', $result->get_error_code() );
	}

	public function test_vote_returns_error_when_invalid_poll_option(): void {
		$this->mock_poll_cache();

		$result = Votes::get_instance()->vote( [
			'client_id'  => self::CLIENT_ID,
			'option_ids' => [ 'nonexistent_option' ],
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_poll_option', $result->get_error_code() );
	}

	public function test_vote_anonymous_voting_nullifies_personal_data(): void {
		$this->mock_poll_cache( [ 'anonymousVoting' => true ] );
		$this->wpdb_mock->insert_val = 1;
		$this->wpdb_mock->insert_id  = 5;

		$result = Votes::get_instance()->vote( [
			'client_id'  => self::CLIENT_ID,
			'option_ids' => [ 'opt1' ],
		] );

		$this->assertIsArray( $result );
		$this->assertNull( $result['user_ip'] );
		$this->assertNull( $result['user_agent'] );
		$this->assertNull( $result['user_location'] );
	}

	public function test_vote_non_anonymous_populates_user_fields(): void {
		$this->mock_poll_cache( [ 'anonymousVoting' => false ] );
		$this->wpdb_mock->insert_val = 1;
		$this->wpdb_mock->insert_id  = 1;

		$result = Votes::get_instance()->vote( [
			'client_id'  => self::CLIENT_ID,
			'option_ids' => [ 'opt1' ],
		] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'user_id', $result );
		$this->assertArrayHasKey( 'user_ip', $result );
		$this->assertArrayHasKey( 'user_agent', $result );
		$this->assertArrayHasKey( 'user_location', $result );
		$this->assertNotNull( $result['user_ip'] );
		$this->assertSame( 'PHPUnit', $result['user_agent'] );
	}

	public function test_vote_returns_error_when_insert_fails(): void {
		$this->mock_poll_cache();
		$this->wpdb_mock->insert_val = false;

		$result = Votes::get_instance()->vote( [
			'client_id'  => self::CLIENT_ID,
			'option_ids' => [ 'opt1' ],
		] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'vote_not_inserted', $result->get_error_code() );
	}

	public function test_vote_returns_data_array_with_id_on_success(): void {
		$this->mock_poll_cache();
		$this->wpdb_mock->insert_val = 1;
		$this->wpdb_mock->insert_id  = 42;

		$result = Votes::get_instance()->vote( [
			'client_id'  => self::CLIENT_ID,
			'option_ids' => [ 'opt1' ],
		] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'id', $result );
		$this->assertSame( 42, $result['id'] );
	}

	public function test_vote_flushes_both_cache_groups_on_success(): void {
		$this->mock_poll_cache();
		$this->wpdb_mock->insert_val = 1;
		$this->wpdb_mock->insert_id  = 1;

		Functions\when( 'wp_cache_supports' )->justReturn( true );

		$flushed = [];
		Functions\when( 'wp_cache_flush_group' )->alias(
			function ( string $group ) use ( &$flushed ) {
				$flushed[] = $group;
			}
		);

		Votes::get_instance()->vote( [
			'client_id'  => self::CLIENT_ID,
			'option_ids' => [ 'opt1' ],
		] );

		$this->assertContains( 'pollify_poll_cache', $flushed );
		$this->assertContains( 'pollify_vote_cache', $flushed );
	}

	// ----------------------------------------------------------------
	// get_votes()
	// ----------------------------------------------------------------

	public function test_get_votes_returns_empty_array_when_client_id_empty(): void {
		$result = Votes::get_instance()->get_votes( [] );
		$this->assertSame( [], $result );
	}

	public function test_get_votes_returns_paginated_results_with_defaults(): void {
		$rows = [
			[ 'id' => 1, 'client_id' => self::CLIENT_ID, 'option' => 'Option 1', 'option_id' => 'opt1' ],
			[ 'id' => 2, 'client_id' => self::CLIENT_ID, 'option' => 'Option 2', 'option_id' => 'opt2' ],
		];
		$this->wpdb_mock->get_res_seq = [ $rows ];

		$result = Votes::get_instance()->get_votes( [ 'client_id' => self::CLIENT_ID ] );

		$this->assertIsArray( $result );
		$this->assertCount( 2, $result );
	}

	public function test_get_votes_returns_count_as_int_when_count_true(): void {
		$this->wpdb_mock->get_var_val = '7';

		$result = Votes::get_instance()->get_votes( [
			'client_id' => self::CLIENT_ID,
			'count'     => true,
		] );

		$this->assertSame( 7, $result );
	}

	public function test_get_votes_uses_cache_group_pollify_vote_cache(): void {
		$cached = [ [ 'id' => 99, 'option_id' => 'opt1' ] ];
		Functions\when( 'wp_cache_get' )->justReturn( $cached );

		$result = Votes::get_instance()->get_votes( [ 'client_id' => self::CLIENT_ID ] );

		$this->assertSame( $cached, $result );
		$this->assertSame( 0, $this->wpdb_mock->get_res_calls );
	}

	public function test_get_votes_applies_where_filters(): void {
		$this->wpdb_mock->get_res_seq = [
			[ [ 'id' => 1, 'client_id' => self::CLIENT_ID, 'option' => 'Opt1', 'option_id' => 'opt1' ] ],
		];

		$result = Votes::get_instance()->get_votes( [
			'client_id' => self::CLIENT_ID,
			'user_id'   => 5,
			'location'  => 'US',
			'ip'        => '1.2.3.4',
			'option'    => 'opt1',
			'search'    => '1.2',
		] );

		$this->assertIsArray( $result );
	}

	public function test_get_votes_returns_cached_result_without_hitting_db(): void {
		$cached = [ [ 'id' => 10, 'option_id' => 'opt2' ] ];
		Functions\when( 'wp_cache_get' )->justReturn( $cached );

		Votes::get_instance()->get_votes( [ 'client_id' => self::CLIENT_ID ] );
		Votes::get_instance()->get_votes( [ 'client_id' => self::CLIENT_ID ] );

		$this->assertSame( 0, $this->wpdb_mock->get_res_calls );
	}

	// ----------------------------------------------------------------
	// get_results()
	// ----------------------------------------------------------------

	public function test_get_results_returns_empty_array_when_feedback_is_wp_error(): void {
		// Default: get_row returns null → FeedbackManager returns WP_Error.
		$result = Votes::get_instance()->get_results( self::CLIENT_ID );
		$this->assertSame( [], $result );
	}

	public function test_get_results_accepts_feedback_object_directly(): void {
		$poll                         = $this->build_poll();
		$this->wpdb_mock->get_res_seq = [
			[
				[ 'option_id' => 'opt1', 'votes' => 2, 'unique_votes' => 2 ],
			],
		];

		$result = Votes::get_instance()->get_results( $poll );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'total_votes', $result );
	}

	public function test_get_results_accepts_client_id_string(): void {
		$this->mock_poll_cache();
		$this->wpdb_mock->get_res_seq = [
			[
				[ 'option_id' => 'opt1', 'votes' => 1, 'unique_votes' => 1 ],
			],
		];

		$result = Votes::get_instance()->get_results( self::CLIENT_ID );

		$this->assertIsArray( $result );
		$this->assertSame( 1, $result['total_votes'] );
	}

	public function test_get_results_calculates_percentage_per_option(): void {
		$poll = $this->build_poll();

		// opt1: 3 votes, opt2: 1 vote → total 4 → 75 % / 25 %.
		$this->wpdb_mock->get_res_seq = [
			[
				[ 'option_id' => 'opt1', 'votes' => 3, 'unique_votes' => 3 ],
				[ 'option_id' => 'opt2', 'votes' => 1, 'unique_votes' => 1 ],
			],
		];

		$result  = Votes::get_instance()->get_results( $poll );
		$options = $result['options'];

		$opt1 = current( array_filter( $options, fn( $o ) => 'opt1' === $o['option_id'] ) );
		$opt2 = current( array_filter( $options, fn( $o ) => 'opt2' === $o['option_id'] ) );

		$this->assertSame( '75.00', $opt1['percentage'] );
		$this->assertSame( '25.00', $opt2['percentage'] );
	}

	public function test_get_results_zeros_options_with_no_votes(): void {
		$poll = $this->build_poll();

		// Only opt1 voted; opt2 should show votes=0.
		$this->wpdb_mock->get_res_seq = [
			[
				[ 'option_id' => 'opt1', 'votes' => 2, 'unique_votes' => 1 ],
			],
		];

		$result  = Votes::get_instance()->get_results( $poll );
		$options = $result['options'];
		$opt2    = current( array_filter( $options, fn( $o ) => 'opt2' === $o['option_id'] ) );

		$this->assertSame( 0, $opt2['votes'] );
		$this->assertSame( 0, $opt2['unique_votes'] );
		$this->assertSame( 0, $opt2['percentage'] );
	}

	public function test_get_results_returns_cached_result_without_hitting_db(): void {
		$poll      = $this->build_poll();
		$cache_key = 'pollify_results_' . self::CLIENT_ID;
		$cached    = [
			'total_votes'        => 5,
			'total_unique_votes' => 4,
			'voter_counts'       => 3,
			'options'            => [],
		];

		Functions\when( 'wp_cache_get' )->alias(
			function ( $key ) use ( $cached, $cache_key ) {
				return $key === $cache_key ? $cached : false;
			}
		);

		$result = Votes::get_instance()->get_results( $poll );

		$this->assertSame( $cached, $result );
		$this->assertSame( 0, $this->wpdb_mock->get_res_calls );
	}

	public function test_get_results_contains_required_keys(): void {
		$poll                         = $this->build_poll();
		$this->wpdb_mock->get_res_seq = [
			[
				[ 'option_id' => 'opt1', 'votes' => 1, 'unique_votes' => 1 ],
			],
		];

		$result = Votes::get_instance()->get_results( $poll );

		$this->assertArrayHasKey( 'total_votes', $result );
		$this->assertArrayHasKey( 'total_unique_votes', $result );
		$this->assertArrayHasKey( 'voter_counts', $result );
		$this->assertArrayHasKey( 'options', $result );
	}

	// ----------------------------------------------------------------
	// get_ip_votes()
	// ----------------------------------------------------------------

	public function test_get_ip_votes_returns_empty_array_when_client_id_empty(): void {
		$result = Votes::get_instance()->get_ip_votes( [] );
		$this->assertSame( [], $result );
	}

	public function test_get_ip_votes_returns_count_as_int_when_count_true(): void {
		$this->wpdb_mock->get_var_val = '3';

		$result = Votes::get_instance()->get_ip_votes( [
			'client_id' => self::CLIENT_ID,
			'count'     => true,
		] );

		$this->assertSame( 3, $result );
	}

	public function test_get_ip_votes_returns_rows_with_ip_location_votes_keys(): void {
		$rows = [
			[ 'ip' => '1.2.3.4', 'location' => 'US', 'votes' => '2' ],
			[ 'ip' => '5.6.7.8', 'location' => 'GB', 'votes' => '1' ],
		];
		$this->wpdb_mock->get_res_seq = [ $rows ];

		$result = Votes::get_instance()->get_ip_votes( [ 'client_id' => self::CLIENT_ID ] );

		$this->assertCount( 2, $result );
		$this->assertArrayHasKey( 'ip', $result[0] );
		$this->assertArrayHasKey( 'location', $result[0] );
		$this->assertArrayHasKey( 'votes', $result[0] );
	}

	public function test_get_ip_votes_respects_location_and_search_filters(): void {
		$this->wpdb_mock->get_res_seq = [
			[ [ 'ip' => '1.2.3.4', 'location' => 'US', 'votes' => '1' ] ],
		];

		$result = Votes::get_instance()->get_ip_votes( [
			'client_id' => self::CLIENT_ID,
			'location'  => 'US',
			'search'    => '1.2',
		] );

		$this->assertIsArray( $result );
	}

	public function test_get_ip_votes_uses_cache_group_pollify_vote_cache(): void {
		$cached = [ [ 'ip' => '9.9.9.9', 'location' => 'FR', 'votes' => '5' ] ];
		Functions\when( 'wp_cache_get' )->justReturn( $cached );

		$result = Votes::get_instance()->get_ip_votes( [ 'client_id' => self::CLIENT_ID ] );

		$this->assertSame( $cached, $result );
		$this->assertSame( 0, $this->wpdb_mock->get_res_calls );
	}

	// ----------------------------------------------------------------
	// get_votes_location()
	// ----------------------------------------------------------------

	public function test_get_votes_location_returns_cached_result_immediately(): void {
		$cached = [ [ 'location' => 'US' ], [ 'location' => 'GB' ] ];
		Functions\when( 'wp_cache_get' )->justReturn( $cached );

		$result = Votes::get_instance()->get_votes_location( self::CLIENT_ID );

		$this->assertSame( $cached, $result );
		$this->assertSame( 0, $this->wpdb_mock->get_res_calls );
	}

	public function test_get_votes_location_queries_db_on_cache_miss(): void {
		$rows                         = [ [ 'location' => 'US' ], [ 'location' => 'DE' ] ];
		$this->wpdb_mock->get_res_seq = [ $rows ];

		$result = Votes::get_instance()->get_votes_location( self::CLIENT_ID );

		$this->assertSame( $rows, $result );
		$this->assertSame( 1, $this->wpdb_mock->get_res_calls );
	}

	public function test_get_votes_location_returns_distinct_locations(): void {
		$rows                         = [ [ 'location' => 'US' ], [ 'location' => 'CA' ] ];
		$this->wpdb_mock->get_res_seq = [ $rows ];

		$result = Votes::get_instance()->get_votes_location( self::CLIENT_ID );

		$this->assertCount( 2, $result );
		$this->assertSame( 'US', $result[0]['location'] );
	}

	public function test_get_votes_location_sets_cache_with_vote_cache_group(): void {
		$rows                         = [ [ 'location' => 'FR' ] ];
		$this->wpdb_mock->get_res_seq = [ $rows ];

		$stored_group = null;
		$stored_data  = null;
		Functions\when( 'wp_cache_set' )->alias(
			function ( $key, $data, $group ) use ( &$stored_group, &$stored_data ) {
				$stored_group = $group;
				$stored_data  = $data;
			}
		);

		Votes::get_instance()->get_votes_location( self::CLIENT_ID );

		$this->assertSame( 'pollify_vote_cache', $stored_group );
		$this->assertSame( $rows, $stored_data );
	}

	// ----------------------------------------------------------------
	// reset_results()
	// ----------------------------------------------------------------

	public function test_reset_results_returns_true_on_successful_delete(): void {
		$this->wpdb_mock->delete_val = 1;
		$this->assertTrue( Votes::get_instance()->reset_results( self::CLIENT_ID ) );
	}

	public function test_reset_results_returns_false_when_delete_fails(): void {
		$this->wpdb_mock->delete_val = false;
		$this->assertFalse( Votes::get_instance()->reset_results( self::CLIENT_ID ) );
	}

	public function test_reset_results_flushes_poll_cache_group(): void {
		$this->wpdb_mock->delete_val = 1;
		Functions\when( 'wp_cache_supports' )->justReturn( true );

		$flushed = [];
		Functions\when( 'wp_cache_flush_group' )->alias(
			function ( string $group ) use ( &$flushed ) {
				$flushed[] = $group;
			}
		);

		Votes::get_instance()->reset_results( self::CLIENT_ID );

		$this->assertContains( 'pollify_poll_cache', $flushed );
	}

	// ----------------------------------------------------------------
	// remove_vote()
	// ----------------------------------------------------------------

	public function test_remove_vote_returns_error_when_client_id_empty(): void {
		$result = Votes::get_instance()->remove_vote( [ 'user_ip' => '1.2.3.4' ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'empty_client_id_or_user_ip', $result->get_error_code() );
	}

	public function test_remove_vote_returns_error_when_user_ip_empty(): void {
		$result = Votes::get_instance()->remove_vote( [ 'client_id' => self::CLIENT_ID ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'empty_client_id_or_user_ip', $result->get_error_code() );
	}

	public function test_remove_vote_returns_true_on_success(): void {
		$this->wpdb_mock->delete_val = 1;

		$result = Votes::get_instance()->remove_vote( [
			'client_id' => self::CLIENT_ID,
			'user_ip'   => '1.2.3.4',
		] );

		$this->assertTrue( $result );
	}

	public function test_remove_vote_returns_false_when_delete_fails(): void {
		$this->wpdb_mock->delete_val = false;

		$result = Votes::get_instance()->remove_vote( [
			'client_id' => self::CLIENT_ID,
			'user_ip'   => '1.2.3.4',
		] );

		$this->assertFalse( $result );
	}

	public function test_remove_vote_flushes_poll_cache_group(): void {
		$this->wpdb_mock->delete_val = 1;
		Functions\when( 'wp_cache_supports' )->justReturn( true );

		$flushed = [];
		Functions\when( 'wp_cache_flush_group' )->alias(
			function ( string $group ) use ( &$flushed ) {
				$flushed[] = $group;
			}
		);

		Votes::get_instance()->remove_vote( [
			'client_id' => self::CLIENT_ID,
			'user_ip'   => '1.2.3.4',
		] );

		$this->assertContains( 'pollify_poll_cache', $flushed );
	}

	// ----------------------------------------------------------------
	// delete_vote_by_id()
	// ----------------------------------------------------------------

	public function test_delete_vote_by_id_returns_error_when_id_is_zero_or_negative(): void {
		$result = Votes::get_instance()->delete_vote_by_id( 0 );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_vote_id', $result->get_error_code() );
	}

	public function test_delete_vote_by_id_returns_true_on_success(): void {
		$this->wpdb_mock->delete_val = 1;
		$this->assertTrue( Votes::get_instance()->delete_vote_by_id( 5 ) );
	}

	public function test_delete_vote_by_id_returns_false_when_delete_fails(): void {
		$this->wpdb_mock->delete_val = false;
		$this->assertFalse( Votes::get_instance()->delete_vote_by_id( 5 ) );
	}

	public function test_delete_vote_by_id_fires_pollify_vote_deleted_action(): void {
		$this->wpdb_mock->delete_val = 1;

		$fired_id   = null;
		$fired_bool = null;

		Functions\when( 'do_action' )->alias(
			function ( string $tag, ...$args ) use ( &$fired_id, &$fired_bool ) {
				if ( 'pollify_vote_deleted' === $tag ) {
					$fired_id   = $args[0];
					$fired_bool = $args[1];
				}
			}
		);

		Votes::get_instance()->delete_vote_by_id( 7 );

		$this->assertSame( 7, $fired_id );
		$this->assertTrue( $fired_bool );
	}

	public function test_delete_vote_by_id_flushes_both_cache_groups(): void {
		$this->wpdb_mock->delete_val = 1;
		Functions\when( 'wp_cache_supports' )->justReturn( true );

		$flushed = [];
		Functions\when( 'wp_cache_flush_group' )->alias(
			function ( string $group ) use ( &$flushed ) {
				$flushed[] = $group;
			}
		);

		Votes::get_instance()->delete_vote_by_id( 3 );

		$this->assertContains( 'pollify_poll_cache', $flushed );
		$this->assertContains( 'pollify_vote_cache', $flushed );
	}
}
