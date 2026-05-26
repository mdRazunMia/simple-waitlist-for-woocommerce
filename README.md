# Simple Waitlist for WooCommerce

A lightweight WooCommerce plugin that lets customers sign up for back-in-stock notifications. When an out-of-stock product is restocked, waitlisted customers receive an automated email notification.

**Contributors:** simple-waitlist  
**Tags:** woocommerce, waitlist, back-in-stock, notifications, stock  
**Requires at least:** 5.8  
**Tested up to:** 6.7  
**Requires PHP:** 7.4  
**License:** GPL-2.0-or-later

## Description

Simple Waitlist for WooCommerce allows your customers to join a waitlist for out-of-stock products. When the product is back in stock, they receive an automated email notification. The plugin is built with performance and extensibility in mind:

- **Custom database table** — dedicated `wp_simple_waitlist` table for scalable storage.
- **Async notifications** — uses WooCommerce's Action Scheduler to send emails asynchronously (with a direct fallback if unavailable).
- **REST API** — AJAX-based form submission via a REST endpoint.
- **Full i18n support** — all strings are translatable via standard `.po`/`.mo` files.
- **Minimal footprint** — no external JavaScript dependencies beyond jQuery (bundled with WordPress).

## Features

- ✅ Customers can sign up for back-in-stock notifications via a shortcode `[simple_waitlist_form]`
- ✅ AJAX-based form submission with inline success/error messages
- ✅ Traditional (non-JS) form submission fallback
- ✅ Customizable email subject and body via WooCommerce admin settings
- ✅ Placeholder support in email templates: `{name}`, `{product_name}`, `{product_link}`
- ✅ Automatic email dispatch when stock is restored (via `woocommerce_product_set_stock` and `woocommerce_variation_set_stock` hooks)
- ✅ Async email sending via Action Scheduler (prevents page-load delays)
- ✅ Duplicate entry detection — prevents customers from signing up twice
- ✅ Supports both simple products and variable product variations
- ✅ Full REST API for headless/custom integrations
- ✅ Complete uninstall cleanup — removes all plugin data
- ✅ Translation-ready with text domain `simple-waitlist-for-woocommerce`
- ✅ Security-first with nonce verification, input sanitization, and output escaping

## Requirements

- WordPress 5.8 or later
- PHP 7.4 or later
- WooCommerce 6.0 or later

## Installation

### From WordPress Admin

1. Navigate to **Plugins → Add New** and search for "Simple Waitlist for WooCommerce".
2. Click **Install Now** and then **Activate**.

### Manual Installation

