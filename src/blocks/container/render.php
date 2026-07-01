<?php
/**
 * Container block — server-side render.
 *
 * CSS is generated at save time by Container_CSS and printed inline on the front end.
 * This file only outputs the HTML wrapper around InnerBlocks content.
 *
 * @package Flexa\Block
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    InnerBlocks content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $attributes, $content provided by WP block API.

use Flexa\Block\HTML_Helpers;

$block_id       = $attributes['blockId'] ?? '';
$container_type = $attributes['containerType'] ?? 'boxed';
$anchor         = $attributes['anchor'] ?? '';
$is_boxed       = 'boxed' === $container_type;

$html_tag = HTML_Helpers::get_html_tag( $attributes );

$classes = [
	'flexa-container',
	'flexa-container--' . sanitize_html_class( $container_type ),
];

if ( '' !== $block_id ) {
	$classes[] = 'flexa-container-' . sanitize_html_class( $block_id );
}

$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}

$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Lazy background: mark the *styled* element (inner div when boxed, outer wrapper
// when full-width) so view.js can reveal the image once it nears the viewport.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

if ( $is_boxed ) {
	printf(
		'<%1$s %2$s%3$s><div class="flexa-container__inner"%4$s>%5$s</div></%1$s>',
		esc_html( $html_tag ),
		$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
		$data_attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
		$lazy_marker, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
		$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- InnerBlocks content already rendered/escaped by WP.
	);
} else {
	printf(
		'<%1$s %2$s%3$s%4$s>%5$s</%1$s>',
		esc_html( $html_tag ),
		$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$data_attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$lazy_marker, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
		$content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}
