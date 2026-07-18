<?php
/**
 * Separator block — server-side render.
 *
 * CSS is generated at save time by Separator_CSS and printed inline on the front
 * end. This file outputs the wrapper + a single <hr> line; the line inherits the
 * theme's text colour until the user overrode its colour/style/geometry.
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
$anchor   = $attributes['anchor'] ?? '';

$html_tag = HTML_Helpers::get_html_tag( $attributes );

$classes = [ 'flexa-separator' ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-separator-' . sanitize_html_class( $block_id );
}

$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

printf(
	'<%1$s %2$s%3$s><hr class="flexa-separator__line" /></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
);
