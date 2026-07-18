<?php
/**
 * Text block — server-side render.
 *
 * CSS is generated at save time by Text_CSS and printed inline on the front end.
 * This file outputs the wrapper plus the chosen text element; the text inherits
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
$text     = (string) ( $attributes['content'] ?? '' );
$anchor   = $attributes['anchor'] ?? '';
$drop_cap = ! empty( $attributes['dropCap'] );

// Whitelist the text element tag.
$text_tags = [ 'p', 'div', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
$tag       = in_array( $attributes['htmlTag'] ?? 'p', $text_tags, true ) ? $attributes['htmlTag'] : 'p';

// Inline formatting allowed inside the text.
$inline_allowed = [
	'strong' => [],
	'em'     => [],
	'b'      => [],
	'i'      => [],
	'a'      => [ 'href' => [], 'target' => [], 'rel' => [] ],
	'br'     => [],
	'span'   => [ 'style' => true, 'class' => true ],
	'mark'   => [ 'style' => true, 'class' => true ],
	'sub'    => [],
	'sup'    => [],
];

$content_classes = 'flexa-text__content' . ( $drop_cap ? ' has-drop-cap' : '' );
$text_html       = '<' . $tag . ' class="' . esc_attr( $content_classes ) . '">' . wp_kses( $text, $inline_allowed ) . '</' . $tag . '>';

$classes = [ 'flexa-text' ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-text-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Lazy background: mark the wrapper so view.js reveals the image near the viewport.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

printf(
	'<div %1$s%2$s%3$s>%4$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$text_html           // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag whitelisted, text wp_kses'd above.
);
