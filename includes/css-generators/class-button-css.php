<?php
declare(strict_types=1);
/**
 * Button block - server-side CSS generator.
 *
 * Produces responsive spacing plus dark-mode-aware color / radius / hover CSS
 * for one button instance (selector: .flexa-button-<id>).
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
 * Button CSS generator.
 */
class Button_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a button instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$selector = '.flexa-button-' . $id;
		$variant  = $attrs['variant'] ?? 'fill';

		// Responsive spacing (padding + margin) on the anchor.
		foreach ( self::$devices as $device ) {
			if ( 'desktop' !== $device ) {
				$css->start_media_query( $device );
			}

			$spacing = $attrs['spacing'][ $device ] ?? [];
			if ( ! empty( $spacing['padding'] ) ) {
				$padding = CSS_Helpers::spacing_shorthand( $spacing['padding'] );
				if ( '' !== $padding ) {
					$css->set_selector( $selector )->add_property( 'padding', $padding );
				}
			}
			if ( ! empty( $spacing['margin'] ) ) {
				$margin = CSS_Helpers::spacing_shorthand( $spacing['margin'] );
				if ( '' !== $margin ) {
					$css->set_selector( $selector )->add_property( 'margin', $margin );
				}
			}

			if ( 'desktop' !== $device ) {
				$css->end_media_query();
			}
		}

		// Base (non-responsive): colors + radius.
		self::apply_colors( $css, $selector, $variant, $attrs['textColor'] ?? [], $attrs['bgColor'] ?? [] );

		$radius = $attrs['radius'] ?? [];
		if ( ! empty( $radius['value'] ) ) {
			$css->set_selector( $selector )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
		}

		// Hover colors.
		self::apply_colors( $css, $selector . ':hover', $variant, $attrs['hoverTextColor'] ?? [], $attrs['hoverBgColor'] ?? [] );

		// Dark mode: base + hover.
		self::apply_dark_colors( $css, $selector, $variant, $attrs['textColor'] ?? [], $attrs['bgColor'] ?? [] );
		self::apply_dark_colors( $css, $selector . ':hover', $variant, $attrs['hoverTextColor'] ?? [], $attrs['hoverBgColor'] ?? [] );
	}

	/**
	 * Emit light (base) text / background color for a selector, respecting the
	 * outline variant (which paints the border instead of the background).
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param string      $variant  Button variant.
	 * @param mixed       $text     Text color pair.
	 * @param mixed       $bg       Background color pair.
	 */
	private static function apply_colors( $css, $selector, $variant, $text, $bg ) {
		$text_light = CSS_Helpers::light( $text );
		$bg_light   = CSS_Helpers::light( $bg );
		if ( '' === $text_light && '' === $bg_light ) {
			return;
		}

		$css->set_selector( $selector );
		if ( '' !== $text_light ) {
			$css->add_property( 'color', $text_light );
		}
		if ( '' !== $bg_light ) {
			if ( 'outline' === $variant ) {
				$css->add_property( 'border-color', $bg_light );
			}
			$css->add_property( 'background-color', $bg_light );
		}
	}

	/**
	 * Emit dark-mode text / background color for a selector.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param string      $variant  Button variant.
	 * @param mixed       $text     Text color pair.
	 * @param mixed       $bg       Background color pair.
	 */
	private static function apply_dark_colors( $css, $selector, $variant, $text, $bg ) {
		CSS_Helpers::add_dark_mode(
			$css,
			$selector,
			function ( $css ) use ( $variant, $text, $bg ) {
				$text_dark = CSS_Helpers::dark( $text );
				$bg_dark   = CSS_Helpers::dark( $bg );
				if ( '' !== $text_dark ) {
					$css->add_property( 'color', $text_dark );
				}
				if ( '' !== $bg_dark ) {
					if ( 'outline' === $variant ) {
						$css->add_property( 'border-color', $bg_dark );
					}
					$css->add_property( 'background-color', $bg_dark );
				}
			}
		);
	}
}
