<?php
/**
 * Steps block — server-side render.
 *
 * CSS is generated at save time by Steps_CSS and printed inline on the front
 * end. This file outputs the vertical timeline: one item per step, each with its
 * marker (an auto-incrementing number, an icon or an image), a connecting line
 * to the next step, a title and a description. Titles and descriptions inherit
 * the theme's typography unless the user overrode it.
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

$global_type    = in_array( $attributes['markerType'] ?? 'number', [ 'number', 'icon', 'image' ], true ) ? $attributes['markerType'] : 'number';
$marker_shape   = in_array( $attributes['markerShape'] ?? 'circle', [ 'circle', 'square', 'rounded' ], true ) ? $attributes['markerShape'] : 'circle';
$connector_show = false !== ( $attributes['connectorShow'] ?? true );

// Inline formatting allowed inside the title.
$title_allowed = [
	'strong' => [],
	'em'     => [],
	'b'      => [],
	'i'      => [],
	'br'     => [],
	'span'   => [],
];

// Build the step items.
$items_html = '';
$position   = 0;
$rows       = array_values( $items );
$total      = count( $rows );

foreach ( $rows as $index => $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}

	$title = wp_kses( (string) ( $item['title'] ?? '' ), $title_allowed );
	$desc  = wp_kses( (string) ( $item['description'] ?? '' ), $title_allowed );

	if ( '' === trim( wp_strip_all_tags( $title ) ) && '' === trim( wp_strip_all_tags( $desc ) ) ) {
		continue;
	}

	++$position;

	$status = in_array( $item['status'] ?? '', [ 'done', 'active', 'upcoming' ], true ) ? $item['status'] : '';

	// Effective marker type: a per-step override, else the block default.
	$type = in_array( $item['markerType'] ?? '', [ 'number', 'icon', 'image' ], true ) ? $item['markerType'] : $global_type;

	// Marker inner content.
	$marker_inner = '';
	if ( 'icon' === $type ) {
		$icon_cfg = is_array( $item['icon'] ?? null ) ? $item['icon'] : [];
		$source   = (string) ( $icon_cfg['source'] ?? 'none' );
		$markup   = (string) ( $icon_cfg['markup'] ?? '' );
		$icon_url = (string) ( $icon_cfg['url'] ?? '' );
		if ( 'upload' === $source && '' !== $icon_url ) {
			$marker_inner = '<img src="' . esc_url( $icon_url ) . '" alt="" width="24" height="24" loading="lazy" />';
		} elseif ( 'none' !== $source && '' !== $markup ) {
			$marker_inner = HTML_Helpers::svg_kses( $markup );
		}
	} elseif ( 'image' === $type ) {
		$image_url = is_array( $item['image'] ?? null ) ? (string) ( $item['image']['url'] ?? '' ) : '';
		if ( '' !== $image_url ) {
			$marker_inner = '<img src="' . esc_url( $image_url ) . '" alt="" width="24" height="24" loading="lazy" />';
		}
	}
	// Fall back to the position number when no icon/image resolved.
	if ( '' === $marker_inner ) {
		$marker_inner = esc_html( (string) $position );
	}

	$item_class = 'flexa-steps__item' . ( '' !== $status ? ' is-' . $status : '' );
	$is_last    = ( $index === $total - 1 );

	$connector_html = ( $connector_show && ! $is_last )
		? '<span class="flexa-steps__connector" aria-hidden="true"></span>'
		: '';

	$title_html = '' !== trim( wp_strip_all_tags( $title ) ) ? '<span class="flexa-steps__title">' . $title . '</span>' : '';
	$desc_html  = '' !== trim( wp_strip_all_tags( $desc ) ) ? '<span class="flexa-steps__description">' . $desc . '</span>' : '';

	$items_html .= '<div class="' . esc_attr( $item_class ) . '"' . \Flexa\Block\Inline_Editor::item_attr( $index ) . '>';
	$items_html .= '<div class="flexa-steps__aside"><span class="flexa-steps__marker">' . $marker_inner . '</span>' . $connector_html . '</div>';
	$items_html .= '<div class="flexa-steps__content">' . $title_html . $desc_html . '</div>';
	$items_html .= '</div>';
}

if ( '' === $items_html ) {
	return;
}

// Wrapper classes + attributes.
$classes = [ 'flexa-steps', 'flexa-steps--marker-' . sanitize_html_class( $marker_shape ) ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-steps-' . sanitize_html_class( $block_id );
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
	'<%1$s %2$s%3$s%4$s><div class="flexa-steps__list">%5$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$items_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- title/desc wp_kses'd, marker esc_html/svg_kses/esc_url'd above.
);
