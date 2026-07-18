<?php
/**
 * Icon List block — server-side render.
 *
 * CSS is generated at save time by Icon_List_CSS and printed inline on the front
 * end. This file outputs the list: one row per item, each with its icon (inline
 * sanitised SVG or an uploaded <img>) and its text, laid out with the icon
 * before or after the text; a row becomes a link when the item has a URL. The
 * list-vs-grid layout and the icon view/shape are wrapper modifier classes the
 * CSS generator + style.scss target.
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

$icon_before = 'after' !== ( $attributes['iconPosition'] ?? 'before' );

// Inline formatting allowed inside an item's text.
$text_allowed = [
	'strong' => [],
	'em'     => [],
	'b'      => [],
	'i'      => [],
	'br'     => [],
	'span'   => [],
];

// Build the list rows.
$items_html = '';
foreach ( $items as $index => $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}

	$icon_svg = HTML_Helpers::icon_html( $item['icon'] ?? [] );
	$text     = wp_kses( (string) ( $item['text'] ?? '' ), $text_allowed );

	// Skip a row that has neither an icon nor any text.
	if ( '' === $icon_svg && '' === trim( wp_strip_all_tags( $text ) ) ) {
		continue;
	}

	$icon_html = '' !== $icon_svg ? '<span class="flexa-icon-list__icon">' . $icon_svg . '</span>' : '';
	$text_html = '' !== trim( wp_strip_all_tags( $text ) ) ? '<span class="flexa-icon-list__text">' . $text . '</span>' : '';

	$inner = $icon_before ? $icon_html . $text_html : $text_html . $icon_html;

	$link = is_array( $item['link'] ?? null ) ? $item['link'] : [];
	$url  = (string) ( $link['url'] ?? '' );

	// Front-end inline-editor hook: map this rendered row back to its source
	// array index (empty rows are skipped, so the original key is required).
	$item_attr = \Flexa\Block\Inline_Editor::item_attr( (int) $index );

	if ( '' !== $url ) {
		$row_attrs = 'class="flexa-icon-list__item" href="' . esc_url( $url ) . '"';
		if ( '_blank' === ( $link['target'] ?? '' ) ) {
			$rel = trim( (string) ( $link['rel'] ?? '' ) );
			if ( '' === $rel ) {
				$rel = 'noopener noreferrer';
			}
			$rel        = trim( (string) preg_replace( '/[^a-z0-9 _-]/i', '', $rel ) );
			$row_attrs .= ' target="_blank" rel="' . esc_attr( $rel ) . '"';
		}
		$items_html .= '<a ' . $row_attrs . $item_attr . '>' . $inner . '</a>';
	} else {
		$items_html .= '<div class="flexa-icon-list__item"' . $item_attr . '>' . $inner . '</div>';
	}
}

if ( '' === $items_html ) {
	return;
}

// Wrapper classes + attributes.
$view       = 'grid' === ( $attributes['view'] ?? 'list' ) ? 'grid' : 'list';
$icon_view  = in_array( $attributes['iconView'] ?? 'default', [ 'default', 'stacked', 'framed' ], true ) ? $attributes['iconView'] : 'default';
$icon_shape = in_array( $attributes['iconShape'] ?? 'square', [ 'square', 'rounded', 'circle' ], true ) ? $attributes['iconShape'] : 'square';

$classes = [
	'flexa-icon-list',
	'flexa-icon-list--' . $view,
	'flexa-icon-list--icon-' . $icon_view,
	'flexa-icon-list--shape-' . $icon_shape,
];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-icon-list-' . sanitize_html_class( $block_id );
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
	'<%1$s %2$s%3$s%4$s><div class="flexa-icon-list__list">%5$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$items_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text wp_kses'd, icon svg_kses'd, url/rel escaped above.
);
