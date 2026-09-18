<?php
/**
 * [event_schedule_upcoming] shortcode.
 *
 * @package EventScheduleWp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eswp_Shortcode {

	public function register(): void {
		add_shortcode( 'event_schedule_upcoming', array( $this, 'render' ) );
	}

	/**
	 * @param array<string, mixed>|string $atts
	 */
	public function render( $atts ): string {
		$settings = Eswp_Settings::get();
		$atts     = shortcode_atts(
			array(
				'limit'           => (int) $settings['event_limit'],
				'days'            => (int) $settings['lookahead_days'],
				'category'        => '',
				'category_ids'    => '',
				'show_location'   => (string) (int) $settings['show_location'],
				'show_category'   => (string) (int) $settings['show_category'],
				'show_ceu'        => (string) (int) $settings['show_ceu'],
				'calendar_url'    => (string) $settings['calendar_url'],
				'title'           => __( 'Upcoming Events', 'events-apptoolstack-com' ),
				'open_in_new_tab' => '0',
			),
			is_array( $atts ) ? $atts : array(),
			'event_schedule_upcoming'
		);

		wp_enqueue_style( 'eswp-frontend' );

		$query_args = array(
			'limit'    => min( 50, max( 1, (int) $atts['limit'] ) ),
			'days'     => min( 3650, max( 0, (int) $atts['days'] ) ),
			'category' => sanitize_text_field( (string) $atts['category'] ),
		);

		if ( '' !== trim( (string) $atts['category_ids'] ) ) {
			$query_args['category_ids'] = (string) $atts['category_ids'];
		}

		$events = Eswp_Query::upcoming( $query_args );

		return Eswp_Query::render_template(
			'upcoming-list.php',
			array(
				'events'          => $events,
				'settings'        => $settings,
				'title'           => sanitize_text_field( (string) $atts['title'] ),
				'calendar_url'    => Eswp_Security::sanitize_calendar_url( (string) $atts['calendar_url'] ),
				'show_location'   => $this->is_truthy( $atts['show_location'] ),
				'show_category'   => $this->is_truthy( $atts['show_category'] ),
				'show_ceu'        => $this->is_truthy( $atts['show_ceu'] ),
				'open_in_new_tab' => $this->is_truthy( $atts['open_in_new_tab'] ),
			)
		);
	}

	private function is_truthy( mixed $value ): bool {
		return in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on' ), true );
	}
}
