<?php
/**
 * Add to Cart block — server-side render.
 *
 * Dynamic WooCommerce block. It does NOT hand-roll the add-to-cart form: it runs
 * WooCommerce's own `woocommerce_<type>_add_to_cart` action, so variable, grouped
 * and external products keep the markup, nonces and scripts core expects. CSS is
 * generated at save time by Product_Add_To_Cart_CSS and printed inline on the
 * front end, and view.js adds the quantity stepper and the inline "select an
 * option" notice to the form core produced. Outputs nothing off a
 * single-product page.
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

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';

$layouts     = [ 'inline', 'stacked', 'button-only' ];
$cart_layout = in_array( $attributes['cartLayout'] ?? 'inline', $layouts, true ) ? $attributes['cartLayout'] : 'inline';
$icon_pos    = 'after' === ( $attributes['iconPosition'] ?? 'before' ) ? 'after' : 'before';
$show_icon   = ! empty( $attributes['showIcon'] );

// The button label is the one thing core lets us swap cleanly. The filter is
// added and removed around the do_action() below so it only touches this
// instance — a second Add to Cart block on the page keeps its own label.
$button_text  = trim( (string) ( $attributes['buttonText'] ?? '' ) );
$label_filter = null;
if ( '' !== $button_text ) {
	$label_filter = static function () use ( $button_text ) {
		return $button_text;
	};
	add_filter( 'woocommerce_product_single_add_to_cart_text', $label_filter, 20 );
}

// Core escapes the button label, so an inline SVG pushed through the filter
// would print as literal text — the cart icon is a ::before/::after pseudo-element
// in style.scss instead, switched on by the wrapper modifier class below.
ob_start();
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Firing WooCommerce core's own add-to-cart action for the product type, not defining a plugin hook.
do_action( 'woocommerce_' . $product->get_type() . '_add_to_cart' );
$form = ob_get_clean();

// Product types with no registered handler (custom types from other plugins)
// still deserve a working form.
if ( '' === trim( (string) $form ) ) {
	ob_start();
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Firing WooCommerce core's own simple-product add-to-cart action as a fallback, not defining a plugin hook.
	do_action( 'woocommerce_simple_add_to_cart' );
	$form = ob_get_clean();
}

if ( $label_filter ) {
	remove_filter( 'woocommerce_product_single_add_to_cart_text', $label_filter, 20 );
}

if ( '' === trim( (string) $form ) ) {
	return;
}

$classes = [ 'flexa-product-add-to-cart', 'flexa-product-add-to-cart--' . $cart_layout ];
if ( $show_icon ) {
	$classes[] = 'flexa-product-add-to-cart--icon-' . $icon_pos;
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-product-add-to-cart-' . sanitize_html_class( $block_id );
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

// Strings view.js needs. A view script cannot reach the block's text domain, so
// they travel on the wrapper rather than through a localised global.
$view_strings = ' data-flexa-label-minus="' . esc_attr__( 'Decrease quantity', 'flexa-block' ) . '"'
	. ' data-flexa-label-plus="' . esc_attr__( 'Increase quantity', 'flexa-block' ) . '"'
	. ' data-flexa-msg-select="' . esc_attr__( 'Please select some product options before adding this product to your cart.', 'flexa-block' ) . '"'
	. ' data-flexa-msg-unavailable="' . esc_attr__( 'Sorry, this product is unavailable. Please choose a different combination.', 'flexa-block' ) . '"';

printf(
	'<div %1$s%2$s%3$s%4$s>%5$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$view_strings,       // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each value esc_attr__'d above.
	$form                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce core template output (same reasoning as get_block_wrapper_attributes); wp_kses would strip the form's own nonce fields and data attributes.
);
