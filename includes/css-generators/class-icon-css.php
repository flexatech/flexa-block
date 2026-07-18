<?php
declare(strict_types=1);
/**
 * Icon block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one Icon instance. Nothing is
 * emitted unless the user picked a value, so an untouched icon keeps the theme's
 * text colour and the base size from style.scss. Frame declarations sit on the
 * `.flexa-icon__inner` span; the glyph's size/colour target the descendant
 * `.flexa-icon` element (the wrapper carries that class too, so the selectors are
 * always scoped to a descendant to avoid the collision).
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
 * Icon CSS generator.
 */
class Icon_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Alignment keyword → auto-margin pair on the shrink-to-fit wrapper. The
	 * wrapper is `width: fit-content` (style.scss), so text-align cannot position
	 * it; auto margins slide it left / centre / right within the column instead.
	 *
	 * @var array
	 */
	private static $align_map = [
		'left'   => [ '0', 'auto' ],
		'center' => [ 'auto', 'auto' ],
		'right'  => [ 'auto', '0' ],
	];

	/**
	 * Frame shape → border-radius on the inner span. `square` has no entry: it
	 * emits nothing (theme-first default).
	 *
	 * @var array
	 */
	private static $shape_radius = [
		'rounded' => '8px',
		'circle'  => '50%',
	];

	/**
	 * Generate CSS for an Icon instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap        = '.flexa-icon-' . $id;
		$inner       = $wrap . ' .flexa-icon__inner';
		$glyph       = $wrap . ' .flexa-icon';
		$hover_inner = $wrap . ':hover .flexa-icon__inner';
		$hover_glyph = $wrap . ':hover .flexa-icon';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Glyph size (width + height on the descendant glyph).
			$size = $attrs['iconSize'][ $device ] ?? [];
			if ( ! empty( $size['value'] ) ) {
				$value = CSS_Helpers::with_unit( $size['value'], $size['unit'] ?? 'px' );
				$css->set_selector( $glyph )->add_property( 'width', $value )->add_property( 'height', $value );
			}

			// Frame border width (needs an explicit style to render).
			$bw = $attrs['iconBorderWidth'][ $device ] ?? [];
			if ( ! empty( $bw['value'] ) ) {
				$css->set_selector( $inner )
					->add_property( 'border-style', 'solid' )
					->add_property( 'border-width', CSS_Helpers::with_unit( $bw['value'], $bw['unit'] ?? 'px' ) );
			}

			// Frame padding.
			$pad = $attrs['iconPadding'][ $device ] ?? [];
			if ( ! empty( $pad['value'] ) ) {
				$css->set_selector( $inner )->add_property( 'padding', CSS_Helpers::with_unit( $pad['value'], $pad['unit'] ?? 'px' ) );
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

			// Alignment → auto margins on the shrink-to-fit wrapper. Emitted after the
			// foundational `margin` shorthand above so these longhands win the cascade.
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( isset( self::$align_map[ $align ] ) ) {
				list( $ml, $mr ) = self::$align_map[ $align ];
				$css->set_selector( $wrap )
					->add_property( 'margin-left', $ml )
					->add_property( 'margin-right', $mr );
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

		// Frame shape → border-radius on the inner span (non-responsive).
		$shape = (string) ( $attrs['shape'] ?? 'square' );
		if ( isset( self::$shape_radius[ $shape ] ) ) {
			$css->set_selector( $inner )->add_property( 'border-radius', self::$shape_radius[ $shape ] );
		}

		// Icon colour (glyph), light + dark.
		self::add_color( $css, $glyph, 'color', $attrs['iconColor'] ?? '' );
		self::add_color( $css, $hover_glyph, 'color', $attrs['iconColorHover'] ?? '' );

		// Frame background (inner span), light + dark.
		self::add_color( $css, $inner, 'background', $attrs['iconBackground'] ?? '' );
		self::add_color( $css, $hover_inner, 'background', $attrs['iconBackgroundHover'] ?? '' );

		// Frame border colour (inner span), light + dark.
		self::add_color( $css, $inner, 'border-color', $attrs['iconBorderColor'] ?? '' );
		self::add_color( $css, $hover_inner, 'border-color', $attrs['iconBorderColorHover'] ?? '' );

		// Foundational wrapper border dark colour (light value + geometry emitted above).
		CSS_Helpers::dark_color( $css, $wrap, 'border-color', CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );

		// Foundational wrapper background (colour / gradient / image), light + dark.
		// Emitted eagerly (no lazy gating): the Icon block ships no view.js to add
		// `.flexa-bg-loaded`, so the image url must apply on the base selector.
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

	/**
	 * Emit one colour declaration on a selector — light at the base, dark under
	 * the dark-mode branch. Nothing is emitted when the light value is empty and
	 * no dark value is present.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param string      $property CSS property (color / background / border-color).
	 * @param mixed       $color    Colour pair.
	 */
	private static function add_color( $css, $selector, $property, $color ) {
		$light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $color ) );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( $property, $light );
		}
		CSS_Helpers::dark_color( $css, $selector, $property, CSS_Helpers::dark( $color ) );
	}
}
