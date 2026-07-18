<?php
declare(strict_types=1);
/**
 * Countdown block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one countdown instance. Nothing
 * is emitted unless the user picked a value, so an untouched countdown keeps the
 * theme's typography. Declarations target the timer / unit / digit / label /
 * separator inside the block's own `.flexa-countdown-<id>` wrapper.
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
 * Countdown CSS generator.
 */
class Countdown_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map an alignment keyword to a flex `align-items` value (aligns the timer
	 * block inside the flex-column wrapper).
	 *
	 * @var array<string, string>
	 */
	private static $align_items = [
		'left'    => 'flex-start',
		'center'  => 'center',
		'right'   => 'flex-end',
		'justify' => 'stretch',
	];

	/**
	 * Generate CSS for a countdown instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap  = '.flexa-countdown-' . $id;
		$timer = $wrap . ' .flexa-countdown__timer';
		$unit  = $wrap . ' .flexa-countdown__unit';
		$digit = $wrap . ' .flexa-countdown__digit';
		$label = $wrap . ' .flexa-countdown__label';
		$sep   = $wrap . ' .flexa-countdown__separator';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Alignment: align-items + text-align on the wrapper; justify spreads
			// the timer row.
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( '' !== $align ) {
				$css->set_selector( $wrap )
					->add_property( 'align-items', self::$align_items[ $align ] ?? 'center' )
					->add_property( 'text-align', 'justify' === $align ? 'center' : $align );
				if ( 'justify' === $align ) {
					$css->set_selector( $timer )
						->add_property( 'justify-content', 'space-between' )
						->add_property( 'width', '100%' );
				}
			}

			// Gap between units.
			$gap = $attrs['itemGap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $timer )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Max width + min height on the wrapper.
			$max_width = $attrs['maxWidth'][ $device ] ?? [];
			if ( ! empty( $max_width['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'max-width', CSS_Helpers::with_unit( $max_width['value'], $max_width['unit'] ?? 'px' ) );
			}
			$min_height = $attrs['size'][ $device ]['minHeight'] ?? [];
			if ( ! empty( $min_height['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'min-height', CSS_Helpers::with_unit( $min_height['value'], $min_height['unit'] ?? 'px' ) );
			}

			// Digit + label typography.
			CSS_Helpers::add_typography( $css, $digit, $attrs['digitTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $label, $attrs['labelTypography'][ $device ] ?? [] );

			// Separator size.
			$sep_size = $attrs['separatorFontSize'][ $device ] ?? [];
			if ( ! empty( $sep_size['value'] ) ) {
				$css->set_selector( $sep )->add_property( 'font-size', CSS_Helpers::with_unit( $sep_size['value'], $sep_size['unit'] ?? 'px' ) );
			}

			// Unit box padding + radius.
			$padding = CSS_Helpers::spacing_shorthand( $attrs['itemPadding'][ $device ] ?? [] );
			if ( '' !== $padding ) {
				$css->set_selector( $unit )->add_property( 'padding', $padding );
			}
			$radius = $attrs['itemBorderRadius'][ $device ] ?? [];
			if ( ! empty( $radius['value'] ) ) {
				$css->set_selector( $unit )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
			}

			// Wrapper spacing: padding + margin.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			if ( ! empty( $spacing['padding'] ) ) {
				$pad = CSS_Helpers::spacing_shorthand( $spacing['padding'] );
				if ( '' !== $pad ) {
					$css->set_selector( $wrap )->add_property( 'padding', $pad );
				}
			}
			if ( ! empty( $spacing['margin'] ) ) {
				$margin = CSS_Helpers::spacing_shorthand( $spacing['margin'] );
				if ( '' !== $margin ) {
					$css->set_selector( $wrap )->add_property( 'margin', $margin );
				}
			}

			// Wrapper border.
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

		// Colours (light, base).
		$digit_light = CSS_Helpers::light( $attrs['digitColor'] ?? '' );
		if ( '' !== $digit_light ) {
			$css->set_selector( $digit )->add_property( 'color', $digit_light );
		}
		$label_light = CSS_Helpers::light( $attrs['labelColor'] ?? '' );
		if ( '' !== $label_light ) {
			$css->set_selector( $label )->add_property( 'color', $label_light );
		}
		$sep_light = CSS_Helpers::light( $attrs['separatorColor'] ?? '' );
		if ( '' !== $sep_light ) {
			$css->set_selector( $sep )->add_property( 'color', $sep_light );
		}
		$item_bg_light = CSS_Helpers::light( $attrs['itemBackground'] ?? '' );
		if ( '' !== $item_bg_light ) {
			$css->set_selector( $unit )->add_property( 'background', $item_bg_light );
		}

		// Wrapper background + box shadow (base, light).
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

		// Dark mode.
		self::add_dark( $css, $wrap, $digit, $label, $sep, $unit, $attrs, $background );
	}

	/**
	 * Dark-mode declarations for colours, background, border and shadow.
	 *
	 * @param CSS_Builder $css        Builder.
	 * @param string      $wrap       Wrapper selector.
	 * @param string      $digit      Digit selector.
	 * @param string      $label      Label selector.
	 * @param string      $sep        Separator selector.
	 * @param string      $unit       Unit-box selector.
	 * @param array       $attrs      Attributes.
	 * @param array       $background Background attribute.
	 */
	private static function add_dark( $css, $wrap, $digit, $label, $sep, $unit, $attrs, $background ) {
		self::dark_color( $css, $digit, 'color', CSS_Helpers::dark( $attrs['digitColor'] ?? '' ) );
		self::dark_color( $css, $label, 'color', CSS_Helpers::dark( $attrs['labelColor'] ?? '' ) );
		self::dark_color( $css, $sep, 'color', CSS_Helpers::dark( $attrs['separatorColor'] ?? '' ) );
		self::dark_color( $css, $unit, 'background', CSS_Helpers::dark( $attrs['itemBackground'] ?? '' ) );

		// Wrapper background / border / shadow.
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

	/**
	 * Emit one dark-mode colour declaration when a dark value is present.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Selector.
	 * @param string      $property CSS property.
	 * @param string      $value    Dark colour value.
	 */
	private static function dark_color( $css, $selector, $property, $value ) {
		if ( '' === $value ) {
			return;
		}
		CSS_Helpers::add_dark_mode(
			$css,
			$selector,
			function ( $css ) use ( $property, $value ) {
				$css->add_property( $property, $value );
			}
		);
	}

}
