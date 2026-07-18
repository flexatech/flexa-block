<?php
/**
 * Social Share block — server-side render.
 *
 * CSS is generated at save time by Social_Share_CSS and printed inline on the
 * front end. This file outputs one share link per network: the destination is
 * built from a share endpoint (Facebook sharer, X intent, LinkedIn share,
 * Pinterest pin) pointed at the current page — or a fixed URL/title/image when
 * the "Custom" source is chosen. Brand artwork comes from the shared
 * Flexa\Block\Social_Catalog (static, code-owned literals — safe to echo).
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

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';
$html_tag = HTML_Helpers::get_html_tag( $attributes );

$items = $attributes['items'] ?? [];
if ( ! is_array( $items ) || empty( $items ) ) {
	return;
}

// Resolve what is shared: the current page, or the custom overrides.
$is_custom = 'custom' === ( $attributes['shareSource'] ?? 'current' );
if ( $is_custom ) {
	$share_url   = (string) ( $attributes['shareUrl'] ?? '' );
	$share_title = (string) ( $attributes['shareTitle'] ?? '' );
	$share_image = (string) ( $attributes['shareImage'] ?? '' );
}
if ( empty( $share_url ) ) {
	$permalink   = get_permalink();
	$share_url   = $permalink ? $permalink : home_url( '/' );
}
if ( empty( $share_title ) ) {
	$share_title = get_the_title();
}
if ( empty( $share_image ) ) {
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
	switch ( $network ) {
		case 'facebook':
			return 'https://www.facebook.com/sharer/sharer.php?u=' . $enc_url;
		case 'x':
			return 'https://twitter.com/intent/tweet?url=' . $enc_url . '&text=' . $enc_title;
		case 'linkedin':
			return 'https://www.linkedin.com/sharing/share-offsite/?url=' . $enc_url;
		case 'pinterest':
			return 'https://www.pinterest.com/pin/create/button/?url=' . $enc_url . '&media=' . $enc_image . '&description=' . $enc_title;
		default:
			return '';
	}
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

	$items_html .= '<a ' . $item_attrs . '><span class="flexa-social-share__icon">' . $icon_svg . '</span></a>';
}

if ( '' === $items_html ) {
	return;
}

// Wrapper classes + attributes.
$classes = [ 'flexa-social-share' ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-social-share-' . sanitize_html_class( $block_id );
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

printf(
	'<%1$s %2$s%3$s><div class="flexa-social-share__list">%4$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$items_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG literals; urls/labels/classes escaped above.
);
