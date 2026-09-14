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
 * @var string   $content    InnerBlocks content — optional top region (breadcrumb / eyebrow / meta).
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

/*
 * Where the content comes from. `fields` is the default, so a banner saved before
 * this option existed takes exactly the path it always took — the fixed promo
 * fields plus the narrow InnerBlocks strip above them. `custom` drops the fields
 * and lets the InnerBlocks region be the whole content.
 *
 * The promo attributes are left untouched either way, so switching back to
 * `fields` restores the heading / description / buttons unchanged.
 */
$content_source = 'custom' === ( $attributes['contentSource'] ?? 'fields' ) ? 'custom' : 'fields';
$is_custom      = 'custom' === $content_source;

$content_html = $is_custom ? '' : HTML_Helpers::promo_content_html( $attributes );

// Optional top region from InnerBlocks (breadcrumb / eyebrow / meta). WP has already
// rendered + escaped this. Render the banner if EITHER the promo fields or the inner
// region has content, so a banner with only a breadcrumb + heading block still shows.
// In `custom` the inner region is all there is, so it alone decides.
$inner_html = trim( (string) $content );
if ( '' === $content_html && '' === $inner_html ) {
	return;
}

// Optional overlay layer (styling emitted by the generator).
$overlay      = $attributes['overlay'] ?? [];
$overlay_type = $overlay['type'] ?? 'none';
$overlay_html = in_array( $overlay_type, [ 'color', 'gradient' ], true )
	? '<div class="flexa-banner__overlay" aria-hidden="true"></div>'
	: '';

$classes = [ 'flexa-banner', 'flexa-banner--' . sanitize_html_class( $container_type ) ];
// Only the custom variant gets a class. `fields` is the base styling, so leaving
// its markup untouched keeps already-published banners byte-identical — including
// the per-instance CSS baked into their post meta, which knows nothing about it.
if ( $is_custom ) {
	$classes[] = 'flexa-banner--content-custom';
}
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
// In `custom` the region carries its own class: `__top` is a 12px-gap flex column
// with a trailing margin, meant for a breadcrumb strip, and would reshape whatever
// blocks the author put in.
$inner_class = $is_custom ? 'flexa-banner__content-blocks' : 'flexa-banner__top';
$top_html    = '' !== $inner_html ? '<div class="' . esc_attr( $inner_class ) . '">' . $inner_html . '</div>' : '';
$content_box = '<div class="flexa-banner__box">' . $top_html . $content_html . '</div>';

printf(
	'<%1$s %2$s%3$s%4$s>%5$s%6$s</%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$overlay_html,       // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$content_box         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text wp_kses'd, button url/rel escaped in helper.
);
