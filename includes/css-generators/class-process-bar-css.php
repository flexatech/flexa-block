<?php
declare(strict_types=1);
/**
 * Progress Bar block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one progress-bar instance.
 * Nothing is emitted unless the user picked a value, so an untouched bar keeps
 * the theme / the neutral default fill. Declarations target the title / counter /
 * track / fill (line) or the SVG ring (circle & semi-circle) inside the block's
 * own `.flexa-process-bar-<id>` wrapper.
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
 * Progress Bar CSS generator.
 */
class Process_Bar_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map an alignment keyword to a flex cross-axis value (the wrapper is a flex
	 * column, so alignment moves the bar / header horizontally).
	 *
	 * @var array<string, string>
	 */
	private static $align_items = [
		'left'   => 'flex-start',
		'center' => 'center',
		'right'  => 'flex-end',
	];

	/**
	 * The diagonal stripe overlay for a striped fill.
	 *
	 * @var string
	 */
	private static $stripe = 'repeating-linear-gradient(45deg, rgba(255,255,255,0.25) 0, rgba(255,255,255,0.25) 8px, transparent 8px, transparent 16px)';

	/**
	 * Generate CSS for a progress-bar instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap       = '.flexa-process-bar-' . $id;
		$title      = $wrap . ' .flexa-process-bar__title';
		$counter    = $wrap . ' .flexa-process-bar__counter';
		$track      = $wrap . ' .flexa-process-bar__track';
		$fill       = $wrap . ' .flexa-process-bar__fill';
		$ring       = $wrap . ' .flexa-process-bar__ring';
		$ring_track = $wrap . ' .flexa-process-bar__ring-track';
		$ring_fill  = $wrap . ' .flexa-process-bar__ring-fill';

		$bar_type = CSS_Helpers::sanitize_enum( (string) ( $attrs['barType'] ?? 'line' ), [ 'line', 'circle', 'semicircle' ], 'line' );
		$is_line  = 'line' === $bar_type;

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Alignment: align-items + text-align on the wrapper.
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( '' !== $align ) {
				$css->set_selector( $wrap )
					->add_property( 'align-items', self::$align_items[ $align ] ?? 'flex-start' )
					->add_property( 'text-align', $align );
			}

			// Max width on the wrapper.
			$max_width = $attrs['maxWidth'][ $device ] ?? [];
			if ( ! empty( $max_width['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'max-width', CSS_Helpers::with_unit( $max_width['value'], $max_width['unit'] ?? 'px' ) );
			}

			if ( $is_line ) {
				// Line thickness on the track.
				$height = $attrs['barHeight'][ $device ] ?? [];
				if ( ! empty( $height['value'] ) ) {
					$css->set_selector( $track )->add_property( 'height', CSS_Helpers::with_unit( $height['value'], $height['unit'] ?? 'px' ) );
				}
			} else {
				// Circle / semi diameter on the SVG element.
				$size = $attrs['circleSize'][ $device ] ?? [];
				if ( ! empty( $size['value'] ) ) {
					$value = CSS_Helpers::with_unit( $size['value'], $size['unit'] ?? 'px' );
					$css->set_selector( $ring )->add_property( 'width', $value )->add_property( 'height', $value );
				}
				// Ring thickness (SVG user units → unitless stroke-width) on both strokes.
				$stroke = $attrs['strokeWidth'][ $device ] ?? [];
				if ( ! empty( $stroke['value'] ) ) {
					$sw = (string) ( (float) $stroke['value'] );
					$css->set_selector( $ring_track )->add_property( 'stroke-width', $sw );
					$css->set_selector( $ring_fill )->add_property( 'stroke-width', $sw );
				}
			}

			// Title + counter typography.
			CSS_Helpers::add_typography( $css, $title, $attrs['titleTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $counter, $attrs['counterTypography'][ $device ] ?? [] );

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

		// Title + counter colours (light at the base, dark under the dark branch).
		$title_light = CSS_Helpers::light( $attrs['titleColor'] ?? '' );
		if ( '' !== $title_light ) {
			$css->set_selector( $title )->add_property( 'color', $title_light );
		}
		CSS_Helpers::dark_color( $css, $title, 'color', CSS_Helpers::dark( $attrs['titleColor'] ?? '' ) );

		$counter_light = CSS_Helpers::light( $attrs['counterColor'] ?? '' );
		if ( '' !== $counter_light ) {
			$css->set_selector( $counter )->add_property( 'color', $counter_light );
		}
		CSS_Helpers::dark_color( $css, $counter, 'color', CSS_Helpers::dark( $attrs['counterColor'] ?? '' ) );

		// Fill + track colours: background-color (line) or stroke (ring).
		$fill_light  = CSS_Helpers::light( $attrs['fillColor'] ?? '' );
		$track_light = CSS_Helpers::light( $attrs['trackColor'] ?? '' );

		if ( $is_line ) {
			if ( '' !== $fill_light ) {
				$css->set_selector( $fill )->add_property( 'background-color', $fill_light );
			}
			CSS_Helpers::dark_color( $css, $fill, 'background-color', CSS_Helpers::dark( $attrs['fillColor'] ?? '' ) );

			if ( '' !== $track_light ) {
				$css->set_selector( $track )->add_property( 'background-color', $track_light );
			}
			CSS_Helpers::dark_color( $css, $track, 'background-color', CSS_Helpers::dark( $attrs['trackColor'] ?? '' ) );

			// Line corner rounding (single, non-responsive) on the track + fill.
			$radius = $attrs['barRadius'] ?? [];
			if ( ! empty( $radius['value'] ) ) {
				$r = CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' );
				$css->set_selector( $track )->add_property( 'border-radius', $r );
				$css->set_selector( $fill )->add_property( 'border-radius', $r );
			}

			// Striped fill overlay (+ optional stripe animation).
			$fill_style = CSS_Helpers::sanitize_enum( (string) ( $attrs['fillStyle'] ?? 'solid' ), [ 'solid', 'striped', 'striped-animated' ], 'solid' );
			if ( 'striped' === $fill_style || 'striped-animated' === $fill_style ) {
				$css->set_selector( $fill )->add_property( 'background-image', self::$stripe );
				if ( 'striped-animated' === $fill_style ) {
					$css->set_selector( $fill )->add_property( 'animation', 'flexa-process-bar-stripes 1s linear infinite' );
				}
			}
		} else {
			if ( '' !== $fill_light ) {
				$css->set_selector( $ring_fill )->add_property( 'stroke', $fill_light );
			}
			CSS_Helpers::dark_color( $css, $ring_fill, 'stroke', CSS_Helpers::dark( $attrs['fillColor'] ?? '' ) );

			if ( '' !== $track_light ) {
				$css->set_selector( $ring_track )->add_property( 'stroke', $track_light );
			}
			CSS_Helpers::dark_color( $css, $ring_track, 'stroke', CSS_Helpers::dark( $attrs['trackColor'] ?? '' ) );

			// Ring end cap on both strokes (only when the user set one).
			if ( isset( $attrs['lineCap'] ) ) {
				$cap = CSS_Helpers::sanitize_enum( (string) $attrs['lineCap'], [ 'round', 'square', 'butt' ], 'round' );
				$css->set_selector( $ring_fill )->add_property( 'stroke-linecap', $cap );
				$css->set_selector( $ring_track )->add_property( 'stroke-linecap', $cap );
			}
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

		// Dark mode for the wrapper background / border / shadow.
		self::add_wrapper_dark( $css, $wrap, $attrs, $background );
	}

	/**
	 * Dark-mode declarations for the wrapper's background, border colour and box
	 * shadow (gathered under one dark-mode branch).
	 *
	 * @param CSS_Builder $css        Builder.
	 * @param string      $wrap       Wrapper selector.
	 * @param array       $attrs      Attributes.
	 * @param array       $background Background attribute.
	 */
	private static function add_wrapper_dark( $css, $wrap, $attrs, $background ) {
		CSS_Helpers::add_dark_mode(
			$css,
			$wrap,
			function ( $css ) use ( $attrs, $background ) {
				$type = $background['type'] ?? 'none';
				if ( 'classic' === $type || 'color' === $type ) {
					$dark = CSS_Helpers::sanitize_color( CSS_Helpers::dark( $background['color'] ?? '' ) );
					if ( '' !== $dark ) {
						$css->add_property( 'background-color', $dark );
					}
				} elseif ( 'gradient' === $type ) {
					$dark = CSS_Helpers::sanitize_gradient( CSS_Helpers::dark( $background['gradient'] ?? '' ) );
					if ( '' !== $dark ) {
						$css->add_property( 'background-image', $dark );
					}
				}

				$border_dark = CSS_Helpers::sanitize_color( CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );
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
