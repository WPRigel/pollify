<?php
/**
 * Main plugin class.
 *
 * @package wpRigel\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify;

use wpRigel\Pollify\FeedbackManager;
use wpRigel\Pollify\Traits\Singleton;

/**
 * Class Plugin.
 *
 * @package wpRigel\Pollify
 */
class Blocks {

	use Singleton;

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'init_blocks' ] );
		add_action( 'block_categories_all', [ $this, 'register_block_category' ] );
		add_action( 'init', [ $this, 'register_block_styles' ] );
		add_action( 'save_post', [ $this, 'save_polls' ], 10, 2 );
		add_action( 'save_post', [ $this, 'delete_unused_blocks' ], 10, 2 );

		// Add localize script for nonces.
		add_action( 'wp_enqueue_scripts', [ $this, 'localize_script' ] );
	}

	/**
	 * Initialize blocks.
	 */
	public function init_blocks() {
		register_block_type(
			POLLIFY_PATH . '/build/poll',
			array(
				'render_callback' => [ $this, 'render_block' ],
			)
		);
	}

	/**
	 * Register block category.
	 *
	 * @param array $categories Block categories.
	 *
	 * @return array
	 */
	public function register_block_category( $categories ): array {
		return array_merge(
			$categories,
			array(
				array(
					'slug'  => 'pollify',
					'title' => __( 'Pollify', 'poll-creator' ),
				),
			)
		);
	}

	/**
	 * Render block.
	 *
	 * @param array $attributes Block attributes.
	 *
	 * @return string|null
	 */
	public function render_block( $attributes ): ?string {
		$poll_client_id = $attributes['pollClientId'] ?? 0;

		if ( empty( $poll_client_id ) ) {
			return null;
		}

		ob_start();
		include plugin()->path . '/templates/poll/poll.php';
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Save polls.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function save_polls( $post_id, $post ) {
		if (
			wp_is_post_autosave( $post_id )
			|| wp_is_post_revision( $post_id )
			|| 'trash' === $post->post_status
			|| 'auto-draft' === $post->post_status
		) {
			return;
		}

		$blocks = parse_blocks( $post->post_content );

		$polls = pollify_filter_allowed_blocks_recursive( $blocks, [ 'pollify/poll' ] );

		if ( empty( $polls ) ) {
			return;
		}

		// Read block.json once before the loop — same file for every poll on this post.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$json             = file_get_contents( plugin()->path . '/build/poll/block.json' );
		$json_data        = json_decode( $json, true );
		$block_attributes = $json_data['attributes'] ?? [];
		$skipped_field    = [ 'pollId', 'pollClientId', 'options', 'title', 'description', 'style' ];

		// Get all attributes and update the poll.
		foreach ( $polls as $poll ) {
			$poll_client_id = $poll['attrs']['pollClientId'] ?? '';

			if ( empty( $poll_client_id ) ) {
				continue;
			}

			$data              = $poll['attrs'] ?? [];
			$data['client_id'] = $poll_client_id;

			unset(
				$poll['attrs']['pollId'],
				$poll['attrs']['pollClientId'],
				$poll['attrs']['options'],
				$poll['attrs']['title'],
				$poll['attrs']['description'],
				$poll['attrs']['style']
			);

			// Loop through all block attributes and check if it's not set then set it to default value.
			foreach ( $block_attributes as $key => $value ) {
				if ( ! in_array( $key, $skipped_field, true ) && ! isset( $poll['attrs'][ $key ] ) ) {
					$poll['attrs'][ $key ] = $value['default'] ?? '';
				}
			}

			$data['reference'] = $post_id;
			$data['settings']  = serialize_block_attributes( $poll['attrs'] );

			// Now it's time to save the poll data.
			FeedbackManager::get_instance()->save( $data );
		}
	}

	/**
	 * Delete unused blocks.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function delete_unused_blocks( $post_id, $post ) {
		if (
			wp_is_post_autosave( $post_id )
			|| wp_is_post_revision( $post_id )
			|| 'trash' === $post->post_status
			|| 'auto-draft' === $post->post_status
		) {
			return;
		}

		$blocks = parse_blocks( $post->post_content );

		// Use recursive helper with wildcard to include any block starting with pollify/.
		$polls = pollify_filter_allowed_blocks_recursive( $blocks, [ 'pollify/*' ] );

		$poll_ids = array_map(
			function ( $poll ) {
				return $poll['attrs']['pollClientId'] ?? '';
			},
			$polls ?? []
		);

		$saved_poll_ids = get_post_meta( $post_id, '_pollify_poll_client_ids', true );

		if ( ! empty( $saved_poll_ids ) ) {
			foreach ( $saved_poll_ids as $saved_poll_id ) {
				if ( ! in_array( $saved_poll_id, $poll_ids, true ) ) {
					FeedbackManager::get_instance()->trash( $saved_poll_id );
				}
			}
		}

		update_post_meta( $post_id, '_pollify_poll_client_ids', $poll_ids );
	}

	/**
	 * Register block styles.
	 *
	 * This function is used to register block styles for the poll block.
	 *
	 * @since 1.0.0
	 */
	public function register_block_styles() {
		$block_styles = [
			[
				'block' => 'pollify/poll',
				'style' => [
					'name'  => 'poll-inline-list',
					'label' => __( 'Inline list', 'poll-creator' ),
				],
			],
		];

		foreach ( $block_styles as $block_style ) {
			register_block_style( $block_style['block'], $block_style['style'] );
		}
	}

	/**
	 * Localize script.
	 *
	 * This function is used to localize the script for nonces.
	 *
	 * @return void
	 */
	public function localize_script() {
		$data    = array( 'nonce' => wp_create_nonce( 'pollify-vote' ) );
		$handles = apply_filters( 'pollify_view_script_handles', array( 'pollify-poll-view-script' ) );
		foreach ( $handles as $handle ) {
			wp_localize_script( $handle, 'pollify', $data );
		}
	}
}
