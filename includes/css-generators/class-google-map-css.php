<?php
declare(strict_types=1);
/**
 * Google Map block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one map instance. Nothing is
 * emitted unless the user picked a value. The width / spacing / background /
 * advanced-layout declarations target the wrapper `.flexa-google-map-<id>`,
 * while the height / border / shadow declarations target the `__frame` that
 * holds the iframe.
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
 * Google Map CSS generator.
 */
class Google_Map_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a Google Map instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$is_boxed = 'full-width' !== ( $attrs['containerType'] ?? 'boxed' );
		$wrap     = '.flexa-google-map-' . $id;
		$frame    = $wrap . ' .flexa-google-map__frame';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Spacing on the wrapper.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			if ( ! empty( $spacing['padding'] ) ) {
				$padding = CSS_Helpers::spacing_shorthand( $spacing['padding'] );
				if ( '' !== $padding ) {
					$css->set_selector( $wrap )->add_property( 'padding', $padding );
				}
			}
			if ( ! empty( $spacing['margin'] ) ) {
				$margin = CSS_Helpers::spacing_shorthand( $spacing['margin'] );
				if ( '' !== $margin ) {
					$css->set_selector( $wrap )->add_property( 'margin', $margin );
				}
			}

			// Width: max-width (boxed) or width (full-width) on the wrapper.
			$width = $is_boxed ? ( $attrs['widthBoxed'][ $device ] ?? [] ) : ( $attrs['widthFullWidth'][ $device ] ?? [] );
			if ( ! empty( $width['value'] ) ) {
				$value = CSS_Helpers::with_unit( $width['value'], $width['unit'] ?? ( $is_boxed ? 'px' : '%' ) );
				$css->set_selector( $wrap )->add_property( $is_boxed ? 'max-width' : 'width', $value );
			}

			// Height on the frame.
			$height = $attrs['height'][ $device ] ?? [];
			if ( ! empty( $height['value'] ) ) {
				$css->set_selector( $frame )->add_property( 'height', CSS_Helpers::with_unit( $height['value'], $height['unit'] ?? 'px' ) );
			}

			// Border on the frame (radius clips the iframe via overflow:hidden).
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $frame );
				CSS_Helpers::add_border( $css, $border );
			}

			// Advanced layout (z-index / overflow / position) on the wrapper.
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Non-responsive: wrapper background + frame box shadow (light).
		$background = $attrs['background'] ?? [];
		$lazy_bg    = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
		if ( ! empty( $background ) ) {
			$css->set_selector( $wrap );
			CSS_Helpers::add_background( $css, $background, $lazy_bg );
			if ( $lazy_bg ) {
				$css->set_selector( $wrap . '.flexa-bg-loaded' )
					->add_property( 'background-image', 'url(' . esc_url_raw( $background['image']['url'] ) . ')' );
			}
		}

		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $frame )->add_property( 'box-shadow', $shadow );
		}

		// Dark mode: wrapper background; frame border colour + shadow colour.
		CSS_Helpers::add_dark_mode(
			$css,
			$wrap,
			function ( $css ) use ( $background ) {
				$type = $background['type'] ?? 'none';
				if ( 'classic' === $type || 'color' === $type ) {
					$dark = CSS_Helpers::dark( $background['color'] ?? '' );
					if ( '' !== $dark ) {
						$css->add_property( 'background-color', $dark );
					}
				} elseif ( 'gradient' === $type ) {
					$dark = CSS_Helpers::dark( $background['gradient'] ?? '' );
					if ( '' !== $dark ) {
						$css->add_property( 'background-image', $dark );
					}
				}
			}
		);

		$border_dark = CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' );
		$shadow_attr = $attrs['boxShadow'] ?? [];
		$shadow_dark = ! empty( $shadow_attr['enabled'] ) ? CSS_Helpers::dark( $shadow_attr['color'] ?? '' ) : '';
		if ( '' !== $border_dark || '' !== $shadow_dark ) {
			CSS_Helpers::add_dark_mode(
				$css,
				$frame,
				function ( $css ) use ( $border_dark, $shadow_attr, $shadow_dark ) {
					if ( '' !== $border_dark ) {
						$colour = CSS_Helpers::sanitize_color( $border_dark );
						if ( '' !== $colour ) {
							$css->add_property( 'border-color', $colour );
						}
					}
					if ( '' !== $shadow_dark ) {
						$value = CSS_Helpers::box_shadow( $shadow_attr, $shadow_dark );
						if ( '' !== $value ) {
							$css->add_property( 'box-shadow', $value );
						}
					}
				}
			);
		}
	}
}
