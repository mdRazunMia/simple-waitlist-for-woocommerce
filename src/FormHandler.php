<?php
/**
 * Form handler for the Simple Waitlist for WooCommerce plugin.
 *
 * Handles traditional (non-AJAX) form submissions from the waitlist shortcode,
 * extracted from the main Plugin class to adhere to Single Responsibility.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

/**
 * Processes waitlist signup form submissions.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class FormHandler {

	const ACTION = 'simple_waitlist_nonce_action';
	const FIELD  = 'simple_waitlist_submit';

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
	 * Register the form POST handler.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'handle' ] );
	}

	/**
	 * Handle traditional (non-AJAX) form submissions.
	 *
	 * @return void
	 */
	public function handle(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified below before processing data.
		if ( ! isset( $_POST[ self::FIELD ] ) ) {
			return;
		}

		if ( ! $this->verify_nonce() ) {
			wc_add_notice( __( 'Security check failed. Please try again.', 'simple-waitlist-for-woocommerce' ), 'error' );
			return;
		}

		$data = $this->sanitize_form_data();

		if ( ! is_email( $data['email'] ) || empty( $data['name'] ) ) {
			wc_add_notice( __( 'Please provide a valid email and name.', 'simple-waitlist-for-woocommerce' ), 'error' );
			return;
		}

		// Check for duplicate before inserting.
		if ( $this->database->has_duplicate( $data['email'], $data['product_id'], $data['variation_id'] ) ) {
			wc_add_notice( __( 'You are already on the waitlist for this product!', 'simple-waitlist-for-woocommerce' ), 'notice' );
			return;
		}

		$result = $this->database->insert_entry( $data['email'], $data['name'], $data['product_id'], $data['variation_id'] );

		if ( false === $result ) {
			wc_add_notice( __( 'Could not save your entry. Please try again.', 'simple-waitlist-for-woocommerce' ), 'error' );
		} else {
			wc_add_notice( __( 'Thank you for joining the waitlist!', 'simple-waitlist-for-woocommerce' ), 'success' );
		}
	}

	/**
	 * Verify the nonce from the form submission.
	 *
	 * Important: nonces must NOT be sanitized before verification,
	 * as sanitization can alter the value and break the check.
	 *
	 * @return bool True if nonce is valid.
	 */
	private function verify_nonce(): bool {
		return isset( $_POST['simple_waitlist_nonce'] )
			&& wp_verify_nonce( wp_unslash( $_POST['simple_waitlist_nonce'] ), self::ACTION );
	}

	/**
	 * Sanitize and extract form data from $_POST.
	 *
	 * @return array{email: string, name: string, product_id: int|null, variation_id: int|null}
	 */
	private function sanitize_form_data(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified in handle() before this is called.
		$data = [
			'email'        => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'name'         => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'product_id'   => isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0,
			'variation_id' => isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0,
		];
		// phpcs:enable
		return $data;
	}
}
