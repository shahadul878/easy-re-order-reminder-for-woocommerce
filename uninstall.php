<?php
/**
 * Uninstall Easy Re-Order Reminder for WooCommerce.
 *
 * Fired when the plugin is uninstalled.
 *
 * @package Easy_Reorder_Reminder_For_WooCommerce
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Delete plugin options.
delete_option( 'easyrere_enable_reminder' );
delete_option( 'easyrere_reminder_days' );
delete_option( 'easyrere_unsubscribed_emails' );

// Drop custom log table.
$table_name = $wpdb->prefix . 'easyrere_logs';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.DB.SchemaChange.ChangeDetected -- On uninstall we intentionally remove the plugin's custom log table.
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
