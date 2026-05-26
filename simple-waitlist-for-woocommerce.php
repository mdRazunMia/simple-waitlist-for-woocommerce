<?php
/**
 * Plugin Name: Simple Waitlist for WooCommerce
 * Plugin URI:  https://wordpress.org/plugins/simple-waitlist-for-woocommerce
 * Description: A simple WooCommerce waitlist plugin that lets customers sign up for back-in-stock notifications.
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://your-website.com
 * License:     GPL-2.0-or-later
 * Text Domain: simple-waitlist-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.0
 *
 * @package SimpleWaitlist\WooCommerce
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoload classes via Composer.
 */
$autoloader = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $autoloader ) ) {
	require_once $autoloader;
} else {
	// Self-contained PSR-4 autoloader for environments without Composer.
	spl_autoload_register(
		static function ( string $fully_qualified_class ): void {
			$prefix   = 'SimpleWaitlist\\WooCommerce\\';
			$base_dir = __DIR__ . '/src/';

			$len = strlen( $prefix );
			if ( strncmp( $prefix, $fully_qualified_class, $len ) !== 0 ) {
				return;
			}

			$relative_class = substr( $fully_qualified_class, $len );
			$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);
}

use SimpleWaitlist\WooCommerce\Database;

/**
 * Activation handler - creates the database table when the plugin is activated.
 *
 * @return void
 */
function simple_waitlist_for_woocommerce_activate(): void {
	if ( ! class_exists( 'SimpleWaitlist\\WooCommerce\\Database' ) ) {
		return;
	}
	$database = new Database();
	$database->create_table();
}
register_activation_hook( __FILE__, 'simple_waitlist_for_woocommerce_activate' );

/**
 * Deactivation handler - cleans up scheduled actions on deactivation.
 *
 * @return void
 */
function simple_waitlist_for_woocommerce_deactivate(): void {
	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( 'simple_waitlist_send_notification', [], 'simple-waitlist' );
	}
}
register_deactivation_hook( __FILE__, 'simple_waitlist_for_woocommerce_deactivate' );

/**
 * Load plugin text domain for translations.
 *
 * @return void
 */
function simple_waitlist_for_woocommerce_load_textdomain(): void {
	load_plugin_textdomain(
		'simple-waitlist-for-woocommerce',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'simple_waitlist_for_woocommerce_load_textdomain' );

/**
 * Bootstrap the plugin.
 *
 * @return SimpleWaitlist\WooCommerce\Plugin|null Plugin instance, or null if WooCommerce is not active.
 */
function simple_waitlist_for_woocommerce_init(): ?SimpleWaitlist\WooCommerce\Plugin {
	// Bail early if WooCommerce is not active.
	if ( ! class_exists( 'WooCommerce' ) ) {
		return null;
	}

	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new SimpleWaitlist\WooCommerce\Plugin( __FILE__ );
		$plugin->init();
	}

	return $plugin;
}
add_action( 'plugins_loaded', 'simple_waitlist_for_woocommerce_init' );
