=== Easy Re-Order Reminder for WooCommerce ===
Contributors: codereyes,shahadul878
Tags: woocommerce, email, reminders, reorder, customer retention
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 5.0
WC tested up to: 8.0

Automatically remind customers to reorder products after a defined time period. Increase repeat sales with automated email reminders.

== Description ==

Easy Re-Order Reminder for WooCommerce helps you increase repeat sales by automatically sending reminder emails to customers who haven't reordered products within a specified time frame. Perfect for subscription products, consumables, and any items customers might need to repurchase.

= Key Features =

* **Automatic Email Reminders**: Set up automatic email reminders after a specified number of days (15, 30, 60, 90 days, etc.)
* **Product-Level Control**: Enable/disable reminders per product and set custom reminder days for individual products
* **Customer Choice**: Customers can select their preferred reminder day on the thank you page after completing an order
* **Beautiful Email Templates**: Professional HTML email templates with one-click reorder button
* **Unsubscribe Option**: Customers can easily unsubscribe from reminders with a single click
* **Comprehensive Logging**: Track sent, pending, and failed reminders with detailed logs
* **Test Email**: Send test emails to verify your email settings and templates
* **WooCommerce Integration**: Fully integrated with WooCommerce email system and settings
* **Global Settings Page**: Dedicated settings page for easy configuration and maintenance

= How It Works =

1. **Order Tracking**: The plugin automatically tracks completed orders
2. **Daily Cron Job**: A daily cron job checks for orders that need reminders based on the configured reminder days
3. **Email Sending**: Reminder emails are sent to customers with product details and a reorder link
4. **Logging**: All reminder activities are logged for tracking and analysis

= Product-Level Settings =

For each product, you can:
* Enable or disable reminders
* Set custom reminder days (overrides global setting)
* Control reminders at the product level for maximum flexibility

= Customer Experience =

* Customers can choose their preferred reminder day on the thank you page
* Beautiful, responsive email templates
* One-click reorder functionality
* Easy unsubscribe option in every email

= Admin Features =

* Global settings page with statistics
* Product-level reminder controls
* Email template customization
* Comprehensive logging system
* Test email functionality
* Quick links to WooCommerce email settings

== Installation ==

**Option A: Via WordPress Admin**
1. Go to WordPress Admin > Plugins > Add New
2. Search for "Easy Re-Order Reminder for WooCommerce"
3. Click "Install Now"
4. Click "Activate"

**Option B: Manual Installation**
1. Download the plugin ZIP file
2. Go to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin"
4. Choose the ZIP file
5. Click "Install Now"
6. Click "Activate Plugin"

**Option C: Via FTP**
1. Extract the plugin folder
2. Upload to `/wp-content/plugins/`
3. Go to WordPress Admin > Plugins
4. Find "Easy Re-Order Reminder for WooCommerce" and click "Activate"

**Verify Installation**
After activation, you should see:
* New menu item: "Re-Order Reminder" in WooCommerce settings
* Settings page accessible at WooCommerce > Settings > Re-Order Reminder
* No error messages in admin

== Frequently Asked Questions ==

= How do I configure reminder days? =

Go to WooCommerce > Settings > Re-Order Reminder and set the default reminder days. You can also set custom reminder days for individual products in the product edit page.

= Can customers choose their reminder day? =

Yes! After completing an order, customers can select their preferred reminder day on the thank you page. This preference is saved and used for that specific order.

= How do I customize the email template? =

The plugin uses WooCommerce's email system. Go to WooCommerce > Settings > Emails and find "Re-Order Reminder" to customize the email subject, heading, and content.

= Can I disable reminders for specific products? =

Yes! Edit any product and scroll to the "Re-Order Reminder" section. You can enable/disable reminders and set custom reminder days for each product.

= How do I view reminder logs? =

Go to WooCommerce > Re-Order Logs to view all reminder activities including sent, pending, and failed reminders.

= Can customers unsubscribe from reminders? =

Yes! Every reminder email includes an unsubscribe link. Unsubscribed emails are stored and will not receive future reminders.

= How do I test the email template? =

Go to WooCommerce > Settings > Re-Order Reminder and use the "Send Test Email" button to send a test email to verify your settings.

= Does this work with WooCommerce subscriptions? =

Yes! The plugin works with all WooCommerce order types including subscriptions.

= What happens if a customer places a new order before the reminder? =

The plugin tracks orders and will only send reminders for products that haven't been reordered within the specified time frame.

= Can I set different reminder days for different products? =

Yes! You can set custom reminder days for each product individually, or use the global default setting.

== Screenshots ==

1. Global Settings Page - Configure default reminder days, enable/disable reminders, and view statistics
2. Product Settings - Enable/disable reminders and set custom reminder days per product
3. Email Template - Beautiful HTML email with reorder button and unsubscribe link
4. Thank You Page - Customers can select their preferred reminder day after order completion
5. Logs Page - View all reminder activities with detailed information
6. WooCommerce Email Settings - Fully integrated with WooCommerce email system

== Changelog ==

= 1.0.2 =
* Maintenance: Version bump for WordPress.org release, keeping 1.0.1 security and HPOS compatibility fixes.

= 1.0.1 =
* Security: Proper nonce sanitization for all AJAX and form handlers
* Compatibility: Full HPOS (High-Performance Order Storage) support for WooCommerce
* Compatibility: Improved WooCommerce dependency check for network activation
* Added: uninstall.php for proper cleanup on plugin removal
* Added: load_plugin_textdomain for translation support
* Fixed: Order edit links in logs page for HPOS compatibility
* Updated: Plugin URI and text domain consistency

= 1.0.0 =
* Initial release
* Automatic email reminders based on configurable days
* Product-level reminder controls
* Customer reminder day selection on thank you page
* Beautiful HTML email templates
* Unsubscribe functionality
* Comprehensive logging system
* Global settings page with statistics
* Test email functionality
* Full WooCommerce integration

== Upgrade Notice ==

= 1.0.2 =
Maintenance release. No functional changes; updates the version for the latest tested WordPress/WooCommerce environment while preserving 1.0.1 security and compatibility fixes.

= 1.0.1 =
Security and compatibility update. Includes nonce sanitization fixes, HPOS support, and WordPress.org compatibility improvements.

= 1.0.0 =
Initial release of Easy Re-Order Reminder for WooCommerce. Activate the plugin and configure your settings to start sending reminder emails to customers.

== Support ==

For support, feature requests, and bug reports, please visit:
https://github.com/shahadul878/easy-reorder-reminder

== Credits ==

* WooCommerce for the excellent e-commerce platform
* WordPress community for inspiration and feedback

== License ==

This plugin is licensed under the GPL v2 or later.

