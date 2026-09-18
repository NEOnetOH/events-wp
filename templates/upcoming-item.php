<?php
/**
 * Single upcoming event row.
 *
 * @package EventScheduleWp
 *
 * @var Eswp_Event           $event
 * @var array<string, mixed> $settings
 * @var DateTimeImmutable    $now
 * @var bool                 $show_location
 * @var bool                 $show_category
 * @var bool                 $show_ceu
 * @var bool                 $open_in_new_tab
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$open_in_new_tab = isset( $open_in_new_tab ) ? (bool) $open_in_new_tab : false;
$link_target     = $open_in_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';

$slot        = $event->next_slot( $now );
$occurrence  = $slot['starts_at'] instanceof DateTimeImmutable ? $slot['starts_at'] : $event->starts_at;
$ends_at     = $slot['ends_at'] instanceof DateTimeImmutable ? $slot['ends_at'] : $event->ends_at;
$permalink   = '' !== $event->url ? $event->url : '';
$duration    = Eswp_Event::duration_label( $occurrence, $ends_at );
$when_line   = '';
$length_line = '';

if ( $occurrence instanceof DateTimeImmutable ) {
	$when_line = $occurrence->format( 'D, M j' ) . ' · ' . $occurrence->format( 'g:i a' );
	if ( $ends_at instanceof DateTimeImmutable ) {
		$when_line .= ' – ' . $ends_at->format( 'g:i a' );
	}

	if ( '' !== $duration ) {
		$length_line = sprintf(
			/* translators: 1: duration such as 1h, 2: weekday name */
			__( '%1$s (%2$s)', 'events-apptoolstack-com' ),
			$duration,
			$occurrence->format( 'l' )
		);
	} else {
		$length_line = $occurrence->format( 'l' );
	}
}
?>
<li class="eswp-event">
	<div class="eswp-event__date" aria-hidden="true">
		<div class="eswp-event__date-frame">
			<span class="eswp-event__day"><?php echo esc_html( $occurrence ? $occurrence->format( 'd' ) : '' ); ?></span>
			<span class="eswp-event__month"><?php echo esc_html( $occurrence ? $occurrence->format( 'M' ) : '' ); ?></span>
		</div>
	</div>
	<div class="eswp-event__body">
		<h3 class="eswp-event__title">
			<?php if ( $permalink ) : ?>
				<a href="<?php echo esc_url( $permalink ); ?>"<?php echo $link_target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo esc_html( $event->title ); ?></a>
			<?php else : ?>
				<?php echo esc_html( $event->title ); ?>
			<?php endif; ?>
		</h3>
		<?php if ( '' !== $when_line ) : ?>
			<p class="eswp-event__when">
				<span class="eswp-event__when-icon" aria-hidden="true"></span>
				<span><?php echo esc_html( $when_line ); ?></span>
			</p>
		<?php endif; ?>
		<?php if ( '' !== $length_line ) : ?>
			<p class="eswp-event__duration">
				<span class="eswp-event__duration-icon" aria-hidden="true"></span>
				<span><?php echo esc_html( $length_line ); ?></span>
			</p>
		<?php endif; ?>
		<?php if ( $show_location && '' !== $event->venue_name ) : ?>
			<p class="eswp-event__meta"><?php echo esc_html( $event->venue_name ); ?></p>
		<?php endif; ?>
		<?php if ( $show_category && '' !== $event->category_name ) : ?>
			<p class="eswp-event__category"><?php echo esc_html( $event->category_name ); ?></p>
		<?php endif; ?>
		<?php if ( $show_ceu && null !== $event->ceu_credits ) : ?>
			<p class="eswp-event__ceu">
				<?php
				printf(
					/* translators: %s: CEU credit amount */
					esc_html__( 'CEU: %s', 'events-apptoolstack-com' ),
					esc_html( (string) $event->ceu_credits )
				);
				?>
			</p>
		<?php endif; ?>
	</div>
</li>
