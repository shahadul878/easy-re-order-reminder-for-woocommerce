<?php
/**
 * Re-Order Reminder Email Template
 *
 * @package WRR
 * @var WC_Order $order
 * @var WC_Product $product
 * @var string $email_heading
 * @var string $additional_content
 * @var bool $sent_to_admin
 * @var bool $plain_text
 * @var EASYRERE_Email $email
 */

defined('ABSPATH') || exit;

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce hook
do_action('woocommerce_email_header', $email_heading, $email); ?>

<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are local scope
// Handle preview mode where order/product might be null
$customer_name = $order && method_exists($order, 'get_billing_first_name')
	? ( $order->get_billing_first_name() ? $order->get_billing_first_name() : __('Customer', 'easy-re-order-reminder-for-woocommerce') )
	: __('Customer', 'easy-re-order-reminder-for-woocommerce');

$product_name = $product && method_exists($product, 'get_name')
	? $product->get_name()
	: __('Sample Product', 'easy-re-order-reminder-for-woocommerce');

$product_id = $product && method_exists($product, 'get_id')
	? $product->get_id()
	: 0;

$reorder_link = $product_id > 0
	? add_query_arg('add-to-cart', $product_id, wc_get_cart_url())
	: wc_get_cart_url();

$customer_email = $order && method_exists($order, 'get_billing_email')
	? $order->get_billing_email()
	: 'customer@example.com';

$unsubscribe_link = add_query_arg(
	array(
		'easyrere_unsubscribe' => 1,
		'email'            => rawurlencode($customer_email),
		'nonce'            => wp_create_nonce('easyrere_unsubscribe_' . $customer_email),
	),
	home_url()
);
?>

<div style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; color: #3c434a; line-height: 1.6;">
    <p style="margin-bottom: 20px; font-size: 16px;"><?php
    printf(
        /* translators: %s: customer name */
        esc_html__('Hi %s,', 'easy-re-order-reminder-for-woocommerce'),
        esc_html($customer_name)
    );
    ?></p>

    <p style="margin-bottom: 30px; font-size: 16px; color: #646970;"><?php
    printf(
        /* translators: %s: product name */
        esc_html__('It looks like it’s about time to reorder %s. We wanted to make it easy for you to stock up again.', 'easy-re-order-reminder-for-woocommerce'),
        '<strong style="color: #111;">' . esc_html($product_name) . '</strong>'
    );
    ?></p>

    <div style="text-align: center; margin: 40px 0;">
        <a href="<?php echo esc_url($reorder_link); ?>" style="background-color: #7f54b3; color: #ffffff; padding: 15px 35px; text-decoration: none; border-radius: 4px; display: inline-block; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(50, 50, 93, 0.11), 0 1px 3px rgba(0, 0, 0, 0.08);">
            <?php esc_html_e('Re-Order Now', 'easy-re-order-reminder-for-woocommerce'); ?>
        </a>
    </div>

    <?php if ($additional_content) : ?>
        <div style="margin-bottom: 30px; padding: 20px; background-color: #f6f7f7; border-radius: 4px;">
            <p style="margin: 0;"><?php echo wp_kses_post($additional_content); ?></p>
        </div>
    <?php endif; ?>

    <hr style="border: 0; border-top: 1px solid #f0f0f1; margin: 30px 0;" />

    <p style="font-size: 13px; color: #8c8f94; text-align: center;">
        <?php esc_html_e('If you no longer wish to receive reorder reminders for this product, you can', 'easy-re-order-reminder-for-woocommerce'); ?>
        <a href="<?php echo esc_url($unsubscribe_link); ?>" style="color: #7f54b3; text-decoration: underline;"><?php esc_html_e('unsubscribe here', 'easy-re-order-reminder-for-woocommerce'); ?></a>.
    </p>
</div>

<?php
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce hook
do_action('woocommerce_email_footer', $email);
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

