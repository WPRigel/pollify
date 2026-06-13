<?php
/**
 * Pollify uninstall handler.
 *
 * Runs when the plugin is deleted from the admin. Data removal is opt-in:
 * set the `pollify_delete_data_on_uninstall` option to a truthy value
 * (or define POLLIFY_DELETE_DATA_ON_UNINSTALL) to drop tables and options.
 *
 * @package wpRigel\Pollify
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$pollify_delete_data = defined( 'POLLIFY_DELETE_DATA_ON_UNINSTALL' )
	? POLLIFY_DELETE_DATA_ON_UNINSTALL
	: get_option( 'pollify_delete_data_on_uninstall' );

if ( ! $pollify_delete_data ) {
	return;
}

global $wpdb;

// Drop custom tables.
foreach ( [ 'pollify_vote', 'pollify_poll_options', 'pollify_poll' ] as $pollify_table ) {
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}{$pollify_table}`" );
}

// Delete plugin options.
foreach ( [ 'pollify_installed', 'pollify_version', 'pollify_db_version', 'pollify_delete_data_on_uninstall' ] as $pollify_option ) {
	delete_option( $pollify_option );
}

// Delete poll references stored as post meta.
delete_post_meta_by_key( '_pollify_poll_client_ids' );

// Delete cached geo lookups and rate-limit transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_pollify_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_pollify_' ) . '%'
	)
);
