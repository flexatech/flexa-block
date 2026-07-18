<?php
/**
 * Modal block — server-side render.
 *
 * CSS is generated at save time by Modal_CSS and printed inline on the front end.
 * This file outputs the trigger (button / text / image / icon) plus a hidden
 * modal root — an overlay and a `.flexa-modal__box` (with a close button) that
 * holds the already-rendered InnerBlocks content. The open/close behaviour lives
 * in view.ts, which reads the escaped data-* flags emitted here. All text is
 * escaped; the icon runs through HTML_Helpers::icon_html; urls through esc_url.
 *
 * @package Flexa\Block
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    InnerBlocks content (already rendered by WP).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $attributes, $content provided by WP block API.

use Flexa\Block\HTML_Helpers;

$block_id = $attributes['blockId'] ?? '';
if ( '' === $block_id ) {
	return;
}

$anchor = $attributes['anchor'] ?? '';
$type   = in_array( $attributes['triggerType'] ?? 'button', [ 'button', 'text', 'image', 'icon' ], true ) ? $attributes['triggerType'] : 'button';

// --- Trigger inner markup ----------------------------------------------------
$trigger_inner = '';
if ( 'icon' === $type ) {
	$trigger_inner = HTML_Helpers::icon_html( $attributes['triggerIcon'] ?? [], 32 );
	if ( '' === $trigger_inner ) {
		// Fallback glyph so an icon trigger without a chosen icon is still clickable.
		$trigger_inner = '<span class="flexa-modal__trigger-placeholder" aria-hidden="true">+</span>';
	}
} elseif ( 'image' === $type ) {
	$image = $attributes['triggerImage'] ?? [];
	$url   = (string) ( $image['url'] ?? '' );
	if ( '' !== $url ) {
		$trigger_inner = '<img class="flexa-modal__trigger-image" src="' . esc_url( $url ) . '" alt="' . esc_attr( (string) ( $image['alt'] ?? '' ) ) . '" loading="lazy" decoding="async" />';
	} else {
		$trigger_inner = '<span class="flexa-modal__trigger-placeholder" aria-hidden="true"></span>';
	}
} else {
	$label         = (string) ( $attributes['triggerText'] ?? 'Open' );
	$trigger_inner = '<span class="flexa-modal__trigger-text">' . esc_html( $label ) . '</span>';
}

$trigger_classes = 'flexa-modal__trigger';
if ( 'button' === $type ) {
	$trigger_classes .= ' wp-element-button';
}

$trigger_html = '<button type="button" class="' . esc_attr( $trigger_classes ) . '" aria-haspopup="dialog" aria-expanded="false">'
	. $trigger_inner
	. '</button>';

// --- Hidden modal root -------------------------------------------------------
$close_pos    = in_array( $attributes['closePosition'] ?? 'inside', [ 'inside', 'outside' ], true ) ? $attributes['closePosition'] : 'inside';
$modal_html   = '<div class="flexa-modal__root flexa-modal__root--close-' . esc_attr( $close_pos ) . '" role="dialog" aria-modal="true" aria-hidden="true" hidden>'
	. '<div class="flexa-modal__overlay"></div>'
	. '<div class="flexa-modal__box" role="document">'
	. '<button type="button" class="flexa-modal__close" aria-label="' . esc_attr__( 'Close', 'flexa-block' ) . '">&times;</button>'
	. '<div class="flexa-modal__content">'
	. $content // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- InnerBlocks content already rendered/escaped by WP.
	. '</div>'
	. '</div>'
	. '</div>';

// --- Wrapper -----------------------------------------------------------------
$classes = [
	'flexa-modal',
	'flexa-modal--' . sanitize_html_class( $type ),
	'flexa-modal-' . sanitize_html_class( $block_id ),
];
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Behaviour flags read by view.ts (booleans → "1"|"0").
$modal_attrs  = ' data-flexa-modal-id="' . esc_attr( sanitize_html_class( $block_id ) ) . '"';
$modal_attrs .= ' data-close-overlay="' . ( false !== ( $attributes['closeOnOverlay'] ?? true ) ? '1' : '0' ) . '"';
$modal_attrs .= ' data-close-esc="' . ( false !== ( $attributes['closeOnEsc'] ?? true ) ? '1' : '0' ) . '"';
$modal_attrs .= ' data-show-once="' . ( ! empty( $attributes['showOnce'] ) ? '1' : '0' ) . '"';

printf(
	'<div %1$s%2$s%3$s>%4$s%5$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$modal_attrs,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values escaped/whitelisted above.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$trigger_html,       // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- icon via icon_html, label esc_html'd, src esc_url'd above.
	$modal_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup + esc_attr'd label; $content pre-rendered by WP.
);
