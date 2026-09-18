<?php
/**
 * Upcoming events list.
 *
 * @package EventScheduleWp
 *
 * @var array<int, Eswp_Event> $events
 * @var array<string, mixed>   $settings
 * @var string                 $title
 * @var string                 $calendar_url
 * @var bool                   $show_location
 * @var bool                   $show_category
 * @var bool                   $show_ceu
 * @var bool                   $open_in_new_tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$open_in_new_tab = isset( $open_in_new_tab ) ? (bool) $open_in_new_tab : false;
$link_target     = $open_in_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';

$accent     = (string) ( $settings['accent_color'] ?? '#1b4f72' );
$date_color = (string) ( $settings['date_badge_color'] ?? '#1b4f72' );
$timezone   = (string) ( $settings['timezone'] ?? 'America/New_York' );
$now        = new DateTimeImmutable( 'now', new DateTimeZone( $timezone ) );
?>
<div class="eswp-upcoming" style="--eswp-accent: <?php echo esc_attr( $accent ); ?>; --eswp-date-badge: <?php echo esc_attr( $date_color ); ?>;">
	<?php if ( '' !== $title ) : ?>
		<h2 class="eswp-upcoming__title"><?php echo esc_html( $title ); ?></h2>
	<?php endif; ?>

	<?php if ( empty( $events ) ) : ?>
		<?php echo Eswp_Query::render_template( 'empty.php', array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php else : ?>
		<ul class="eswp-upcoming__list">
			<?php foreach ( $events as $event ) : ?>
				<?php
				echo Eswp_Query::render_template( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'upcoming-item.php',
					array(
						'event'           => $event,
						'settings'        => $settings,
						'now'             => $now,
						'show_location'   => $show_location,
						'show_category'   => $show_category,
						'show_ceu'        => $show_ceu,
						'open_in_new_tab' => $open_in_new_tab,
					)
				);
				?>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( '' !== $calendar_url ) : ?>
		<p class="eswp-upcoming__footer">
			<a class="eswp-upcoming__calendar-link" href="<?php echo esc_url( $calendar_url ); ?>"<?php echo $link_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php esc_html_e( 'View Calendar', 'events-apptoolstack-com' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
