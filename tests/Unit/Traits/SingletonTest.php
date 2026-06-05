<?php
/**
 * Tests for Traits/Singleton.
 *
 * @package wpRigel\Pollify\Tests\Unit\Traits
 */

declare(strict_types=1);

namespace wpRigel\Pollify\Tests\Unit\Traits;

use Brain\Monkey\Functions;
use wpRigel\Pollify\Traits\Singleton;
use wpRigel\Pollify\Tests\Unit\AbstractTestCase;

// Concrete test doubles — each used by exactly one test to avoid static instance cache conflicts.
class SingletonDoubleA {
	use Singleton;
}

class SingletonDoubleB {
	use Singleton;
}

class SingletonDoubleC {
	use Singleton;
}

class SingletonTest extends AbstractTestCase {

	protected function setUp(): void {
		parent::setUp();
		// maybe_serialize is called in get_instance() to build the cache key.
		Functions\when( 'maybe_serialize' )->alias(
			fn( $v ) => is_array( $v ) || is_object( $v ) ? serialize( $v ) : (string) $v
		);
	}

	// -----------------------------------------------------------------------
	// get_instance()
	// -----------------------------------------------------------------------

	public function test_get_instance_returns_same_object_on_repeated_calls(): void {
		Functions\when( 'do_action' )->justReturn( null );

		$first  = SingletonDoubleA::get_instance();
		$second = SingletonDoubleA::get_instance();

		$this->assertSame( $first, $second );
	}

	public function test_get_instance_fires_init_action_on_first_instantiation(): void {
		Functions\expect( 'do_action' )->once();

		SingletonDoubleB::get_instance();
		$this->addToAssertionCount( 1 );
	}

	public function test_get_instance_does_not_fire_action_on_subsequent_calls(): void {
		// DoublC is fresh — first call fires once, second call must not fire again.
		Functions\when( 'do_action' )->justReturn( null );
		SingletonDoubleC::get_instance(); // prime the cache

		// Reset mocks, then expect do_action is never called on the next call.
		\Brain\Monkey\tearDown();
		\Brain\Monkey\setUp();
		\Brain\Monkey\Functions\stubTranslationFunctions();
		Functions\when( 'maybe_serialize' )->alias(
			fn( $v ) => is_array( $v ) || is_object( $v ) ? serialize( $v ) : (string) $v
		);
		Functions\expect( 'do_action' )->never();

		SingletonDoubleC::get_instance();
		$this->addToAssertionCount( 1 );
	}

	public function test_get_instance_returns_different_objects_for_different_classes(): void {
		Functions\when( 'do_action' )->justReturn( null );

		$a = SingletonDoubleA::get_instance();
		$b = SingletonDoubleB::get_instance();

		$this->assertNotSame( $a, $b );
	}
}
