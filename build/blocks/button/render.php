<?php
/**
 * Button block — server-side render.
 *
 * CSS is generated at save time by Button_CSS and printed inline on the front
 * end. This file outputs the wrapper + the anchor styled as a button; the label
 * inherits the theme's `wp-element-button` look unless the user overrode it.
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

$block_id   = $attributes['blockId'] ?? '';
$text       = (string) ( $attributes['text'] ?? '' );
$url        = (string) ( $attributes['url'] ?? '' );
$new_tab    = '_blank' === ( $attributes['linkTarget'] ?? '' );
$rel        = trim( (string) ( $attributes['rel'] ?? '' ) );
$variant    = $attributes['variant'] ?? 'fill';
$size       = $attributes['sizePreset'] ?? 'md';
$align      = $attributes['align'] ?? 'left';
$full_width = ! empty( $attributes['fullWidth'] );
$anchor     = $attributes['anchor'] ?? '';

$icon_cfg    = is_array( $attributes['icon'] ?? null ) ? $attributes['icon'] : [];
$icon_source = (string) ( $icon_cfg['source'] ?? 'none' );
$icon_pos    = 'before' === ( $icon_cfg['position'] ?? 'after' ) ? 'before' : 'after';

// Whitelist enum-ish attributes so only known class suffixes reach the markup.
$variant = in_array( $variant, [ 'fill', 'outline', 'ghost' ], true ) ? $variant : 'fill';
$size    = in_array( $size, [ 'sm', 'md', 'lg' ], true ) ? $size : 'md';
$align   = in_array( $align, [ 'left', 'center', 'right' ], true ) ? $align : 'left';

$classes = [
	'flexa-button',
	'flexa-button--' . $variant,
	'flexa-button--' . $size,
];
if ( 'left' !== $align ) {
	$classes[] = 'flexa-button--' . $align;
}
if ( $full_width ) {
	$classes[] = 'flexa-button--full';
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-button-' . sanitize_html_class( $block_id );
}

$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Build the anchor's attribute string with escaped values.
$link_attrs = 'class="wp-element-button flexa-button__link"';
if ( '' !== $url ) {
	$link_attrs .= ' href="' . esc_url( $url ) . '"';
}
if ( $new_tab ) {
	$link_attrs .= ' target="_blank"';
	if ( '' === $rel ) {
		$rel = 'noopener noreferrer';
	}
}
if ( '' !== $rel ) {
	$rel        = trim( (string) preg_replace( '/[^a-z0-9 _-]/i', '', $rel ) );
	$link_attrs .= ' rel="' . esc_attr( $rel ) . '"';
}

// Resolve the icon to inline SVG (builtin/library) or an <img> (uploaded SVG).
$icon_svg = '';
if ( 'upload' === $icon_source && '' !== (string) ( $icon_cfg['url'] ?? '' ) ) {
	$icon_svg = '<img class="flexa-icon" src="' . esc_url( $icon_cfg['url'] ) . '" alt="" width="20" height="20" loading="lazy" />';
} elseif ( 'none' !== $icon_source && '' !== (string) ( $icon_cfg['markup'] ?? '' ) ) {
	$icon_svg = HTML_Helpers::svg_kses( $icon_cfg['markup'] );
}

$label     = wp_kses( $text, [ 'strong' => [], 'em' => [], 'b' => [], 'i' => [], 'br' => [], 'span' => [] ] );
$text_html = '<span class="flexa-button__text">' . $label . '</span>';

$inner = '';
if ( '' !== $icon_svg && 'before' === $icon_pos ) {
	$inner .= $icon_svg;
}
$inner .= $text_html;
if ( '' !== $icon_svg && 'after' === $icon_pos ) {
	$inner .= $icon_svg;
}

printf(
	'<div %1$s%2$s><a %3$s>%4$s</a></div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$link_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values escaped via esc_url/esc_attr above.
	$inner               // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG literal + wp_kses'd label.
);
