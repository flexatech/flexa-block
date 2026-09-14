<?php
/**
 * Tab block — server-side render (child of flexa/tabs).
 *
 * The parent Tabs block builds the nav and the panel wrappers itself (reading
 * each child's label / icon / text and rendering its inner blocks), so this
 * standalone output is only a safe fallback — it is ignored when the tab is
 * rendered inside flexa/tabs.
 *
 * @package Flexa\Block
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    InnerBlocks content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $attributes, $content provided by WP block API.

$text = wpautop(
	wp_kses(
		(string) ( $attributes['text'] ?? '' ),
		[
			'strong' => [],
			'b'      => [],
			'em'     => [],
			'i'      => [],
			'a'      => [ 'href' => true, 'target' => true, 'rel' => true ],
			'br'     => [],
			'span'   => [],
		]
	)
);

$text_html = '' !== trim( wp_strip_all_tags( $text ) ) ? '<div class="flexa-tab__text">' . $text . '</div>' : '';

printf(
	'<div class="flexa-tab">%1$s%2$s</div>',
	$text_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_kses'd above.
	$content    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- InnerBlocks content already rendered/escaped by WP.
);
