<?php
/**
 * Normalized event DTO from the events.apptoolstack.com API.
 *
 * @package EventScheduleWp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eswp_Event {

	public string $id = '';

	public string $uuid = '';

	public string $title = '';

	public ?DateTimeImmutable $starts_at = null;

	public ?DateTimeImmutable $ends_at = null;

	/**
	 * Meeting occurrences.
	 *
	 * @var array<int, array{starts_at: DateTimeImmutable|null, ends_at: DateTimeImmutable|null}>
	 */
	public array $times = array();

	public string $description = '';

	public string $url = '';

	public string $registration_url = '';

	public string $venue_name = '';

	public string $venue_address = '';

	public string $category_name = '';

	public string $flyer_url = '';

	public ?float $ceu_credits = null;

	public bool $is_private = false;

	/**
	 * @param array<string, mixed> $payload
	 */
	public static function from_api( array $payload, string $timezone ): self {
		$event              = new self();
		$event->id          = Eswp_Security::sanitize_text( isset( $payload['id'] ) ? (string) $payload['id'] : '', 80 );
		$event->uuid        = Eswp_Security::sanitize_text( isset( $payload['uuid'] ) ? (string) $payload['uuid'] : '', 80 );
		$event->title       = Eswp_Security::sanitize_text( isset( $payload['title'] ) ? (string) $payload['title'] : (string) ( $payload['name'] ?? '' ), 300 );
		$description        = isset( $payload['description'] ) ? (string) $payload['description'] : (string) ( $payload['short_description'] ?? '' );
		if ( strlen( $description ) > 20000 ) {
			$description = substr( $description, 0, 20000 );
		}
		$event->description = wp_kses_post( $description );
		$event->url         = Eswp_Security::sanitize_http_url( isset( $payload['url'] ) ? (string) $payload['url'] : (string) ( $payload['event_url'] ?? '' ) );
		$event->registration_url = Eswp_Security::sanitize_http_url( isset( $payload['registration_url'] ) ? (string) $payload['registration_url'] : '' );
		$event->venue_name  = Eswp_Security::sanitize_text( isset( $payload['venue_name'] ) ? (string) $payload['venue_name'] : '', 200 );
		$event->venue_address = Eswp_Security::sanitize_text( isset( $payload['venue_address1'] ) ? (string) $payload['venue_address1'] : (string) ( $payload['venue_address'] ?? '' ), 300 );
		$event->category_name = Eswp_Security::sanitize_text( isset( $payload['category_name'] ) ? (string) $payload['category_name'] : '', 120 );
		$event->flyer_url   = Eswp_Security::sanitize_http_url( isset( $payload['flyer_image_url'] ) ? (string) $payload['flyer_image_url'] : (string) ( $payload['flyer_url'] ?? '' ), true );
		$event->is_private  = ! empty( $payload['is_private'] );
		$event->ceu_credits = isset( $payload['ceu_credits'] ) && is_numeric( $payload['ceu_credits'] )
			? (float) $payload['ceu_credits']
			: null;
		$event->starts_at   = self::parse_datetime( $payload['starts_at'] ?? null, $timezone );
		$event->ends_at     = self::parse_datetime( $payload['ends_at'] ?? null, $timezone );

		if ( ! $event->ends_at && $event->starts_at && isset( $payload['duration'] ) && is_numeric( $payload['duration'] ) ) {
			$hours          = (float) $payload['duration'];
			$event->ends_at = $event->starts_at->modify( '+' . (int) round( $hours * 3600 ) . ' seconds' );
		}

		$times = array();
		if ( ! empty( $payload['times'] ) && is_array( $payload['times'] ) ) {
			foreach ( $payload['times'] as $time ) {
				if ( ! is_array( $time ) ) {
					continue;
				}

				$times[] = array(
					'starts_at' => self::parse_datetime( $time['starts_at'] ?? null, $timezone ),
					'ends_at'   => self::parse_datetime( $time['ends_at'] ?? null, $timezone ),
				);
			}
		}

		if ( empty( $times ) && $event->starts_at ) {
			$times[] = array(
				'starts_at' => $event->starts_at,
				'ends_at'   => $event->ends_at,
			);
		}

		$event->times = $times;

		return $event;
	}

	public function next_occurrence( DateTimeImmutable $now ): ?DateTimeImmutable {
		$slot = $this->next_slot( $now );

		return $slot['starts_at'];
	}

	/**
	 * @return array{starts_at: DateTimeImmutable|null, ends_at: DateTimeImmutable|null}
	 */
	public function next_slot( DateTimeImmutable $now ): array {
		$chosen = null;

		foreach ( $this->times as $time ) {
			$start = $time['starts_at'] ?? null;
			if ( ! $start instanceof DateTimeImmutable ) {
				continue;
			}

			if ( $start >= $now && ( null === $chosen || $start < $chosen['starts_at'] ) ) {
				$chosen = $time;
			}
		}

		if ( is_array( $chosen ) ) {
			return array(
				'starts_at' => $chosen['starts_at'] ?? null,
				'ends_at'   => $chosen['ends_at'] ?? null,
			);
		}

		return array(
			'starts_at' => $this->starts_at,
			'ends_at'   => $this->ends_at,
		);
	}

	public static function duration_label( ?DateTimeImmutable $start, ?DateTimeImmutable $end ): string {
		if ( ! $start instanceof DateTimeImmutable || ! $end instanceof DateTimeImmutable || $end <= $start ) {
			return '';
		}

		$minutes = (int) max( 1, (int) round( ( $end->getTimestamp() - $start->getTimestamp() ) / 60 ) );
		$hours   = intdiv( $minutes, 60 );
		$remain  = $minutes % 60;

		if ( $hours > 0 && $remain > 0 ) {
			return $hours . 'h ' . $remain . 'm';
		}

		if ( $hours > 0 ) {
			return $hours . 'h';
		}

		return $remain . 'm';
	}

	public static function parse_datetime( mixed $value, string $timezone ): ?DateTimeImmutable {
		if ( ! in_array( $timezone, timezone_identifiers_list(), true ) ) {
			$timezone = 'America/New_York';
		}

		if ( $value instanceof DateTimeImmutable ) {
			return $value->setTimezone( new DateTimeZone( $timezone ) );
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		try {
			$parsed = new DateTimeImmutable( $value );
			return $parsed->setTimezone( new DateTimeZone( $timezone ) );
		} catch ( Exception $exception ) {
			return null;
		}
	}
}
