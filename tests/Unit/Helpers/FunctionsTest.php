<?php
/**
 * Tests for includes/helpers/functions.php.
 *
 * @package wpRigel\Pollify\Tests\Unit\Helpers
 */

declare(strict_types=1);

namespace wpRigel\Pollify\Tests\Unit\Helpers;

use Brain\Monkey\Functions;
use wpRigel\Pollify\Tests\Unit\AbstractTestCase;

class FunctionsTest extends AbstractTestCase {

	// -----------------------------------------------------------------------
	// pollify_get_country_name()
	// -----------------------------------------------------------------------

	public function test_get_country_name_returns_correct_name(): void {
		$this->assertSame( 'United States', pollify_get_country_name( 'US' ) );
	}

	public function test_get_country_name_returns_another_known_country(): void {
		$this->assertSame( 'Bangladesh', pollify_get_country_name( 'BD' ) );
	}

	public function test_get_country_name_returns_empty_string_for_unknown_code(): void {
		$this->assertSame( '', pollify_get_country_name( 'XX' ) );
	}

	// -----------------------------------------------------------------------
	// pollify_get_formatted_country_votes()
	// -----------------------------------------------------------------------

	public function test_get_formatted_country_votes_returns_empty_array_for_empty_input(): void {
		$this->assertSame( [], pollify_get_formatted_country_votes( [] ) );
	}

	public function test_get_formatted_country_votes_maps_code_and_count_correctly(): void {
		$result = pollify_get_formatted_country_votes( [
			[ 'location' => 'US', 'votes' => 5 ],
			[ 'location' => 'BD', 'votes' => 3 ],
		] );

		$this->assertSame( [ [ 'United States', 5 ], [ 'Bangladesh', 3 ] ], $result );
	}

	public function test_get_formatted_country_votes_maps_unknown_code_to_empty_name(): void {
		$result = pollify_get_formatted_country_votes( [ [ 'location' => 'XX', 'votes' => 1 ] ] );
		$this->assertSame( [ [ '', 1 ] ], $result );
	}

	// -----------------------------------------------------------------------
	// pollify_generate_shorthand_styles()
	// -----------------------------------------------------------------------

	public function test_generate_shorthand_styles_with_detailed_trbl_values(): void {
		$result = pollify_generate_shorthand_styles( 'btn', 'padding', [
			'top'    => '1px',
			'right'  => '2px',
			'bottom' => '3px',
			'left'   => '4px',
		] );
		$this->assertSame( '--pollify-btn-padding: 1px 2px 3px 4px;', $result );
	}

	public function test_generate_shorthand_styles_defaults_missing_sides_to_zero(): void {
		$result = pollify_generate_shorthand_styles( 'btn', 'margin', [
			'top'   => '5px',
			'right' => '10px',
		] );
		$this->assertSame( '--pollify-btn-margin: 5px 10px 0 0;', $result );
	}

	public function test_generate_shorthand_styles_with_flat_value_key(): void {
		$result = pollify_generate_shorthand_styles( 'btn', 'padding', [ 'value' => '10px' ] );
		$this->assertSame( '--pollify-btn-padding: 10px;', $result );
	}

	public function test_generate_shorthand_styles_returns_empty_for_no_matching_keys(): void {
		$this->assertSame( '', pollify_generate_shorthand_styles( 'btn', 'padding', [] ) );
	}

	// -----------------------------------------------------------------------
	// pollify_generate_shorthand_border_styles()
	// -----------------------------------------------------------------------

	public function test_generate_shorthand_border_styles_with_flat_border(): void {
		$result = pollify_generate_shorthand_border_styles( 'btn', [
			'color' => '#000',
			'style' => 'solid',
			'width' => '1px',
		] );
		$this->assertSame(
			'--pollify-btn-bordercolor: #000; --pollify-btn-borderstyle: solid; --pollify-btn-borderwidth: 1px;',
			$result
		);
	}

