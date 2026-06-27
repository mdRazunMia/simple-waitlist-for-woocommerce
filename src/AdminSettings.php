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
class AdminSettings {

	const PAGE_SLUG      = 'simple-waitlist';
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
			self::PAGE_SLUG,
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Register plugin settings with the Settings API.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting( self::SETTINGS_GROUP, EmailService::OPTION_SUBJECT, [ $this, 'sanitize_text' ] );
		register_setting( self::SETTINGS_GROUP, EmailService::OPTION_BODY, [ $this, 'sanitize_textarea' ] );
		register_setting( self::SETTINGS_GROUP, Settings::OPTION_AUTO_INJECT, [ $this, 'sanitize_bool' ] );
		register_setting( self::SETTINGS_GROUP, Settings::OPTION_AUTO_INJECT_TYPES, [ $this, 'sanitize_types' ] );
		register_setting( self::SETTINGS_GROUP, Settings::OPTION_FORM_POSITION, [ $this, 'sanitize_position' ] );
		register_setting( self::SETTINGS_GROUP, Settings::OPTION_REQUIRE_CONSENT, [ $this, 'sanitize_bool' ] );
		register_setting( self::SETTINGS_GROUP, Settings::OPTION_CONSENT_LABEL, [ $this, 'sanitize_text' ] );
	}

	/**
	 * Sanitize a text field.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public function sanitize_text( $value ): string {
		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize a textarea field.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public function sanitize_textarea( $value ): string {
		return sanitize_textarea_field( $value );
	}

	/**
	 * Sanitize a boolean value.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return bool
	 */
	public function sanitize_bool( $value ): bool {
		return (bool) $value;
	}

	/**
	 * Sanitize product type list.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return array
	 */
	public function sanitize_types( $value ): array {
		$allowed = [ Settings::PRODUCT_TYPE_SIMPLE, Settings::PRODUCT_TYPE_VARIABLE, Settings::PRODUCT_TYPE_GROUPED ];
		$types   = is_array( $value ) ? $value : [];

		return array_values( array_intersect( $types, $allowed ) );
	}

	/**
	 * Sanitize form position value.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public function sanitize_position( $value ): string {
		$allowed = [ Settings::POSITION_AFTER_CART, Settings::POSITION_BEFORE_CART, Settings::POSITION_AFTER_SUMMARY ];

		return in_array( $value, $allowed, true ) ? $value : Settings::POSITION_AFTER_CART;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		$email_subject = get_option( EmailService::OPTION_SUBJECT, EmailService::DEFAULT_SUBJECT );
		$email_body    = get_option( EmailService::OPTION_BODY, EmailService::DEFAULT_BODY );

		$auto_inject       = Settings::is_auto_inject_enabled();
		$auto_inject_types = Settings::get_auto_inject_types();
		$form_position     = Settings::get_form_position();
		$require_consent   = Settings::is_consent_required();
		$consent_label     = Settings::get_consent_label();

		$all_types = [
			Settings::PRODUCT_TYPE_SIMPLE   => __( 'Simple products', 'simple-waitlist-for-woocommerce' ),
			Settings::PRODUCT_TYPE_VARIABLE => __( 'Variable products', 'simple-waitlist-for-woocommerce' ),
			Settings::PRODUCT_TYPE_GROUPED  => __( 'Grouped products', 'simple-waitlist-for-woocommerce' ),
		];

		$positions = [
			Settings::POSITION_AFTER_CART    => __( 'After add-to-cart button', 'simple-waitlist-for-woocommerce' ),
			Settings::POSITION_BEFORE_CART   => __( 'Before add-to-cart button', 'simple-waitlist-for-woocommerce' ),
			Settings::POSITION_AFTER_SUMMARY => __( 'After product summary', 'simple-waitlist-for-woocommerce' ),
		];
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Waitlist Notification Settings', 'simple-waitlist-for-woocommerce' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::SETTINGS_GROUP );
				do_settings_sections( self::SETTINGS_GROUP );
				?>
				<h2><?php esc_html_e( 'Email Template', 'simple-waitlist-for-woocommerce' ); ?></h2>
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

				<h2><?php esc_html_e( 'Form Display', 'simple-waitlist-for-woocommerce' ); ?></h2>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Auto-inject form', 'simple-waitlist-for-woocommerce' ); ?></th>
						<td>
							<label>
								<input
									type="checkbox"
									name="<?php echo esc_attr( Settings::OPTION_AUTO_INJECT ); ?>"
									value="1"
									<?php checked( $auto_inject ); ?>
								/>
								<?php esc_html_e( 'Show the waitlist form automatically on out-of-stock product pages.', 'simple-waitlist-for-woocommerce' ); ?>
							</label>
							<p class="description">
								<?php esc_html_e( 'You can still use the [simple_waitlist_form] shortcode manually when this is disabled.', 'simple-waitlist-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Auto-inject product types', 'simple-waitlist-for-woocommerce' ); ?></th>
						<td>
							<?php foreach ( $all_types as $type => $label ) : ?>
								<label style="display:block; margin-bottom: 4px;">
									<input
										type="checkbox"
										name="<?php echo esc_attr( Settings::OPTION_AUTO_INJECT_TYPES ); ?>[]"
										value="<?php echo esc_attr( $type ); ?>"
										<?php checked( in_array( $type, $auto_inject_types, true ) ); ?>
									/>
									<?php echo esc_html( $label ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo esc_attr( Settings::OPTION_FORM_POSITION ); ?>"><?php esc_html_e( 'Form position', 'simple-waitlist-for-woocommerce' ); ?></label>
						</th>
						<td>
							<select id="<?php echo esc_attr( Settings::OPTION_FORM_POSITION ); ?>" name="<?php echo esc_attr( Settings::OPTION_FORM_POSITION ); ?>">
								<?php foreach ( $positions as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $form_position, $value ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Consent', 'simple-waitlist-for-woocommerce' ); ?></h2>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Require consent', 'simple-waitlist-for-woocommerce' ); ?></th>
						<td>
							<label>
								<input
									type="checkbox"
									name="<?php echo esc_attr( Settings::OPTION_REQUIRE_CONSENT ); ?>"
									value="1"
									<?php checked( $require_consent ); ?>
								/>
								<?php esc_html_e( 'Show a consent checkbox on the waitlist form.', 'simple-waitlist-for-woocommerce' ); ?>
							</label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="<?php echo esc_attr( Settings::OPTION_CONSENT_LABEL ); ?>"><?php esc_html_e( 'Consent label', 'simple-waitlist-for-woocommerce' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="<?php echo esc_attr( Settings::OPTION_CONSENT_LABEL ); ?>"
								name="<?php echo esc_attr( Settings::OPTION_CONSENT_LABEL ); ?>"
								value="<?php echo esc_attr( $consent_label ); ?>"
								class="large-text"
							/>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
