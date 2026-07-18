<?php
/**
 * Filter: Search — server-side render (child of flexa/post-filter).
 *
 * A real `<input type="search">` named `s_<gridId>` — exactly the query arg the
 * grid's render.php reads — so submitting the parent form filters the grid with no
 * JavaScript at all. The value is pre-filled from the current request, so a shared
 * or bookmarked filtered URL comes back looking the way it left.
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

use Flexa\Block\Filter_Fields;
use Flexa\Block\Post_Query;

$target = Filter_Fields::target( $block instanceof WP_Block ? $block : null );
if ( '' === $target ) {
	return;
}

$input_id    = Filter_Fields::control_id( $attributes, 'search' );
$placeholder = trim( (string) ( $attributes['placeholder'] ?? '' ) );
$value       = Filter_Fields::current_search( $target );

$input = '<input type="search" class="flexa-filter-field__control"'
	. ' id="' . esc_attr( $input_id ) . '"'
	. ' name="' . esc_attr( Post_Query::search_key( $target ) ) . '"'
	. ' value="' . esc_attr( $value ) . '"'
	. ( '' !== $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '' )
	. ' />';

// The magnifier sits INSIDE the field, so the box reads as a search box before the
// visitor reads the label. Decorative: the <label> is what names the control.
$icon = '<svg class="flexa-filter-field__search-icon" viewBox="0 0 24 24" width="18" height="18" fill="none"'
	. ' stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false">'
	. '<circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>';

$field = '<span class="flexa-filter-field__search-wrap">' . $icon . $input . '</span>';

$html = Filter_Fields::shell(
	$attributes,
	Filter_Fields::label( $attributes, $input_id ) . $field,
	'flexa-filter-field--search'
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output escaped above and inside the helper.
echo $html;
