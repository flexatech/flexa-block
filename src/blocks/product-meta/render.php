<?php
/**
 * Product Meta block — server-side render.
 *
 * A container: it stacks whatever flexa/product-field blocks the author put
 * inside, and owns what only a list can own — the label column that lines every
 * row up, the gap between rows and the divider. The rows themselves are
 * rendered by their own blocks, so `$content` is already the finished markup.
 *
 * Nothing is printed off a single-product page, or when every field inside came
 * back empty (a product with no SKU, no categories and no tags) — an empty
 * bordered box would be worse than no box.
 *
 * CSS is generated at save time by Product_Meta_CSS, which styles the fields
 * through the shared `flexa-product-field__*` class names.
 *
 * @package Flexa\Block
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks' rendered markup.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $attributes provided by WP block API.

use Flexa\Block\HTML_Helpers;
use Flexa\Block\Woo_Helpers;

if ( ! Woo_Helpers::current_product() ) {
	return;
}

if ( '' === trim( (string) $content ) ) {
	return;
}

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';

$classes = [ 'flexa-product-meta' ];
if ( ! empty( $attributes['showDivider'] ) ) {
	$classes[] = 'flexa-product-meta--divided';
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-product-meta-' . sanitize_html_class( $block_id );
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
	$content             // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner blocks' own render output, already escaped there.
);
