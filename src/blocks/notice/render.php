<?php
/**
 * Notice block — server-side render.
 *
 * CSS is generated at save time by Notice_CSS and printed inline on the front
 * end. This file outputs the notice: an optional icon (a chosen glyph or a
 * per-type default), an optional title, the message body and an optional dismiss
 * button. All text is escaped (wp_kses with a small inline whitelist); icons run
 * through HTML_Helpers::icon_html / svg_kses. The notice keeps the theme's
 * typography unless the user overrode it.
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
$html_tag = HTML_Helpers::get_html_tag( $attributes );

$notice_type = in_array( $attributes['noticeType'] ?? 'info', [ 'info', 'success', 'warning', 'danger', 'default' ], true )
	? $attributes['noticeType']
	: 'info';

// Title element tag — whitelist so only a known text tag reaches the markup.
$text_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'strong', 'div' ];
$title_tag = in_array( $attributes['titleTag'] ?? 'strong', $text_tags, true ) ? $attributes['titleTag'] : 'strong';

$show_icon   = false !== ( $attributes['showIcon'] ?? true );
$show_title  = false !== ( $attributes['showTitle'] ?? true );
$dismissible = ! empty( $attributes['dismissible'] );
$remember    = $dismissible && ! empty( $attributes['rememberDismiss'] );

$title   = (string) ( $attributes['title'] ?? '' );
$message = (string) ( $attributes['content'] ?? '' );

// Inline formatting allowed inside the title / message.
$inline_allowed = [
	'strong' => [],
	'em'     => [],
	'b'      => [],
	'i'      => [],
	'br'     => [],
	'span'   => [],
];
$message_allowed = $inline_allowed + [ 'a' => [ 'href' => true, 'target' => true, 'rel' => true ] ];

// Per-type default glyphs (mirrors DEFAULT_ICONS in edit.tsx). Used when the user
// has not picked a custom icon. Each runs through svg_kses before output.
$default_icons = [
	'info'    => '<svg class="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>',
	'success' => '<svg class="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>',
	'warning' => '<svg class="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 2 20h20L12 3z"/><path d="M12 10v4M12 18h.01"/></svg>',
	'danger'  => '<svg class="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
	'default' => '<svg class="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>',
];

// --- Icon ------------------------------------------------------------------
$icon_html = '';
if ( $show_icon ) {
	$icon      = $attributes['icon'] ?? [];
	$chosen    = HTML_Helpers::icon_html( $icon );
	$icon_svg  = '' !== $chosen ? $chosen : HTML_Helpers::svg_kses( $default_icons[ $notice_type ] ?? $default_icons['info'] );
	if ( '' !== $icon_svg ) {
		$icon_html = '<span class="flexa-notice__icon">' . $icon_svg . '</span>';
	}
}

// --- Text pieces -----------------------------------------------------------
$title_html = ( $show_title && '' !== trim( $title ) )
	? '<' . $title_tag . ' class="flexa-notice__title">' . wp_kses( $title, $inline_allowed ) . '</' . $title_tag . '>'
	: '';

$message_html = '' !== trim( $message )
	? '<div class="flexa-notice__content">' . wp_kses( $message, $message_allowed ) . '</div>'
	: '';

$body_inner = $title_html . $message_html;
if ( '' === $body_inner && '' === $icon_html ) {
	return;
}
$body_html = '<div class="flexa-notice__body">' . $body_inner . '</div>';

// --- Dismiss button --------------------------------------------------------
$dismiss_html = $dismissible
	? '<button type="button" class="flexa-notice__dismiss" aria-label="' . esc_attr__( 'Dismiss', 'flexa-block' ) . '"><span aria-hidden="true">&times;</span></button>'
	: '';

// --- Wrapper ---------------------------------------------------------------
$classes = [ 'flexa-notice', 'flexa-notice--' . $notice_type ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-notice-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Dismiss behaviour: view.js hides the wrapper on click; when "remember" is on it
// persists the dismissal under `flexa-notice-<id>` in localStorage.
$dismiss_attrs = '';
if ( $dismissible && '' !== $block_id ) {
	$dismiss_attrs = ' data-flexa-notice="' . esc_attr( sanitize_html_class( $block_id ) ) . '"';
	if ( $remember ) {
		$dismiss_attrs .= ' data-remember="1"';
	}
}

// Lazy background: mark the wrapper so view.js reveals the image near the viewport.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

printf(
	'<%1$s %2$s%3$s%4$s%5$s><div class="flexa-notice__inner">%6$s%7$s%8$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$dismiss_attrs,      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- id sanitized, static literal remember flag.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$icon_html,          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- icon via svg_kses.
	$body_html,          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text wp_kses'd above.
	$dismiss_html        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup, label escaped above.
);
