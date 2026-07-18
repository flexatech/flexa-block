<?php
/**
 * Images Gallery block — server-side render.
 *
 * CSS is generated at save time by Images_Gallery_CSS and printed inline on the
 * front end. This file outputs the items > item > figure > img structure with an
 * optional overlay and caption per image, plus the click/lightbox wiring read by
 * view.js. Output is escaped and caption text passes through wp_kses with a small
 * inline whitelist.
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
$images   = $attributes['images'] ?? [];

// Nothing to render without a block id or at least one image.
if ( '' === $block_id || ! is_array( $images ) || empty( $images ) ) {
	return;
}

$anchor       = $attributes['anchor'] ?? '';
$layout       = in_array( $attributes['galleryLayout'] ?? 'grid', [ 'grid', 'masonry', 'tiled' ], true ) ? $attributes['galleryLayout'] : 'grid';
$image_size   = $attributes['imageSize'] ?? 'large';
$lazy_load    = ! empty( $attributes['lazyLoad'] );
$hover_effect = (string) ( $attributes['hoverEffect'] ?? 'none' );
$overlay      = $attributes['overlay'] ?? [];
$caption      = $attributes['caption'] ?? [];
$click_action = in_array( $attributes['clickAction'] ?? 'lightbox', [ 'none', 'lightbox', 'media' ], true ) ? $attributes['clickAction'] : 'lightbox';
$lightbox     = $attributes['lightbox'] ?? [];

$caption_show    = ! empty( $caption['show'] );
$caption_display = 'below' === ( $caption['display'] ?? 'overlay' ) ? 'below' : 'overlay';
$caption_pos     = in_array( $caption['position'] ?? 'bottom', [ 'top', 'center', 'bottom' ], true ) ? $caption['position'] : 'bottom';
$caption_hover   = 'hover' === ( $caption['visibility'] ?? 'always' );

$has_overlay = 'none' !== ( $overlay['type'] ?? 'none' );

$inline_allowed = [
	'strong' => [],
	'em'     => [],
	'b'      => [],
	'i'      => [],
	'br'     => [],
	'span'   => [],
	'a'      => [ 'href' => true, 'target' => true, 'rel' => true ],
];

// Wrapper classes.
$classes = [ 'flexa-images-gallery', 'flexa-images-gallery-' . sanitize_html_class( $block_id ), 'flexa-images-gallery--' . sanitize_html_class( $layout ) ];
if ( 'none' !== $hover_effect ) {
	$classes[] = 'flexa-images-gallery--hover-' . sanitize_html_class( $hover_effect );
}
if ( $caption_show && 'overlay' === $caption_display ) {
	$classes[] = 'flexa-images-gallery--cap-' . sanitize_html_class( $caption_pos );
	if ( $caption_hover ) {
		$classes[] = 'flexa-images-gallery--cap-hover';
	}
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Click / lightbox wiring on the wrapper.
$click_attrs = '';
if ( 'lightbox' === $click_action || 'media' === $click_action ) {
	$click_attrs = ' data-click="' . esc_attr( $click_action ) . '"';
	if ( 'lightbox' === $click_action ) {
		$lb_bg      = $lightbox['background']['light'] ?? '';
		$lb_bg_dark = $lightbox['background']['dark'] ?? '';
		if ( '' !== $lb_bg ) {
			$click_attrs .= ' data-lightbox-bg="' . esc_attr( $lb_bg ) . '"';
		}
		if ( '' !== $lb_bg_dark ) {
			$click_attrs .= ' data-lightbox-bg-dark="' . esc_attr( $lb_bg_dark ) . '"';
		}
		if ( false === ( $lightbox['showCaption'] ?? true ) ) {
			$click_attrs .= ' data-lightbox-no-caption="1"';
		}
	}
}

// Build each item.
$items_html = '';
foreach ( $images as $image ) {
	if ( ! is_array( $image ) ) {
		continue;
	}
	$img_url = (string) ( $image['url'] ?? '' );
	$img_id  = $image['id'] ?? null;
	$alt     = (string) ( $image['alt'] ?? '' );

	// Caption comes live from the attachment's own "Caption" field in the media
	// library; the stored value is only a fallback for id-less external images.
	$cap_txt = '';
	if ( $img_id ) {
		$cap_txt = (string) wp_get_attachment_caption( (int) $img_id );
	}
	if ( '' === $cap_txt ) {
		$cap_txt = (string) ( $image['caption'] ?? '' );
	}

	$img_width  = $image['width'] ?? null;
	$img_height = $image['height'] ?? null;
	$full_url   = $img_url;

	if ( $img_id ) {
		$sized = wp_get_attachment_image_src( (int) $img_id, $image_size );
		if ( $sized ) {
			$img_url    = $sized[0];
			$img_width  = $sized[1];
			$img_height = $sized[2];
		}
		$full = wp_get_attachment_image_src( (int) $img_id, 'full' );
		if ( $full ) {
			$full_url = $full[0];
		}
	}

	if ( '' === $img_url ) {
		continue;
	}

	// <img>.
	$img_attr = ' src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $alt ) . '" class="flexa-images-gallery__image"';
	if ( $img_width ) {
		$img_attr .= ' width="' . absint( $img_width ) . '"';
	}
	if ( $img_height ) {
		$img_attr .= ' height="' . absint( $img_height ) . '"';
	}
	if ( $lazy_load ) {
		$img_attr .= ' loading="lazy" decoding="async"';
	}
	$img_html = '<img' . $img_attr . ' />';

	$overlay_html = $has_overlay ? '<span class="flexa-images-gallery__overlay" aria-hidden="true"></span>' : '';

	$overlay_caption = '';
	$below_caption   = '';
	if ( $caption_show && '' !== trim( $cap_txt ) ) {
		if ( 'overlay' === $caption_display ) {
			$overlay_caption = '<figcaption class="flexa-images-gallery__caption">' . wp_kses( $cap_txt, $inline_allowed ) . '</figcaption>';
		} else {
			$below_caption = '<figcaption class="flexa-images-gallery__caption flexa-images-gallery__caption--below">' . wp_kses( $cap_txt, $inline_allowed ) . '</figcaption>';
		}
	}

	// Per-item click data (full-size URL + caption) feeds the lightbox / media jump.
	$item_data = '';
	if ( 'lightbox' === $click_action || 'media' === $click_action ) {
		$item_data = ' data-full="' . esc_url( $full_url ) . '"';
		if ( 'lightbox' === $click_action && '' !== trim( $cap_txt ) ) {
			$item_data .= ' data-caption="' . esc_attr( wp_strip_all_tags( $cap_txt ) ) . '"';
		}
	}

	$items_html .= '<div class="flexa-images-gallery__item"' . $item_data . '>'
		. '<figure class="flexa-images-gallery__media">' . $img_html . $overlay_html . $overlay_caption . '</figure>'
		. $below_caption
		. '</div>';
}

if ( '' === $items_html ) {
	return;
}

printf(
	'<div %1$s%2$s%3$s><div class="flexa-images-gallery__items">%4$s</div></div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$click_attrs,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values escaped with esc_attr/esc_url above.
	$items_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- src/alt escaped, caption wp_kses'd, data escaped above.
);
