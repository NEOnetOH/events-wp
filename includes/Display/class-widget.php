<?php
/**
 * Upcoming-events widget — the main way to publish the list on a site.
 *
 * @package EventScheduleWp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eswp_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'eswp_upcoming',
			__( 'Event Scheduler: Upcoming Events', 'event-schedule-wp' ),
			array(
				'description'           => __( 'Show live Event Scheduler events in a sidebar or widget area.', 'event-schedule-wp' ),
				'show_instance_in_rest' => true,
			)
		);
	}

	/**
	 * @param array<string, mixed> $args
	 * @param array<string, mixed> $instance
	 */
	public function widget( $args, $instance ): void {
		$settings = Eswp_Settings::get();
		$title    = isset( $instance['title'] ) ? (string) $instance['title'] : __( 'Upcoming Events', 'event-schedule-wp' );
		$limit    = isset( $instance['limit'] ) ? (int) $instance['limit'] : (int) $settings['event_limit'];
		$days     = isset( $instance['days'] ) ? (int) $instance['days'] : (int) $settings['lookahead_days'];

		wp_enqueue_style( 'eswp-frontend' );

		$events = Eswp_Query::upcoming(
			array(
				'limit' => $limit,
				'days'  => $days,
			)
		);
		$html   = Eswp_Query::render_template(
			'upcoming-list.php',
			array(
				'events'        => $events,
				'settings'      => $settings,
				'title'         => $title,
				'calendar_url'  => (string) $settings['calendar_url'],
				'show_location' => ! empty( $settings['show_location'] ),
				'show_category' => ! empty( $settings['show_category'] ),
				'show_ceu'      => ! empty( $settings['show_ceu'] ),
			)
		);

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * @param array<string, mixed> $instance
	 */
	public function form( $instance ): void {
		$title = isset( $instance['title'] ) ? (string) $instance['title'] : __( 'Upcoming Events', 'event-schedule-wp' );
		$limit = isset( $instance['limit'] ) ? (int) $instance['limit'] : 6;
		$days  = isset( $instance['days'] ) ? (int) $instance['days'] : 90;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'event-schedule-wp' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Number of events', 'event-schedule-wp' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number" min="1" max="50" value="<?php echo esc_attr( (string) $limit ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'days' ) ); ?>"><?php esc_html_e( 'Show events this many days ahead (0 = no date cap)', 'event-schedule-wp' ); ?></label>
			<input class="small-text" id="<?php echo esc_attr( $this->get_field_id( 'days' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'days' ) ); ?>" type="number" min="0" max="3650" value="<?php echo esc_attr( (string) $days ); ?>" />
		</p>
		<?php
	}

	/**
	 * @param array<string, mixed> $new_instance
	 * @param array<string, mixed> $old_instance
	 * @return array<string, mixed>
	 */
	public function update( $new_instance, $old_instance ): array {
		return array(
			'title' => sanitize_text_field( (string) ( $new_instance['title'] ?? '' ) ),
			'limit' => min( 50, max( 1, (int) ( $new_instance['limit'] ?? 6 ) ) ),
			'days'  => min( 3650, max( 0, (int) ( $new_instance['days'] ?? 90 ) ) ),
		);
	}
}
