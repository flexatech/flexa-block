<?php
declare(strict_types=1);
/**
 * Separator block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one separator instance. Nothing
 * is emitted unless the user picked a value, so an untouched separator keeps the
 * theme-driven default line from style.scss (`currentColor`, 1px solid, 100%).
 * Declarations target the line via `.flexa-separator-<id> .flexa-separator__line`
 * and the alignment target is the flex wrapper `.flexa-separator-<id>`.
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
 * Separator CSS generator.
 */
class Separator_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map an alignment keyword to the wrapper's flex justify-content value.
	 *
	 * @var array<string, string>
	 */
	private static $justify = [
		'left'   => 'flex-start',
		'center' => 'center',
		'right'  => 'flex-end',
	];

	/**
	 * Generate CSS for a separator instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$root = '.flexa-separator-' . $id;
		$line = $root . ' .flexa-separator__line';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Alignment: justify-content on the flex wrapper.
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( '' !== $align && isset( self::$justify[ $align ] ) ) {
				$css->set_selector( $root )->add_property( 'justify-content', self::$justify[ $align ] );
			}

			// Line width.
			$width = $attrs['width'][ $device ] ?? [];
			if ( ! empty( $width['value'] ) ) {
				$css->set_selector( $line )->add_property( 'width', CSS_Helpers::with_unit( $width['value'], $width['unit'] ?? '%' ) );
			}

			// Line thickness → border-top-width.
			$weight = $attrs['weight'][ $device ] ?? [];
			if ( ! empty( $weight['value'] ) ) {
				$css->set_selector( $line )->add_property( 'border-top-width', CSS_Helpers::with_unit( $weight['value'], $weight['unit'] ?? 'px' ) );
			}

			// Spacing: padding + margin on the wrapper.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			if ( ! empty( $spacing['padding'] ) ) {
				$padding = CSS_Helpers::spacing_shorthand( $spacing['padding'] );
				if ( '' !== $padding ) {
					$css->set_selector( $root )->add_property( 'padding', $padding );
				}
			}
			if ( ! empty( $spacing['margin'] ) ) {
				$margin = CSS_Helpers::spacing_shorthand( $spacing['margin'] );
				if ( '' !== $margin ) {
					$css->set_selector( $root )->add_property( 'margin', $margin );
				}
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Non-responsive: line style + colour (light at base, dark under dark mode).
		if ( ! empty( $attrs['lineStyle'] ) ) {
			$line_style = CSS_Helpers::sanitize_enum( $attrs['lineStyle'], [ 'solid', 'dashed', 'dotted', 'double' ] );
			if ( '' !== $line_style ) {
				$css->set_selector( $line )->add_property( 'border-top-style', $line_style );
			}
		}

		$colour = CSS_Helpers::light( $attrs['color'] ?? '' );
		if ( '' !== $colour ) {
			$css->set_selector( $line )->add_property( 'border-top-color', $colour );
		}
		CSS_Helpers::dark_color( $css, $line, 'border-top-color', CSS_Helpers::dark( $attrs['color'] ?? '' ) );
	}
}
