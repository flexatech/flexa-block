<?php
/**
 * Tabs block — server-side render.
 *
 * CSS is generated at save time by Tabs_CSS and printed inline on the front end.
 * This file outputs the accessible tab markup: a role="tablist" bar of <button>
 * tabs plus one role="tabpanel" per tab, with the active panel visible by default
 * so it works without JS. Each panel also carries a mobile accordion header (the
 * bar collapses to an accordion on small screens, driven by view.js). Tab labels
 * are plain text; panel content runs through wp_kses with a small whitelist, and
 * icons through svg_kses — so stored data can never become an XSS vector.
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

$tabs = $attributes['tabs'] ?? [];
if ( ! is_array( $tabs ) ) {
	$tabs = [];
}

// Keep only tabs that have a label or some content. Preserve the original
// attribute keys ($tab_source_keys) so the inline editor can map a rendered tab
// back to its exact `tabs` array entry even after empty tabs are dropped.
$filtered_tabs = array_filter(
	$tabs,
	static function ( $tab ) {
		return is_array( $tab )
			&& ( '' !== trim( (string) ( $tab['label'] ?? '' ) ) || '' !== trim( (string) ( $tab['content'] ?? '' ) ) );
	}
);
$tab_source_keys = array_keys( $filtered_tabs );
$tabs            = array_values( $filtered_tabs );
if ( empty( $tabs ) ) {
	return;
}

$tab_style = in_array( $attributes['tabStyle'] ?? 'underline', [ 'underline', 'pill', 'boxed' ], true ) ? $attributes['tabStyle'] : 'underline';
$tab_align = in_array( $attributes['tabAlign'] ?? 'left', [ 'left', 'center', 'right', 'justify' ], true ) ? $attributes['tabAlign'] : 'left';
$show_icon = false !== ( $attributes['showIcon'] ?? true );

$count        = count( $tabs );
$active_index = (int) ( $attributes['activeTab'] ?? 0 );
if ( $active_index < 0 || $active_index >= $count ) {
	$active_index = 0;
}

// Tags allowed inside a tab's panel content (plain multiline text, formatted).
$content_allowed = [
	'p'      => [],
	'br'     => [],
	'strong' => [],
	'b'      => [],
	'em'     => [],
	'i'      => [],
	'u'      => [],
	'span'   => [],
	'a'      => [ 'href' => true, 'target' => true, 'rel' => true ],
	'ul'     => [],
	'ol'     => [],
	'li'     => [],
	'h2'     => [],
	'h3'     => [],
	'h4'     => [],
];

/**
 * Build the icon markup for one tab (inline SVG for builtin/library, <img> for an
 * uploaded SVG). Returns '' when icons are off or the tab has none.
 *
 * @param array $tab Tab data.
 * @return string
 */
$icon_html = static function ( $tab ) use ( $show_icon ) {
	if ( ! $show_icon ) {
		return '';
	}
	$icon = is_array( $tab['icon'] ?? null ) ? $tab['icon'] : [];
	if ( 'upload' === ( $icon['source'] ?? '' ) && '' !== (string) ( $icon['url'] ?? '' ) ) {
		return '<span class="flexa-tabs__icon"><img class="flexa-icon" src="' . esc_url( $icon['url'] ) . '" alt="" width="20" height="20" loading="lazy" /></span>';
	}
	if ( '' !== (string) ( $icon['markup'] ?? '' ) ) {
		$svg = HTML_Helpers::svg_kses( $icon['markup'] );
		if ( '' !== $svg ) {
			return '<span class="flexa-tabs__icon" aria-hidden="true">' . $svg . '</span>';
		}
	}
	return '';
};

$id_base    = '' !== $block_id ? sanitize_html_class( $block_id ) : 'tabs';
$nav_html    = '';
$panels_html = '';

foreach ( $tabs as $index => $tab ) {
	$label        = esc_html( (string) ( $tab['label'] ?? '' ) );
	$icon         = $icon_html( $tab );
	$content      = wpautop( wp_kses( (string) ( $tab['content'] ?? '' ), $content_allowed ) );
	$is_active    = $index === $active_index;
	$source_index = $tab_source_keys[ $index ] ?? $index;
	$edit_attr    = \Flexa\Block\Inline_Editor::item_attr( $source_index );

	$tab_id   = 'flexa-tabs-' . $id_base . '-t' . $index;
	$panel_id = 'flexa-tabs-' . $id_base . '-p' . $index;
	$selected = $is_active ? 'true' : 'false';
	$expanded = $is_active ? 'true' : 'false';
	$tabindex = $is_active ? '0' : '-1';

	$label_html = '<span class="flexa-tabs__label">' . $label . '</span>';

	// Nav tab (horizontal bar).
	$tab_class = 'flexa-tabs__tab' . ( $is_active ? ' is-active' : '' );
	$nav_html .= '<button type="button" class="' . esc_attr( $tab_class ) . '" role="tab" id="' . esc_attr( $tab_id ) . '"'
		. ' aria-selected="' . $selected . '" aria-controls="' . esc_attr( $panel_id ) . '" tabindex="' . $tabindex . '"' . $edit_attr . '>'
		. $icon . $label_html . '</button>';

	// Panel + its mobile accordion header (shares the tab styling via .flexa-tabs__tab).
	$wrap_class   = 'flexa-tabs__panel-wrap' . ( $is_active ? ' is-active is-open' : '' );
	$header_class = 'flexa-tabs__tab flexa-tabs__accordion-header' . ( $is_active ? ' is-active' : '' );

	$panels_html .= '<div class="' . esc_attr( $wrap_class ) . '" data-index="' . (int) $index . '"' . $edit_attr . '>';
	$panels_html .= '<button type="button" class="' . esc_attr( $header_class ) . '" aria-expanded="' . $expanded . '" aria-controls="' . esc_attr( $panel_id ) . '">'
		. $icon . $label_html . '</button>';
	$panels_html .= '<div class="flexa-tabs__panel" id="' . esc_attr( $panel_id ) . '" role="tabpanel" aria-labelledby="' . esc_attr( $tab_id ) . '">' . $content . '</div>';
	$panels_html .= '</div>';
}

// Wrapper classes + attributes.
$classes = [
	'flexa-tabs',
	'flexa-tabs--' . sanitize_html_class( $tab_style ),
	'flexa-tabs--align-' . sanitize_html_class( $tab_align ),
];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-tabs-' . sanitize_html_class( $block_id );
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
	'<%1$s %2$s%3$s data-flexa-tabs%4$s><div class="flexa-tabs__nav" role="tablist"><span class="flexa-tabs__indicator" aria-hidden="true"></span>%5$s</div><div class="flexa-tabs__panels">%6$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$nav_html,           // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- labels esc_html'd, icons svg_kses'd, ids esc_attr'd above.
	$panels_html         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- content wp_kses'd, labels esc_html'd, ids esc_attr'd above.
);
