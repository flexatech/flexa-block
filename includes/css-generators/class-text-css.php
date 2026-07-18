<?php
declare(strict_types=1);
/**
 * Text block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one text instance. Nothing is
 * emitted unless the user picked a value, so an untouched text block keeps the
 * theme's typography. Layout/background/border/shadow target the wrapper
 * `.flexa-text-<id>`; typography, colour and effects target the inner
 * `.flexa-text-<id> .flexa-text__content`.
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
 * Text CSS generator.
 */
class Text_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a text instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap    = '.flexa-text-' . $id;
		$content = $wrap . ' .flexa-text__content';
		$hover   = $content . ':hover';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Alignment on the wrapper.
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( '' !== $align ) {
				$css->set_selector( $wrap )->add_property( 'text-align', $align );
			}

			// Typography on the content element.
			CSS_Helpers::add_typography( $css, $content, $attrs['typography'][ $device ] ?? [] );

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

		// Text colour / gradient (light + dark) on the content.
		CSS_Helpers::add_text_fill( $css, $content, $attrs['textType'] ?? 'color', $attrs['textColor'] ?? '', $attrs['textGradient'] ?? '' );

		// Hover colour (light at base, dark under the dark-mode branch).
		$hover_light = CSS_Helpers::light( $attrs['textColorHover'] ?? '' );
		if ( '' !== $hover_light ) {
			$css->set_selector( $hover )->add_property( 'color', $hover_light );
		}
		CSS_Helpers::dark_color( $css, $hover, 'color', CSS_Helpers::dark( $attrs['textColorHover'] ?? '' ) );

		// Text stroke (light + dark) — shared helper.
		CSS_Helpers::add_text_stroke( $css, $content, $attrs['textStroke'] ?? [] );

		// Text shadow (light at base, dark under the dark-mode branch).
		$text_shadow = CSS_Helpers::text_shadow( $attrs['textShadow'] ?? [] );
		if ( '' !== $text_shadow ) {
			$css->set_selector( $content )->add_property( 'text-shadow', $text_shadow );
		}
		$shadow_attr = $attrs['textShadow'] ?? [];
		if ( ! empty( $shadow_attr['enabled'] ) ) {
			$shadow_dark = CSS_Helpers::dark( $shadow_attr['color'] ?? '' );
			if ( '' !== $shadow_dark ) {
				$value = CSS_Helpers::text_shadow( $shadow_attr, $shadow_dark );
				if ( '' !== $value ) {
					CSS_Helpers::dark_color( $css, $content, 'text-shadow', $value );
				}
			}
		}

		// Blend mode on the content.
		if ( ! empty( $attrs['blendMode'] ) ) {
			$blend_mode = CSS_Helpers::sanitize_enum( $attrs['blendMode'], [ 'multiply', 'screen', 'overlay', 'darken', 'lighten', 'color-dodge', 'color-burn', 'hard-light', 'soft-light', 'difference', 'exclusion' ] );
			if ( '' !== $blend_mode ) {
				$css->set_selector( $content )->add_property( 'mix-blend-mode', $blend_mode );
			}
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
