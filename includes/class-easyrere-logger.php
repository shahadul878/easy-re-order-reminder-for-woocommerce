<?php

/**
 * Logger Class
 *
 * @package WRR
 */

defined( 'ABSPATH' ) || exit;

/**
 * EASYRERE_Logger Class
 */
class EASYRERE_Logger {

	/**
	 * Instance
	 *
	 * @var EASYRERE_Logger
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return EASYRERE_Logger
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		// Table creation is handled by plugin activation hook
		// Also ensure table exists when logger is first used
		add_action( 'init', array( __CLASS__, 'maybe_create_table' ), 5 );
	}

	/**
	 * Maybe create table if it doesn't exist
	 */
	public static function maybe_create_table() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'easyrere_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table existence check is necessary and doesn't benefit from caching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			self::create_log_table();
		}
	}

	/**
	 * Create log table
	 */
	public static function create_log_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'easyrere_logs';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			order_id bigint(20) NOT NULL,
			product_id bigint(20) NOT NULL,
			email varchar(255) NOT NULL,
			sent_at datetime DEFAULT CURRENT_TIMESTAMP,
			status varchar(20) NOT NULL,
			PRIMARY KEY (id),
			KEY order_id (order_id),
			KEY product_id (product_id),
			KEY email (email),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Log reminder
	 *
	 * @param int    $order_id Order ID.
	 * @param int    $product_id Product ID.
	 * @param string $email Email address.
	 * @param string $status Status (pending, sent, failed).
	 * @return int|false Log ID or false on failure
	 */
	public static function log( $order_id, $product_id, $email, $status = 'pending' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'easyrere_logs';

		// Ensure table exists
		self::create_log_table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Logging requires direct database writes and cannot be cached
		$result = $wpdb->insert(
			$table_name,
			array(
				'order_id'   => absint( $order_id ),
				'product_id' => absint( $product_id ),
				'email'      => sanitize_email( $email ),
				'status'     => sanitize_text_field( $status ),
				'sent_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s' )
		);

		if ( $result ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get logs
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public static function get_logs( $args = array() ) {
		global $wpdb;

		// Table name constant - safe, never user input
		$table_suffix = 'easyrere_logs';
		$table_name   = $wpdb->prefix . $table_suffix;

		// Ensure table exists before querying
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table existence check is necessary and doesn't benefit from caching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			self::create_log_table();
		}

		$defaults = array(
			'limit'  => 50,
			'offset' => 0,
			'status' => '',
			'order'  => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Sanitize and validate inputs
		$limit  = absint( $args['limit'] );
		$offset = absint( $args['offset'] );
		$order  = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC';
		$status = ! empty( $args['status'] ) ? sanitize_text_field( $args['status'] ) : '';

		// Build query using prepare() for all user input
		// Use whitelist approach for ORDER BY clause to avoid interpolation
		if ( ! empty( $status ) ) {
			if ( 'ASC' === $order ) {
				$query = $wpdb->prepare(
					"SELECT * FROM `{$wpdb->prefix}easyrere_logs` WHERE status = %s ORDER BY sent_at ASC LIMIT %d OFFSET %d",
					$status,
					$limit,
					$offset
				);
			} else {
				$query = $wpdb->prepare(
					"SELECT * FROM `{$wpdb->prefix}easyrere_logs` WHERE status = %s ORDER BY sent_at DESC LIMIT %d OFFSET %d",
					$status,
					$limit,
					$offset
				);
			}
		} elseif ( 'ASC' === $order ) {
				$query = $wpdb->prepare(
					"SELECT * FROM `{$wpdb->prefix}easyrere_logs` WHERE 1=1 ORDER BY sent_at ASC LIMIT %d OFFSET %d",
					$limit,
					$offset
				);
		} else {
			$query = $wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}easyrere_logs` WHERE 1=1 ORDER BY sent_at DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above, log queries require direct access and real-time data (no caching)
		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Get log count
	 *
	 * @param string $status Status filter.
	 * @return int
	 */
	public static function get_log_count( $status = '' ) {
		global $wpdb;

		// Table name constant - safe, never user input
		$table_suffix = 'easyrere_logs';
		$table_name   = $wpdb->prefix . $table_suffix;

		// Ensure table exists before querying
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table existence check is necessary and doesn't benefit from caching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			self::create_log_table();
		}

		// Sanitize status
		$status = ! empty( $status ) ? sanitize_text_field( $status ) : '';

		// Build query using prepare() for user input
		if ( ! empty( $status ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$query = $wpdb->prepare(
				"SELECT COUNT(*) FROM `{$wpdb->prefix}easyrere_logs` WHERE status = %s",
				$status
			);
		} else {
			// No user input in this query, table name is safe (from $wpdb->prefix)
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$query = "SELECT COUNT(*) FROM `{$wpdb->prefix}easyrere_logs` WHERE 1=1";
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above (or contains no user input), log count queries require direct access and real-time data (no caching)
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Update log status
	 *
	 * @param int    $log_id Log ID.
	 * @param string $status New status.
	 * @return bool
	 */
	public static function update_status( $log_id, $status ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'easyrere_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Log status updates require direct database writes and cannot be cached
		$result = $wpdb->update(
			$table_name,
			array( 'status' => sanitize_text_field( $status ) ),
			array( 'id' => absint( $log_id ) ),
			array( '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}
}
