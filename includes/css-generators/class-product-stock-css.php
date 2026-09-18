<?php
declare(strict_types=1);
/**
 * Product Stock block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one product-stock instance.
 * Nothing is emitted unless the user picked a value, so an untouched block reads
 * as ordinary theme text. Layout / background / border / shadow target the
 * wrapper `.flexa-product-stock-<id>`; typography, gap and the badge padding +
 * radius target `.flexa-product-stock__status`; the icon size targets
 * `.flexa-product-stock__icon`; and the four status colour pairs target the
 * per-state modifier of the status element so only the state WooCommerce
 * resolved on the front end is coloured.
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
 * Product Stock CSS generator.
 */
class Product_Stock_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a product-stock instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap   = '.flexa-product-stock-' . $id;
		$status = $wrap . ' .flexa-product-stock__status';
		$icon   = $wrap . ' .flexa-product-stock__icon';

		$in_stock     = $status . '--in-stock';
		$out_of_stock = $status . '--out-of-stock';
		$on_backorder = $status . '--on-backorder';
		$low_stock    = $status . '--low-stock';

		// Padding and radius only make sense once the status is a badge; in text
		// mode they would box in a plain run of text, so they stay unemitted.
		$is_badge  = 'badge' === CSS_Helpers::sanitize_enum( $attrs['displayType'] ?? 'text', [ 'text', 'badge' ], 'text' );
		$show_icon = ! empty( $attrs['showIcon'] );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Alignment / spacing / border / advanced layout on the wrapper.
			CSS_Helpers::add_wrapper_device( $css, $wrap, $attrs, $device );

			CSS_Helpers::add_typography( $css, $status, $attrs['typography'][ $device ] ?? [] );

			// Gap between icon, text and quantity.
			$gap = $attrs['gap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $status )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			if ( $show_icon ) {
				$icon_size = $attrs['iconSize'][ $device ] ?? [];
				if ( ! empty( $icon_size['value'] ) ) {
					$value = CSS_Helpers::with_unit( $icon_size['value'], $icon_size['unit'] ?? 'px' );
					$css->set_selector( $icon )->add_property( 'width', $value )->add_property( 'height', $value );
				}
			}

			if ( $is_badge ) {
				$padding = CSS_Helpers::spacing_shorthand( $attrs['badgePadding'][ $device ] ?? [] );
				if ( '' !== $padding ) {
					$css->set_selector( $status )->add_property( 'padding', $padding );
				}

				$radius = $attrs['badgeRadius'][ $device ] ?? [];
				if ( ! empty( $radius['value'] ) ) {
					$css->set_selector( $status )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
				}
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Status colours (light + dark). They apply in both display types — a badge
		// just adds the padding/radius around them.
		CSS_Helpers::color_pair( $css, $in_stock, 'color', $attrs['inStockColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $in_stock, 'background-color', $attrs['inStockBackground'] ?? '' );
		CSS_Helpers::color_pair( $css, $out_of_stock, 'color', $attrs['outOfStockColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $out_of_stock, 'background-color', $attrs['outOfStockBackground'] ?? '' );
		CSS_Helpers::color_pair( $css, $on_backorder, 'color', $attrs['backorderColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $on_backorder, 'background-color', $attrs['backorderBackground'] ?? '' );
		CSS_Helpers::color_pair( $css, $low_stock, 'color', $attrs['lowStockColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $low_stock, 'background-color', $attrs['lowStockBackground'] ?? '' );

		// Wrapper background / shadow / dark-mode branch.
		CSS_Helpers::add_wrapper_base( $css, $wrap, $attrs );
	}
}
