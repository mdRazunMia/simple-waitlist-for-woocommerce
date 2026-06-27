<?php
/**
 * Plugin settings helpers for Simple Waitlist for WooCommerce.
 *
 * Centralizes option names and default values so both admin and frontend
 * code read settings the same way.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

/**
 * Plugin settings accessor.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class Settings {

	const OPTION_AUTO_INJECT       = 'simple_waitlist_auto_inject';
	const OPTION_AUTO_INJECT_TYPES = 'simple_waitlist_auto_inject_types';
	const OPTION_FORM_POSITION     = 'simple_waitlist_form_position';
	const OPTION_REQUIRE_CONSENT   = 'simple_waitlist_require_consent';
	const OPTION_CONSENT_LABEL     = 'simple_waitlist_consent_label';
	const OPTION_CONSENT_TIMESTAMP = 'simple_waitlist_consent_timestamp';

	const POSITION_AFTER_CART    = 'after_add_to_cart';
	const POSITION_BEFORE_CART   = 'before_add_to_cart';
	const POSITION_AFTER_SUMMARY = 'after_summary';

	const PRODUCT_TYPE_SIMPLE   = 'simple';
	const PRODUCT_TYPE_VARIABLE = 'variable';
	const PRODUCT_TYPE_GROUPED  = 'grouped';

	/**
	 * Check if auto-injection is enabled.
	 *
	 * @return bool
	 */
	public static function is_auto_inject_enabled(): bool {
		return (bool) get_option( self::OPTION_AUTO_INJECT, true );
	}

	/**
	 * Get product types that should auto-inject the form.
	 *
	 * @return array
	 */
	public static function get_auto_inject_types(): array {
		$defaults = [ self::PRODUCT_TYPE_SIMPLE, self::PRODUCT_TYPE_VARIABLE, self::PRODUCT_TYPE_GROUPED ];
		$types    = get_option( self::OPTION_AUTO_INJECT_TYPES, $defaults );

		return is_array( $types ) ? array_values( array_filter( $types ) ) : $defaults;
	}

	/**
	 * Check if a specific product type should auto-inject.
	 *
	 * @param string $type Product type slug.
	 *
	 * @return bool
	 */
	public static function is_auto_inject_type_enabled( string $type ): bool {
		return in_array( $type, self::get_auto_inject_types(), true );
	}

	/**
	 * Get the form position hook.
	 *
	 * @return string
	 */
	public static function get_form_position(): string {
		$position = get_option( self::OPTION_FORM_POSITION, self::POSITION_AFTER_CART );
		$allowed  = [ self::POSITION_AFTER_CART, self::POSITION_BEFORE_CART, self::POSITION_AFTER_SUMMARY ];

		return in_array( $position, $allowed, true ) ? $position : self::POSITION_AFTER_CART;
	}

	/**
	 * Check if GDPR consent checkbox is required.
	 *
	 * @return bool
	 */
	public static function is_consent_required(): bool {
		return (bool) get_option( self::OPTION_REQUIRE_CONSENT, true );
	}

	/**
	 * Get the consent checkbox label.
	 *
	 * @return string
	 */
	public static function get_consent_label(): string {
		$default = __( 'I agree to be notified when this product is back in stock.', 'simple-waitlist-for-woocommerce' );

		return get_option( self::OPTION_CONSENT_LABEL, $default );
	}
}
