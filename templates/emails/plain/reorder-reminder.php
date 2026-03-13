<?php

/**
 * Re-Order Reminder Email Template (Plain)
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

defined( 'ABSPATH' ) || exit;

echo '= ' . esc_html( $email_heading ) . " =\n\n";

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are local scope
// Handle preview mode where order/product might be null
$customer_name = $order && method_exists( $order, 'get_billing_first_name' )
	? ( $order->get_billing_first_name() ? $order->get_billing_first_name() : __( 'Customer', 'easy-re-order-reminder-for-woocommerce' ) )
	: __( 'Customer', 'easy-re-order-reminder-for-woocommerce' );

$product_name = $product && method_exists( $product, 'get_name' )
	? $product->get_name()
	: __( 'Sample Product', 'easy-re-order-reminder-for-woocommerce' );

$product_id = $product && method_exists( $product, 'get_id' )
	? $product->get_id()
	: 0;

$reorder_link = $product_id > 0
	? add_query_arg( 'add-to-cart', $product_id, wc_get_cart_url() )
	: wc_get_cart_url();

$customer_email = $order && method_exists( $order, 'get_billing_email' )
	? $order->get_billing_email()
	: 'customer@example.com';

$unsubscribe_link = add_query_arg(
	array(
		'easyrere_unsubscribe' => 1,
		'email'                => rawurlencode( $customer_email ),
		'nonce'                => wp_create_nonce( 'easyrere_unsubscribe_' . $customer_email ),
	),
	home_url()
);

printf(
	/* translators: %s: customer name */
	esc_html__( 'Hi %s,', 'easy-re-order-reminder-for-woocommerce' ),
	esc_html( $customer_name )
);
echo "\n\n";

printf(
	/* translators: %s: product name */
	esc_html__( 'It\'s been a while since you last purchased %s. We wanted to remind you to reorder if you need it again.', 'easy-re-order-reminder-for-woocommerce' ),
	esc_html( $product_name )
);
echo "\n\n";

echo esc_html__( 'Re-Order Now:', 'easy-re-order-reminder-for-woocommerce' ) . "\n";
echo esc_url( $reorder_link ) . "\n\n";

if ( $additional_content ) {
	echo wp_kses_post( $additional_content ) . "\n\n";
}

echo esc_html__( 'If you no longer wish to receive these reminders, you can unsubscribe here:', 'easy-re-order-reminder-for-woocommerce' ) . "\n";
echo esc_url( $unsubscribe_link ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core WooCommerce hook
do_action( 'woocommerce_email_footer', $email );
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound