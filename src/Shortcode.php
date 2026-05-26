<?php
/**
 * Shortcode handler for the Simple Waitlist for WooCommerce plugin.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

/**
 * Handles the waitlist form shortcode.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class Shortcode {

	const TAG = 'simple_waitlist_form';

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( self::TAG, [ $this, 'render' ] );
	}

	/**
	 * Render the waitlist form.
	 *
	 * @param array|string $atts Shortcode attributes.
	 *
	 * @return string Rendered HTML.
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			[
				'product_id'   => '',
				'variation_id' => '',
			],
			$atts,
			self::TAG
		);

		$product_id   = intval( $atts['product_id'] );
		$variation_id = intval( $atts['variation_id'] );

		ob_start();
		?>
		<form method="post" action="" class="simple-waitlist-form">
			<?php wp_nonce_field( 'simple_waitlist_nonce_action', 'simple_waitlist_nonce' ); ?>
			<input type="hidden" name="product_id" value="<?php echo esc_attr( $product_id ); ?>" />
			<input type="hidden" name="variation_id" value="<?php echo esc_attr( $variation_id ); ?>" />
			<p>
				<input
					type="email"
					name="email"
					placeholder="<?php esc_attr_e( 'Your email', 'simple-waitlist-for-woocommerce' ); ?>"
					required
				/>
			</p>
			<p>
				<input
					type="text"
					name="name"
					placeholder="<?php esc_attr_e( 'Your name', 'simple-waitlist-for-woocommerce' ); ?>"
					required
				/>
			</p>
			<p>
				<button type="submit" id="simple-waitlist-submit" class="button" name="simple_waitlist_submit">
					<?php esc_html_e( 'Join Waitlist', 'simple-waitlist-for-woocommerce' ); ?>
				</button>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}
}
