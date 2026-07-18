<?php
/**
 * Video Popup block — server-side render.
 *
 * CSS is generated at save time by Video_Popup_CSS and printed inline on the
 * front end. This file outputs the trigger (thumbnail / button / text) plus a
 * hidden lightbox container; view.ts injects the iframe/<video> into the player
 * on demand and removes it on close (so the video stops). The provider URL is
 * parsed to an embed base here and travels to view.ts in escaped data-*
 * attributes. Output is escaped; the play icon runs through HTML_Helpers::svg_kses.
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
if ( '' === $block_id ) {
	return;
}

$anchor  = $attributes['anchor'] ?? '';
$source  = in_array( $attributes['videoSource'] ?? 'youtube', [ 'youtube', 'vimeo', 'mp4' ], true ) ? $attributes['videoSource'] : 'youtube';
$url     = trim( (string) ( $attributes['videoUrl'] ?? '' ) );
$display = 'inline' === ( $attributes['displayMode'] ?? 'popup' ) ? 'inline' : 'popup';
$trigger = in_array( $attributes['trigger'] ?? 'thumbnail', [ 'thumbnail', 'button', 'text' ], true ) ? $attributes['trigger'] : 'thumbnail';

// Inline mode always uses the thumbnail façade — it turns into the player in
// place, so the button / text triggers (which belong to the popup) don't apply.
if ( 'inline' === $display ) {
	$trigger = 'thumbnail';
}

// --- Resolve the embed base URL + (for YouTube) a fallback thumbnail. --------
// The auto thumbnail uses maxresdefault.jpg (true 16:9, no baked-in letterbox
// bars). hqdefault.jpg is 4:3 with black bars, which show through at 4:3 / 1:1.
// When maxres is missing YouTube returns a 120×90 grey image (HTTP 200, so no
// onerror fires) — view.ts detects that by naturalWidth and swaps to $yt_thumb_fb.
$embed        = '';
$yt_thumb     = '';
$yt_thumb_fb  = '';
if ( 'youtube' === $source ) {
	if ( preg_match( '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
		$embed       = 'https://www.youtube.com/embed/' . $m[1] . '?rel=0&playsinline=1';
		$yt_thumb    = 'https://img.youtube.com/vi/' . $m[1] . '/maxresdefault.jpg';
		$yt_thumb_fb = 'https://img.youtube.com/vi/' . $m[1] . '/mqdefault.jpg';
	}
} elseif ( 'vimeo' === $source ) {
	if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~', $url, $m ) ) {
		$embed = 'https://player.vimeo.com/video/' . $m[1];
	}
} elseif ( 'mp4' === $source ) {
	$embed = $url;
}

// Nothing playable → don't render an empty, non-functional trigger.
if ( '' === $embed ) {
	return;
}

$autoplay = false !== ( $attributes['autoplay'] ?? true );

// --- Play icon (shared with edit.tsx's fallback triangle). -------------------
$play_glyph = '<svg class="flexa-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="M8 5v14l11-7z" fill="currentColor" /></svg>';
$icon       = $attributes['playIcon'] ?? [];
$icon_html  = '';
if ( 'upload' === ( $icon['source'] ?? 'none' ) && '' !== (string) ( $icon['url'] ?? '' ) ) {
	$icon_html = '<img class="flexa-icon" src="' . esc_url( $icon['url'] ) . '" alt="" width="24" height="24" loading="lazy" />';
} elseif ( '' !== (string) ( $icon['markup'] ?? '' ) ) {
	$icon_html = HTML_Helpers::svg_kses( $icon['markup'] );
}
if ( '' === $icon_html ) {
	$icon_html = $play_glyph;
}
$play_html = '<span class="flexa-video-popup__play" aria-hidden="true">' . $icon_html . '</span>';

// --- Trigger markup ----------------------------------------------------------
$aria_label = esc_attr__( 'Play video', 'flexa-block' );

if ( 'button' === $trigger ) {
	$label        = (string) ( $attributes['buttonText'] ?? 'Play Video' );
	$trigger_html = '<button type="button" class="flexa-video-popup__trigger flexa-video-popup__button wp-element-button" aria-label="' . $aria_label . '">'
		. $play_html
		. '<span class="flexa-video-popup__button-text">' . esc_html( $label ) . '</span>'
		. '</button>';
} elseif ( 'text' === $trigger ) {
	$label        = (string) ( $attributes['linkText'] ?? 'Watch the video' );
	$trigger_html = '<button type="button" class="flexa-video-popup__trigger flexa-video-popup__text" aria-label="' . $aria_label . '">'
		. $play_html
		. '<span class="flexa-video-popup__text-label">' . esc_html( $label ) . '</span>'
		. '</button>';
} else {
	$thumb    = $attributes['thumbnailImage'] ?? [];
	$thumb_url = (string) ( $thumb['url'] ?? '' );
	$thumb_alt = (string) ( $thumb['alt'] ?? '' );
	// Only the auto YouTube thumbnail carries a fallback (view.ts swaps it when
	// maxresdefault is missing); a user-picked image never does.
	$thumb_fb = '';
	if ( '' === $thumb_url && '' !== $yt_thumb ) {
		$thumb_url = $yt_thumb;
		$thumb_fb  = $yt_thumb_fb;
	}

	$fb_attr    = '' !== $thumb_fb ? ' data-vp-fallback="' . esc_url( $thumb_fb ) . '"' : '';
	$image_html = '' !== $thumb_url
		? '<img class="flexa-video-popup__image" src="' . esc_url( $thumb_url ) . '" alt="' . esc_attr( $thumb_alt ) . '" loading="lazy" decoding="async"' . $fb_attr . ' />'
		: '<span class="flexa-video-popup__placeholder" aria-hidden="true"></span>';

	$trigger_html = '<button type="button" class="flexa-video-popup__trigger flexa-video-popup__thumb" aria-label="' . $aria_label . '">'
		. '<span class="flexa-video-popup__media">'
		. $image_html
		. '<span class="flexa-video-popup__scrim" aria-hidden="true"></span>'
		. $play_html
		. '</span>'
		. '</button>';
}

// --- Hidden lightbox container (popup mode only; view.ts injects the player on
// demand). Inline mode plays inside the thumbnail media instead, so no lightbox.
$lightbox_html = '';
if ( 'inline' !== $display ) {
	$lightbox_html = '<div class="flexa-video-popup__lightbox" role="dialog" aria-modal="true" aria-label="' . esc_attr__( 'Video', 'flexa-block' ) . '" hidden>'
		. '<div class="flexa-video-popup__backdrop"></div>'
		. '<div class="flexa-video-popup__dialog">'
		. '<button type="button" class="flexa-video-popup__close" aria-label="' . esc_attr__( 'Close', 'flexa-block' ) . '">&times;</button>'
		. '<div class="flexa-video-popup__player"></div>'
		. '</div>'
		. '</div>';
}

// --- Wrapper -----------------------------------------------------------------
$classes = [
	'flexa-video-popup',
	'flexa-video-popup--' . $trigger,
	'flexa-video-popup-' . sanitize_html_class( $block_id ),
];
if ( 'inline' === $display ) {
	$classes[] = 'flexa-video-popup--inline';
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

$video_attrs  = ' data-video-type="' . esc_attr( $source ) . '"';
$video_attrs .= ' data-video-src="' . esc_url( $embed ) . '"';
$video_attrs .= ' data-autoplay="' . ( $autoplay ? '1' : '0' ) . '"';
$video_attrs .= ' data-display-mode="' . esc_attr( $display ) . '"';

printf(
	'<div %1$s%2$s%3$s>%4$s%5$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$video_attrs,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values escaped with esc_attr/esc_url above.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$trigger_html,       // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- icon via svg_kses, labels esc_html'd, src escaped above.
	$lightbox_html       // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup + esc_attr'd labels above.
);
