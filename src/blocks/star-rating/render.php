<?php
/**
 * Star Rating block — server-side render.
 *
 * CSS is generated at save time by Star_Rating_CSS and printed inline on the
 * front end. This file outputs the star row (each star an unmarked base with a
 * width-clipped marked overlay so fractional ratings fill smoothly), an optional
 * title before/after the stars, and — when enabled — an aggregateRating JSON-LD
 * block. All star markup is static; the title text is escaped (wp_kses with a
 * small inline whitelist). Colours inherit the theme unless the user overrode them.
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

$layout = 'stacked' === ( $attributes['ratingLayout'] ?? 'inline' ) ? 'stacked' : 'inline';

// Rating value out of a max range. Max is 1..10; the value is clamped to [0, max].
$max_rating = (int) round( (float) ( $attributes['maxRating'] ?? 5 ) );
$max_rating = max( 1, min( 10, $max_rating ) );
$rating     = (float) ( $attributes['rating'] ?? 4 );
$rating     = max( 0.0, min( (float) $max_rating, $rating ) );

// Title.
$show_title     = ! empty( $attributes['showTitle'] );
$title          = (string) ( $attributes['title'] ?? '' );
$title_position = 'before' === ( $attributes['titlePosition'] ?? 'after' ) ? 'before' : 'after';
$enable_schema  = ! empty( $attributes['enableSchema'] );

// Inline formatting allowed inside the title.
$inline_allowed = [
	'strong' => [],
	'em'     => [],
	'b'      => [],
	'i'      => [],
	'br'     => [],
	'span'   => [],
];

/** Format a number without a trailing `.0` (e.g. 4, 4.5). */
$fmt = static function ( $number ) {
	return rtrim( rtrim( number_format( (float) $number, 1, '.', '' ), '0' ), '.' );
};

// Star row — static markup (no user input), safe to echo directly. Each star is
// an unmarked base with a marked overlay clipped to the per-star fill width.
$star_svg = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L3.6 9.4l6.5-.9L12 2.6Z" fill="currentColor"/></svg>';

$stars = '';
for ( $i = 0; $i < $max_rating; $i++ ) {
	$fill = max( 0.0, min( 1.0, $rating - $i ) ) * 100;
	$pct  = $fmt( $fill );
	$stars .= '<span class="flexa-star-rating__star">'
		. $star_svg
		. '<span class="flexa-star-rating__star-fill" style="width:' . esc_attr( $pct ) . '%">' . $star_svg . '</span>'
		. '</span>';
}

$aria_label = sprintf(
	/* translators: 1: rating value, 2: maximum rating. */
	esc_attr__( 'Rated %1$s out of %2$s', 'flexa-block' ),
	$fmt( $rating ),
	$fmt( $max_rating )
);
$stars_html = '<span class="flexa-star-rating__stars" role="img" aria-label="' . $aria_label . '">' . $stars . '</span>';

// Optional title.
$title_html = '';
if ( $show_title && '' !== trim( $title ) ) {
	$title_html = '<span class="flexa-star-rating__title">' . wp_kses( $title, $inline_allowed ) . '</span>';
}

// Assemble in visual order.
$inner = '';
if ( 'before' === $title_position ) {
	$inner .= $title_html . $stars_html;
} else {
	$inner .= $stars_html . $title_html;
}

// Wrapper classes + attributes.
$classes = [ 'flexa-star-rating', 'flexa-star-rating--' . $layout ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-star-rating-' . sanitize_html_class( $block_id );
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

// Optional aggregateRating schema (printed once, before the stars).
$schema_html = '';
if ( $enable_schema ) {
	$schema = [
		'@context'    => 'https://schema.org',
		'@type'       => 'AggregateRating',
		'ratingValue' => $rating,
		'bestRating'  => $max_rating,
		'worstRating' => 0,
		'ratingCount' => 1,
	];
	$name = trim( wp_strip_all_tags( $title ) );
	if ( '' !== $name ) {
		$schema['itemReviewed'] = [
			'@type' => 'Thing',
			'name'  => $name,
		];
	}
	$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false !== $json ) {
		$schema_html = '<script type="application/ld+json">' . $json . '</script>';
	}
}

printf(
	'<%1$s %2$s%3$s%4$s>%5$s%6$s</%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$schema_html,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output, script tag static.
	$inner               // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- star markup static, title wp_kses'd, width/aria escaped above.
);
