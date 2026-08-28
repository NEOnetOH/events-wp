<?php
/**
 * OAuth client-credentials client for any Event Scheduler v2 instance.
 *
 * @package EventScheduleWp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eswp_Api_Client {

	/**
	 * @var array<string, mixed>
	 */
	private array $settings;

	/**
	 * @param array<string, mixed> $settings
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	public function test_connection(): array|WP_Error {
		$token = $this->get_access_token( true );
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$group_ids = $this->group_ids();
		if ( empty( $group_ids ) ) {
			$groups = $this->list_groups();
			if ( is_wp_error( $groups ) ) {
				return $groups;
			}

			return array(
				'ok'      => true,
				'host'    => $this->host_label(),
				'message' => sprintf(
					/* translators: 1: scheduler hostname, 2: number of groups */
					_n( 'Connected to %1$s. Found %2$d group. Enter a group ID to list events.', 'Connected to %1$s. Found %2$d groups. Enter a group ID to list events.', count( $groups ), 'event-schedule-wp' ),
					$this->host_label(),
					count( $groups )
				),
			);
		}

		$events = $this->list_events(
			array(
				'start' => wp_date( 'Y-m-d' ),
				'limit' => 5,
			)
		);

		if ( is_wp_error( $events ) ) {
			return $events;
		}

		return array(
			'ok'      => true,
			'host'    => $this->host_label(),
			'message' => sprintf(
				/* translators: 1: scheduler hostname, 2: number of upcoming events */
				_n( 'Connected to %1$s. Found %2$d upcoming event.', 'Connected to %1$s. Found %2$d upcoming events.', count( $events ), 'event-schedule-wp' ),
				$this->host_label(),
				count( $events )
			),
		);
	}

	/**
	 * @return array<int, array<string, mixed>>|WP_Error
	 */
	public function list_groups(): array|WP_Error {
		$response = $this->request( 'GET', '/groups' );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $response['data'] ?? $response;
		return is_array( $data ) ? $data : array();
	}

	/**
	 * @param array<string, mixed> $args
	 * @return array<int, Eswp_Event>|WP_Error
	 */
	public function list_events( array $args = array() ): array|WP_Error {
		if ( empty( $this->group_ids() ) ) {
			return new WP_Error( 'eswp_missing_groups', __( 'Enter the Event Scheduler group ID.', 'event-schedule-wp' ) );
		}

		$filters = $this->event_filters( $args );
		$ttl     = (int) ( $this->settings['cache_ttl'] ?? 0 );
		$key     = Eswp_Cache::events_key( (string) ( $this->settings['api_base_url'] ?? '' ), $filters );
		$use_cache = empty( $args['bypass_cache'] ) && $ttl > 0;

		if ( $use_cache ) {
			$cached = Eswp_Cache::get_events( $key );
			if ( is_array( $cached ) ) {
				return $this->hydrate( $cached );
			}
		}

		$all       = array();
		$limit     = isset( $args['page_size'] ) ? max( 1, min( 100, (int) $args['page_size'] ) ) : 100;
		$offset    = 0;
		$has_cap   = ! empty( $args['limit'] );
		$page_num  = 0;
		$max_pages = 50;

		do {
			$query            = $filters;
			$query['limit']   = $has_cap ? min( $limit, (int) $args['limit'] - count( $all ) ) : $limit;
			$query['offset']  = $offset;
			if ( $query['limit'] < 1 ) {
				break;
			}

			$response = $this->request( 'GET', '/events', $query );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$page = $response['data'] ?? array();
			if ( ! is_array( $page ) ) {
				return new WP_Error( 'eswp_invalid_response', __( 'The events API returned an unexpected payload.', 'event-schedule-wp' ) );
			}

			foreach ( $page as $row ) {
				if ( is_array( $row ) ) {
					$all[] = $row;
				}
			}

			$fetched  = count( $page );
			$offset  += $fetched;
			++$page_num;
			$continue = $page_num < $max_pages
				&& $fetched >= $query['limit']
				&& ( ! $has_cap || count( $all ) < (int) $args['limit'] );
		} while ( $continue );

		if ( $use_cache ) {
			Eswp_Cache::set_events( $key, $all, $ttl );
		}

		return $this->hydrate( $all );
	}

	/**
	 * @param array<string, mixed> $query
	 * @return array<string, mixed>|WP_Error
	 */
	public function request( string $method, string $path, array $query = array() ): array|WP_Error {
		if ( 'GET' !== strtoupper( $method ) ) {
			return new WP_Error( 'eswp_method', __( 'Only GET requests are allowed to the Event Scheduler API.', 'event-schedule-wp' ) );
		}

		$token = $this->get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$url = $this->api_url( $path );
		if ( is_wp_error( $url ) ) {
			return $url;
		}

		if ( ! empty( $query ) ) {
			$encoded = http_build_query( $this->flatten_query( $query ), '', '&', PHP_QUERY_RFC3986 );
			$url    .= ( str_contains( $url, '?' ) ? '&' : '?' ) . $encoded;
		}

		if ( ! Eswp_Security::is_safe_remote_url( $url, true ) ) {
			return new WP_Error( 'eswp_unsafe_url', __( 'The Event Scheduler URL is not a public HTTPS address.', 'event-schedule-wp' ) );
		}

		$response = wp_remote_request(
			$url,
			Eswp_Security::remote_args(
				array(
					'method'  => 'GET',
					'headers' => array(
						'Accept'        => 'application/json',
						'Authorization' => 'Bearer ' . $token,
					),
				)
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 401 === $code ) {
			$base = $this->base_url();
			Eswp_Cache::clear_token( is_wp_error( $base ) ? '' : $base );
			return new WP_Error( 'eswp_unauthorized', __( 'The events API rejected the access token. Check the API key and secret.', 'event-schedule-wp' ) );
		}

		if ( $code < 200 || $code >= 300 ) {
			$message = is_array( $body ) ? (string) ( $body['message'] ?? $body['error'] ?? '' ) : '';
			return new WP_Error(
				'eswp_http_error',
				$message
					? sprintf(
						/* translators: 1: HTTP status, 2: API error message */
						__( 'Events API error %1$s: %2$s', 'event-schedule-wp' ),
						$code,
						$message
					)
					: sprintf(
						/* translators: %d: HTTP status */
						__( 'Events API returned HTTP %d.', 'event-schedule-wp' ),
						$code
					)
			);
		}

		return is_array( $body ) ? $body : array();
	}

	public function get_access_token( bool $force = false ): string|WP_Error {
		$base = $this->base_url();
		if ( is_wp_error( $base ) ) {
			return $base;
		}

		if ( ! $force ) {
			$cached = Eswp_Cache::get_token( $base );
			if ( isset( $cached['token'] ) && is_string( $cached['token'] ) && '' !== $cached['token'] ) {
				return $cached['token'];
			}
		}

		$client_id     = trim( (string) ( $this->settings['client_id'] ?? '' ) );
		$client_secret = (string) ( $this->settings['client_secret'] ?? '' );

		if ( '' === $client_id || '' === $client_secret ) {
			return new WP_Error( 'eswp_missing_credentials', __( 'Enter the Event Scheduler API key and secret.', 'event-schedule-wp' ) );
		}

		$token_path = Eswp_Security::sanitize_api_path( (string) ( $this->settings['oauth_path'] ?? '/oauth/token' ), '/oauth/token' );
		$token_url  = $base . $token_path;

		$body = array(
			'grant_type'    => 'client_credentials',
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
		);

		$scope = trim( (string) ( $this->settings['scope'] ?? '' ) );
		if ( '' !== $scope ) {
			$body['scope'] = $scope;
		}

		if ( ! Eswp_Security::is_safe_remote_url( $token_url, true ) ) {
			return new WP_Error( 'eswp_unsafe_url', __( 'The Event Scheduler URL is not a public HTTPS address.', 'event-schedule-wp' ) );
		}

		$response = wp_remote_post(
			$token_url,
			Eswp_Security::remote_args(
				array(
					'body' => $body,
				)
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code    = (int) wp_remote_retrieve_response_code( $response );
		$payload = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 || ! is_array( $payload ) || empty( $payload['access_token'] ) ) {
			$message = is_array( $payload ) ? (string) ( $payload['message'] ?? $payload['error_description'] ?? $payload['error'] ?? '' ) : '';
			return new WP_Error(
				'eswp_token_error',
				$message
					? sprintf(
						/* translators: 1: HTTP status, 2: token error */
						__( 'OAuth token request failed (%1$s): %2$s', 'event-schedule-wp' ),
						$code,
						$message
					)
					: sprintf(
						/* translators: %d: HTTP status */
						__( 'OAuth token request failed with HTTP %d.', 'event-schedule-wp' ),
						$code
					)
			);
		}

		$expires_in = isset( $payload['expires_in'] ) ? (int) $payload['expires_in'] : 3600;
		Eswp_Cache::set_token( (string) $payload['access_token'], $expires_in, $base );

		return (string) $payload['access_token'];
	}

	/**
	 * @return int[]
	 */
	public function group_ids(): array {
		return $this->id_list( (string) ( $this->settings['group_ids'] ?? '' ) );
	}

	/**
	 * @return int[]
	 */
	public function category_ids(): array {
		return $this->id_list( (string) ( $this->settings['category_ids'] ?? '' ) );
	}

	/**
	 * @param array<string, mixed> $args
	 * @return array<string, mixed>
	 */
	private function event_filters( array $args ): array {
		$filters = array(
			'group_ids' => $this->group_ids(),
		);

		$start = $args['start'] ?? wp_date( 'Y-m-d' );
		if ( is_string( $start ) && preg_match( '/^\d{4}-\d{2}-\d{2}/', $start ) ) {
			$filters['start'] = substr( $start, 0, 25 );
		}

		if ( ! empty( $args['end'] ) && is_string( $args['end'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}/', $args['end'] ) ) {
			$filters['end'] = substr( $args['end'], 0, 25 );
		}

		$category_ids = $this->category_ids();
		if ( ! empty( $args['category_ids'] ) && is_array( $args['category_ids'] ) ) {
			$category_ids = array_map( 'intval', $args['category_ids'] );
		}

		if ( ! empty( $category_ids ) ) {
			$filters['category_ids'] = $category_ids;
		}

		if ( ! empty( $args['statuses'] ) && is_array( $args['statuses'] ) ) {
			$filters['statuses'] = array_values(
				array_filter(
					array_map(
						static fn( $status ) => Eswp_Security::sanitize_text( (string) $status, 40 ),
						$args['statuses']
					)
				)
			);
		}

		return $filters;
	}

	/**
	 * @param array<int, array<string, mixed>> $rows
	 * @return array<int, Eswp_Event>
	 */
	private function hydrate( array $rows ): array {
		$timezone = (string) ( $this->settings['timezone'] ?? 'America/New_York' );
		$events   = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$event = Eswp_Event::from_api( $row, $timezone );
			if ( $event->is_private ) {
				continue;
			}

			if ( '' === $event->id && '' === $event->title ) {
				continue;
			}

			$events[] = $event;
		}

		return $events;
	}

	private function api_url( string $path ): string|WP_Error {
		$base = $this->base_url();
		if ( is_wp_error( $base ) ) {
			return $base;
		}

		$prefix = Eswp_Security::sanitize_api_path( (string) ( $this->settings['api_prefix'] ?? '/api/v2' ), '/api/v2' );
		$path   = Eswp_Security::sanitize_api_path( $path, '/events' );

		return $base . $prefix . $path;
	}

	public function host_label(): string {
		$base = $this->base_url();
		if ( is_wp_error( $base ) ) {
			return '';
		}

		$host = wp_parse_url( $base, PHP_URL_HOST );

		return is_string( $host ) && '' !== $host ? $host : $base;
	}

	private function base_url(): string|WP_Error {
		$base = Eswp_Settings::normalize_scheduler_url( (string) ( $this->settings['api_base_url'] ?? '' ) );
		if ( '' === $base ) {
			return new WP_Error(
				'eswp_missing_base_url',
				__( 'Enter your Event Scheduler URL, for example https://events.districta.com.', 'event-schedule-wp' )
			);
		}

		return $base;
	}

	/**
	 * @param array<string, mixed> $query
	 * @return array<string, mixed>
	 */
	private function flatten_query( array $query ): array {
		$flat = array();

		foreach ( $query as $key => $value ) {
			if ( is_array( $value ) ) {
				$flat[ $key ] = array_values( $value );
				continue;
			}

			$flat[ $key ] = $value;
		}

		return $flat;
	}

	/**
	 * @return int[]
	 */
	private function id_list( string $raw ): array {
		$ids = array();

		foreach ( preg_split( '/[\s,]+/', $raw ) as $part ) {
			if ( '' === $part || ! is_numeric( $part ) ) {
				continue;
			}

			$ids[] = (int) $part;
		}

		return array_values( array_unique( $ids ) );
	}
}
