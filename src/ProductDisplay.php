<?php
/**
 * Auto-injects the waitlist form on WooCommerce product pages.
 *
 * Removes the need to manually place the [simple_waitlist_form] shortcode
 * and figure out product_id / variation_id attributes.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

use WC_Product;
use WC_Product_Grouped;
use WC_Product_Variable;

/**
 * Renders waitlist forms automatically on single product pages.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class ProductDisplay {

	/**
	 * Shortcode instance used to render the form markup.
	 *
	 * @var Shortcode
	 */
	private Shortcode $shortcode;

	/**
	 * Constructor.
	 *
	 * @param Shortcode $shortcode Shortcode instance.
	 */
	public function __construct( Shortcode $shortcode ) {
		$this->shortcode = $shortcode;
	}

	/**
	 * Register product-page hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'woocommerce_single_product_summary', [ $this, 'maybe_render_form' ], 35 );
		add_action( 'wp_footer', [ $this, 'variation_selection_script' ] );
	}

	/**
	 * Render the appropriate waitlist form for the current product.
	 *
	 * @return void
	 */
	public function maybe_render_form(): void {
		if ( ! is_product() ) {
			return;
		}

		if ( ! apply_filters( 'simple_waitlist_auto_inject', true ) ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		switch ( $product->get_type() ) {
			case 'simple':
				$this->render_simple_form( $product );
				break;

			case 'variable':
				$this->render_variable_form( $product );
				break;

			case 'grouped':
				$this->render_grouped_forms( $product );
				break;
		}
	}

	/**
	 * Render the form for a simple/external product when it is out of stock.
	 *
	 * @param WC_Product $product The product.
	 *
	 * @return void
	 */
	private function render_simple_form( WC_Product $product ): void {
		if ( $this->is_available( $product ) ) {
			return;
		}

		echo '<div class="simple-waitlist-auto-wrap">';
		echo $this->shortcode->render_form( $product->get_id(), null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Render the form for a variable product.
	 *
	 * The variation_id is filled in by JavaScript when the customer selects
	 * an out-of-stock variation.
	 *
	 * @param WC_Product_Variable $product The variable product.
	 *
	 * @return void
	 */
	private function render_variable_form( WC_Product_Variable $product ): void {
		echo '<div class="simple-waitlist-auto-wrap simple-waitlist-variable-wrap" data-product-id="' . esc_attr( (string) $product->get_id() ) . '">';
		echo '<p class="simple-waitlist-variation-msg" style="display:none;"></p>';
		echo $this->shortcode->render_form( $product->get_id(), null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Render forms for out-of-stock children of a grouped product.
	 *
	 * @param WC_Product_Grouped $product The grouped product.
	 *
	 * @return void
	 */
	private function render_grouped_forms( WC_Product_Grouped $product ): void {
		$children = $product->get_children();
		if ( empty( $children ) ) {
			return;
		}

		$rendered = false;
		ob_start();
		foreach ( $children as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( ! $child instanceof WC_Product || $this->is_available( $child ) ) {
				continue;
			}

			$rendered = true;
			echo '<div class="simple-waitlist-grouped-item" data-child-id="' . esc_attr( (string) $child_id ) . '">';
			echo '<p class="simple-waitlist-grouped-label">' . esc_html( $child->get_name() ) . '</p>';
			echo $this->shortcode->render_form( $child->get_id(), null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
		}
		$forms = ob_get_clean();

		if ( ! $rendered ) {
			return;
		}

		echo '<div class="simple-waitlist-auto-wrap simple-waitlist-grouped-wrap">';
		echo $forms; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Check whether a product is currently available for purchase.
	 *
	 * @param WC_Product $product The product.
	 *
	 * @return bool True if in stock and purchasable.
	 */
	private function is_available( WC_Product $product ): bool {
		return $product->is_in_stock() && $product->is_purchasable();
	}

	/**
	 * Inline JS to sync the selected variation ID and toggle the submit button.
	 *
	 * @return void
	 */
	public function variation_selection_script(): void {
		if ( ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product_Variable ) {
			return;
		}
		?>
		<script>
		(function ($) {
			'use strict';

			function toggleVariationForm($form, variation) {
				var $wrap = $form.closest('.simple-waitlist-variable-wrap');
				var $variationInput = $form.find('input[name="variation_id"]');
				var $button = $form.find('button[type="submit"]');
				var $msg = $wrap.find('.simple-waitlist-variation-msg');

				if (!variation || !variation.variation_id) {
					$variationInput.val('');
					$button.prop('disabled', true);
					$msg.text('<?php echo esc_js( __( 'Select a variation to join the waitlist.', 'simple-waitlist-for-woocommerce' ) ); ?>').show();
					return;
				}

				$variationInput.val(variation.variation_id);

				if (variation.is_in_stock && variation.is_purchasable) {
					$button.prop('disabled', true);
					$msg.text('<?php echo esc_js( __( 'Selected variation is in stock.', 'simple-waitlist-for-woocommerce' ) ); ?>').show();
				} else {
					$button.prop('disabled', false);
					$msg.hide();
				}
			}

			$(document).on('found_variation', 'form.variations_form', function (event, variation) {
				var $form = $(this).closest('.product').find('.simple-waitlist-variable-wrap form');
				toggleVariationForm($form, variation);
			});

			$(document).on('reset_data', 'form.variations_form', function () {
				var $form = $(this).closest('.product').find('.simple-waitlist-variable-wrap form');
				toggleVariationForm($form, null);
			});

			$(function () {
				$('form.variations_form').closest('.product').find('.simple-waitlist-variable-wrap form').each(function () {
					$(this).find('button[type="submit"]').prop('disabled', true);
				});
			});
		})(jQuery);
		</script>
		<?php
	}
}
