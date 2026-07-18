<?php
declare(strict_types=1);
/**
 * Lottie Animation block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one Lottie instance. Nothing is
 * emitted unless the user picked a value, so an untouched block keeps the theme
 * defaults. The animation box (`.flexa-lottie__player`) carries the width/height;
 * the wrapper (`.flexa-lottie-<id>`) carries alignment, spacing, background,
 * border, shadow and advanced layout. Playback options (loop / speed / reverse /
 * play trigger) are read by view.ts and emit no CSS.
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
 * Lottie CSS generator.
 */
class Lottie_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Alignment keyword → justify-content value (the wrapper is a flex container).
	 *
	 * @var array
	 */
	private static $align_map = [
		'left'   => 'flex-start',
		'center' => 'center',
		'right'  => 'flex-end',
	];

	/**
	 * Generate CSS for a Lottie instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap   = '.flexa-lottie-' . $id;
		$player = $wrap . ' .flexa-lottie__player';
		$hover  = $wrap . ':hover';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Player box width + height.
			$width = $attrs['width'][ $device ] ?? [];
			if ( ! empty( $width['value'] ) ) {
				$css->set_selector( $player )->add_property( 'width', CSS_Helpers::with_unit( $width['value'], $width['unit'] ?? 'px' ) );
			}
			$height = $attrs['height'][ $device ] ?? [];
			if ( ! empty( $height['value'] ) ) {
				$css->set_selector( $player )->add_property( 'height', CSS_Helpers::with_unit( $height['value'], $height['unit'] ?? 'px' ) );
			}

			// Alignment → justify-content on the wrapper.
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( isset( self::$align_map[ $align ] ) ) {
				$css->set_selector( $wrap )->add_property( 'justify-content', self::$align_map[ $align ] );
			}

			// Foundational spacing: padding + margin on the wrapper.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			$padding = CSS_Helpers::spacing_shorthand( $spacing['padding'] ?? [] );
			if ( '' !== $padding ) {
				$css->set_selector( $wrap )->add_property( 'padding', $padding );
			}
			$margin = CSS_Helpers::spacing_shorthand( $spacing['margin'] ?? [] );
			if ( '' !== $margin ) {
				$css->set_selector( $wrap )->add_property( 'margin', $margin );
			}

			// Foundational border on the wrapper.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_border( $css, $border );
			}

			// Foundational advanced layout (overflow / position / z-index / inset).
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Foundational wrapper border dark colour (light value + geometry above).
		CSS_Helpers::dark_color( $css, $wrap, 'border-color', CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );

		// Base background (colour / gradient / image), light + dark. Emitted eagerly
		// — view.js only drives the animation, not a lazy-background reveal, so the
		// image url must apply on the base selector.
		$background = $attrs['background'] ?? [];
		if ( ! empty( $background ) ) {
			$css->set_selector( $wrap );
			CSS_Helpers::add_background( $css, $background );

			$type = $background['type'] ?? 'none';
			if ( 'classic' === $type || 'color' === $type ) {
				CSS_Helpers::dark_color( $css, $wrap, 'background-color', CSS_Helpers::dark( $background['color'] ?? '' ) );
			} elseif ( 'gradient' === $type ) {
				CSS_Helpers::dark_color( $css, $wrap, 'background-image', CSS_Helpers::dark( $background['gradient'] ?? '' ) );
			}
		}

		// Background hover colour on the wrapper, light + dark.
		$hover_light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['backgroundColorHover'] ?? '' ) );
		if ( '' !== $hover_light ) {
			$css->set_selector( $hover )->add_property( 'background-color', $hover_light );
		}
		CSS_Helpers::dark_color( $css, $hover, 'background-color', CSS_Helpers::dark( $attrs['backgroundColorHover'] ?? '' ) );

		// Foundational wrapper box shadow, light + dark.
		$shadow_cfg = $attrs['boxShadow'] ?? [];
		$shadow     = CSS_Helpers::box_shadow( $shadow_cfg );
		if ( '' !== $shadow ) {
			$css->set_selector( $wrap )->add_property( 'box-shadow', $shadow );
			$shadow_dark = CSS_Helpers::dark( $shadow_cfg['color'] ?? '' );
			if ( '' !== $shadow_dark ) {
				CSS_Helpers::dark_color( $css, $wrap, 'box-shadow', CSS_Helpers::box_shadow( $shadow_cfg, $shadow_dark ) );
			}
		}
	}
}
