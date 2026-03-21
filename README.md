# Easy Re-Order Reminder for WooCommerce

Automatically remind customers to re-order previously purchased products after a defined time period.

## Description

Easy Re-Order Reminder for WooCommerce is a WooCommerce extension that helps you increase repeat sales by automatically sending reminder emails to customers who haven't reordered products within a specified time frame.

### Features

- **Automatic Reminders**: Set up automatic email reminders after a specified number of days (e.g., 15, 30, 60 days)
- **Product-Level Control**: Enable/disable reminders per product and set custom reminder days
- **Email Templates**: Beautiful HTML email templates with reorder button
- **Unsubscribe Option**: Customers can easily unsubscribe from reminders
- **Comprehensive Logging**: Track sent, pending, and failed reminders
- **Test Email**: Send test emails to verify your settings
- **WooCommerce Integration**: Fully integrated with WooCommerce email system

## Installation

1. Upload the plugin files to the `/wp-content/plugins/easy-reorder-reminder` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to WooCommerce → Settings → Re-Order Reminder to configure the plugin.

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Configuration

### Global Settings

1. Navigate to **WooCommerce → Settings → Re-Order Reminder**
2. Enable/disable reminders
3. Set the default reminder days (e.g., 30 days)
4. Customize email subject and content
5. Send a test email to verify settings

### Product-Level Settings

1. Edit any product in WooCommerce
2. Scroll to the "Re-Order Reminder" section
3. Enable/disable reminder for that specific product
4. Set custom reminder days (optional, uses global setting if not set)

## Usage

Once configured, the plugin will:

1. Track completed orders automatically
2. Run a daily cron job to check for orders that need reminders
3. Send reminder emails to customers
4. Log all reminder activities

## Viewing Logs

Navigate to **WooCommerce → Re-Order Logs** to view:
- Sent reminders
- Pending reminders
- Failed reminders
- Order and product details

## Unsubscribe

Customers can unsubscribe from reminders by clicking the unsubscribe link in any reminder email. Unsubscribed emails are stored and will not receive future reminders.

## Support

For support, feature requests, or bug reports, please visit the [GitHub repository](https://github.com/shahadul878/easy-reorder-reminder).

## Changelog

### 1.0.2
- Maintenance release for WordPress.org distribution.
- Keeps all security and HPOS compatibility fixes from `1.0.1`.

### 1.0.1
- Security: Added proper nonce sanitization for AJAX and form handlers.
- Compatibility: Added full HPOS (High-Performance Order Storage) support.
- Compatibility: Improved WooCommerce dependency checks for network activation.
- Added: `uninstall.php` for proper data cleanup on plugin removal.
- Added: `load_plugin_textdomain()` for translation support.
- Fixed: Updated order edit links in logs page for HPOS compatibility.
- Updated: Plugin URI and text domain consistency.

### 1.0.0
- Initial release.
- Automatic email reminders based on configurable days.
- Product-level reminder controls.
- Customer reminder day selection on the thank-you page.
- HTML email templates with reorder CTA.
- Unsubscribe functionality.
- Reminder logging system.
- Global settings page and test email functionality.

## License

GPL v2 or later

## Author

**H M Shahadul Islam**

- GitHub: [@shahadul878](https://github.com/shahadul878)
- Email: shahadul.islam1@gmail.com

## Credits

Developed for Codereyes

