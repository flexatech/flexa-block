<?php
declare(strict_types=1);
/**
 * Notice block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one notice instance. Nothing is
 * emitted unless the user picked a value, so an untouched notice keeps the theme's
 * typography plus the subtle per-type accent from style.scss. Declarations target
 * the inner row / icon / body / title / message parts inside the block's own
 * `.flexa-notice-<id>` wrapper.
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
 * Notice CSS generator.
 */
class Notice_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a notice instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap    = '.flexa-notice-' . $id;
		$inner   = $wrap . ' .flexa-notice__inner';
		$icon    = $wrap . ' .flexa-notice__icon';
		$body    = $wrap . ' .flexa-notice__body';
		$title   = $wrap . ' .flexa-notice__title';
		$content = $wrap . ' .flexa-notice__content';

		// Icon position → flex-direction on the inner row. Only emitted for "top"
		// (column); "left" is the style.scss default row, so nothing is emitted then.
		if ( 'top' === ( $attrs['iconPosition'] ?? 'left' ) ) {
			$css->set_selector( $inner )->add_property( 'flex-direction', 'column' );
		}

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Content alignment → text-align on the body column.
			$align = CSS_Helpers::sanitize_enum( (string) ( $attrs['alignment'][ $device ] ?? '' ), [ 'left', 'center', 'right', 'justify' ] );
			if ( '' !== $align ) {
				$css->set_selector( $body )->add_property( 'text-align', $align );
			}

			// Icon size → width / height / font-size on the icon.
			$icon_size = $attrs['iconSize'][ $device ] ?? [];
			if ( ! empty( $icon_size['value'] ) ) {
				$size = CSS_Helpers::with_unit( $icon_size['value'], $icon_size['unit'] ?? 'px' );
				$css->set_selector( $icon )->add_property( 'width', $size );
				$css->set_selector( $icon )->add_property( 'height', $size );
				$css->set_selector( $icon )->add_property( 'font-size', $size );
			}

			// Typography on the title + message.
			CSS_Helpers::add_typography( $css, $title, $attrs['titleTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $content, $attrs['contentTypography'][ $device ] ?? [] );

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

			// Border (4-side outline + radius) on the wrapper.
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

		// Accent bar (left border) colour override — light at the base, dark under
		// the dark-mode branch. Wins over the per-type style.scss default because the
		// generated CSS prints after the block stylesheet.
		CSS_Helpers::color_pair( $css, $wrap, 'border-left-color', $attrs['accentColor'] ?? '' );

		// Colours — light at the base, dark under the dark-mode branch.
		CSS_Helpers::color_pair( $css, $icon, 'color', $attrs['iconColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $title, 'color', $attrs['titleColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $content, 'color', $attrs['contentColor'] ?? '' );

		// Wrapper background (base, light) + lazy image handling.
		$background = $attrs['background'] ?? [];
		$lazy_bg    = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
		if ( ! empty( $background ) ) {
			$css->set_selector( $wrap );
			CSS_Helpers::add_background( $css, $background, $lazy_bg );

			// Lazy: the image url only applies once the front-end script adds
			// `.flexa-bg-loaded` (see view.js). Until then no image is fetched.
			if ( $lazy_bg ) {
				$css->set_selector( $wrap . '.flexa-bg-loaded' )
					->add_property( 'background-image', 'url(' . esc_url_raw( $background['image']['url'] ) . ')' );
			}
		}

		// Box shadow on the wrapper (light).
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $wrap )->add_property( 'box-shadow', $shadow );
		}

		// Dark mode: background color / gradient, border color, shadow color — one
		// branch on the wrapper, exactly like Container_CSS.
		CSS_Helpers::add_dark_mode(
			$css,
			$wrap,
			function ( $css ) use ( $attrs, $background ) {
				// Background dark color / gradient.
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

				// Border dark color (desktop only).
				$border_dark = CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' );
				if ( '' !== $border_dark ) {
					$css->add_property( 'border-color', $border_dark );
				}

				// Shadow dark color.
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
