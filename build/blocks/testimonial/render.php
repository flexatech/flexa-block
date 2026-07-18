<?php
/**
 * Testimonial block — server-side render.
 *
 * CSS is generated at save time by Testimonial_CSS and printed inline on the
 * front end. This file outputs the card: an optional star rating, the headline,
 * the quote and the author block (avatar + name + role). All text is escaped
 * (wp_kses with a small inline whitelist); the star markup is static. The card
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

$author_layout = 'stacked' === ( $attributes['authorLayout'] ?? 'inline' ) ? 'stacked' : 'inline';

$show_rating = false !== ( $attributes['showRating'] ?? true );
$rating      = (int) round( (float) ( $attributes['rating'] ?? 5 ) );
$rating      = max( 0, min( 5, $rating ) );

$show_avatar = false !== ( $attributes['showAvatar'] ?? true );
$avatar      = $attributes['authorImage'] ?? [];
$avatar_url  = (string) ( $avatar['url'] ?? '' );
$avatar_alt  = (string) ( $avatar['alt'] ?? '' );

$title = (string) ( $attributes['title'] ?? '' );
$quote = (string) ( $attributes['quote'] ?? '' );
$name  = (string) ( $attributes['authorName'] ?? '' );
$job   = (string) ( $attributes['authorJob'] ?? '' );

// Inline formatting allowed inside each text field.
$inline_allowed = [
	'strong' => [],
	'em'     => [],
	'b'      => [],
	'i'      => [],
	'br'     => [],
	'span'   => [],
];
$quote_allowed = $inline_allowed + [ 'a' => [ 'href' => true, 'target' => true, 'rel' => true ] ];

// Star rating — static markup (no user input), so it is safe to echo directly.
$rating_html = '';
if ( $show_rating ) {
	$star = '<svg class="%s" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L3.6 9.4l6.5-.9L12 2.6Z" fill="currentColor"/></svg>';
	$stars = '';
	for ( $i = 0; $i < 5; $i++ ) {
		$cls    = $i < $rating ? 'flexa-testimonial__star' : 'flexa-testimonial__star flexa-testimonial__star--empty';
		$stars .= sprintf( $star, esc_attr( $cls ) );
	}
	$label       = sprintf(
		/* translators: %d: star rating out of five. */
		esc_attr__( 'Rated %d out of 5', 'flexa-block' ),
		$rating
	);
	$rating_html = '<div class="flexa-testimonial__rating" role="img" aria-label="' . $label . '">' . $stars . '</div>';
}

// Title + quote.
$title_html = '' !== trim( $title ) ? '<p class="flexa-testimonial__title">' . wp_kses( $title, $inline_allowed ) . '</p>' : '';
$quote_html = '' !== trim( $quote ) ? '<blockquote class="flexa-testimonial__quote">' . wp_kses( $quote, $quote_allowed ) . '</blockquote>' : '';

// Author block.
$avatar_html = '';
if ( $show_avatar && '' !== $avatar_url ) {
	$avatar_html = '<img class="flexa-testimonial__avatar" src="' . esc_url( $avatar_url ) . '" alt="' . esc_attr( $avatar_alt ) . '" loading="lazy" decoding="async" />';
}

$name_html = '' !== trim( $name ) ? '<span class="flexa-testimonial__name">' . wp_kses( $name, $inline_allowed ) . '</span>' : '';
$job_html  = '' !== trim( $job ) ? '<span class="flexa-testimonial__job">' . wp_kses( $job, $inline_allowed ) . '</span>' : '';

$author_html = '';
if ( '' !== $avatar_html || '' !== $name_html || '' !== $job_html ) {
	$author_html  = '<div class="flexa-testimonial__author">';
	$author_html .= $avatar_html;
	if ( '' !== $name_html || '' !== $job_html ) {
		$author_html .= '<div class="flexa-testimonial__author-text">' . $name_html . $job_html . '</div>';
	}
	$author_html .= '</div>';
}

$inner = $rating_html . $title_html . $quote_html . $author_html;
if ( '' === $inner ) {
	return;
}

// Wrapper classes + attributes.
$classes = [ 'flexa-testimonial', 'flexa-testimonial--author-' . $author_layout ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-testimonial-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [ 'class' => implode( ' ', $classes ) ];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Lazy background: mark the wrapper so view.js reveals the image near the viewport.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

printf(
	'<%1$s %2$s%3$s%4$s>%5$s</%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$inner               // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- star markup static, text wp_kses'd, avatar src/alt escaped above.
);
