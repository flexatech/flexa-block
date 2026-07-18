<?php
/**
 * Pricing Table block — server-side render.
 *
 * CSS is generated at save time by Pricing_Table_CSS and printed inline on the
 * front end. This file outputs a grid of plan cards: each card carries an
 * optional badge, the plan name, the price + billing period, a feature list and
 * a call-to-action. When the monthly/yearly toggle is on, both a monthly and a
 * yearly price group are printed (monthly shown first, yearly revealed by
 * view.js) preceded by the toggle switch. Text inherits the theme's typography
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

$plans = $attributes['items'] ?? [];
if ( ! is_array( $plans ) ) {
	$plans = [];
}
// Preserve the original attribute keys ($plan_source_keys) so the inline editor
// can map a rendered plan back to its exact `items` entry after non-array
// entries are dropped.
$plans           = array_filter( $plans, 'is_array' );
$plan_source_keys = array_keys( $plans );
$plans           = array_values( $plans );
if ( empty( $plans ) ) {
	return;
}

$container_type = 'boxed' === ( $attributes['containerType'] ?? 'full-width' ) ? 'boxed' : 'full-width';
$has_toggle     = ! empty( $attributes['billingToggle'] );

// Billing switch: its two labels (empty falls back to the defaults) and the
// period the table opens on. view.js takes over from the first click.
$monthly_label  = trim( (string) ( $attributes['billingMonthlyLabel'] ?? '' ) );
$yearly_label   = trim( (string) ( $attributes['billingYearlyLabel'] ?? '' ) );
$monthly_label  = '' !== $monthly_label ? $monthly_label : __( 'Monthly', 'flexa-block' );
$yearly_label   = '' !== $yearly_label ? $yearly_label : __( 'Yearly', 'flexa-block' );
$is_yearly      = $has_toggle && 'yearly' === ( $attributes['billingDefault'] ?? 'monthly' );

/**
 * Build one price group (amount + period).
 *
 * @param string $amount Price text.
 * @param string $period Period text.
 * @param string $mode   'monthly' or 'yearly'.
 * @return string
 */
$price_group = static function ( $amount, $period, $mode ) {
	$amount = (string) $amount;
	$period = (string) $period;
	if ( '' === $amount && '' === $period ) {
		return '';
	}
	$html  = '<span class="flexa-pricing-table__price-group flexa-pricing-table__price-group--' . esc_attr( $mode ) . '" data-billing="' . esc_attr( $mode ) . '">';
	$html .= '<span class="flexa-pricing-table__amount">' . esc_html( $amount ) . '</span>';
	if ( '' !== $period ) {
		$html .= '<span class="flexa-pricing-table__period">' . esc_html( $period ) . '</span>';
	}
	$html .= '</span>';
	return $html;
};

