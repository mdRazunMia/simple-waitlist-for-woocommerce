<?php
/**
 * Email service for the Simple Waitlist for WooCommerce plugin.
 *
 * Centralizes email template defaults, placeholder replacement,
 * and sending logic to eliminate duplication between Admin and Notifier.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

use WC_Product;

/**
 * Handles email composition and sending for waitlist notifications.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class EmailService {

	const OPTION_SUBJECT = 'simple_waitlist_email_subject';
	const OPTION_BODY    = 'simple_waitlist_email_body';

	const DEFAULT_SUBJECT = 'Good news! Your waitlist item is back in stock!';
	const DEFAULT_BODY    = "Hi {name},\n\nWe're excited to let you know that the product you were waiting for is now back in stock. You can purchase it here: {product_link}\n\nThank you for your patience!";

	/**
	 * Get the email subject with fallback to default.
	 *
	 * @return string
	 */
	public function get_subject(): string {
		return get_option( self::OPTION_SUBJECT, self::DEFAULT_SUBJECT );
	}

	/**
	 * Get the email body with fallback to default.
	 *
	 * @return string
	 */
	public function get_body(): string {
		return get_option( self::OPTION_BODY, self::DEFAULT_BODY );
	}

	/**
	 * Replace placeholders in the email template with actual values.
	 *
	 * Supported placeholders: {name}, {product_name}, {product_link}
	 *
	 * @param string $template     Email template with placeholders.
	 * @param string $name         Subscriber name.
	 * @param string $product_name Product name.
	 * @param string $product_link Product URL.
	 *
	 * @return string Processed string with placeholders replaced.
	 */
	public function replace_placeholders( string $template, string $name, string $product_name, string $product_link ): string {
		$placeholders = [
			'{name}'         => $name,
			'{product_name}' => $product_name,
			'{product_link}' => $product_link,
		];

		return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $template );
	}

	/**
	 * Send a back-in-stock notification email.
	 *
	 * @param string $email        Recipient email address.
	 * @param string $name         Recipient name.
	 * @param int    $product_id   Product ID.
	 * @param int    $variation_id Variation ID (0 for simple products).
	 *
	 * @return bool Whether the email was sent successfully.
	 */
	public function send_notification( string $email, string $name, int $product_id, int $variation_id = 0 ): bool {
		if ( empty( $email ) || empty( $name ) || empty( $product_id ) ) {
			return false;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		$subject_template = $this->get_subject();
		$body_template    = $this->get_body();
		$product_name     = $product->get_name();
		$product_link     = get_permalink( $product_id );

		$subject = $this->replace_placeholders( $subject_template, $name, $product_name, $product_link );
		$message = $this->replace_placeholders( $body_template, $name, $product_name, $product_link );

		return wp_mail( $email, $subject, $message );
	}
}
