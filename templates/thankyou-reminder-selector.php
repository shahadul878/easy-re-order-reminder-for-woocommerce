<?php
/**
 * Thank You Page Reminder Day Selector Template
 *
 * @package WRR
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are local scope
// Check if reminders are enabled
$reminders_enabled = get_option( 'easyrere_enable_reminder', 'yes' );
if ( 'yes' !== $reminders_enabled ) {
	// Debug: Uncomment to see why selector is not showing
	// error_log( 'WRR Debug: Reminders disabled globally' );
	return;
}

// Get available reminder day options
$default_days = absint( get_option( 'easyrere_reminder_days', 30 ) );
$day_options  = apply_filters( 'easyrere_reminder_day_options', array( 15, 30, 45, 60, 90 ) );

// Check if customer already selected a preference (HPOS compatible)
$selected_days = $order->get_meta( '_easyrere_customer_reminder_days' );
$selected_days = $selected_days ? absint( $selected_days ) : $default_days;

// Check if order has products with reminders enabled
$has_reminder_products = false;
foreach ( $order->get_items() as $item ) {
	$product_id = $item->get_product_id();
	if ( $product_id ) {
		$product_enabled = get_post_meta( $product_id, '_easyrere_enable', true );
		if ( 'no' !== $product_enabled ) {
			$has_reminder_products = true;
			break;
		}
	}
}

if ( ! $has_reminder_products ) {
	// Debug: Uncomment to see why selector is not showing
	// error_log( 'WRR Debug: No products with reminders enabled in order #' . $order->get_id() );
	return;
}
?>

<div class="easyrere-thankyou-wrapper">
	<div class="easyrere-reminder-card">
		<div class="easyrere-reminder-header">
			<h3 class="easyrere-reminder-title"><?php esc_html_e( 'When would you like a reminder to reorder?', 'easy-re-order-reminder-for-woocommerce' ); ?></h3>
			<p class="easyrere-reminder-desc"><?php esc_html_e( 'Choose when you\'d like to receive a reminder email to reorder these products:', 'easy-re-order-reminder-for-woocommerce' ); ?></p>
		</div>
	
		<form id="easyrere-reminder-days-form" method="post">
			<input type="hidden" name="easyrere_order_id" value="<?php echo esc_attr( $order->get_id() ); ?>" />
			<input type="hidden" name="easyrere_nonce" value="<?php echo esc_attr( wp_create_nonce( 'easyrere_save_reminder_days_' . $order->get_id() ) ); ?>" />
			
			<div class="easyrere-form-group">
				<div class="easyrere-select-wrapper">
					<select name="easyrere_reminder_days" id="easyrere_reminder_days" class="easyrere-select">
						<?php foreach ( $day_options as $days ) : ?>
							<?php $days = absint( $days ); // Ensure days is an integer ?>
							<option value="<?php echo esc_attr( $days ); ?>" <?php selected( $selected_days, $days ); ?>>
								<?php
								if ( 1 === $days ) {
									/* translators: %d: number of days (singular) */
									printf( esc_html__( '%d day', 'easy-re-order-reminder-for-woocommerce' ), esc_html( $days ) );
								} else {
									/* translators: %d: number of days (plural) */
									printf( esc_html__( '%d days', 'easy-re-order-reminder-for-woocommerce' ), esc_html( $days ) );
								}
								?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				
				<button type="submit" class="easyrere-btn">
					<span class="easyrere-btn-text"><?php esc_html_e( 'Save Preference', 'easy-re-order-reminder-for-woocommerce' ); ?></span>
				</button>
			</div>
		</form>
		
		<div id="easyrere-reminder-message" class="easyrere-message" style="display: none;"></div>
	</div>
</div>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

