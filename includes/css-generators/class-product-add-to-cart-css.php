<?php
declare(strict_types=1);
/**
 * Add to Cart block — server-side CSS generator.
 *
 * The markup this styles is WooCommerce's own add-to-cart template, so the
 * selectors are core's (`form.cart`, `.quantity input.qty`,
 * `button.single_add_to_cart_button`, `.variations label|select`) nested under
 * the block wrapper `.flexa-product-add-to-cart-<id>` — we cannot rename them.
 *
 * Nothing is emitted unless the user picked a value, so an untouched block looks
 * exactly like the theme's own add-to-cart form.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\CSS_Generators;

use Flexa\Block\CSS_Builder;
use Flexa\Block\CSS_Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add to Cart CSS generator.
 */
class Product_Add_To_Cart_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a product-add-to-cart instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap       = '.flexa-product-add-to-cart-' . $id;
		$form       = $wrap . ' form.cart';
		$qty        = $wrap . ' .quantity';
		$qty_group  = $wrap . ' .quantity.flexa-qty';
		$qty_grouped_input = $qty_group . ' input.qty';
		$qty_step   = $wrap . ' .flexa-qty__btn';
		$qty_input  = $wrap . ' .quantity input.qty';
		$button     = $wrap . ' button.single_add_to_cart_button';
		$var_label  = $wrap . ' .variations label';
		$var_select = $wrap . ' .variations select';

		$layout    = CSS_Helpers::sanitize_enum( $attrs['cartLayout'] ?? 'inline', [ 'inline', 'stacked', 'button-only' ], 'inline' );
		$position  = CSS_Helpers::sanitize_enum( $attrs['iconPosition'] ?? 'before', [ 'before', 'after' ], 'before' );
		$icon      = $button . '::' . $position;
		$show_icon = ! empty( $attrs['showIcon'] );

		// `button-only` hides the field outright, so quantity styling would be dead
		// weight; the same goes for the author switching the field off by hand.
		// The attribute defaults to true, so a missing key means "shown".
		$show_qty = array_key_exists( 'showQuantity', $attrs ) ? ! empty( $attrs['showQuantity'] ) : true;
		$has_qty  = 'button-only' !== $layout && $show_qty;

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Alignment / spacing / border / advanced layout on the wrapper.
			CSS_Helpers::add_wrapper_device( $css, $wrap, $attrs, $device );

			// Alignment. The form is a flex row, so `text-align` on the wrapper
			// moves nothing — `justify-content` is what shifts the field and the
			// button. A stacked form lays out down the page instead, so there the
			// same intent has to travel on `align-items`.
			$align = CSS_Helpers::flex_align( $attrs['alignment'][ $device ] ?? '' );
			if ( '' !== $align ) {
				$css->set_selector( $form )->add_property( 'justify-content', $align );
				if ( 'stacked' === $layout ) {
					$css->set_selector( $form )->add_property( 'align-items', $align );
				}
			}

			$gap = $attrs['gap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $form )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Button label typography — `add_button()` covers colour/box, not text.
			CSS_Helpers::add_typography( $css, $button, $attrs['buttonTypography'][ $device ] ?? [] );

			if ( $has_qty ) {
				$width = $attrs['quantityWidth'][ $device ] ?? [];
				if ( ! empty( $width['value'] ) ) {
					$value = CSS_Helpers::with_unit( $width['value'], $width['unit'] ?? 'px' );
					$css->set_selector( $qty )->add_property( 'width', $value );
					$css->set_selector( $qty_input )->add_property( 'width', $value );
				}

				$height = $attrs['quantityHeight'][ $device ] ?? [];
				if ( ! empty( $height['value'] ) ) {
					$css->set_selector( $qty_input )->add_property( 'height', CSS_Helpers::with_unit( $height['value'], $height['unit'] ?? 'px' ) );
				}

				$border_width = $attrs['quantityBorderWidth'][ $device ] ?? [];
				if ( ! empty( $border_width['value'] ) ) {
					$css->set_selector( $qty_input )
						->add_property( 'border-style', 'solid' )
						->add_property( 'border-width', CSS_Helpers::with_unit( $border_width['value'], $border_width['unit'] ?? 'px' ) );
				}

				$radius = $attrs['quantityRadius'][ $device ] ?? [];
				if ( ! empty( $radius['value'] ) ) {
					$css->set_selector( $qty_input )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
				}

				CSS_Helpers::add_typography( $css, $qty_input, $attrs['quantityTypography'][ $device ] ?? [] );

				// Once view.js has wrapped the field, the box the author styled is
				// the GROUP (minus, input, plus) rather than the input alone. Both
				// are emitted, and the input's own border is dropped only inside
				// the group — so a page where the script never ran still shows a
				// bordered field instead of a bare one.
				if ( ! empty( $border_width['value'] ) ) {
					$css->set_selector( $qty_group )
						->add_property( 'border-style', 'solid' )
						->add_property( 'border-width', CSS_Helpers::with_unit( $border_width['value'], $border_width['unit'] ?? 'px' ) );
					$css->set_selector( $qty_grouped_input )->add_property( 'border-width', '0' );
				}

				if ( ! empty( $radius['value'] ) ) {
					$css->set_selector( $qty_group )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
				}

				// No height on the group: it is an `align-items: stretch` flex box,
				// so it takes the input's height and the buttons match it. Setting
				// both would count the border twice and overflow the box.
			}

			// Variation label / select typography.
			CSS_Helpers::add_typography( $css, $var_label, $attrs['variationLabelTypography'][ $device ] ?? [] );

			if ( $show_icon ) {
				$icon_size = $attrs['iconSize'][ $device ] ?? [];
				if ( ! empty( $icon_size['value'] ) ) {
					$value = CSS_Helpers::with_unit( $icon_size['value'], $icon_size['unit'] ?? 'px' );
					$css->set_selector( $icon )->add_property( 'width', $value )->add_property( 'height', $value );
				}

				$icon_gap = $attrs['iconGap'][ $device ] ?? [];
				if ( ! empty( $icon_gap['value'] ) ) {
					$value = CSS_Helpers::with_unit( $icon_gap['value'], $icon_gap['unit'] ?? 'px' );
					$css->set_selector( $icon )->add_property( 'before' === $position ? 'margin-right' : 'margin-left', $value );
				}
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Button colours (base + hover), radius, padding, alignment and width.
		CSS_Helpers::add_button( $css, $button, $attrs );

		if ( $has_qty ) {
			CSS_Helpers::color_pair( $css, $qty_input, 'color', $attrs['quantityColor'] ?? '' );
			CSS_Helpers::color_pair( $css, $qty_step, 'color', $attrs['quantityColor'] ?? '' );
			CSS_Helpers::color_pair( $css, $qty_input, 'background-color', $attrs['quantityBackground'] ?? '' );
			CSS_Helpers::color_pair( $css, $qty_group, 'background-color', $attrs['quantityBackground'] ?? '' );
			CSS_Helpers::color_pair( $css, $qty_input, 'border-color', $attrs['quantityBorderColor'] ?? '' );
			CSS_Helpers::color_pair( $css, $qty_group, 'border-color', $attrs['quantityBorderColor'] ?? '' );
			CSS_Helpers::color_pair( $css, $qty_step, 'background-color', $attrs['quantityBackground'] ?? '' );
		} else {
			// Core's template always prints the field; hiding it is the only way to
			// honour `button-only` / the quantity toggle.
			$css->set_selector( $qty )->add_property( 'display', 'none' );
		}

		CSS_Helpers::color_pair( $css, $var_label, 'color', $attrs['variationLabelColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $var_select, 'color', $attrs['variationSelectColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $var_select, 'background-color', $attrs['variationSelectBackground'] ?? '' );
		CSS_Helpers::color_pair( $css, $var_select, 'border-color', $attrs['variationSelectBorderColor'] ?? '' );

		// Wrapper background / shadow / dark-mode branch.
		CSS_Helpers::add_wrapper_base( $css, $wrap, $attrs );
	}
}
