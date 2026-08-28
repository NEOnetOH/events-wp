<?php
/**
 * Resolves upcoming events from the live Event Scheduler API.
 *
 * @package EventScheduleWp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eswp_Query {

	/**
	 * @param array<string, mixed> $settings
	 * @param array<string, mixed> $args
	 * @return array{timezone: string, now: DateTimeImmutable, end: DateTimeImmutable|null, days: int}
	 */
	public static function window( array $settings, array $args = array() ): array {
		$timezone = (string) ( $settings['timezone'] ?? 'America/New_York' );
		if ( ! in_array( $timezone, timezone_identifiers_list(), true ) ) {
			$timezone = 'America/New_York';
		}

		$now  = new DateTimeImmutable( 'now', new DateTimeZone( $timezone ) );
		$days = array_key_exists( 'days', $args ) && '' !== $args['days'] && null !== $args['days']
			? (int) $args['days']
			: (int) ( $settings['lookahead_days'] ?? 90 );
		$days = min( 3650, max( 0, $days ) );
		$end  = $days > 0 ? $now->modify( '+' . $days . ' days' ) : null;

		return array(
			'timezone' => $timezone,
			'now'      => $now,
			'end'      => $end,
			'days'     => $days,
		);
	}

	public static function is_within_window( Eswp_Event $event, DateTimeImmutable $now, ?DateTimeImmutable $end ): bool {
		$next = $event->next_occurrence( $now );
		if ( ! $next instanceof DateTimeImmutable ) {
			return false;
		}

		if ( $next < $now && ( ! $event->ends_at || $event->ends_at < $now ) ) {
			return false;
		}

		if ( $end instanceof DateTimeImmutable && $next > $end ) {
			return false;
		}

		return true;
	}

	public static function upcoming( array $args = array() ): array {
		$settings = Eswp_Settings::get();
		$limit    = isset( $args['limit'] ) ? min( 50, max( 1, (int) $args['limit'] ) ) : (int) $settings['event_limit'];

		return self::from_api( $settings, $limit, $args );
	}

	/**
	 * @param array<string, mixed> $settings
	 * @param array<string, mixed> $args
	 * @return array<int, Eswp_Event>
	 */
	private static function from_api( array $settings, int $limit, array $args ): array {
		$window = self::window( $settings, $args );
		$client = new Eswp_Api_Client( $settings );
		$query  = array(
			'start'         => $window['now']->format( 'Y-m-d' ),
			'limit'         => max( $limit, 20 ),
			'bypass_cache'  => true,
		);

		if ( $window['end'] instanceof DateTimeImmutable ) {
			$query['end'] = $window['end']->format( 'Y-m-d' );
		}

		if ( ! empty( $args['category_ids'] ) ) {
			$query['category_ids'] = $args['category_ids'];
		}

		$events = $client->list_events( $query );
		if ( is_wp_error( $events ) ) {
			return array();
		}

		$now      = $window['now'];
		$end      = $window['end'];
		$filtered = array();

		foreach ( $events as $event ) {
			if ( ! empty( $args['category'] ) && strcasecmp( $event->category_name, (string) $args['category'] ) !== 0 ) {
				continue;
			}

			if ( ! self::is_within_window( $event, $now, $end ) ) {
				continue;
			}

			$filtered[] = $event;
		}

		usort(
			$filtered,
			static function ( Eswp_Event $left, Eswp_Event $right ) use ( $now ): int {
				$left_next  = $left->next_occurrence( $now );
				$right_next = $right->next_occurrence( $now );
				$left_ts    = $left_next ? $left_next->getTimestamp() : PHP_INT_MAX;
				$right_ts   = $right_next ? $right_next->getTimestamp() : PHP_INT_MAX;

				return $left_ts <=> $right_ts;
			}
		);

		return array_slice( $filtered, 0, $limit );
	}

	/**
	 * Locate a template, allowing theme overrides in event-schedule-wp/.
	 */
	public static function locate_template( string $name ): string {
		$name = Eswp_Security::template_name( $name );
		if ( '' === $name ) {
			return '';
		}

		$theme = locate_template( 'event-schedule-wp/' . $name );
		if ( $theme && self::is_allowed_template_path( $theme, get_stylesheet_directory() ) ) {
			return $theme;
		}

		$plugin = ESWP_PATH . 'templates/' . $name;
		return self::is_allowed_template_path( $plugin, ESWP_PATH . 'templates' ) ? $plugin : '';
	}

	/**
	 * @param array<string, mixed> $vars
	 */
	public static function render_template( string $name, array $vars ): string {
		$path = self::locate_template( $name );
		if ( '' === $path || ! is_readable( $path ) ) {
			return '';
		}

		$render = static function ( string $template_path, array $template_vars ): void {
			extract( $template_vars, EXTR_SKIP );
			include $template_path;
		};

		ob_start();
		$render( $path, $vars );
		return (string) ob_get_clean();
	}

	private static function is_allowed_template_path( string $path, string $root ): bool {
		$real_path = realpath( $path );
		$real_root = realpath( $root );

		return is_string( $real_path )
			&& is_string( $real_root )
			&& str_starts_with( $real_path, $real_root );
	}
}
