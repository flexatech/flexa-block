<?php
/**
 * Banner block — server-side render.
 *
 * CSS is generated at save time by Banner_CSS and printed inline on the front
 * end. This file outputs the banner wrapper, an optional colour/gradient overlay
 * and the shared promo content (heading, description, CTA buttons). All text is
 * escaped (wp_kses with a small inline whitelist) in HTML_Helpers::promo_content_html;
 * the banner inherits the theme's typography unless the user overrode it.
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
$container_type = 'full-width' === ( $attributes['containerType'] ?? 'full-width' ) ? 'full-width' : 'boxed';
$anchor         = $attributes['anchor'] ?? '';
$html_tag       = HTML_Helpers::get_html_tag( $attributes, 'section' );

$content_html = HTML_Helpers::promo_content_html( $attributes );
if ( '' === $content_html ) {
	return;
}

// Optional overlay layer (styling emitted by the generator).
$overlay      = $attributes['overlay'] ?? [];
$overlay_type = $overlay['type'] ?? 'none';
$overlay_html = in_array( $overlay_type, [ 'color', 'gradient' ], true )
	? '<div class="flexa-banner__overlay" aria-hidden="true"></div>'
	: '';

$classes = [ 'flexa-banner', 'flexa-banner--' . sanitize_html_class( $container_type ) ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-banner-' . sanitize_html_class( $block_id );
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

// The content sits inside a centred box wrapper. With no `contentBoxWidth` the box
// is width:100% (spans the banner) so it changes nothing; set a max-width and it
// centres to that width — a full-bleed background with content on the site grid,
// while `contentMaxWidth` + `contentAlign` still position the column inside it.
$content_box = '<div class="flexa-banner__box">' . $content_html . '</div>';

printf(
	'<%1$s %2$s%3$s%4$s>%5$s%6$s</%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$overlay_html,       // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$content_box         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text wp_kses'd, button url/rel escaped in helper.
);
