<?php
/**
 * Admin Settings Page View
 *
 * @package WRR
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables are local scope
// Get current settings
$enable_reminder = get_option( 'easyrere_enable_reminder', 'yes' );
$reminder_days   = get_option( 'easyrere_reminder_days', 30 );
$unsubscribed    = get_option( 'easyrere_unsubscribed_emails', array() );

// Get statistics
$sent_count    = EASYRERE_Logger::get_log_count( 'sent' );
$pending_count = EASYRERE_Logger::get_log_count( 'pending' );
$failed_count  = EASYRERE_Logger::get_log_count( 'failed' );
$total_count   = EASYRERE_Logger::get_log_count();
?>

<div class="easyrere-settings-page">
	<div class="easyrere-grid">
		<div class="easyrere-settings-main">
			<!-- Settings Fields are auto-saved by WooCommerce because we use matching name attributes -->
			
				<div class="easyrere-card">
					<h2><?php esc_html_e( 'General Settings', 'easy-re-order-reminder-for-woocommerce' ); ?></h2>

					<div class="easyrere-form-row">
						<label for="easyrere_enable_reminder">
							<input type="checkbox" name="easyrere_enable_reminder" id="easyrere_enable_reminder" value="yes" <?php checked( $enable_reminder, 'yes' ); ?> />
							<?php esc_html_e( 'Enable Reminder System', 'easy-re-order-reminder-for-woocommerce' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Uncheck to disable all reminder emails globally.', 'easy-re-order-reminder-for-woocommerce' ); ?></p>
					</div>

					<div class="easyrere-form-row">
						<label for="easyrere_reminder_days"><?php esc_html_e( 'Default Reminder Days', 'easy-re-order-reminder-for-woocommerce' ); ?></label>
						<input type="number" name="easyrere_reminder_days" id="easyrere_reminder_days" value="<?php echo esc_attr( $reminder_days ); ?>" min="1" step="1" class="small-text" />
						<p class="description"><?php esc_html_e( 'Number of days after order completion to send reminder. This can be overridden per product or by customer preference.', 'easy-re-order-reminder-for-woocommerce' ); ?></p>
					</div>
				</div>

				<div class="easyrere-card">
					<h2><?php esc_html_e( 'Email Settings', 'easy-re-order-reminder-for-woocommerce' ); ?></h2>
					<p>
						<?php esc_html_e( 'Email templates and settings are managed in', 'easy-re-order-reminder-for-woocommerce' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=easyrere_reorder_reminder' ) ); ?>">
							<?php esc_html_e( 'WooCommerce → Settings → Emails → Re-Order Reminder', 'easy-re-order-reminder-for-woocommerce' ); ?>
						</a>
					</p>
					<div class="easyrere-test-email-section">
						<p>
							<button type="button" class="button" id="easyrere_send_test_email_btn">
								<?php esc_html_e( 'Send Test Email', 'easy-re-order-reminder-for-woocommerce' ); ?>
							</button>
							<span id="easyrere_test_email_result" style="margin-left: 10px;"></span>
						</p>
						<div id="easyrere_test_email_form" style="margin-top: 15px; display: none;">
							<div class="easyrere-test-email-container">
								<input type="email" id="easyrere_test_email_address" placeholder="<?php esc_attr_e( 'Enter email address', 'easy-re-order-reminder-for-woocommerce' ); ?>" class="regular-text" />
								<button type="button" class="button button-primary" id="easyrere_send_test_email_submit">
									<?php esc_html_e( 'Send', 'easy-re-order-reminder-for-woocommerce' ); ?>
								</button>
							</div>
						</div>
					</div>
				</div>

				<div class="easyrere-card">
					<h2><?php esc_html_e( 'Unsubscribed Emails', 'easy-re-order-reminder-for-woocommerce' ); ?></h2>
					<?php if ( ! empty( $unsubscribed ) ) : ?>
						<p>
						<?php
						/* translators: %d: number of unsubscribed emails */
						printf( esc_html__( 'Total unsubscribed: %d', 'easy-re-order-reminder-for-woocommerce' ), count( $unsubscribed ) );
						?>
						</p>
						<textarea readonly class="easyrere-textarea-code"><?php echo esc_textarea( implode( "\n", $unsubscribed ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'These email addresses will not receive reminder emails.', 'easy-re-order-reminder-for-woocommerce' ); ?></p>
					<?php else : ?>
						<p><?php esc_html_e( 'No unsubscribed emails.', 'easy-re-order-reminder-for-woocommerce' ); ?></p>
					<?php endif; ?>
				</div>
		</div>

		<div class="easyrere-settings-sidebar">
			<div class="easyrere-card">
				<h3><?php esc_html_e( 'Statistics', 'easy-re-order-reminder-for-woocommerce' ); ?></h3>
				<ul class="easyrere-stats-list">
					<li>
						<strong><?php esc_html_e( 'Total Logs:', 'easy-re-order-reminder-for-woocommerce' ); ?></strong>
						<span><?php echo esc_html( $total_count ); ?></span>
					</li>
					<li>
						<strong class="easyrere-stats-sent"><?php esc_html_e( 'Sent:', 'easy-re-order-reminder-for-woocommerce' ); ?></strong>
						<span><?php echo esc_html( $sent_count ); ?></span>
					</li>
					<li>
						<strong class="easyrere-stats-pending"><?php esc_html_e( 'Pending:', 'easy-re-order-reminder-for-woocommerce' ); ?></strong>
						<span><?php echo esc_html( $pending_count ); ?></span>
					</li>
					<li>
						<strong class="easyrere-stats-failed"><?php esc_html_e( 'Failed:', 'easy-re-order-reminder-for-woocommerce' ); ?></strong>
						<span><?php echo esc_html( $failed_count ); ?></span>
					</li>
				</ul>
				<p style="margin-top: 15px;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=easyrere-logs' ) ); ?>" class="button">
						<?php esc_html_e( 'View All Logs', 'easy-re-order-reminder-for-woocommerce' ); ?>
					</a>
				</p>
			</div>

			<div class="easyrere-card">
				<h3><?php esc_html_e( 'Quick Links', 'easy-re-order-reminder-for-woocommerce' ); ?></h3>
				<ul class="easyrere-links-list">
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=easyrere_settings' ) ); ?>">
							<?php esc_html_e( 'WooCommerce Settings', 'easy-re-order-reminder-for-woocommerce' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=easyrere-logs' ) ); ?>">
							<?php esc_html_e( 'View Logs', 'easy-re-order-reminder-for-woocommerce' ); ?>
						</a>
					</li>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=easyrere_reorder_reminder' ) ); ?>">
							<?php esc_html_e( 'Email Template Settings', 'easy-re-order-reminder-for-woocommerce' ); ?>
						</a>
					</li>
				</ul>
			</div>
		</div>
	</div>
</div>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

