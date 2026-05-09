<?php
/**
 * Tests for Voter model: is_local_ip(), get_user_id(), get_user_ip().
 *
 * @package wpRigel\Pollify\Tests\Unit\Model
 */

namespace wpRigel\Pollify\Tests\Unit\Model;

use Brain\Monkey\Functions;
use wpRigel\Pollify\Model\Voter;
use wpRigel\Pollify\Tests\Unit\AbstractTestCase;

class VoterTest extends AbstractTestCase {

	private function make_voter( bool $logged_in = false, int $user_id = 0, string $remote_addr = '127.0.0.1' ): Voter {
		$_SERVER['REMOTE_ADDR']     = $remote_addr;
		$_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
		unset( $_SERVER['HTTP_CLIENT_IP'], $_SERVER['HTTP_X_FORWARDED_FOR'] );

		Functions\when( 'is_user_logged_in' )->justReturn( $logged_in );
		Functions\when( 'get_current_user_id' )->justReturn( $user_id );
		Functions\when( 'sanitize_text_field' )->returnArg();

		return new Voter();
	}

	// -----------------------------------------------------------------------
	// is_local_ip()
	// -----------------------------------------------------------------------

	public function test_is_local_ip_returns_true_for_ipv4_localhost(): void {
		$voter = $this->make_voter();
		$this->assertTrue( $voter->is_local_ip( '127.0.0.1' ) );
	}

	public function test_is_local_ip_returns_true_for_ipv6_localhost(): void {
		$voter = $this->make_voter();
		$this->assertTrue( $voter->is_local_ip( '::1' ) );
	}

	public function test_is_local_ip_returns_true_for_class_a_private(): void {
		$voter = $this->make_voter();
		$this->assertTrue( $voter->is_local_ip( '10.0.0.1' ) );
		$this->assertTrue( $voter->is_local_ip( '10.255.255.255' ) );
	}

	public function test_is_local_ip_returns_true_for_class_c_private(): void {
		$voter = $this->make_voter();
		$this->assertTrue( $voter->is_local_ip( '192.168.1.100' ) );
	}

	public function test_is_local_ip_returns_false_for_public_ip(): void {
		$voter = $this->make_voter();
		$this->assertFalse( $voter->is_local_ip( '8.8.8.8' ) );
		$this->assertFalse( $voter->is_local_ip( '203.0.113.1' ) );
	}

	// -----------------------------------------------------------------------
	// get_user_id()
	// -----------------------------------------------------------------------

	public function test_get_user_id_returns_zero_when_not_logged_in(): void {
		$voter = $this->make_voter( logged_in: false, user_id: 0 );
		$this->assertSame( 0, $voter->get_user_id() );
	}

	public function test_get_user_id_returns_id_when_logged_in(): void {
		$voter = $this->make_voter( logged_in: true, user_id: 42 );
		$this->assertSame( 42, $voter->get_user_id() );
	}

	// -----------------------------------------------------------------------
	// get_user_ip()
	// -----------------------------------------------------------------------

	public function test_get_user_ip_returns_remote_addr(): void {
		$voter = $this->make_voter( remote_addr: '203.0.113.5' );
		$this->assertSame( '203.0.113.5', $voter->get_user_ip() );
	}

	public function test_get_user_ip_falls_back_to_localhost_for_invalid_ip(): void {
		$voter = $this->make_voter( remote_addr: 'not-an-ip' );
		$this->assertSame( '127.0.0.1', $voter->get_user_ip() );
	}

	public function test_get_user_ip_prefers_http_client_ip(): void {
		$_SERVER['HTTP_CLIENT_IP'] = '203.0.113.10';
		$_SERVER['REMOTE_ADDR']    = '10.0.0.1';
		$_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
		unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );

		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'sanitize_text_field' )->returnArg();

		$voter = new Voter();
		$this->assertSame( '203.0.113.10', $voter->get_user_ip() );

		unset( $_SERVER['HTTP_CLIENT_IP'] );
	}
}
