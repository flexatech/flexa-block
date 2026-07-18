<?php
/**
 * RSS block — server-side render.
 *
 * CSS is generated at save time by Rss_CSS and printed inline on the front end.
 * This file fetches the configured RSS/Atom feed with WordPress `fetch_feed()`
 * (SimplePie, transient-cached for the chosen number of minutes) and outputs a
 * list or grid of item cards (thumbnail, meta, title, excerpt, read-more link).
 * Every dynamic value is escaped on output.
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
$html_tag = HTML_Helpers::get_html_tag( $attributes, 'section' );

$container_type = 'full-width' === ( $attributes['containerType'] ?? 'boxed' ) ? 'full-width' : 'boxed';
$layout         = 'list' === ( $attributes['feedLayout'] ?? 'grid' ) ? 'list' : 'grid';

// --- Source attributes (sanitized). ---
$feed_url    = esc_url_raw( trim( (string) ( $attributes['feedUrl'] ?? '' ) ) );
$items_count = max( 1, min( 30, (int) ( $attributes['itemsToShow'] ?? 5 ) ) );
$cache_min   = max( 5, min( 1440, (int) ( $attributes['cacheTime'] ?? 60 ) ) );
$newest_first = false !== ( $attributes['sortNewestFirst'] ?? true );
$new_tab     = false !== ( $attributes['openInNewTab'] ?? true );

// --- Element toggles. ---
$show_image     = false !== ( $attributes['showImage'] ?? true );
$show_title     = false !== ( $attributes['showTitle'] ?? true );
$title_tag      = in_array( $attributes['titleTag'] ?? 'h3', [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' ], true ) ? $attributes['titleTag'] : 'h3';
$show_meta      = false !== ( $attributes['showMeta'] ?? true );
$show_date      = false !== ( $attributes['showDate'] ?? true );
$show_author    = false !== ( $attributes['showAuthor'] ?? true );
$show_source    = ! empty( $attributes['showSource'] );
$show_excerpt   = false !== ( $attributes['showExcerpt'] ?? true );
$excerpt_length = max( 0, (int) ( $attributes['excerptLength'] ?? 20 ) );
$show_read_more = false !== ( $attributes['showReadMore'] ?? true );
$read_more_text = (string) ( $attributes['readMoreText'] ?? '' );
$read_more_text = '' !== trim( $read_more_text ) ? $read_more_text : __( 'Read more', 'flexa-block' );

// --- Pagination (client-side over the fetched pool). ---
$pag_type       = in_array( $attributes['paginationType'] ?? 'none', [ 'none', 'numbered', 'loadmore' ], true ) ? $attributes['paginationType'] : 'none';
$per_page       = max( 1, min( 30, (int) ( $attributes['perPage'] ?? 6 ) ) );
$load_more_text = (string) ( $attributes['loadMoreText'] ?? '' );
$load_more_text = '' !== trim( $load_more_text ) ? $load_more_text : __( 'Load more', 'flexa-block' );

// --- Wrapper classes + attributes. ---
$classes = [ 'flexa-rss', 'flexa-rss--' . $layout, 'flexa-rss--' . $container_type ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-rss-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
// Pagination config for the view script (no-JS shows every item; JS pages them).
if ( 'none' !== $pag_type ) {
	$wrapper_args['data-flexa-rss-pagination'] = $pag_type;
	$wrapper_args['data-per-page']             = (string) $per_page;
	$wrapper_args['data-loadmore-rows']        = '2';
	if ( 'numbered' === $pag_type ) {
		$prev = trim( (string) ( $attributes['prevLabel'] ?? '' ) );
		$next = trim( (string) ( $attributes['nextLabel'] ?? '' ) );
		$wrapper_args['data-prev-label'] = '' !== $prev ? $prev : '‹';
		$wrapper_args['data-next-label'] = '' !== $next ? $next : '›';
	}
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

$link_target = $new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
$date_format = (string) get_option( 'date_format' );

// Inline aspect-ratio for the "no image" placeholder so it matches the chosen ratio.
$image_ratio = trim( (string) ( $attributes['imageRatio'] ?? '' ) );
$ratio_style = ( '' !== $image_ratio && preg_match( '#^[0-9]+\s*/\s*[0-9]+$#', $image_ratio ) ) ? 'aspect-ratio:' . $image_ratio : '';

// --- Fetch + normalise the feed (shared with the editor preview). ---
$items = \Flexa\Block\Rss_Feed::items( $feed_url, $items_count, $newest_first, $cache_min );

