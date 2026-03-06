<?php
/**
 * Uninstall Easy Re-Order Reminder for WooCommerce
 *
 * Fired when the plugin is uninstalled.
 *
 * @package Easy_Re_Order_Reminder
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

// Delete plugin options
delete_option('easyrere_enable_reminder');
delete_option('easyrere_reminder_days');
delete_option('easyrere_unsubscribed_emails');

// Drop custom log table
$table_name = $wpdb->prefix . 'easyrere_logs';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");
