<?php
/**
 * Post Grid block — server-side render.
 *
 * CSS is generated at save time by Post_Grid_CSS and printed inline on the front
 * end. The query, the cards and the pagination all come from Post_Query, which the
 * /post-query REST route renders through as well — so a grid a visitor has filtered
 * looks the same whether the markup arrived by AJAX or by a cold page load.
 *
 * Visitor search/filter state travels in per-instance query args (`s_<blockId>`,
 * `tx_<blockId>_<taxonomy>`, `pg_<blockId>`), so several grids on one page never
 * read each other's state — and so the whole thing works with JavaScript off.
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
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filtering/paging, no state change.

use Flexa\Block\HTML_Helpers;
use Flexa\Block\Post_Query;

$block_id = sanitize_html_class( (string) ( $attributes['blockId'] ?? '' ) );
$anchor   = $attributes['anchor'] ?? '';
$html_tag = HTML_Helpers::get_html_tag( $attributes, 'section' );

$container_type = 'full-width' === ( $attributes['containerType'] ?? 'boxed' ) ? 'full-width' : 'boxed';

$post_id = (int) get_the_ID();
$cfg     = Post_Query::config( $attributes );

// The visitor's filters — dropped unless a filter block on this page actually
// offers that taxonomy for this grid.
$request = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- each value is sanitized in parse_filters().
$allowed = Post_Query::allowed_taxonomies( $post_id, $block_id );
$filters = Post_Query::parse_filters( is_array( $request ) ? $request : [], $block_id, $allowed );
$paged   = Post_Query::paged( is_array( $request ) ? $request : [], $block_id );

$result = Post_Query::run( $cfg, $filters, $paged );

$grid_html       = Post_Query::render_grid( Post_Query::render_cards( $result['query'], $cfg ), ! empty( $cfg['showResultCount'] ) );
$pagination_html = Post_Query::render_pagination( $cfg, $block_id, $result['paged'], $result['totalPages'] );
$status_html     = Post_Query::render_status( $result['total'], ! empty( $cfg['showResultCount'] ), (string) ( $cfg['resultCountText'] ?? '' ) );

// --- Wrapper classes + attributes. ---
$classes = [ 'flexa-post-grid', 'flexa-post-grid--' . $container_type ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-post-grid-' . $block_id;
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
// Per-instance key: the view script uses it to scope AJAX paging/filtering to this
// grid. The post id + REST root let it re-render just this grid instead of the page.
$wrapper_args['data-flexa-post-grid'] = Post_Query::page_key( $block_id );
$wrapper_args['data-flexa-grid-id']   = $block_id;
$wrapper_args['data-flexa-post-id']   = (string) $post_id;
$wrapper_args['data-flexa-rest']      = esc_url( rest_url( 'flexa-block/v1/post-query' ) );
if ( 'loadmore' === $cfg['paginationType'] ) {
	$wrapper_args['data-flexa-loadmore'] = '1';
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Lazy background marker on the styled inner element.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

printf(
	'<%1$s %2$s%3$s><div class="flexa-post-grid__inner"%4$s>%5$s%6$s%7$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$status_html,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Post_Query.
	$grid_html,          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Post_Query.
	$pagination_html     // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Post_Query.
);
