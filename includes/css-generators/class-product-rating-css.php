<?php
declare(strict_types=1);
/**
 * Product Rating block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one product-rating instance.
 * Nothing is emitted unless the user picked a value, so an untouched rating
 * keeps the theme colour (filled stars solid, empty stars faint) and the base
 * layout from style.scss. Layout / background / border / shadow target the
 * wrapper `.flexa-product-rating-<id>`; the star size targets each
 * `.flexa-product-rating__star`, the gap the `.flexa-product-rating__stars`
 * row, the filled/empty colours the `__stars-fill` / `__stars-base` rows, and
 * the numeric-score colour + typography the `.flexa-product-rating__number`; the count colour + typography the `.flexa-product-rating__count`.
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
 * Product Rating CSS generator.
 */
class Product_Rating_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a product-rating instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap  = '.flexa-product-rating-' . $id;
		$stars = $wrap . ' .flexa-product-rating__stars';
		$star  = $wrap . ' .flexa-product-rating__star';
		$fill  = $wrap . ' .flexa-product-rating__stars-fill';
		$base   = $wrap . ' .flexa-product-rating__stars-base';
		$number = $wrap . ' .flexa-product-rating__number';
		$count  = $wrap . ' .flexa-product-rating__count';

		$show_count = ! isset( $attrs['showReviewCount'] ) || ! empty( $attrs['showReviewCount'] );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Alignment on the wrapper (inline children flow with text-align).
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( '' !== $align ) {
				$css->set_selector( $wrap )->add_property( 'text-align', $align );
			}

			// Star size (width + height on each star box).
			$size = $attrs['starSize'][ $device ] ?? [];
			if ( ! empty( $size['value'] ) ) {
				$value = CSS_Helpers::with_unit( $size['value'], $size['unit'] ?? 'px' );
				$css->set_selector( $star )->add_property( 'width', $value )->add_property( 'height', $value );
			}

			// Gap between stars (inherited by both rows).
			$gap = $attrs['starGap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $stars )->add_property( 'column-gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Numeric-score typography.
			CSS_Helpers::add_typography( $css, $number, $attrs['numberTypography'][ $device ] ?? [] );

			// Review-count typography.
			if ( $show_count ) {
				CSS_Helpers::add_typography( $css, $count, $attrs['countTypography'][ $device ] ?? [] );
			}

			// Spacing: padding + margin on the wrapper.
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

			// Border on the wrapper.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_border( $css, $border );
			}

			// Advanced layout (overflow / position / z-index) on the wrapper.
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Star colours — filled on the fill row, empty on the base row (light + dark).
		CSS_Helpers::color_pair( $css, $fill, 'color', $attrs['starColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $base, 'color', $attrs['starEmptyColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $number, 'color', $attrs['numberColor'] ?? '' );

		// Review-count colour (light + dark), only when the count is shown.
		if ( $show_count ) {
			CSS_Helpers::color_pair( $css, $count, 'color', $attrs['countColor'] ?? '' );
		}

		// Background + box shadow on the wrapper (base, light).
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
			$css->set_selector( $wrap )->add_property( 'box-shadow', $shadow );
		}

		// Dark mode for the wrapper (background / border / shadow).
		CSS_Helpers::add_dark_mode(
			$css,
			$wrap,
			function ( $css ) use ( $attrs, $background ) {
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

				$border_dark = CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' );
				if ( '' !== $border_dark ) {
					$css->add_property( 'border-color', $border_dark );
				}

				$shadow = $attrs['boxShadow'] ?? [];
				if ( ! empty( $shadow['enabled'] ) ) {
					$shadow_dark = CSS_Helpers::dark( $shadow['color'] ?? '' );
					if ( '' !== $shadow_dark ) {
						$value = CSS_Helpers::box_shadow( $shadow, $shadow_dark );
						if ( '' !== $value ) {
							$css->add_property( 'box-shadow', $value );
						}
					}
				}
			}
		);
	}
}
