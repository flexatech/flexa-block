<?php
/**
 * CTA block — server-side render.
 *
 * CSS is generated at save time by Cta_CSS and printed inline on the front end.
 * This file outputs the CTA wrapper (centred or split) and the shared promo
 * content (heading, description, CTA buttons). All text is escaped (wp_kses with
 * a small inline whitelist) in HTML_Helpers::promo_content_html; the CTA inherits
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

$block_id       = $attributes['blockId'] ?? '';
$arrangement    = 'split' === ( $attributes['arrangement'] ?? 'centered' ) ? 'split' : 'centered';
$container_type = 'full-width' === ( $attributes['containerType'] ?? 'boxed' ) ? 'full-width' : 'boxed';
$anchor         = $attributes['anchor'] ?? '';
$html_tag       = HTML_Helpers::get_html_tag( $attributes, 'section' );

$content_html = HTML_Helpers::promo_content_html( $attributes );
if ( '' === $content_html ) {
	return;
}

$classes = [ 'flexa-cta', 'flexa-cta--' . $arrangement, 'flexa-cta--' . sanitize_html_class( $container_type ) ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-cta-' . sanitize_html_class( $block_id );
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
	'<%1$s %2$s%3$s%4$s>%5$s</%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$content_html        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text wp_kses'd, button url/rel escaped in helper.
);
