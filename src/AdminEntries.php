<?php
/**
 * Admin waitlist entries page.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

/**
 * Registers and renders the waitlist entries admin page.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class AdminEntries {

	const PAGE_SLUG = 'simple-waitlist-entries';

	/**
	 * Database instance.
	 *
	 * @var DatabaseInterface
	 */
	private DatabaseInterface $database;

	/**
	 * Exporter instance.
	 *
	 * @var Exporter
	 */
	private Exporter $exporter;

	/**
	 * Constructor.
	 *
	 * @param DatabaseInterface $database Database instance.
	 * @param Exporter          $exporter Exporter instance.
	 */
	public function __construct( DatabaseInterface $database, Exporter $exporter ) {
		$this->database = $database;
		$this->exporter = $exporter;
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ], 99 );
		add_action( 'admin_init', [ $this, 'handle_export' ] );
	}

	/**
	 * Add the entries submenu under WooCommerce.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Waitlist Entries', 'simple-waitlist-for-woocommerce' ),
			__( 'Waitlist Entries', 'simple-waitlist-for-woocommerce' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Handle CSV export request.
	 *
	 * @return void
	 */
	public function handle_export(): void {
		if ( ! isset( $_GET['simple_waitlist_export'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to export entries.', 'simple-waitlist-for-woocommerce' ) );
		}

		check_admin_referer( 'simple_waitlist_export', '_wpnonce' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified above.
		$product_id        = isset( $_GET['product_id'] ) ? absint( $_GET['product_id'] ) : 0;
		$notification_sent = isset( $_GET['notification_sent'] ) ? sanitize_text_field( wp_unslash( $_GET['notification_sent'] ) ) : '';
		// phpcs:enable

		$args = [
			'product_id' => $product_id,
		];

		if ( '' !== $notification_sent && in_array( $notification_sent, [ '0', '1' ], true ) ) {
			$args['notification_sent'] = (int) $notification_sent;
		}

		$this->exporter->send_csv( $args );
	}

	/**
	 * Render the entries admin page.
	 *
	 * @return void
	 */
	public function render_page(): void {
		$table = new WaitlistTable( $this->database );
		$table->prepare_items();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Filter values are sanitized and used for display only.
		$product_id_filter = isset( $_REQUEST['product_id'] ) ? absint( $_REQUEST['product_id'] ) : 0;
		$status_filter     = isset( $_REQUEST['notification_sent'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['notification_sent'] ) ) : '';
		// phpcs:enable

		$export_url = wp_nonce_url(
			add_query_arg(
				[
					'simple_waitlist_export' => 1,
					'product_id'             => $product_id_filter,
					'notification_sent'      => $status_filter,
				]
			),
			'simple_waitlist_export'
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Waitlist Entries', 'simple-waitlist-for-woocommerce' ); ?></h1>

			<?php settings_errors( 'simple_waitlist_entries' ); ?>

			<p class="description">
				<?php esc_html_e( 'View, filter, and export customers waiting for products to come back in stock.', 'simple-waitlist-for-woocommerce' ); ?>
			</p>

			<a href="<?php echo esc_url( $export_url ); ?>" class="button" style="margin-bottom: 10px;">
				<?php esc_html_e( 'Export CSV', 'simple-waitlist-for-woocommerce' ); ?>
			</a>

			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>" />
				<?php $table->search_box( __( 'Search entries', 'simple-waitlist-for-woocommerce' ), 'simple_waitlist_entry' ); ?>
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}
}
