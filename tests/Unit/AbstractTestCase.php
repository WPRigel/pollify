<?php
/**
 * Base test case for all unit tests.
 *
 * @package wpRigel\Pollify\Tests\Unit
 */

namespace wpRigel\Pollify\Tests\Unit;

use Brain\Monkey;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

abstract class AbstractTestCase extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Monkey\Functions\stubTranslationFunctions();
		$_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
