<?php
/**
 * Before / After block — server-side render.
 *
 * CSS is generated at save time by Before_After_CSS and printed inline on the
 * front end. This file outputs the two stacked images (after in flow, before
 * clipped to the handle position by CSS) plus the drag handle and corner labels.
 * Behaviour flags travel to view.js as data-* attributes. Output is escaped and
 * image URLs/alts pass through esc_url()/esc_attr().
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

$block_id     = $attributes['blockId'] ?? '';
$before_image = $attributes['beforeImage'] ?? [];
$after_image  = $attributes['afterImage'] ?? [];
$before_url   = (string) ( $before_image['url'] ?? '' );
$after_url    = (string) ( $after_image['url'] ?? '' );

// Nothing to compare without a block id and both images.
if ( '' === $block_id || '' === $before_url || '' === $after_url ) {
	return;
}

$anchor      = $attributes['anchor'] ?? '';
$orientation = 'vertical' === ( $attributes['orientation'] ?? 'horizontal' ) ? 'vertical' : 'horizontal';
$interaction = 'hover' === ( $attributes['interaction'] ?? 'drag' ) ? 'hover' : 'drag';
$position    = (int) ( $attributes['initialPosition'] ?? 50 );
$position    = max( 0, min( 100, $position ) );
$show_labels = false !== ( $attributes['showLabels'] ?? true );

$before_alt = (string) ( $attributes['beforeAlt'] ?? '' );
$after_alt  = (string) ( $attributes['afterAlt'] ?? '' );
$before_alt = '' !== $before_alt ? $before_alt : (string) ( $before_image['alt'] ?? '' );
$after_alt  = '' !== $after_alt ? $after_alt : (string) ( $after_image['alt'] ?? '' );

$before_label = (string) ( $attributes['beforeLabel'] ?? 'Before' );
$after_label  = (string) ( $attributes['afterLabel'] ?? 'After' );

$classes = [
	'flexa-before-after',
	'flexa-before-after--' . $orientation,
	'flexa-before-after-' . sanitize_html_class( $block_id ),
];
if ( 'hover' === $interaction ) {
	$classes[] = 'flexa-before-after--hover';
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

$behaviour_attrs  = ' data-orientation="' . esc_attr( $orientation ) . '"';
$behaviour_attrs .= ' data-interaction="' . esc_attr( $interaction ) . '"';
$behaviour_attrs .= ' data-position="' . esc_attr( (string) $position ) . '"';

/**
 * Build one <img>. The "after" image is the flow element that sets the frame
 * height; the "before" image is absolutely positioned to fill it.
 *
 * @param string $url URL.
 * @param string $alt Alt text.
 * @param array  $img Image attribute (for intrinsic size).
 * @return string
 */
$build_img = static function ( $url, $alt, $img ) {
	$html = '<img class="flexa-before-after__img" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async"';
	if ( ! empty( $img['width'] ) ) {
		$html .= ' width="' . absint( $img['width'] ) . '"';
	}
	if ( ! empty( $img['height'] ) ) {
		$html .= ' height="' . absint( $img['height'] ) . '"';
	}
	return $html . ' />';
};

$after_label_html  = $show_labels ? '<span class="flexa-before-after__label flexa-before-after__label--after">' . esc_html( $after_label ) . '</span>' : '';
$before_label_html = $show_labels ? '<span class="flexa-before-after__label flexa-before-after__label--before">' . esc_html( $before_label ) . '</span>' : '';

$handle_html = '<div class="flexa-before-after__handle" role="slider" tabindex="0"'
	. ' aria-label="' . esc_attr__( 'Drag to compare the two images', 'flexa-block' ) . '"'
	. ' aria-orientation="' . esc_attr( $orientation ) . '"'
	. ' aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr( (string) $position ) . '">'
	. '<span class="flexa-before-after__line"></span>'
	. '<span class="flexa-before-after__grip">'
	. '<svg class="flexa-before-after__arrows" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">'
	. '<path d="M9.5 7 5 12l4.5 5M14.5 7l4.5 5-4.5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />'
	. '</svg></span></div>';

// Labels sit on the frame itself (not inside the clipped image layers) so both
// stay visible at every slider position.
$frame_html = '<div class="flexa-before-after__frame">'
	. '<div class="flexa-before-after__img-wrap flexa-before-after__img-wrap--after">' . $build_img( $after_url, $after_alt, $after_image ) . '</div>'
	. '<div class="flexa-before-after__img-wrap flexa-before-after__img-wrap--before">' . $build_img( $before_url, $before_alt, $before_image ) . '</div>'
	. $before_label_html . $after_label_html
	. $handle_html
	. '</div>';

printf(
	'<div %1$s%2$s%3$s>%4$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$behaviour_attrs,    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values escaped with esc_attr above.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$frame_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- src/alt escaped, labels esc_html'd above.
);
