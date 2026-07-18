<?php
/**
 * Lottie Animation block — server-side render.
 *
 * CSS is generated at save time by Lottie_CSS and printed inline on the front
 * end. This file outputs the wrapper plus an empty `.flexa-lottie__player` box
 * carrying the animation config in escaped data-* attributes; view.ts reads them
 * and loads the animation from the source URL via lottie-web's `path` option (the
 * JSON is never inlined here). Output is escaped.
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
if ( '' === $block_id ) {
	return;
}

$source = in_array( $attributes['source'] ?? 'file', [ 'url', 'file' ], true ) ? $attributes['source'] : 'file';
$src    = 'file' === $source ? ( $attributes['fileUrl'] ?? '' ) : ( $attributes['url'] ?? '' );
$src    = trim( (string) $src );

// Nothing playable → don't render an empty animation box.
if ( '' === $src ) {
	return;
}

$loop    = false !== ( $attributes['loop'] ?? true );
$speed   = (float) ( $attributes['speed'] ?? 1 );
$reverse = ! empty( $attributes['reverse'] );
$play_on = in_array( $attributes['playOn'] ?? 'autoplay', [ 'autoplay', 'none', 'hover', 'click', 'scroll', 'viewport' ], true )
	? $attributes['playOn']
	: 'autoplay';

$anchor   = $attributes['anchor'] ?? '';
$html_tag = HTML_Helpers::get_html_tag( $attributes );

$classes = [
	'flexa-lottie',
	'flexa-lottie-' . sanitize_html_class( $block_id ),
];
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

$player_html = sprintf(
	'<div class="flexa-lottie__player" data-src="%1$s" data-loop="%2$s" data-speed="%3$s" data-reverse="%4$s" data-play-on="%5$s"></div>',
	esc_url( $src ),
	$loop ? '1' : '0',
	esc_attr( (string) $speed ),
	$reverse ? '1' : '0',
	esc_attr( $play_on )
);

printf(
	'<%1$s %2$s%3$s>%4$s</%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$player_html         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- src via esc_url, other values escaped above.
);
