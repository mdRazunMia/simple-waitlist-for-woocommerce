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
	const DB_VERSION        = '1.2.0';

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
			consent_given tinyint(1) DEFAULT 0 NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY (id),
			KEY idx_product (product_id),
			KEY idx_notification_sent (notification_sent),
			KEY idx_email (email)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Upgrade the table schema if the installed version is older.
	 *
	 * @return void
	 */
	public function maybe_upgrade(): void {
		$installed_version = get_option( self::DB_VERSION_OPTION, '1.0.0' );

		if ( version_compare( $installed_version, self::DB_VERSION, '>=' ) ) {
			return;
		}

		$this->create_table();
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
	 * @param bool     $consent      Whether consent was given.
	 *
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function insert_entry( string $email, string $name, ?int $product_id = null, ?int $variation_id = null, bool $consent = false ) {
		return $this->wpdb->insert(
			$this->table_name,
			[
				'email'         => $email,
				'name'          => $name,
				'product_id'    => $product_id,
				'variation_id'  => $variation_id,
				'consent_given' => $consent ? 1 : 0,
			],
			[ '%s', '%s', '%d', '%d', '%d' ]
		);
	}

	/**
	 * Check if a waitlist entry already exists for the given email and product.
	 *
	 * @param string   $email        Email address.
	 * @param int|null $product_id   Product ID.
	 * @param int|null $variation_id Variation ID.
	 *
	 * @return bool True if a duplicate entry exists.
	 */
	public function has_duplicate( string $email, ?int $product_id = null, ?int $variation_id = null ): bool {
		return $this->has_entry_for_email( $email, $product_id, $variation_id );
	}

	/**
	 * Check if an entry exists for a given email and product/variation.
	 *
	 * @param string   $email        Email address.
	 * @param int|null $product_id   Product ID.
	 * @param int|null $variation_id Variation ID.
	 *
	 * @return bool True if entry exists.
	 */
	public function has_entry_for_email( string $email, ?int $product_id = null, ?int $variation_id = null ): bool {
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
		if ( null === $variation_id ) {
			$sql = $this->wpdb->prepare(
				"SELECT * FROM {$this->table_name}
				WHERE product_id = %d
				AND variation_id IS NULL
				AND notification_sent = 0",
				$product_id
			);
		} else {
			$sql = $this->wpdb->prepare(
				"SELECT * FROM {$this->table_name}
				WHERE product_id = %d
				AND variation_id = %d
				AND notification_sent = 0",
				$product_id,
				$variation_id
			);
		}
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

	/**
	 * Get paginated waitlist entries for the admin list table.
	 *
	 * @param array $args Query arguments (per_page, paged, search, product_id, notification_sent).
	 *
	 * @return array List of entry objects.
	 */
	public function get_entries( array $args = [] ): array {
		$args = wp_parse_args(
			$args,
			[
				'per_page'          => 20,
				'paged'             => 1,
				'search'            => '',
				'product_id'        => 0,
				'notification_sent' => null,
				'orderby'           => 'created_at',
				'order'             => 'DESC',
			]
		);

		$where   = [];
		$prepare = [];

		if ( ! empty( $args['search'] ) ) {
			$where[] = '(email LIKE %s OR name LIKE %s)';
			$search  = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$prepare = array_merge( $prepare, [ $search, $search ] );
		}

		if ( ! empty( $args['product_id'] ) ) {
			$where[]   = 'product_id = %d';
			$prepare[] = (int) $args['product_id'];
		}

		if ( null !== $args['notification_sent'] ) {
			$where[]   = 'notification_sent = %d';
			$prepare[] = (int) $args['notification_sent'];
		}

		$where_sql = empty( $where ) ? '' : 'WHERE ' . implode( ' AND ', $where );

		$allowed_orderby = [ 'id', 'email', 'name', 'created_at', 'notification_sent' ];
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$limit  = (int) $args['per_page'];
		$offset = ( (int) $args['paged'] - 1 ) * $limit;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name cannot be parameterized.
		if ( empty( $prepare ) ) {
			$sql = "SELECT * FROM {$this->table_name} ORDER BY {$orderby} {$order} LIMIT {$limit} OFFSET {$offset}";
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- Placeholders live in $where_sql; array_merge supplies correct count.
			$sql = $this->wpdb->prepare(
				"SELECT * FROM {$this->table_name} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				array_merge( $prepare, [ $limit, $offset ] )
			);
		}
		// phpcs:enable

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return $this->wpdb->get_results( $sql );
	}

	/**
	 * Count waitlist entries for pagination.
	 *
	 * @param array $args Filter arguments (search, product_id, notification_sent).
	 *
	 * @return int Total count.
	 */
	public function count_entries( array $args = [] ): int {
		$args = wp_parse_args(
			$args,
			[
				'search'            => '',
				'product_id'        => 0,
				'notification_sent' => null,
			]
		);

		$where   = [];
		$prepare = [];

		if ( ! empty( $args['search'] ) ) {
			$where[] = '(email LIKE %s OR name LIKE %s)';
			$search  = '%' . $this->wpdb->esc_like( $args['search'] ) . '%';
			$prepare = array_merge( $prepare, [ $search, $search ] );
		}

		if ( ! empty( $args['product_id'] ) ) {
			$where[]   = 'product_id = %d';
			$prepare[] = (int) $args['product_id'];
		}

		if ( null !== $args['notification_sent'] ) {
			$where[]   = 'notification_sent = %d';
			$prepare[] = (int) $args['notification_sent'];
		}

		$where_sql = empty( $where ) ? '' : 'WHERE ' . implode( ' AND ', $where );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name cannot be parameterized.
		if ( empty( $prepare ) ) {
			$sql = "SELECT COUNT(*) FROM {$this->table_name}";
		} else {
			$sql = $this->wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Placeholders live in $where_sql; $prepare supplies values.
				"SELECT COUNT(*) FROM {$this->table_name} {$where_sql}",
				$prepare
			);
		}
		// phpcs:enable

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * Delete a waitlist entry by ID.
	 *
	 * @param int $entry_id Entry ID.
	 *
	 * @return int|false Rows deleted, or false on error.
	 */
	public function delete_entry( int $entry_id ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $this->wpdb->delete( $this->table_name, [ 'id' => $entry_id ], [ '%d' ] );
	}
}
