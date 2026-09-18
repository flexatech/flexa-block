<?php
declare(strict_types=1);
/**
 * Product Meta block — server-side CSS generator.
 *
 * Product Meta is a container of flexa/product-field rows, so this generator
 * styles two things: the list itself (gap between rows, divider, foundation),
 * and the defaults every row inside it starts from — the label column that lines
 * the rows up, plus the label and value typography and colour.
 *
 * It reaches the rows with a descendant selector against the shared
 * `flexa-product-field__*` classes, exactly the way Subscribe Form styles its
 * fields. A row can still override any of it: Product_Field_CSS emits the same
 * weight (0,2,0) but later, because the generator service walks a block before
 * recursing into its inner blocks. Parent sets the look, row overrides it.
 *
 * Nothing is emitted unless the user picked a value, so an untouched list reads
 * as ordinary theme text.
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
 * Product Meta CSS generator.
 */
class Product_Meta_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a product-meta instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap = '.flexa-product-meta-' . $id;
		$row  = $wrap . ' .flexa-product-field';
		$row2 = $wrap . ' > .flexa-product-field + .flexa-product-field';

		$label = $wrap . ' .flexa-product-field__label';
		$value = $wrap . ' .flexa-product-field__value';
		$term  = $wrap . ' .flexa-product-field__term';

		$has_divider = ! empty( $attrs['showDivider'] );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Alignment / spacing / border / advanced layout on the wrapper.
			CSS_Helpers::add_wrapper_device( $css, $wrap, $attrs, $device );

			// Rows are stacked by style.scss, so the wrapper's row-gap spaces them.
			$gap = $attrs['rowGap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'row-gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Gap between every row's label and its value.
			$label_gap = $attrs['labelGap'][ $device ] ?? [];
			if ( ! empty( $label_gap['value'] ) ) {
				$css->set_selector( $row )->add_property( 'column-gap', CSS_Helpers::with_unit( $label_gap['value'], $label_gap['unit'] ?? 'px' ) );
			}

			// The label column: a fixed width on every row's label is what turns a
			// stack of independent rows into a table whose values line up.
			$width = $attrs['labelWidth'][ $device ] ?? [];
			if ( ! empty( $width['value'] ) ) {
				$css->set_selector( $label )->add_property( 'flex', '0 0 ' . CSS_Helpers::with_unit( $width['value'], $width['unit'] ?? 'px' ) );
			}

			// Shared typography for every row.
			CSS_Helpers::add_typography( $css, $label, $attrs['labelTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $value, $attrs['valueTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $term, $attrs['valueTypography'][ $device ] ?? [] );

			if ( $has_divider ) {
				$divider = $attrs['dividerWidth'][ $device ] ?? [];
				if ( ! empty( $divider['value'] ) ) {
					$css->set_selector( $row2 )->add_property( 'border-top-width', CSS_Helpers::with_unit( $divider['value'], $divider['unit'] ?? 'px' ) );
				}
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Shared colours for every row; the terms follow the value so a linked
		// category looks like the plain SKU above it.
		CSS_Helpers::color_pair( $css, $label, 'color', $attrs['labelColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $value, 'color', $attrs['valueColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $term, 'color', $attrs['valueColor'] ?? '' );

		if ( $has_divider ) {
			CSS_Helpers::color_pair( $css, $row2, 'border-top-color', $attrs['dividerColor'] ?? '' );
		}

		// Wrapper background / shadow / dark-mode branch.
		CSS_Helpers::add_wrapper_base( $css, $wrap, $attrs );
	}
}
