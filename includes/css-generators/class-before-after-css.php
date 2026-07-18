<?php
declare(strict_types=1);
/**
 * Before / After block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one comparison-slider instance.
 * Nothing is emitted unless the user picked a value (the base look lives in
 * style.scss). Declarations target the frame / images / handle / labels inside
 * the block's own `.flexa-before-after-<id>` wrapper, and the split position,
 * handle size and divider width are exposed as custom properties.
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
 * Before / After CSS generator.
 */
class Before_After_CSS {

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
	 * Generate CSS for a before/after instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap   = '.flexa-before-after-' . $id;
		$frame  = $wrap . ' .flexa-before-after__frame';
		$img    = $wrap . ' .flexa-before-after__img';
		$handle = $wrap . ' .flexa-before-after__handle';
		$label  = $wrap . ' .flexa-before-after__label';

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

			// Frame max width.
			$width = $attrs['maxWidth'][ $device ] ?? [];
			if ( ! empty( $width['value'] ) ) {
				$css->set_selector( $frame )->add_property( 'max-width', CSS_Helpers::with_unit( $width['value'], $width['unit'] ?? 'px' ) );
			}

			// Frame alignment (auto margins).
			$align = (string) ( $attrs['align'][ $device ] ?? '' );
			if ( isset( self::$align_margins[ $align ] ) ) {
				$css->set_selector( $frame );
				foreach ( self::$align_margins[ $align ] as $prop => $value ) {
					$css->add_property( $prop, $value );
				}
			}

			// Frame min height.
			$min_height = $attrs['size'][ $device ]['minHeight'] ?? [];
			if ( ! empty( $min_height['value'] ) ) {
				$css->set_selector( $frame )->add_property( 'min-height', CSS_Helpers::with_unit( $min_height['value'], $min_height['unit'] ?? 'px' ) );
			}

			// Border on the frame (clips the images).
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $frame );
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

		// Split position (only when it differs from the style.scss 50% default).
		$position = $attrs['initialPosition'] ?? 50;
		if ( is_numeric( $position ) ) {
			$position = max( 0, min( 100, (int) $position ) );
			if ( 50 !== $position ) {
				$css->set_selector( $wrap )->add_property( '--flexa-ba-pos', $position . '%' );
			}
		}

		// Handle grip size + divider line width (custom properties, px only).
		$handle_size = $attrs['handleSize']['value'] ?? '';
		if ( '' !== (string) $handle_size ) {
			$css->set_selector( $wrap )->add_property( '--flexa-ba-handle-size', CSS_Helpers::with_unit( $handle_size, 'px' ) );
		}
		$divider_width = $attrs['dividerWidth']['value'] ?? '';
		if ( '' !== (string) $divider_width ) {
			$css->set_selector( $wrap )->add_property( '--flexa-ba-line-width', CSS_Helpers::with_unit( $divider_width, 'px' ) );
		}

		// Object fit on both images (only when it differs from the CSS default).
		$object_fit = (string) ( $attrs['objectFit'] ?? 'cover' );
		if ( 'cover' !== $object_fit ) {
			$fit = CSS_Helpers::sanitize_enum( $object_fit, [ 'cover', 'contain', 'fill', 'none', 'scale-down' ] );
			if ( '' !== $fit ) {
				$css->set_selector( $img )->add_property( 'object-fit', $fit );
			}
		}

		// Aspect-ratio lock on the frame.
		$aspect = $attrs['aspectRatio'] ?? [];
		if ( ! empty( $aspect['enabled'] ) && ! empty( $aspect['ratio'] ) ) {
			$css->set_selector( $frame )->add_property( 'aspect-ratio', $aspect['ratio'] );
		}

		// Handle colour (drives the line + grip via currentColor).
		$handle_light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['handleColor'] ?? '' ) );
		if ( '' !== $handle_light ) {
			$css->set_selector( $handle )->add_property( 'color', $handle_light );
		}
		CSS_Helpers::dark_color( $css, $handle, 'color', CSS_Helpers::dark( $attrs['handleColor'] ?? '' ) );

		// Corner labels (only when shown).
		if ( false !== ( $attrs['showLabels'] ?? true ) ) {
			$label_colour = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['labelColor'] ?? '' ) );
			if ( '' !== $label_colour ) {
				$css->set_selector( $label )->add_property( 'color', $label_colour );
			}
			$label_bg = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['labelBackground'] ?? '' ) );
			if ( '' !== $label_bg ) {
				$css->set_selector( $label )->add_property( 'background', $label_bg );
			}
			CSS_Helpers::dark_color( $css, $label, 'color', CSS_Helpers::dark( $attrs['labelColor'] ?? '' ) );
			CSS_Helpers::dark_color( $css, $label, 'background', CSS_Helpers::dark( $attrs['labelBackground'] ?? '' ) );
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

		// Box shadow on the frame (light).
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $frame )->add_property( 'box-shadow', $shadow );
		}

		// Frame border dark colour (desktop drives it, like the other blocks).
		CSS_Helpers::dark_color( $css, $frame, 'border-color', CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );

		// Wrapper background + frame shadow dark.
		self::add_dark( $css, $wrap, $frame, $attrs, $background );
	}

	/**
	 * Dark-mode declarations for the wrapper background and frame box-shadow.
	 *
	 * @param CSS_Builder $css        Builder.
	 * @param string      $wrap       Wrapper selector.
	 * @param string      $frame      Frame selector.
	 * @param array       $attrs      Attributes.
	 * @param array       $background Background attribute.
	 */
	private static function add_dark( $css, $wrap, $frame, $attrs, $background ) {
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
						$frame,
						function ( $css ) use ( $value ) {
							$css->add_property( 'box-shadow', $value );
						}
					);
				}
			}
		}
	}
}
