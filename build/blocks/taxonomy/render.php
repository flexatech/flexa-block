<?php
/**
 * Taxonomy block — server-side render.
 *
 * CSS is generated at save time by Taxonomy_CSS and printed inline on the front
 * end. This file queries the chosen taxonomy's terms with `get_terms()` and
 * outputs them in the chosen display — a vertical list, an inline row (optionally
 * separated), a native navigation dropdown, or a grid — each term linking to its
 * archive, with an optional post count and an icon/text prefix & suffix. In list
 * display with hierarchy on, child terms nest under their parents. Every dynamic
 * value is escaped on output.
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

// --- Query configuration (sanitised to known enums). ---
$tax      = sanitize_key( (string) ( $attributes['taxonomy'] ?? 'category' ) );
$display  = in_array( $attributes['displayStyle'] ?? 'list', [ 'list', 'inline', 'dropdown', 'grid' ], true ) ? $attributes['displayStyle'] : 'list';
$order_by = in_array( $attributes['orderBy'] ?? 'name', [ 'name', 'count', 'slug' ], true ) ? $attributes['orderBy'] : 'name';
$order    = 'desc' === ( $attributes['order'] ?? 'asc' ) ? 'DESC' : 'ASC';

$show_count     = false !== ( $attributes['showCount'] ?? true );
$show_empty     = ! empty( $attributes['showEmpty'] );
$hierarchical   = ! empty( $attributes['hierarchical'] );
$limit          = max( 0, (int) ( $attributes['limit'] ?? 0 ) );
$show_separator = ! empty( $attributes['showSeparator'] );
$separator      = (string) ( $attributes['separator'] ?? '/' );

// --- Wrapper classes + attributes. ---
$classes = [ 'flexa-taxonomy', 'flexa-taxonomy--' . $display ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-taxonomy-' . sanitize_html_class( $block_id );
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

/**
 * Render the wrapper around already-built inner HTML.
 *
 * @param string $inner_html Escaped inner markup.
 * @return void
 */
$print_wrapper = static function ( $inner_html ) use ( $html_tag, $wrapper_attributes, $data_attrs, $lazy_marker ) {
	printf(
		'<%1$s %2$s%3$s%4$s>%5$s</%1$s>',
		esc_html( $html_tag ),
		$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
		$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
		$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
		$inner_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- names esc_html'd, urls esc_url'd, icons svg_kses'd below.
	);
};

// --- Bail cleanly when the taxonomy is gone / invalid. ---
if ( '' === $tax || ! taxonomy_exists( $tax ) ) {
	$print_wrapper( '<p class="flexa-taxonomy__empty">' . esc_html__( 'No terms found.', 'flexa-block' ) . '</p>' );
	return;
}

// --- Query the terms. ---
$query_args = [
	'taxonomy'   => $tax,
	'orderby'    => $order_by,
	'order'      => $order,
	'hide_empty' => ! $show_empty,
];
if ( $limit > 0 ) {
	$query_args['number'] = $limit;
}
$terms = get_terms( $query_args );

if ( is_wp_error( $terms ) || empty( $terms ) ) {
	$print_wrapper( '<p class="flexa-taxonomy__empty">' . esc_html__( 'No terms found.', 'flexa-block' ) . '</p>' );
	return;
}
$terms = array_values( $terms );

// --- Resolve the (constant) prefix / suffix affixes once. ---
$affix_allowed = [ 'strong' => [], 'em' => [], 'b' => [], 'i' => [], 'br' => [], 'span' => [] ];

/**
 * Build one affix's inner HTML (icon glyph or minimal-kses'd text), or ''.
 *
 * @param string $type    'none' | 'icon' | 'text'.
 * @param mixed  $icon    IconValue.
 * @param string $text    Text value.
 * @param string $cls     Element class.
 * @param array  $allowed Allowed tags for text.
 * @return string
 */
$build_affix = static function ( $type, $icon, $text, $cls, $allowed ) {
	if ( 'icon' === $type ) {
		$svg = HTML_Helpers::icon_html( $icon );
		return '' !== $svg ? '<span class="' . esc_attr( $cls ) . '">' . $svg . '</span>' : '';
	}
	if ( 'text' === $type && '' !== trim( (string) $text ) ) {
		return '<span class="' . esc_attr( $cls ) . '">' . wp_kses( (string) $text, $allowed ) . '</span>';
	}
	return '';
};

