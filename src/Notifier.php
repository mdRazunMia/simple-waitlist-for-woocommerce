<?php
/**
 * Stock change notifier for the Simple Waitlist for WooCommerce plugin.
 *
 * Listens for WooCommerce stock changes and triggers email notifications
 * to waitlist subscribers via Action Scheduler.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

use WC_Product;

/**
 * Monitors product stock changes and dispatches waitlist notifications.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class Notifier {

	const ASYNC_ACTION = 'simple_waitlist_send_notification';

	/**
	 * Database instance.
	 *
	 * @var DatabaseInterface
	 */
	private DatabaseInterface $database;

	/**
	 * Email service instance.
	 *
	 * @var EmailService
	 */
	private EmailService $email_service;

	/**
	 * Constructor.
	 *
	 * @param DatabaseInterface $database      Database instance.
	 * @param EmailService      $email_service Email service instance.
	 */
	public function __construct( DatabaseInterface $database, EmailService $email_service ) {
		$this->database      = $database;
		$this->email_service = $email_service;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_product_set_stock', [ $this, 'on_stock_change' ] );
		add_action( 'woocommerce_variation_set_stock', [ $this, 'on_stock_change' ] );
		add_action( self::ASYNC_ACTION, [ $this, 'send_notification' ] );
	}

	/**
	 * Fired when a product or variation stock changes.
	 *
	 * @param WC_Product $product The product whose stock changed.
	 *
	 * @return void
	 */
	public function on_stock_change( $product ): void {
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		if ( ! $product->is_in_stock() ) {
			return;
		}

		if ( $product->get_type() === 'variation' ) {
			$variation_id = $product->get_id();
			$product_id   = $product->get_parent_id();
		} else {
			$variation_id = null;
			$product_id   = $product->get_id();
		}

		$entries = $this->database->get_unsent_entries( $product_id, $variation_id );

		if ( empty( $entries ) ) {
			return;
		}

		foreach ( $entries as $entry ) {
			$this->schedule_notification( $entry );
			$this->database->mark_as_notified( (int) $entry->id );
		}
	}

	/**
	 * Schedule an async notification via Action Scheduler.
	 *
	 * Falls back to sending directly if Action Scheduler is not available.
	 *
	 * @param object $entry Waitlist entry object.
	 *
	 * @return void
	 */
	private function schedule_notification( $entry ): void {
		$args = [
			'email'        => $entry->email,
			'name'         => $entry->name,
			'product_id'   => (int) $entry->product_id,
			'variation_id' => (int) $entry->variation_id,
		];

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::ASYNC_ACTION, $args, 'simple-waitlist' );
		} else {
			// Fallback: send directly if Action Scheduler is unavailable.
			$this->send_notification( $args );
		}
	}

	/**
	 * Send the back-in-stock email notification.
	 *
	 * Hooked to the Action Scheduler async action.
	 *
	 * @param array $args Notification arguments (email, name, product_id, variation_id).
	 *
	 * @return void
	 */
	public function send_notification( array $args ): void {
		$email        = $args['email'] ?? '';
		$name         = $args['name'] ?? '';
		$product_id   = $args['product_id'] ?? 0;
		$variation_id = $args['variation_id'] ?? 0;

		if ( empty( $email ) || empty( $name ) || empty( $product_id ) ) {
			return;
		}

		$this->email_service->send_notification( $email, $name, $product_id, (int) $variation_id );
	}
}
