<?php

/**
 * Settings Page Class
 *
 * @package WRR
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Settings_Page' ) ) {
	return;
}

/**
 * EASYRERE_Settings_Page Class
 */
class EASYRERE_Settings_Page extends WC_Settings_Page {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id    = 'easyrere_settings';
		$this->label = __( 'Re-Order Reminder', 'easy-re-order-reminder-for-woocommerce' );

		parent::__construct();

		// Handle test email AJAX
		add_action( 'wp_ajax_easyrere_send_test_email', array( $this, 'send_test_email_ajax' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Output the settings
	 */
	public function output() {
		// Include the custom view
		require_once EASYRERE_PATH . 'includes/views/admin-settings-page.php';
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Page parameter check only, no form processing
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab parameter check only, no form processing
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		if ( 'wc-settings' === $page && 'easyrere_settings' === $tab ) {
			wp_enqueue_style( 'easyrere-admin-css', EASYRERE_URL . 'assets/css/admin.css', array(), EASYRERE_VERSION );
			wp_enqueue_script( 'easyrere-settings-page', EASYRERE_URL . 'assets/js/settings-page.js', array( 'jquery' ), EASYRERE_VERSION, true );
			wp_localize_script(
				'easyrere-settings-page',
				'easyrereSettings',
				array(
					'nonce' => wp_create_nonce( 'easyrere_test_email' ),
					'i18n'  => array(
						'enterEmail' => __( 'Please enter an email address', 'easy-re-order-reminder-for-woocommerce' ),
						'sending'    => __( 'Sending...', 'easy-re-order-reminder-for-woocommerce' ),
						'success'    => __( 'Test email sent successfully!', 'easy-re-order-reminder-for-woocommerce' ),
						'error'      => __( 'Error sending email', 'easy-re-order-reminder-for-woocommerce' ),
					),
				)
			);
		}
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = array(
			array(
				'title' => __( 'Re-Order Reminder Settings', 'easy-re-order-reminder-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'Configure automatic reorder reminders for your customers.', 'easy-re-order-reminder-for-woocommerce' ),
				'id'    => 'easyrere_settings_title',
			),
			array(
				'title'    => __( 'Enable Reminder', 'easy-re-order-reminder-for-woocommerce' ),
				'desc'     => __( 'Enable reorder reminders', 'easy-re-order-reminder-for-woocommerce' ),
				'id'       => 'easyrere_enable_reminder',
				'default'  => 'yes',
				'type'     => 'checkbox',
				'desc_tip' => __( 'Uncheck to disable all reorder reminders.', 'easy-re-order-reminder-for-woocommerce' ),
			),
			array(
				'title'             => __( 'Reminder Days', 'easy-re-order-reminder-for-woocommerce' ),
				'desc'              => __( 'Number of days after order completion to send reminder', 'easy-re-order-reminder-for-woocommerce' ),
				'id'                => 'easyrere_reminder_days',
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '1',
				),
				'default'           => '30',
				'desc_tip'          => __( 'This can be overridden per product.', 'easy-re-order-reminder-for-woocommerce' ),
			),
			array(
				'title' => __( 'Test Reminder Email', 'easy-re-order-reminder-for-woocommerce' ),
				'desc'  => __( 'Send a test email to verify email settings', 'easy-re-order-reminder-for-woocommerce' ),
				'id'    => 'easyrere_test_email',
				'type'  => 'easyrere_test_email',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'easyrere_settings_end',
			),
		);

		return apply_filters( 'easyrere_settings', $settings );
	}

	/**
	 * Output custom field type
	 *
	 * @param array $value Field value.
	 */
	public function output_easyrere_test_email_field( $value ) {
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( isset( $value['id'] ) ? $value['id'] : '' ); ?>"><?php echo esc_html( isset( $value['title'] ) ? $value['title'] : '' ); ?></label>
			</th>
			<td class="forminp">
				<div class="easyrere-test-email-container">
					<input
						type="email"
						id="easyrere_test_email_address"
						name="easyrere_test_email_address"
						placeholder="<?php esc_attr_e( 'Enter email address', 'easy-re-order-reminder-for-woocommerce' ); ?>"
						class="regular-text"
					/>
					<button type="button" class="button button-secondary" id="easyrere_send_test_email">
						<?php esc_html_e( 'Send Test Email', 'easy-re-order-reminder-for-woocommerce' ); ?>
					</button>
				</div>
				<?php if ( isset( $value['desc'] ) && ! empty( $value['desc'] ) ) : ?>
					<p class="description"><?php echo esc_html( $value['desc'] ); ?></p>
				<?php endif; ?>
				<div id="easyrere_test_email_result" style="margin-top: 10px;"></div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Send test email via AJAX
	 */
	public function send_test_email_ajax() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'easyrere_test_email' ) ) {
			wp_send_json_error( __( 'Invalid security token.', 'easy-re-order-reminder-for-woocommerce' ) );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( __( 'Permission denied', 'easy-re-order-reminder-for-woocommerce' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above via check_ajax_referer
		if ( ! isset( $_POST['email'] ) ) {
			wp_send_json_error( __( 'Email address is required', 'easy-re-order-reminder-for-woocommerce' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above via check_ajax_referer
		$email = sanitize_email( wp_unslash( $_POST['email'] ) );
		if ( ! $email ) {
			wp_send_json_error( __( 'Invalid email address', 'easy-re-order-reminder-for-woocommerce' ) );
		}

		// Create a mock order for testing
		$test_order = new WC_Order();
		$test_order->set_billing_email( $email );
		$test_order->set_billing_first_name( __( 'Test', 'easy-re-order-reminder-for-woocommerce' ) );

		// Get first product
		$products = wc_get_products( array( 'limit' => 1 ) );
		if ( empty( $products ) ) {
			wp_send_json_error( __( 'No products found. Please create at least one product first.', 'easy-re-order-reminder-for-woocommerce' ) );
		}

		$product     = $products[0];
		$email_class = EASYRERE_Email::instance();
		$email_class->trigger( $test_order, $product->get_id() );

		wp_send_json_success( __( 'Test email sent successfully!', 'easy-re-order-reminder-for-woocommerce' ) );
	}
}

