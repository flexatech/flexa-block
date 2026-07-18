<?php
/**
 * Info Box block — server-side render.
 *
 * CSS is generated at save time by Info_Box_CSS and printed inline on the front
 * end. This file outputs the media (icon or image) plus the content column —
 * prefix, title, optional separator, description and an optional CTA button. All
 * text is escaped (wp_kses with a small inline whitelist); icons run through
 * HTML_Helpers::svg_kses. The card inherits the theme's typography unless the
 * user overrode it.
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

$icon_position = 'left' === ( $attributes['iconPosition'] ?? 'top' ) ? 'left' : 'top';
$stack_on      = in_array( $attributes['stackOn'] ?? 'none', [ 'tablet', 'mobile' ], true ) ? $attributes['stackOn'] : 'none';

// Title element tag — whitelist so only a known tag reaches the markup.
$text_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' ];
$title_tag = in_array( $attributes['titleTag'] ?? 'h3', $text_tags, true ) ? $attributes['titleTag'] : 'h3';

$show_media = false !== ( $attributes['showMedia'] ?? true );
$media_type = 'image' === ( $attributes['mediaType'] ?? 'icon' ) ? 'image' : 'icon';

$show_prefix    = ! empty( $attributes['showPrefix'] );
$show_separator = ! empty( $attributes['showSeparator'] );
$show_button    = ! empty( $attributes['showButton'] );

$prefix      = (string) ( $attributes['prefix'] ?? '' );
$title       = (string) ( $attributes['title'] ?? '' );
$description = (string) ( $attributes['description'] ?? '' );

// Inline formatting allowed inside each text field.
$inline_allowed = [
	'strong' => [],
	'em'     => [],
	'b'      => [],
	'i'      => [],
	'br'     => [],
	'span'   => [],
];
$desc_allowed = $inline_allowed + [ 'a' => [ 'href' => true, 'target' => true, 'rel' => true ] ];

// --- Media -----------------------------------------------------------------
$media_html = '';
if ( $show_media ) {
	$inner_media = '';
	if ( 'image' === $media_type ) {
		$image = $attributes['image'] ?? [];
		$url   = (string) ( $image['url'] ?? '' );
		if ( '' !== $url ) {
			$inner_media = '<img class="flexa-info-box__image" src="' . esc_url( $url ) . '" alt="' . esc_attr( (string) ( $image['alt'] ?? '' ) ) . '" loading="lazy" decoding="async" />';
		}
	} else {
		$icon = $attributes['icon'] ?? [];
		if ( 'upload' === ( $icon['source'] ?? '' ) && '' !== (string) ( $icon['url'] ?? '' ) ) {
			$inner_media = '<span class="flexa-info-box__icon"><img class="flexa-icon" src="' . esc_url( $icon['url'] ) . '" alt="" width="24" height="24" loading="lazy" /></span>';
		} elseif ( '' !== (string) ( $icon['markup'] ?? '' ) ) {
			$svg = HTML_Helpers::svg_kses( $icon['markup'] );
			if ( '' !== $svg ) {
				$inner_media = '<span class="flexa-info-box__icon">' . $svg . '</span>';
			}
		}
	}
	if ( '' !== $inner_media ) {
		$media_html = '<div class="flexa-info-box__media">' . $inner_media . '</div>';
	}
}

// --- Text pieces -----------------------------------------------------------
$prefix_html = ( $show_prefix && '' !== trim( $prefix ) )
	? '<span class="flexa-info-box__prefix">' . wp_kses( $prefix, $inline_allowed ) . '</span>'
	: '';

$title_html = '' !== trim( $title )
	? '<' . $title_tag . ' class="flexa-info-box__title">' . wp_kses( $title, $inline_allowed ) . '</' . $title_tag . '>'
	: '';

$separator_html = $show_separator ? '<div class="flexa-info-box__separator" aria-hidden="true"></div>' : '';

$description_html = '' !== trim( $description )
	? '<p class="flexa-info-box__description">' . wp_kses( $description, $desc_allowed ) . '</p>'
	: '';

// --- CTA button ------------------------------------------------------------
$button_html = '';
if ( $show_button ) {
	$button_text = (string) ( $attributes['buttonText'] ?? '' );
	if ( '' !== trim( $button_text ) ) {
		$url     = (string) ( $attributes['buttonUrl'] ?? '' );
		$new_tab = '_blank' === ( $attributes['buttonTarget'] ?? '' );
		$rel     = trim( (string) ( $attributes['buttonRel'] ?? '' ) );

		$link_attrs = 'class="wp-element-button flexa-info-box__button"';
		if ( '' !== $url ) {
			$link_attrs .= ' href="' . esc_url( $url ) . '"';
		}
		if ( $new_tab ) {
			$link_attrs .= ' target="_blank"';
			if ( '' === $rel ) {
				$rel = 'noopener noreferrer';
			}
		}
		if ( '' !== $rel ) {
			$rel         = trim( (string) preg_replace( '/[^a-z0-9 _-]/i', '', $rel ) );
			$link_attrs .= ' rel="' . esc_attr( $rel ) . '"';
		}

		// Resolve the button icon to inline SVG (builtin/library) or an <img> (upload).
		$icon_cfg = is_array( $attributes['buttonIcon'] ?? null ) ? $attributes['buttonIcon'] : [];
		$icon_pos = 'before' === ( $icon_cfg['position'] ?? 'after' ) ? 'before' : 'after';
		$icon_svg = '';
		if ( 'upload' === ( $icon_cfg['source'] ?? 'none' ) && '' !== (string) ( $icon_cfg['url'] ?? '' ) ) {
			$icon_svg = '<img class="flexa-icon" src="' . esc_url( $icon_cfg['url'] ) . '" alt="" width="20" height="20" loading="lazy" />';
		} elseif ( 'none' !== ( $icon_cfg['source'] ?? 'none' ) && '' !== (string) ( $icon_cfg['markup'] ?? '' ) ) {
			$icon_svg = HTML_Helpers::svg_kses( $icon_cfg['markup'] );
		}

		$label = '<span class="flexa-info-box__button-text">' . wp_kses( $button_text, $inline_allowed ) . '</span>';

		$button_inner = '';
		if ( '' !== $icon_svg && 'before' === $icon_pos ) {
			$button_inner .= $icon_svg;
		}
		$button_inner .= $label;
		if ( '' !== $icon_svg && 'after' === $icon_pos ) {
			$button_inner .= $icon_svg;
		}

		$button_html = '<div class="flexa-info-box__cta"><a ' . $link_attrs . '>' . $button_inner . '</a></div>';
	}
}

$content_inner = $prefix_html . $title_html . $separator_html . $description_html . $button_html;
if ( '' === $media_html && '' === $content_inner ) {
	return;
}
$content_html = '' !== $content_inner ? '<div class="flexa-info-box__content">' . $content_inner . '</div>' : '';

// --- Wrapper ---------------------------------------------------------------
$classes = [ 'flexa-info-box', 'flexa-info-box--icon-' . $icon_position ];
if ( 'left' === $icon_position && 'none' !== $stack_on ) {
	$classes[] = 'flexa-info-box--stack-' . $stack_on;
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-info-box-' . sanitize_html_class( $block_id );
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
	$media_html,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- icon via svg_kses, image src/alt escaped above.
	$content_html        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text wp_kses'd, button url/rel escaped above.
);
