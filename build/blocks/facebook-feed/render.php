<?php
/**
 * Facebook Feed block — server-side render.
 *
 * CSS is generated at save time by Facebook_Feed_CSS and printed inline on the
 * front end. This file fetches the configured Facebook Page's posts with the
 * cached Facebook_Feed helper (Graph API; the Page access token stays server-side)
 * and outputs a grid / list / masonry / carousel of post cards (avatar, page name,
 * timestamp, image, message, reactions / comments / shares). Every dynamic value
 * is escaped on output.
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
use Flexa\Block\Facebook_Feed;

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';
$html_tag = HTML_Helpers::get_html_tag( $attributes, 'section' );

$container_type = 'full-width' === ( $attributes['containerType'] ?? 'boxed' ) ? 'full-width' : 'boxed';
$layout         = in_array( $attributes['feedLayout'] ?? 'grid', [ 'grid', 'list', 'masonry', 'carousel' ], true ) ? $attributes['feedLayout'] : 'grid';

// --- Source: the token comes from the admin-only server-side store (never from
//     the block/post content), so it can't leak to the front end. ---
$token        = \Flexa\Block\Feed_Tokens::get( 'facebook' );
$page_id      = trim( (string) ( $attributes['pageId'] ?? '' ) );
$count        = max( 1, min( 50, (int) ( $attributes['numberOfPosts'] ?? 6 ) ) );
$cache_min    = max( 5, min( 1440, (int) ( $attributes['cacheTime'] ?? 30 ) ) );
$newest_first = false !== ( $attributes['sortNewestFirst'] ?? true );

// --- Content toggles. ---
$show_avatar    = false !== ( $attributes['showAvatar'] ?? true );
$show_page_name = false !== ( $attributes['showPageName'] ?? true );
$show_timestamp = false !== ( $attributes['showTimestamp'] ?? true );
$show_image     = false !== ( $attributes['showImage'] ?? true );
$show_message   = false !== ( $attributes['showMessage'] ?? true );
$message_limit  = max( 0, (int) ( $attributes['messageLimit'] ?? 20 ) );
$show_reactions = false !== ( $attributes['showReactions'] ?? true );
$show_comments  = ! empty( $attributes['showComments'] );
$show_shares    = false !== ( $attributes['showShares'] ?? true );
$enable_link    = false !== ( $attributes['enableLink'] ?? true );
$new_tab        = false !== ( $attributes['openInNewTab'] ?? true );

// --- Pagination (client-side over the fetched pool). ---
$pag_type       = in_array( $attributes['paginationType'] ?? 'none', [ 'none', 'numbered', 'loadmore' ], true ) ? $attributes['paginationType'] : 'none';
$per_page       = max( 1, min( 50, (int) ( $attributes['perPage'] ?? 6 ) ) );
$load_more_text = (string) ( $attributes['loadMoreText'] ?? '' );
$load_more_text = '' !== trim( $load_more_text ) ? $load_more_text : __( 'Load more', 'flexa-block' );

// --- Wrapper classes + attributes. ---
$classes = [ 'flexa-facebook-feed', 'flexa-facebook-feed--' . $layout, 'flexa-facebook-feed--' . $container_type ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-facebook-feed-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
// Pagination config for the view script (no-JS shows every post; JS pages them).
if ( 'none' !== $pag_type ) {
	$wrapper_args['data-flexa-fb-pagination'] = $pag_type;
	$wrapper_args['data-per-page']            = (string) $per_page;
	$wrapper_args['data-loadmore-rows']       = '2';
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
// The exact post time: the site's date + time formats combined.
$date_format = trim( (string) get_option( 'date_format' ) . ' ' . (string) get_option( 'time_format' ) );

// Action glyphs (reactions / comments / shares) — mirror the editor icons.
$icon_reactions = '<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M7 10v11"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>';
$icon_comments  = '<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z"/></svg>';
$icon_shares    = '<svg width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>';

// --- Fetch + normalise the posts (shared with the editor preview). ---
$posts = Facebook_Feed::posts( $token, $page_id, $count, $newest_first, $cache_min );

// --- Build the post cards. ---
$cards_html = '';
foreach ( $posts as $post ) {
	$permalink = esc_url( (string) $post['permalink'] );
	$has_link  = $enable_link && '' !== $permalink;

	// Timestamp (date with a leading clock glyph) — sits under the page name.
	$timestamp_html = '';
	if ( $show_timestamp && (int) $post['timestamp'] > 0 ) {
		$ts             = (int) $post['timestamp'];
		$timestamp_html = '<time class="flexa-facebook-feed__timestamp" datetime="' . esc_attr( gmdate( 'c', $ts ) ) . '">'
			. HTML_Helpers::clock_icon( 'flexa-facebook-feed__meta-icon' )
			. esc_html( date_i18n( $date_format, $ts ) ) . '</time>';
	}

	// Header — avatar beside a text column holding the page name and, under it, the date.
	$header_html = '';
	$avatar_html = ( $show_avatar && '' !== (string) $post['page_avatar'] )
		? '<img class="flexa-facebook-feed__avatar" src="' . esc_url( (string) $post['page_avatar'] ) . '" alt="" loading="lazy" decoding="async" />'
		: '';
	$name_html   = ( $show_page_name && '' !== (string) $post['page_name'] )
		? '<span class="flexa-facebook-feed__page-name">' . esc_html( (string) $post['page_name'] ) . '</span>'
		: '';
	$text_col    = ( '' !== $name_html || '' !== $timestamp_html )
		? '<div class="flexa-facebook-feed__header-text">' . $name_html . $timestamp_html . '</div>'
		: '';
	if ( '' !== $avatar_html || '' !== $text_col ) {
		$header_html = '<div class="flexa-facebook-feed__header">' . $avatar_html . $text_col . '</div>';
	}

	// Image (linked when linking is on) or a neutral placeholder glyph, with a
	// video / album badge over it when the post is a video or a multi-photo album.
	$image_html = '';
	if ( $show_image ) {
		if ( '' !== (string) $post['image'] ) {
			$img = '<img class="flexa-facebook-feed__image" src="' . esc_url( (string) $post['image'] ) . '" alt="" loading="lazy" decoding="async" />';
			if ( $has_link ) {
				$img = '<a class="flexa-facebook-feed__image-link" href="' . $permalink . '"' . $link_target . ' tabindex="-1" aria-hidden="true">' . $img . '</a>';
			}
			$badge      = HTML_Helpers::media_badge( (string) $post['type'], 'flexa-facebook-feed__media-badge' );
			$image_html = '<div class="flexa-facebook-feed__image-wrap">' . $img . $badge . '</div>';
		} else {
			$image_html = '<div class="flexa-facebook-feed__image-wrap">' . HTML_Helpers::image_placeholder() . '</div>';
		}
	}

	// Message (plain text; trimmed to the chosen word count).
	$message_html = '';
	if ( $show_message && $message_limit > 0 && '' !== trim( (string) $post['message'] ) ) {
		$trimmed = wp_trim_words( (string) $post['message'], $message_limit, '…' );
		if ( '' !== trim( $trimmed ) ) {
			$inner = esc_html( $trimmed );
			if ( $has_link ) {
				$inner = '<a class="flexa-facebook-feed__message-link" href="' . $permalink . '"' . $link_target . '>' . $inner . '</a>';
			}
			$message_html = '<div class="flexa-facebook-feed__message">' . $inner . '</div>';
		}
	}

	// Actions — reactions / comments / shares counts.
	$actions_html = '';
	if ( $show_reactions || $show_comments || $show_shares ) {
		$action_parts = '';
		if ( $show_reactions ) {
			$action_parts .= '<span class="flexa-facebook-feed__reactions">' . $icon_reactions . esc_html( number_format_i18n( (int) $post['likes'] ) ) . '</span>';
		}
		if ( $show_comments ) {
			$action_parts .= '<span class="flexa-facebook-feed__comments">' . $icon_comments . esc_html( number_format_i18n( (int) $post['comments'] ) ) . '</span>';
		}
		if ( $show_shares ) {
			$action_parts .= '<span class="flexa-facebook-feed__shares">' . $icon_shares . esc_html( number_format_i18n( (int) $post['shares'] ) ) . '</span>';
		}
		if ( '' !== $action_parts ) {
			$actions_html = '<div class="flexa-facebook-feed__actions">' . $action_parts . '</div>';
		}
	}

	$cards_html .= '<article class="flexa-facebook-feed__item">' . $header_html . $image_html . $message_html . $actions_html . '</article>';
}

// --- Pagination control (filled/toggled by view.js; no-JS shows every post). ---
$pagination_html = '';
if ( '' !== $cards_html && 'numbered' === $pag_type ) {
	$pagination_html = '<nav class="flexa-pagination" aria-label="' . esc_attr__( 'Feed navigation', 'flexa-block' ) . '"></nav>';
} elseif ( '' !== $cards_html && 'loadmore' === $pag_type ) {
	$pagination_html = '<div class="flexa-pagination-loadmore"><button type="button" class="flexa-pagination-loadmore__btn wp-element-button">' . esc_html( $load_more_text ) . '</button></div>';
}

// --- Body: the cards, or a graceful empty state. ---
if ( '' !== $cards_html ) {
	$inner_html = '<div class="flexa-facebook-feed__grid">' . $cards_html . '</div>' . $pagination_html;
} elseif ( '' === $token ) {
	$inner_html = '<p class="flexa-facebook-feed__empty">' . esc_html__( 'Add a Facebook Page access token to show posts.', 'flexa-block' ) . '</p>';
} else {
	$inner_html = '<p class="flexa-facebook-feed__empty">' . esc_html__( 'No posts could be loaded.', 'flexa-block' ) . '</p>';
}

printf(
	'<%1$s %2$s%3$s><div class="flexa-facebook-feed__inner">%4$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$inner_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- messages/dates esc_html'd, urls esc_url'd, image markup built with escaped src.
);
