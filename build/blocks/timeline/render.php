<?php
/**
 * Timeline block — server-side render.
 *
 * CSS is generated at save time by Timeline_CSS and printed inline on the front
 * end. This file outputs the chronological timeline: one item per event, each
 * with its marker (an icon, else an empty node), a connecting line to the next
 * event, a date, a title and a description. In the "alternate" layout entries
 * zig-zag left/right of the line; in "left"/"right" they sit on a single side.
 * Dates, titles and descriptions inherit the theme's typography unless the user
 * overrode it.
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

$items = $attributes['items'] ?? [];
if ( ! is_array( $items ) || empty( $items ) ) {
	return;
}

$layout         = in_array( $attributes['timelineLayout'] ?? 'alternate', [ 'alternate', 'left', 'right' ], true ) ? $attributes['timelineLayout'] : 'alternate';
$marker_shape   = in_array( $attributes['markerShape'] ?? 'circle', [ 'circle', 'square', 'rounded' ], true ) ? $attributes['markerShape'] : 'circle';
$date_position  = in_array( $attributes['datePosition'] ?? 'above', [ 'inline', 'above' ], true ) ? $attributes['datePosition'] : 'above';
$image_position = in_array( $attributes['imagePosition'] ?? 'top', [ 'top', 'bottom' ], true ) ? $attributes['imagePosition'] : 'top';
$connector_show = false !== ( $attributes['connectorShow'] ?? true );

// Inline formatting allowed inside the title / date.
$title_allowed = [
	'strong' => [],
	'em'     => [],
	'b'      => [],
	'i'      => [],
	'br'     => [],
	'span'   => [],
];

// Build the timeline items.
$items_html = '';
$rows       = array_values( $items );
$total      = count( $rows );
$position   = 0;

foreach ( $rows as $index => $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}

	$date  = wp_kses( (string) ( $item['date'] ?? '' ), $title_allowed );
	$title = wp_kses( (string) ( $item['title'] ?? '' ), $title_allowed );
	$desc  = wp_kses( (string) ( $item['description'] ?? '' ), $title_allowed );

	$has_image = '' !== (string) ( ( is_array( $item['image'] ?? null ) ? $item['image']['url'] ?? '' : '' ) );
	if ( '' === trim( wp_strip_all_tags( $title ) ) && '' === trim( wp_strip_all_tags( $desc ) ) && '' === trim( wp_strip_all_tags( $date ) ) && ! $has_image ) {
		continue;
	}

	// Marker inner content: an icon (uploaded image or inline SVG), else empty.
	$marker_inner = '';
	$icon_cfg     = is_array( $item['icon'] ?? null ) ? $item['icon'] : [];
	$source       = (string) ( $icon_cfg['source'] ?? 'none' );
	$markup       = (string) ( $icon_cfg['markup'] ?? '' );
	$icon_url     = (string) ( $icon_cfg['url'] ?? '' );
	if ( 'upload' === $source && '' !== $icon_url ) {
		$marker_inner = '<img src="' . esc_url( $icon_url ) . '" alt="" width="24" height="24" loading="lazy" />';
	} elseif ( 'none' !== $source && '' !== $markup ) {
		$marker_inner = HTML_Helpers::svg_kses( $markup );
	}

	// Side placement: alternate zig-zags, otherwise a fixed single side.
	$side = 'alternate' === $layout ? ( 0 === $position % 2 ? 'left' : 'right' ) : $layout;
	++$position;

	$item_class = 'flexa-timeline__item is-' . sanitize_html_class( $side );
	$is_last    = ( $index === $total - 1 );

	$connector_html = ( $connector_show && ! $is_last )
		? '<span class="flexa-timeline__connector" aria-hidden="true"></span>'
		: '';

	$date_html  = '' !== trim( wp_strip_all_tags( $date ) ) ? '<span class="flexa-timeline__date">' . $date . '</span>' : '';
	$title_html = '' !== trim( wp_strip_all_tags( $title ) ) ? '<span class="flexa-timeline__title">' . $title . '</span>' : '';
	$desc_html  = '' !== trim( wp_strip_all_tags( $desc ) ) ? '<span class="flexa-timeline__description">' . $desc . '</span>' : '';

	// Optional per-entry image, placed above or below the text per imagePosition.
	$image_html = '';
	$image_cfg  = is_array( $item['image'] ?? null ) ? $item['image'] : [];
	$image_url  = (string) ( $image_cfg['url'] ?? '' );
	if ( '' !== $image_url ) {
		$image_alt  = esc_attr( (string) ( $image_cfg['alt'] ?? '' ) );
		$image_html = '<img class="flexa-timeline__image flexa-timeline__image--' . esc_attr( $image_position ) . '" src="' . esc_url( $image_url ) . '" alt="' . $image_alt . '" loading="lazy" />';
	}

	$content_html = 'top' === $image_position
		? $image_html . $date_html . $title_html . $desc_html
		: $date_html . $title_html . $desc_html . $image_html;

	$items_html .= '<div class="' . esc_attr( $item_class ) . '"' . \Flexa\Block\Inline_Editor::item_attr( $index ) . '>';
	$items_html .= '<div class="flexa-timeline__aside"><span class="flexa-timeline__marker">' . $marker_inner . '</span>' . $connector_html . '</div>';
	$items_html .= '<div class="flexa-timeline__content"><div class="flexa-timeline__card">' . $content_html . '</div></div>';
	$items_html .= '</div>';
}

if ( '' === $items_html ) {
	return;
}

// Wrapper classes + attributes.
$classes = [
	'flexa-timeline',
	'flexa-timeline--' . sanitize_html_class( $layout ),
	'flexa-timeline--marker-' . sanitize_html_class( $marker_shape ),
	'flexa-timeline--date-' . sanitize_html_class( $date_position ),
];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-timeline-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Lazy background marker.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

printf(
	'<%1$s %2$s%3$s%4$s><div class="flexa-timeline__list">%5$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$items_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- date/title/desc wp_kses'd, marker svg_kses/esc_url'd above.
);
