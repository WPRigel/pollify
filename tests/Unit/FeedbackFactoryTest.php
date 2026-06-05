<?php
/**
 * Tests for FeedbackFactory.
 *
 * @package wpRigel\Pollify\Tests\Unit
 */

declare(strict_types=1);

namespace wpRigel\Pollify\Tests\Unit;

use Brain\Monkey\Functions;
use WP_Error;
use wpRigel\Pollify\FeedbackFactory;
use wpRigel\Pollify\Model\Poll;
use wpRigel\Pollify\Tests\Unit\AbstractTestCase;

class FeedbackFactoryTest extends AbstractTestCase {

	protected function setUp(): void {
		parent::setUp();
		// Pass-through apply_filters so class_map is not modified by filter stubs.
		Functions\when( 'apply_filters' )->alias( fn( $tag, $value ) => $value );
		Functions\when( 'maybe_unserialize' )->returnArg();
	}

	// -----------------------------------------------------------------------
	// Constructor
	// -----------------------------------------------------------------------

	public function test_constructor_accepts_array_input(): void {
		$factory = new FeedbackFactory( [ 'type' => 'poll' ] );
		$this->assertInstanceOf( FeedbackFactory::class, $factory );
	}

	public function test_constructor_accepts_object_input(): void {
		$factory = new FeedbackFactory( (object) [ 'type' => 'poll' ] );
		$this->assertInstanceOf( FeedbackFactory::class, $factory );
	}

	// -----------------------------------------------------------------------
	// get()
	// -----------------------------------------------------------------------

	public function test_get_returns_error_when_type_is_empty(): void {
		$result = ( new FeedbackFactory( [ 'type' => '' ] ) )->get();
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid-feedback-type', $result->get_error_code() );
	}

	public function test_get_returns_error_when_type_is_missing(): void {
		$result = ( new FeedbackFactory( [] ) )->get();
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid-feedback-type', $result->get_error_code() );
	}

	public function test_get_returns_error_when_type_not_in_class_map(): void {
		$result = ( new FeedbackFactory( [ 'type' => 'nonexistent_type' ] ) )->get();
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid-feedback-type', $result->get_error_code() );
	}

	public function test_get_returns_poll_instance_for_poll_type(): void {
		$result = ( new FeedbackFactory( [ 'type' => 'poll', 'client_id' => 'abc-123' ] ) )->get();
		$this->assertInstanceOf( Poll::class, $result );
	}

	public function test_get_hydrates_poll_with_provided_data(): void {
		$poll = ( new FeedbackFactory( [ 'type' => 'poll', 'title' => 'My Poll' ] ) )->get();
		$this->assertInstanceOf( Poll::class, $poll );
		$this->assertSame( 'My Poll', $poll->get_title() );
	}

	// -----------------------------------------------------------------------
	// get_class_map()
	// -----------------------------------------------------------------------

	public function test_get_class_map_contains_poll_key_by_default(): void {
		$map = FeedbackFactory::get_class_map();
		$this->assertArrayHasKey( 'poll', $map );
		$this->assertSame( Poll::class, $map['poll'] );
	}

	// -----------------------------------------------------------------------
	// Filter hook extends the class map
	// -----------------------------------------------------------------------

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_get_applies_filter_to_extend_class_map(): void {
		// Inline stub class — only defined in this isolated process.
		$custom_class = new class( [] ) extends \wpRigel\Pollify\Model\Feedback {
			public function vote( array $options = [], $request = [] ): void {} // phpcs:ignore
		};
		$custom_class_name = get_class( $custom_class );

		Functions\when( 'apply_filters' )->alias(
			function ( $tag, $map ) use ( $custom_class_name ) {
				if ( 'pollify_map_feedback_classes' === $tag ) {
					return array_merge( $map, [ 'custom' => $custom_class_name ] );
				}
				return $map;
			}
		);
		Functions\when( 'maybe_unserialize' )->returnArg();

		$result = ( new FeedbackFactory( [ 'type' => 'custom' ] ) )->get();
		$this->assertInstanceOf( $custom_class_name, $result );
	}
}
