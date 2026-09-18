<?php
/**
 * Plugin bootstrap.
 *
 * @package EventScheduleWp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eswp_Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$settings = new Eswp_Settings();
		$settings->register();

		$connect = new Eswp_Connect();
		$connect->register();

		$shortcode = new Eswp_Shortcode();
		$shortcode->register();

		$block = new Eswp_Block();
		$block->register();

		add_action( 'widgets_init', array( $this, 'register_widget' ) );
		add_action( 'init', array( $this, 'register_frontend_style' ) );
	}

	public static function activate(): void {
		wp_clear_scheduled_hook( 'eswp_sync_cron' );
		delete_option( 'eswp_sync_status' );
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'eswp_sync_cron' );
		flush_rewrite_rules();
	}

	public function register_widget(): void {
		register_widget( Eswp_Widget::class );
	}

	public function register_frontend_style(): void {
		wp_register_style(
			'eswp-frontend',
			ESWP_URL . 'assets/css/frontend.css',
			array(),
			ESWP_VERSION
		);
	}
}
