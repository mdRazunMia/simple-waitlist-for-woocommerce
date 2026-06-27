<?php
/**
 * Gutenberg block for the waitlist form.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

/**
 * Registers a dynamic block that renders the waitlist form.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class Block {

	/**
	 * Shortcode instance.
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
	 * Register block hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Register the block type from metadata.
	 *
	 * @return void
	 */
	public function register_block(): void {
		$block_file = dirname( __DIR__ ) . '/blocks/waitlist-form/block.json';

		if ( ! file_exists( $block_file ) ) {
			return;
		}

		register_block_type_from_metadata(
			$block_file,
			[
				'render_callback' => [ $this, 'render' ],
			]
		);
	}

	/**
	 * Render the block.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content    Block content.
	 * @param WP_Block $block      Block instance.
	 *
	 * @return string
	 */
	public function render( array $attributes, string $content, $block ): string {
		$product_id   = ! empty( $attributes['productId'] ) ? (int) $attributes['productId'] : 0;
		$variation_id = ! empty( $attributes['variationId'] ) ? (int) $attributes['variationId'] : 0;

		// Auto-detect from block context (e.g. Single Product template).
		if ( ! $product_id && ! empty( $block->context['postId'] ) ) {
			$product_id = (int) $block->context['postId'];
		}

		if ( ! $product_id ) {
			global $product;
			if ( $product instanceof \WC_Product ) {
				$product_id = $product->get_id();
			}
		}

		if ( ! $product_id ) {
			return '';
		}

		$variation_id = $variation_id > 0 ? $variation_id : null;

		return $this->shortcode->render_form( $product_id, $variation_id );
	}
}
