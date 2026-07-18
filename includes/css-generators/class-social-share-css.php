<?php
declare(strict_types=1);
/**
 * Social Share block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one Social Share instance.
 * Nothing is emitted unless the user picked a value, so an untouched block
 * keeps the official brand artwork and the theme's spacing. Colour mode, tint,
 * shape and button background are block-level (uniform across every button),
 * so no per-item :nth-child rules are needed.
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
 * Social Share CSS generator.
 */
class Social_Share_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Alignment keyword → flex justify-content.
	 *
	 * @var array
	 */
	private static $justify_map = [
		'left'   => 'flex-start',
		'center' => 'center',
		'right'  => 'flex-end',
	];

	/**
	 * Generate CSS for a Social Share instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap = '.flexa-social-share-' . $id;
		$list = $wrap . ' .flexa-social-share__list';
		$item = $wrap . ' .flexa-social-share__item';
		$icon = $wrap . ' .flexa-social-share__icon';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Gap between buttons.
			$gap = $attrs['gap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $list )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Row alignment.
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( isset( self::$justify_map[ $align ] ) ) {
				$css->set_selector( $list )->add_property( 'justify-content', self::$justify_map[ $align ] );
			}

			// Icon size (square).
			$size = $attrs['iconSize'][ $device ] ?? [];
			if ( ! empty( $size['value'] ) ) {
				$value = CSS_Helpers::with_unit( $size['value'], $size['unit'] ?? 'px' );
				$css->set_selector( $icon )->add_property( 'width', $value )->add_property( 'height', $value );
			}

			// Wrapper spacing: padding + margin.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			$padding = CSS_Helpers::spacing_shorthand( $spacing['padding'] ?? [] );
			if ( '' !== $padding ) {
				$css->set_selector( $wrap )->add_property( 'padding', $padding );
			}
			$margin = CSS_Helpers::spacing_shorthand( $spacing['margin'] ?? [] );
			if ( '' !== $margin ) {
				$css->set_selector( $wrap )->add_property( 'margin', $margin );
			}

			// Wrapper border.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_border( $css, $border );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Column direction (block-level, no cascade).
		if ( 'column' === ( $attrs['direction'] ?? 'row' ) ) {
			$css->set_selector( $list )->add_property( 'flex-direction', 'column' );
		}

		// Dark colour for the wrapper border (light value + geometry above).
		CSS_Helpers::dark_color( $css, $wrap, 'border-color', CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );

		// Wrapper background (colour or gradient), light + dark.
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

		// Wrapper box shadow, light + dark.
		$shadow_cfg = $attrs['boxShadow'] ?? [];
		$shadow     = CSS_Helpers::box_shadow( $shadow_cfg );
		if ( '' !== $shadow ) {
			$css->set_selector( $wrap )->add_property( 'box-shadow', $shadow );
			$shadow_dark = CSS_Helpers::dark( $shadow_cfg['color'] ?? '' );
			if ( '' !== $shadow_dark ) {
				CSS_Helpers::dark_color( $css, $wrap, 'box-shadow', CSS_Helpers::box_shadow( $shadow_cfg, $shadow_dark ) );
			}
		}

		// Icon tint (custom colour mode only) — the mono glyph uses currentColor.
		if ( 'custom' === ( $attrs['colorMode'] ?? 'official' ) ) {
			$tint_light = CSS_Helpers::light( $attrs['tint'] ?? '' );
			if ( '' !== $tint_light ) {
				$css->set_selector( $item )->add_property( 'color', $tint_light );
			}
			CSS_Helpers::dark_color( $css, $item, 'color', CSS_Helpers::dark( $attrs['tint'] ?? '' ) );
		}

		// Button background (chip behind the icon), light + dark.
		$btn_light = CSS_Helpers::light( $attrs['buttonBackground'] ?? '' );
		if ( '' !== $btn_light ) {
			$css->set_selector( $item )->add_property( 'background-color', $btn_light );
		}
		CSS_Helpers::dark_color( $css, $item, 'background-color', CSS_Helpers::dark( $attrs['buttonBackground'] ?? '' ) );
	}
}
