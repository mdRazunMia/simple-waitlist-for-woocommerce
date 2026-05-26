<?php
/**
 * Database layer for the Simple Waitlist for WooCommerce plugin.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

use wpdb;

/**
 * Handles database table creation, queries, and schema management.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class Database implements DatabaseInterface {

	const TABLE_NAME        = 'simple_waitlist';
	const DB_VERSION_OPTION = 'simple_waitlist_db_version';
	const DB_VERSION        = '1.1.0';

	/**
	 * WordPress database instance.
	 *
	 * @var wpdb
	 */
	private wpdb $wpdb;

	/**
	 * Full table name with prefix.
	 *
	 * @var string
	 */
	private string $table_name;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb       = $wpdb;
		$this->table_name = $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Create or update the waitlist table schema.
	 *
	 * @return void
	 */
	public function create_table(): void {
		$charset_collate = $this->wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			email varchar(255) NOT NULL,
			name varchar(255) NOT NULL,
			notification_sent tinyint(1) DEFAULT 0 NOT NULL,
			product_id bigint(20) unsigned DEFAULT NULL,
			variation_id bigint(20) unsigned DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY (id),
			KEY idx_product (product_id),
			KEY idx_notification_sent (notification_sent)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Drop the waitlist table.
	 *
	 * @return void
	 */
	public function drop_table(): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->wpdb->query( "DROP TABLE IF EXISTS {$this->table_name}" );
	}

	/**
	 * Get the full table name.
	 *
	 * @return string
	 */
	public function get_table_name(): string {
		return $this->table_name;
	}

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
	public function insert_entry( string $email, string $name, ?int $product_id = null, ?int $variation_id = null ) {
		return $this->wpdb->insert(
			$this->table_name,
			[
				'email'        => $email,
				'name'         => $name,
				'product_id'   => $product_id,
				'variation_id' => $variation_id,
			],
			[ '%s', '%s', '%d', '%d' ]
		);
	}

	/**
	 * Check if a waitlist entry already exists for the given email and product.
	 *
	 * Prevents duplicate signups. Dynamically builds conditions to handle
	 * nullable product_id and variation_id correctly (null is not equal to 0
	 * in prepared statements).
	 *
	 * @param string   $email        Email address.
	 * @param int|null $product_id   Product ID.
	 * @param int|null $variation_id Variation ID.
	 *
	 * @return bool True if a duplicate entry exists.
	 */
	public function has_duplicate( string $email, ?int $product_id = null, ?int $variation_id = null ): bool {
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql     = $this->wpdb->prepare( "SELECT COUNT(*) FROM {$this->table_name} WHERE email = %s", $email );
		$clauses = [];

		if ( null === $product_id ) {
			$clauses[] = 'product_id IS NULL';
		} else {
			$clauses[] = $this->wpdb->prepare( 'product_id = %d', $product_id );
		}

		if ( null === $variation_id ) {
			$clauses[] = 'variation_id IS NULL';
		} else {
			$clauses[] = $this->wpdb->prepare( 'variation_id = %d', $variation_id );
		}

		$sql .= ' AND ' . implode( ' AND ', $clauses );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $this->wpdb->get_var( $sql ) > 0;
	}

	/**
	 * Get unsent waitlist entries for a product.
	 *
	 * @param int      $product_id   Product ID.
	 * @param int|null $variation_id Variation ID (null for simple products).
	 *
	 * @return array List of waitlist entry objects.
	 */
	public function get_unsent_entries( int $product_id, ?int $variation_id = null ): array {
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name cannot be parameterized.
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->table_name}
			WHERE product_id = %d
			AND (variation_id IS NULL OR variation_id = %d)
			AND notification_sent = 0",
			$product_id,
			$variation_id
		);
		// phpcs:enable

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return $this->wpdb->get_results( $sql );
	}

	/**
	 * Mark a waitlist entry as notified.
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function mark_as_notified( int $entry_id ) {
		return $this->wpdb->update(
			$this->table_name,
			[ 'notification_sent' => 1 ],
			[ 'id' => $entry_id ],
			[ '%d' ],
			[ '%d' ]
		);
	}
}
