<?php
/**
 * Product Price block — server-side render.
 *
 * Dynamic WooCommerce block: reads the *current* product on a single-product
 * page and prints its price (regular + sale + currency) straight from
 * WooCommerce — there is no user-entered text. CSS is generated at save time by
 * Product_Price_CSS and printed inline on the front end. Outputs nothing when
 * there is no product in context, keeping the block scoped to product pages.
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
$position = ( 'before' === ( $attributes['salePricePosition'] ?? 'after' ) ) ? 'before' : 'after';

// Build the price markup. When on sale with both amounts, split into regular +
// sale spans ordered by the chosen position; otherwise fall back to WooCommerce's
// own price HTML wrapped as the regular amount.
$on_sale = $product->is_on_sale();
$regular = (string) $product->get_regular_price();
$sale    = (string) $product->get_sale_price();

if ( $on_sale && '' !== $sale && '' !== $regular ) {
	$reg       = '<span class="flexa-product-price__regular">' . wp_kses_post( wc_price( $regular ) ) . '</span>';
	$sale_html = '<span class="flexa-product-price__sale">' . wp_kses_post( wc_price( $sale ) ) . '</span>';
	$price     = ( 'before' === $position ) ? $sale_html . $reg : $reg . $sale_html;
} else {
	$reg       = '<span class="flexa-product-price__regular">' . wp_kses_post( $product->get_price_html() ) . '</span>';
	$sale_html = '';
	$price     = $reg;
}

$inner = '<span class="flexa-product-price__inner">' . $price . '</span>';

$strike  = ! isset( $attributes['strikethrough'] ) || ! empty( $attributes['strikethrough'] );
$classes = [ 'flexa-product-price' ];
if ( $on_sale && '' !== $sale && '' !== $regular && $strike ) {
	// Marks the regular amount for a strike-through (see style.scss).
	$classes[] = 'flexa-product-price--strike';
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-product-price-' . sanitize_html_class( $block_id );
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
	$inner               // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- price wc_price/get_price_html wp_kses_post'd above.
);
