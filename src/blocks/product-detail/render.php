<?php
/**
 * Product Details block — server-side render.
 *
 * Dynamic WooCommerce block: reads the current product and prints its details as
 * tabs (Description / Additional information / Reviews). CSS is generated at save
 * time by Product_Detail_CSS and printed inline on the front end. Renders nothing
 * when there is no product in context (i.e. off a single-product page) or when no
 * enabled tab has any content. The active panel is visible by default so it works
 * without JS; view.js switches tabs on click.
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
use Flexa\Block\Woo_Helpers;

$product = Woo_Helpers::current_product();
if ( ! $product ) {
	return;
}

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';

$show_description = false !== ( $attributes['showDescriptionTab'] ?? true );
$show_additional = false !== ( $attributes['showAdditionalTab'] ?? true );
$show_reviews    = false !== ( $attributes['showReviewsTab'] ?? true );

// Collect the enabled tabs that actually have content. Each entry is a
// [ label, content ] pair; the content is pre-escaped for its type below.
$rendered_tabs = [];

// Description → the product long description, run through `the_content`.
if ( $show_description ) {
	$description = (string) $product->get_description();
	if ( '' !== trim( $description ) ) {
		$rendered_tabs[] = [
			'label'   => esc_html__( 'Description', 'flexa-block' ),
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- `the_content` is a WordPress core filter, applied intentionally to render the product description.
			'content' => wp_kses_post( apply_filters( 'the_content', $description ) ),
		];
	}
}

// Additional information → the WooCommerce product attributes table.
if ( $show_additional && function_exists( 'wc_display_product_attributes' ) ) {
	ob_start();
	wc_display_product_attributes( $product );
	$additional = (string) ob_get_clean();
	if ( '' !== trim( $additional ) ) {
		$rendered_tabs[] = [
			'label'   => esc_html__( 'Additional information', 'flexa-block' ),
			'content' => wp_kses_post( $additional ),
		];
	}
}

// Reviews → the real WooCommerce reviews template (review list + rating + form),
// exactly like the default single-product Reviews tab. It uses the global $post,
// which is the current product on a single-product page. If reviews are closed
// and there are none, fall back to a short text summary so the tab is never empty.
if ( $show_reviews ) {
	$reviews_html    = '';
	$reviews_context = ( function_exists( 'comments_open' ) && comments_open( $product->get_id() ) )
		|| (int) $product->get_review_count() > 0;

	if ( $reviews_context && function_exists( 'comments_template' ) ) {
		// Threaded review replies (the rating <select> is turned into clickable
		// stars by this block's own view.js, so WooCommerce's single-product
		// script isn't required here).
		if ( function_exists( 'wp_enqueue_script' ) && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}

		ob_start();
		// WooCommerce ships this template; it renders the review list and the
		// "Add a review" form (which contains form controls, so it must NOT be
		// wp_kses'd — it is trusted core/plugin template output). The list is
		// paginated client-side by this block's view.js (WP comment pagination is
		// unreliable when comments_template() runs inside a block).
		comments_template( '/single-product-reviews.php' );
		$reviews_html = trim( (string) ob_get_clean() );
	}

	if ( '' !== $reviews_html ) {
		// Wrap the reviews so the shared client-side pager (view.js) can paginate
		// the list. A numbered nav (`.flexa-pagination`) or a load-more button is
		// appended; view.js shows/hides the `.commentlist` items accordingly.
		$pg_type   = in_array( $attributes['paginationType'] ?? 'numbered', [ 'none', 'numbered', 'loadmore' ], true ) ? $attributes['paginationType'] : 'numbered';
		$per_page  = max( 1, (int) ( $attributes['reviewsPerPage'] ?? 5 ) );
		$prev_lbl  = (string) ( $attributes['prevLabel'] ?? '' );
		$next_lbl  = (string) ( $attributes['nextLabel'] ?? '' );
		$more_text = '' !== (string) ( $attributes['loadMoreText'] ?? '' ) ? (string) $attributes['loadMoreText'] : __( 'Load more', 'flexa-block' );

		$pager = '';
		if ( 'numbered' === $pg_type ) {
			$pager = '<nav class="flexa-pagination" aria-label="' . esc_attr__( 'Reviews pagination', 'flexa-block' ) . '"></nav>';
		} elseif ( 'loadmore' === $pg_type ) {
			$pager = '<div class="flexa-pagination-loadmore"><button type="button" class="flexa-pagination-loadmore__btn wp-element-button">' . esc_html( $more_text ) . '</button></div>';
		}

		$load_more = max( 1, (int) ( $attributes['reviewsLoadMore'] ?? 5 ) );
		$data      = ' data-flexa-pd-pagination="' . esc_attr( $pg_type ) . '"';
		$data     .= ' data-per-page="' . esc_attr( (string) $per_page ) . '"';
		$data     .= ' data-loadmore-rows="' . esc_attr( (string) $per_page ) . '"';
		$data     .= ' data-loadmore-step="' . esc_attr( (string) $load_more ) . '"';
		if ( '' !== $prev_lbl ) {
			$data .= ' data-prev-label="' . esc_attr( $prev_lbl ) . '"';
		}
		if ( '' !== $next_lbl ) {
			$data .= ' data-next-label="' . esc_attr( $next_lbl ) . '"';
		}

		$rendered_tabs[] = [
			'label'   => esc_html__( 'Reviews', 'flexa-block' ),
			'content' => '<div class="flexa-product-detail__reviews"' . $data . '>' . $reviews_html . $pager . '</div>',
			'raw'     => true,
		];
	} else {
		$count   = (int) $product->get_review_count();
		$average = (float) $product->get_average_rating();
		$parts   = [];
		if ( $average > 0 ) {
			$parts[] = sprintf(
				/* translators: %s: average product rating out of 5. */
				__( 'Average rating: %s out of 5', 'flexa-block' ),
				number_format_i18n( $average, 2 )
			);
		}
		$parts[] = sprintf(
			/* translators: %s: number of product reviews. */
			_n( '%s review', '%s reviews', $count, 'flexa-block' ),
			number_format_i18n( $count )
		);
		$rendered_tabs[] = [
			'label'   => esc_html__( 'Reviews', 'flexa-block' ),
			'content' => '<p>' . esc_html( implode( ' — ', $parts ) ) . '</p>',
		];
	}
}

