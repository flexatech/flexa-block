<?php
/**
 * Google Map block — server-side render.
 *
 * CSS is generated at save time by Google_Map_CSS and printed inline on the
 * front end. This file outputs the wrapper > frame > iframe structure. The embed
 * URL is built from the location + zoom and escaped with esc_url().
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

$block_id = $attributes['blockId'] ?? '';
$location = trim( (string) ( $attributes['location'] ?? '' ) );

// Nothing to render without a block id or a location.
if ( '' === $block_id || '' === $location ) {
	return;
}

$zoom            = max( 1, min( 20, (int) ( $attributes['zoom'] ?? 12 ) ) );
$api_key         = $attributes['apiKey'] ?? [];
$api_enabled     = ! empty( $api_key['enabled'] );
$api_key_value   = (string) ( $api_key['key'] ?? '' );
$anchor          = $attributes['anchor'] ?? '';
$container_type  = 'full-width' === ( $attributes['containerType'] ?? 'boxed' ) ? 'full-width' : 'boxed';

// Build the embed URL: the official Embed API when a key is set, else the free
// public embed endpoint.
$encoded_location = rawurlencode( $location );
if ( $api_enabled && '' !== $api_key_value ) {
	$embed_url = sprintf(
		'https://www.google.com/maps/embed/v1/place?key=%s&q=%s&zoom=%d',
		rawurlencode( $api_key_value ),
		$encoded_location,
		$zoom
	);
} else {
	$embed_url = sprintf(
		'https://maps.google.com/maps?q=%s&z=%d&output=embed',
		$encoded_location,
		$zoom
	);
}

$classes = [
	'flexa-google-map',
	'flexa-google-map--' . sanitize_html_class( $container_type ),
];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-google-map-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$html_tag = HTML_Helpers::get_html_tag( $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

printf(
	'<%1$s %2$s%3$s><div class="flexa-google-map__frame"><iframe class="flexa-google-map__iframe" src="%4$s" title="%5$s" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	esc_url( $embed_url ),
	esc_attr__( 'Google Map', 'flexa-block' )
);
