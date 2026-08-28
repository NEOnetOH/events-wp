<?php
/**
 * Transient cache for API responses and OAuth tokens.
 *
 * @package EventScheduleWp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eswp_Cache {

	/**
	 * @param array<string, mixed> $filters
	 */
	public static function events_key( string $base_url, array $filters ): string {
		return ESWP_EVENTS_TRANSIENT . '_' . md5( $base_url . wp_json_encode( $filters ) );
	}

	public static function get_events( string $key ): ?array {
		$cached = get_transient( $key );

		return is_array( $cached ) ? $cached : null;
	}

	/**
	 * @param array<int, array<string, mixed>> $events
	 */
	public static function set_events( string $key, array $events, int $ttl ): void {
		if ( $ttl < 1 ) {
			return;
		}

		set_transient( $key, $events, max( 60, $ttl ) );
	}

	public static function token_key( string $base_url = '' ): string {
		$suffix = '' === $base_url ? '' : '_' . md5( $base_url );

		return ESWP_TOKEN_TRANSIENT . $suffix;
	}

	public static function get_token( string $base_url = '' ): ?array {
		$cached = get_transient( self::token_key( $base_url ) );

		return is_array( $cached ) ? $cached : null;
	}

	public static function set_token( string $token, int $expires_in, string $base_url = '' ): void {
		set_transient(
			self::token_key( $base_url ),
			array(
				'token' => $token,
			),
			max( 60, $expires_in - 60 )
		);
	}

	public static function clear_token( string $base_url = '' ): void {
		if ( '' !== $base_url ) {
			delete_transient( self::token_key( $base_url ) );
			return;
		}

		delete_transient( ESWP_TOKEN_TRANSIENT );

		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . ESWP_TOKEN_TRANSIENT ) . '%',
				$wpdb->esc_like( '_transient_timeout_' . ESWP_TOKEN_TRANSIENT ) . '%'
			)
		);
	}

	public static function clear_events(): void {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . ESWP_EVENTS_TRANSIENT ) . '%',
				$wpdb->esc_like( '_transient_timeout_' . ESWP_EVENTS_TRANSIENT ) . '%'
			)
		);
	}
}
