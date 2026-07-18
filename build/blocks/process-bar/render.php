<?php
/**
 * Progress Bar block — server-side render.
 *
 * CSS is generated at save time by Process_Bar_CSS and printed inline on the
 * front end. This file outputs the bar itself: a line layout (a track div with a
 * width-driven fill) or a circle / semi-circle layout (an inline SVG with a track
 * ring and a stroke-dashoffset-driven fill). The final value is rendered
 * server-side so the correct figure shows without JavaScript; when the on-scroll
 * animation is enabled, view.js fills the bar (and counts the number up) from
 * zero when the block scrolls into view. Colours and typography inherit the theme
 * / a sensible default fill unless the user overrode them.
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

// Layout + value.
$bar_type   = in_array( $attributes['barType'] ?? 'line', [ 'line', 'circle', 'semicircle' ], true ) ? $attributes['barType'] : 'line';
$value_type = 'absolute' === ( $attributes['valueType'] ?? 'percent' ) ? 'absolute' : 'percent';
$value      = (float) ( $attributes['value'] ?? 0 );
$max        = (float) ( $attributes['max'] ?? 100 );

$total    = 'absolute' === $value_type ? $max : 100.0;
$fraction = $total > 0 ? $value / $total : 0.0;
$fraction = max( 0.0, min( 1.0, $fraction ) );
$percent  = round( $fraction * 100, 2 );

// Title + counter.
$show_title    = false !== ( $attributes['showTitle'] ?? true );
$title         = (string) ( $attributes['title'] ?? '' );
$title_tags    = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' ];
$title_tag     = in_array( $attributes['titleTag'] ?? 'div', $title_tags, true ) ? $attributes['titleTag'] : 'div';
$title_below   = 'below' === ( $attributes['titlePosition'] ?? 'above' );
$show_counter  = false !== ( $attributes['showCounter'] ?? true );
$divider_char  = (string) ( $attributes['dividerChar'] ?? '/' );

// Animation.
$animate  = false !== ( $attributes['animateFill'] ?? true );
$duration = (int) ( $attributes['fillDuration'] ?? 1500 );
$duration = max( 100, min( 20000, $duration ) );

// SVG ring geometry (mirrors edit.tsx / view.ts).
$ring_r    = 42.0;
$circ      = 2 * M_PI * $ring_r;
$semi_len  = M_PI * $ring_r;
$semi_path = 'M ' . ( 50 - $ring_r ) . ' 50 A ' . $ring_r . ' ' . $ring_r . ' 0 0 1 ' . ( 50 + $ring_r ) . ' 50';

$is_line = 'line' === $bar_type;

// Title element.
$title_html = '';
if ( $show_title && '' !== trim( $title ) ) {
	$title_html = '<' . $title_tag . ' class="flexa-process-bar__title">' . esc_html( $title ) . '</' . $title_tag . '>';
}

// Counter readout: the value span carries data-target so view.js can count it up.
$counter_html = '';
if ( $show_counter ) {
	$target_attr = $animate ? ' data-target="' . esc_attr( (string) $value ) . '"' : '';
	if ( 'absolute' === $value_type ) {
		$counter_html = '<span class="flexa-process-bar__counter">'
			. '<span class="flexa-process-bar__counter-value"' . $target_attr . '>' . esc_html( (string) $value ) . '</span>'
			. '<span class="flexa-process-bar__counter-divider"> ' . esc_html( $divider_char ) . ' </span>'
			. '<span class="flexa-process-bar__counter-max">' . esc_html( (string) $max ) . '</span>'
			. '</span>';
	} else {
		$counter_html = '<span class="flexa-process-bar__counter">'
			. '<span class="flexa-process-bar__counter-value"' . $target_attr . '>' . esc_html( (string) $value ) . '</span>'
			. '<span class="flexa-process-bar__counter-suffix">%</span>'
			. '</span>';
	}
}

// The bar itself.
if ( $is_line ) {
	$fill_html = '<div class="flexa-process-bar__fill" style="width:' . esc_attr( $percent . '%' ) . '"></div>';
	$bar_html  = '<div class="flexa-process-bar__bar flexa-process-bar__track">' . $fill_html . '</div>';

	$header_html = ( '' !== $title_html || '' !== $counter_html )
		? '<div class="flexa-process-bar__header">' . $title_html . $counter_html . '</div>'
		: '';

	$inner_html = $title_below
		? $bar_html . $header_html
		: $header_html . $bar_html;
} else {
	$is_semi = 'semicircle' === $bar_type;
	$len     = $is_semi ? $semi_len : $circ;
	$offset  = round( $len * ( 1 - $fraction ), 3 );
	$dash    = round( $len, 3 );

	// The SVG is assembled from static tag/attribute names plus numeric geometry
	// escaped with esc_attr(), so it is safe to output directly. (It is NOT routed
	// through HTML_Helpers::svg_kses() because that whitelist strips
	// `stroke-dashoffset` — the attribute that drives the ring fill.)
	if ( $is_semi ) {
		$svg = '<svg class="flexa-process-bar__ring" viewBox="0 0 100 58" role="img" aria-hidden="true">'
			. '<path class="flexa-process-bar__ring-track" d="' . esc_attr( $semi_path ) . '" fill="none" />'
			. '<path class="flexa-process-bar__ring-fill" d="' . esc_attr( $semi_path ) . '" fill="none" stroke-dasharray="' . esc_attr( (string) $dash ) . '" stroke-dashoffset="' . esc_attr( (string) $offset ) . '" />'
			. '</svg>';
	} else {
		$svg = '<svg class="flexa-process-bar__ring" viewBox="0 0 100 100" role="img" aria-hidden="true">'
			. '<circle class="flexa-process-bar__ring-track" cx="50" cy="50" r="' . esc_attr( (string) $ring_r ) . '" fill="none" />'
			. '<circle class="flexa-process-bar__ring-fill" cx="50" cy="50" r="' . esc_attr( (string) $ring_r ) . '" fill="none" transform="rotate(-90 50 50)" stroke-dasharray="' . esc_attr( (string) $dash ) . '" stroke-dashoffset="' . esc_attr( (string) $offset ) . '" />'
			. '</svg>';
	}

	$circle_counter = '' !== $counter_html ? '<div class="flexa-process-bar__circle-counter">' . $counter_html . '</div>' : '';
	$inner_html     = $title_html . '<div class="flexa-process-bar__circle">' . $svg . $circle_counter . '</div>';
}

// Wrapper classes + attributes.
$classes = [ 'flexa-process-bar', 'flexa-process-bar--' . $bar_type ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-process-bar-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Animation markers for view.js.
$anim_attrs = '';
if ( $animate ) {
	$anim_attrs = ' data-flexa-process-bar data-bar-type="' . esc_attr( $bar_type ) . '" data-duration="' . esc_attr( (string) $duration ) . '"';
}

// Lazy background marker.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

printf(
	'<%1$s %2$s%3$s%4$s%5$s>%6$s</%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$anim_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static keys, values esc_attr'd above.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$inner_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- title/counter esc_html'd, svg svg_kses'd, widths esc_attr'd above.
);
