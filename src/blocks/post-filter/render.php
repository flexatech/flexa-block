<?php
/**
 * Post Filter block — server-side render (parent of the filter children).
 *
 * A real `<form method="get">` around the rendered children. With JavaScript off
 * it submits, the URL gains this grid's filter args (`s_<gid>`, `tx_<gid>_<tax>`)
 * and the grid's own render.php reads them — filtering works, full stop. The view
 * script only upgrades that to an in-place update.
 *
 * The paging arg is deliberately NOT carried through: changing a filter must send
 * the visitor back to page 1, or they'd land on page 4 of a 2-page result.
 *
 * @package Flexa\Block
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    InnerBlocks content (the rendered filter children).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $attributes, $content provided by WP block API.
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filter state, no state change.

use Flexa\Block\HTML_Helpers;
use Flexa\Block\Filter_Fields;
use Flexa\Block\Post_Query;

$block_id = sanitize_html_class( (string) ( $attributes['blockId'] ?? '' ) );
$anchor   = $attributes['anchor'] ?? '';

// The grid this bar drives. Empty target = the first grid on the page — the same
// rule the view script applies client side.
$target = Filter_Fields::target( $block instanceof WP_Block ? $block : null );

// A filter bar with no grid to filter is inert — render nothing rather than a
// form that would reload the page to no effect.
if ( '' === $target || '' === trim( (string) $content ) ) {
	return;
}

$direction = 'column' === ( $attributes['direction'] ?? 'row' ) ? 'column' : 'row';

$form_label = trim( (string) ( $attributes['formLabel'] ?? '' ) );
$form_label = '' !== $form_label ? $form_label : __( 'Filter posts', 'flexa-block' );

$submit_text = trim( (string) ( $attributes['submitText'] ?? '' ) );
$submit_text = '' !== $submit_text ? $submit_text : __( 'Filter', 'flexa-block' );

$action = get_permalink();
$action = is_string( $action ) && '' !== $action ? $action : home_url( '/' );

// Carry every unrelated query arg through the submit (a language switcher, another
// grid's page…) — this grid's own args are supplied by the fields themselves, and
// its page arg is dropped on purpose so a new filter starts at page 1.
$own_prefix = 'tx_' . $target . '_';
$own_keys   = [ Post_Query::page_key( $target ), Post_Query::search_key( $target ) ];
$hidden     = '';
foreach ( (array) $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$key = (string) $key;
	if ( in_array( $key, $own_keys, true ) || 0 === strpos( $key, $own_prefix ) || ! is_scalar( $value ) ) {
		continue;
	}
	$hidden .= '<input type="hidden" name="' . esc_attr( sanitize_key( $key ) ) . '" value="'
		. esc_attr( sanitize_text_field( wp_unslash( (string) $value ) ) ) . '" />';
}

$classes = [ 'flexa-post-filter', 'flexa-post-filter--' . $direction ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-post-filter-' . $block_id;
}
// Something is filtering the grid. Term counts are counted against the WHOLE archive,
// so the moment a visitor narrows it they start lying — "Design (4)" while four is no
// longer reachable. Rather than re-count on every keystroke, the bar stops showing
// them; CSS hides them, and the view script keeps the class in step without a reload.
if ( Filter_Fields::has_active_filters( (int) get_the_ID(), $target ) ) {
	$classes[] = 'flexa-post-filter--filtered';
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [
	'class'                   => implode( ' ', $classes ),
	'role'                    => 'search',
	'method'                  => 'get',
	'action'                  => esc_url( $action ),
	'aria-label'              => $form_label,
	'data-flexa-post-filter'  => $target,
];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}

$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// The submit button is ALWAYS rendered. Auto-submit hides it with CSS, but only
// once the view script has marked the form as JS-driven (`is-js`) — so a visitor
// without JavaScript never loses the only way to apply a filter.
$submit = '<button type="submit" class="flexa-post-filter__submit wp-element-button">'
	. esc_html( $submit_text ) . '</button>';

printf(
	'<form %1$s%2$s>%3$s<div class="flexa-post-filter__fields">%4$s</div>%5$s</form>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$hidden,             // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
	$content,            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- InnerBlocks content already rendered/escaped by WP.
	$submit              // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
);
