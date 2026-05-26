<?php
/**
 * Uninstall script for Simple Waitlist for WooCommerce.
 *
 * Cleans up all plugin data: database tables, options, and scheduled actions.
 * Uses the Database class to avoid duplicating table name and schema logic.
 *
 * @package SimpleWaitlist\WooCommerce
 *
 * @noinspection PhpIncludeInspection
 */

// Exit if not called by WordPress uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load the plugin autoloader so we can use the Database class.
$autoloader = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $autoloader ) ) {
	require_once $autoloader;
} else {
	// Self-contained PSR-4 autoloader fallback.
	spl_autoload_register(
		static function ( string $class ): void {
			$prefix   = 'SimpleWaitlist\\WooCommerce\\';
			$base_dir = __DIR__ . '/src/';
			$len      = strlen( $prefix );
			if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				return;
			}
			$relative_class = substr( $class, $len );
			$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	);
}

use SimpleWaitlist\WooCommerce\Database;

// Drop the waitlist table via the Database class.
$database = new Database();
$database->drop_table();

// Delete plugin options.
delete_option( 'simple_waitlist_email_subject' );
delete_option( 'simple_waitlist_email_body' );
delete_option( Database::DB_VERSION_OPTION );

// Delete all scheduled Action Scheduler actions for this plugin.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'simple_waitlist_send_notification', [], 'simple-waitlist' );
}
