<?php
declare(strict_types=1);
/**
 * Video Popup block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one video-popup instance. Nothing
 * is emitted unless the user picked a value (the base look lives in style.scss).
 * Declarations target the trigger frame / media / play button / thumbnail scrim
 * and the lightbox backdrop inside the block's own `.flexa-video-popup-<id>`
 * wrapper. The play-button size is exposed as the `--flexa-vp-icon-size` custom
 * property.
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
 * Video Popup CSS generator.
 */
class Video_Popup_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map an alignment keyword to its flex cross-axis value (the wrapper is a
	 * flex column, so alignment lives on `align-items`).
	 *
	 * @var array<string, string>
	 */
	private static $align_items = [
		'left'   => 'flex-start',
		'center' => 'center',
		'right'  => 'flex-end',
	];

	/**
	 * Map the block's colon-form aspect choice to a CSS `aspect-ratio` value.
	 *
	 * @var array<string, string>
	 */
	private static $ratios = [
		'16:9' => '16/9',
		'4:3'  => '4/3',
		'1:1'  => '1/1',
	];

	/**
	 * Generate CSS for a video-popup instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap     = '.flexa-video-popup-' . $id;
		$trigger  = $wrap . ' .flexa-video-popup__trigger';
		$media    = $wrap . ' .flexa-video-popup__media';
		$play     = $wrap . ' .flexa-video-popup__play';
		$scrim    = $wrap . ' .flexa-video-popup__scrim';
		$backdrop = $wrap . ' .flexa-video-popup__backdrop';

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

			// Trigger max width.
			$width = $attrs['maxWidth'][ $device ] ?? [];
			if ( ! empty( $width['value'] ) ) {
				$css->set_selector( $trigger )->add_property( 'max-width', CSS_Helpers::with_unit( $width['value'], $width['unit'] ?? 'px' ) );
			}

			// Alignment (flex cross-axis on the wrapper).
			$align = (string) ( $attrs['align'][ $device ] ?? '' );
			if ( isset( self::$align_items[ $align ] ) ) {
				$css->set_selector( $wrap )->add_property( 'align-items', self::$align_items[ $align ] );
			}

			// Border on the trigger frame (rounds the thumbnail corners).
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $trigger );
				CSS_Helpers::add_border( $css, $border );
			}

			// Advanced layout on the wrapper.
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Aspect ratio on the media (only when it differs from the style.scss default).
		$aspect = (string) ( $attrs['aspectRatio'] ?? '16:9' );
		if ( '16:9' !== $aspect && isset( self::$ratios[ $aspect ] ) ) {
			$css->set_selector( $media )->add_property( 'aspect-ratio', self::$ratios[ $aspect ] );
		}

		// Play-button size (custom property, px only).
		$icon_size = $attrs['playIconSize']['value'] ?? '';
		if ( '' !== (string) $icon_size ) {
			$css->set_selector( $wrap )->add_property( '--flexa-vp-icon-size', CSS_Helpers::with_unit( $icon_size, 'px' ) );
		}

		// Play-button colour + background (light) and dark.
		$icon_colour = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['playIconColor'] ?? '' ) );
		if ( '' !== $icon_colour ) {
			$css->set_selector( $play )->add_property( 'color', $icon_colour );
		}
		CSS_Helpers::dark_color( $css, $play, 'color', CSS_Helpers::dark( $attrs['playIconColor'] ?? '' ) );

		$icon_bg = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['playIconBackground'] ?? '' ) );
		if ( '' !== $icon_bg ) {
			$css->set_selector( $play )->add_property( 'background', $icon_bg );
		}
		CSS_Helpers::dark_color( $css, $play, 'background', CSS_Helpers::dark( $attrs['playIconBackground'] ?? '' ) );

		// Thumbnail scrim opacity (only when it differs from the style.scss default).
		$scrim_opacity = $attrs['scrimOpacity'] ?? 30;
		if ( is_numeric( $scrim_opacity ) ) {
			$scrim_opacity = max( 0, min( 100, (int) $scrim_opacity ) );
			if ( 30 !== $scrim_opacity ) {
				$css->set_selector( $scrim )->add_property( 'opacity', (string) round( $scrim_opacity / 100, 2 ) );
			}
		}

		// Lightbox backdrop colour (light) + opacity + dark.
		$overlay_colour = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['overlayColor'] ?? '' ) );
		if ( '' !== $overlay_colour ) {
			$css->set_selector( $backdrop )->add_property( 'background-color', $overlay_colour );
		}
		CSS_Helpers::dark_color( $css, $backdrop, 'background-color', CSS_Helpers::dark( $attrs['overlayColor'] ?? '' ) );

		$overlay_opacity = $attrs['overlayOpacity'] ?? 90;
		if ( is_numeric( $overlay_opacity ) ) {
			$overlay_opacity = max( 0, min( 100, (int) $overlay_opacity ) );
			if ( 90 !== $overlay_opacity ) {
				$css->set_selector( $backdrop )->add_property( 'opacity', (string) round( $overlay_opacity / 100, 2 ) );
			}
		}

		// Wrapper background (light) + lazy background support.
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

		// Box shadow on the trigger frame (light).
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $trigger )->add_property( 'box-shadow', $shadow );
		}

		// Trigger border dark colour (desktop drives it, like the other blocks).
		CSS_Helpers::dark_color( $css, $trigger, 'border-color', CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );

		// Wrapper background + trigger shadow dark.
		self::add_dark( $css, $wrap, $trigger, $attrs, $background );
	}

	/**
	 * Dark-mode declarations for the wrapper background and trigger box-shadow.
	 *
	 * @param CSS_Builder $css        Builder.
	 * @param string      $wrap       Wrapper selector.
	 * @param string      $trigger    Trigger selector.
	 * @param array       $attrs      Attributes.
	 * @param array       $background Background attribute.
	 */
	private static function add_dark( $css, $wrap, $trigger, $attrs, $background ) {
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

		$shadow = $attrs['boxShadow'] ?? [];
		if ( ! empty( $shadow['enabled'] ) ) {
			$shadow_dark = CSS_Helpers::dark( $shadow['color'] ?? '' );
			if ( '' !== $shadow_dark ) {
				$value = CSS_Helpers::box_shadow( $shadow, $shadow_dark );
				if ( '' !== $value ) {
					CSS_Helpers::add_dark_mode(
						$css,
						$trigger,
						function ( $css ) use ( $value ) {
							$css->add_property( 'box-shadow', $value );
						}
					);
				}
			}
		}
	}
}
