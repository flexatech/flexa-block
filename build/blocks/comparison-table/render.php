<?php
/**
 * Comparison Table block — server-side render.
 *
 * CSS is generated at save time by Comparison_Table_CSS and printed inline on
 * the front end. This file outputs the comparison grid: a header row of plan
 * columns (logo, badge, title, subtitle, CTA) and one table row per feature,
 * where a cell value of "true"/"false" becomes a check/cross mark and anything
 * else prints as text. The whole table sits in a horizontally scrollable wrapper
 * so it stays usable on small screens. Text inherits the theme's typography
 * unless the user overrode it.
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
$html_tag = HTML_Helpers::get_html_tag( $attributes );

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

$container_type = 'boxed' === ( $attributes['containerType'] ?? 'full-width' ) ? 'boxed' : 'full-width';
$sticky         = ! empty( $attributes['stickyHeader'] );
$zebra          = false !== ( $attributes['zebra'] ?? true );
$check_icon     = in_array( $attributes['checkIcon'] ?? 'check', [ 'check', 'star', 'dot' ], true ) ? $attributes['checkIcon'] : 'check';
$cross_icon     = in_array( $attributes['crossIcon'] ?? 'cross', [ 'cross', 'dash', 'minus' ], true ) ? $attributes['crossIcon'] : 'cross';

/**
 * Static (no user input) check/cross mark markup for the chosen glyph style.
 *
 * @param string $kind  'yes' or 'no'.
 * @param string $style Glyph style key.
 * @return string
 */
$mark_html = static function ( $kind, $style ) {
	$paths = [
		'check' => '<path d="M5 13l4 4L19 7" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>',
		'star'  => '<path d="M12 3l2.7 5.9 6.3.6-4.7 4.3 1.3 6.2L12 17.8 6.1 20.9l1.3-6.2L2.7 9.5l6.3-.6z" fill="currentColor"/>',
		'dot'   => '<circle cx="12" cy="12" r="6" fill="currentColor"/>',
		'cross' => '<path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>',
		'dash'  => '<path d="M6 12h12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
		'minus' => '<path d="M6 12h12" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>',
	];
	$path  = $paths[ $style ] ?? $paths[ 'yes' === $kind ? 'check' : 'cross' ];
	$class = 'flexa-comparison-table__mark flexa-comparison-table__mark--' . $kind;
	return '<span class="' . esc_attr( $class ) . '" aria-hidden="true"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">' . $path . '</svg></span>';
};

// --- Header row: one <th> per column. ---
$head_html = '<tr class="flexa-comparison-table__head-row"><th class="flexa-comparison-table__corner"></th>';
foreach ( $columns as $ci => $col ) {
	$title     = esc_html( (string) ( $col['title'] ?? '' ) );
	$subtitle  = (string) ( $col['subtitle'] ?? '' );
	$badge     = (string) ( $col['badge'] ?? '' );
	$image_url = (string) ( $col['imageUrl'] ?? '' );
	$cta_text  = (string) ( $col['ctaText'] ?? '' );
	$cta_url   = (string) ( $col['ctaUrl'] ?? '' );
	$is_hi     = ! empty( $col['highlighted'] );

	$th_class = 'flexa-comparison-table__col' . ( $is_hi ? ' is-highlighted' : '' );

	$inner = '';
	if ( '' !== $badge ) {
		$inner .= '<span class="flexa-comparison-table__badge">' . esc_html( $badge ) . '</span>';
	}
	if ( '' !== $image_url ) {
		$inner .= '<img class="flexa-comparison-table__logo" src="' . esc_url( $image_url ) . '" alt="" loading="lazy" />';
	}
	$inner .= '<span class="flexa-comparison-table__col-title">' . $title . '</span>';
	if ( '' !== $subtitle ) {
		$inner .= '<span class="flexa-comparison-table__col-subtitle">' . esc_html( $subtitle ) . '</span>';
	}
	if ( '' !== $cta_text ) {
		if ( '' !== $cta_url ) {
			$inner .= '<a class="flexa-comparison-table__cta wp-element-button" href="' . esc_url( $cta_url ) . '">' . esc_html( $cta_text ) . '</a>';
		} else {
			$inner .= '<span class="flexa-comparison-table__cta wp-element-button">' . esc_html( $cta_text ) . '</span>';
		}
	}

	$col_index  = $col_source_keys[ $ci ] ?? $ci;
	$head_html .= '<th class="' . esc_attr( $th_class ) . '" scope="col"' . \Flexa\Block\Inline_Editor::item_attr( (int) $col_index ) . '><div class="flexa-comparison-table__col-inner">' . $inner . '</div></th>';
}
$head_html .= '</tr>';

// --- Body rows: a row header <th> plus one <td> per column. ---
$col_count = count( $columns );
$body_html = '';
foreach ( array_values( $rows ) as $ri => $row ) {
	if ( ! is_array( $row ) ) {
		continue;
	}
	$label  = esc_html( (string) ( $row['label'] ?? '' ) );
	$values = isset( $row['values'] ) && is_array( $row['values'] ) ? array_values( $row['values'] ) : [];

	$cells = '';
	for ( $c = 0; $c < $col_count; $c++ ) {
		$raw   = isset( $values[ $c ] ) ? (string) $values[ $c ] : '';
		$token = strtolower( trim( $raw ) );
		$is_hi = ! empty( $columns[ $c ]['highlighted'] );

		// Only free-text cells are inline-editable; true/false render as a
		// static check/cross mark and carry no data-flexa-cell.
		$cell_edit = '';
		if ( 'true' === $token ) {
			$cell_content = $mark_html( 'yes', $check_icon );
		} elseif ( 'false' === $token ) {
			$cell_content = $mark_html( 'no', $cross_icon );
		} else {
			$cell_content = esc_html( $raw );
			$cell_edit    = \Flexa\Block\Inline_Editor::cell_attr( $c );
		}

		$td_class = 'flexa-comparison-table__cell' . ( $is_hi ? ' is-highlighted' : '' );
		$cells   .= '<td class="' . esc_attr( $td_class ) . '"' . $cell_edit . '>' . $cell_content . '</td>';
	}

	$body_html .= '<tr class="flexa-comparison-table__row"' . \Flexa\Block\Inline_Editor::item_attr( $ri ) . '><th scope="row" class="flexa-comparison-table__label">' . $label . '</th>' . $cells . '</tr>';
}

// --- Wrapper classes + attributes. ---
$classes = [
	'flexa-comparison-table',
	'flexa-comparison-table--' . sanitize_html_class( $container_type ),
];
if ( $sticky ) {
	$classes[] = 'flexa-comparison-table--sticky';
}
if ( $zebra ) {
	$classes[] = 'flexa-comparison-table--zebra';
}
if ( false !== ( $attributes['showRowDivider'] ?? true ) ) {
	$classes[] = 'flexa-comparison-table--row-divider';
}
if ( ! empty( $attributes['showColumnDivider'] ) ) {
	$classes[] = 'flexa-comparison-table--col-divider';
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-comparison-table-' . sanitize_html_class( $block_id );
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
	'<%1$s %2$s%3$s%4$s><div class="flexa-comparison-table__scroll"><table class="flexa-comparison-table__table"><thead>%5$s</thead><tbody>%6$s</tbody></table></div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$head_html,          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- title/subtitle/badge/cta esc_html'd, urls esc_url'd, marks static above.
	$body_html           // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- label/text esc_html'd, marks static above.
);
