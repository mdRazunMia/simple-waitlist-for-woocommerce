<?php
/**
 * Per-product waitlist settings meta box.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

/**
 * Adds a "Waitlist" meta box to WooCommerce products.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class ProductMetaBox {

	const META_KEY     = '_simple_waitlist_disabled';
	const NONCE_ACTION = 'simple_waitlist_product_meta';
	const NONCE_NAME   = 'simple_waitlist_product_nonce';

	/**
	 * Register meta box hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post_product', [ $this, 'save' ] );
	}

	/**
	 * Add the waitlist meta box to products.
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'simple_waitlist_product_meta',
			__( 'Waitlist', 'simple-waitlist-for-woocommerce' ),
			[ $this, 'render' ],
			'product',
			'side',
			'default'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post Current post object.
	 *
	 * @return void
	 */
	public function render( $post ): void {
		$disabled = (bool) get_post_meta( $post->ID, self::META_KEY, true );
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<p>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( self::META_KEY ); ?>" value="1" <?php checked( $disabled ); ?> />
				<?php esc_html_e( 'Disable waitlist form for this product.', 'simple-waitlist-for-woocommerce' ); ?>
			</label>
		</p>
		<p class="description">
			<?php esc_html_e( 'Applies to both auto-injected and shortcode forms on this product.', 'simple-waitlist-for-woocommerce' ); ?>
		</p>
		<?php
	}

	/**
	 * Save the meta box value.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function save( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$disabled = isset( $_POST[ self::META_KEY ] ) ? 1 : 0;

		if ( $disabled ) {
			update_post_meta( $post_id, self::META_KEY, 1 );
		} else {
			delete_post_meta( $post_id, self::META_KEY );
		}
	}
}
