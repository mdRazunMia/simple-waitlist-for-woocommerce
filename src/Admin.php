<?php
/**
 * Admin settings page for the Simple Waitlist for WooCommerce plugin.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

/**
 * Handles the WordPress admin settings page for the waitlist.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class Admin {

	const SETTINGS_GROUP = 'simple_waitlist_settings';

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 99 );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Add the waitlist submenu page under WooCommerce.
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Simple Waitlist for WooCommerce', 'simple-waitlist-for-woocommerce' ),
			__( 'Simple Waitlist for WooCommerce', 'simple-waitlist-for-woocommerce' ),
			'manage_woocommerce',
			'simple-waitlist',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Register plugin settings with the Settings API.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting( self::SETTINGS_GROUP, EmailService::OPTION_SUBJECT, [ $this, 'sanitize_subject' ] );
		register_setting( self::SETTINGS_GROUP, EmailService::OPTION_BODY, [ $this, 'sanitize_body' ] );
	}

	/**
	 * Sanitize email subject.
	 *
	 * @param string $value Raw input value.
	 *
	 * @return string Sanitized subject.
	 */
	public function sanitize_subject( string $value ): string {
		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize email body.
	 *
	 * @param string $value Raw input value.
	 *
	 * @return string Sanitized body.
	 */
	public function sanitize_body( string $value ): string {
		return sanitize_textarea_field( $value );
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		$email_subject = get_option( EmailService::OPTION_SUBJECT, EmailService::DEFAULT_SUBJECT );
		$email_body    = get_option( EmailService::OPTION_BODY, EmailService::DEFAULT_BODY );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Waitlist Notification Settings', 'simple-waitlist-for-woocommerce' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::SETTINGS_GROUP );
				?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo esc_attr( EmailService::OPTION_SUBJECT ); ?>"><?php esc_html_e( 'Email Subject', 'simple-waitlist-for-woocommerce' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="<?php echo esc_attr( EmailService::OPTION_SUBJECT ); ?>"
								name="<?php echo esc_attr( EmailService::OPTION_SUBJECT ); ?>"
								value="<?php echo esc_attr( $email_subject ); ?>"
								class="regular-text"
							/>
							<p class="description">
								<?php esc_html_e( 'Use {name} and {product_name} as placeholders.', 'simple-waitlist-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo esc_attr( EmailService::OPTION_BODY ); ?>"><?php esc_html_e( 'Email Body', 'simple-waitlist-for-woocommerce' ); ?></label>
						</th>
						<td>
							<textarea
								id="<?php echo esc_attr( EmailService::OPTION_BODY ); ?>"
								name="<?php echo esc_attr( EmailService::OPTION_BODY ); ?>"
								rows="10"
								cols="50"
								class="large-text"
							><?php echo esc_textarea( $email_body ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Available placeholders: {name}, {product_name}, {product_link}.', 'simple-waitlist-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
