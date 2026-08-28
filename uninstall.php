<?php
/**
 * Removes plugin options, transients, and native synced posts on uninstall.
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

$synced_posts = get_posts(
	array(
		'post_type'      => 'es_event',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $synced_posts as $post_id ) {
	wp_delete_post( (int) $post_id, true );
}

foreach ( array( 'es_category', 'es_venue' ) as $taxonomy ) {
	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	if ( is_wp_error( $terms ) ) {
		continue;
	}

	foreach ( $terms as $term_id ) {
		wp_delete_term( (int) $term_id, $taxonomy );
	}
}
