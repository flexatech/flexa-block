<?php
/**
 * Product Image block — server-side render.
 *
 * Dynamic WooCommerce block: it reads the current product on a single-product
 * page and prints its featured image plus a thumbnail strip (featured image
 * first, then the product gallery). Clicking a thumbnail swaps the main image
 * (see view.js). CSS is generated at save time by Product_Image_CSS and printed
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

$block_id    = $attributes['blockId'] ?? '';
$anchor      = $attributes['anchor'] ?? '';
$position    = $attributes['galleryPosition'] ?? 'bottom';
$position    = in_array( $position, [ 'bottom', 'left', 'right' ], true ) ? $position : 'bottom';
$show_thumbs = ! isset( $attributes['showThumbnails'] ) || ! empty( $attributes['showThumbnails'] );
$zoom        = ! empty( $attributes['zoomOnHover'] );
$autoplay    = ! empty( $attributes['autoplay'] );
$autoplay_ms = max( 1, (int) ( $attributes['autoplaySpeed'] ?? 4 ) ) * 1000;

// Featured image ( get_image() returns an escaped <img> ).
$main_html = $product->get_image( 'woocommerce_single' );
$main      = '<div class="flexa-product-image__main">' . $main_html . '</div>';

// Thumbnail ids: the featured image first, then the product gallery images.
$ids         = (array) $product->get_gallery_image_ids();
$featured_id = $product->get_image_id();
if ( $featured_id ) {
	array_unshift( $ids, $featured_id );
}

// Thumbnail strip (carousel) — only when enabled and there is more than one image.
$per_view = max( 1, (int) ( $attributes['thumbnailsPerView'] ?? 4 ) );
$thumbs   = '';
if ( $show_thumbs && count( $ids ) > 1 ) {
	$list  = '';
	$first = true;
	foreach ( $ids as $id ) {
		$id        = (int) $id;
		$full      = wp_get_attachment_image_url( $id, 'woocommerce_single' );
		$full      = $full ? $full : wp_get_attachment_image_url( $id, 'large' );
		$thumb_img = wp_get_attachment_image( $id, 'woocommerce_gallery_thumbnail' );
		if ( '' === $thumb_img ) {
			continue;
		}
		$cls   = 'flexa-product-image__thumb' . ( $first ? ' is-active' : '' );
		$list .= '<li class="' . esc_attr( $cls ) . '" data-full="' . esc_url( (string) $full ) . '">' . $thumb_img . '</li>';
		$first = false;
	}
	if ( '' !== $list ) {
		// Prev / next arrows only when there are more thumbnails than fit in view
		// (hidden by CSS for the left/right vertical layout).
		$prev = '';
		$next = '';
		if ( count( $ids ) > $per_view ) {
			$prev = '<button type="button" class="flexa-product-image__nav flexa-product-image__nav--prev" aria-label="' . esc_attr__( 'Previous', 'flexa-block' ) . '">&#8249;</button>';
			$next = '<button type="button" class="flexa-product-image__nav flexa-product-image__nav--next" aria-label="' . esc_attr__( 'Next', 'flexa-block' ) . '">&#8250;</button>';
		}
		$thumbs = '<div class="flexa-product-image__thumbs-wrap">' . $prev . '<ul class="flexa-product-image__thumbs">' . $list . '</ul>' . $next . '</div>';
	}
}

$classes = [
	'flexa-product-image',
	'flexa-product-image--pos-' . sanitize_html_class( $position ),
];
if ( $zoom ) {
	$classes[] = 'flexa-product-image--zoom';
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-product-image-' . sanitize_html_class( $block_id );
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

// Autoplay: only meaningful when there is an interactive thumbnail strip.
$autoplay_marker = ( $autoplay && '' !== $thumbs )
	? ' data-flexa-autoplay="1" data-flexa-autoplay-speed="' . esc_attr( (string) $autoplay_ms ) . '"'
	: '';

printf(
	'<div %1$s%2$s%3$s%4$s>%5$s%6$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$autoplay_marker,    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- integer speed esc_attr'd above.
	$main,               // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce get_image() returns escaped markup.
	$thumbs              // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() escaped; class esc_attr'd, url esc_url'd above.
);
