=== Simple Waitlist for WooCommerce ===
Contributors: simple-waitlist
Tags: woocommerce, waitlist, back-in-stock, notifications, stock
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight WooCommerce plugin that lets customers sign up for back-in-stock notifications.

== Description ==

Simple Waitlist for WooCommerce allows your customers to join a waitlist for out-of-stock products. When the product is back in stock, they receive an automated email notification. The plugin is built with performance and extensibility in mind:

* **Custom database table** — dedicated `wp_simple_waitlist` table for scalable storage.
* **Async notifications** — uses WooCommerce's Action Scheduler to send emails asynchronously (with a direct fallback if unavailable).
* **REST API** — AJAX-based form submission via a REST endpoint.
* **Full i18n support** — all strings are translatable via standard `.po`/`.mo` files.
* **Minimal footprint** — no external JavaScript dependencies beyond jQuery (bundled with WordPress).

= Features =

* Customers can sign up for back-in-stock notifications via a shortcode `[simple_waitlist_form]`
* AJAX-based form submission with inline success/error messages
* Traditional (non-JS) form submission fallback
* Customizable email subject and body via WooCommerce admin settings
* Placeholder support in email templates: `{name}`, `{product_name}`, `{product_link}`
* Automatic email dispatch when stock is restored
* Async email sending via Action Scheduler (prevents page-load delays)
* Duplicate entry detection — prevents customers from signing up twice
* Supports both simple products and variable product variations
* Full REST API for headless/custom integrations
* Complete uninstall cleanup — removes all plugin data
* Translation-ready with text domain `simple-waitlist-for-woocommerce`
* Security-first with nonce verification, input sanitization, and output escaping

== Installation ==

= From WordPress Admin =

1. Navigate to **Plugins → Add New** and search for "Simple Waitlist for WooCommerce".
2. Click **Install Now** and then **Activate**.

= Manual Installation =

1. Download the plugin zip file from the WordPress plugin repository.
2. Upload the `simple-waitlist-for-woocommerce` folder to `/wp-content/plugins/`.
3. Activate the plugin from the **Plugins** screen.

= Activation =

On activation, the plugin automatically creates the `wp_simple_waitlist` database table. No manual setup is required.

== Frequently Asked Questions ==

= Does this plugin work with variable products? =

Yes. The shortcode accepts a `variation_id` attribute for variable products, and the stock change listener handles both simple products and variations.

= Can I customize the email that is sent? =

Yes. You can customize both the email subject and body from **WooCommerce → Simple Waitlist for WooCommerce** in the admin. Placeholders are available for `{name}`, `{product_name}`, and `{product_link}`.

= What happens if a customer signs up twice? =

The plugin checks for duplicate entries (same email + same product + same variation) before inserting a new record. Duplicate signups are prevented with a clear notice to the customer.

= Does the plugin clean up after itself? =

Yes. When the plugin is uninstalled via **Plugins → Delete**, the custom database table is dropped, all plugin options are deleted, and any scheduled Action Scheduler actions are removed.

= Is this compatible with caching plugins? =

Yes. The form submission uses WordPress REST API (not admin-ajax.php), which is broadly compatible with most caching setups. The form itself can be cached, and submissions are handled dynamically.

== Screenshots ==

1. Waitlist signup form displayed on a product page.
2. Admin settings page for email customization under WooCommerce menu.

== Changelog ==

= 1.0.0 =
* Initial release.
* Shortcode `[simple_waitlist_form]` for displaying the waitlist signup form.
* Admin settings page under WooCommerce for customizing email templates.
* Back-in-stock notification emails via Action Scheduler.
* REST API endpoint for AJAX form submission.
* Full uninstall cleanup (drops table, removes options, unschedules actions).

== Upgrade Notice ==

= 1.0.0 =
Initial release.
