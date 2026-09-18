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

$eswp_prefixes = array(
	'eswp_oauth_state_',
	'eswp_access_token',
	'eswp_events_cache',
);

foreach ( $eswp_prefixes as $eswp_prefix ) {
	$eswp_like_value   = $wpdb->esc_like( '_transient_' . $eswp_prefix ) . '%';
	$eswp_like_timeout = $wpdb->esc_like( '_transient_timeout_' . $eswp_prefix ) . '%';

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall prefix wipe of plugin transients.
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$eswp_like_value,
			$eswp_like_timeout
		)
	);
}
