<?php
/**
 * Heading block — server-side render.
 *
 * CSS is generated at save time by Heading_CSS and printed inline on the front
 * end. This file outputs the wrapper plus the heading element, with an optional
 * subheading and separator ordered by their chosen positions. The title inherits
 * the theme's typography unless the user overrode it.
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
$content  = (string) ( $attributes['content'] ?? '' );
$anchor   = $attributes['anchor'] ?? '';

// Whitelist the heading/subheading element tags.
$text_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' ];
$tag       = in_array( $attributes['tag'] ?? 'h2', $text_tags, true ) ? $attributes['tag'] : 'h2';
$sub_tag   = in_array( $attributes['subheadingTag'] ?? 'span', $text_tags, true ) ? $attributes['subheadingTag'] : 'span';

$url       = (string) ( $attributes['url'] ?? '' );
$new_tab   = '_blank' === ( $attributes['linkTarget'] ?? '' );
$rel       = trim( (string) ( $attributes['rel'] ?? '' ) );

$show_sub    = ! empty( $attributes['showSubheading'] );
$sub_content = (string) ( $attributes['subheadingContent'] ?? '' );
$sub_pos     = 'top' === ( $attributes['subheadingPosition'] ?? 'bottom' ) ? 'top' : 'bottom';

$show_sep = ! empty( $attributes['showSeparator'] );
$sep_pos  = 'top' === ( $attributes['separatorPosition'] ?? 'bottom' ) ? 'top' : 'bottom';

// Inline formatting allowed inside the heading / subheading text.
$inline_allowed = [
	'strong' => [],
	'em'     => [],
	'b'      => [],
	'i'      => [],
	'br'     => [],
	'span'   => [ 'style' => true, 'class' => true ],
	'mark'   => [ 'style' => true, 'class' => true ],
	'sub'    => [],
	'sup'    => [],
];

// Build the title, wrapping it in a link when a URL is set.
$title_inner = wp_kses( $content, $inline_allowed );
if ( '' !== $url ) {
	$link_attrs = 'class="flexa-heading__link" href="' . esc_url( $url ) . '"';
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
	$title_inner = '<a ' . $link_attrs . '>' . $title_inner . '</a>';
}
$title_html = '<' . $tag . ' class="flexa-heading__title">' . $title_inner . '</' . $tag . '>';

$sub_html = '';
if ( $show_sub && '' !== trim( $sub_content ) ) {
	$sub_html = '<' . $sub_tag . ' class="flexa-heading__subheading">' . wp_kses( $sub_content, $inline_allowed ) . '</' . $sub_tag . '>';
}

$sep_html = $show_sep ? '<div class="flexa-heading__separator" aria-hidden="true"></div>' : '';

// Assemble the pieces in visual order.
$inner = '';
if ( 'top' === $sep_pos ) {
	$inner .= $sep_html;
}
if ( 'top' === $sub_pos ) {
	$inner .= $sub_html;
}
$inner .= $title_html;
if ( 'bottom' === $sub_pos ) {
	$inner .= $sub_html;
}
if ( 'bottom' === $sep_pos ) {
	$inner .= $sep_html;
}

$classes = [ 'flexa-heading' ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-heading-' . sanitize_html_class( $block_id );
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
	$inner               // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tags whitelisted, text wp_kses'd, url esc_url'd above.
);
