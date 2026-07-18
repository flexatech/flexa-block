<?php
/**
 * Team Member block — server-side render.
 *
 * CSS is generated at save time by Team_Member_CSS and printed inline on the
 * front end. This file outputs the photo plus the content column — name, role,
 * bio and a row of linked social icons. All text is escaped (wp_kses with a
 * small inline whitelist); icons run through HTML_Helpers::svg_kses. The card
 * inherits the theme's typography unless the user overrode it.
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

$image_position = in_array( $attributes['imagePosition'] ?? 'top', [ 'top', 'left', 'right' ], true ) ? $attributes['imagePosition'] : 'top';
$image_shape    = in_array( $attributes['imageShape'] ?? 'circle', [ 'circle', 'rounded', 'square' ], true ) ? $attributes['imageShape'] : 'circle';
$stack_on       = in_array( $attributes['stackOn'] ?? 'none', [ 'tablet', 'mobile' ], true ) ? $attributes['stackOn'] : 'none';

// Name element tag — whitelist so only a known tag reaches the markup.
$text_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' ];
$name_tag  = in_array( $attributes['nameTag'] ?? 'h3', $text_tags, true ) ? $attributes['nameTag'] : 'h3';

$name = (string) ( $attributes['name'] ?? '' );
$role = (string) ( $attributes['role'] ?? '' );
$bio  = (string) ( $attributes['bio'] ?? '' );

$show_social = false !== ( $attributes['showSocial'] ?? true );

// Inline formatting allowed inside each text field.
$inline_allowed = [
	'strong' => [],
	'em'     => [],
	'b'      => [],
	'i'      => [],
	'br'     => [],
	'span'   => [],
];
$bio_allowed = $inline_allowed + [ 'a' => [ 'href' => true, 'target' => true, 'rel' => true ] ];

// --- Photo -----------------------------------------------------------------
$photo_html = '';
$image      = $attributes['image'] ?? [];
$image_url  = (string) ( $image['url'] ?? '' );
if ( '' !== $image_url ) {
	$photo_html = '<div class="flexa-team-member__photo"><img class="flexa-team-member__image" src="' . esc_url( $image_url ) . '" alt="' . esc_attr( (string) ( $image['alt'] ?? '' ) ) . '" loading="lazy" decoding="async" /></div>';
}

// --- Text pieces -----------------------------------------------------------
$name_html = '' !== trim( $name )
	? '<' . $name_tag . ' class="flexa-team-member__name">' . wp_kses( $name, $inline_allowed ) . '</' . $name_tag . '>'
	: '';

$role_html = '' !== trim( $role )
	? '<span class="flexa-team-member__role">' . wp_kses( $role, $inline_allowed ) . '</span>'
	: '';

$bio_html = '' !== trim( $bio )
	? '<p class="flexa-team-member__bio">' . wp_kses( $bio, $bio_allowed ) . '</p>'
	: '';

// --- Social links ----------------------------------------------------------
$social_html = '';
if ( $show_social ) {
	$items      = is_array( $attributes['items'] ?? null ) ? $attributes['items'] : [];
	$links_html = '';
	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		// Resolve the icon to inline SVG (builtin/library) or an <img> (upload).
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

		$label = '' !== (string) ( $icon_cfg['name'] ?? '' ) ? (string) $icon_cfg['name'] : __( 'Social link', 'flexa-block' );
		$inner = '<span class="flexa-icon-slot">' . $icon_svg . '</span>';

		$link = is_array( $item['link'] ?? null ) ? $item['link'] : [];
		$url  = (string) ( $link['url'] ?? '' );

		if ( '' !== $url ) {
			$link_attrs = 'class="flexa-team-member__social-link" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $label ) . '"';
			if ( '_blank' === ( $link['target'] ?? '' ) ) {
				$rel = trim( (string) ( $link['rel'] ?? '' ) );
				if ( '' === $rel ) {
					$rel = 'noopener noreferrer';
				}
				$rel         = trim( (string) preg_replace( '/[^a-z0-9 _-]/i', '', $rel ) );
				$link_attrs .= ' target="_blank" rel="' . esc_attr( $rel ) . '"';
			}
			$links_html .= '<a ' . $link_attrs . '>' . $inner . '</a>';
		} else {
			$links_html .= '<span class="flexa-team-member__social-link">' . $inner . '</span>';
		}
	}
	if ( '' !== $links_html ) {
		$social_html = '<div class="flexa-team-member__social">' . $links_html . '</div>';
	}
}

$content_inner = $name_html . $role_html . $bio_html . $social_html;
if ( '' === $photo_html && '' === $content_inner ) {
	return;
}
$content_html = '' !== $content_inner ? '<div class="flexa-team-member__content">' . $content_inner . '</div>' : '';

// --- Wrapper ---------------------------------------------------------------
$classes = [
	'flexa-team-member',
	'flexa-team-member--pos-' . $image_position,
	'flexa-team-member--shape-' . $image_shape,
];
if ( 'top' !== $image_position && 'none' !== $stack_on ) {
	$classes[] = 'flexa-team-member--stack-' . $stack_on;
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-team-member-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Lazy background: mark the wrapper so view.js reveals the image near the viewport.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

printf(
	'<%1$s %2$s%3$s%4$s>%5$s%6$s</%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$photo_html,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- image src/alt escaped above.
	$content_html        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text wp_kses'd, icons via svg_kses, link url/rel escaped above.
);
