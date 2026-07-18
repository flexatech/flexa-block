<?php
declare(strict_types=1);
/**
 * Slider block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one slider instance: the box
 * (spacing / background / border / shadow / position), the slide height, the
 * navigation arrows and the pagination dots. Behaviour (autoplay / loop /
 * effect / slides-per-view) is JS-only and carried in a data attribute, so it
 * emits no CSS here. Nothing is emitted unless the user picked a value.
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
 * Slider CSS generator.
 */
class Slides_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a slider instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$root         = '.flexa-slides-' . $id;
		$slide        = $root . ' .swiper-slide';
		$arrow        = $root . ' .flexa-slides__arrow';
		$arrow_svg    = $arrow . ' svg';
		$arrow_hover  = $arrow . ':hover';
		$prev         = $root . ' .swiper-button-prev';
		$next         = $root . ' .swiper-button-next';
		$bullet       = $root . ' .swiper-pagination-bullet';
		$bullet_on    = $root . ' .swiper-pagination-bullet-active';

		// --- Responsive box (padding/margin, slide height, border, position). ---
		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

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

			$min_height = $attrs['minHeight'][ $device ] ?? [];
			if ( ! empty( $min_height['value'] ) ) {
				$css->set_selector( $slide )->add_property( 'min-height', CSS_Helpers::with_unit( $min_height['value'], $min_height['unit'] ?? 'px' ) );
			}

			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $root );
				CSS_Helpers::add_border( $css, $border );
			}

			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $root );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// --- Arrows (base + hover, light at base, dark under the dark branch). ---
		$arrow_size = $attrs['arrowSize'] ?? [];
		if ( ! empty( $arrow_size['value'] ) ) {
			$value = CSS_Helpers::with_unit( $arrow_size['value'], $arrow_size['unit'] ?? 'px' );
			$css->set_selector( $arrow_svg )->add_property( 'width', $value )->add_property( 'height', $value );
		}

		$arrow_radius = $attrs['arrowRadius'] ?? [];
		if ( ! empty( $arrow_radius['value'] ) ) {
			$css->set_selector( $arrow )->add_property( 'border-radius', CSS_Helpers::with_unit( $arrow_radius['value'], $arrow_radius['unit'] ?? 'px' ) );
		}

		$arrow_offset = $attrs['arrowOffset'] ?? [];
		if ( isset( $arrow_offset['value'] ) && '' !== (string) $arrow_offset['value'] ) {
			$offset = CSS_Helpers::with_unit( $arrow_offset['value'], $arrow_offset['unit'] ?? 'px' );
			$css->set_selector( $prev )->add_property( 'left', $offset );
			$css->set_selector( $next )->add_property( 'right', $offset );
		}

		self::color_pair( $css, $arrow, 'color', $attrs['arrowColor'] ?? [] );
		self::color_pair( $css, $arrow, 'background-color', $attrs['arrowBackground'] ?? [] );
		self::color_pair( $css, $arrow_hover, 'color', $attrs['arrowColorHover'] ?? [] );
		self::color_pair( $css, $arrow_hover, 'background-color', $attrs['arrowBackgroundHover'] ?? [] );

		// --- Pagination dots. ---
		$bullet_size = $attrs['bulletSize'] ?? [];
		if ( ! empty( $bullet_size['value'] ) ) {
			$value = CSS_Helpers::with_unit( $bullet_size['value'], $bullet_size['unit'] ?? 'px' );
			$css->set_selector( $bullet )->add_property( 'width', $value )->add_property( 'height', $value );
		}
		self::color_pair( $css, $bullet, 'background-color', $attrs['bulletColor'] ?? [] );
		self::color_pair( $css, $bullet_on, 'background-color', $attrs['bulletColorActive'] ?? [] );

		// --- Background + box shadow on the wrapper (base + dark). ---
		$background = $attrs['background'] ?? [];
		if ( ! empty( $background ) ) {
			$css->set_selector( $root );
			CSS_Helpers::add_background( $css, $background );
		}

		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $root )->add_property( 'box-shadow', $shadow );
		}

		CSS_Helpers::add_dark_mode(
			$css,
			$root,
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

	/**
	 * Emit a colour-pair property: light at the base, dark under the dark branch.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector (single, not comma-joined).
	 * @param string      $property CSS property (e.g. 'color', 'background-color').
	 * @param mixed       $pair     Colour pair { light, dark }.
	 */
	private static function color_pair( $css, $selector, $property, $pair ) {
		$light = CSS_Helpers::light( $pair );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( $property, $light );
		}
		CSS_Helpers::dark_color( $css, $selector, $property, CSS_Helpers::dark( $pair ) );
	}
}
