<?php

/**
 * Settings Handler Class
 *
 * @package WRR
 */

defined( 'ABSPATH' ) || exit;

/**
 * EASYRERE_Settings Class
 */
class EASYRERE_Settings {

	/**
	 * Instance
	 *
	 * @var EASYRERE_Settings
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return EASYRERE_Settings
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
	public function __construct() {
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_product_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_fields' ) );
		add_action( 'admin_init', array( $this, 'handle_unsubscribe' ) );
		add_action( 'init', array( $this, 'handle_unsubscribe_frontend' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_easyrere_resend_email', array( $this, 'ajax_resend_email' ) );

		// Register meta fields for block editor (REST API)
		add_action( 'init', array( $this, 'register_meta_fields' ) );

		// Add fields to WooCommerce block editor
		// Use priority 15 to run after WooCommerce initializes templates but before they're finalized
		add_filter( 'woocommerce_product_editor_product_templates', array( $this, 'add_block_editor_fields' ), 15, 1 );

		// Ensure meta fields are included in REST API response
		add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'add_meta_to_rest_response' ), 10, 3 );

		// Handle meta field updates via REST API
		add_action( 'woocommerce_rest_insert_product_object', array( $this, 'update_meta_from_rest' ), 10, 2 );
	}

	/**
	 * Resend email via AJAX
	 */
	public function ajax_resend_email() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Permission denied', 'easy-re-order-reminder-for-woocommerce' ) );
		}

		$log_id = isset( $_POST['log_id'] ) ? absint( $_POST['log_id'] ) : 0;
		$nonce  = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'easyrere_resend_email_' . $log_id ) ) {
			wp_send_json_error( __( 'Invalid security token.', 'easy-re-order-reminder-for-woocommerce' ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Log retrieval requires direct database access for real-time data
		$log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}easyrere_logs WHERE id = %d", $log_id ) );

		if ( ! $log ) {
			wp_send_json_error( __( 'Log entry not found', 'easy-re-order-reminder-for-woocommerce' ) );
		}

		$order = wc_get_order( $log->order_id );
		if ( ! $order ) {
			wp_send_json_error( __( 'Order not found', 'easy-re-order-reminder-for-woocommerce' ) );
		}

		// Initialize WooCommerce email system - this loads WC_Email class via WC_Emails::init()
		if ( class_exists( 'WC_Emails' ) ) {
			// Calling instance() triggers the constructor which calls init() which includes class-wc-email.php
			WC_Emails::instance();
		}

		// Ensure WC_Email base class is loaded (it should be after WC_Emails::instance())
		if ( ! class_exists( 'WC_Email' ) ) {
			wp_send_json_error( __( 'WooCommerce email system not available', 'easy-re-order-reminder-for-woocommerce' ) );
		}

		// Ensure EASYRERE_Email class file is loaded
		if ( ! class_exists( 'EASYRERE_Email' ) ) {
			require_once EASYRERE_PATH . 'includes/class-easyrere-email.php';
		}

		// Trigger the filter to register our email class with WooCommerce
		// This ensures the email class is properly registered even during AJAX
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce hook
		apply_filters( 'woocommerce_email_classes', array() );

		$email_class = EASYRERE_Email::instance();

		if ( ! $email_class ) {
			wp_send_json_error( __( 'Email class not available', 'easy-re-order-reminder-for-woocommerce' ) );
		}

		// Trigger email
		try {
			$email_class->trigger( $order, $log->product_id );
		} catch ( Exception $e ) {
			wp_send_json_error( __( 'Error sending email: ', 'easy-re-order-reminder-for-woocommerce' ) . $e->getMessage() );
		} catch ( Error $e ) {
			wp_send_json_error( __( 'Fatal error sending email: ', 'easy-re-order-reminder-for-woocommerce' ) . $e->getMessage() );
		}

		// Update log status to 'sent'
		EASYRERE_Logger::update_status( $log_id, 'sent' );

		wp_send_json_success( __( 'Email queued for sending', 'easy-re-order-reminder-for-woocommerce' ) );
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Page parameter check only
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'easyrere-settings' === $page || 'easyrere-logs' === $page ) {
			wp_enqueue_style( 'easyrere-admin-css', EASYRERE_URL . 'assets/css/admin.css', array(), EASYRERE_VERSION );

			if ( 'easyrere-settings' === $page ) {
				wp_enqueue_script( 'easyrere-admin-settings', EASYRERE_URL . 'assets/js/admin-settings-page.js', array( 'jquery' ), EASYRERE_VERSION, true );
				wp_localize_script(
					'easyrere-admin-settings',
					'easyrereAdminSettings',
					array(
						'nonce' => wp_create_nonce( 'easyrere_test_email' ),
						'i18n'  => array(
							'enterEmail' => __( 'Please enter an email address', 'easy-re-order-reminder-for-woocommerce' ),
							'sending'    => __( 'Sending...', 'easy-re-order-reminder-for-woocommerce' ),
							'success'    => __( '✓ Test email sent successfully!', 'easy-re-order-reminder-for-woocommerce' ),
							'error'      => __( 'Error sending email', 'easy-re-order-reminder-for-woocommerce' ),
						),
					)
				);
			}

			if ( 'easyrere-logs' === $page ) {
				wp_enqueue_script( 'easyrere-logs', EASYRERE_URL . 'assets/js/logs-page.js', array( 'jquery' ), EASYRERE_VERSION, true );
				wp_localize_script(
					'easyrere-logs',
					'easyrereLogs',
					array(
						'i18n' => array(
							'sending'      => __( 'Sending...', 'easy-re-order-reminder-for-woocommerce' ),
							'resend'       => __( 'Resend', 'easy-re-order-reminder-for-woocommerce' ),
							'emailSent'    => __( 'Email sent successfully', 'easy-re-order-reminder-for-woocommerce' ),
							'error'        => __( 'Error sending email', 'easy-re-order-reminder-for-woocommerce' ),
							'networkError' => __( 'Network error', 'easy-re-order-reminder-for-woocommerce' ),
						),
					)
				);
			}
		}
	}

	/**
	 * Add settings page
	 *
	 * @param array $settings Settings pages.
	 * @return array
	 */
	public function add_settings_page( $settings ) {
		$settings[] = include EASYRERE_PATH . 'includes/class-easyrere-settings-page.php';
		return $settings;
	}

	/**
	 * Add product fields
	 */
	public function add_product_fields() {
		global $post;

		$enable = get_post_meta( $post->ID, '_easyrere_enable', true );
		$days   = get_post_meta( $post->ID, '_easyrere_reminder_days', true );

		echo '<div class="options_group">';
		echo '<h3>' . esc_html__( 'Re-Order Reminder', 'easy-re-order-reminder-for-woocommerce' ) . '</h3>';

		woocommerce_wp_checkbox(
			array(
				'id'          => '_easyrere_enable',
				'label'       => __( 'Enable reminder', 'easy-re-order-reminder-for-woocommerce' ),
				'description' => __( 'Enable reorder reminders for this product', 'easy-re-order-reminder-for-woocommerce' ),
				'value'       => $enable ? $enable : 'yes',
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => '_easyrere_reminder_days',
				'label'             => __( 'Reminder days', 'easy-re-order-reminder-for-woocommerce' ),
				'description'       => __( 'Days after order completion to send reminder. Leave empty to use global setting.', 'easy-re-order-reminder-for-woocommerce' ),
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '1',
				),
				'value'             => $days ? $days : '',
			)
		);

		echo '</div>';
	}

	/**
	 * Save product fields
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_product_fields( $post_id ) {
		// Verify nonce - WooCommerce product save uses 'woocommerce_save_data' nonce
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified below
		if ( ! isset( $_POST['woocommerce_meta_nonce'] ) ) {
			return;
		}

		// Verify nonce for product meta save (WooCommerce standard)
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verification only
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$enable = isset( $_POST['_easyrere_enable'] ) ? 'yes' : 'no';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above
		$days = isset( $_POST['_easyrere_reminder_days'] ) ? absint( $_POST['_easyrere_reminder_days'] ) : '';

		update_post_meta( $post_id, '_easyrere_enable', $enable );
		if ( $days ) {
			update_post_meta( $post_id, '_easyrere_reminder_days', $days );
		} else {
			delete_post_meta( $post_id, '_easyrere_reminder_days' );
		}
	}

	/**
	 * Handle unsubscribe from admin
	 */
	public function handle_unsubscribe() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below
		if ( ! isset( $_GET['easyrere_unsubscribe'] ) || ! isset( $_GET['email'] ) || ! isset( $_GET['nonce'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below
		$email = sanitize_email( wp_unslash( $_GET['email'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below
		$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'easyrere_unsubscribe_' . $email ) ) {
			wp_die( esc_html__( 'Invalid security token.', 'easy-re-order-reminder-for-woocommerce' ) );
		}

		$this->unsubscribe_email( $email );
		wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=easyrere_settings&easyrere_unsubscribed=1' ) );
		exit;
	}

	/**
	 * Handle unsubscribe from frontend
	 */
	public function handle_unsubscribe_frontend() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below
		if ( ! isset( $_GET['easyrere_unsubscribe'] ) || ! isset( $_GET['email'] ) || ! isset( $_GET['nonce'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below
		$email = sanitize_email( wp_unslash( $_GET['email'] ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified below
		$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'easyrere_unsubscribe_' . $email ) ) {
			wp_die( esc_html__( 'Invalid security token.', 'easy-re-order-reminder-for-woocommerce' ) );
		}

		$this->unsubscribe_email( $email );

		// Show success message
		add_action(
			'wp_footer',
			function () {
				?>
			<div style="position: fixed; top: 20px; right: 20px; background: #4CAF50; color: white; padding: 15px 20px; border-radius: 5px; z-index: 9999;">
				<?php esc_html_e( 'You have been unsubscribed from reorder reminders.', 'easy-re-order-reminder-for-woocommerce' ); ?>
			</div>
				<?php
			}
		);
	}

	/**
	 * Unsubscribe email
	 *
	 * @param string $email Email address.
	 */
	private function unsubscribe_email( $email ) {
		$unsubscribed = get_option( 'easyrere_unsubscribed_emails', array() );
		if ( ! in_array( $email, $unsubscribed, true ) ) {
			$unsubscribed[] = $email;
			update_option( 'easyrere_unsubscribed_emails', $unsubscribed );
		}
	}

	/**
	 * Add admin menu pages
	 */
	public function add_admin_menu() {
		// Main settings page
		add_menu_page(
			__( 'Re-Order Reminder', 'easy-re-order-reminder-for-woocommerce' ),
			__( 'Re-Order Reminder', 'easy-re-order-reminder-for-woocommerce' ),
			'manage_woocommerce',
			'easyrere-settings',
			array( $this, 'render_settings_page' ),
			'dashicons-email-alt',
			56
		);

		// Settings submenu (same as main page)
		add_submenu_page(
			'easyrere-settings',
			__( 'Settings', 'easy-re-order-reminder-for-woocommerce' ),
			__( 'Settings', 'easy-re-order-reminder-for-woocommerce' ),
			'manage_woocommerce',
			'easyrere-settings',
			array( $this, 'render_settings_page' )
		);

		// Logs submenu
		add_submenu_page(
			'easyrere-settings',
			__( 'Re-Order Reminder Logs', 'easy-re-order-reminder-for-woocommerce' ),
			__( 'Logs', 'easy-re-order-reminder-for-woocommerce' ),
			'manage_woocommerce',
			'easyrere-logs',
			array( $this, 'render_logs_page' )
		);
	}

	/**
	 * Render main settings page
	 */
	public function render_settings_page() {
		include EASYRERE_PATH . 'includes/views/admin-settings-page.php';
	}

	/**
	 * Render logs page
	 */
	public function render_logs_page() {
		$logs = EASYRERE_Logger::get_logs();
		include EASYRERE_PATH . 'includes/views/logs-page.php';
	}

	/**
	 * Register meta fields for REST API (block editor)
	 */
	public function register_meta_fields() {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// Register _easyrere_enable meta field
		register_post_meta(
			'product',
			'_easyrere_enable',
			array(
				'type'          => 'string',
				'description'   => __( 'Enable reorder reminders for this product', 'easy-re-order-reminder-for-woocommerce' ),
				'single'        => true,
				'show_in_rest'  => array(
					'schema' => array(
						'type'    => 'string',
						'default' => 'yes',
					),
				),
				'auth_callback' => function () {
					return current_user_can( 'edit_products' );
				},
			)
		);

		// Register _easyrere_reminder_days meta field
		register_post_meta(
			'product',
			'_easyrere_reminder_days',
			array(
				'type'          => 'integer',
				'description'   => __( 'Days after order completion to send reminder', 'easy-re-order-reminder-for-woocommerce' ),
				'single'        => true,
				'show_in_rest'  => array(
					'schema' => array(
						'type' => 'integer',
					),
				),
				'auth_callback' => function () {
					return current_user_can( 'edit_products' );
				},
			)
		);
	}

	/**
	 * Add fields to WooCommerce block editor
	 * Uses the product template filter to add custom fields
	 *
	 * @param array $templates Product templates.
	 * @return array
	 */
	public function add_block_editor_fields( $templates ) {
		// Check if WooCommerce block editor is available
		if ( ! function_exists( 'wc_get_container' ) || ! class_exists( '\Automattic\WooCommerce\Internal\Features\ProductBlockEditor\ProductTemplates\AbstractProductFormTemplate' ) ) {
			return $templates;
		}

		// Ensure templates is an array
		if ( ! is_array( $templates ) ) {
			return $templates;
		}

		// Iterate through templates to find product templates
		foreach ( $templates as $index => $template ) {
			if ( ! is_object( $template ) ) {
				continue;
			}

			// Check if template has required methods
			if ( ! method_exists( $template, 'get_id' ) || ! method_exists( $template, 'get_group_by_id' ) ) {
				continue;
			}

			try {
				$template_id = $template->get_id();

				// Add to product templates (simple-product, variable-product, etc.)
				// Skip variation templates as they inherit from parent
				if ( strpos( $template_id, 'variation' ) !== false ) {
					continue;
				}

				// Get the general tab/group
				$general_group = $template->get_group_by_id( 'general' );

				if ( ! $general_group || ! method_exists( $general_group, 'add_section' ) ) {
					continue;
				}

				// Add a new section for reorder reminder settings
				$reminder_section = $general_group->add_section(
					array(
						'id'         => 'reorder-reminder-section',
						'order'      => 25,
						'attributes' => array(
							'title'       => __( 'Re-Order Reminder', 'easy-re-order-reminder-for-woocommerce' ),
							'description' => __( 'Configure reorder reminder settings for this product', 'easy-re-order-reminder-for-woocommerce' ),
						),
					)
				);

				if ( $reminder_section && method_exists( $reminder_section, 'add_block' ) ) {
					// Add enable toggle field
					// Note: In WooCommerce 10.x, meta fields are accessed via meta_data array in REST API
					$reminder_section->add_block(
						array(
							'id'         => 'reorder-reminder-enable',
							'blockName'  => 'woocommerce/product-toggle-field',
							'order'      => 10,
							'attributes' => array(
								'label'          => __( 'Enable reminder', 'easy-re-order-reminder-for-woocommerce' ),
								'property'       => 'meta_data._easyrere_enable',
								'help'           => __( 'Enable reorder reminders for this product', 'easy-re-order-reminder-for-woocommerce' ),
								'checkedValue'   => 'yes',
								'uncheckedValue' => 'no',
							),
						)
					);

					// Add reminder days number field
					$reminder_section->add_block(
						array(
							'id'         => 'reorder-reminder-days',
							'blockName'  => 'woocommerce/product-number-field',
							'order'      => 20,
							'attributes' => array(
								'label'    => __( 'Reminder days', 'easy-re-order-reminder-for-woocommerce' ),
								'property' => 'meta_data._easyrere_reminder_days',
								'help'     => __( 'Days after order completion to send reminder. Leave empty to use global setting.', 'easy-re-order-reminder-for-woocommerce' ),
								'min'      => 1,
								'step'     => 1,
							),
						)
					);
				}
			} catch ( Exception $e ) {
				// Log error but don't break the filter
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging only when WP_DEBUG is enabled
					error_log( 'Easy Re-Order Reminder: Error adding block editor fields - ' . $e->getMessage() );
				}
				continue;
			}
		}

		return $templates;
	}

	/**
	 * Add meta fields to REST API response
	 * WooCommerce 10.x uses meta_data array format
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WC_Product       $product  Product object.
	 * @param WP_REST_Request  $request Request object.
	 * @return WP_REST_Response
	 */
	public function add_meta_to_rest_response( $response, $product, $request ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return $response;
		}

		$data = $response->get_data();

		// Prevent unused parameter warnings in analysis tools.
		unset( $request );

		// Ensure meta_data array exists
		if ( ! isset( $data['meta_data'] ) || ! is_array( $data['meta_data'] ) ) {
			$data['meta_data'] = array();
		}

		// Get our meta field values
		$enable = get_post_meta( $product->get_id(), '_easyrere_enable', true );
		$days   = get_post_meta( $product->get_id(), '_easyrere_reminder_days', true );

		// Check if meta already exists in meta_data array
		$enable_exists = false;
		$days_exists   = false;

		foreach ( $data['meta_data'] as $index => $meta ) {
			if ( is_object( $meta ) && isset( $meta->key ) ) {
				if ( '_easyrere_enable' === $meta->key ) {
					$enable_exists                      = true;
					$data['meta_data'][ $index ]->value = $enable ? $enable : 'yes';
				}
				if ( '_easyrere_reminder_days' === $meta->key ) {
					$days_exists = true;
					if ( $days ) {
						$data['meta_data'][ $index ]->value = absint( $days );
					} else {
						unset( $data['meta_data'][ $index ] );
					}
				}
			} elseif ( is_array( $meta ) && isset( $meta['key'] ) ) {
				if ( '_easyrere_enable' === $meta['key'] ) {
					$enable_exists                        = true;
					$data['meta_data'][ $index ]['value'] = $enable ? $enable : 'yes';
				}
				if ( '_easyrere_reminder_days' === $meta['key'] ) {
					$days_exists = true;
					if ( $days ) {
						$data['meta_data'][ $index ]['value'] = absint( $days );
					} else {
						unset( $data['meta_data'][ $index ] );
					}
				}
			}
		}

		// Add meta if it doesn't exist
		if ( ! $enable_exists ) {
			$data['meta_data'][] = array(
				'key'   => '_easyrere_enable',
				'value' => $enable ? $enable : 'yes',
			);
		}

		if ( ! $days_exists && $days ) {
			$data['meta_data'][] = array(
				'key'   => '_easyrere_reminder_days',
				'value' => absint( $days ),
			);
		}

		// Re-index array
		$data['meta_data'] = array_values( $data['meta_data'] );

		$response->set_data( $data );

		return $response;
	}

	/**
	 * Update meta fields from REST API request
	 * Handles both direct params and meta_data array format
	 *
	 * @param WC_Product      $product Product object.
	 * @param WP_REST_Request $request Request object.
	 */
	public function update_meta_from_rest( $product, $request ) {
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$params     = $request->get_params();
		$product_id = $product->get_id();

		// Check if meta_data array is provided (WooCommerce 10.x format)
		if ( isset( $params['meta_data'] ) && is_array( $params['meta_data'] ) ) {
			foreach ( $params['meta_data'] as $meta ) {
				$key   = is_array( $meta ) ? ( isset( $meta['key'] ) ? $meta['key'] : '' ) : ( isset( $meta->key ) ? $meta->key : '' );
				$value = is_array( $meta ) ? ( isset( $meta['value'] ) ? $meta['value'] : '' ) : ( isset( $meta->value ) ? $meta->value : '' );

				if ( '_easyrere_enable' === $key ) {
					update_post_meta( $product_id, '_easyrere_enable', sanitize_text_field( $value ) );
				} elseif ( '_easyrere_reminder_days' === $key ) {
					$days = absint( $value );
					if ( 0 < $days ) {
						update_post_meta( $product_id, '_easyrere_reminder_days', $days );
					} else {
						delete_post_meta( $product_id, '_easyrere_reminder_days' );
					}
				}
			}
		}

		// Also check direct params (fallback)
		if ( isset( $params['_easyrere_enable'] ) ) {
			update_post_meta( $product_id, '_easyrere_enable', sanitize_text_field( $params['_easyrere_enable'] ) );
		}

		if ( isset( $params['_easyrere_reminder_days'] ) ) {
			$days = absint( $params['_easyrere_reminder_days'] );
			if ( 0 < $days ) {
				update_post_meta( $product_id, '_easyrere_reminder_days', $days );
			} else {
				delete_post_meta( $product_id, '_easyrere_reminder_days' );
			}
		}
	}
}

