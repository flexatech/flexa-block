<?php
/**
 * Table of Contents block — server-side render.
 *
 * CSS is generated at save time by Table_Of_Content_CSS and printed inline on
 * the front end. This file outputs the shell — an accessible title (or collapse
 * toggle) plus an empty list container — and the data attributes view.js needs;
 * the actual jump-link list is built client-side from the page's headings so it
 * always tracks the live content.
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
$html_tag = HTML_Helpers::get_html_tag( $attributes, 'nav' );

// Which heading levels feed the list → a tag list view.js scans for.
$headings     = $attributes['headings'] ?? [];
$level_tags   = [];
foreach ( [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ] as $level ) {
	if ( ! empty( $headings[ $level ] ) ) {
		$level_tags[] = $level;
	}
}

$marker       = in_array( $attributes['markerType'] ?? 'bullet', [ 'bullet', 'number', 'none' ], true ) ? $attributes['markerType'] : 'bullet';
$show_title   = false !== ( $attributes['showTitle'] ?? true );
$title        = (string) ( $attributes['title'] ?? '' );
$title_tag    = in_array( $attributes['titleTag'] ?? 'h2', [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' ], true ) ? $attributes['titleTag'] : 'h2';
$empty_text   = (string) ( $attributes['emptyText'] ?? '' );
$smooth       = false !== ( $attributes['smoothScroll'] ?? true );
$offset       = (int) ( $attributes['scrollOffset'] ?? 0 );
$collapsible  = ! empty( $attributes['collapsible'] );
$collapsed    = $collapsible && ! empty( $attributes['initialCollapsed'] );

// Wrapper classes.
$classes = [ 'flexa-toc', 'flexa-toc--marker-' . $marker ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-table-of-content-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Behaviour flags for view.js.
$toc_attrs = ' data-flexa-toc data-levels="' . esc_attr( implode( ',', $level_tags ) ) . '"';
if ( $smooth ) {
	$toc_attrs .= ' data-smooth data-offset="' . esc_attr( (string) $offset ) . '"';
}
if ( $collapsible ) {
	$toc_attrs .= ' data-collapsible';
}
if ( $collapsed ) {
	$toc_attrs .= ' data-collapsed';
}

// Lazy background marker.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

// A chevron toggle icon (static markup — safe to echo).
$icon_html = '<span class="flexa-toc__icon" aria-hidden="true"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';

// Header: a collapse toggle when collapsible, otherwise the plain title.
$header_html = '';
if ( $collapsible ) {
	$expanded    = $collapsed ? 'false' : 'true';
	$title_span  = $show_title ? '<span class="flexa-toc__title">' . esc_html( $title ) . '</span>' : '';
	$header_html = '<button type="button" class="flexa-toc__toggle" aria-expanded="' . esc_attr( $expanded ) . '">' . $title_span . $icon_html . '</button>';
} elseif ( $show_title ) {
	$header_html = '<' . $title_tag . ' class="flexa-toc__title">' . esc_html( $title ) . '</' . $title_tag . '>';
}

// Body: an empty list view.js fills, plus a note shown when no headings match.
$body_html  = '<div class="flexa-toc__body">';
$body_html .= '<ul class="flexa-toc__list"></ul>';
$body_html .= '<p class="flexa-toc__empty" hidden>' . esc_html( $empty_text ) . '</p>';
$body_html .= '</div>';

printf(
	'<%1$s %2$s%3$s%4$s%5$s>%6$s%7$s</%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$toc_attrs,          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literals + esc_attr'd values.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$header_html,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- title esc_html'd, tag whitelisted, icon static.
	$body_html           // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- emptyText esc_html'd, rest static.
);
