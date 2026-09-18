<?php
/**
 * Settings API page and AJAX actions.
 *
 * @package EventScheduleWp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eswp_Settings {

	public static function defaults(): array {
		return array(
			'api_base_url'     => '',
			'oauth_path'       => '/oauth/token',
			'api_prefix'       => '/api/v2',
			'client_id'        => '',
			'client_secret'    => '',
			'scope'            => '',
			'group_ids'        => '',
			'category_ids'     => '',
			'timezone'         => 'America/New_York',
			'cache_ttl'        => 0,
			'event_limit'      => 6,
			'lookahead_days'   => 90,
			'calendar_url'     => '/events',
			'date_format'      => 'M, j - h:i a',
			'show_location'    => 0,
			'show_category'    => 0,
			'show_ceu'         => 0,
			'accent_color'     => '#3372f0',
			'date_badge_color' => '#212121',
		);
	}

	public static function get(): array {
		$stored = get_option( ESWP_OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$merged = array_merge( self::defaults(), $stored );
		$secret = (string) get_option( ESWP_SECRET_OPTION, '' );
		if ( '' === $secret && ! empty( $stored['client_secret'] ) ) {
			$secret = (string) $stored['client_secret'];
		}
		$merged['client_secret'] = $secret;

		return $merged;
	}

	/**
	 * Accept a full URL or a hostname from any events.apptoolstack.com host.
	 *
	 * events.districta.com and https://events.districtb.com/ become a clean
	 * HTTPS origin. Private, loopback, and non-HTTPS targets are rejected.
	 */
	public static function normalize_scheduler_url( string $value ): string {
		return Eswp_Security::normalize_scheduler_url( $value );
	}

	public static function scheduler_host( ?array $settings = null ): string {
		$settings = $settings ?? self::get();
		$base     = self::normalize_scheduler_url( (string) ( $settings['api_base_url'] ?? '' ) );
		$host     = wp_parse_url( $base, PHP_URL_HOST );

		return is_string( $host ) ? $host : '';
	}

	/**
	 * @param int[] $group_ids
	 */
	public static function apply_oauth_connection(
		string $base_url,
		string $client_id,
		string $client_secret,
		array $group_ids,
		string $oauth_path = '/oauth/token',
		string $api_prefix = '/api/v2'
	): void {
		$settings = self::get();
		$settings['api_base_url'] = self::normalize_scheduler_url( $base_url );
		$settings['client_id']    = Eswp_Security::sanitize_text( $client_id, 200 );
		$settings['oauth_path']   = Eswp_Security::sanitize_api_path( $oauth_path, '/oauth/token' );
		$settings['api_prefix']   = Eswp_Security::sanitize_api_path( $api_prefix, '/api/v2' );
		$settings['group_ids']    = implode(
			',',
			array_values( array_unique( array_filter( array_map( 'intval', $group_ids ) ) ) )
		);
		unset( $settings['client_secret'] );

		update_option( ESWP_OPTION_KEY, $settings, false );
		update_option( ESWP_SECRET_OPTION, Eswp_Security::sanitize_secret( $client_secret ), false );
		Eswp_Cache::clear_token();
		Eswp_Cache::clear_events();
	}

	public static function clear_oauth_connection(): void {
		$settings = self::get();
		$base     = (string) ( $settings['api_base_url'] ?? '' );
		$settings['client_id']  = '';
		$settings['group_ids']  = '';
		unset( $settings['client_secret'] );

		update_option( ESWP_OPTION_KEY, $settings, false );
		delete_option( ESWP_SECRET_OPTION );
		Eswp_Cache::clear_token( $base );
		Eswp_Cache::clear_events();
	}

	public static function is_connected( ?array $settings = null ): bool {
		$settings = $settings ?? self::get();

		return '' !== trim( (string) ( $settings['client_id'] ?? '' ) )
			&& '' !== trim( (string) ( $settings['client_secret'] ?? '' ) )
			&& '' !== trim( (string) ( $settings['group_ids'] ?? '' ) );
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
		add_action( 'wp_ajax_eswp_test_connection', array( $this, 'ajax_test_connection' ) );
	}

	public function add_page(): void {
		add_options_page(
			__( 'events.apptoolstack.com', 'event-schedule-wp' ),
			__( 'events.apptoolstack.com', 'event-schedule-wp' ),
			'manage_options',
			'event-schedule-wp',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_admin( string $hook ): void {
		if ( 'settings_page_event-schedule-wp' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'eswp-admin',
			ESWP_URL . 'assets/css/admin.css',
			array(),
			ESWP_VERSION
		);

		wp_enqueue_script(
			'eswp-admin',
			ESWP_URL . 'assets/js/admin.js',
			array(),
			ESWP_VERSION,
			true
		);

		wp_localize_script(
			'eswp-admin',
			'eswpAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'eswp_admin' ),
			)
		);
	}

	public function register_settings(): void {
		register_setting(
			'eswp_settings_group',
			ESWP_OPTION_KEY,
			array(
				'type'              => 'array',
				'capability'        => 'manage_options',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'eswp_api',
			__( 'API connection', 'event-schedule-wp' ),
			array( $this, 'render_api_intro' ),
			'event-schedule-wp'
		);

		add_settings_section(
			'eswp_display',
			__( 'Display', 'event-schedule-wp' ),
			array( $this, 'render_display_intro' ),
			'event-schedule-wp'
		);

		foreach ( $this->fields() as $field ) {
			add_settings_field(
				$field['id'],
				$field['label'],
				array( $this, 'render_field' ),
				'event-schedule-wp',
				$field['section'],
				$field
			);
		}
	}

	public function sanitize( mixed $input ): array {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();
		$current  = self::get();
		$clean    = $defaults;

		$clean['api_base_url'] = self::normalize_scheduler_url( (string) ( $input['api_base_url'] ?? '' ) );
		$clean['oauth_path']   = Eswp_Security::sanitize_api_path( (string) ( $input['oauth_path'] ?? $current['oauth_path'] ?? $defaults['oauth_path'] ), $defaults['oauth_path'] );
		$clean['api_prefix']   = Eswp_Security::sanitize_api_path( (string) ( $input['api_prefix'] ?? $current['api_prefix'] ?? $defaults['api_prefix'] ), $defaults['api_prefix'] );
		$clean['client_id']    = Eswp_Security::sanitize_text( (string) ( $input['client_id'] ?? '' ), 200 );
		$secret                = Eswp_Security::sanitize_secret( (string) ( $input['client_secret'] ?? '' ) );
		if ( '' !== $secret ) {
			update_option( ESWP_SECRET_OPTION, $secret, false );
		} elseif ( '' === (string) get_option( ESWP_SECRET_OPTION, '' ) && ! empty( $current['client_secret'] ) ) {
			update_option( ESWP_SECRET_OPTION, Eswp_Security::sanitize_secret( (string) $current['client_secret'] ), false );
		}
		unset( $clean['client_secret'] );
		$clean['scope'] = Eswp_Security::sanitize_text( (string) ( $input['scope'] ?? $current['scope'] ?? '' ), 200 );
		$clean['group_ids']     = sanitize_text_field( (string) ( $input['group_ids'] ?? '' ) );
		$clean['category_ids']  = sanitize_text_field( (string) ( $input['category_ids'] ?? '' ) );

		$timezone = (string) ( $input['timezone'] ?? $defaults['timezone'] );
		$clean['timezone'] = in_array( $timezone, timezone_identifiers_list(), true ) ? $timezone : $defaults['timezone'];

		$clean['cache_ttl']    = min( 86400, max( 0, (int) ( $input['cache_ttl'] ?? $current['cache_ttl'] ?? $defaults['cache_ttl'] ) ) );
		$clean['event_limit']    = min( 50, max( 1, (int) ( $input['event_limit'] ?? $defaults['event_limit'] ) ) );
		$clean['lookahead_days'] = min( 3650, max( 0, (int) ( $input['lookahead_days'] ?? $defaults['lookahead_days'] ) ) );
		$clean['calendar_url'] = Eswp_Security::sanitize_calendar_url( (string) ( $input['calendar_url'] ?? $defaults['calendar_url'] ) );
		$clean['date_format']  = Eswp_Security::sanitize_date_format( (string) ( $input['date_format'] ?? $current['date_format'] ?? $defaults['date_format'] ), $defaults['date_format'] );
		$clean['show_location'] = empty( $input['show_location'] ) ? 0 : 1;
		$clean['show_category'] = empty( $input['show_category'] ) ? 0 : 1;
		$clean['show_ceu']      = empty( $input['show_ceu'] ) ? 0 : 1;
		$clean['accent_color']  = sanitize_hex_color( (string) ( $input['accent_color'] ?? $defaults['accent_color'] ) ) ?: $defaults['accent_color'];
		$clean['date_badge_color'] = sanitize_hex_color( (string) ( $input['date_badge_color'] ?? $defaults['date_badge_color'] ) ) ?: $defaults['date_badge_color'];

		Eswp_Cache::clear_token();
		Eswp_Cache::clear_events();

		return $clean;
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap eswp-settings">
			<h1><?php esc_html_e( 'events.apptoolstack.com', 'event-schedule-wp' ); ?></h1>
			<p><?php esc_html_e( 'This plugin lists live events from events.apptoolstack.com. The required settings are the host URL, API key, secret, and group ID. Then publish the list with the Upcoming Events widget. Events, categories, and venues stay in events.apptoolstack.com.', 'event-schedule-wp' ); ?></p>
			<?php $this->render_connection_card( self::get() ); ?>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'eswp_settings_group' );
				do_settings_sections( 'event-schedule-wp' );
				submit_button();
				?>
			</form>

			<div class="eswp-actions">
				<h2><?php esc_html_e( 'Connection test', 'event-schedule-wp' ); ?></h2>
				<p>
					<button type="button" class="button" id="eswp-test-connection"><?php esc_html_e( 'Test connection', 'event-schedule-wp' ); ?></button>
				</p>
				<p class="eswp-action-status" id="eswp-action-status" aria-live="polite"></p>
			</div>
		</div>
		<?php
	}

	public function render_display_intro(): void {
		$widgets = admin_url( 'widgets.php' );
		echo '<p>' . wp_kses(
			sprintf(
				/* translators: %s: Appearance → Widgets admin URL */
				__( 'The main way to publish events is the <strong>events.apptoolstack.com: Upcoming Events</strong> widget. Add it under <a href="%s">Appearance → Widgets</a>. A shortcode and block are available if a page builder needs them.', 'event-schedule-wp' ),
				esc_url( $widgets )
			),
			array(
				'strong' => array(),
				'a'      => array( 'href' => array() ),
			)
		) . '</p>';
	}

	public function render_api_intro(): void {
		echo '<p>' . esc_html__( 'Required configuration: host URL, API key, secret, and group ID. Those four values come from this organization’s events.apptoolstack.com host. Connect can fill them in, or enter them by hand and save.', 'event-schedule-wp' ) . '</p>';
	}

	/**
	 * @param array<string, mixed> $field
	 */
	public function render_field( array $field ): void {
		$settings = self::get();
		$id       = (string) $field['id'];
		$value    = $settings[ $id ] ?? '';
		$name     = ESWP_OPTION_KEY . '[' . $id . ']';
		$type     = (string) $field['type'];

		if ( 'checkbox' === $type ) {
			printf(
				'<label><input type="checkbox" name="%1$s" value="1" %2$s /> %3$s</label>',
				esc_attr( $name ),
				checked( ! empty( $value ), true, false ),
				esc_html( (string) ( $field['help'] ?? '' ) )
			);
			return;
		}

		if ( 'select' === $type ) {
			echo '<select name="' . esc_attr( $name ) . '">';
			foreach ( (array) $field['options'] as $option => $label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( (string) $option ),
					selected( (string) $value, (string) $option, false ),
					esc_html( (string) $label )
				);
			}
			echo '</select>';
			$this->help( $field );
			return;
		}

		if ( 'password' === $type ) {
			printf(
				'<input type="password" class="regular-text" name="%1$s" value="" autocomplete="new-password" placeholder="%2$s" />',
				esc_attr( $name ),
				esc_attr( '' !== $value ? __( 'Saved. Enter a new secret to replace it.', 'event-schedule-wp' ) : '' )
			);
			$this->help( $field );
			return;
		}

		$input_type = in_array( $type, array( 'url', 'number', 'color', 'text' ), true ) ? $type : 'text';
		$extra      = '';
		if ( 'number' === $input_type ) {
			$extra = ' min="' . esc_attr( (string) ( $field['min'] ?? 0 ) ) . '" max="' . esc_attr( (string) ( $field['max'] ?? '' ) ) . '"';
		}

		if ( ! empty( $field['placeholder'] ) ) {
			$extra .= ' placeholder="' . esc_attr( (string) $field['placeholder'] ) . '"';
		}

		printf(
			'<input type="%1$s" class="%2$s" name="%3$s" value="%4$s"%5$s />',
			esc_attr( $input_type ),
			'color' === $input_type ? 'eswp-color' : 'regular-text',
			esc_attr( $name ),
			esc_attr( (string) $value ),
			$extra // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		$this->help( $field );
	}

	public function ajax_test_connection(): void {
		$this->assert_ajax();

		$client = new Eswp_Api_Client( self::get() );
		$result = $client->test_connection();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function fields(): array {
		return array(
			array(
				'id'          => 'api_base_url',
				'label'       => __( 'Host URL', 'event-schedule-wp' ),
				'type'        => 'text',
				'section'     => 'eswp_api',
				'placeholder' => 'https://events.districta.com',
				'help'        => __( 'Required. This organization’s public HTTPS events.apptoolstack.com hostname, or a custom domain such as https://events.districta.com. Localhost and private IPs are rejected.', 'event-schedule-wp' ),
			),
			array(
				'id'      => 'client_id',
				'label'   => __( 'API key', 'event-schedule-wp' ),
				'type'    => 'text',
				'section' => 'eswp_api',
				'help'    => __( 'Required. The events.apptoolstack.com API key (OAuth client ID). Created in events.apptoolstack.com, not in WordPress.', 'event-schedule-wp' ),
			),
			array(
				'id'      => 'client_secret',
				'label'   => __( 'Secret', 'event-schedule-wp' ),
				'type'    => 'password',
				'section' => 'eswp_api',
				'help'    => __( 'Required. The events.apptoolstack.com API secret (OAuth client secret). Leave blank to keep the saved secret.', 'event-schedule-wp' ),
			),
			array(
				'id'      => 'group_ids',
				'label'   => __( 'Group ID', 'event-schedule-wp' ),
				'type'    => 'text',
				'section' => 'eswp_api',
				'help'    => __( 'Required. The events.apptoolstack.com group this site should list. Use a comma-separated list only if more than one group is needed.', 'event-schedule-wp' ),
			),
			array(
				'id'      => 'category_ids',
				'label'   => __( 'Category IDs', 'event-schedule-wp' ),
				'type'    => 'text',
				'section' => 'eswp_api',
				'help'    => __( 'Optional. Comma-separated category IDs to include.', 'event-schedule-wp' ),
			),
			array(
				'id'      => 'timezone',
				'label'   => __( 'Timezone', 'event-schedule-wp' ),
				'type'    => 'text',
				'section' => 'eswp_display',
				'help'    => __( 'PHP timezone identifier. Default America/New_York.', 'event-schedule-wp' ),
			),
			array(
				'id'      => 'event_limit',
				'label'   => __( 'Default event limit', 'event-schedule-wp' ),
				'type'    => 'number',
				'section' => 'eswp_display',
				'min'     => 1,
				'max'     => 50,
				'help'    => __( 'Default number of events in the widget. The widget can override this.', 'event-schedule-wp' ),
			),
			array(
				'id'      => 'lookahead_days',
				'label'   => __( 'Show events this far ahead', 'event-schedule-wp' ),
				'type'    => 'number',
				'section' => 'eswp_display',
				'min'     => 0,
				'max'     => 3650,
				'help'    => __( 'Only include events that start within this many days. 30 is about a month, 90 a quarter, 365 a year. 0 means no date cap — only the event limit applies.', 'event-schedule-wp' ),
			),
			array(
				'id'          => 'calendar_url',
				'label'       => __( 'Calendar URL', 'event-schedule-wp' ),
				'type'        => 'text',
				'section'     => 'eswp_display',
				'placeholder' => '/events',
				'help'        => __( 'Where the “View Calendar” footer link (under the upcoming-events list) should point. Enter a path on this WordPress site, such as /events, or a full https:// URL. Leave blank to hide the link. This is a page on this WordPress site, not the events.apptoolstack.com host.', 'event-schedule-wp' ),
			),
			array(
				'id'      => 'show_location',
				'label'   => __( 'Show location', 'event-schedule-wp' ),
				'type'    => 'checkbox',
				'section' => 'eswp_display',
				'help'    => __( 'Show venue when the API provides one.', 'event-schedule-wp' ),
			),
			array(
				'id'      => 'show_category',
				'label'   => __( 'Show category', 'event-schedule-wp' ),
				'type'    => 'checkbox',
				'section' => 'eswp_display',
				'help'    => __( 'Show category when the API provides one.', 'event-schedule-wp' ),
			),
			array(
				'id'      => 'show_ceu',
				'label'   => __( 'Show CEU credits', 'event-schedule-wp' ),
				'type'    => 'checkbox',
				'section' => 'eswp_display',
				'help'    => __( 'Show continuing-education credits when present.', 'event-schedule-wp' ),
			),
			array(
				'id'      => 'accent_color',
				'label'   => __( 'Accent color', 'event-schedule-wp' ),
				'type'    => 'color',
				'section' => 'eswp_display',
			),
			array(
				'id'      => 'date_badge_color',
				'label'   => __( 'Date badge color', 'event-schedule-wp' ),
				'type'    => 'color',
				'section' => 'eswp_display',
			),
		);
	}

	/**
	 * @param array<string, mixed> $field
	 */
	private function help( array $field ): void {
		if ( empty( $field['help'] ) ) {
			return;
		}

		echo '<p class="description">' . esc_html( (string) $field['help'] ) . '</p>';
	}

	/**
	 * @param array<string, mixed> $settings
	 */
	private function render_connection_card( array $settings ): void {
		$host      = self::scheduler_host( $settings );
		$connected = self::is_connected( $settings );
		?>
		<div class="eswp-connection-card">
			<h2><?php esc_html_e( 'This WordPress site talks to', 'event-schedule-wp' ); ?></h2>
			<?php if ( '' === $host ) : ?>
				<p class="eswp-connection-card__empty">
					<?php esc_html_e( 'No events.apptoolstack.com host configured yet. Enter the host URL, API key, secret, and group ID below, or use Connect to fill them in.', 'event-schedule-wp' ); ?>
				</p>
			<?php else : ?>
				<p class="eswp-connection-card__host">
					<code><?php echo esc_html( $host ); ?></code>
					<?php if ( $connected ) : ?>
						<span class="eswp-connection-card__badge"><?php esc_html_e( 'Connected', 'event-schedule-wp' ); ?></span>
					<?php endif; ?>
				</p>
				<p class="description">
					<?php esc_html_e( 'Tokens and event caches are stored per host, so changing this URL switches the site to a different events.apptoolstack.com customer without leftover data from the previous one.', 'event-schedule-wp' ); ?>
				</p>
			<?php endif; ?>

			<div class="eswp-connect-actions">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="eswp-connect-form">
					<?php wp_nonce_field( 'eswp_connect_start' ); ?>
					<input type="hidden" name="action" value="eswp_connect_start" />
					<input type="hidden" name="api_base_url" id="eswp-connect-url" value="<?php echo esc_attr( (string) ( $settings['api_base_url'] ?? '' ) ); ?>" />
					<button type="submit" class="button button-primary">
						<?php echo $connected ? esc_html__( 'Reconnect to events.apptoolstack.com', 'event-schedule-wp' ) : esc_html__( 'Connect to events.apptoolstack.com', 'event-schedule-wp' ); ?>
					</button>
				</form>
				<?php if ( $connected ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="eswp-disconnect-form">
						<?php wp_nonce_field( 'eswp_disconnect' ); ?>
						<input type="hidden" name="action" value="eswp_disconnect" />
						<button type="submit" class="button">
							<?php esc_html_e( 'Disconnect', 'event-schedule-wp' ); ?>
						</button>
					</form>
				<?php endif; ?>
			</div>
			<p class="description">
				<?php esc_html_e( 'Connect is optional. It asks an events.apptoolstack.com admin to approve this site and writes the API key, secret, and group ID here. You can also paste those four values by hand.', 'event-schedule-wp' ); ?>
			</p>
		</div>
		<?php
	}

	private function assert_ajax(): void {
		check_ajax_referer( 'eswp_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to do that.', 'event-schedule-wp' ) ), 403 );
		}
	}
}
