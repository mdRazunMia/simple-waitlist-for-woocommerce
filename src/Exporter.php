<?php
/**
 * CSV exporter for waitlist entries.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

/**
 * Exports waitlist entries to CSV.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class Exporter {

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
		$this->database = $database;
	}

	/**
	 * Generate and send a CSV export.
	 *
	 * @param array $args Filter arguments passed to Database::get_entries.
	 *
	 * @return void
	 */
	public function send_csv( array $args = [] ): void {
		$args['per_page'] = PHP_INT_MAX;
		$args['paged']    = 1;

		$entries = $this->database->get_entries( $args );

		$filename = 'waitlist-entries-' . gmdate( 'Y-m-d-H-i-s' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			return;
		}

		// UTF-8 BOM for Excel compatibility.
		fprintf( $output, "\xEF\xBB\xBF" );

		fputcsv(
			$output,
			[
				__( 'ID', 'simple-waitlist-for-woocommerce' ),
				__( 'Name', 'simple-waitlist-for-woocommerce' ),
				__( 'Email', 'simple-waitlist-for-woocommerce' ),
				__( 'Product ID', 'simple-waitlist-for-woocommerce' ),
				__( 'Product Name', 'simple-waitlist-for-woocommerce' ),
				__( 'Variation ID', 'simple-waitlist-for-woocommerce' ),
				__( 'Variation Name', 'simple-waitlist-for-woocommerce' ),
				__( 'Notified', 'simple-waitlist-for-woocommerce' ),
				__( 'Consent', 'simple-waitlist-for-woocommerce' ),
				__( 'Signed up', 'simple-waitlist-for-woocommerce' ),
			]
		);

		foreach ( $entries as $entry ) {
			$product   = ! empty( $entry->product_id ) ? wc_get_product( (int) $entry->product_id ) : null;
			$variation = ! empty( $entry->variation_id ) ? wc_get_product( (int) $entry->variation_id ) : null;

			fputcsv(
				$output,
				[
					$entry->id,
					$entry->name,
					$entry->email,
					$entry->product_id,
					$product ? $product->get_name() : '',
					$entry->variation_id,
					$variation ? $variation->get_name() : '',
					! empty( $entry->notification_sent ) ? __( 'Yes', 'simple-waitlist-for-woocommerce' ) : __( 'No', 'simple-waitlist-for-woocommerce' ),
					! empty( $entry->consent_given ) ? __( 'Yes', 'simple-waitlist-for-woocommerce' ) : __( 'No', 'simple-waitlist-for-woocommerce' ),
					$entry->created_at,
				]
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Direct output stream, not a filesystem file.
		fclose( $output );
		exit;
	}
}
