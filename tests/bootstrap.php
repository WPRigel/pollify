<?php
/**
 * PHPUnit bootstrap for unit tests.
 *
 * @package wpRigel\Pollify\Tests
 */

require dirname( __DIR__ ) . '/vendor/autoload.php';

// Minimal WP constants required by plugin classes.
defined( 'ABSPATH' ) || define( 'ABSPATH', '/tmp/' );
defined( 'WPINC' ) || define( 'WPINC', 'wp-includes' );

// Stub WP_Error so tests can assert on return types without a WP bootstrap.
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error { // phpcs:ignore
		public string $code;
		public string $message;
		public mixed $data;

		public function __construct( string $code = '', string $message = '', mixed $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}

		public function get_error_data(): mixed {
			return $this->data;
		}
	}
}

// Pure utility stubs — deterministic, never mocked per-test via Brain\Monkey.
// WP_Error must be defined above before is_wp_error() references it.
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_array( $value )
			? array_map( 'wp_unslash', $value )
			: stripslashes( (string) $value );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

// Constants required by helpers/functions.php.
defined( 'POLLIFY_FILTER_SANITIZE_STRING' ) || define( 'POLLIFY_FILTER_SANITIZE_STRING', 999 );
defined( 'POLLIFY_PATH' ) || define( 'POLLIFY_PATH', dirname( __DIR__ ) );

// Load helper functions (not PSR-4 autoloaded).
require dirname( __DIR__ ) . '/includes/helpers/functions.php';
