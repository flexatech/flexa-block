<?php
/**
 * Product Stock block — server-side render.
 *
 * Dynamic WooCommerce block: it reads the current product on a single-product
 * page and prints its availability as text or a badge, with the wording the
 * author chose. CSS is generated at save time by Product_Stock_CSS and printed
 * inline on the front end. Outputs nothing off a single-product page.
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
$is_badge = 'badge' === ( $attributes['displayType'] ?? 'text' );

$in_text        = (string) ( $attributes['inStockText'] ?? 'In stock' );
$out_text       = (string) ( $attributes['outOfStockText'] ?? 'Out of stock' );
$backorder_text = (string) ( $attributes['backorderText'] ?? 'On backorder' );
$low_threshold  = (int) ( $attributes['lowStockThreshold'] ?? 0 );

$manages_stock = $product->managing_stock();
$quantity      = $manages_stock ? (int) $product->get_stock_quantity() : 0;

// State resolution runs worst-case first: a product can both be out of stock and
// carry a stale quantity, so availability wins over the numbers.
if ( ! $product->is_in_stock() ) {
	$state = 'out-of-stock';
	$label = $out_text;
} elseif ( $product->is_on_backorder( 1 ) || 'onbackorder' === $product->get_stock_status() ) {
	$state = 'on-backorder';
	$label = $backorder_text;
} elseif ( $manages_stock && $low_threshold > 0 && $quantity > 0 && $quantity <= $low_threshold ) {
	// Low stock is still "in stock" — only the colour and the state class differ.
	$state = 'low-stock';
	$label = $in_text;
} else {
	$state = 'in-stock';
	$label = $in_text;
}

$inner = '';

if ( ! empty( $attributes['showIcon'] ) ) {
	// Built-in per-status glyphs; `fill:none; stroke:currentColor` so each icon
	// takes the status colour the generator emits.
	$paths = [
		'in-stock'     => '<path d="M20 6 9 17l-5-5" />',
		'low-stock'    => '<circle cx="12" cy="12" r="9" /><path d="M12 7v5.5l3.5 2" />',
		'on-backorder' => '<circle cx="12" cy="12" r="9" /><path d="M12 7v5.5l3.5 2" />',
		'out-of-stock' => '<path d="M18 6 6 18M6 6l12 12" />',
	];

	$inner .= '<svg class="flexa-product-stock__icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $state ] . '</svg>';
}

$inner .= '<span class="flexa-product-stock__text">' . esc_html( $label ) . '</span>';

if ( ! empty( $attributes['showQuantity'] ) && $manages_stock && $quantity > 0 ) {
	$inner .= '<span class="flexa-product-stock__qty">(' . esc_html( (string) $quantity ) . ')</span>';
}

$status_html = '<span class="flexa-product-stock__status flexa-product-stock__status--' . esc_attr( $state ) . '">' . $inner . '</span>';

$classes = [ 'flexa-product-stock' ];
if ( $is_badge ) {
	$classes[] = 'flexa-product-stock--badge';
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-product-stock-' . sanitize_html_class( $block_id );
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
	$status_html         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- state from a fixed list, label/quantity esc_html'd above.
);
