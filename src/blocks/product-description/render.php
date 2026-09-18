<?php
/**
 * Product Description block — server-side render.
 *
 * Dynamic WooCommerce block: it reads the *current* product on a single-product
 * page and prints either its long or its short description, optionally under a
 * heading and optionally clamped behind a Read more toggle. CSS is generated at
 * save time by Product_Description_CSS and printed inline on the front end.
 * Outputs nothing off a single-product page, or when the chosen description is
 * empty — an empty box with only a heading in it would be worse than nothing.
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

$product = \Flexa\Block\Woo_Helpers::current_product();
if ( ! $product ) {
	return;
}

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';

// Always the long description: the short one is Product Excerpt's job, and
// having both blocks able to print it made them impossible to tell apart.
$raw = (string) $product->get_description();
if ( '' === trim( $raw ) ) {
	return;
}

// Same pipeline WooCommerce itself uses for these fields: shortcodes first, then
// paragraphs, then a post-content kses pass so stored markup survives intact.
$description = wp_kses_post( wpautop( do_shortcode( $raw ) ) );
if ( '' === trim( $description ) ) {
	return;
}

$enable_clamp = ! empty( $attributes['enableClamp'] );

// Optional heading above the description.
$title_html = '';
if ( ! empty( $attributes['showTitle'] ) && '' !== (string) ( $attributes['titleText'] ?? '' ) ) {
	$title_tags = [ 'h2', 'h3', 'h4', 'h5', 'h6', 'p' ];
	$tag        = in_array( $attributes['titleTag'] ?? 'h3', $title_tags, true ) ? $attributes['titleTag'] : 'h3';
	$title_html = '<' . $tag . ' class="flexa-product-description__title">' . esc_html( (string) $attributes['titleText'] ) . '</' . $tag . '>';
}

$inner = $title_html . '<div class="flexa-product-description__content">' . $description . '</div>';

// Read more / Read less: both labels ride along as data attributes so view.js
// can swap them without another round trip.
if ( $enable_clamp ) {
	$more   = (string) ( $attributes['readMoreText'] ?? '' );
	$less   = (string) ( $attributes['readLessText'] ?? '' );
	$more   = '' !== $more ? $more : __( 'Read more', 'flexa-block' );
	$less   = '' !== $less ? $less : __( 'Read less', 'flexa-block' );
	$inner .= '<button type="button" class="flexa-product-description__toggle" data-more="' . esc_attr( $more ) . '" data-less="' . esc_attr( $less ) . '">' . esc_html( $more ) . '</button>';
}

$classes = [ 'flexa-product-description' ];
if ( $enable_clamp ) {
	$classes[] = 'flexa-product-description--clamped';
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-product-description-' . sanitize_html_class( $block_id );
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
	$inner               // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag whitelisted, title esc_html'd, description wp_kses_post'd, labels esc_attr/esc_html'd above.
);
