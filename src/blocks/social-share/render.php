<?php
/**
 * Social Share block — server-side render.
 *
 * CSS is generated at save time by Social_Share_CSS and printed inline on the
 * front end. This file outputs one share link per network: the destination is
 * built from a share endpoint (Facebook sharer, X intent, LinkedIn share,
 * Pinterest pin, WhatsApp, Telegram) pointed at the current page — at a fixed
 * URL/title/image when the "Custom" source is chosen, or at the current
 * WooCommerce product for the "Product Share" variation. That variation falls
 * back to the current page when no product is in context, so a block placed
 * outside a product template still shares something rather than vanishing.
 * Brand artwork comes from the shared Flexa\Block\Social_Catalog (static,
 * code-owned literals — safe to echo).
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
use Flexa\Block\Social_Catalog;
use Flexa\Block\Woo_Helpers;

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';
$html_tag = HTML_Helpers::get_html_tag( $attributes );

$items = $attributes['items'] ?? [];
if ( ! is_array( $items ) || empty( $items ) ) {
	return;
}

// Resolve what is shared: the current page, the custom overrides, or the
// WooCommerce product in context.
$share_source = (string) ( $attributes['shareSource'] ?? 'current' );
$share_url    = '';
$share_title  = '';
$share_image  = '';
$allow_image  = true;

if ( 'custom' === $share_source ) {
	$share_url   = (string) ( $attributes['shareUrl'] ?? '' );
	$share_title = (string) ( $attributes['shareTitle'] ?? '' );
	$share_image = (string) ( $attributes['shareImage'] ?? '' );
} elseif ( 'product' === $share_source ) {
	$product = Woo_Helpers::current_product();
	if ( $product ) {
		$permalink   = get_permalink( $product->get_id() );
		$share_url   = $permalink ? $permalink : '';
		$share_title = $product->get_name();

		// The image only travels when the author asked for it — Pinterest is the
		// network that uses one.
		$allow_image = false !== ( $attributes['includeImage'] ?? true );
		if ( $allow_image ) {
			$image_id    = $product->get_image_id();
			$share_image = $image_id ? (string) wp_get_attachment_url( (int) $image_id ) : '';
		}
	}
}

if ( empty( $share_url ) ) {
	$permalink   = get_permalink();
	$share_url   = $permalink ? $permalink : home_url( '/' );
}
if ( empty( $share_title ) ) {
	$share_title = get_the_title();
}
if ( $allow_image && empty( $share_image ) ) {
	$thumb       = get_the_post_thumbnail_url( null, 'full' );
	$share_image = $thumb ? $thumb : '';
}

$enc_url   = rawurlencode( $share_url );
$enc_title = rawurlencode( $share_title );
$enc_image = rawurlencode( $share_image );

// Colour mode + shape are block-level (uniform across buttons).
$color_mode = 'custom' === ( $attributes['colorMode'] ?? 'official' ) ? 'custom' : 'official';
$shape      = (string) ( $attributes['shape'] ?? 'bare' );
$shape_ok   = in_array( $shape, [ 'rounded', 'circle', 'square' ], true );

// New-tab behaviour (default on — share dialogs open in a popup/tab).
$new_tab = false !== ( $attributes['newTab'] ?? true );

// Network names beside the icons (off by default — icon-only rows).
$show_labels = ! empty( $attributes['showLabels'] );

$catalog = Social_Catalog::platforms();

/**
 * Build the share endpoint URL for a network, or '' when it has no web share.
 *
 * @param string $network   Network key.
 * @param string $enc_url   URL-encoded page URL.
 * @param string $enc_title URL-encoded title.
 * @param string $enc_image URL-encoded image URL.
 * @return string
 */
$build_share = static function ( $network, $enc_url, $enc_title, $enc_image ) {
	return \Flexa\Block\Social_Catalog::share_url( (string) $network, (string) $enc_url, (string) $enc_title, (string) $enc_image );
};

// Build the button list.
$items_html = '';
foreach ( $items as $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}
	$network = (string) ( $item['network'] ?? '' );
	if ( ! isset( $catalog[ $network ] ) ) {
		continue;
	}
	$href = $build_share( $network, $enc_url, $enc_title, $enc_image );
	if ( '' === $href ) {
		continue;
	}

	$entry    = $catalog[ $network ];
	$icon_svg = 'custom' === $color_mode
		? '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="' . $entry['mono'] . '"/></svg>'
		: $entry['brand'];

	$item_classes = 'flexa-social-share__item flexa-social-share__item--' . sanitize_html_class( $network );
	if ( $shape_ok ) {
		$item_classes .= ' flexa-social-share__item--shape-' . $shape;
	}

	/* translators: %s: social network name. */
	$label = sprintf( __( 'Share on %s', 'flexa-block' ), $entry['label'] );

	$item_attrs = 'class="' . esc_attr( $item_classes ) . '" href="' . esc_url( $href ) . '" aria-label="' . esc_attr( $label ) . '"';
	if ( $new_tab ) {
		$item_attrs .= ' target="_blank" rel="noopener noreferrer"';
	}

	$label_html = $show_labels
		? '<span class="flexa-social-share__label">' . esc_html( $entry['label'] ) . '</span>'
		: '';

	$items_html .= '<a ' . $item_attrs . '><span class="flexa-social-share__icon">' . $icon_svg . '</span>'
		. $label_html . '</a>';
}

if ( '' === $items_html ) {
	return;
}

// Wrapper classes + attributes.
$classes = [ 'flexa-social-share' ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-social-share-' . sanitize_html_class( $block_id );
}
if ( $show_labels ) {
	$classes[] = 'flexa-social-share--labels';
}
$hover_effect = (string) ( $attributes['hoverEffect'] ?? '' );
if ( in_array( $hover_effect, [ 'grow', 'shrink', 'lift', 'rotate' ], true ) ) {
	$classes[] = 'flexa-social-share--hover-' . $hover_effect;
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
	'<%1$s %2$s%3$s%4$s><div class="flexa-social-share__list">%5$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$items_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG literals; urls/labels/classes escaped above.
);
