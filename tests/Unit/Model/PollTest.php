<?php
/**
 * Tests for Model/Poll.php — Poll::vote().
 *
 * @package wpRigel\Pollify\Tests\Unit\Model
 */

declare(strict_types=1);

namespace wpRigel\Pollify\Tests\Unit\Model;

use Brain\Monkey\Functions;
use WP_Error;
use wpRigel\Pollify\Model\Poll;
use wpRigel\Pollify\Tests\Unit\AbstractTestCase;

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

class PollTest extends AbstractTestCase {

	private const CLIENT_ID = 'aaaabbbb-cccc-dddd-eeee-ffffaaaabbbb';

	/** @var object */
	private object $wpdb_mock;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'maybe_serialize' )->alias(
			fn( $value ) => ( is_array( $value ) || is_object( $value ) ) ? serialize( $value ) : $value
		);

		Functions\when( 'wp_parse_args' )->alias(
			fn( $args, $defaults = [] ) => array_merge( (array) $defaults, (array) $args )
		);

		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\when( 'wp_cache_set' )->justReturn( true );
		Functions\when( 'wp_cache_supports' )->justReturn( false );
		Functions\when( 'wp_cache_flush_group' )->justReturn( true );

		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'current_time' )->justReturn( '2024-01-01 00:00:00' );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'wp_remote_get' )->justReturn( new WP_Error( 'no-remote', 'No remote in tests' ) );
		Functions\when( 'wp_list_pluck' )->alias( fn( $list, $field ) => array_column( $list, $field ) );
		Functions\when( 'number_format_i18n' )->alias( fn( $n, $d = 0 ) => number_format( (float) $n, $d ) );
		Functions\when( 'wp_kses_post' )->returnArg();

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
		return new class() {
			public string $prefix      = 'wp_';
			public int $insert_id      = 0;
			public int $get_res_idx    = 0;
			public int $get_res_calls  = 0;
			public array $get_res_seq  = [];
			public $insert_val         = 1;
			public ?array $last_insert = null;

			public function prepare( string $sql, ...$args ): string {
				return $sql; }
			public function get_row( $sql, $output = null ): mixed {
				return null; }
			public function get_results( $sql, $output = null ): array {
				++$this->get_res_calls;
				return $this->get_res_seq[ $this->get_res_idx++ ] ?? [];
			}
			public function get_var( $sql ): mixed {
				return null; }
			public function insert( $table, $data, $format = null ): int|false {
				$this->last_insert = $data;
				return $this->insert_val;
			}
			public function delete( $table, $where, $format = null ): int|false {
				return 1; }
			public function esc_like( string $text ): string {
				return $text; }
		};
	}

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
			'settings'    => wp_json_encode(
				array_merge(
					[
						'anonymousVoting' => false,
						'status'          => 'publish',
					],
					$settings
				)
			),
			'response'    => 0,
			'options'     => [
				[
					'id'        => 1,
					'option_id' => 'opt1',
					'type'      => 'text',
					'option'    => 'Option 1',
				],
				[
					'id'        => 2,
					'option_id' => 'opt2',
					'type'      => 'text',
					'option'    => 'Option 2',
				],
			],
		];
	}

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

	private function make_poll( array $settings = [] ): Poll {
		return new Poll( $this->poll_data( $settings ) );
	}

	// ----------------------------------------------------------------
	// vote() — validation short-circuits
	// ----------------------------------------------------------------

	public function test_vote_returns_error_when_options_empty(): void {
		$result = $this->make_poll()->vote( [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'empty-options', $result->get_error_code() );
	}

	public function test_vote_returns_error_when_poll_is_closed(): void {
		$result = $this->make_poll( [ 'status' => 'draft' ] )->vote( [ 'opt1' ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'poll-closed', $result->get_error_code() );
	}

	public function test_vote_returns_error_when_login_required_and_guest(): void {
		// is_user_logged_in → false (set in setUp)
		$result = $this->make_poll( [ 'requireLogin' => true ] )->vote( [ 'opt1' ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'login-required', $result->get_error_code() );
	}

	// ----------------------------------------------------------------
	// vote() — DB-level failures
	// ----------------------------------------------------------------

	public function test_vote_returns_error_when_db_insert_fails(): void {
		$this->mock_poll_cache();
		$this->wpdb_mock->insert_val = false;

		$result = $this->make_poll()->vote( [ 'opt1' ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'vote_not_inserted', $result->get_error_code() );
	}

	// ----------------------------------------------------------------
	// vote() — success paths
	// ----------------------------------------------------------------

	public function test_vote_returns_success_data_on_valid_vote(): void {
		$this->mock_poll_cache();
		$this->wpdb_mock->insert_val = 1;
		$this->wpdb_mock->insert_id  = 1;

		$result = $this->make_poll()->vote( [ 'opt1' ] );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
	}

	public function test_vote_strips_sensitive_user_fields_from_response(): void {
		$this->mock_poll_cache();
		$this->wpdb_mock->insert_val = 1;
		$this->wpdb_mock->insert_id  = 1;

		$result = $this->make_poll()->vote( [ 'opt1' ] );

		$this->assertIsArray( $result );
		$this->assertArrayNotHasKey( 'user_id', $result['data'] );
		$this->assertArrayNotHasKey( 'user_ip', $result['data'] );
		$this->assertArrayNotHasKey( 'user_location', $result['data'] );
		$this->assertArrayNotHasKey( 'user_agent', $result['data'] );
	}

	public function test_vote_includes_result_and_template_when_confirmation_type_is_view_result(): void {
		$this->mock_poll_cache( [ 'confirmationMessageType' => 'view-result' ] );
		$this->wpdb_mock->insert_val  = 1;
		$this->wpdb_mock->insert_id   = 1;
		$this->wpdb_mock->get_res_seq = [
			[
				[
					'option_id'    => 'opt1',
					'votes'        => 1,
					'unique_votes' => 1,
				],
			],
		];

		// Return a non-existent path so pollify_load_template exits early (no file to include).
		Functions\when( 'wp_sprintf' )->justReturn( '/tmp/pollify_nonexistent_tpl.php' );

		$result = $this->make_poll( [ 'confirmationMessageType' => 'view-result' ] )->vote( [ 'opt1' ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'resultTemplate', $result );
		$this->assertArrayHasKey( 'result', $result );
	}
}
