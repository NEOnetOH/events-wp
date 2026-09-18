<?php
/**
 * Gutenberg upcoming-events block.
 *
 * @package EventScheduleWp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eswp_Block {

	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	public function register_block(): void {
		wp_register_script(
			'eswp-block',
			ESWP_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ),
			ESWP_VERSION,
			true
		);

		register_block_type(
			'event-schedule-wp/upcoming',
			array(
				'api_version'     => 3,
				'title'           => __( 'Upcoming Events', 'event-schedule-wp' ),
				'description'     => __( 'Live upcoming events from events.apptoolstack.com.', 'event-schedule-wp' ),
				'category'        => 'widgets',
				'icon'            => 'calendar-alt',
				'editor_script'   => 'eswp-block',
				'style'           => 'eswp-frontend',
				'render_callback' => array( $this, 'render' ),
				'attributes'      => array(
					'limit'         => array(
						'type'    => 'number',
						'default' => 6,
					),
					'days'          => array(
						'type'    => 'number',
						'default' => 90,
					),
					'category'      => array(
						'type'    => 'string',
						'default' => '',
					),
					'categoryIds'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'showLocation'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'showCategory'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'title'         => array(
						'type'    => 'string',
						'default' => 'Upcoming Events',
					),
					'openInNewTab'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);
	}

	/**
	 * @param array<string, mixed> $attributes
	 */
	public function render( array $attributes ): string {
		$settings = Eswp_Settings::get();
		$shortcode = new Eswp_Shortcode();

		return $shortcode->render(
			array(
				'limit'           => min( 50, max( 1, (int) ( $attributes['limit'] ?? $settings['event_limit'] ) ) ),
				'days'            => min( 3650, max( 0, (int) ( $attributes['days'] ?? $settings['lookahead_days'] ) ) ),
				'category'        => sanitize_text_field( (string) ( $attributes['category'] ?? '' ) ),
				'category_ids'    => sanitize_text_field( (string) ( $attributes['categoryIds'] ?? '' ) ),
				'show_location'   => ! empty( $attributes['showLocation'] ) ? '1' : '0',
				'show_category'   => ! empty( $attributes['showCategory'] ) ? '1' : '0',
				'title'           => sanitize_text_field( (string) ( $attributes['title'] ?? __( 'Upcoming Events', 'event-schedule-wp' ) ) ),
				'open_in_new_tab' => ! empty( $attributes['openInNewTab'] ) ? '1' : '0',
			)
		);
	}
}
