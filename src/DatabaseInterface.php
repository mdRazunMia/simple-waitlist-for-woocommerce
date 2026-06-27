<?php
/**
 * Database contract for the Simple Waitlist for WooCommerce plugin.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

/**
 * Interface for waitlist database operations.
 *
 * @package SimpleWaitlist\WooCommerce
 */
interface DatabaseInterface {

	/**
	 * Create or update the waitlist table schema.
	 *
	 * @return void
	 */
	public function create_table(): void;

	/**
	 * Upgrade the table schema if needed.
	 *
	 * @return void
	 */
	public function maybe_upgrade(): void;

	/**
	 * Drop the waitlist table.
	 *
	 * @return void
	 */
	public function drop_table(): void;

	/**
	 * Get the full table name.
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
	 * @param bool     $consent      Whether consent was given.
	 *
	 * @return int|false Rows inserted, or false on error.
	 */
	public function insert_entry( string $email, string $name, ?int $product_id = null, ?int $variation_id = null, bool $consent = false );

	/**
	 * Check if a duplicate entry exists.
	 *
	 * @param string   $email        Email address.
	 * @param int|null $product_id   Product ID.
	 * @param int|null $variation_id Variation ID.
	 *
	 * @return bool
	 */
	public function has_duplicate( string $email, ?int $product_id = null, ?int $variation_id = null ): bool;

	/**
	 * Check if an entry exists for a given email and product/variation.
	 *
	 * @param string   $email        Email address.
	 * @param int|null $product_id   Product ID.
	 * @param int|null $variation_id Variation ID.
	 *
	 * @return bool
	 */
	public function has_entry_for_email( string $email, ?int $product_id = null, ?int $variation_id = null ): bool;

	/**
	 * Get unsent entries for a product/variation.
	 *
	 * @param int      $product_id   Product ID.
	 * @param int|null $variation_id Variation ID.
	 *
	 * @return array
	 */
	public function get_unsent_entries( int $product_id, ?int $variation_id = null ): array;

	/**
	 * Mark an entry as notified.
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return int|false
	 */
	public function mark_as_notified( int $entry_id );

	/**
	 * Get paginated entries for admin list table.
	 *
	 * @param array $args Query arguments.
	 *
	 * @return array
	 */
	public function get_entries( array $args = [] ): array;

	/**
	 * Count entries for pagination.
	 *
	 * @param array $args Filter arguments.
	 *
	 * @return int
	 */
	public function count_entries( array $args = [] ): int;

	/**
	 * Delete an entry by ID.
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return int|false
	 */
	public function delete_entry( int $entry_id );
}
