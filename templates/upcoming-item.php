<?php
/**
 * Single upcoming event row.
 *
 * @package EventScheduleWp
 *
 * @var Eswp_Event           $eswp_event
 * @var array<string, mixed> $eswp_settings
 * @var DateTimeImmutable    $eswp_now
 * @var bool                 $eswp_show_location
 * @var bool                 $eswp_show_category
 * @var bool                 $eswp_show_ceu
 * @var bool                 $eswp_open_in_new_tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$eswp_open_in_new_tab = ! empty( $eswp_open_in_new_tab );
$eswp_slot            = $eswp_event->next_slot( $eswp_now );
$eswp_occurrence      = $eswp_slot['starts_at'] instanceof DateTimeImmutable ? $eswp_slot['starts_at'] : $eswp_event->starts_at;
$eswp_ends_at         = $eswp_slot['ends_at'] instanceof DateTimeImmutable ? $eswp_slot['ends_at'] : $eswp_event->ends_at;
$eswp_permalink       = '' !== $eswp_event->url ? $eswp_event->url : '';
$eswp_duration        = Eswp_Event::duration_label( $eswp_occurrence, $eswp_ends_at );
$eswp_when_line       = '';
$eswp_length_line     = '';

if ( $eswp_occurrence instanceof DateTimeImmutable ) {
	$eswp_when_line = $eswp_occurrence->format( 'D, M j' ) . ' · ' . $eswp_occurrence->format( 'g:i a' );
	if ( $eswp_ends_at instanceof DateTimeImmutable ) {
		$eswp_when_line .= ' – ' . $eswp_ends_at->format( 'g:i a' );
	}

	if ( '' !== $eswp_duration ) {
		$eswp_length_line = sprintf(
			/* translators: 1: duration such as 1h, 2: weekday name */
			__( '%1$s (%2$s)', 'events-apptoolstack-com' ),
			$eswp_duration,
			$eswp_occurrence->format( 'l' )
		);
	} else {
		$eswp_length_line = $eswp_occurrence->format( 'l' );
	}
}
?>
<li class="eswp-event">
	<div class="eswp-event__date" aria-hidden="true">
		<div class="eswp-event__date-frame">
			<span class="eswp-event__day"><?php echo esc_html( $eswp_occurrence ? $eswp_occurrence->format( 'd' ) : '' ); ?></span>
			<span class="eswp-event__month"><?php echo esc_html( $eswp_occurrence ? $eswp_occurrence->format( 'M' ) : '' ); ?></span>
		</div>
	</div>
	<div class="eswp-event__body">
		<h3 class="eswp-event__title">
			<?php if ( $eswp_permalink ) : ?>
				<a href="<?php echo esc_url( $eswp_permalink ); ?>"<?php if ( $eswp_open_in_new_tab ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php echo esc_html( $eswp_event->title ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $eswp_event->title ); ?>
			<?php endif; ?>
		</h3>
		<?php if ( '' !== $eswp_when_line ) : ?>
			<p class="eswp-event__when">
				<span class="eswp-event__when-icon" aria-hidden="true"></span>
				<span><?php echo esc_html( $eswp_when_line ); ?></span>
			</p>
		<?php endif; ?>
		<?php if ( '' !== $eswp_length_line ) : ?>
			<p class="eswp-event__duration">
				<span class="eswp-event__duration-icon" aria-hidden="true"></span>
				<span><?php echo esc_html( $eswp_length_line ); ?></span>
			</p>
		<?php endif; ?>
		<?php if ( $eswp_show_location && '' !== $eswp_event->venue_name ) : ?>
			<p class="eswp-event__meta"><?php echo esc_html( $eswp_event->venue_name ); ?></p>
		<?php endif; ?>
		<?php if ( $eswp_show_category && '' !== $eswp_event->category_name ) : ?>
			<p class="eswp-event__category"><?php echo esc_html( $eswp_event->category_name ); ?></p>
		<?php endif; ?>
		<?php if ( $eswp_show_ceu && null !== $eswp_event->ceu_credits ) : ?>
			<p class="eswp-event__ceu">
				<?php
				printf(
					/* translators: %s: CEU credit amount */
					esc_html__( 'CEU: %s', 'events-apptoolstack-com' ),
					esc_html( (string) $eswp_event->ceu_credits )
				);
				?>
			</p>
		<?php endif; ?>
	</div>
</li>