$prefix_html = $build_affix( $attributes['prefixType'] ?? 'none', $attributes['prefixIcon'] ?? [], $attributes['prefixText'] ?? '', 'flexa-taxonomy__prefix', $affix_allowed );
$suffix_html = $build_affix( $attributes['suffixType'] ?? 'none', $attributes['suffixIcon'] ?? [], $attributes['suffixText'] ?? '', 'flexa-taxonomy__suffix', $affix_allowed );

/**
 * The clickable body of one term: the linked prefix + name + count + suffix.
 *
 * @param WP_Term $term Term object.
 * @return string
 */
$build_link = static function ( $term ) use ( $prefix_html, $suffix_html, $show_count ) {
	$link_raw = get_term_link( $term );
	$href     = is_wp_error( $link_raw ) ? '' : esc_url( $link_raw );
	$name     = '<span class="flexa-taxonomy__name">' . esc_html( $term->name ) . '</span>';
	$count    = $show_count ? '<span class="flexa-taxonomy__count">(' . (int) $term->count . ')</span>' : '';

	$inner = $prefix_html . $name . $count . $suffix_html;
	if ( '' !== $href ) {
		return '<a class="flexa-taxonomy__link" href="' . $href . '">' . $inner . '</a>';
	}
	return '<span class="flexa-taxonomy__link">' . $inner . '</span>';
};

// --- Dropdown: a native <select> whose option values are term links. ---
if ( 'dropdown' === $display ) {
	$options = '<option value="">' . esc_html__( 'Select…', 'flexa-block' ) . '</option>';
	foreach ( $terms as $term ) {
		$link_raw = get_term_link( $term );
		if ( is_wp_error( $link_raw ) ) {
			continue;
		}
		$label    = $term->name . ( $show_count ? ' (' . (int) $term->count . ')' : '' );
		$options .= '<option value="' . esc_url( $link_raw ) . '">' . esc_html( $label ) . '</option>';
	}
	$select = '<select class="flexa-taxonomy__select" data-flexa-taxonomy-nav aria-label="' . esc_attr__( 'Taxonomy terms', 'flexa-block' ) . '">' . $options . '</select>';
	$print_wrapper( $select );
	return;
}

// --- List / inline / grid: an <ul> of term chips. ---
$sep_html = $show_separator && 'inline' === $display
	? '<span class="flexa-taxonomy__separator" aria-hidden="true">' . esc_html( $separator ) . '</span>'
	: '';

if ( $hierarchical && 'list' === $display ) {
	// Group terms by parent, treating terms whose parent is absent as roots so
	// nothing is dropped when the query filtered a parent out.
	$present   = [];
	foreach ( $terms as $term ) {
		$present[ (int) $term->term_id ] = true;
	}
	$by_parent = [];
	foreach ( $terms as $term ) {
		$parent = (int) $term->parent;
		if ( ! isset( $present[ $parent ] ) ) {
			$parent = 0;
		}
		$by_parent[ $parent ][] = $term;
	}

	$walk = static function ( $parent ) use ( &$walk, $by_parent, $build_link ) {
		if ( empty( $by_parent[ $parent ] ) ) {
			return '';
		}
		$html = '<ul class="flexa-taxonomy__list">';
		foreach ( $by_parent[ $parent ] as $term ) {
			$children = $walk( (int) $term->term_id );
			$html    .= '<li class="flexa-taxonomy__item">' . $build_link( $term ) . $children . '</li>';
		}
		return $html . '</ul>';
	};

	$inner_html = $walk( 0 );
} else {
	$last  = count( $terms ) - 1;
	$items = '';
	foreach ( $terms as $i => $term ) {
		$sep    = $i < $last ? $sep_html : '';
		$items .= '<li class="flexa-taxonomy__item">' . $build_link( $term ) . $sep . '</li>';
	}
	$inner_html = '<ul class="flexa-taxonomy__list">' . $items . '</ul>';
}

$print_wrapper( $inner_html );
