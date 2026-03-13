<?php

/**
 * Plugin Name: Easy Re-Order Reminder for WooCommerce
 * Plugin URI: https://github.com/shahadul878/easy-reorder-reminder
 * Description: Automatically remind customers to reorder products after a defined time period.
 * Version: 1.0.1
 * Author: Codereyes
 * Author URI: https://codereyes.com
 * Text Domain: easy-re-order-reminder-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('EASYRERE_VERSION', '1.0.1');
define('EASYRERE_FILE', __FILE__);
define('EASYRERE_PATH', plugin_dir_path(__FILE__));
define('EASYRERE_URL', plugin_dir_url(__FILE__));
define('EASYRERE_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active (supports network activation)
if (! function_exists('easyrere_is_woocommerce_active')) {
	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	function easyrere_is_woocommerce_active() {
		if (class_exists('WooCommerce')) {
			return true;
		}
		if (! function_exists('is_plugin_active')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active('woocommerce/woocommerce.php');
	}
}
if (! easyrere_is_woocommerce_active()) {
	add_action('admin_notices', function () {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e('Easy Re-Order Reminder for WooCommerce requires WooCommerce to be installed and active.', 'easy-re-order-reminder-for-woocommerce'); ?></p>
		</div>
		<?php
	});
	return;
}

// Load the main plugin class
require_once EASYRERE_PATH . 'includes/class-easyrere-plugin.php';

/**
 * Initialize the plugin
 */
function easyrere_init()
{
	return EASYRERE_Plugin::instance();
}

add_action('plugins_loaded', 'easyrere_init');

/**
 * Declare HPOS Compatibility
 */
add_action('before_woocommerce_init', function () {
	if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
	}
});

