<?php
/**
 * Product Excerpt block — server-side render.
 *
 * Dynamic WooCommerce block: it reads the *current* product on a single-product
 * page and prints its short description, optionally falling back to the long one
 * when the short field is empty. CSS is generated at save time by
 * Product_Excerpt_CSS and printed inline on the front end. Outputs nothing off a
 * single-product page, or when the resolved text is empty — an empty box is
 * worse than no box.
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
$source   = ( 'short' === ( $attributes['source'] ?? 'auto' ) ) ? 'short' : 'auto';

$raw = (string) $product->get_short_description();
if ( 'auto' === $source && '' === trim( $raw ) ) {
	// Fall back to the long description so the block still says something on
	// products where the shop owner never filled the short field in.
	$raw = (string) $product->get_description();
}
if ( '' === trim( $raw ) ) {
	return;
}

// Same pipeline WooCommerce itself uses for these fields: shortcodes first, then
// paragraphs, then a post-content kses pass so stored markup survives intact.
$excerpt = wp_kses_post( wpautop( do_shortcode( $raw ) ) );

// Cap AFTER the markup is built, with a tag-aware trim. wp_trim_words() would
// strip every tag first, which threw away the shop owner's links along with the
// styling aimed at them.
$word_limit = (int) ( $attributes['wordLimit'] ?? 0 );
if ( $word_limit > 0 ) {
	$excerpt = \Flexa\Block\Woo_Helpers::trim_words_html( $excerpt, $word_limit );
}
if ( '' === trim( $excerpt ) ) {
	return;
}

$enable_clamp = ! empty( $attributes['enableClamp'] );

$inner = '<div class="flexa-product-excerpt__content">' . $excerpt . '</div>';

$classes = [ 'flexa-product-excerpt' ];
if ( $enable_clamp ) {
	$classes[] = 'flexa-product-excerpt--clamped';
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-product-excerpt-' . sanitize_html_class( $block_id );
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
	$inner               // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- excerpt wp_kses_post'd above.
);
