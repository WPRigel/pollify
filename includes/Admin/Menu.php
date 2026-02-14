<?php
/**
 * Menu class.
 *
 * @package wpRigel\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify\Admin;

use wpRigel\Pollify\Traits\Singleton;

/**
 * Class Menu
 */
class Menu {

	use Singleton;

	/**
	 * Load class hooks
	 *
	 * @return void
	 */
	public function __construct() {

		$this->setup_hooks();
	}

	/**
	 * Load all hooks.
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		// Register admin menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 10 );

		// Render admin header for pollify menu.
		add_action( 'in_admin_header', [ $this, 'render_admin_header' ], 99 );

		// Enqueue scripts and styles for pollify menu.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Handle actions for pollify menu.
		add_action( 'admin_init', [ $this, 'handle_actions' ] );

		// Load feedback poll overview template.
		add_action( 'pollify_load_feedback_overview_template', [ $this, 'load_feedback_overview_template' ] );
	}

	/**
	 * Check if the current page is pollify admin page.
	 *
	 * @return bool
	 */
	public function if_pollify_admin_page() {
		// Check if the page is pollify menu or not.
		global $pollify_menu;

		$screen = get_current_screen();

		return $screen->id === $pollify_menu;
	}

	/**
	 * Outputs the plugin admin header.
	 *
	 * @since 1.0.0
	 */
	public function render_admin_header() {
		if ( ! $this->if_pollify_admin_page() ) {
			return;
		}
		?>
		<div id="wp-pollify-header-screen"></div>
		<div id="wp-pollify-header">
			<div class="logo-wrapper">
				<svg viewBox="-32 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M448 432V80c0-26.5-21.5-48-48-48H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48zM112 192c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h128c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16H112zm0 96c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h224c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16H112zm0 96c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h64c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16h-64z"/></svg>
				<h1>Pollify</h1>
			</div>
			<div class="quick-links">
				<a href="https://wprigel.com/contact-us" target="_blank">
					<span class="dashicons dashicons-phone"></span> <?php esc_html_e( 'Contact us', 'poll-creator' ); ?>
				</a>
				<a href="https://wprigel.com/docs" target="_blank" class="button button"><?php esc_html_e( 'Documentation', 'poll-creator' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Register menu for rendering poll related things.
	 *
	 * @return void
	 */
	public function admin_menu(): void {
		global $pollify_menu;

		$pollify_menu = add_menu_page(
			__( 'Pollify', 'poll-creator' ),
			__( 'Pollify', 'poll-creator' ),
			'edit_posts',
			'pollify',
			[ $this, 'render_polls' ],
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'data:image/svg+xml;base64,' . base64_encode( '<svg fill="#ffffff" viewBox="-32 0 512 512" xmlns="http://www.w3.org/2000/svg"><path d="M448 432V80c0-26.5-21.5-48-48-48H48C21.5 32 0 53.5 0 80v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48zM112 192c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h128c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16H112zm0 96c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h224c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16H112zm0 96c-8.84 0-16-7.16-16-16v-32c0-8.84 7.16-16 16-16h64c8.84 0 16 7.16 16 16v32c0 8.84-7.16 16-16 16h-64z"/></svg>' ),
			'26'
		);

		add_action( 'load-' . $pollify_menu, [ $this, 'add_screen_option' ] );
	}

	/**
	 * Enqueue scripts and styles for pollify menu.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		// Check if the page is pollify menu or not.
		global $pollify_menu;

		$screen = get_current_screen();

		if ( $screen->id !== $pollify_menu ) {
			return;
		}

		// Enqueue styles and script.
		wp_enqueue_style( 'pollify-admin' );
		wp_enqueue_script( 'pollify-admin' );

		// Localize script for trash delete functionality.
		wp_localize_script(
			'pollify-admin',
			'pollifyAdmin',
			array(
				'restUrl'    => rest_url( 'pollify/v1/polls/' ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'confirmMsg' => __( 'This poll will be permanently deleted and cannot be recovered.', 'poll-creator' ),
			)
		);

		// Add inline script for permanent delete handling.
		$inline_script = "
		(function($) {
			$(document).ready(function() {
				$('.pollify-delete-permanently').on('click', function(e) {
					e.preventDefault();
					var pollId = $(this).data('poll-id');
					var row = $(this).closest('tr');

					// Fetch poll stats first
					$.ajax({
						url: pollifyAdmin.restUrl + pollId + '/stats',
						method: 'GET',
						beforeSend: function(xhr) {
							xhr.setRequestHeader('X-WP-Nonce', pollifyAdmin.nonce);
						},
						success: function(stats) {
							var message = pollifyAdmin.confirmMsg + '\\n\\n';
							message += 'Total Votes: ' + stats.total_votes + '\\n';
							message += 'Unique Voters: ' + (stats.unique_voters !== null ? stats.unique_voters : 'N/A (Anonymous Poll)');

							if (confirm(message)) {
								// Delete permanently
								$.ajax({
									url: pollifyAdmin.restUrl + pollId + '/permanent-delete',
									method: 'DELETE',
									beforeSend: function(xhr) {
										xhr.setRequestHeader('X-WP-Nonce', pollifyAdmin.nonce);
									},
									success: function(response) {
										row.fadeOut(300, function() {
											$(this).remove();
										});
									},
									error: function(xhr) {
										alert('Error deleting poll: ' + (xhr.responseJSON?.message || 'Unknown error'));
									}
								});
							}
						},
						error: function(xhr) {
							alert('Error fetching poll stats: ' + (xhr.responseJSON?.message || 'Unknown error'));
						}
					});
				});
			});
		})(jQuery);
		";
		wp_add_inline_script( 'pollify-admin', $inline_script );

		$action = pollify_filter_input( INPUT_GET, 'action', POLLIFY_FILTER_SANITIZE_STRING );

		if ( ! empty( $action ) ) {
			wp_enqueue_style( 'pollify-flag-icons' );
			wp_enqueue_script( 'pollify-geo-chart' );
		}

		// Hook for loading admin scripts.
		do_action( 'pollify_load_admin_scripts' );
	}

	/**
	 * Render Polls.
	 *
	 * @return void
	 */
	public function render_polls(): void {
		// Get the page.
		$page   = pollify_filter_input( INPUT_GET, 'page', POLLIFY_FILTER_SANITIZE_STRING );
		$action = pollify_filter_input( INPUT_GET, 'action', POLLIFY_FILTER_SANITIZE_STRING );

		if ( 'pollify' === $page && 'view_results' === $action ) {
			$poll_id = pollify_filter_input( INPUT_GET, 'poll_id', POLLIFY_FILTER_SANITIZE_STRING );

			$feedback = \wpRigel\Pollify\FeedbackManager::get_instance()->get( $poll_id );

			// Need to do more styles later update.
			if ( is_wp_error( $feedback ) ) {
				echo '<div class="wrap">';
				echo '<div class="notice notice-error">';
				echo '<p>' . wp_kses_post( $feedback->get_error_message() ) . '</p>';
				echo '</div>';
				echo '</div>';
				return;
			}

			// Hooked for loading feedback overview template dynamically.
			do_action( 'pollify_load_feedback_overview_template', $feedback );

		} else {
			// Load poll lists template.
			pollify_load_template( 'admin/polls.php' );
		}
	}

	/**
	 * Load feedback overview template.
	 *
	 * @param \wpRigel\Pollify\Feedback $feedback Feedback object.
	 * @return void
	 */
	public function load_feedback_overview_template( $feedback ): void {
		if ( 'poll' === $feedback->get_type() ) {
			// Load feedback overview template.
			pollify_load_template(
				'admin/overview.php',
				false,
				[
					'poll' => $feedback,
				]
			);
		}
	}

	/**
	 * Add screen option for polls.
	 *
	 * @return void
	 */
	public function add_screen_option(): void {
		global $pollify_menu;

		$screen = get_current_screen();

		// Bail out of here if we are not on our pollify page.
		if ( ! is_object( $screen ) || $screen->id !== $pollify_menu ) {
			return;
		}

		$action = pollify_filter_input( INPUT_GET, 'action', POLLIFY_FILTER_SANITIZE_STRING );

		// Check if has action and the value is view_results, reset_results or trash_poll. Then return.
		if ( $action && in_array( $action, [ 'view_results', 'reset_results', 'trash_poll' ], true ) ) {
			return;
		}

		$args = [
			'label'   => __( 'Polls per page', 'poll-creator' ),
			'default' => 10,
			'option'  => 'polls_per_page',
		];

		add_screen_option( 'per_page', $args );
	}

	/**
	 * Handle actions.
	 *
	 * @return void
	 */
	public function handle_actions(): void {
		$page   = pollify_filter_input( INPUT_GET, 'page', POLLIFY_FILTER_SANITIZE_STRING );
		$action = pollify_filter_input( INPUT_GET, 'action', POLLIFY_FILTER_SANITIZE_STRING );
		$nonce  = pollify_filter_input( INPUT_GET, '_nonce', POLLIFY_FILTER_SANITIZE_STRING );

		if ( 'pollify' !== $page || empty( $action ) ) {
			return;
		}

		if ( 'reset_results' === $action && wp_verify_nonce( $nonce, 'pollify_reset_results' ) ) {
			$client_id = pollify_filter_input( INPUT_GET, 'poll_id', POLLIFY_FILTER_SANITIZE_STRING );

			if ( ! empty( $client_id ) ) {
				\wpRigel\Pollify\Votes::get_instance()->reset_results( $client_id );

				wp_safe_redirect( admin_url( 'admin.php?page=pollify&updated=1' ) );
			}
		}

		if ( 'trash_poll' === $action && wp_verify_nonce( $nonce, 'pollify_trash_poll' ) ) {
			$client_id    = pollify_filter_input( INPUT_GET, 'poll_id', POLLIFY_FILTER_SANITIZE_STRING );
			$reference_id = pollify_filter_input( INPUT_GET, 'reference_id', FILTER_VALIDATE_INT );

			if ( ! empty( $client_id ) ) {
				// First, remove the block from post if there's a reference.
				if ( ! empty( $reference_id ) ) {
					$post = get_post( $reference_id );

					if ( $post && ! is_wp_error( $post ) ) {
						$content = $post->post_content;
						$blocks  = parse_blocks( $content );
						$changed = false;

						// Remove the poll block with matching client_id.
						$filtered_blocks = $this->pollify_filter_blocks_recursive( $blocks, $client_id, $changed );

						if ( $changed ) {
							$new_content = serialize_blocks( $filtered_blocks );

							wp_update_post(
								[
									'ID'           => $reference_id,
									'post_content' => $new_content,
								]
							);
						}
					}
				}

				// Now move the poll to trash.
				\wpRigel\Pollify\FeedbackManager::get_instance()->trash( $client_id );

				wp_safe_redirect( admin_url( 'admin.php?page=pollify&trashed=1' ) );
			}
		}

		if ( 'delete_poll' === $action && wp_verify_nonce( $nonce, 'pollify_delete_poll' ) ) {
			$client_id    = pollify_filter_input( INPUT_GET, 'poll_id', POLLIFY_FILTER_SANITIZE_STRING );
			$reference_id = pollify_filter_input( INPUT_GET, 'reference_id', FILTER_VALIDATE_INT );

			if ( ! empty( $client_id ) && ! empty( $reference_id ) ) {
				$post = get_post( $reference_id );

				if ( $post && ! is_wp_error( $post ) ) {
					$content = $post->post_content;
					$blocks  = parse_blocks( $content );
					$changed = false;

					// Remove the poll block with matching client_id.
					$filtered_blocks = $this->pollify_filter_blocks_recursive( $blocks, $client_id, $changed );

					if ( $changed ) {
						$new_content = serialize_blocks( $filtered_blocks );

						$result = wp_update_post(
							[
								'ID'           => $reference_id,
								'post_content' => $new_content,
							]
						);

						if ( is_wp_error( $result ) ) {
							wp_die( esc_html__( 'Failed to update the post content.', 'poll-creator' ) );
						}
					}

					// Now delete the poll data.
					\wpRigel\Pollify\FeedbackManager::get_instance()->delete( $client_id );
				}

				wp_safe_redirect( admin_url( 'admin.php?page=pollify&deleted=1' ) );
			}
		}

		$ip = pollify_filter_input( INPUT_GET, 'ip_address', POLLIFY_FILTER_SANITIZE_STRING );

		if ( 'pollify_remove_ip' === $action
			&& current_user_can( 'manage_options' )
			&& wp_verify_nonce( $nonce, 'pollify_remove_ip_' . $ip )
		) {

			$poll_id = pollify_filter_input(
				INPUT_GET,
				'poll_id',
				POLLIFY_FILTER_SANITIZE_STRING,
			);

			$redirect_url = pollify_filter_input(
				INPUT_GET,
				'redirect_url',
				FILTER_VALIDATE_URL,
			);

			// Need to call remove_vote from Votes class to remove the IP address.
			$result = \wpRigel\Pollify\Votes::get_instance()->remove_vote(
				[
					'client_id' => $poll_id,
					'user_ip'   => $ip,
				]
			);

			if ( $result ) {
				$message = __( 'IP address removed successfully.', 'poll-creator' );
			} else {
				$message = __( 'Failed to remove IP address.', 'poll-creator' );
			}

			// Redirect back to the poll overview page.
			wp_safe_redirect(
				add_query_arg(
					[
						'updated' => $message,
					],
					! empty( $redirect_url ) ? $redirect_url : admin_url( 'admin.php?page=pollify' )
				)
			);

			exit;
		}

		// Handle single row deletion early so list reflects updated data.
		if ( 'pollify_delete_vote' === $action ) {
			$vote_id      = absint( pollify_filter_input( INPUT_GET, 'vote_id', POLLIFY_FILTER_SANITIZE_STRING ) );
			$nonce        = pollify_filter_input( INPUT_GET, '_wpnonce', POLLIFY_FILTER_SANITIZE_STRING );
			$redirect_url = pollify_filter_input( INPUT_GET, 'redirect_url', FILTER_VALIDATE_URL ) ?? '';

			if ( $vote_id && wp_verify_nonce( $nonce, 'pollify_delete_vote_' . $vote_id ) && current_user_can( 'edit_posts' ) ) {
				\wpRigel\Pollify\Votes::get_instance()->delete_vote_by_id( $vote_id );

				// Redirect to remove query args to avoid repeat deletion on refresh.
				wp_safe_redirect(
					add_query_arg(
						[
							'updated' => __( 'Vote deleted successfully.', 'poll-creator' ),
						],
						! empty( $redirect_url ) ? $redirect_url : admin_url( 'admin.php?page=pollify' )
					)
				);

				exit;
			}
		}
	}

	/**
	 * Filter blocks recursively.
	 *
	 * @param array $blocks Array of blocks to filter.
	 * @param mixed $poll_client_id Poll client ID to match for filtering.
	 * @param bool  &$changed Reference variable set to true if any block is filtered.
	 * @return array Filtered blocks.
	 */
	public function pollify_filter_blocks_recursive( $blocks, $poll_client_id, &$changed ) {
		$filtered = [];

		foreach ( $blocks as $block ) {

			$condition = isset( $block['blockName'] ) &&
				isset( $block['attrs']['pollClientId'] ) &&
				$block['attrs']['pollClientId'] === $poll_client_id;

			if ( $condition ) {
				$changed = true;
				// Skip this block (delete).
				continue;
			}

			// Recursively check inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->pollify_filter_blocks_recursive( $block['innerBlocks'], $poll_client_id, $changed );
			}

			$filtered[] = $block;
		}

		return $filtered;
	}
}
