<?php
/**
 * Data Table block — server-side render.
 *
 * CSS is generated at save time by Data_Table_CSS and printed inline on the
 * front end. This file outputs a simple table: an optional header row of column
 * labels and one body row per data row, each cell aligned to its column and
 * escaped as plain text. The whole table sits in a horizontally scrollable
 * wrapper (Radix-style thin scrollbar) so a table wider than its max width — or
 * the viewport — scrolls sideways instead of overflowing.
 * Column labels and body cells are inline-editable on the front end: the label
 * span carries data-flexa-item (column index) and each text cell carries
 * data-flexa-cell (column index) inside a row stamped with data-flexa-item.
 * Text inherits the theme's typography unless the user overrode it.
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
$html_tag = HTML_Helpers::get_html_tag( $attributes, 'div' );

$columns = $attributes['columns'] ?? [];
$rows    = $attributes['rows'] ?? [];
if ( ! is_array( $columns ) ) {
	$columns = [];
}
if ( ! is_array( $rows ) ) {
	$rows = [];
}
// Preserve original column keys ($col_source_keys) so the inline editor maps a
// rendered column back to its exact `columns` entry after non-array ones drop.
$columns_filtered = array_filter( $columns, 'is_array' );
$col_source_keys  = array_keys( $columns_filtered );
$columns          = array_values( $columns_filtered );
if ( empty( $columns ) ) {
	return;
}

$show_header = false !== ( $attributes['showHeader'] ?? true );
$col_count   = count( $columns );

/**
 * Sanitize a per-column text alignment to one of the three allowed values.
 *
 * @param mixed $align Raw alignment.
 * @return string One of left|center|right.
 */
$align_of = static function ( $align ): string {
	return in_array( $align, [ 'left', 'center', 'right' ], true ) ? $align : 'left';
};

// --- Header row: one <th> per column, its label inline-editable. ---
$head_html = '';
if ( $show_header ) {
	$cells = '';
	foreach ( $columns as $ci => $col ) {
		$align      = $align_of( $col['align'] ?? 'left' );
		$col_index  = $col_source_keys[ $ci ] ?? $ci;
		$cells     .= '<th class="flexa-data-table__th" style="text-align:' . esc_attr( $align ) . '">'
			. '<span class="flexa-data-table__col-label"' . \Flexa\Block\Inline_Editor::item_attr( (int) $col_index ) . '>'
			. esc_html( (string) ( $col['label'] ?? '' ) )
			. '</span></th>';
	}
	$head_html = '<thead class="flexa-data-table__thead"><tr class="flexa-data-table__row">' . $cells . '</tr></thead>';
}

// --- Body rows: one <td> per column, each text cell inline-editable. ---
$body_html = '';
foreach ( $rows as $r => $row ) {
	if ( ! is_array( $row ) ) {
		continue;
	}
	$values = isset( $row['values'] ) && is_array( $row['values'] ) ? array_values( $row['values'] ) : [];

	$cells = '';
	for ( $c = 0; $c < $col_count; $c++ ) {
		$align  = $align_of( $columns[ $c ]['align'] ?? 'left' );
		$cells .= '<td class="flexa-data-table__cell" style="text-align:' . esc_attr( $align ) . '"' . \Flexa\Block\Inline_Editor::cell_attr( $c ) . '>'
			. esc_html( isset( $values[ $c ] ) ? (string) $values[ $c ] : '' )
			. '</td>';
	}

	$body_html .= '<tr class="flexa-data-table__row"' . \Flexa\Block\Inline_Editor::item_attr( (int) $r ) . '>' . $cells . '</tr>';
}

// --- Wrapper classes + attributes. ---
$classes = [
	'flexa-data-table',
];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-data-table-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Lazy background marker.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

printf(
	'<%1$s %2$s%3$s%4$s><div class="flexa-data-table__scroll"><table class="flexa-data-table__table">%5$s<tbody class="flexa-data-table__tbody">%6$s</tbody></table></div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$head_html,          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- labels esc_html'd, align esc_attr'd.
	$body_html           // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- cell text esc_html'd, align esc_attr'd.
);
