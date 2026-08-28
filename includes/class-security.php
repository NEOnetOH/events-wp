<?php
/**
 * URL, path, and outbound-request hardening.
 *
 * @package EventScheduleWp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eswp_Security {

	/**
	 * Allowed frontend templates. Prevents path traversal via include.
	 */
	private const TEMPLATES = array(
		'upcoming-list.php',
		'upcoming-item.php',
		'empty.php',
	);

	/**
	 * Normalize a customer scheduler origin and reject private or non-HTTPS targets.
	 */
	public static function normalize_scheduler_url( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}

		if ( ! preg_match( '#^https?://#i', $value ) ) {
			$value = 'https://' . ltrim( $value, '/' );
		}

		$normalized = esc_url_raw( untrailingslashit( $value ), array( 'https' ) );
		if ( '' === $normalized ) {
			return '';
		}

		return self::is_safe_remote_url( $normalized, true ) ? $normalized : '';
	}

	/**
	 * Public http(s) URL from the API or settings. Empty string if unsafe.
	 */
	public static function sanitize_http_url( string $url, bool $https_only = false ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}

		$allowed = $https_only ? array( 'https' ) : array( 'http', 'https' );
		$url     = esc_url_raw( $url, $allowed );
		if ( '' === $url || ! self::is_safe_remote_url( $url, $https_only ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * Same-site relative path or a public https URL.
	 */
	public static function sanitize_calendar_url( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}

		if ( ! preg_match( '#^https?://#i', $url ) ) {
			$path = '/' . ltrim( sanitize_text_field( $url ), '/' );
			if ( str_contains( $path, '..' ) || str_contains( $path, '\\' ) ) {
				return '';
			}

			return $path;
		}

		return self::sanitize_http_url( $url, true );
	}

	/**
	 * Relative API path such as /oauth/token or /api/v2.
	 */
	public static function sanitize_api_path( string $path, string $fallback ): string {
		$path = trim( $path );
		if ( '' === $path ) {
			$path = $fallback;
		}

		$path = '/' . ltrim( $path, '/' );
		$path = untrailingslashit( $path );

		if ( ! preg_match( '#^/[A-Za-z0-9/_-]+$#', $path ) || str_contains( $path, '..' ) ) {
			return untrailingslashit( '/' . ltrim( $fallback, '/' ) );
		}

		return $path;
	}

	public static function sanitize_secret( string $secret ): string {
		$secret = trim( $secret );
		$secret = preg_replace( '/[\x00-\x1F\x7F]/', '', $secret );

		return is_string( $secret ) ? $secret : '';
	}

	public static function sanitize_text( string $value, int $max = 500 ): string {
		$value = sanitize_text_field( wp_strip_all_tags( $value ) );

		if ( strlen( $value ) > $max ) {
			$value = substr( $value, 0, $max );
		}

		return $value;
	}

	public static function sanitize_date_format( string $format, string $fallback ): string {
		$format = trim( $format );
		if ( '' === $format || strlen( $format ) > 40 || ! preg_match( '/^[A-Za-z0-9 ,:\-\/\\\\T\.]+$/', $format ) ) {
			return $fallback;
		}

		return $format;
	}

	public static function template_name( string $name ): string {
		$base = basename( str_replace( '\\', '/', $name ) );

		return in_array( $base, self::TEMPLATES, true ) ? $base : '';
	}

	public static function is_safe_remote_url( string $url, bool $https_only = false ): bool {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return false;
		}

		$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
		if ( $https_only && 'https' !== $scheme ) {
			return false;
		}

		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return false;
		}

		if ( ! empty( $parts['user'] ) || ! empty( $parts['pass'] ) ) {
			return false;
		}

		$host = strtolower( (string) ( $parts['host'] ?? '' ) );
		if ( '' === $host || str_contains( $host, '..' ) ) {
			return false;
		}

		$port = isset( $parts['port'] ) ? (int) $parts['port'] : ( 'https' === $scheme ? 443 : 80 );
		if ( ! in_array( $port, array( 80, 443 ), true ) ) {
			return false;
		}

		if ( self::is_blocked_host( $host ) ) {
			return false;
		}

		if ( false === wp_http_validate_url( $url ) ) {
			return false;
		}

		return true;
	}

	public static function is_blocked_host( string $host ): bool {
		$host = rtrim( strtolower( $host ), '.' );

		if ( in_array( $host, array( 'localhost', 'localhost.localdomain' ), true ) ) {
			return true;
		}

		foreach ( array( '.localhost', '.local', '.internal', '.intranet', '.home', '.lan' ) as $suffix ) {
			if ( str_ends_with( $host, $suffix ) ) {
				return true;
			}
		}

		if ( str_contains( $host, 'metadata.google.internal' ) ) {
			return true;
		}

		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			return self::is_blocked_ip( $host );
		}

		if ( function_exists( 'gethostbynamel' ) ) {
			$ips = gethostbynamel( $host );
			if ( is_array( $ips ) ) {
				foreach ( $ips as $ip ) {
					if ( self::is_blocked_ip( (string) $ip ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	public static function is_blocked_ip( string $ip ): bool {
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return false === filter_var(
				$ip,
				FILTER_VALIDATE_IP,
				FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
			);
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$packed = inet_pton( $ip );
			if ( false === $packed ) {
				return true;
			}

			if ( $packed === inet_pton( '::1' ) ) {
				return true;
			}

			$first = ord( $packed[0] );
			$second = ord( $packed[1] );

			if ( 0xfc === ( $first & 0xfe ) ) {
				return true;
			}

			if ( 0xfe === $first && 0x80 === ( $second & 0xc0 ) ) {
				return true;
			}

			return false;
		}

		return true;
	}

	/**
	 * @param array<string, mixed> $args
	 * @return array<string, mixed>
	 */
	public static function remote_args( array $args = array() ): array {
		$defaults = array(
			'timeout'     => 15,
			'redirection' => 0,
			'user-agent'  => 'EventScheduleWp/' . ESWP_VERSION . '; ' . home_url( '/' ),
			'headers'     => array(
				'Accept' => 'application/json',
			),
		);

		return array_merge( $defaults, $args );
	}
}