// --- Build the item cards. ---
$cards_html = '';
foreach ( $items as $item ) {
	$permalink = esc_url( (string) $item['link'] );

	// Thumbnail when the item carried an image URL — otherwise a neutral
	// placeholder glyph so cards keep a consistent height/layout.
	$image_html = '';
	if ( $show_image ) {
		if ( '' !== (string) $item['image'] ) {
			$img        = '<img class="flexa-rss__image" src="' . esc_url( (string) $item['image'] ) . '" alt="" loading="lazy" decoding="async" />';
			$image_html = '<div class="flexa-rss__image-wrap"><a class="flexa-rss__image-link" href="' . $permalink . '"' . $link_target . ' tabindex="-1" aria-hidden="true">' . $img . '</a></div>';
		} else {
			$image_html = '<div class="flexa-rss__image-wrap">' . HTML_Helpers::image_placeholder( '', $ratio_style ) . '</div>';
		}
	}

	// Meta line: date · author · source.
	$meta_html = '';
	if ( $show_meta ) {
		$parts = [];
		if ( $show_date && (int) $item['timestamp'] > 0 ) {
			$ts      = (int) $item['timestamp'];
			$parts[] = '<time class="flexa-rss__date" datetime="' . esc_attr( gmdate( 'c', $ts ) ) . '">' . esc_html( date_i18n( $date_format, $ts ) ) . '</time>';
		}
		if ( $show_author && '' !== (string) $item['author'] ) {
			$parts[] = '<span class="flexa-rss__author">' . esc_html( (string) $item['author'] ) . '</span>';
		}
		if ( $show_source && '' !== (string) $item['source'] ) {
			$parts[] = '<span class="flexa-rss__source">' . esc_html( (string) $item['source'] ) . '</span>';
		}
		if ( ! empty( $parts ) ) {
			$meta_html = '<div class="flexa-rss__meta">' . implode( '<span class="flexa-rss__meta-sep" aria-hidden="true">·</span>', $parts ) . '</div>';
		}
	}

	// Title (linked).
	$title_html = '';
	if ( $show_title ) {
		$title_text = trim( (string) $item['title'] );
		if ( '' === $title_text ) {
			$title_text = __( '(untitled)', 'flexa-block' );
		}
		$title_html = '<' . $title_tag . ' class="flexa-rss__title"><a class="flexa-rss__title-link" href="' . $permalink . '"' . $link_target . '>' . esc_html( $title_text ) . '</a></' . $title_tag . '>';
	}

	// Excerpt (already plain text; trimmed to the chosen word count).
	$excerpt_html = '';
	if ( $show_excerpt && $excerpt_length > 0 ) {
		$trimmed = wp_trim_words( (string) $item['description'], $excerpt_length, '…' );
		if ( '' !== trim( $trimmed ) ) {
			$excerpt_html = '<div class="flexa-rss__excerpt">' . esc_html( $trimmed ) . '</div>';
		}
	}

	// Read-more link.
	$read_more_html = '';
	if ( $show_read_more ) {
		$read_more_html = '<a class="flexa-rss__readmore wp-element-button" href="' . $permalink . '"' . $link_target . '>' . esc_html( $read_more_text ) . '</a>';
	}

	$body_html   = '<div class="flexa-rss__body">' . $meta_html . $title_html . $excerpt_html . $read_more_html . '</div>';
	$cards_html .= '<article class="flexa-rss__item">' . $image_html . $body_html . '</article>';
}

// --- Pagination control (filled/toggled by view.js; no-JS shows every item). ---
$pagination_html = '';
if ( '' !== $cards_html && 'numbered' === $pag_type ) {
	$pagination_html = '<nav class="flexa-pagination" aria-label="' . esc_attr__( 'Feed navigation', 'flexa-block' ) . '"></nav>';
} elseif ( '' !== $cards_html && 'loadmore' === $pag_type ) {
	$pagination_html = '<div class="flexa-pagination-loadmore"><button type="button" class="flexa-pagination-loadmore__btn wp-element-button">' . esc_html( $load_more_text ) . '</button></div>';
}

// --- Body: the cards, or a graceful empty state. ---
if ( '' !== $cards_html ) {
	$inner_html = '<div class="flexa-rss__grid">' . $cards_html . '</div>' . $pagination_html;
} elseif ( '' === $feed_url ) {
	$inner_html = '<p class="flexa-rss__empty">' . esc_html__( 'Add a feed URL to show entries.', 'flexa-block' ) . '</p>';
} else {
	$inner_html = '<p class="flexa-rss__empty">' . esc_html__( 'No feed entries could be loaded.', 'flexa-block' ) . '</p>';
}

printf(
	'<%1$s %2$s%3$s><div class="flexa-rss__inner">%4$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$inner_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- titles/dates/excerpts esc_html'd, urls esc_url'd, image markup built with escaped src.
);
