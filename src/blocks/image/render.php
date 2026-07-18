<?php
/**
 * Image block — server-side render.
 *
 * CSS is generated at save time by Image_CSS and printed inline on the front
 * end. This file outputs the figure > frame > img structure with an optional
 * overlay, caption and link/lightbox wiring. Output is escaped and the caption
 * text is passed through wp_kses with a small inline whitelist.
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
$image    = $attributes['image'] ?? [];
$img_url  = (string) ( $image['url'] ?? '' );

// Nothing to render without a block id or an image.
if ( '' === $block_id || '' === $img_url ) {
	return;
}

$anchor       = $attributes['anchor'] ?? '';
$image_id     = $image['id'] ?? null;
$image_size   = $attributes['imageSize'] ?? 'large';
$lazy_load    = ! empty( $attributes['lazyLoad'] );
$object_fit   = $attributes['objectFit'] ?? 'cover';
$aspect       = $attributes['aspectRatio'] ?? [];
$hover_effect = (string) ( $attributes['hoverEffect'] ?? 'none' );
$overlay      = $attributes['overlay'] ?? [];
$mask         = $attributes['mask'] ?? [];
$caption      = $attributes['caption'] ?? [];
$click_action = (string) ( $attributes['clickAction'] ?? 'none' );
$link         = $attributes['link'] ?? [];
$lightbox     = $attributes['lightbox'] ?? [];
$alt_text     = (string) ( $attributes['altText'] ?? '' );

// Resolve the sized URL + intrinsic dimensions from the attachment when we can.
$img_width  = $image['width'] ?? null;
$img_height = $image['height'] ?? null;
$full_url   = $img_url;
if ( $image_id ) {
	$sized = wp_get_attachment_image_src( (int) $image_id, $image_size );
	if ( $sized ) {
		$img_url    = $sized[0];
		$img_width  = $sized[1];
		$img_height = $sized[2];
	}
	$full = wp_get_attachment_image_src( (int) $image_id, 'full' );
	if ( $full ) {
		$full_url = $full[0];
	}
}

$alt = '' !== $alt_text ? $alt_text : (string) ( $image['alt'] ?? '' );

// Caption text: custom string, or the attachment's own caption.
$caption_show = ! empty( $caption['show'] );
$caption_text = '';
if ( $caption_show ) {
	if ( 'attachment' === ( $caption['source'] ?? 'custom' ) && $image_id ) {
		$caption_text = (string) wp_get_attachment_caption( (int) $image_id );
	} else {
		$caption_text = (string) ( $caption['text'] ?? '' );
	}
}
$caption_show    = $caption_show && '' !== trim( $caption_text );
$caption_display = 'overlay' === ( $caption['display'] ?? 'below' ) ? 'overlay' : 'below';
$caption_pos     = in_array( $caption['position'] ?? 'bottom', [ 'top', 'center', 'bottom' ], true ) ? $caption['position'] : 'bottom';

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
$mask_shape   = (string) ( $mask['shape'] ?? 'none' );
$has_mask     = 'none' !== $mask_shape;
$built_in     = [ 'circle', 'ellipse', 'rounded', 'triangle', 'diamond', 'hexagon', 'pentagon', 'octagon', 'star', 'heart', 'blob' ];
$is_built_in  = $has_mask && in_array( $mask_shape, $built_in, true );

$classes = [ 'flexa-image', 'flexa-image-' . sanitize_html_class( $block_id ) ];
if ( 'none' !== $hover_effect ) {
	$classes[] = 'flexa-image--hover-' . sanitize_html_class( $hover_effect );
}
if ( $has_mask ) {
	$classes[] = 'flexa-image--masked';
}
if ( $is_built_in ) {
	$classes[] = 'flexa-image--mask-' . sanitize_html_class( $mask_shape );
}
if ( ! empty( $aspect['enabled'] ) ) {
	$classes[] = 'flexa-image--has-ratio';
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

// Click behaviour data attributes (link uses an <a>, so no data needed).
$click_attrs = '';
if ( 'lightbox' === $click_action || 'media' === $click_action ) {
	$click_attrs  = ' data-click="' . esc_attr( $click_action ) . '"';
	$click_attrs .= ' data-full="' . esc_url( $full_url ) . '"';
	if ( 'lightbox' === $click_action ) {
		$lb_bg      = $lightbox['background']['light'] ?? '';
		$lb_bg_dark = $lightbox['background']['dark'] ?? '';
		if ( '' !== $lb_bg ) {
			$click_attrs .= ' data-lightbox-bg="' . esc_attr( $lb_bg ) . '"';
		}
		if ( '' !== $lb_bg_dark ) {
			$click_attrs .= ' data-lightbox-bg-dark="' . esc_attr( $lb_bg_dark ) . '"';
		}
		if ( false !== ( $lightbox['showCaption'] ?? true ) && '' !== $caption_text ) {
			$click_attrs .= ' data-lightbox-caption="' . esc_attr( wp_strip_all_tags( $caption_text ) ) . '"';
		}
	}
}

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Build the <img>.
$img_attr = ' src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $alt ) . '" class="flexa-image__img"';
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

// Overlay + overlay-mode caption live inside the frame.
$overlay_html = '';
if ( 'none' !== ( $overlay['type'] ?? 'none' ) ) {
	$overlay_html = '<span class="flexa-image__overlay" aria-hidden="true"></span>';
}

$overlay_caption = '';
if ( $caption_show && 'overlay' === $caption_display ) {
	$overlay_caption = '<figcaption class="flexa-image__caption flexa-image__caption--' . esc_attr( $caption_pos ) . '">' . wp_kses( $caption_text, $inline_allowed ) . '</figcaption>';
}

$frame_html = '<div class="flexa-image__frame">' . $img_html . $overlay_html . $overlay_caption . '</div>';

// Link mode wraps the frame in an anchor.
if ( 'link' === $click_action && ! empty( $link['url'] ) ) {
	$href   = esc_url( $link['url'] );
	$target = '_blank' === ( $link['target'] ?? '' ) ? ' target="_blank"' : '';
	$rel    = trim( (string) ( $link['rel'] ?? '' ) );
	if ( '_blank' === ( $link['target'] ?? '' ) && '' === $rel ) {
		$rel = 'noopener noreferrer';
	}
	$rel_attr = '';
	if ( '' !== $rel ) {
		$rel      = trim( (string) preg_replace( '/[^a-z0-9 _-]/i', '', $rel ) );
		$rel_attr = ' rel="' . esc_attr( $rel ) . '"';
	}
	$frame_html = '<a class="flexa-image__link" href="' . $href . '"' . $target . $rel_attr . '>' . $frame_html . '</a>';
}

// Below-mode caption sits outside the frame / link.
$below_caption = '';
if ( $caption_show && 'below' === $caption_display ) {
	$below_caption = '<figcaption class="flexa-image__caption flexa-image__caption--below">' . wp_kses( $caption_text, $inline_allowed ) . '</figcaption>';
}

printf(
	'<figure %1$s%2$s%3$s>%4$s%5$s</figure>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$click_attrs,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values escaped with esc_attr/esc_url above.
	$frame_html,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- src/alt/href escaped, caption wp_kses'd above.
	$below_caption       // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- caption wp_kses'd above.
);
