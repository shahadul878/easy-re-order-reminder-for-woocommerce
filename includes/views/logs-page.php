<?php

/**
 * Logs Page View
 *
 * @package WRR
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are local scope
$logs          = EASYRERE_Logger::get_logs();
$sent_count    = EASYRERE_Logger::get_log_count( 'sent' );
$pending_count = EASYRERE_Logger::get_log_count( 'pending' );
$failed_count  = EASYRERE_Logger::get_log_count( 'failed' );
?>
<div class="wrap easyrere-settings-page">
	<div class="easyrere-header">
		<h1><?php esc_html_e( 'Re-Order Reminder Logs', 'easy-re-order-reminder-for-woocommerce' ); ?></h1>
	</div>

	<div class="easyrere-stats-overview">
		<div class="easyrere-stat-box">
			<strong><?php esc_html_e( 'Sent', 'easy-re-order-reminder-for-woocommerce' ); ?></strong>
			<div class="count" style="color: var(--easyrere-success);"><?php echo esc_html( $sent_count ); ?></div>
		</div>
		<div class="easyrere-stat-box">
			<strong><?php esc_html_e( 'Pending', 'easy-re-order-reminder-for-woocommerce' ); ?></strong>
			<div class="count" style="color: var(--easyrere-warning);"><?php echo esc_html( $pending_count ); ?></div>
		</div>
		<div class="easyrere-stat-box">
			<strong><?php esc_html_e( 'Failed', 'easy-re-order-reminder-for-woocommerce' ); ?></strong>
			<div class="count" style="color: var(--easyrere-error);"><?php echo esc_html( $failed_count ); ?></div>
		</div>
	</div>

	<div class="easyrere-card">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'easy-re-order-reminder-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Order ID', 'easy-re-order-reminder-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Product', 'easy-re-order-reminder-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Email', 'easy-re-order-reminder-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Status', 'easy-re-order-reminder-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Sent At', 'easy-re-order-reminder-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'easy-re-order-reminder-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $logs ) ) : ?>
					<tr>
						<td colspan="7" style="text-align: center; padding: 20px;">
							<?php esc_html_e( 'No logs found.', 'easy-re-order-reminder-for-woocommerce' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $logs as $log ) : ?>
						<?php
						$product      = wc_get_product( $log['product_id'] );
						$product_name = $product ? $product->get_name() : __( 'Product not found', 'easy-re-order-reminder-for-woocommerce' );
						$status_class = 'sent' === $log['status'] ? 'sent' : ( 'failed' === $log['status'] ? 'failed' : 'pending' );
						?>
						<tr>
							<td><?php echo esc_html( $log['id'] ); ?></td>
							<td>
								<?php
								$order_edit_url = admin_url( 'post.php?post=' . $log['order_id'] . '&action=edit' );
								if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
									$order_edit_url = \Automattic\WooCommerce\Utilities\OrderUtil::get_order_admin_edit_url( (int) $log['order_id'] );
								}
								?>
								<a href="<?php echo esc_url( $order_edit_url ); ?>">
									#<?php echo esc_html( $log['order_id'] ); ?>
								</a>
							</td>
							<td>
								<?php if ( $product ) : ?>
									<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $log['product_id'] . '&action=edit' ) ); ?>">
										<?php echo esc_html( $product_name ); ?>
									</a>
								<?php else : ?>
									<?php echo esc_html( $product_name ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $log['email'] ); ?></td>
							<td>
								<span class="easyrere-badge <?php echo esc_attr( $status_class ); ?>">
									<?php echo esc_html( ucfirst( $log['status'] ) ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $log['sent_at'] ); ?></td>
							<td>
								<button type="button" class="button button-small easyrere-resend-email" 
									data-id="<?php echo esc_attr( $log['id'] ); ?>"
									data-nonce="<?php echo esc_attr( wp_create_nonce( 'easyrere_resend_email_' . $log['id'] ) ); ?>">
									<?php esc_html_e( 'Resend', 'easy-re-order-reminder-for-woocommerce' ); ?>
								</button>
								<span class="easyrere-resend-status" style="margin-left: 5px;"></span>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

