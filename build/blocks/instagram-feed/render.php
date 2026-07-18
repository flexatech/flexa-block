<?php
/**
 * Instagram Feed block — server-side render.
 *
 * CSS is generated at save time by Instagram_Feed_CSS and printed inline on the
 * front end. This file fetches the account's media with the Instagram Graph API
 * (Instagram_Feed::media, transient-cached; the access token stays server-side)
 * and outputs a grid / overlay / card / carousel of media items (image, optional
 * caption, date and profile name, each optionally linking to the post). Every
 * dynamic value is escaped on output.
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
use Flexa\Block\Instagram_Feed;

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';
$html_tag = HTML_Helpers::get_html_tag( $attributes, 'section' );

$container_type = 'full-width' === ( $attributes['containerType'] ?? 'boxed' ) ? 'full-width' : 'boxed';
$layout         = in_array( $attributes['feedLayout'] ?? 'grid', [ 'grid', 'overlay', 'card', 'carousel' ], true ) ? $attributes['feedLayout'] : 'grid';
$is_overlay     = 'overlay' === $layout;

// --- Source: the token comes from the admin-only server-side store (never from
//     the block/post content), so it can't leak to the front end. ---
$token        = \Flexa\Block\Feed_Tokens::get( 'instagram' );
$count        = max( 1, min( 50, (int) ( $attributes['numberOfImages'] ?? 8 ) ) );
$cache_min    = max( 5, min( 1440, (int) ( $attributes['cacheTime'] ?? 30 ) ) );
$newest_first = false !== ( $attributes['sortNewestFirst'] ?? true );

// --- Content toggles. ---
$square        = false !== ( $attributes['squareThumbnail'] ?? true );
$show_caption  = false !== ( $attributes['showCaption'] ?? true );
$caption_limit = max( 0, (int) ( $attributes['captionLimit'] ?? 15 ) );
$show_meta     = ! empty( $attributes['showMeta'] );
$show_profile  = ! empty( $attributes['showProfile'] );
$enable_link   = false !== ( $attributes['enableLink'] ?? true );
$new_tab       = false !== ( $attributes['openInNewTab'] ?? true );

// --- Wrapper classes + attributes. ---
$classes = [ 'flexa-instagram-feed', 'flexa-instagram-feed--' . $layout, 'flexa-instagram-feed--' . $container_type ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-instagram-feed-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

$link_target = $new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
// The exact post time: the site's date + time formats combined.
$date_format = trim( (string) get_option( 'date_format' ) . ' ' . (string) get_option( 'time_format' ) );
// Square thumbnails: inline the ratio on the "no image" placeholder so it matches
// the images (the real <img> gets the ratio from the generated CSS).
$square_style = $square ? 'aspect-ratio:1;object-fit:cover' : '';

// --- Fetch + normalise the media (shared with the editor preview). ---
$media = Instagram_Feed::media( $token, $count, $newest_first, $cache_min );

// --- Build the item cards. ---
$items_html = '';
foreach ( $media as $item ) {
	$permalink = esc_url( (string) $item['permalink'] );

	// Image (real thumbnail, or a neutral placeholder glyph so items keep height).
	if ( '' !== (string) $item['image'] ) {
		$image_html = '<img class="flexa-instagram-feed__image" src="' . esc_url( (string) $item['image'] ) . '" alt="" loading="lazy" decoding="async" />';
	} else {
		$image_html = HTML_Helpers::image_placeholder( 'flexa-instagram-feed__image', $square_style );
	}

	// Optionally wrap the image in a link to the Instagram post.
	if ( $enable_link && '' !== $permalink ) {
		$media_html = '<a class="flexa-instagram-feed__link" href="' . $permalink . '"' . $link_target . '>' . $image_html . '</a>';
	} else {
		$media_html = '<span class="flexa-instagram-feed__link">' . $image_html . '</span>';
	}

	// Profile / caption / meta text parts (respecting the toggles).
	$profile_html = '';
	if ( $show_profile && '' !== (string) $item['username'] ) {
		$profile_html = '<div class="flexa-instagram-feed__profile">@' . esc_html( (string) $item['username'] ) . '</div>';
	}

	$caption_html = '';
	if ( $show_caption && $caption_limit > 0 ) {
		$trimmed = wp_trim_words( (string) $item['caption'], $caption_limit, '…' );
		if ( '' !== trim( $trimmed ) ) {
			$caption_html = '<div class="flexa-instagram-feed__caption">' . esc_html( $trimmed ) . '</div>';
		}
	}

	$meta_html = '';
	if ( $show_meta && (int) $item['timestamp'] > 0 ) {
		$ts        = (int) $item['timestamp'];
		$meta_html = '<time class="flexa-instagram-feed__meta" datetime="' . esc_attr( gmdate( 'c', $ts ) ) . '">'
			. HTML_Helpers::clock_icon( 'flexa-instagram-feed__meta-icon' )
			. esc_html( date_i18n( $date_format, $ts ) ) . '</time>';
	}

	// Profile name + date share one line (the byline). Overlay layout gathers the
	// text over the image; other layouts stack it below in a __body wrapper. Both
	// carry the content padding + gap from the generator.
	$byline_sep  = ( '' !== $profile_html && '' !== $meta_html ) ? '<span class="flexa-instagram-feed__byline-sep" aria-hidden="true">·</span>' : '';
	$byline_html = ( '' !== $profile_html || '' !== $meta_html ) ? '<div class="flexa-instagram-feed__byline">' . $profile_html . $byline_sep . $meta_html . '</div>' : '';
	$text_html   = $byline_html . $caption_html;
	if ( '' !== $text_html ) {
		$text_class = $is_overlay ? 'flexa-instagram-feed__overlay' : 'flexa-instagram-feed__body';
		$text_html  = '<div class="' . $text_class . '">' . $text_html . '</div>';
	}

	// Video / album (carousel) badge over the thumbnail.
	$badge_html  = HTML_Helpers::media_badge( (string) $item['type'], 'flexa-instagram-feed__media-badge' );
	$items_html .= '<div class="flexa-instagram-feed__item">' . $media_html . $badge_html . $text_html . '</div>';
}

// --- Body: the items, or a graceful empty state. ---
if ( '' !== $items_html ) {
	$inner_html = '<div class="flexa-instagram-feed__grid">' . $items_html . '</div>';
} elseif ( '' === $token ) {
	$inner_html = '<p class="flexa-instagram-feed__empty">' . esc_html__( 'Add an Instagram access token to show media.', 'flexa-block' ) . '</p>';
} else {
	$inner_html = '<p class="flexa-instagram-feed__empty">' . esc_html__( 'No media could be loaded.', 'flexa-block' ) . '</p>';
}

printf(
	'<%1$s %2$s%3$s><div class="flexa-instagram-feed__inner">%4$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$inner_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- captions/dates/usernames esc_html'd, urls esc_url'd, image markup built with escaped src.
);
