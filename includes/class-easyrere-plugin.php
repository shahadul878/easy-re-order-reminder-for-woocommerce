<?php

/**
 * Main Plugin Class
 *
 * @package WRR
 */

defined( 'ABSPATH' ) || exit;

/**
 * EASYRERE_Plugin Class
 */
class EASYRERE_Plugin {

	/**
	 * Plugin instance
	 *
	 * @var EASYRERE_Plugin
	 */
	private static $instance = null;

	/**
	 * Get plugin instance
	 *
	 * @return EASYRERE_Plugin
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
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Include required files
	 */
	private function includes() {
		require_once EASYRERE_PATH . 'includes/class-easyrere-cron.php';
		require_once EASYRERE_PATH . 'includes/class-easyrere-order.php';
		// Email class will be loaded after WooCommerce is available
		require_once EASYRERE_PATH . 'includes/class-easyrere-settings.php';
		require_once EASYRERE_PATH . 'includes/class-easyrere-logger.php';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		register_activation_hook( EASYRERE_FILE, array( 'EASYRERE_Cron', 'activate' ) );
		register_activation_hook( EASYRERE_FILE, array( 'EASYRERE_Logger', 'create_log_table' ) );
		register_deactivation_hook( EASYRERE_FILE, array( 'EASYRERE_Cron', 'deactivate' ) );

		// Initialize components
		add_action( 'init', array( $this, 'init_components' ) );

		// Register email filter early - hook it directly so it's available when WooCommerce initializes emails
		// The filter will load the class when it's called
		add_filter( 'woocommerce_email_classes', array( $this, 'add_email_class' ), 20 );
	}

	/**
	 * Initialize plugin components
	 */
	public function init_components() {
		EASYRERE_Cron::instance();
		EASYRERE_Order::instance();
		EASYRERE_Settings::instance();
		EASYRERE_Logger::instance();
	}

	/**
	 * Add email class to WooCommerce emails
	 * This filter is called when WooCommerce initializes its email system
	 *
	 * @param array $emails Email classes.
	 * @return array
	 */
	public function add_email_class( $emails ) {
		// Ensure WC_Email exists - if not, we can't extend it
		if ( ! class_exists( 'WC_Email' ) ) {
			return $emails;
		}

		// Load email class if not already loaded
		if ( ! class_exists( 'EASYRERE_Email' ) ) {
			require_once EASYRERE_PATH . 'includes/class-easyrere-email.php';
		}

		// If class still doesn't exist, something went wrong
		if ( ! class_exists( 'EASYRERE_Email' ) ) {
			return $emails;
		}

		try {
			// Get email instance
			$email_instance = EASYRERE_Email::instance();

			if ( ! $email_instance || ! is_a( $email_instance, 'WC_Email' ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when WP_DEBUG is enabled
					error_log( 'WRR Debug: Email instance invalid or not WC_Email' );
				}
				return $emails;
			}

			// WooCommerce uses email ID as the key for email settings
			// Only register once with the email ID to avoid duplicates
			// Check if already registered to prevent duplicates
			if ( ! isset( $emails[ $email_instance->id ] ) ) {
				$emails[ $email_instance->id ] = $email_instance;
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when WP_DEBUG is enabled
					error_log( 'WRR Debug: Email registered with ID: ' . $email_instance->id );
				}
			}
		} catch ( Exception $e ) {
			// Log error but don't break
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Error logging for debugging
				error_log( 'WRR Email registration error: ' . $e->getMessage() );
			}
		}

		return $emails;
	}
}
