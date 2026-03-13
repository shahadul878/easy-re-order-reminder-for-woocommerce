<?php

/**
 * Email Handler Class.
 *
 * Handles the reorder reminder email for customers.
 *
 * @package Easy_Reorder_Reminder_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Email' ) ) {
	return;
}

if ( ! class_exists( 'EASYRERE_Email' ) ) {
	/**
	 * EASYRERE_Email Class.
	 *
	 * Email implementation for reorder reminders.
	 */
	class EASYRERE_Email extends WC_Email {

		/**
		 * Instance
		 *
		 * @var EASYRERE_Email
		 */
		private static $instance = null;

		/**
		 * Get instance
		 *
		 * @return EASYRERE_Email
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
			$this->id             = 'easyrere_reorder_reminder';
			$this->title          = __( 'Re-Order Reminder', 'easy-re-order-reminder-for-woocommerce' );
			$this->description    = __( 'Email sent to customers to remind them to reorder products.', 'easy-re-order-reminder-for-woocommerce' );
			$this->customer_email = true;
			$this->template_html  = 'emails/reorder-reminder.php';
			$this->template_plain = 'emails/plain/reorder-reminder.php';
			$this->template_base  = EASYRERE_PATH . 'templates/';
			$this->placeholders   = array(
				'{customer_name}' => '',
				'{product_name}'  => '',
				'{reorder_link}'  => '',
			);

			// Call parent constructor
			parent::__construct();

			// Set up preview data if in preview mode
			add_filter( 'woocommerce_prepare_email_for_preview', array( $this, 'prepare_preview_data' ), 10, 1 );

			// Help WooCommerce locate our template files
			add_filter( 'woocommerce_locate_core_template', array( $this, 'locate_template' ), 10, 4 );
		}

		/**
		 * Get email subject
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'Time to reorder {product_name}!', 'easy-re-order-reminder-for-woocommerce' );
		}

		/**
		 * Get email heading
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Don\'t forget to reorder {product_name}', 'easy-re-order-reminder-for-woocommerce' );
		}

		/**
		 * Get email content
		 *
		 * @return string
		 */
		public function get_default_content() {
			return __( 'Hi {customer_name},', 'easy-re-order-reminder-for-woocommerce' ) . "\n\n" .
			__( 'It\'s been a while since you last purchased {product_name}. We wanted to remind you to reorder if you need it again.', 'easy-re-order-reminder-for-woocommerce' ) . "\n\n" .
			__( 'Click here to add it to your cart: {reorder_link}', 'easy-re-order-reminder-for-woocommerce' ) . "\n\n" .
			__( 'If you no longer wish to receive these reminders, you can unsubscribe here: {unsubscribe_link}', 'easy-re-order-reminder-for-woocommerce' );
		}

		/**
		 * Trigger email
		 *
		 * @param WC_Order $order Order object.
		 * @param int      $product_id Product ID.
		 */
		public function trigger( $order, $product_id ) {
			if ( ! $order || ! $product_id ) {
				return;
			}

			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				return;
			}

			$this->object     = $order;
			$this->product    = $product;
			$this->recipient  = $order->get_billing_email();
			$this->product_id = $product_id;

			if ( ! $this->is_enabled() || ! $this->get_recipient() ) {
				return;
			}

			// Set placeholders
			$this->placeholders['{customer_name}']    = $order->get_billing_first_name() ? $order->get_billing_first_name() : __( 'Customer', 'easy-re-order-reminder-for-woocommerce' );
			$this->placeholders['{product_name}']     = $product->get_name();
			$this->placeholders['{reorder_link}']     = $this->get_reorder_link( $product_id );
			$this->placeholders['{unsubscribe_link}'] = $this->get_unsubscribe_link( $this->recipient );

			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		/**
		 * Get reorder link
		 *
		 * @param int $product_id Product ID.
		 * @return string
		 */
		private function get_reorder_link( $product_id ) {
			$cart_url = wc_get_cart_url();
			$link     = add_query_arg( 'add-to-cart', $product_id, $cart_url );
			return apply_filters( 'easyrere_reorder_link', $link, $product_id );
		}

		/**
		 * Get unsubscribe link
		 *
		 * @param string $email Customer email.
		 * @return string
		 */
		private function get_unsubscribe_link( $email ) {
			$nonce = wp_create_nonce( 'easyrere_unsubscribe_' . $email );
			$link  = add_query_arg(
				array(
					'easyrere_unsubscribe' => 1,
					'email'                => rawurlencode( $email ),
					'nonce'                => $nonce,
				),
				home_url()
			);
			return $link;
		}

		/**
		 * Locate template file for WooCommerce
		 *
		 * @param string $core_file     Core template file path.
		 * @param string $template       Template name.
		 * @param string $template_base  Template base path.
		 * @param string $email_id       Email ID.
		 * @return string
		 */
		public function locate_template( $core_file, $template, $template_base, $email_id ) {
			// Only handle our email templates
			if ( $email_id === $this->id ) {
				$plugin_template = EASYRERE_PATH . 'templates/' . $template;
				if ( file_exists( $plugin_template ) ) {
					return $plugin_template;
				}
			}
			return $core_file;
		}

		/**
		 * Prepare preview data for email preview
		 *
		 * @param WC_Email $email Email object.
		 * @return WC_Email
		 */
		public function prepare_preview_data( $email ) {
			if ( $email === $this ) {
				// Set dummy order if not set
				if ( ! $this->object ) {
					$orders = wc_get_orders(
						array(
							'limit'   => 1,
							'orderby' => 'date',
							'order'   => 'DESC',
							'status'  => array( 'completed', 'processing' ),
						)
					);
					if ( ! empty( $orders ) ) {
						$this->object = $orders[0];
					}
				}

				// Set dummy product if not set
				if ( ! $this->product ) {
					$products = wc_get_products(
						array(
							'limit'  => 1,
							'status' => 'publish',
						)
					);
					if ( ! empty( $products ) ) {
						$this->product = $products[0];
					}
				}
			}
			return $email;
		}

		/**
		 * Get content html
		 *
		 * @return string
		 */
		public function get_content_html() {
			// Ensure we have order and product for preview
			if ( ! $this->object || ! $this->product ) {
				$this->prepare_preview_data( $this );
			}

			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'product'            => $this->product,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
				),
				'',
				EASYRERE_PATH . 'templates/'
			);
		}

		/**
		 * Get content plain
		 *
		 * @return string
		 */
		public function get_content_plain() {
			// Ensure we have order and product for preview
			if ( ! $this->object || ! $this->product ) {
				$this->prepare_preview_data( $this );
			}

			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'product'            => $this->product,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				),
				'',
				EASYRERE_PATH . 'templates/'
			);
		}

		/**
		 * Initialize settings form fields
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				'enabled'            => array(
					'title'   => __( 'Enable/Disable', 'easy-re-order-reminder-for-woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Enable this email notification', 'easy-re-order-reminder-for-woocommerce' ),
					'default' => 'yes',
				),
				'subject'            => array(
					'title'       => __( 'Subject', 'easy-re-order-reminder-for-woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					/* translators: %s: list of available placeholders */
					'description' => sprintf( __( 'Available placeholders: %s', 'easy-re-order-reminder-for-woocommerce' ), '{customer_name}, {product_name}, {reorder_link}' ),
					'placeholder' => $this->get_default_subject(),
					'default'     => '',
				),
				'heading'            => array(
					'title'       => __( 'Email heading', 'easy-re-order-reminder-for-woocommerce' ),
					'type'        => 'text',
					'desc_tip'    => true,
					/* translators: %s: list of available placeholders */
					'description' => sprintf( __( 'Available placeholders: %s', 'easy-re-order-reminder-for-woocommerce' ), '{customer_name}, {product_name}, {reorder_link}' ),
					'placeholder' => $this->get_default_heading(),
					'default'     => '',
				),
				'additional_content' => array(
					'title'       => __( 'Additional content', 'easy-re-order-reminder-for-woocommerce' ),
					'description' => __( 'Text to appear below the main email content.', 'easy-re-order-reminder-for-woocommerce' ),
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => __( 'N/A', 'easy-re-order-reminder-for-woocommerce' ),
					'type'        => 'textarea',
					'default'     => '',
					'desc_tip'    => true,
				),
				'email_type'         => array(
					'title'       => __( 'Email type', 'easy-re-order-reminder-for-woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Choose which format of email to send.', 'easy-re-order-reminder-for-woocommerce' ),
					'default'     => 'html',
					'class'       => 'email_type wc-enhanced-select',
					'options'     => $this->get_email_type_options(),
					'desc_tip'    => true,
				),
			);
		}

		/**
		 * Send reorder email (static method for easy access)
		 *
		 * @param WC_Order $order Order object.
		 * @param int      $product_id Product ID.
		 * @return bool
		 */
		public static function send_reorder_email( $order, $product_id ) {
			$email = self::instance();
			$email->trigger( $order, $product_id );

			// Log email sent
			EASYRERE_Logger::log( $order->get_id(), $product_id, $order->get_billing_email(), 'sent' );

			return true;
		}
	} // End class EASYRERE_Email
} // End if class_exists check
