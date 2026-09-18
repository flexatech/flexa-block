<?php
/**
 * Product Field block — server-side render.
 *
 * Dynamic WooCommerce block: one line of product data — the SKU, the categories
 * or the tags, chosen by the `field` attribute. The three inserter variations
 * (Product SKU / Product Categories / Product Tags) are this block with that one
 * attribute preset, so the linking, escaping and separator rules have exactly
 * one implementation.
 *
 * It stands alone anywhere on a single-product page, and stacks inside
 * flexa/product-meta, which styles it through the shared `flexa-product-field__*`
 * class names. Term markup comes from Woo_Helpers::term_list_html(). CSS is
 * generated at save time by Product_Field_CSS. Outputs nothing off a
 * single-product page, or when the product has nothing for this field.
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

$field = in_array( $attributes['field'] ?? 'sku', [ 'sku', 'categories', 'tags' ], true )
	? (string) $attributes['field']
	: 'sku';

$term_layout = in_array( $attributes['termLayout'] ?? 'inline', [ 'inline', 'badge', 'list' ], true )
	? (string) $attributes['termLayout']
	: 'inline';

if ( 'sku' === $field ) {
	$sku      = (string) $product->get_sku();
	$fallback = (string) ( $attributes['skuFallback'] ?? '' );

	// No SKU and no fallback to print in its place: render nothing at all.
	if ( '' === $sku && '' === $fallback ) {
		return;
	}

	// Prefix and suffix decorate a real SKU only — the fallback stands on its own.
	$text = '' !== $sku
		? ( (string) ( $attributes['skuPrefix'] ?? '' ) ) . $sku . ( (string) ( $attributes['skuSuffix'] ?? '' ) )
		: $fallback;

	$value_html = esc_html( $text );

	// Copy only makes sense for a real code, never for the fallback wording.
	if ( ! empty( $attributes['showCopy'] ) && '' !== $sku ) {
		$copied      = (string) ( $attributes['copiedText'] ?? '' );
		$value_html .= '<button type="button" class="flexa-product-field__copy"'
			. ' data-sku="' . esc_attr( $sku ) . '"'
			. ' data-copied="' . esc_attr( '' !== $copied ? $copied : __( 'Copied', 'flexa-block' ) ) . '"'
			. ' aria-label="' . esc_attr__( 'Copy SKU', 'flexa-block' ) . '">'
			. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
			. '<rect x="9" y="9" width="12" height="12" rx="2"></rect>'
			. '<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>'
			. '</svg></button>';
	}
} else {
	// The block owns its label, so the helper's own label is switched off.
	$term_args = [
		'termLayout'    => $term_layout,
		'showLabel'     => false,
		'separator'     => (string) ( $attributes['separator'] ?? ', ' ),
		'linkToArchive' => ! isset( $attributes['linkTerms'] ) || ! empty( $attributes['linkTerms'] ),
		'linkTarget'    => ! empty( $attributes['linkTarget'] ),
		'maxTerms'      => (int) ( $attributes['maxTerms'] ?? 0 ),
	];

	$taxonomy   = 'categories' === $field ? 'product_cat' : 'product_tag';
	$value_html = Woo_Helpers::term_list_html( $product, $taxonomy, $term_args, 'product-field' );

	// A product with no terms in this taxonomy prints nothing.
	if ( '' === $value_html ) {
		return;
	}
}

$label      = trim( (string) ( $attributes['label'] ?? '' ) );
$label_html = '' !== $label
	? '<span class="flexa-product-field__label">' . esc_html( $label ) . '</span>'
	: '';

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';
$layout   = 'stacked' === ( $attributes['fieldLayout'] ?? 'inline' ) ? 'stacked' : 'inline';

$classes = [
	'flexa-product-field',
	'flexa-product-field--' . $field,
	'flexa-product-field--' . $layout,
];
if ( 'sku' !== $field ) {
	$classes[] = 'flexa-product-field--terms-' . $term_layout;
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-product-field-' . sanitize_html_class( $block_id );
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
	'<div %1$s%2$s%3$s>%4$s<span class="flexa-product-field__value">%5$s</span></div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$label_html,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html'd above, markup literal.
	$value_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SKU esc_html'd / esc_attr'd above, term names and URLs escaped in Woo_Helpers.
);
