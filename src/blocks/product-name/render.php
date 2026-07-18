<?php
/**
 * Product Name block — server-side render.
 *
 * Dynamic WooCommerce block: it reads the current product on a single-product
 * page and prints its title in the chosen tag, optionally linked to the product.
 * CSS is generated at save time by Product_Name_CSS and printed inline on the
 * front end. Outputs nothing off a single-product page.
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
$name     = $product->get_name();

// Whitelist the title element tag.
$title_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span' ];
$tag        = in_array( $attributes['htmlTag'] ?? 'h2', $title_tags, true ) ? $attributes['htmlTag'] : 'h2';

// Title element; optionally wrapped in a link to the product page.
$inner = esc_html( $name );
if ( ! empty( $attributes['linkToProduct'] ) ) {
	$link_attrs = '';
	if ( ! empty( $attributes['linkTarget'] ) ) {
		$link_attrs = ' target="_blank" rel="noopener"';
	}
	$inner = '<a class="flexa-product-name__link" href="' . esc_url( get_permalink( $product->get_id() ) ) . '"' . $link_attrs . '>' . $inner . '</a>';
}

$title_html = '<' . $tag . ' class="flexa-product-name__title">' . $inner . '</' . $tag . '>';

$classes = [ 'flexa-product-name' ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-product-name-' . sanitize_html_class( $block_id );
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
	$title_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tag whitelisted, name esc_html'd, url esc_url'd above.
);
