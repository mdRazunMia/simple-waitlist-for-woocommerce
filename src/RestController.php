<?php
/**
 * REST API controller for the Simple Waitlist for WooCommerce plugin.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Handles REST API endpoints for the waitlist.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class RestController {

	const REST_NAMESPACE = 'simple-waitlist/v1';
	const REST_ROUTE     = '/waitlist';

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
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Register the waitlist submission endpoint.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle_submission' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => $this->get_args_schema(),
			]
		);
	}

	/**
	 * Permission callback — verifies the nonce for CSRF protection.
	 *
	 * This is called before the main handler to reject unauthenticated requests early.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 *
	 * @return bool|WP_Error True if nonce is valid, WP_Error otherwise.
	 */
	public function check_permission( WP_REST_Request $request ) {
		$nonce = $request->get_param( 'simple_waitlist_nonce' );

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, FormHandler::ACTION ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Security check failed. Please refresh the page and try again.', 'simple-waitlist-for-woocommerce' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Get the argument schema for the REST endpoint.
	 *
	 * @return array Argument schema.
	 */
	private function get_args_schema(): array {
		return [
			'email'                 => [
				'required'          => true,
				'type'              => 'string',
				'format'            => 'email',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => function ( $value ) {
					return is_email( $value );
				},
			],
			'name'                  => [
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $value ) {
					return ! empty( trim( $value ) );
				},
			],
			'product_id'            => [
				'required'          => false,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],
			'variation_id'          => [
				'required'          => false,
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			],
			'simple_waitlist_nonce' => [
				'required' => true,
				'type'     => 'string',
			],
		];
	}

	/**
	 * Handle waitlist form submission via REST API.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 *
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function handle_submission( WP_REST_Request $request ) {
		$email        = $request->get_param( 'email' );
		$name         = $request->get_param( 'name' );
		$product_id   = $request->get_param( 'product_id' );
		$variation_id = $request->get_param( 'variation_id' );

		// Additional validation beyond schema validation.
		if ( ! is_email( $email ) || empty( trim( $name ) ) ) {
			return new WP_Error(
				'rest_invalid_data',
				__( 'Please provide a valid email and name.', 'simple-waitlist-for-woocommerce' ),
				[ 'status' => 400 ]
			);
		}

		$sanitized_email = sanitize_email( $email );
		$sanitized_name  = sanitize_text_field( $name );
		$sanitized_pid   = $product_id ? absint( $product_id ) : null;
		$sanitized_vid   = $variation_id ? absint( $variation_id ) : null;

		// Check for duplicate before inserting.
		if ( $this->database->has_duplicate( $sanitized_email, $sanitized_pid, $sanitized_vid ) ) {
			return new WP_Error(
				'rest_duplicate_entry',
				__( 'You are already on the waitlist for this product!', 'simple-waitlist-for-woocommerce' ),
				[ 'status' => 409 ]
			);
		}

		$result = $this->database->insert_entry( $sanitized_email, $sanitized_name, $sanitized_pid, $sanitized_vid );

		if ( false === $result ) {
			return new WP_Error(
				'rest_db_error',
				__( 'Could not save your entry. Please try again.', 'simple-waitlist-for-woocommerce' ),
				[ 'status' => 500 ]
			);
		}

		return new WP_REST_Response(
			[
				'message' => __( 'Thank you for joining the waitlist!', 'simple-waitlist-for-woocommerce' ),
			],
			200
		);
	}
}
