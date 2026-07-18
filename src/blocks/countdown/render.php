<?php
/**
 * Countdown block — server-side render.
 *
 * CSS is generated at save time by Countdown_CSS and printed inline on the front
 * end. This file outputs the timer markup with server-computed initial digits
 * (so there is no flash of "00" before view.js starts ticking) plus the data
 * attributes the front-end script needs. The digits inherit the theme's
 * typography unless the user overrode it.
 *
 * @package Flexa\Block
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Save content (unused — dynamic block).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $attributes provided by WP block API.

use Flexa\Block\HTML_Helpers;

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';
$html_tag = HTML_Helpers::get_html_tag( $attributes );

$end = (string) ( $attributes['endDate'] ?? '' );

// Interpret the stored wall-clock date in the chosen UTC offset (block setting,
// else the site's gmt_offset) so the target resolves to the same instant for
// every visitor regardless of their local timezone.
$tz_attr     = (string) ( $attributes['timezone'] ?? '' );
$offset_hrs  = '' !== $tz_attr ? (float) $tz_attr : (float) get_option( 'gmt_offset', 0 );
$target_ts   = false;
if ( '' !== $end ) {
	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?$/', $end, $m ) ) {
		$utc       = gmmktime( (int) $m[4], (int) $m[5], isset( $m[6] ) ? (int) $m[6] : 0, (int) $m[2], (int) $m[3], (int) $m[1] );
		$target_ts = $utc - (int) round( $offset_hrs * 3600 );
	} else {
		$target_ts = strtotime( $end );
	}
}
$remaining = ( false !== $target_ts ) ? max( 0, $target_ts - time() ) : 0;

$expired        = $attributes['expiredAction'] ?? [];
$expired_type   = in_array( $expired['type'] ?? 'hide', [ 'hide', 'zero', 'message' ], true ) ? $expired['type'] : 'hide';
$expired_msg    = (string) ( $expired['message'] ?? '' );
$is_expired_now = ( false !== $target_ts ) && $remaining <= 0;

// Hand the front-end script a fully-resolved UTC instant so it can parse it
// unambiguously (no wall-clock/timezone guessing on the client).
$data_end = ( false !== $target_ts ) ? gmdate( 'c', $target_ts ) : '';

// An already-finished countdown set to "hide" outputs nothing at all.
if ( $is_expired_now && 'hide' === $expired_type ) {
	return;
}

// Which units to show + their labels.
$units      = [ 'days', 'hours', 'minutes', 'seconds' ];
$unit_flags = [
	'days'    => false !== ( $attributes['showDays'] ?? true ),
	'hours'   => false !== ( $attributes['showHours'] ?? true ),
	'minutes' => false !== ( $attributes['showMinutes'] ?? true ),
	'seconds' => false !== ( $attributes['showSeconds'] ?? true ),
];
$active = array_values( array_filter( $units, static fn( $u ) => $unit_flags[ $u ] ) );

// Roll a remaining duration into the visible units (hidden higher units roll
// into the next visible one) — mirrors shared.ts remainingParts().
$unit_seconds = [ 'days' => 86400, 'hours' => 3600, 'minutes' => 60, 'seconds' => 1 ];
$parts        = [ 'days' => 0, 'hours' => 0, 'minutes' => 0, 'seconds' => 0 ];
$rem          = $remaining;
foreach ( $units as $u ) {
	if ( ! $unit_flags[ $u ] ) {
		continue;
	}
	if ( 'seconds' === $u ) {
		$parts['seconds'] = $rem;
	} else {
		$parts[ $u ] = (int) floor( $rem / $unit_seconds[ $u ] );
		$rem        -= $parts[ $u ] * $unit_seconds[ $u ];
	}
}

// Labels.
$labels       = $attributes['labels'] ?? [];
$show_labels  = false !== ( $labels['show'] ?? true );
$labels_below = 'above' !== ( $labels['position'] ?? 'below' );

// Separator character.
$separator = $attributes['separator'] ?? [];
$sep_type  = $separator['type'] ?? 'none';
$sep_char  = '';
if ( 'colon' === $sep_type ) {
	$sep_char = ':';
} elseif ( 'dash' === $sep_type ) {
	$sep_char = '-';
} elseif ( 'custom' === $sep_type ) {
	$sep_char = (string) ( $separator['custom'] ?? '' );
}

// Build the timer inner markup.
$timer_inner = '';
$count       = count( $active );
foreach ( $active as $index => $unit ) {
	$digit = sprintf(
		'<span class="flexa-countdown__digit" data-unit="%1$s">%2$s</span>',
		esc_attr( $unit ),
		esc_html( str_pad( (string) max( 0, $parts[ $unit ] ), 2, '0', STR_PAD_LEFT ) )
	);

	$label = '';
	if ( $show_labels ) {
		$label = '<span class="flexa-countdown__label">' . esc_html( (string) ( $labels[ $unit ] ?? '' ) ) . '</span>';
	}

	$unit_html  = '<div class="flexa-countdown__unit flexa-countdown__unit--' . esc_attr( $unit ) . '">';
	$unit_html .= $labels_below ? $digit . $label : $label . $digit;
	$unit_html .= '</div>';

	$timer_inner .= '<div class="flexa-countdown__group">' . $unit_html;
	if ( '' !== $sep_char && $index < $count - 1 ) {
		$timer_inner .= '<span class="flexa-countdown__separator" aria-hidden="true">' . esc_html( $sep_char ) . '</span>';
	}
	$timer_inner .= '</div>';
}

// Wrapper classes + attributes.
$classes = [ 'flexa-countdown' ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-countdown-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Countdown data attributes for view.js.
$aria_live = in_array( $attributes['ariaLive'] ?? 'off', [ 'off', 'polite', 'assertive' ], true ) ? $attributes['ariaLive'] : 'off';
$cd_attrs  = ' data-flexa-countdown data-end="' . esc_attr( $data_end ) . '" data-expired-action="' . esc_attr( $expired_type ) . '"';
if ( 'message' === $expired_type ) {
	$cd_attrs .= ' data-expired-message="' . esc_attr( $expired_msg ) . '"';
}
if ( 'off' !== $aria_live ) {
	$cd_attrs .= ' aria-live="' . esc_attr( $aria_live ) . '"';
}

// Lazy background marker.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

// If already finished and set to "message", show the message instead of the timer.
if ( $is_expired_now && 'message' === $expired_type ) {
	$inner = '<div class="flexa-countdown__message">' . esc_html( $expired_msg ) . '</div>';
} else {
	$inner = '<div class="flexa-countdown__timer">' . $timer_inner . '</div>';
}

printf(
	'<%1$s %2$s%3$s%4$s%5$s>%6$s</%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$cd_attrs,           // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static keys, values esc_attr'd above.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$inner               // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tags static, text esc_html'd above.
);
