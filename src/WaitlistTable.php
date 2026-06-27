<?php
/**
 * Admin list table for waitlist entries.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

use WP_List_Table;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Renders the waitlist entries list in the admin.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class WaitlistTable extends WP_List_Table {

	/**
	 * Database instance.
	 *
	 * @var DatabaseInterface
	 */
	private DatabaseInterface $database;

	/**
	 * Constructor.
	 *
	 * @param DatabaseInterface $database Database instance.
	 */
	public function __construct( DatabaseInterface $database ) {
		parent::__construct(
			[
				'singular' => __( 'Entry', 'simple-waitlist-for-woocommerce' ),
				'plural'   => __( 'Entries', 'simple-waitlist-for-woocommerce' ),
				'ajax'     => false,
			]
		);

		$this->database = $database;
	}

	/**
	 * Define column headers.
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'cb'                => '<input type="checkbox" />',
			'name'              => __( 'Name', 'simple-waitlist-for-woocommerce' ),
			'email'             => __( 'Email', 'simple-waitlist-for-woocommerce' ),
			'product'           => __( 'Product', 'simple-waitlist-for-woocommerce' ),
			'variation'         => __( 'Variation', 'simple-waitlist-for-woocommerce' ),
			'notification_sent' => __( 'Notified', 'simple-waitlist-for-woocommerce' ),
			'consent_given'     => __( 'Consent', 'simple-waitlist-for-woocommerce' ),
			'created_at'        => __( 'Signed up', 'simple-waitlist-for-woocommerce' ),
		];
	}

	/**
	 * Define sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return [
			'name'       => [ 'name', true ],
			'email'      => [ 'email', true ],
			'created_at' => [ 'created_at', false ],
		];
	}

	/**
	 * Define bulk actions.
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return [
			'mark_notified' => __( 'Mark as notified', 'simple-waitlist-for-woocommerce' ),
			'delete'        => __( 'Delete', 'simple-waitlist-for-woocommerce' ),
		];
	}

	/**
	 * Default column renderer.
	 *
	 * @param object $item        Entry object.
	 * @param string $column_name Column name.
	 *
	 * @return string
	 */
	protected function column_default( $item, $column_name ): string {
		switch ( $column_name ) {
			case 'name':
			case 'email':
			case 'created_at':
				return esc_html( $item->$column_name );

			case 'notification_sent':
			case 'consent_given':
				return ! empty( $item->$column_name )
					? '<span class="dashicons dashicons-yes-alt" style="color:#2271b1;"></span>'
					: '<span class="dashicons dashicons-marker" style="color:#787c82;"></span>';

			default:
				return '';
		}
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param object $item Entry object.
	 *
	 * @return string
	 */
	protected function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="entry[]" value="%d" />',
			(int) $item->id
		);
	}

	/**
	 * Render the product column.
	 *
	 * @param object $item Entry object.
	 *
	 * @return string
	 */
	protected function column_product( $item ): string {
		if ( empty( $item->product_id ) ) {
			return '<em>' . esc_html__( 'None', 'simple-waitlist-for-woocommerce' ) . '</em>';
		}

		$product = wc_get_product( (int) $item->product_id );
		if ( ! $product ) {
			return '#' . esc_html( $item->product_id );
		}

		$edit_link = get_edit_post_link( (int) $item->product_id );
		$name      = $product->get_name();

		return $edit_link
			? '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $name ) . '</a>'
			: esc_html( $name );
	}

	/**
	 * Render the variation column.
	 *
	 * @param object $item Entry object.
	 *
	 * @return string
	 */
	protected function column_variation( $item ): string {
		if ( empty( $item->variation_id ) ) {
			return '<em>' . esc_html__( 'N/A', 'simple-waitlist-for-woocommerce' ) . '</em>';
		}

		$variation = wc_get_product( (int) $item->variation_id );
		if ( ! $variation ) {
			return '#' . esc_html( $item->variation_id );
		}

		return esc_html( $variation->get_name() );
	}

	/**
	 * Prepare items for display.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$this->process_bulk_action();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filter values, sanitized below.
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$search       = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		$product_id   = isset( $_REQUEST['product_id'] ) ? absint( $_REQUEST['product_id'] ) : 0;
		$status       = isset( $_REQUEST['notification_sent'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['notification_sent'] ) ) : '';
		$orderby      = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at';
		$order        = isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'DESC';
		// phpcs:enable

		$args = [
			'per_page'   => $per_page,
			'paged'      => $current_page,
			'search'     => $search,
			'product_id' => $product_id,
		];

		if ( '' !== $status && in_array( $status, [ '0', '1' ], true ) ) {
			$args['notification_sent'] = (int) $status;
		} else {
			$args['notification_sent'] = null;
		}

		$allowed_orderby = [ 'name', 'email', 'created_at' ];
		if ( in_array( $orderby, $allowed_orderby, true ) ) {
			$args['orderby'] = $orderby;
			$args['order']   = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';
		}

		$total_items = $this->database->count_entries( $args );
		$this->items = $this->database->get_entries( $args );
		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total_items / $per_page ),
			]
		);
	}

	/**
	 * Process bulk actions.
	 *
	 * @return void
	 */
	public function process_bulk_action(): void {
		$action = $this->current_action();
		if ( ! $action ) {
			return;
		}

		if ( ! isset( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
			return;
		}

		$ids = isset( $_REQUEST['entry'] ) ? array_map( 'intval', (array) wp_unslash( $_REQUEST['entry'] ) ) : [];
		if ( empty( $ids ) ) {
			return;
		}

		switch ( $action ) {
			case 'delete':
				foreach ( $ids as $id ) {
					$this->database->delete_entry( $id );
				}
				add_settings_error(
					'simple_waitlist_entries',
					'entries_deleted',
					__( 'Selected entries deleted.', 'simple-waitlist-for-woocommerce' ),
					'success'
				);
				break;

			case 'mark_notified':
				foreach ( $ids as $id ) {
					$this->database->mark_as_notified( $id );
				}
				add_settings_error(
					'simple_waitlist_entries',
					'entries_marked',
					__( 'Selected entries marked as notified.', 'simple-waitlist-for-woocommerce' ),
					'success'
				);
				break;
		}
	}

	/**
	 * Render extra tablenav (filters).
	 *
	 * @param string $which Top or bottom.
	 *
	 * @return void
	 */
	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filter values, sanitized below.
		$product_id        = isset( $_REQUEST['product_id'] ) ? absint( $_REQUEST['product_id'] ) : 0;
		$notification_sent = isset( $_REQUEST['notification_sent'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['notification_sent'] ) ) : '';
		// phpcs:enable
		?>
		<div class="alignleft actions">
			<label for="simple-waitlist-product-filter" class="screen-reader-text">
				<?php esc_html_e( 'Filter by product', 'simple-waitlist-for-woocommerce' ); ?>
			</label>
			<select name="product_id" id="simple-waitlist-product-filter">
				<option value="0"><?php esc_html_e( 'All products', 'simple-waitlist-for-woocommerce' ); ?></option>
				<?php
				$products = wc_get_products(
					[
						'limit'  => -1,
						'status' => 'publish',
					]
				);
				foreach ( $products as $product ) {
					printf(
						'<option value="%d" %s>%s</option>',
						esc_attr( (string) $product->get_id() ),
						selected( $product_id, $product->get_id(), false ),
						esc_html( $product->get_name() )
					);
				}
				?>
			</select>

			<label for="simple-waitlist-status-filter" class="screen-reader-text">
				<?php esc_html_e( 'Filter by status', 'simple-waitlist-for-woocommerce' ); ?>
			</label>
			<select name="notification_sent" id="simple-waitlist-status-filter">
				<option value=""><?php esc_html_e( 'All statuses', 'simple-waitlist-for-woocommerce' ); ?></option>
				<option value="0" <?php selected( $notification_sent, '0' ); ?>><?php esc_html_e( 'Pending', 'simple-waitlist-for-woocommerce' ); ?></option>
				<option value="1" <?php selected( $notification_sent, '1' ); ?>><?php esc_html_e( 'Notified', 'simple-waitlist-for-woocommerce' ); ?></option>
			</select>

			<?php submit_button( __( 'Filter', 'simple-waitlist-for-woocommerce' ), 'button', 'filter_action', false ); ?>
		</div>
		<?php
	}
}
