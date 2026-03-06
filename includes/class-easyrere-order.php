<?php

/**
 * Order Handler Class
 *
 * @package WRR
 */

defined('ABSPATH') || exit;

/**
 * EASYRERE_Order Class
 */
class EASYRERE_Order {

	/**
	 * Instance
	 *
	 * @var EASYRERE_Order
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return EASYRERE_Order
	 */
	public static function instance()
    {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
    {
		add_action('woocommerce_order_status_completed', array( $this, 'save_order_data' ), 10, 1);
		add_action('woocommerce_thankyou', array( $this, 'display_reminder_selector' ), 20);
		add_action('wp_ajax_easyrere_save_reminder_days', array( $this, 'save_reminder_days_ajax' ));
		add_action('wp_ajax_nopriv_easyrere_save_reminder_days', array( $this, 'save_reminder_days_ajax' ));
        add_action('wp_enqueue_scripts', array( $this, 'enqueue_scripts' ));
	}

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
            wp_enqueue_style('easyrere-frontend-css', EASYRERE_URL . 'assets/css/frontend.css', array(), EASYRERE_VERSION);
            
            // Get order ID from query vars or URL
            global $wp;
            $order_id = isset($wp->query_vars['order-received']) ? absint($wp->query_vars['order-received']) : 0;
            
            // Fallback: try to get from $_GET if query vars not set
            if (!$order_id && isset($_GET['order-received'])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading URL parameter only, not processing form data
                $order_id = absint(sanitize_text_field(wp_unslash($_GET['order-received'])));
            }
            
            if ($order_id) {
                $order = wc_get_order($order_id);
                if ($order) {
                    wp_enqueue_script('easyrere-thankyou', EASYRERE_URL . 'assets/js/thankyou-reminder-selector.js', array('jquery'), EASYRERE_VERSION, true);
                    wp_localize_script('easyrere-thankyou', 'easyrereThankYou', array(
                        'ajaxUrl' => admin_url('admin-ajax.php'),
                        'orderKey' => $order->get_order_key(),
                        'i18n' => array(
                            'saving' => __('Saving...', 'easy-re-order-reminder-for-woocommerce'),
                            'success' => __('✓ Preference saved successfully!', 'easy-re-order-reminder-for-woocommerce'),
                            'error' => __('Error saving preference', 'easy-re-order-reminder-for-woocommerce'),
                        ),
                    ));
                }
            }
        }
    }

	/**
	 * Save order data when order is completed
	 *
	 * @param int $order_id Order ID.
	 */
	public function save_order_data($order_id)
    {
		$order = wc_get_order($order_id);
		if (! $order) {
			return;
		}

		// Check if reminders are enabled globally
		if ('yes' !== get_option('easyrere_enable_reminder', 'yes')) {
			return;
		}

		$email = $order->get_billing_email();
		if (! $email) {
			return;
		}

		// Save order data for each product
		foreach ($order->get_items() as $item) {
			$product_id = $item->get_product_id();
			if (! $product_id) {
				continue;
			}

			// Check if reminder is enabled for this product
			$product_enabled = get_post_meta($product_id, '_easyrere_enable', true);
			if ('no' === $product_enabled) {
				continue;
			}

			// Store order completion timestamp for this product (HPOS compatible)
			$order->update_meta_data('_easyrere_pending_' . $product_id, time());
			$order->update_meta_data('_easyrere_product_' . $product_id, $product_id);
			$order->update_meta_data('_easyrere_email_' . $product_id, $email);

			// Log pending reminder
			EASYRERE_Logger::log($order_id, $product_id, $email, 'pending');
		}
		$order->save();
	}

	/**
	 * Display reminder day selector on thank you page
	 *
	 * @param int $order_id Order ID.
	 */
	public function display_reminder_selector($order_id)
    {
		$order = wc_get_order($order_id);
		if (! $order) {
			return;
		}

		// Do not show for failed orders
		if ($order->has_status('failed')) {
			return;
		}

		wc_get_template(
			'thankyou-reminder-selector.php',
			array( 'order' => $order ),
			'',
			EASYRERE_PATH . 'templates/'
		);
	}

	/**
	 * Save reminder days via AJAX
	 */
	public function save_reminder_days_ajax()
    {
		// Verify nonce
		$order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
		$nonce    = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

		if (! wp_verify_nonce($nonce, 'easyrere_save_reminder_days_' . $order_id)) {
			wp_send_json_error(__('Invalid security token.', 'easy-re-order-reminder-for-woocommerce'));
		}

		$order = wc_get_order($order_id);
		if (! $order) {
			wp_send_json_error(__('Order not found.', 'easy-re-order-reminder-for-woocommerce'));
		}

		// Verify user has access to this order
		// Check if user is logged in and owns the order
		$has_access = false;
		if (is_user_logged_in() && get_current_user_id() === $order->get_user_id()) {
			$has_access = true;
		} else {
			// Check order key from referrer or session
			// Order key is sanitized and validated against the order's key
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input is sanitized with sanitize_text_field
			$order_key = isset($_REQUEST['key']) ? sanitize_text_field(wp_unslash($_REQUEST['key'])) : '';
			if ($order_key && $order->get_order_key() === $order_key) {
				$has_access = true;
			}
		}

		if (! $has_access) {
			wp_send_json_error(__('Permission denied.', 'easy-re-order-reminder-for-woocommerce'));
		}

		$reminder_days = isset($_POST['reminder_days']) ? absint($_POST['reminder_days']) : 0;

		if ($reminder_days < 1) {
			wp_send_json_error(__('Invalid reminder days.', 'easy-re-order-reminder-for-woocommerce'));
		}

		// Save customer preference (HPOS compatible)
		$order->update_meta_data('_easyrere_customer_reminder_days', $reminder_days);
		$order->save();

		wp_send_json_success(__('Reminder preference saved successfully.', 'easy-re-order-reminder-for-woocommerce'));
	}
}
