<?php
/**
 * Filter: Taxonomy — server-side render (child of flexa/post-filter).
 *
 * Renders a `<select>` (or a checkbox group) named `tx_<gridId>_<taxonomy>` — the
 * query arg the grid's render.php reads — so the parent form filters with no
 * JavaScript at all.
 *
 * The options offered are the intersection of this taxonomy's terms with whatever
 * the TARGET GRID's own query already constrains it to. The server enforces that
 * intersection anyway (Post_Query::build_tax_query), but offering an option that
 * would then be ignored is a lie the UI shouldn't tell.
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

use Flexa\Block\Block_Locator;
use Flexa\Block\Filter_Fields;
use Flexa\Block\Post_Query;

$target = Filter_Fields::target( $block instanceof WP_Block ? $block : null );
if ( '' === $target ) {
	return;
}

$taxonomy = sanitize_key( (string) ( $attributes['taxonomy'] ?? '' ) );
if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
	return;
}

$control     = in_array( $attributes['control'] ?? 'dropdown', [ 'dropdown', 'checkbox', 'pills' ], true ) ? (string) $attributes['control'] : 'dropdown';
$multiple    = ! empty( $attributes['multiple'] );
$show_count  = ! empty( $attributes['showCount'] );
$hide_empty  = false !== ( $attributes['hideEmpty'] ?? true );
$order_by    = in_array( $attributes['orderBy'] ?? 'name', [ 'name', 'count', 'term_order' ], true ) ? (string) $attributes['orderBy'] : 'name';
$limit       = max( 1, min( 100, (int) ( $attributes['limit'] ?? 50 ) ) );

$all_label = trim( (string) ( $attributes['allTermsLabel'] ?? '' ) );
$all_label = '' !== $all_label ? $all_label : __( 'All', 'flexa-block' );

// Only offer terms the target grid would actually honour.
$grid_attrs = Block_Locator::find_attrs( 'flexa/post-grid', $target, (int) get_the_ID() );
$constraint = null !== $grid_attrs ? Post_Query::constrained_terms( Post_Query::config( $grid_attrs ), $taxonomy ) : [];

$term_args = [
	'taxonomy'   => $taxonomy,
	'hide_empty' => $hide_empty,
	'orderby'    => $order_by,
	'number'     => $limit,
];
if ( ! empty( $constraint ) ) {
	$term_args['include'] = $constraint;
}

$terms = get_terms( $term_args );
if ( is_wp_error( $terms ) || empty( $terms ) ) {
	return;
}

$selected = Filter_Fields::current_terms( $target, $taxonomy );
$field_id = Filter_Fields::control_id( $attributes, $taxonomy );
$name     = Post_Query::tax_key( $target, $taxonomy );

/**
 * One term's display name, optionally with its post count.
 *
 * @param WP_Term $term       Term.
 * @param bool    $with_count Append the post count.
 * @return string Escaped.
 */
$term_label = static function ( $term, $with_count ) {
	$label = esc_html( $term->name );
	if ( $with_count ) {
		$label .= ' <span class="flexa-filter-field__count">' . esc_html( (string) (int) $term->count ) . '</span>';
	}
	return $label;
};

