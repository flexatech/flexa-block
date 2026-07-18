<?php
/**
 * Product Rating block — server-side render.
 *
 * Dynamic WooCommerce block: it reads the current product on a single-product
 * page and prints its average star rating (as stars, a numeric score or both)
 * plus an optional review count. CSS is generated at save time by
 * Product_Rating_CSS and printed inline on the front end. Outputs nothing off a
 * single-product page, or when the product is unrated unless "show when unrated"
 * is enabled. All star markup is static; the count text is escaped.
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

$product = \Flexa\Block\Woo_Helpers::current_product();
if ( ! $product ) {
	return;
}

$average = (float) $product->get_average_rating();
$count   = (int) $product->get_review_count();

// Off unless the product is rated (or the user opted to show it while unrated).
if ( $average <= 0 && empty( $attributes['showEmptyRating'] ) ) {
	return;
}

$block_id     = $attributes['blockId'] ?? '';
$anchor       = $attributes['anchor'] ?? '';
$display_type = in_array( $attributes['displayType'] ?? 'stars', [ 'stars', 'stars-number', 'number' ], true ) ? $attributes['displayType'] : 'stars';
$show_count   = ! isset( $attributes['showReviewCount'] ) || ! empty( $attributes['showReviewCount'] );

$show_stars  = 'stars' === $display_type || 'stars-number' === $display_type;
$show_number = 'number' === $display_type || 'stars-number' === $display_type;

$max_stars = 5;
$average   = max( 0.0, min( (float) $max_stars, $average ) );

/** Format a rating without a trailing `.0` (e.g. 4, 4.5). */
$fmt = static function ( $number ) {
	return rtrim( rtrim( number_format( (float) $number, 1, '.', '' ), '0' ), '.' );
};

// A single star glyph, reused for the base (empty) and fill (marked) rows.
$star_svg = '<span class="flexa-product-rating__star"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L3.6 9.4l6.5-.9L12 2.6Z" fill="currentColor"/></svg></span>';
$star_row = str_repeat( $star_svg, $max_stars );

// Star row — an empty base with a marked overlay clipped to the fill width.
$stars_html = '';
if ( $show_stars ) {
	$fill_pct = $fmt( ( $average / $max_stars ) * 100 );
	$aria     = sprintf(
		/* translators: 1: rating value, 2: maximum rating. */
		esc_attr__( 'Rated %1$s out of %2$s', 'flexa-block' ),
		$fmt( $average ),
		$max_stars
	);
	$stars_html = '<span class="flexa-product-rating__stars" role="img" aria-label="' . $aria . '">'
		. '<span class="flexa-product-rating__stars-base">' . $star_row . '</span>'
		. '<span class="flexa-product-rating__stars-fill" style="width:' . esc_attr( $fill_pct ) . '%">' . $star_row . '</span>'
		. '</span>';
}

// Numeric score.
$number_html = '';
if ( $show_number ) {
	$number_html = '<span class="flexa-product-rating__number">' . esc_html( $fmt( $average ) ) . '</span>';
}

// Optional review count.
$count_html = '';
if ( $show_count ) {
	$count_text = sprintf(
		/* translators: %s: number of reviews. */
		_n( '(%s review)', '(%s reviews)', $count, 'flexa-block' ),
		number_format_i18n( $count )
	);
	$count_html = '<span class="flexa-product-rating__count">' . esc_html( $count_text ) . '</span>';
}

$inner = $stars_html . $number_html . $count_html;

$classes = [ 'flexa-product-rating' ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-product-rating-' . sanitize_html_class( $block_id );
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
	'<div %1$s%2$s%3$s>%4$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$inner               // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- star markup static, width/aria escaped, count text esc_html'd above.
);
