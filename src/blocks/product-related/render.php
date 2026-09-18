<?php
/**
 * Related Products block — server-side render.
 *
 * Dynamic WooCommerce block: it reads the current product on a single-product
 * page, asks WooCommerce for its related product IDs and re-queries them through
 * WP_Query so the author's own ordering (random / newest / price / popularity /
 * rating) can be honoured. CSS is generated at save time by Product_Related_CSS
 * and printed inline on the front end. Outputs nothing off a single-product page
 * or when the product has no related products.
 *
 * The add-to-cart control is a plain link to `add_to_cart_url()` rather than a
 * form, so the block never needs JavaScript: simple products add straight to the
 * cart, variable / external products land on the product page as WooCommerce
 * intends.
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

$limit   = max( 1, min( 24, (int) ( $attributes['postsPerPage'] ?? 4 ) ) );
$related = wc_get_related_products( $product->get_id(), $limit );
if ( empty( $related ) ) {
	return;
}

$order_by = in_array( $attributes['orderBy'] ?? 'rand', [ 'rand', 'date', 'price', 'popularity', 'rating' ], true )
	? $attributes['orderBy']
	: 'rand';

$query_args = [
	'post_type'           => 'product',
	'post__in'            => $related,
	'posts_per_page'      => $limit,
	'ignore_sticky_posts' => true,
	'post_status'         => 'publish',
];

// The meta-backed orderings need their meta key; `rand` and `date` are native.
switch ( $order_by ) {
	case 'date':
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'DESC';
		break;
	case 'price':
		$query_args['meta_key'] = '_price'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- indexed WooCommerce lookup key.
		$query_args['orderby']  = 'meta_value_num';
		$query_args['order']    = 'ASC';
		break;
	case 'popularity':
		$query_args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- indexed WooCommerce lookup key.
		$query_args['orderby']  = 'meta_value_num';
		$query_args['order']    = 'DESC';
		break;
	case 'rating':
		$query_args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- indexed WooCommerce lookup key.
		$query_args['orderby']  = 'meta_value_num';
		$query_args['order']    = 'DESC';
		break;
	default:
		$query_args['orderby'] = 'rand';
		break;
}

$query = new WP_Query( $query_args );
if ( ! $query->have_posts() ) {
	wp_reset_postdata();
	return;
}

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';

$layout       = 'list' === ( $attributes['relatedLayout'] ?? 'grid' ) ? 'list' : 'grid';
$show_heading = ! isset( $attributes['showHeading'] ) || ! empty( $attributes['showHeading'] );
$show_image   = ! isset( $attributes['showImage'] ) || ! empty( $attributes['showImage'] );
$show_title   = ! isset( $attributes['showTitle'] ) || ! empty( $attributes['showTitle'] );
$show_price   = ! isset( $attributes['showPrice'] ) || ! empty( $attributes['showPrice'] );
$show_rating  = ! empty( $attributes['showRating'] );
$show_button  = ! empty( $attributes['showButton'] );

$hover_classes = HTML_Helpers::hover_effect_classes( $attributes['hoverEffect'] ?? '' );

// One star glyph, reused for the empty base row and the clipped fill overlay —
// the same convention as the Product Rating block so the two look identical.
$star_svg = '<span class="flexa-product-related__star"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L3.6 9.4l6.5-.9L12 2.6Z" fill="currentColor"/></svg></span>';
$star_row = str_repeat( $star_svg, 5 );

/** Format a rating without a trailing `.0` (e.g. 4, 4.5). */
$fmt = static function ( $number ) {
	return rtrim( rtrim( number_format( (float) $number, 1, '.', '' ), '0' ), '.' );
};

$items_html = '';
while ( $query->have_posts() ) {
	$query->the_post();

	$item = wc_get_product( get_the_ID() );
	if ( ! $item ) {
		continue;
	}

	$permalink = get_permalink( $item->get_id() );
	$card      = '';

	if ( $show_image ) {
		$image_classes = array_merge( [ 'flexa-product-related__image' ], $hover_classes );
		$card         .= '<a class="' . esc_attr( implode( ' ', $image_classes ) ) . '" href="' . esc_url( $permalink ) . '">'
			. wp_kses_post( $item->get_image( 'woocommerce_thumbnail' ) )
			. '</a>';
	}

	if ( $show_title ) {
		$card .= '<h3 class="flexa-product-related__title"><a href="' . esc_url( $permalink ) . '">' . esc_html( $item->get_name() ) . '</a></h3>';
	}

	if ( $show_rating ) {
		$average  = max( 0.0, min( 5.0, (float) $item->get_average_rating() ) );
		$fill_pct = $fmt( ( $average / 5 ) * 100 );
		$aria     = sprintf(
			/* translators: 1: rating value, 2: maximum rating. */
			esc_attr__( 'Rated %1$s out of %2$s', 'flexa-block' ),
			$fmt( $average ),
			5
		);
		$card .= '<div class="flexa-product-related__rating">'
			. '<span class="flexa-product-related__stars" role="img" aria-label="' . $aria . '">'
			. '<span class="flexa-product-related__stars-base">' . $star_row . '</span>'
			. '<span class="flexa-product-related__stars-fill" style="width:' . esc_attr( $fill_pct ) . '%">' . $star_row . '</span>'
			. '</span>'
			. '</div>';
	}

	if ( $show_price ) {
		$price_html = $item->get_price_html();
		if ( '' !== $price_html ) {
			$card .= '<div class="flexa-product-related__price">' . wp_kses_post( $price_html ) . '</div>';
		}
	}

	if ( $show_button ) {
		$card .= '<a class="flexa-product-related__button wp-element-button" href="' . esc_url( $item->add_to_cart_url() ) . '" rel="nofollow">'
			. esc_html( $item->add_to_cart_text() )
			. '</a>';
	}

	$items_html .= '<article class="flexa-product-related__item">' . $card . '</article>';
}
wp_reset_postdata();

if ( '' === $items_html ) {
	return;
}

$heading_html = '';
if ( $show_heading ) {
	$heading_text = (string) ( $attributes['headingText'] ?? '' );
	if ( '' !== trim( $heading_text ) ) {
		$heading_tags = [ 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div' ];
		$heading_tag  = in_array( $attributes['headingTag'] ?? 'h2', $heading_tags, true ) ? $attributes['headingTag'] : 'h2';
		$heading_html = '<' . $heading_tag . ' class="flexa-product-related__heading">' . esc_html( $heading_text ) . '</' . $heading_tag . '>';
	}
}

$inner = $heading_html . '<div class="flexa-product-related__list">' . $items_html . '</div>';

$classes = [ 'flexa-product-related', 'flexa-product-related--' . $layout ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-product-related-' . sanitize_html_class( $block_id );
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
	$inner               // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- tags whitelisted, text esc_html'd, urls esc_url'd, WooCommerce markup wp_kses_post'd above.
);
