<?php
/**
 * Filter: Reset — server-side render (child of flexa/post-filter).
 *
 * A real `<a>` pointing at the current URL with this grid's filter args stripped —
 * so it clears the filters by navigating, with no JavaScript involved at all. (The
 * view script intercepts the click to swap in place, but the link is the behaviour,
 * not a fallback for it.)
 *
 * Only THIS grid's args are stripped: a second grid's filters, a language switcher
 * or anything else on the URL survives the reset.
 *
 * @package Flexa\Block
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    InnerBlocks content (unused).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $attributes provided by WP block API.
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filter state.

use Flexa\Block\Filter_Fields;
use Flexa\Block\Post_Query;

$target = Filter_Fields::target( $block instanceof WP_Block ? $block : null );
if ( '' === $target ) {
	return;
}

$text = trim( (string) ( $attributes['text'] ?? '' ) );
$text = '' !== $text ? $text : __( 'Reset', 'flexa-block' );

$is_button = 'button' === ( $attributes['variant'] ?? 'link' );

// Strip this grid's args from the current URL.
$strip = [ Post_Query::page_key( $target ), Post_Query::search_key( $target ) ];
$prefix = 'tx_' . $target . '_';
foreach ( array_keys( (array) $_GET ) as $key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 0 === strpos( (string) $key, $prefix ) ) {
		$strip[] = (string) $key;
	}
}

$reset_url = remove_query_arg( $strip );

$class = 'flexa-filter-reset__control' . ( $is_button ? ' wp-element-button' : ' flexa-filter-reset__control--link' );

$link = '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $reset_url ) . '">' . esc_html( $text ) . '</a>';

// Nothing applied, nothing to clear: the control is rendered but hidden (CSS), so the
// view script can bring it back the moment the visitor types or ticks something —
// without JavaScript it appears exactly when a submitted filter is in the URL, which
// is the only moment it could do anything anyway.
$modifier = 'flexa-filter-field--reset';
if ( ! Filter_Fields::has_active_filters( (int) get_the_ID(), $target ) ) {
	$modifier .= ' flexa-filter-field--empty';
}

$html = Filter_Fields::shell(
	$attributes,
	$link,
	$modifier
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output escaped above and inside the helper.
echo $html;
