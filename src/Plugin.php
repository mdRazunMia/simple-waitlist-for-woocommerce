<?php
/**
 * Main plugin bootstrap class for Simple Waitlist for WooCommerce.
 *
 * Wires together all components and handles the plugin lifecycle.
 *
 * @package SimpleWaitlist\WooCommerce
 */

declare(strict_types=1);

namespace SimpleWaitlist\WooCommerce;

/**
 * Main plugin bootstrap class.
 *
 * @package SimpleWaitlist\WooCommerce
 */
class Plugin {

	/**
	 * Database instance.
	 *
	 * @var DatabaseInterface
	 */
	private DatabaseInterface $database;

	/**
	 * Email service instance.
	 *
	 * @var EmailService
	 */
	private EmailService $email_service;

	/**
	 * Admin instance.
	 *
	 * @var Admin
	 */
	private Admin $admin;

	/**
	 * Shortcode instance.
	 *
	 * @var Shortcode
	 */
	private Shortcode $shortcode;

	/**
	 * REST controller instance.
	 *
	 * @var RestController
	 */
	private RestController $rest_controller;

	/**
	 * Notifier instance.
	 *
	 * @var Notifier
	 */
	private Notifier $notifier;

	/**
	 * Form handler instance.
	 *
	 * @var FormHandler
	 */
	private FormHandler $form_handler;

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private string $version = '1.0.0';

	/**
	 * Constructor.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 */
	public function __construct( string $plugin_file ) {
		$this->plugin_file = $plugin_file;

		// Create shared services.
		$this->database      = new Database();
		$this->email_service = new EmailService();

		// Create components with dependency injection.
		$this->admin           = new Admin();
		$this->shortcode       = new Shortcode();
		$this->rest_controller = new RestController( $this->database );
		$this->notifier        = new Notifier( $this->database, $this->email_service );
		$this->form_handler    = new FormHandler( $this->database );
	}

	/**
	 * Initialize the plugin by registering all hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->admin->register();
		$this->shortcode->register();
		$this->rest_controller->register();
		$this->notifier->register();
		$this->form_handler->register();
		$this->register_script();
	}

	/**
	 * Register front-end scripts and localize REST URL + nonce.
	 *
	 * @return void
	 */
	private function register_script(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Enqueue the public JavaScript and CSS files.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		$plugin_url = plugin_dir_url( $this->plugin_file );

		// Enqueue styles.
		wp_enqueue_style(
			'simple-waitlist',
			$plugin_url . 'public/css/public.css',
			[],
			$this->version
		);

		// Enqueue scripts.
		wp_enqueue_script(
			'simple-waitlist',
			$plugin_url . 'public/js/public.js',
			[ 'jquery' ],
			$this->version,
			true
		);

		wp_localize_script(
			'simple-waitlist',
			'simpleWaitlist',
			[
				'ajaxUrl' => rest_url( RestController::REST_NAMESPACE . RestController::REST_ROUTE ),
				'nonce'   => wp_create_nonce( FormHandler::ACTION ),
			]
		);
	}
}
