<?php
/**
 * Counter block — server-side render.
 *
 * CSS is generated at save time by Counter_CSS and printed inline on the front
 * end. This file outputs the stat grid: one item per stat, each with its icon,
 * its number (prefix + value + suffix) and its label. The final number value is
 * rendered server-side so the correct figure shows without JavaScript; when
 * count-up is enabled, view.js reads `data-target` and animates from zero when
 * the counter scrolls into view. Numbers and labels inherit the theme's
 * typography unless the user overrode it.
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

$items = $attributes['items'] ?? [];
if ( ! is_array( $items ) || empty( $items ) ) {
	return;
}

$show_icon      = false !== ( $attributes['showIcon'] ?? true );
$icon_inline    = 'inline' === ( $attributes['iconPosition'] ?? 'top' );
$show_separator = ! empty( $attributes['showSeparator'] );
$count_up       = false !== ( $attributes['countUp'] ?? true );
$duration       = (int) ( $attributes['countDuration'] ?? 2000 );

// Inline formatting allowed inside a label.
$label_allowed = [
	'strong' => [],
	'em'     => [],
	'b'      => [],
	'i'      => [],
	'br'     => [],
	'span'   => [],
];

// Build the stat items.
$items_html = '';
foreach ( array_values( $items ) as $index => $item ) {
	if ( ! is_array( $item ) ) {
		continue;
	}

	$number = is_array( $item['number'] ?? null ) ? $item['number'] : [];
	$value  = trim( (string) ( $number['value'] ?? '' ) );
	$prefix = (string) ( $number['prefix'] ?? '' );
	$suffix = (string) ( $number['suffix'] ?? '' );
	$label  = wp_kses( (string) ( $item['label'] ?? '' ), $label_allowed );

	if ( '' === $value && '' === trim( wp_strip_all_tags( $label ) ) ) {
		continue;
	}

	// Icon: an uploaded image, or an inline (sanitised) library/builtin glyph.
	$icon_html = '';
	if ( $show_icon ) {
		$icon_cfg = is_array( $item['icon'] ?? null ) ? $item['icon'] : [];
		$source   = (string) ( $icon_cfg['source'] ?? 'none' );
		$markup   = (string) ( $icon_cfg['markup'] ?? '' );
		$icon_url = (string) ( $icon_cfg['url'] ?? '' );

		if ( 'upload' === $source && '' !== $icon_url ) {
			$icon_html = '<span class="flexa-counter__icon"><img src="' . esc_url( $icon_url ) . '" alt="" width="24" height="24" loading="lazy" /></span>';
		} elseif ( 'none' !== $source && '' !== $markup ) {
			$svg = HTML_Helpers::svg_kses( $markup );
			if ( '' !== $svg ) {
				$icon_html = '<span class="flexa-counter__icon">' . $svg . '</span>';
			}
		}
	}

	// Number: the value carries data-target so view.js can animate it; the
	// numeric part it parses is the value, prefix/suffix are decoration.
	$value_attr = ( $count_up && is_numeric( $value ) ) ? ' data-target="' . esc_attr( $value ) . '"' : '';
	$number_html  = '<div class="flexa-counter__number">';
	if ( '' !== $prefix ) {
		$number_html .= '<span class="flexa-counter__affix">' . esc_html( $prefix ) . '</span>';
	}
	$number_html .= '<span class="flexa-counter__value"' . $value_attr . '>' . esc_html( $value ) . '</span>';
	if ( '' !== $suffix ) {
		$number_html .= '<span class="flexa-counter__affix">' . esc_html( $suffix ) . '</span>';
	}
	$number_html .= '</div>';

	// Icon + number sit on one row when the icon is inline; otherwise the icon
	// stacks above the number.
	$head_html = ( $icon_inline && '' !== $icon_html )
		? '<div class="flexa-counter__row">' . $icon_html . $number_html . '</div>'
		: $icon_html . $number_html;

	$separator_html = $show_separator ? '<span class="flexa-counter__separator" aria-hidden="true"></span>' : '';
	$label_html     = '' !== $label ? '<span class="flexa-counter__label">' . $label . '</span>' : '';

	$items_html .= '<div class="flexa-counter__item"' . \Flexa\Block\Inline_Editor::item_attr( $index ) . '>' . $head_html . $separator_html . $label_html . '</div>';
}

if ( '' === $items_html ) {
	return;
}

// Wrapper classes + attributes.
$classes = [ 'flexa-counter' ];
if ( $icon_inline ) {
	$classes[] = 'flexa-counter--icon-inline';
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-counter-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Count-up markers for view.js.
$counter_attrs = '';
if ( $count_up ) {
	$duration      = max( 200, min( 20000, $duration ) );
	$counter_attrs = ' data-flexa-counter data-duration="' . esc_attr( (string) $duration ) . '"';
}

// Lazy background marker.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

printf(
	'<%1$s %2$s%3$s%4$s%5$s><div class="flexa-counter__list">%6$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$counter_attrs,      // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static keys, values esc_attr'd above.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$items_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- label wp_kses'd, value/affix esc_html'd, icon svg_kses'd above.
);
