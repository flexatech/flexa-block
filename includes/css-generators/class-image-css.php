<?php
declare(strict_types=1);
/**
 * Image block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one image instance. Nothing is
 * emitted unless the user picked a value. Declarations target the frame / image
 * / overlay / caption inside the block's own `.flexa-image-<id>` wrapper.
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
 * Image CSS generator.
 */
class Image_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map an alignment keyword to the frame's auto margins.
	 *
	 * @var array<string, array<string, string>>
	 */
	private static $align_margins = [
		'left'   => [ 'margin-right' => 'auto' ],
		'center' => [ 'margin-left' => 'auto', 'margin-right' => 'auto' ],
		'right'  => [ 'margin-left' => 'auto' ],
	];

	/**
	 * Generate CSS for an image instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap    = '.flexa-image-' . $id;
		$frame   = $wrap . ' .flexa-image__frame';
		$img     = $wrap . ' .flexa-image__img';
		$overlay = $wrap . ' .flexa-image__overlay';
		$caption = $wrap . ' .flexa-image__caption';

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

			// Frame width.
			$width = $attrs['imageWidth'][ $device ] ?? [];
			if ( ! empty( $width['value'] ) ) {
				$css->set_selector( $frame )->add_property( 'width', CSS_Helpers::with_unit( $width['value'], $width['unit'] ?? '%' ) );
			}

			// Frame alignment (auto margins).
			$align = (string) ( $attrs['imageAlign'][ $device ] ?? '' );
			if ( isset( self::$align_margins[ $align ] ) ) {
				$css->set_selector( $frame );
				foreach ( self::$align_margins[ $align ] as $prop => $value ) {
					$css->add_property( $prop, $value );
				}
			}

			// Border on the frame (clips the image).
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $frame );
				CSS_Helpers::add_border( $css, $border );
			}

			// Caption typography.
			CSS_Helpers::add_typography( $css, $caption, $attrs['captionTypography'][ $device ] ?? [] );

			CSS_Helpers::close_device( $css, $device );
		}

		// Object fit on the image.
		if ( ! empty( $attrs['objectFit'] ) ) {
			$object_fit = CSS_Helpers::sanitize_enum( $attrs['objectFit'], [ 'cover', 'contain', 'fill', 'none', 'scale-down' ] );
			if ( '' !== $object_fit ) {
				$css->set_selector( $img )->add_property( 'object-fit', $object_fit );
			}
		}

		// Aspect ratio lock on the frame.
		$aspect = $attrs['aspectRatio'] ?? [];
		if ( ! empty( $aspect['enabled'] ) && ! empty( $aspect['ratio'] ) ) {
			$css->set_selector( $frame )->add_property( 'aspect-ratio', $aspect['ratio'] );
		}

		// Custom mask on the frame (built-in shapes come from static classes).
		$mask = $attrs['mask'] ?? [];
		if ( 'custom' === ( $mask['shape'] ?? 'none' ) && ! empty( $mask['customImage']['url'] ) ) {
			$css->set_selector( $frame )
				->add_property( '--flexa-img-mask', 'url(' . esc_url_raw( $mask['customImage']['url'] ) . ')' )
				->add_property( '--flexa-img-mask-size', CSS_Helpers::sanitize_enum( $mask['size'] ?? '', [ 'cover', 'contain', 'auto' ], 'contain' ) )
				->add_property( '--flexa-img-mask-position', CSS_Helpers::sanitize_position_pair( $mask['position'] ?? '', 'center center' ) )
				->add_property( '--flexa-img-mask-repeat', CSS_Helpers::sanitize_enum( $mask['repeat'] ?? '', [ 'repeat', 'repeat-x', 'repeat-y', 'no-repeat' ], 'no-repeat' ) );
		}

		// Overlay (light).
		self::add_overlay( $css, $overlay, $attrs['overlay'] ?? [] );

		// Caption text alignment (only meaningful when a caption is shown).
		$caption_attr = $attrs['caption'] ?? [];
		if ( ! empty( $caption_attr['show'] ) ) {
			$cap_align = (string) ( $caption_attr['alignment'] ?? '' );
			if ( in_array( $cap_align, [ 'left', 'center', 'right' ], true ) ) {
				$css->set_selector( $caption )->add_property( 'text-align', $cap_align );
			}
		}

		// Caption colours (light).
		$cap_colour = CSS_Helpers::light( $attrs['captionColor'] ?? '' );
		if ( '' !== $cap_colour ) {
			$css->set_selector( $caption )->add_property( 'color', $cap_colour );
		}
		$cap_bg = CSS_Helpers::light( $attrs['captionBackground'] ?? '' );
		if ( '' !== $cap_bg ) {
			$css->set_selector( $caption )->add_property( 'background-color', $cap_bg );
		}

		// Wrapper background + box shadow (light).
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
		self::add_dark( $css, $wrap, $frame, $overlay, $caption, $attrs, $background );
	}

	/**
	 * Overlay colour / gradient, opacity and blend mode (light).
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Overlay selector.
	 * @param array       $overlay  Overlay attribute.
	 */
	private static function add_overlay( $css, $selector, $overlay ) {
		$type = $overlay['type'] ?? 'none';
		if ( 'none' === $type ) {
			return;
		}

		$css->set_selector( $selector );

		if ( 'color' === $type ) {
			$colour = CSS_Helpers::light( $overlay['color'] ?? '' );
			if ( '' !== $colour ) {
				$css->add_property( 'background-color', $colour );
			}
		} elseif ( 'gradient' === $type ) {
			$gradient = CSS_Helpers::light( $overlay['gradient'] ?? '' );
			if ( '' !== $gradient ) {
				$css->add_property( 'background-image', $gradient );
			}
		}

		if ( isset( $overlay['opacity'] ) && '' !== (string) $overlay['opacity'] ) {
			$css->add_property( 'opacity', (string) $overlay['opacity'] );
		}
		if ( ! empty( $overlay['blendMode'] ) ) {
			$blend_mode = CSS_Helpers::sanitize_enum( $overlay['blendMode'], [ 'multiply', 'screen', 'overlay', 'darken', 'lighten', 'color-dodge', 'color-burn', 'hard-light', 'soft-light', 'difference', 'exclusion' ] );
			if ( '' !== $blend_mode ) {
				$css->add_property( 'mix-blend-mode', $blend_mode );
			}
		}
	}

	/**
	 * Dark-mode declarations for the overlay / caption / wrapper / frame.
	 *
	 * @param CSS_Builder $css        Builder.
	 * @param string      $wrap       Wrapper selector.
	 * @param string      $frame      Frame selector.
	 * @param string      $overlay    Overlay selector.
	 * @param string      $caption    Caption selector.
	 * @param array       $attrs      Attributes.
	 * @param array       $background Background attribute.
	 */
	private static function add_dark( $css, $wrap, $frame, $overlay, $caption, $attrs, $background ) {
		// Overlay dark colour / gradient.
		$ov = $attrs['overlay'] ?? [];
		if ( 'none' !== ( $ov['type'] ?? 'none' ) ) {
			CSS_Helpers::add_dark_mode(
				$css,
				$overlay,
				function ( $css ) use ( $ov ) {
					if ( 'gradient' === ( $ov['type'] ?? '' ) ) {
						$dark = CSS_Helpers::dark( $ov['gradient'] ?? '' );
						if ( '' !== $dark ) {
							$css->add_property( 'background-image', $dark );
						}
					} else {
						$dark = CSS_Helpers::dark( $ov['color'] ?? '' );
						if ( '' !== $dark ) {
							$css->add_property( 'background-color', $dark );
						}
					}
				}
			);
		}

		// Caption dark colours.
		$cap_dark    = CSS_Helpers::dark( $attrs['captionColor'] ?? '' );
		$cap_bg_dark = CSS_Helpers::dark( $attrs['captionBackground'] ?? '' );
		if ( '' !== $cap_dark || '' !== $cap_bg_dark ) {
			CSS_Helpers::add_dark_mode(
				$css,
				$caption,
				function ( $css ) use ( $cap_dark, $cap_bg_dark ) {
					if ( '' !== $cap_dark ) {
						$css->add_property( 'color', $cap_dark );
					}
					if ( '' !== $cap_bg_dark ) {
						$css->add_property( 'background-color', $cap_bg_dark );
					}
				}
			);
		}

		// Frame border dark colour.
		$border_dark = CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' );
		if ( '' !== $border_dark ) {
			CSS_Helpers::add_dark_mode(
				$css,
				$frame,
				function ( $css ) use ( $border_dark ) {
					$css->add_property( 'border-color', $border_dark );
				}
			);
		}

		// Wrapper background / shadow dark.
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
