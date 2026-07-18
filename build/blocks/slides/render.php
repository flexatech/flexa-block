<?php
/**
 * Slider block — server-side render.
 *
 * CSS is generated at save time by Slides_CSS and printed inline on the front
 * end. This file outputs the Swiper markup (wrapper / track / navigation /
 * pagination) around the rendered slide children and a `data-flexa-slider`
 * config blob that view.js reads to boot Swiper.
 *
 * @package Flexa\Block
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    InnerBlocks content (the rendered slides).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $attributes, $content provided by WP block API.

use Flexa\Block\HTML_Helpers;

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';

$show_arrows     = ! empty( $attributes['showArrows'] );
$show_pagination = ! empty( $attributes['showPagination'] );

$arrow_position = 'outside' === ( $attributes['arrowPosition'] ?? 'inside' ) ? 'outside' : 'inside';
$pag_position   = 'inside' === ( $attributes['paginationPosition'] ?? 'outside' ) ? 'inside' : 'outside';
$arrows_hover   = ! empty( $attributes['arrowShowOnHover'] );

$effect = $attributes['effect'] ?? 'slide';
$effect = in_array( $effect, [ 'slide', 'fade', 'cube', 'coverflow', 'flip', 'creative' ], true ) ? $effect : 'slide';

$pagination_type = $attributes['paginationType'] ?? 'bullets';
$pagination_type = in_array( $pagination_type, [ 'bullets', 'fraction', 'progressbar' ], true ) ? $pagination_type : 'bullets';

$per_view = is_array( $attributes['slidesPerView'] ?? null ) ? $attributes['slidesPerView'] : [];

// Front-end Swiper config. Only primitive, already-validated values go in.
$config = [
	'autoplay'           => ! empty( $attributes['autoplay'] ),
	'autoplayDelay'      => max( 100, (int) ( $attributes['autoplayDelay'] ?? 3000 ) ),
	'pauseOnHover'       => ! empty( $attributes['pauseOnHover'] ),
	'pauseOnInteraction' => ! empty( $attributes['pauseOnInteraction'] ),
	'loop'               => ! empty( $attributes['loop'] ),
	'speed'              => max( 0, (int) ( $attributes['speed'] ?? 500 ) ),
	'effect'             => $effect,
	'spaceBetween'       => max( 0, (int) ( $attributes['spaceBetween'] ?? 0 ) ),
	'slidesPerView'      => [
		'desktop' => max( 1, (int) ( $per_view['desktop'] ?? 1 ) ),
		'tablet'  => max( 1, (int) ( $per_view['tablet'] ?? 1 ) ),
		'mobile'  => max( 1, (int) ( $per_view['mobile'] ?? 1 ) ),
	],
	'navigation'         => $show_arrows,
	'pagination'         => $show_pagination,
	'paginationType'     => $pagination_type,
];

$classes = [ 'flexa-slides' ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-slides-' . sanitize_html_class( $block_id );
}
if ( $show_arrows ) {
	$classes[] = 'flexa-slides--arrows-' . $arrow_position;
	if ( $arrows_hover ) {
		$classes[] = 'flexa-slides--arrows-hover';
	}
}
if ( $show_pagination ) {
	$classes[] = 'flexa-slides--pagination-' . $pag_position;
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [
	'class'              => implode( ' ', $classes ),
	'data-flexa-slider'  => wp_json_encode( $config ),
];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Default chevron fallbacks when the user hasn't picked a custom arrow icon.
$default_prev = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>';
$default_next = '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>';

// Resolve an arrow icon (IconValue-shaped) to already-safe HTML: an escaped
// <img> for uploaded artwork, wp_kses'd inline SVG for builtin/library markup,
// or the default chevron. Inlined (not a named function) because render.php is
// included once per block instance — a top-level function would redeclare.
$resolve_arrow = static function ( $icon, string $fallback ): string {
	$icon   = is_array( $icon ) ? $icon : [];
	$source = (string) ( $icon['source'] ?? 'none' );
	if ( 'upload' === $source && '' !== (string) ( $icon['url'] ?? '' ) ) {
		return '<img class="flexa-icon" src="' . esc_url( $icon['url'] ) . '" alt="" width="24" height="24" loading="lazy" />';
	}
	if ( 'none' !== $source && '' !== (string) ( $icon['markup'] ?? '' ) ) {
		return HTML_Helpers::svg_kses( (string) $icon['markup'] );
	}
	return HTML_Helpers::svg_kses( $fallback );
};

$prev_icon = $resolve_arrow( $attributes['arrowIconPrev'] ?? [], $default_prev );
$next_icon = $resolve_arrow( $attributes['arrowIconNext'] ?? [], $default_next );

$arrows_html = '';
if ( $show_arrows ) {
	$arrows_html  = '<div class="swiper-button-prev flexa-slides__arrow" role="button" tabindex="0" aria-label="' . esc_attr__( 'Previous slide', 'flexa-block' ) . '">' . $prev_icon . '</div>';
	$arrows_html .= '<div class="swiper-button-next flexa-slides__arrow" role="button" tabindex="0" aria-label="' . esc_attr__( 'Next slide', 'flexa-block' ) . '">' . $next_icon . '</div>';
}

$pagination_html = $show_pagination ? '<div class="swiper-pagination"></div>' : '';

// Layout model: a positioned `.flexa-slides__viewport` wraps ONLY the swiper and
// the arrows (+ overlay pagination), so arrows anchor to the image's vertical
// centre — not to the whole block (which may also carry pagination below).
// Arrows live outside `.swiper` so the "outside" gutter is not clipped by
// Swiper's own `overflow:hidden`. Pagination sits inside the viewport (overlay)
// or after it (below) depending on the chosen position. view.js resolves all of
// them by querying from the block root, so their DOM location is free to change.
$overlay_pagination = ( $show_pagination && 'inside' === $pag_position ) ? $pagination_html : '';
$below_pagination   = ( $show_pagination && 'outside' === $pag_position ) ? $pagination_html : '';

printf(
	'<div %1$s%2$s><div class="flexa-slides__viewport"><div class="swiper"><div class="swiper-wrapper">%3$s</div></div>%4$s%5$s</div>%6$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$content,            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- InnerBlocks content already rendered/escaped by WP.
	$arrows_html,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses'd SVG + static literals.
	$overlay_pagination, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$below_pagination    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
);
