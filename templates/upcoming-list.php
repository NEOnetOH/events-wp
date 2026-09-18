<?php
/**
 * Upcoming events list.
 *
 * @package EventScheduleWp
 *
 * @var array<int, Eswp_Event> $eswp_events
 * @var array<string, mixed>   $eswp_settings
 * @var string                 $eswp_title
 * @var string                 $eswp_calendar_url
 * @var bool                   $eswp_show_location
 * @var bool                   $eswp_show_category
 * @var bool                   $eswp_show_ceu
 * @var bool                   $eswp_open_in_new_tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eswp_open_in_new_tab = ! empty( $eswp_open_in_new_tab );
$eswp_accent          = (string) ( $eswp_settings['accent_color'] ?? '#1b4f72' );
$eswp_date_color      = (string) ( $eswp_settings['date_badge_color'] ?? '#1b4f72' );
$eswp_timezone        = (string) ( $eswp_settings['timezone'] ?? 'America/New_York' );
if ( ! in_array( $eswp_timezone, timezone_identifiers_list(), true ) ) {
	$eswp_timezone = 'America/New_York';
}
$eswp_now = new DateTimeImmutable( 'now', new DateTimeZone( $eswp_timezone ) );
?>
<div class="eswp-upcoming" style="--eswp-accent: <?php echo esc_attr( $eswp_accent ); ?>; --eswp-date-badge: <?php echo esc_attr( $eswp_date_color ); ?>;">
	<?php if ( '' !== $eswp_title ) : ?>
		<h2 class="eswp-upcoming__title"><?php echo esc_html( $eswp_title ); ?></h2>
	<?php endif; ?>

	<?php if ( empty( $eswp_events ) ) : ?>
		<?php
		$eswp_empty_html = Eswp_Query::render_template( 'empty.php', array() );
		echo $eswp_empty_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template already escaped its output.
		?>
	<?php else : ?>
		<ul class="eswp-upcoming__list">
			<?php foreach ( $eswp_events as $eswp_event ) : ?>
				<?php
				$eswp_item_html = Eswp_Query::render_template(
					'upcoming-item.php',
					array(
						'eswp_event'           => $eswp_event,
						'eswp_settings'        => $eswp_settings,
						'eswp_now'             => $eswp_now,
						'eswp_show_location'   => $eswp_show_location,
						'eswp_show_category'   => $eswp_show_category,
						'eswp_show_ceu'        => $eswp_show_ceu,
						'eswp_open_in_new_tab' => $eswp_open_in_new_tab,
					)
				);
				echo $eswp_item_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template already escaped its output.
				?>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( '' !== $eswp_calendar_url ) : ?>
		<p class="eswp-upcoming__footer">
			<a class="eswp-upcoming__calendar-link" href="<?php echo esc_url( $eswp_calendar_url ); ?>"<?php if ( $eswp_open_in_new_tab ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>>
				<?php esc_html_e( 'View Calendar', 'events-apptoolstack-com' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
