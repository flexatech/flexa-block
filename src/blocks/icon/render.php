<?php
/**
 * Icon block — server-side render.
 *
 * CSS is generated at save time by Icon_CSS and printed inline on the front end.
 * This file outputs the wrapper, the frame span and the resolved glyph (inline
 * SVG via HTML_Helpers::icon_html, or an <img> for uploaded SVGs), optionally
 * wrapped in a link. The icon inherits the theme's text colour unless the user
 * overrode it.
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

// Whitelist the shape modifier so only a known class suffix reaches markup.
$shape = in_array( $attributes['shape'] ?? 'square', [ 'square', 'rounded', 'circle' ], true ) ? $attributes['shape'] : 'square';

// Resolve the glyph (inline SVG for builtin/library, <img> for uploads). Nothing
// to render when no icon is chosen.
$icon_html = HTML_Helpers::icon_html( $attributes['icon'] ?? [], 48 );
if ( '' === $icon_html ) {
	return;
}

$inner = '<span class="flexa-icon__inner">' . $icon_html . '</span>';

// Optional link wrap around the frame.
$link = is_array( $attributes['link'] ?? null ) ? $attributes['link'] : [];
$url  = (string) ( $link['url'] ?? '' );
if ( '' !== $url ) {
	$new_tab    = '_blank' === ( $link['target'] ?? '' );
	$rel        = trim( (string) ( $link['rel'] ?? '' ) );
	$link_attrs = 'class="flexa-icon__link" href="' . esc_url( $url ) . '"';
	if ( $new_tab ) {
		$link_attrs .= ' target="_blank"';
		if ( '' === $rel ) {
			$rel = 'noopener noreferrer';
		}
	}
	if ( '' !== $rel ) {
		$rel         = trim( (string) preg_replace( '/[^a-z0-9 _-]/i', '', $rel ) );
		$link_attrs .= ' rel="' . esc_attr( $rel ) . '"';
	}
	$inner = '<a ' . $link_attrs . '>' . $inner . '</a>';
}

// --- Wrapper ---------------------------------------------------------------
$classes = [
	'flexa-icon',
	'flexa-icon--shape-' . $shape,
];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-icon-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

printf(
	'<%1$s %2$s%3$s>%4$s</%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$inner               // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- glyph via icon_html (svg_kses / escaped img), link url/rel escaped above.
);
