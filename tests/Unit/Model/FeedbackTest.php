<?php
/**
 * Tests for Feedback model: is_poll_closed() and validate_vote_request().
 *
 * @package wpRigel\Pollify\Tests\Unit\Model
 */

namespace wpRigel\Pollify\Tests\Unit\Model;

use Brain\Monkey\Functions;
use WP_Error;
use wpRigel\Pollify\Model\Feedback;
use wpRigel\Pollify\Tests\Unit\AbstractTestCase;

class FeedbackTest extends AbstractTestCase {

	/**
	 * Concrete test double for the abstract Feedback class.
	 */
	private function make_feedback( array $settings ): Feedback {
		$args = [ 'settings' => wp_json_encode( $settings ) ];

		// Stub maybe_unserialize to pass-through (settings already decoded to array in constructor).
		Functions\when( 'maybe_unserialize' )->returnArg();

		return new class( $args ) extends Feedback {
			public function vote( array $options = [], $request = [] ): void {} // phpcs:ignore

			public function call_validate( array $options ): bool|WP_Error {
				return $this->validate_vote_request( $options );
			}
		};
	}

	// -----------------------------------------------------------------------
	// is_poll_closed()
	// -----------------------------------------------------------------------

	public function test_is_poll_closed_returns_true_for_draft(): void {
		$feedback = $this->make_feedback( [ 'status' => 'draft' ] );
		$this->assertTrue( $feedback->is_poll_closed() );
	}

	public function test_is_poll_closed_returns_true_for_expired_schedule(): void {
		$past     = gmdate( 'c', strtotime( '-1 day' ) );
		$feedback = $this->make_feedback(
			[
				'status'  => 'schedule',
				'endDate' => $past,
			]
		);
		$this->assertTrue( $feedback->is_poll_closed() );
	}

	public function test_is_poll_closed_returns_false_for_future_schedule(): void {
		$future   = gmdate( 'c', strtotime( '+1 day' ) );
		$feedback = $this->make_feedback(
			[
				'status'  => 'schedule',
				'endDate' => $future,
			]
		);
		$this->assertFalse( $feedback->is_poll_closed() );
	}

	public function test_is_poll_closed_returns_false_for_publish(): void {
		$feedback = $this->make_feedback( [ 'status' => 'publish' ] );
		$this->assertFalse( $feedback->is_poll_closed() );
	}

	// -----------------------------------------------------------------------
	// validate_vote_request()
	// -----------------------------------------------------------------------

	public function test_validate_returns_error_for_empty_options(): void {
		$settings = [
			'status'                     => 'publish',
			'allowedPerComputerResponse' => false,
			'requireLogin'               => false,
			'anonymousVoting'            => false,
		];
		$feedback = $this->make_feedback( $settings );

		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'sanitize_text_field' )->returnArg();

		$result = $feedback->call_validate( [] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'empty-options', $result->get_error_code() );
	}

	public function test_validate_returns_error_when_poll_is_closed(): void {
		$settings = [
			'status'                     => 'draft',
			'allowedPerComputerResponse' => false,
			'requireLogin'               => false,
			'anonymousVoting'            => false,
			'closePollmessage'           => 'Closed',
		];
		$feedback = $this->make_feedback( $settings );

		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();

		$result = $feedback->call_validate( [ 'option-1' ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'poll-closed', $result->get_error_code() );
	}

	public function test_validate_returns_error_when_login_required_and_guest(): void {
		$settings = [
			'status'                     => 'publish',
			'requireLogin'               => true,
			'requireLoginMessage'        => 'Please log in.',
			'allowedPerComputerResponse' => false,
			'anonymousVoting'            => false,
		];
		$feedback = $this->make_feedback( $settings );

		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();

		$result = $feedback->call_validate( [ 'option-1' ] );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'login-required', $result->get_error_code() );
	}

	public function test_validate_returns_true_when_valid(): void {
		$settings = [
			'status'                     => 'publish',
			'requireLogin'               => false,
			'anonymousVoting'            => false,
			'allowedPerComputerResponse' => false,
		];
		$feedback = $this->make_feedback( $settings );

		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'apply_filters' )->justReturn( true );

		$result = $feedback->call_validate( [ 'option-1' ] );

		$this->assertTrue( $result );
	}

	// -----------------------------------------------------------------------
	// is_valid_poll_option()
	// -----------------------------------------------------------------------

	public function test_is_valid_poll_option_returns_true_for_known_option_id(): void {
		Functions\when( 'maybe_unserialize' )->returnArg();
		$feedback = new class( [ 'options' => [ [ 'option_id' => 'opt1' ] ] ] ) extends Feedback {
			public function vote( array $options = [], $request = [] ): void {} // phpcs:ignore
		};

		$this->assertTrue( $feedback->is_valid_poll_option( [ 'opt1' ] ) );
	}

	public function test_is_valid_poll_option_returns_false_for_unknown_option_id(): void {
		Functions\when( 'maybe_unserialize' )->returnArg();
		$feedback = new class( [ 'options' => [ [ 'option_id' => 'opt1' ] ] ] ) extends Feedback {
			public function vote( array $options = [], $request = [] ): void {} // phpcs:ignore
		};

		$this->assertFalse( $feedback->is_valid_poll_option( [ 'opt2' ] ) );
	}

	// -----------------------------------------------------------------------
	// get_settings()
	// -----------------------------------------------------------------------

	public function test_get_settings_returns_decoded_array(): void {
		$feedback = $this->make_feedback(
			[
				'status'       => 'publish',
				'requireLogin' => true,
			]
		);

		$this->assertSame(
			[
				'status'       => 'publish',
				'requireLogin' => true,
			],
			$feedback->get_settings()
		);
	}

	public function test_get_settings_returns_empty_array_when_settings_field_is_null(): void {
		Functions\when( 'maybe_unserialize' )->returnArg();
		$feedback = new class( [ 'settings' => null ] ) extends Feedback {
			public function vote( array $options = [], $request = [] ): void {} // phpcs:ignore
		};

		$this->assertSame( [], $feedback->get_settings() );
	}

	// -----------------------------------------------------------------------
	// get_data()
	// -----------------------------------------------------------------------

	public function test_get_data_contains_all_expected_keys(): void {
		$feedback = $this->make_feedback( [] );
		$data     = $feedback->get_data();

		foreach ( [ 'id', 'client_id', 'title', 'description', 'type', 'status', 'reference', 'options', 'created_at', 'updated_at', 'settings', 'response' ] as $key ) {
			$this->assertArrayHasKey( $key, $data );
		}
	}

	// -----------------------------------------------------------------------
	// get_options()
	// -----------------------------------------------------------------------

	public function test_get_options_returns_options_array(): void {
		$options = [
			[
				'option_id' => 'opt1',
				'option'    => 'Option 1',
			],
		];
		Functions\when( 'maybe_unserialize' )->returnArg();
		$feedback = new class( [ 'options' => $options ] ) extends Feedback {
			public function vote( array $options = [], $request = [] ): void {} // phpcs:ignore
		};

		$this->assertSame( $options, $feedback->get_options() );
	}

	// -----------------------------------------------------------------------
	// get_response()
	// -----------------------------------------------------------------------

	public function test_get_response_returns_integer_vote_count(): void {
		Functions\when( 'maybe_unserialize' )->returnArg();
		$feedback = new class( [ 'response' => 7 ] ) extends Feedback {
			public function vote( array $options = [], $request = [] ): void {} // phpcs:ignore
		};

		$this->assertSame( 7, $feedback->get_response() );
	}
}
