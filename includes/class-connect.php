<?php
/**
 * One-click OAuth handshake with an Event Scheduler instance.
 *
 * @package EventScheduleWp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Eswp_Connect {

	private const TRANSIENT_PREFIX = 'eswp_oauth_state_';
	private const STATE_TTL        = 15 * MINUTE_IN_SECONDS;

	public function register(): void {
		add_action( 'admin_post_eswp_connect_start', array( $this, 'start' ) );
		add_action( 'admin_post_eswp_disconnect', array( $this, 'disconnect' ) );
		add_action( 'admin_init', array( $this, 'maybe_handle_callback' ) );
		add_action( 'admin_notices', array( $this, 'render_notices' ) );
	}

	public function start(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'event-schedule-wp' ) );
		}

		check_admin_referer( 'eswp_connect_start' );

		$posted = isset( $_POST['api_base_url'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['api_base_url'] ) ) : '';
		$base   = Eswp_Settings::normalize_scheduler_url( $posted );
		if ( '' === $base ) {
			$base = Eswp_Settings::normalize_scheduler_url( (string) ( Eswp_Settings::get()['api_base_url'] ?? '' ) );
		}

		if ( '' === $base ) {
			$this->redirect_settings( 'missing_url' );
		}

		$state     = wp_generate_password( 32, false, false );
		$verifier  = $this->base64_url_encode( random_bytes( 32 ) );
		$challenge = $this->base64_url_encode( hash( 'sha256', $verifier, true ) );
		$callback  = $this->callback_url();

		set_transient(
			$this->state_key(),
			array(
				'state'        => $state,
				'verifier'     => $verifier,
				'base_url'     => $base,
				'redirect_uri' => $callback,
			),
			self::STATE_TTL
		);

		$authorize = $base . '/wordpress/connect?' . http_build_query(
			array(
				'redirect_uri'          => $callback,
				'state'                 => $state,
				'site_url'              => home_url( '/' ),
				'site_name'             => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
				'code_challenge'        => $challenge,
				'code_challenge_method' => 'S256',
			),
			'',
			'&',
			PHP_QUERY_RFC3986
		);

		if ( ! Eswp_Security::is_safe_remote_url( $authorize, true ) ) {
			$this->redirect_settings( 'unsafe_url' );
		}

		wp_redirect( $authorize );
		exit;
	}

	public function disconnect(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'event-schedule-wp' ) );
		}

		check_admin_referer( 'eswp_disconnect' );

		Eswp_Settings::clear_oauth_connection();
		$this->redirect_settings( 'disconnected' );
	}

	public function maybe_handle_callback(): void {
		if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';
		$action = isset( $_GET['eswp_oauth'] ) ? sanitize_key( wp_unslash( (string) $_GET['eswp_oauth'] ) ) : '';
		if ( 'event-schedule-wp' !== $page || 'callback' !== $action ) {
			return;
		}

		if ( isset( $_GET['error'] ) ) {
			$this->redirect_settings( 'denied' );
		}

		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['state'] ) ) : '';
		$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['code'] ) ) : '';
		$saved = get_transient( $this->state_key() );

		if ( ! is_array( $saved ) || '' === $state || ! hash_equals( (string) ( $saved['state'] ?? '' ), $state ) ) {
			$this->redirect_settings( 'bad_state' );
		}

		if ( '' === $code ) {
			$this->redirect_settings( 'missing_code' );
		}

		delete_transient( $this->state_key() );

		$payload = $this->exchange(
			(string) $saved['base_url'],
			$code,
			(string) $saved['verifier'],
			(string) $saved['redirect_uri']
		);

		if ( is_wp_error( $payload ) ) {
			$this->redirect_settings( 'exchange_failed', $payload->get_error_message() );
		}

		Eswp_Settings::apply_oauth_connection(
			(string) $saved['base_url'],
			(string) $payload['client_id'],
			(string) $payload['client_secret'],
			(array) $payload['group_ids'],
			isset( $payload['oauth_path'] ) ? (string) $payload['oauth_path'] : '/oauth/token',
			isset( $payload['api_prefix'] ) ? (string) $payload['api_prefix'] : '/api/v2'
		);

		$this->redirect_settings( 'connected' );
	}

	public function render_notices(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'settings_page_event-schedule-wp' !== $screen->id ) {
			return;
		}

		$status = isset( $_GET['eswp'] ) ? sanitize_key( wp_unslash( (string) $_GET['eswp'] ) ) : '';
		if ( '' === $status ) {
			return;
		}

		$messages = array(
			'connected'       => array( 'success', __( 'Connected to Event Scheduler. The API key, secret, and group ID were saved.', 'event-schedule-wp' ) ),
			'disconnected'    => array( 'success', __( 'Disconnected. API credentials were removed from this WordPress site.', 'event-schedule-wp' ) ),
			'missing_url'     => array( 'error', __( 'Enter a public Event Scheduler URL first, then click Connect.', 'event-schedule-wp' ) ),
			'unsafe_url'      => array( 'error', __( 'That Event Scheduler URL is not a public HTTPS address.', 'event-schedule-wp' ) ),
			'denied'          => array( 'error', __( 'The Event Scheduler admin cancelled the connection.', 'event-schedule-wp' ) ),
			'bad_state'       => array( 'error', __( 'The connection expired or the request did not match this WordPress site. Try Connect again.', 'event-schedule-wp' ) ),
			'missing_code'    => array( 'error', __( 'Event Scheduler did not return an authorization code.', 'event-schedule-wp' ) ),
			'exchange_failed' => array( 'error', $this->stored_error_message() ),
		);

		if ( ! isset( $messages[ $status ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $messages[ $status ][0] ),
			esc_html( $messages[ $status ][1] )
		);
	}

	/**
	 * @return array{client_id: string, client_secret: string, group_ids: int[], oauth_path?: string, api_prefix?: string}|WP_Error
	 */
	private function exchange( string $base, string $code, string $verifier, string $redirect_uri ): array|WP_Error {
		$url = $base . '/wordpress/exchange';
		if ( ! Eswp_Security::is_safe_remote_url( $url, true ) ) {
			return new WP_Error( 'eswp_unsafe_url', __( 'The Event Scheduler URL is not a public HTTPS address.', 'event-schedule-wp' ) );
		}

		$response = wp_remote_post(
			$url,
			Eswp_Security::remote_args(
				array(
					'headers' => array(
						'Accept'       => 'application/json',
						'Content-Type' => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'code'          => $code,
							'code_verifier' => $verifier,
							'redirect_uri'  => $redirect_uri,
						)
					),
				)
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code_http = (int) wp_remote_retrieve_response_code( $response );
		$body      = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( $code_http < 200 || $code_http >= 300 || ! is_array( $body ) || empty( $body['client_id'] ) || empty( $body['client_secret'] ) ) {
			return new WP_Error(
				'eswp_exchange',
				sprintf(
					/* translators: %d: HTTP status */
					__( 'Event Scheduler did not issue credentials (HTTP %d). Confirm this instance supports WordPress connect.', 'event-schedule-wp' ),
					$code_http
				)
			);
		}

		$group_ids = array();
		if ( ! empty( $body['group_ids'] ) && is_array( $body['group_ids'] ) ) {
			$group_ids = array_values( array_filter( array_map( 'intval', $body['group_ids'] ) ) );
		}

		if ( empty( $group_ids ) ) {
			return new WP_Error( 'eswp_exchange', __( 'Event Scheduler did not return any group IDs.', 'event-schedule-wp' ) );
		}

		return array(
			'client_id'     => (string) $body['client_id'],
			'client_secret' => (string) $body['client_secret'],
			'group_ids'     => $group_ids,
			'oauth_path'    => isset( $body['oauth_path'] ) ? (string) $body['oauth_path'] : '/oauth/token',
			'api_prefix'    => isset( $body['api_prefix'] ) ? (string) $body['api_prefix'] : '/api/v2',
		);
	}

	private function callback_url(): string {
		return add_query_arg(
			array(
				'page'       => 'event-schedule-wp',
				'eswp_oauth' => 'callback',
			),
			admin_url( 'options-general.php' )
		);
	}

	private function state_key(): string {
		return self::TRANSIENT_PREFIX . get_current_user_id();
	}

	private function redirect_settings( string $status, string $detail = '' ): void {
		if ( '' !== $detail ) {
			set_transient( $this->state_key() . '_error', $detail, 5 * MINUTE_IN_SECONDS );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => 'event-schedule-wp',
					'eswp' => $status,
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	private function stored_error_message(): string {
		$key     = $this->state_key() . '_error';
		$message = get_transient( $key );
		delete_transient( $key );

		return is_string( $message ) && '' !== $message
			? $message
			: __( 'Could not exchange the authorization code for API credentials.', 'event-schedule-wp' );
	}

	private function base64_url_encode( string $raw ): string {
		return rtrim( strtr( base64_encode( $raw ), '+/', '-_' ), '=' );
	}
}