// Nothing to show → render nothing.
if ( empty( $rendered_tabs ) ) {
	return;
}

$nav_html    = '';
$panels_html = '';

foreach ( $rendered_tabs as $index => $tab ) {
	$is_active   = 0 === $index;
	$tab_class   = 'flexa-product-detail__tab' . ( $is_active ? ' is-active' : '' );
	$panel_class = 'flexa-product-detail__panel' . ( $is_active ? ' is-active' : '' );

	$nav_html .= '<li><button type="button" class="' . esc_attr( $tab_class ) . '" role="tab"'
		. ' aria-selected="' . ( $is_active ? 'true' : 'false' ) . '" data-tab="' . (int) $index . '">'
		. $tab['label'] . '</button></li>';

	$panels_html .= '<div class="' . esc_attr( $panel_class ) . '" role="tabpanel" data-tab="' . (int) $index . '">'
		. '<div class="flexa-product-detail__content">' . $tab['content'] . '</div>'
		. '</div>';
}

// Sliding active-tab indicator (positioned by view.js; a no-JS underline
// fallback lives on `.is-active` in style.scss).
$nav_html .= '<li class="flexa-product-detail__indicator" aria-hidden="true"></li>';

// Wrapper classes + attributes.
$classes = [ 'flexa-product-detail' ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-product-detail-' . sanitize_html_class( $block_id );
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
	'<div %1$s%2$s%3$s><ul class="flexa-product-detail__nav" role="tablist">%4$s</ul>%5$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$nav_html,           // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- labels esc_html'd, classes esc_attr'd above.
	$panels_html         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- description/additional wp_kses_post'd, reviews are trusted core template output, classes esc_attr'd above.
);
