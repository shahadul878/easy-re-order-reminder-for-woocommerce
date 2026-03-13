<?php

/**
 * Cron Handler Class
 *
 * @package WRR
 */

defined( 'ABSPATH' ) || exit;

/**
 * EASYRERE_Cron Class
 */
class EASYRERE_Cron {

	/**
	 * Instance
	 *
	 * @var EASYRERE_Cron
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return EASYRERE_Cron
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
		add_action( 'easyrere_daily_cron', array( $this, 'process_reminders' ) );
	}

	/**
	 * Activate cron on plugin activation
	 */
	public static function activate() {
		if ( ! wp_next_scheduled( 'easyrere_daily_cron' ) ) {
			wp_schedule_event( time(), 'daily', 'easyrere_daily_cron' );
		}
	}

	/**
	 * Deactivate cron on plugin deactivation
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'easyrere_daily_cron' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'easyrere_daily_cron' );
		}
	}

	/**
	 * Process reminders
	 */
	public function process_reminders() {
		// Check if reminders are enabled
		if ( 'yes' !== get_option( 'easyrere_enable_reminder', 'yes' ) ) {
			return;
		}

		$global_days = absint( get_option( 'easyrere_reminder_days', 30 ) );
		$timestamp   = strtotime( "-{$global_days} days" );
		$date_from   = gmdate( 'Y-m-d H:i:s', $timestamp );

		// Get completed orders
		$orders = wc_get_orders(
			array(
				'limit'          => -1,
				'status'         => 'completed',
				'date_completed' => '<=' . $date_from,
				'return'         => 'ids',
			)
		);

		if ( empty( $orders ) ) {
			return;
		}

		foreach ( $orders as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}

			// Check if customer has unsubscribed
			$customer_email = $order->get_billing_email();
			if ( $this->is_unsubscribed( $customer_email ) ) {
				continue;
			}

			// Process each product in the order
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
				if ( ! $product_id ) {
					continue;
				}

				// Check if reminder is enabled for this product
				if ( ! $this->is_reminder_enabled( $product_id ) ) {
					continue;
				}

				// Check if reminder already sent for this order-product combination (HPOS compatible)
				if ( $this->is_reminder_sent( $order, $product_id ) ) {
					continue;
				}

				// Get reminder days - check customer preference first, then product, then global (HPOS compatible)
				$customer_days = $order->get_meta( '_easyrere_customer_reminder_days' );
				if ( $customer_days && $customer_days > 0 ) {
					// Use customer's selected preference
					$reminder_days = absint( $customer_days );
				} else {
					// Fall back to product or global setting
					$reminder_days = $this->get_reminder_days( $product_id, $global_days );
				}

				$order_date = $order->get_date_completed();
				if ( ! $order_date ) {
					continue;
				}

				$order_timestamp = $order_date->getTimestamp();
				$reminder_time   = $order_timestamp + ( $reminder_days * DAY_IN_SECONDS );

				// Check if it's time to send reminder
				if ( time() >= $reminder_time ) {
					EASYRERE_Email::send_reorder_email( $order, $product_id );
					$this->mark_reminder_sent( $order, $product_id );
				}
			}
		}
	}

	/**
	 * Check if customer has unsubscribed
	 *
	 * @param string $email Customer email.
	 * @return bool
	 */
	private function is_unsubscribed( $email ) {
		$unsubscribed = get_option( 'easyrere_unsubscribed_emails', array() );
		return in_array( $email, $unsubscribed, true );
	}

	/**
	 * Check if reminder is enabled for product
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	private function is_reminder_enabled( $product_id ) {
		$product_enabled = get_post_meta( $product_id, '_easyrere_enable', true );
		if ( 'no' === $product_enabled ) {
			return false;
		}
		// If not set, use global setting
		return true;
	}

	/**
	 * Get reminder days for product
	 *
	 * @param int $product_id Product ID.
	 * @param int $global_days Global reminder days.
	 * @return int
	 */
	private function get_reminder_days( $product_id, $global_days ) {
		$product_days = get_post_meta( $product_id, '_easyrere_reminder_days', true );
		return $product_days ? absint( $product_days ) : $global_days;
	}

	/**
	 * Check if reminder already sent (HPOS compatible)
	 *
	 * @param WC_Order $order Order object.
	 * @param int      $product_id Product ID.
	 * @return bool
	 */
	private function is_reminder_sent( $order, $product_id ) {
		$sent = $order->get_meta( '_easyrere_sent_' . $product_id );
		return 'yes' === $sent;
	}

	/**
	 * Mark reminder as sent (HPOS compatible)
	 *
	 * @param WC_Order $order Order object.
	 * @param int      $product_id Product ID.
	 */
	private function mark_reminder_sent( $order, $product_id ) {
		$order->update_meta_data( '_easyrere_sent_' . $product_id, 'yes' );
		$order->update_meta_data( '_easyrere_sent_date_' . $product_id, current_time( 'mysql' ) );
		$order->save();
	}
}