	public function test_generate_shorthand_border_styles_with_detailed_four_sides(): void {
		$result = pollify_generate_shorthand_border_styles( 'btn', [
			'top'    => [ 'color' => '#f00', 'style' => 'solid',  'width' => '1px' ],
			'right'  => [ 'color' => '#0f0', 'style' => 'dashed', 'width' => '2px' ],
			'bottom' => [ 'color' => '#00f', 'style' => 'dotted', 'width' => '3px' ],
			'left'   => [ 'color' => '#ff0', 'style' => 'none',   'width' => '4px' ],
		] );
		$this->assertSame(
			'--pollify-btn-bordercolor: #f00 #0f0 #00f #ff0; --pollify-btn-borderstyle: solid dashed dotted none; --pollify-btn-borderwidth: 1px 2px 3px 4px;',
			$result
		);
	}

	public function test_generate_shorthand_border_styles_defaults_missing_sides_to_transparent_and_none(): void {
		$result = pollify_generate_shorthand_border_styles( 'btn', [
			'top' => [ 'color' => '#f00', 'style' => 'solid', 'width' => '1px' ],
		] );
		$this->assertStringContainsString( 'transparent', $result );
		$this->assertStringContainsString( 'none', $result );
	}

	public function test_generate_shorthand_border_styles_returns_empty_for_unknown_structure(): void {
		$this->assertSame( '', pollify_generate_shorthand_border_styles( 'btn', [] ) );
	}

	// -----------------------------------------------------------------------
	// pollify_filter_allowed_blocks_recursive()
	// -----------------------------------------------------------------------

	public function test_filter_allowed_blocks_exact_match(): void {
		$blocks = [ [ 'blockName' => 'pollify/poll', 'innerBlocks' => [] ] ];
		$result = pollify_filter_allowed_blocks_recursive( $blocks, [ 'pollify/poll' ] );
		$this->assertCount( 1, $result );
		$this->assertSame( 'pollify/poll', $result[0]['blockName'] );
	}

	public function test_filter_allowed_blocks_wildcard_prefix_matches_multiple(): void {
		$blocks = [
			[ 'blockName' => 'pollify/poll', 'innerBlocks' => [] ],
			[ 'blockName' => 'pollify/quiz', 'innerBlocks' => [] ],
			[ 'blockName' => 'core/paragraph', 'innerBlocks' => [] ],
		];
		$result = pollify_filter_allowed_blocks_recursive( $blocks, [ 'pollify/*' ] );
		$this->assertCount( 2, $result );
	}

	public function test_filter_allowed_blocks_no_match_returns_empty_array(): void {
		$blocks = [ [ 'blockName' => 'core/paragraph', 'innerBlocks' => [] ] ];
		$result = pollify_filter_allowed_blocks_recursive( $blocks, [ 'pollify/poll' ] );
		$this->assertSame( [], $result );
	}

	public function test_filter_allowed_blocks_recursive_finds_block_in_inner_blocks(): void {
		$blocks = [
			[
				'blockName'   => 'core/group',
				'innerBlocks' => [
					[ 'blockName' => 'pollify/poll', 'innerBlocks' => [] ],
				],
			],
		];
		$result = pollify_filter_allowed_blocks_recursive( $blocks, [ 'pollify/poll' ] );
		$this->assertCount( 1, $result );
		$this->assertSame( 'pollify/poll', $result[0]['blockName'] );
	}

	public function test_filter_allowed_blocks_skips_empty_block_name(): void {
		$blocks = [ [ 'blockName' => '', 'innerBlocks' => [] ] ];
		$result = pollify_filter_allowed_blocks_recursive( $blocks, [ 'pollify/poll' ] );
		$this->assertSame( [], $result );
	}

	public function test_filter_allowed_blocks_skips_missing_block_name_key(): void {
		$blocks = [ [ 'attrs' => [], 'innerBlocks' => [] ] ];
		$result = pollify_filter_allowed_blocks_recursive( $blocks, [ 'pollify/poll' ] );
		$this->assertSame( [], $result );
	}

	public function test_filter_allowed_blocks_empty_blocks_returns_empty(): void {
		$result = pollify_filter_allowed_blocks_recursive( [], [ 'pollify/poll' ] );
		$this->assertSame( [], $result );
	}

	// -----------------------------------------------------------------------
	// pollify_load_template()
	// -----------------------------------------------------------------------

	public function test_load_template_does_nothing_when_file_does_not_exist(): void {
		Functions\when( 'wp_sprintf' )->alias( fn( $fmt, ...$args ) => vsprintf( $fmt, $args ) );
		Functions\when( 'apply_filters' )->returnArg( 2 );

		pollify_load_template( 'nonexistent/template.php' );
		$this->addToAssertionCount( 1 );
	}

