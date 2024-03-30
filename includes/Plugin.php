<?php
/**
 * Main plugin class.
 *
 * @package UnderDev\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace UnderDev\Pollify;

use \UnderDev\Pollify\Admin\Menu;

/**
 * Class Plugin.
 *
 * @package UnderDev\Pollify
 */
class Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Plugin's url.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * Assets directory path.
	 *
	 * @var string
	 */
	public $assets_dir;

	/**
	 * Fire the plugin initialization step.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->path       = dirname( __FILE__, 2 );
		$this->url        = plugin_dir_url( trailingslashit( dirname( __FILE__, 2 ) ) . 'pollify.php' );
		$this->assets_dir = trailingslashit( $this->path ) . 'assets/';

		$this->load();
	}

	/**
	 * Run the activator from installer
	 *
	 * @return void
	 */
	public function activator(): void {
		// phpcs:ignore;
		register_activation_hook( dirname( __FILE__, 2 ) . '/pollify.php', [ Installer::get_instance(), 'run' ] );
	}

	/**
	 * Load the plugin.
	 *
	 * @return void
	 */
	public function load() {
		Assets::get_instance();

		if ( is_admin() ) {
			Menu::get_instance();
		}

		Apis::get_instance();
		Blocks::get_instance();
	}
}