if ( 'dropdown' === $control && ! $multiple ) {
	// Single-select needs an "any term" option — otherwise a visitor who picks a
	// category has no way back to the unfiltered list without hitting Reset.
	$options = '<option value="">' . esc_html( $all_label ) . '</option>';
	foreach ( $terms as $term ) {
		$is_selected = in_array( $term->slug, $selected, true ) ? ' selected' : '';
		$count       = $show_count ? ' (' . (int) $term->count . ')' : '';
		$options    .= '<option value="' . esc_attr( $term->slug ) . '"' . $is_selected . '>'
			. esc_html( $term->name . $count ) . '</option>';
	}

	$inner = Filter_Fields::label( $attributes, $field_id )
		. '<select class="flexa-filter-field__control"'
		. ' id="' . esc_attr( $field_id ) . '"'
		. ' name="' . esc_attr( $name ) . '"'
		. '>' . $options . '</select>';
} elseif ( 'dropdown' === $control ) {
	// Multi-select. `<select multiple>` is the native answer and it is the wrong one:
	// the browser draws it as an open list box, so a "Tag" dropdown ends up looking
	// nothing like the "Category" dropdown beside it, and picking two tags means
	// knowing to hold Ctrl. So the control is a disclosure whose summary wears the
	// same skin as the select (same `__control` class — the CSS generator paints it
	// with no extra selector), and whose panel holds ordinary checkboxes.
	//
	// <details> is what keeps this honest with JavaScript off: the browser opens and
	// closes it natively, and the checkboxes inside are plain form fields the GET form
	// submits like any other. The view script only adds the niceties — closing on an
	// outside click, and keeping the summary text in step with the ticks.
	$options = '';
	$chosen  = [];
	foreach ( $terms as $term ) {
		$option_id = $field_id . '-' . (int) $term->term_id;
		$checked   = in_array( $term->slug, $selected, true );
		if ( $checked ) {
			$chosen[] = $term->name;
		}
		$options .= '<label class="flexa-filter-field__option" for="' . esc_attr( $option_id ) . '">'
			. '<input type="checkbox" class="flexa-filter-field__option-input" id="' . esc_attr( $option_id ) . '"'
			. ' name="' . esc_attr( $name ) . '[]"'
			. ' value="' . esc_attr( $term->slug ) . '"' . ( $checked ? ' checked' : '' ) . ' />'
			. '<span class="flexa-filter-field__option-text">' . $term_label( $term, $show_count ) . '</span></label>';
	}

	// The summary says what is picked, the way a closed select does. Rendered server
	// side too, so a bookmarked filtered URL reads correctly before any JS runs.
	$summary_text = ! empty( $chosen ) ? implode( ', ', $chosen ) : $all_label;

	// Past a dozen terms the panel is a scrollbar and a memory test. The box that fixes
	// that is a JS convenience — it narrows the list already on the page and submits
	// nothing (no `name`), so a visitor without JavaScript loses nothing by not having
	// it. Hence `hidden`: the view script unhides it once it can actually drive it.
	$term_search = '';
	if ( count( $terms ) > Filter_Fields::MENU_SEARCH_FROM ) {
		$term_search = '<input type="search" class="flexa-filter-field__menu-search" hidden'
			. ' placeholder="' . esc_attr__( 'Filter terms…', 'flexa-block' ) . '"'
			. ' aria-label="' . esc_attr__( 'Filter terms', 'flexa-block' ) . '" />';
	}

	$legend = trim( (string) ( $attributes['label'] ?? '' ) );
	$hidden = false === ( $attributes['showLabel'] ?? true ) ? ' class="screen-reader-text"' : '';

	$inner = '<fieldset class="flexa-filter-field__fieldset">'
		. ( '' !== $legend ? '<legend class="flexa-filter-field__label"' . $hidden . '>' . esc_html( $legend ) . '</legend>' : '' )
		. '<details class="flexa-filter-field__menu">'
		. '<summary class="flexa-filter-field__control flexa-filter-field__menu-toggle">'
		. '<span class="flexa-filter-field__menu-value" data-empty="' . esc_attr( $all_label ) . '">'
		. esc_html( $summary_text ) . '</span></summary>'
		. '<div class="flexa-filter-field__menu-panel">'
		. $term_search
		. '<div class="flexa-filter-field__menu-list">' . $options . '</div>'
		. '</div></details></fieldset>';
} else {
	// Checkbox and pills are the same control wearing different clothes: a group of
	// inputs the browser submits natively. Pills just hide the input and style the
	// label as a chip — so the "pretty" mode is not a JS mode, it still filters with
	// JavaScript off.
	$is_pills = 'pills' === $control;

	// Pills default to single-choice (radio), which is what a tag/category chip row
	// normally means; ticking "Allow multiple" turns them into checkboxes. A checkbox
	// list is always multi — that is the whole point of it.
	$type = ( $is_pills && ! $multiple ) ? 'radio' : 'checkbox';
	$key  = 'radio' === $type ? esc_attr( $name ) : esc_attr( $name ) . '[]';

	$legend = trim( (string) ( $attributes['label'] ?? '' ) );
	$hidden = false === ( $attributes['showLabel'] ?? true ) ? ' class="screen-reader-text"' : '';

	$options = '';

	// Radio pills need an explicit "All" chip: unlike a dropdown, a radio group has
	// no neutral state a visitor can get back to once they've picked one.
	if ( 'radio' === $type ) {
		$all_id   = $field_id . '-all';
		$checked  = empty( $selected ) ? ' checked' : '';
		$options .= '<label class="flexa-filter-field__option" for="' . esc_attr( $all_id ) . '">'
			. '<input type="radio" class="flexa-filter-field__option-input" id="' . esc_attr( $all_id ) . '"'
			. ' name="' . $key . '" value=""' . $checked . ' />'
			. '<span class="flexa-filter-field__option-text">' . esc_html( $all_label ) . '</span></label>';
	}

	foreach ( $terms as $term ) {
		$option_id = $field_id . '-' . (int) $term->term_id;
		$checked   = in_array( $term->slug, $selected, true ) ? ' checked' : '';
		$options  .= '<label class="flexa-filter-field__option" for="' . esc_attr( $option_id ) . '">'
			. '<input type="' . esc_attr( $type ) . '" class="flexa-filter-field__option-input" id="' . esc_attr( $option_id ) . '"'
			. ' name="' . $key . '"'
			. ' value="' . esc_attr( $term->slug ) . '"' . $checked . ' />'
			. '<span class="flexa-filter-field__option-text">' . $term_label( $term, $show_count ) . '</span></label>';
	}

	// A group of inputs has no single control to label — a fieldset legend names it.
	$inner = '<fieldset class="flexa-filter-field__fieldset">'
		. ( '' !== $legend ? '<legend class="flexa-filter-field__label"' . $hidden . '>' . esc_html( $legend ) . '</legend>' : '' )
		. '<div class="flexa-filter-field__options flexa-filter-field__options--' . ( $is_pills ? 'pills' : 'checkbox' ) . '">'
		. $options
		. '</div></fieldset>';
}

$html = Filter_Fields::shell(
	$attributes,
	$inner,
	'flexa-filter-field--taxonomy flexa-filter-field--' . $control . ' flexa-filter-field--' . sanitize_html_class( $taxonomy )
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output escaped above and inside the helper.
echo $html;