	public function test_load_template_executes_file_when_it_exists(): void {
		$tmp = sys_get_temp_dir() . '/pollify_tpl_' . uniqid() . '.php';
		file_put_contents( $tmp, '<?php // no-op' );

		Functions\when( 'wp_sprintf' )->justReturn( $tmp );
		Functions\when( 'apply_filters' )->justReturn( $tmp );

		pollify_load_template( 'any.php' );
		unlink( $tmp );

		$this->addToAssertionCount( 1 );
	}

	// -----------------------------------------------------------------------
	// pollify_filter_input()
	// -----------------------------------------------------------------------

	public function test_filter_input_calls_sanitize_text_field_for_custom_filter(): void {
		Functions\expect( 'sanitize_text_field' )->once()->andReturn( 'clean' );
		$result = pollify_filter_input( INPUT_GET, 'any_var', POLLIFY_FILTER_SANITIZE_STRING );
		$this->assertSame( 'clean', $result );
	}

	public function test_filter_input_uses_native_filter_for_other_types(): void {
		// filter_input returns null in CLI for non-existent variables — no WP mock needed.
		$result = pollify_filter_input( INPUT_GET, 'undefined_var', FILTER_VALIDATE_INT );
		$this->assertNull( $result );
	}

	// -----------------------------------------------------------------------
	// pollify_poll_results_page_nav()
	// -----------------------------------------------------------------------

	public function test_poll_results_page_nav_returns_empty_for_null_poll_and_no_get_param(): void {
		Functions\when( 'sanitize_text_field' )->justReturn( '' );
		$this->assertSame( [], pollify_poll_results_page_nav( null ) );
	}

	public function test_poll_results_page_nav_returns_empty_for_wp_error(): void {
		Functions\when( 'sanitize_text_field' )->justReturn( '' );
		$this->assertSame( [], pollify_poll_results_page_nav( new \WP_Error( 'err' ) ) );
	}

	public function test_poll_results_page_nav_returns_nav_array_for_valid_poll(): void {
		Functions\when( 'sanitize_text_field' )->justReturn( '' );
		Functions\when( 'add_query_arg' )->alias(
			fn( $args, $url = '' ) => $url . '?' . http_build_query( (array) $args )
		);
		Functions\when( 'admin_url' )->justReturn( 'http://example.com/wp-admin/admin.php' );
		Functions\when( 'apply_filters' )->returnArg( 2 );

		$poll = new class {
			public function get_client_id(): string {
				return 'poll-client-123';
			}
		};

		$result = pollify_poll_results_page_nav( $poll );

		$this->assertArrayHasKey( 'overview', $result );
		$this->assertArrayHasKey( 'votes', $result );
		$this->assertArrayHasKey( 'ip-details', $result );
		$this->assertArrayHasKey( 'link', $result['overview'] );
	}

	// -----------------------------------------------------------------------
	// pollify_display_ip_with_actions()
	// -----------------------------------------------------------------------

	public function test_display_ip_shows_na_for_null_ip(): void {
		ob_start();
		pollify_display_ip_with_actions( null, new \stdClass() );
		$output = ob_get_clean();
		$this->assertStringContainsString( 'N/A', $output );
	}

	public function test_display_ip_shows_na_for_empty_string_ip(): void {
		ob_start();
		pollify_display_ip_with_actions( '', new \stdClass() );
		$output = ob_get_clean();
		$this->assertStringContainsString( 'N/A', $output );
	}

	public function test_display_ip_renders_ip_and_remove_action_for_valid_ip(): void {
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'add_query_arg' )->alias(
			fn( $args, $url = '' ) => $url . '?' . http_build_query( (array) $args )
		);
		Functions\when( 'admin_url' )->justReturn( 'http://example.com/wp-admin/admin.php' );
		Functions\when( 'wp_nonce_url' )->alias( fn( $url, ...$rest ) => $url );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_js' )->returnArg();

		$poll = new class {
			public function get_client_id(): string {
				return 'poll-123';
			}
		};

		ob_start();
		pollify_display_ip_with_actions( '203.0.113.5', $poll );
		$output = ob_get_clean();

		$this->assertStringContainsString( '203.0.113.5', $output );
		$this->assertStringContainsString( 'pollify-remove-ip', $output );
	}
}
