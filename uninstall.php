<?php
/**
 * Removes plugin options and transients on uninstall.
 *
 * @package EventScheduleWp
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'eswp_settings' );
delete_option( 'eswp_client_secret' );
delete_option( 'eswp_sync_status' );

global $wpdb;

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_eswp_oauth_state_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_eswp_oauth_state_' ) . '%'
	)
);

$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_eswp_access_token' ) . '%',
		$wpdb->esc_like( '_transient_timeout_eswp_access_token' ) . '%'
	)
);
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_eswp_events_cache' ) . '%',
		$wpdb->esc_like( '_transient_timeout_eswp_events_cache' ) . '%'
	)
);
