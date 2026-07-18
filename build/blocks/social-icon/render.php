<?php
/**
 * Social Icons block — server-side render.
 *
 * CSS is generated at save time by Social_Icon_CSS and printed inline on the
 * front end. This file outputs the icon row: one link (or span when no URL)
 * per item, carrying either the full-colour brand artwork ('official' colour
 * mode) or the monochrome glyph tinted via currentColor ('custom' mode).
 *
 * The brand SVG catalog lives in the shared Flexa\Block\Social_Catalog (mirror
 * of src/utils/social-platforms.tsx) so every block that prints brand marks
 * reuses one source. All icon markup is a static, code-owned literal (no user
 * input), so it is safe to echo directly.
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
use Flexa\Block\Social_Catalog;

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';
$html_tag = HTML_Helpers::get_html_tag( $attributes );

$items = $attributes['items'] ?? [];
if ( ! is_array( $items ) || empty( $items ) ) {
	return;
}

/*
 * Platform catalog: label + full-colour brand artwork + monochrome silhouette.
 * Shared source (mirror of src/utils/social-platforms.tsx).
 */
$social_catalog = Social_Catalog::platforms();

// Build the icon list.
$items_html = '';
foreach ( $items as $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}
	$platform       = (string) ( $item['platform'] ?? '' );
	$is_custom_icon = 'custom' === $platform;

	if ( $is_custom_icon ) {
		// A user-picked icon: an uploaded SVG (as <img>) or a library/builtin
		// glyph (inline, sanitised). Skip until one is chosen — mirrors the
		// generator's renderable rule so :nth-child tint positions stay in sync.
		$icon_cfg = is_array( $item['icon'] ?? null ) ? $item['icon'] : [];
		$source   = (string) ( $icon_cfg['source'] ?? 'none' );
		$markup   = (string) ( $icon_cfg['markup'] ?? '' );
		$icon_url = (string) ( $icon_cfg['url'] ?? '' );

		if ( 'upload' === $source && '' !== $icon_url ) {
			$icon_svg = '<img class="flexa-icon" src="' . esc_url( $icon_url ) . '" alt="" width="24" height="24" loading="lazy" />';
		} elseif ( 'none' !== $source && '' !== $markup ) {
			$icon_svg = HTML_Helpers::svg_kses( $markup );
			if ( '' === $icon_svg ) {
				continue;
			}
		} else {
			continue;
		}

		$label      = '' !== (string) ( $icon_cfg['name'] ?? '' ) ? (string) $icon_cfg['name'] : __( 'Social link', 'flexa-block' );
		$type_class = 'flexa-social-icon__item--custom-icon';
		$tinted     = true;
	} else {
		if ( ! isset( $social_catalog[ $platform ] ) ) {
			continue;
		}
		$entry      = $social_catalog[ $platform ];
		$tinted     = 'custom' === ( $item['colorMode'] ?? 'official' );
		$icon_svg   = $tinted
			? '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false"><path d="' . $entry['mono'] . '"/></svg>'
			: $entry['brand'];
		$label      = $entry['label'];
		$type_class = 'flexa-social-icon__item--' . sanitize_html_class( $platform );
	}

	$item_classes = 'flexa-social-icon__item ' . $type_class;
	if ( $tinted ) {
		$item_classes .= ' flexa-social-icon__item--custom';
	}

	$inner = '<span class="flexa-social-icon__icon">' . $icon_svg . '</span>';

	$link = is_array( $item['link'] ?? null ) ? $item['link'] : [];
	$url  = (string) ( $link['url'] ?? '' );

	if ( '' !== $url ) {
		$item_attrs = 'class="' . esc_attr( $item_classes ) . '" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $label ) . '"';
		if ( '_blank' === ( $link['target'] ?? '' ) ) {
			$rel = trim( (string) ( $link['rel'] ?? '' ) );
			if ( '' === $rel ) {
				$rel = 'noopener noreferrer';
			}
			$rel         = trim( (string) preg_replace( '/[^a-z0-9 _-]/i', '', $rel ) );
			$item_attrs .= ' target="_blank" rel="' . esc_attr( $rel ) . '"';
		}
		$items_html .= '<a ' . $item_attrs . '>' . $inner . '</a>';
	} else {
		$items_html .= '<span class="' . esc_attr( $item_classes ) . '">' . $inner . '</span>';
	}
}

if ( '' === $items_html ) {
	return;
}

// Wrapper classes + attributes.
$classes = [ 'flexa-social-icon' ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-social-icon-' . sanitize_html_class( $block_id );
}
$hover_effect = (string) ( $attributes['hoverEffect'] ?? '' );
if ( in_array( $hover_effect, [ 'grow', 'shrink', 'lift', 'rotate' ], true ) ) {
	$classes[] = 'flexa-social-icon--hover-' . $hover_effect;
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

printf(
	'<%1$s %2$s%3$s><div class="flexa-social-icon__list">%4$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$items_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG literals; urls/labels/classes escaped above.
);
