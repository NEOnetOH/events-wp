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
			__( 'events.apptoolstack.com: Upcoming Events', 'events-apptoolstack-com' ),
			array(
				'description'           => __( 'Show live events.apptoolstack.com events in a sidebar or widget area.', 'events-apptoolstack-com' ),
				'show_instance_in_rest' => true,
			)
		);
	}

	/**
	 * @param array<string, mixed> $args
	 * @param array<string, mixed> $instance
	 */
	public function widget( $args, $instance ): void {
		$settings         = Eswp_Settings::get();
		$title            = isset( $instance['title'] ) ? (string) $instance['title'] : __( 'Upcoming Events', 'events-apptoolstack-com' );
		$limit            = isset( $instance['limit'] ) ? (int) $instance['limit'] : (int) $settings['event_limit'];
		$days             = isset( $instance['days'] ) ? (int) $instance['days'] : (int) $settings['lookahead_days'];
		$category_ids     = isset( $instance['category_ids'] ) ? (string) $instance['category_ids'] : '';
		$open_in_new_tab  = ! empty( $instance['open_in_new_tab'] );

		wp_enqueue_style( 'eswp-frontend' );

		$query_args = array(
			'limit' => $limit,
			'days'  => $days,
		);

		if ( '' !== trim( $category_ids ) ) {
			$query_args['category_ids'] = $category_ids;
		}

		$events = Eswp_Query::upcoming( $query_args );
		$html   = Eswp_Query::render_template(
			'upcoming-list.php',
			array(
				'events'          => $events,
				'settings'        => $settings,
				'title'           => $title,
				'calendar_url'    => (string) $settings['calendar_url'],
				'show_location'   => ! empty( $settings['show_location'] ),
				'show_category'   => ! empty( $settings['show_category'] ),
				'show_ceu'        => ! empty( $settings['show_ceu'] ),
				'open_in_new_tab' => $open_in_new_tab,
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
		$title            = isset( $instance['title'] ) ? (string) $instance['title'] : __( 'Upcoming Events', 'events-apptoolstack-com' );
		$limit            = isset( $instance['limit'] ) ? (int) $instance['limit'] : 6;
		$days             = isset( $instance['days'] ) ? (int) $instance['days'] : 90;
		$category_ids     = isset( $instance['category_ids'] ) ? (string) $instance['category_ids'] : '';
		$open_in_new_tab  = ! empty( $instance['open_in_new_tab'] );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'events-apptoolstack-com' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>"><?php esc_html_e( 'Number of events', 'events-apptoolstack-com' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'limit' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'limit' ) ); ?>" type="number" min="1" max="50" value="<?php echo esc_attr( (string) $limit ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'days' ) ); ?>"><?php esc_html_e( 'Show events this many days ahead (0 = no date cap)', 'events-apptoolstack-com' ); ?></label>
			<input class="small-text" id="<?php echo esc_attr( $this->get_field_id( 'days' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'days' ) ); ?>" type="number" min="0" max="3650" value="<?php echo esc_attr( (string) $days ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'category_ids' ) ); ?>"><?php esc_html_e( 'Category IDs (comma-separated)', 'events-apptoolstack-com' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'category_ids' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'category_ids' ) ); ?>" type="text" value="<?php echo esc_attr( $category_ids ); ?>" placeholder="12, 34" />
			<small><?php esc_html_e( 'Limit this widget to specific categories (e.g. events for one department). Overrides the global Category IDs setting. Leave blank to use the global setting.', 'events-apptoolstack-com' ); ?></small>
		</p>
		<p>
			<input class="checkbox" type="checkbox"<?php checked( $open_in_new_tab ); ?> id="<?php echo esc_attr( $this->get_field_id( 'open_in_new_tab' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'open_in_new_tab' ) ); ?>" value="1" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'open_in_new_tab' ) ); ?>"><?php esc_html_e( 'Open event links in a new tab', 'events-apptoolstack-com' ); ?></label>
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
			'title'           => sanitize_text_field( (string) ( $new_instance['title'] ?? '' ) ),
			'limit'           => min( 50, max( 1, (int) ( $new_instance['limit'] ?? 6 ) ) ),
			'days'            => min( 3650, max( 0, (int) ( $new_instance['days'] ?? 90 ) ) ),
			'category_ids'    => self::sanitize_category_ids( (string) ( $new_instance['category_ids'] ?? '' ) ),
			'open_in_new_tab' => empty( $new_instance['open_in_new_tab'] ) ? 0 : 1,
		);
	}

	/**
	 * Keep only digits, commas, and spaces so the stored value is safe and predictable.
	 */
	private static function sanitize_category_ids( string $value ): string {
		$ids = Eswp_Query::normalize_id_list( $value );

		return implode( ',', $ids );
	}
}
