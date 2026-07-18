<?php
/**
 * FAQ block — server-side render.
 *
 * CSS is generated at save time by Faq_CSS and printed inline on the front end.
 * This file outputs the accessible accordion markup (a <button> question that
 * toggles its answer region) plus the data attributes view.js needs, and — when
 * enabled — a FAQPage JSON-LD block for rich results. The question/answer text
 * inherits the theme's typography unless the user overrode it.
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

$icon_pos      = 'left' === ( $attributes['iconPosition'] ?? 'right' ) ? 'left' : 'right';
$show_icon     = false !== ( $attributes['showIcon'] ?? true );
$icon_style    = in_array( $attributes['iconStyle'] ?? 'plus', [ 'plus', 'chevron', 'arrow' ], true ) ? $attributes['iconStyle'] : 'plus';
$expand_first  = ! empty( $attributes['expandFirst'] );
$close_others  = false !== ( $attributes['closeOthers'] ?? true );
$allow_toggle  = false !== ( $attributes['allowToggle'] ?? true );
$enable_schema = ! empty( $attributes['enableSchema'] );

// Inline formatting allowed inside the question text.
$question_allowed = [
	'strong' => [],
	'em'     => [],
	'b'      => [],
	'i'      => [],
	'br'     => [],
	'span'   => [],
];

// Tags Google accepts inside a FAQ rich result's answer text — the question
// name must stay plain text, but the answer keeps this markup for SEO.
$schema_answer_allowed = [
	'h1'     => [],
	'h2'     => [],
	'h3'     => [],
	'h4'     => [],
	'h5'     => [],
	'h6'     => [],
	'br'     => [],
	'ol'     => [],
	'ul'     => [],
	'li'     => [],
	'a'      => [ 'href' => true ],
	'p'      => [],
	'div'    => [],
	'b'      => [],
	'strong' => [],
	'i'      => [],
	'em'     => [],
];

/**
 * Build the open/close icon markup for the chosen style. All markup is static
 * (no user input) so it is safe to echo directly.
 */
$icon_html = '';
if ( $show_icon ) {
	if ( 'chevron' === $icon_style ) {
		$icon_html = '<span class="flexa-faq__icon flexa-faq__icon--chevron" aria-hidden="true"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
	} elseif ( 'arrow' === $icon_style ) {
		$icon_html = '<span class="flexa-faq__icon flexa-faq__icon--arrow" aria-hidden="true"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 5v14M6 13l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
	} else {
		$icon_html = '<span class="flexa-faq__icon flexa-faq__icon--plus" aria-hidden="true"></span>';
	}
}

// Build the list items.
$id_base    = '' !== $block_id ? sanitize_html_class( $block_id ) : 'faq';
$items_html = '';
$schema_qa  = [];

foreach ( array_values( $items ) as $index => $item ) {
	$question = wp_kses( (string) ( $item['question'] ?? '' ), $question_allowed );
	$answer   = wp_kses_post( (string) ( $item['answer'] ?? '' ) );

	if ( '' === trim( wp_strip_all_tags( $question ) ) && '' === trim( wp_strip_all_tags( $answer ) ) ) {
		continue;
	}

	$is_open       = 0 === $index && $expand_first;
	$answer_id     = 'flexa-faq-' . $id_base . '-a' . $index;
	$question_id   = 'flexa-faq-' . $id_base . '-q' . $index;
	$open_attr     = $is_open ? ' data-open' : '';
	$expanded      = $is_open ? 'true' : 'false';

	$text_html = '<span class="flexa-faq__question-text">' . $question . '</span>';
	$inner_q   = $show_icon && 'left' === $icon_pos ? $icon_html . $text_html : $text_html . $icon_html;

	$items_html .= '<div class="flexa-faq__item"' . $open_attr . \Flexa\Block\Inline_Editor::item_attr( $index ) . '>';
	$items_html .= '<button type="button" class="flexa-faq__question" id="' . esc_attr( $question_id ) . '" aria-expanded="' . esc_attr( $expanded ) . '" aria-controls="' . esc_attr( $answer_id ) . '">' . $inner_q . '</button>';
	$items_html .= '<div class="flexa-faq__answer" id="' . esc_attr( $answer_id ) . '" role="region" aria-labelledby="' . esc_attr( $question_id ) . '">';
	$items_html .= '<div class="flexa-faq__answer-inner"><div class="flexa-faq__answer-content">' . $answer . '</div></div>';
	$items_html .= '</div></div>';

	if ( $enable_schema ) {
		$schema_qa[] = [
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( $question ),
			'acceptedAnswer' => [
				'@type' => 'Answer',
				'text'  => wp_kses( $answer, $schema_answer_allowed ),
			],
		];
	}
}

if ( '' === $items_html ) {
	return;
}

// Wrapper classes + attributes.
$classes = [ 'flexa-faq', 'flexa-faq--accordion' ];
if ( $show_icon && 'left' === $icon_pos ) {
	$classes[] = 'flexa-faq--icon-left';
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-faq-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Behaviour flags for view.js.
$faq_attrs = ' data-flexa-faq';
if ( $close_others ) {
	$faq_attrs .= ' data-close-others';
}
if ( $allow_toggle ) {
	$faq_attrs .= ' data-allow-toggle';
}

// Lazy background marker.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

// Optional FAQ schema (printed once, before the list).
$schema_html = '';
if ( $enable_schema && ! empty( $schema_qa ) ) {
	$schema = [
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $schema_qa,
	];
	$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( false !== $json ) {
		$schema_html = '<script type="application/ld+json">' . $json . '</script>';
	}
}

printf(
	'<%1$s %2$s%3$s%4$s%5$s>%6$s<div class="flexa-faq__list">%7$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$faq_attrs,          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literals only.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$schema_html,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output, script tag static.
	$items_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- question wp_kses'd, answer wp_kses_post'd, ids esc_attr'd above.
);