// --- Plan cards. ---
$cards_html = '';
foreach ( $plans as $i => $plan ) {
	$name           = (string) ( $plan['name'] ?? '' );
	$badge          = (string) ( $plan['badge'] ?? '' );
	$cta_text       = (string) ( $plan['ctaText'] ?? '' );
	$cta_url        = (string) ( $plan['ctaUrl'] ?? '' );
	$price_monthly  = (string) ( $plan['priceMonthly'] ?? '' );
	$period_monthly = (string) ( $plan['periodMonthly'] ?? '' );
	$price_yearly   = (string) ( $plan['priceYearly'] ?? '' );
	$period_yearly  = (string) ( $plan['periodYearly'] ?? '' );
	$is_hi          = ! empty( $plan['highlighted'] );

	// Features: a newline-separated string, or an array of lines.
	$raw_features = $plan['features'] ?? '';
	if ( is_array( $raw_features ) ) {
		$feature_lines = $raw_features;
	} else {
		$feature_lines = preg_split( '/\r\n|\r|\n/', (string) $raw_features );
	}
	$feature_lines = array_values( array_filter( array_map( 'trim', array_map( 'strval', (array) $feature_lines ) ), static function ( $l ) {
		return '' !== $l;
	} ) );

	$card = '';
	if ( '' !== $badge ) {
		$card .= '<span class="flexa-pricing-table__badge">' . esc_html( $badge ) . '</span>';
	}
	$card .= '<span class="flexa-pricing-table__name">' . esc_html( '' !== $name ? $name : ( __( 'Plan', 'flexa-block' ) . ' ' . ( (int) $i + 1 ) ) ) . '</span>';

	$card .= '<div class="flexa-pricing-table__price">';
	$card .= $price_group( $price_monthly, $period_monthly, 'monthly' );
	if ( $has_toggle ) {
		$card .= $price_group( $price_yearly, $period_yearly, 'yearly' );
	}
	$card .= '</div>';

	if ( ! empty( $feature_lines ) ) {
		$card .= '<ul class="flexa-pricing-table__features">';
		foreach ( $feature_lines as $line ) {
			$card .= '<li class="flexa-pricing-table__feature">' . esc_html( $line ) . '</li>';
		}
		$card .= '</ul>';
	}

	if ( '' !== $cta_text ) {
		if ( '' !== $cta_url ) {
			$card .= '<a class="flexa-pricing-table__cta wp-element-button" href="' . esc_url( $cta_url ) . '">' . esc_html( $cta_text ) . '</a>';
		} else {
			$card .= '<span class="flexa-pricing-table__cta wp-element-button">' . esc_html( $cta_text ) . '</span>';
		}
	}

	$card_class   = 'flexa-pricing-table__plan' . ( $is_hi ? ' is-highlighted' : '' );
	$source_index = $plan_source_keys[ $i ] ?? $i;
	$cards_html  .= '<div class="' . esc_attr( $card_class ) . '"' . \Flexa\Block\Inline_Editor::item_attr( $source_index ) . '>' . $card . '</div>';
}

// --- Billing toggle switch (only when enabled). ---
$toggle_html = '';
if ( $has_toggle ) {
	$monthly_active = $is_yearly ? '' : ' is-active';
	$yearly_active  = $is_yearly ? ' is-active' : '';

	$toggle_html  = '<div class="flexa-pricing-table__billing" data-billing="' . ( $is_yearly ? 'yearly' : 'monthly' ) . '" role="group" aria-label="' . esc_attr__( 'Billing period', 'flexa-block' ) . '">';
	$toggle_html .= '<span class="flexa-pricing-table__billing-indicator" aria-hidden="true"></span>';
	$toggle_html .= '<button type="button" class="flexa-pricing-table__billing-option' . $monthly_active . '" data-billing-value="monthly" aria-pressed="' . ( $is_yearly ? 'false' : 'true' ) . '">' . esc_html( $monthly_label ) . '</button>';
	$toggle_html .= '<button type="button" class="flexa-pricing-table__billing-option' . $yearly_active . '" data-billing-value="yearly" aria-pressed="' . ( $is_yearly ? 'true' : 'false' ) . '">' . esc_html( $yearly_label ) . '</button>';
	$toggle_html .= '</div>';
}

// --- Wrapper classes + attributes. ---
$classes = [
	'flexa-pricing-table',
	'flexa-pricing-table--' . sanitize_html_class( $container_type ),
];
if ( $has_toggle ) {
	$classes[] = 'flexa-pricing-table--has-toggle';
}
if ( $is_yearly ) {
	// Opens on the yearly price; view.js flips this class from the first click.
	$classes[] = 'is-yearly';
}
if ( '' !== $block_id ) {
	$classes[] = 'flexa-pricing-table-' . sanitize_html_class( $block_id );
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
	'<%1$s %2$s%3$s%4$s>%5$s<div class="flexa-pricing-table__grid">%6$s</div></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$toggle_html,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static labels esc_html__'d.
	$cards_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- name/badge/features/cta esc_html'd, url esc_url'd.
);
