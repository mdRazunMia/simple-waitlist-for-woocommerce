<?php
/**
 * Database interface for the Simple Waitlist for WooCommerce plugin.
 *
 * Defines the contract for database operations so that consumers
 * depend on an abstraction rather than a concrete implementation.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

interface DatabaseInterface {

	/**
	 * Create or update the waitlist table schema.
	 *
	 * @return void
	 */
	public function create_table(): void;

	/**
	 * Drop the waitlist table.
	 *
	 * @return void
	 */
	public function drop_table(): void;

	/**
	 * Get the full table name with WordPress prefix.
	 *
	 * @return string
	 */
	public function get_table_name(): string;

	/**
	 * Insert a new waitlist entry.
	 *
	 * @param string   $email        Email address.
	 * @param string   $name         Subscriber name.
	 * @param int|null $product_id   Product ID.
	 * @param int|null $variation_id Variation ID.
	 *
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function insert_entry( string $email, string $name, ?int $product_id = null, ?int $variation_id = null );

	/**
	 * Check if a waitlist entry already exists for the given email and product.
	 *
	 * @param string   $email        Email address.
	 * @param int|null $product_id   Product ID.
	 * @param int|null $variation_id Variation ID.
	 *
	 * @return bool True if a duplicate entry exists.
	 */
	public function has_duplicate( string $email, ?int $product_id = null, ?int $variation_id = null ): bool;

	/**
	 * Get unsent waitlist entries for a product.
	 *
	 * @param int      $product_id   Product ID.
	 * @param int|null $variation_id Variation ID (null for simple products).
	 *
	 * @return array List of waitlist entry objects.
	 */
	public function get_unsent_entries( int $product_id, ?int $variation_id = null ): array;

	/**
	 * Mark a waitlist entry as notified.
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function mark_as_notified( int $entry_id );
}
