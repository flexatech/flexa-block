<?php
declare(strict_types=1);
/**
 * Product Field block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one product-field instance —
 * every inserter variation (SKU / Categories / Tags) runs through here, because
 * they are one block with a different `field`. Nothing is emitted unless the
 * user picked a value, so an untouched field reads as ordinary theme text.
 *
 * PRECEDENCE. A field sitting inside Product Meta is styled twice: the parent
 * emits `.flexa-product-meta-<parent> .flexa-product-field__label` for every
 * field it holds, and this generator emits
 * `.flexa-product-field-<child> .flexa-product-field__label` for one of them.
 * Both selectors weigh the same (0,2,0), so the later rule wins — and the
 * generator service walks a block before recursing into its inner blocks
 * (Flexa\Block\CSS_Generator_Service::process_blocks), which puts the child's
 * rule after the parent's. That is the intended order: the parent sets the
 * shared look, a field overrides it. ProductFieldCssTest pins it down.
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
 * Product Field CSS generator.
 */
class Product_Field_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a product-field instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap  = '.flexa-product-field-' . $id;
		$label = $wrap . ' .flexa-product-field__label';
		$value = $wrap . ' .flexa-product-field__value';
		$terms = $wrap . ' .flexa-product-field__terms';
		$term  = $wrap . ' .flexa-product-field__term';
		$hover = $term . ':hover';

		// Term options are dead weight on a SKU field, which renders no terms.
		$is_terms = 'sku' !== CSS_Helpers::sanitize_enum( $attrs['field'] ?? 'sku', [ 'sku', 'categories', 'tags' ], 'sku' );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Alignment / spacing / border / advanced layout on the wrapper.
			CSS_Helpers::add_wrapper_device( $css, $wrap, $attrs, $device );

			// Gap between the label and the value (and, on a SKU field, between
			// the value and the copy button).
			$label_gap = $attrs['labelGap'][ $device ] ?? [];
			if ( ! empty( $label_gap['value'] ) ) {
				$gap = CSS_Helpers::with_unit( $label_gap['value'], $label_gap['unit'] ?? 'px' );
				$css->set_selector( $wrap )->add_property( 'column-gap', $gap );
				$css->set_selector( $value )->add_property( 'gap', $gap );
			}

			CSS_Helpers::add_typography( $css, $label, $attrs['labelTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $value, $attrs['valueTypography'][ $device ] ?? [] );

			if ( ! $is_terms ) {
				CSS_Helpers::close_device( $css, $device );
				continue;
			}

			// Terms are links inside the value, so they take the value's type too.
			CSS_Helpers::add_typography( $css, $term, $attrs['valueTypography'][ $device ] ?? [] );

			$term_gap = $attrs['termGap'][ $device ] ?? [];
			if ( ! empty( $term_gap['value'] ) ) {
				$css->set_selector( $terms )->add_property( 'gap', CSS_Helpers::with_unit( $term_gap['value'], $term_gap['unit'] ?? 'px' ) );
			}

			// Chip box on each term: padding, radius and a solid outline driven by
			// a single width (the colour is a light/dark pair, emitted below).
			$padding = CSS_Helpers::spacing_shorthand( $attrs['itemPadding'][ $device ] ?? [] );
			if ( '' !== $padding ) {
				$css->set_selector( $term )->add_property( 'padding', $padding );
			}

			$radius = $attrs['itemRadius'][ $device ] ?? [];
			if ( ! empty( $radius['value'] ) ) {
				$css->set_selector( $term )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
			}

			$border_width = $attrs['itemBorderWidth'][ $device ] ?? [];
			if ( ! empty( $border_width['value'] ) ) {
				$css->set_selector( $term )
					->add_property( 'border-style', 'solid' )
					->add_property( 'border-width', CSS_Helpers::with_unit( $border_width['value'], $border_width['unit'] ?? 'px' ) );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Label and value colours (light + dark).
		CSS_Helpers::color_pair( $css, $label, 'color', $attrs['labelColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $value, 'color', $attrs['valueColor'] ?? '' );

		if ( ! $is_terms ) {
			CSS_Helpers::add_wrapper_base( $css, $wrap, $attrs );
			return;
		}

		// The terms follow the value…
		CSS_Helpers::color_pair( $css, $term, 'color', $attrs['valueColor'] ?? '' );

		// …unless the term box says otherwise. Emitted after the value colour so
		// the builder's later write wins for the same selector + property.
		CSS_Helpers::color_pair( $css, $term, 'color', $attrs['itemColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $term, 'background', $attrs['itemBackground'] ?? '' );
		CSS_Helpers::color_pair( $css, $hover, 'color', $attrs['itemColorHover'] ?? '' );
		CSS_Helpers::color_pair( $css, $hover, 'background', $attrs['itemBackgroundHover'] ?? '' );
		CSS_Helpers::color_pair( $css, $term, 'border-color', $attrs['itemBorderColor'] ?? '' );

		// Wrapper background / shadow / dark-mode branch.
		CSS_Helpers::add_wrapper_base( $css, $wrap, $attrs );
	}
}