1. Download the plugin zip file from the [WordPress plugin repository](https://wordpress.org/plugins/simple-waitlist-for-woocommerce/).
2. Upload the `simple-waitlist-for-woocommerce` folder to `/wp-content/plugins/`.
3. Activate the plugin from the **Plugins** screen.

### Via Composer

If your project uses Composer, you can require the plugin:

```bash
composer require simple-waitlist/simple-waitlist-for-woocommerce
```

### Activation

On activation, the plugin automatically creates the `wp_simple_waitlist` database table. No manual setup is required.

## Usage

### 1. Display the Waitlist Form

Use the `[simple_waitlist_form]` shortcode on any product page or post where you want the waitlist signup form to appear.

**Simple product:**
```
[simple_waitlist_form product_id="123"]
```

**Variable product variation:**
```
[simple_waitlist_form product_id="123" variation_id="456"]
```

**Without attributes (manual entry):** If the shortcode is placed on a product page without explicit IDs, users can still sign up by entering their email and name.

### 2. Configure Email Settings

1. Go to **WooCommerce → Simple Waitlist for WooCommerce** in the WordPress admin.
2. Customize the **Email Subject** and **Email Body**.
3. Use the available placeholders:
   - `{name}` — Subscriber's name
   - `{product_name}` — The product name
   - `{product_link}` — The product URL
4. Click **Save Changes**.

### 3. How Notifications Work

1. A customer joins the waitlist for an out-of-stock product.
2. When the product stock is updated to "in stock", the plugin detects the change via WooCommerce's `woocommerce_product_set_stock` action.
3. All unsent waitlist entries for that product are marked as notified, and an email is scheduled via Action Scheduler.
4. The scheduled action sends the back-in-stock email asynchronously.

## REST API

The plugin exposes a REST API endpoint for custom integrations:

**Endpoint:** `POST /wp-json/simple-waitlist/v1/waitlist`

**Parameters:**

| Parameter              | Type   | Required | Description                        |
|------------------------|--------|----------|------------------------------------|
| `email`                | string | Yes      | Customer email address             |
| `name`                 | string | Yes      | Customer name                      |
| `product_id`           | int    | No       | Product ID                         |
| `variation_id`         | int    | No       | Variation ID                       |
| `simple_waitlist_nonce`| string | Yes      | Security nonce                     |

**Success response (200):**
```json
{
  "message": "Thank you for joining the waitlist!"
}
```

**Error responses:**
- `403` — Nonce verification failed
- `400` — Invalid email or name
- `409` — Duplicate entry (already on waitlist)
- `500` — Database error

## Developer Hooks

### Actions

| Hook                                      | Description                                             |
|-------------------------------------------|---------------------------------------------------------|
| `simple_waitlist_send_notification`       | Fired by Action Scheduler to send a notification email. |

### Filters

_(The plugin currently does not expose filter hooks, but the codebase is designed to be easily extensible. All components accept dependencies via constructor injection.)_

### Integration Points

- **Stock change detection:** The plugin hooks into `woocommerce_product_set_stock` and `woocommerce_variation_set_stock` to detect when a product is restocked.
- **Action Scheduler:** Uses `as_enqueue_async_action()` for async email dispatch. Falls back to synchronous sending if Action Scheduler is not available.
- **WooCommerce notices:** Form handler success/error messages are displayed via `wc_add_notice()`.

## Database

The plugin uses a custom database table to store waitlist entries. The table is created on plugin activation and dropped on uninstall.

**Table:** `wp_simple_waitlist`

| Column             | Type                  | Description                                 |
|--------------------|-----------------------|---------------------------------------------|
| `id`               | mediumint(9)          | Auto-increment primary key                  |
| `email`            | varchar(255)          | Customer email address                      |
| `name`             | varchar(255)          | Customer name                               |
| `notification_sent`| tinyint(1) DEFAULT 0  | Whether the notification email was sent     |
| `product_id`       | bigint(20) unsigned   | WooCommerce product ID (nullable)           |
| `variation_id`     | bigint(20) unsigned   | Variation ID for variable products (nullable)|
| `created_at`       | datetime              | Timestamp of waitlist signup                |

**Indexes:** `id` (primary), `product_id`, `notification_sent`

### Lifecycle

- **Activation:** The `wp_simple_waitlist` table is created if it does not exist, using `dbDelta()` for safe schema updates.
- **Deactivation:** All pending Action Scheduler notifications are unscheduled. **No data is deleted** — waitlist entries are preserved.
- **Uninstall:** The table is dropped, all plugin options are deleted, and any remaining scheduled actions are removed.

### Options

| Option                          | Description                          |
|---------------------------------|--------------------------------------|
| `simple_waitlist_email_subject` | Custom email subject template        |
| `simple_waitlist_email_body`    | Custom email body template           |
| `simple_waitlist_db_version`    | Database schema version for migrations|

## File Structure

```
simple-waitlist-for-woocommerce/
├── simple-waitlist-for-woocommerce.php   # Plugin bootstrap & activation hooks
├── uninstall.php                         # Cleanup on plugin deletion
├── composer.json                         # Composer configuration
├── src/
│   ├── Plugin.php                        # Main plugin bootstrap class
│   ├── DatabaseInterface.php             # Database contract interface
│   ├── Database.php                      # Database table & query handling
│   ├── Admin.php                         # Admin settings page
│   ├── EmailService.php                  # Email composition & sending
│   ├── FormHandler.php                   # Traditional form POST handler
│   ├── Notifier.php                      # Stock change listener & dispatcher
│   ├── Shortcode.php                     # Waitlist form shortcode
│   └── RestController.php                # REST API endpoint
├── public/
│   ├── css/
│   │   └── public.css                    # Frontend styles
│   └── js/
│       └── public.js                     # Frontend AJAX handler
└── vendor/                               # Composer dependencies
```

## Changelog

### 1.0.0
- Initial release.
- Shortcode `[simple_waitlist_form]` for displaying the waitlist signup form.
- Admin settings page under WooCommerce for customizing email templates.
- Back-in-stock notification emails via Action Scheduler.
- REST API endpoint for AJAX form submission.
- Full uninstall cleanup (drops table, removes options, unschedules actions).

## Frequently Asked Questions

### Does this plugin work with variable products?
Yes. The shortcode accepts a `variation_id` attribute for variable products, and the stock change listener handles both simple products and variations.

### Can I customize the email that is sent?
Yes. You can customize both the email subject and body from **WooCommerce → Simple Waitlist for WooCommerce** in the admin. Placeholders are available for `{name}`, `{product_name}`, and `{product_link}`.

### What happens if a customer signs up twice?
The plugin checks for duplicate entries (same email + same product + same variation) before inserting a new record. Duplicate signups are prevented with a clear notice to the customer.

### Does the plugin clean up after itself?
Yes. When the plugin is uninstalled via **Plugins → Delete**, the custom database table is dropped, all plugin options are deleted, and any scheduled Action Scheduler actions are removed.

### Is this compatible with caching plugins?
Yes. The form submission uses WordPress REST API (not admin-ajax.php), which is broadly compatible with most caching setups. The form itself can be cached, and submissions are handled dynamically.

## Screenshots

1. Waitlist signup form displayed on a product page.
2. Admin settings page for email customization under WooCommerce menu.

## Support

If you encounter any issues, please [open a support thread](https://wordpress.org/support/plugin/simple-waitlist-for-woocommerce/) on the WordPress plugin forum.

## Contributing

Contributions are welcome! If the plugin is mirrored on GitHub, please submit pull requests there. Otherwise, feel free to open a support thread on the [WordPress plugin forum](https://wordpress.org/support/plugin/simple-waitlist-for-woocommerce/) with your suggestions.

### Development Setup

1. Clone the repository.
2. Run `composer install` to install dependencies.
3. Make your changes and submit a pull request (if a GitHub mirror exists) or share via the support forum.

## License

This plugin is licensed under the GPL-2.0-or-later license. See [LICENSE](LICENSE) for more information.
