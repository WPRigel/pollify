<?php
/**
 * Installer classe that handle functionality
 * during activation and installation.
 *
 * @package wpRigel\Pollify
 * @since 1.0.0
 */

declare(strict_types=1);

namespace wpRigel\Pollify;

use wpRigel\Pollify\Traits\Singleton;

/**
 * Class Installer.
 *
 * @package wpRigel\Pollify
 */
class Installer {

	use Singleton;

	/**
	 * Run the installer.
	 *
	 * @return void
	 */
	public function run(): void {
		$this->add_version();
		$this->create_tables();
	}

	/**
	 * Run schema upgrades when the plugin version advances.
	 *
	 * Called on every plugins_loaded so existing installs get new indexes
	 * without requiring a manual deactivate/reactivate cycle.
	 *
	 * @return void
	 */
	public function maybe_upgrade(): void {
		$db_version = get_option( 'pollify_db_version', '0' );

		if ( version_compare( $db_version, POLLIFY_VERSION, '>=' ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$this->create_tables();
		$this->sync_indexes();

		update_option( 'pollify_db_version', POLLIFY_VERSION, true );
	}

	/**
	 * Explicitly add missing indexes and drop redundant ones via ALTER TABLE.
	 *
	 * The dbDelta function handles new table creation but is unreliable for modifying indexes
	 * on tables that already exist, so we manage index changes directly here.
	 *
	 * @return void
	 */
	private function sync_indexes(): void {
		global $wpdb;

		// Add KEY poll_id (poll_id) to pollify_poll_options if missing.
		$has_poll_id_key = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND INDEX_NAME = %s',
				$wpdb->prefix . 'pollify_poll_options',
				'poll_id'
			)
		);

		if ( ! $has_poll_id_key ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}pollify_poll_options` ADD KEY `poll_id` (`poll_id`)" );
		}

		// Drop standalone KEY client_id (client_id) from pollify_vote — it is
		// already covered by the composite KEY poll_id (client_id, option_id).
		$has_standalone_client_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND INDEX_NAME = %s AND SEQ_IN_INDEX = 1',
				$wpdb->prefix . 'pollify_vote',
				'client_id'
			)
		);

		if ( $has_standalone_client_id ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}pollify_vote` DROP INDEX `client_id`" );
		}
	}

	/**
	 * Add version and check if the plugin is installed or not.
	 *
	 * @return void
	 */
	public function add_version(): void {
		$installed = get_option( 'pollify_installed' );

		if ( ! $installed ) {
			update_option( 'pollify_installed', time() );
		}

		update_option( 'pollify_version', POLLIFY_VERSION );
		update_option( 'pollify_db_version', POLLIFY_VERSION, true );
	}

	/**
	 * Create tables for the plugin.
	 *
	 * @return void
	 */
	public function create_tables(): void {
		$this->create_poll_table();
		$this->create_poll_option_table();
		$this->create_poll_vote_table();

		// Trigger an action after creating tables.
		do_action( 'pollify_run_after_creating_tables' );
	}

	/**
	 * Create poll table.
	 *
	 * @return void
	 */
	public function create_poll_table(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'pollify_poll';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			`id` int NOT NULL AUTO_INCREMENT,
			`client_id` varchar(255) DEFAULT NULL,
			`title` text NOT NULL,
			`description` text DEFAULT NULL,
			`type` varchar(11) NOT NULL,
			`status` varchar(25) NOT NULL,
			`reference` text,
			`created_at` datetime NOT NULL,
			`updated_at` datetime NOT NULL,
			`settings` longtext,
			PRIMARY KEY (`id`),
			KEY `client_id` (`client_id`)
		  ) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Create poll option table.
	 *
	 * @return void
	 */
	public function create_poll_option_table(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'pollify_poll_options';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			`id` int NOT NULL AUTO_INCREMENT,
			`poll_id` int NOT NULL,
			`option_id` varchar(255) NOT NULL,
			`type` varchar(25) NOT NULL,
			`option` longtext DEFAULT NULL,
			PRIMARY KEY (`id`),
			KEY `poll_id` (`poll_id`),
			KEY `option_id` (`option_id`)
		  ) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Create poll table.
	 *
	 * @return void
	 */
	public function create_poll_vote_table(): void {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'pollify_vote';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			`id` bigint NOT NULL AUTO_INCREMENT,
			`client_id` varchar(255) NOT NULL,
			`option_id` varchar(255) NOT NULL,
			`user_id` bigint DEFAULT NULL,
			`user_ip` varchar(50) DEFAULT NULL,
			`user_location` text,
			`user_state` text DEFAULT NULL,
			`user_agent` text,
			`created_at` datetime NOT NULL,
			PRIMARY KEY (`id`),
			KEY `poll_id` (`client_id`,`option_id`)
		  ) ENGINE=InnoDB $charset_collate;";

		dbDelta( $sql );
	}
}
