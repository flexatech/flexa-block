<?php
declare(strict_types=1);
/**
 * Star Rating block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one star-rating instance. Nothing
 * is emitted unless the user picked a value, so an untouched rating keeps the
 * theme colour (filled stars solid, empty stars faint) and the base layout from
 * style.scss. Declarations target the stars row / individual stars / the marked
 * (fill) overlay / the title inside the block's own `.flexa-star-rating-<id>`
 * wrapper.
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
 * Star Rating CSS generator.
 */
class Star_Rating_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map a content alignment to its flex placement.
	 *
	 * @param string $align left|center|right.
	 * @return string flex value.
	 */
	private static function align_flex( $align ) {
		$map = [
			'left'   => 'flex-start',
			'center' => 'center',
			'right'  => 'flex-end',
		];
		return $map[ $align ] ?? 'flex-start';
	}

	/**
	 * Generate CSS for a star-rating instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$layout = 'stacked' === ( $attrs['ratingLayout'] ?? 'inline' ) ? 'stacked' : 'inline';

		$wrap  = '.flexa-star-rating-' . $id;
		$stars = $wrap . ' .flexa-star-rating__stars';
		$star  = $wrap . ' .flexa-star-rating__star';
		$base  = $wrap . ' .flexa-star-rating__star > svg';
		$fill  = $wrap . ' .flexa-star-rating__star-fill';
		$title = $wrap . ' .flexa-star-rating__title';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Alignment — how the title + stars line up (and how the title text sits).
			$align = $attrs['alignment'][ $device ] ?? '';
			if ( '' !== $align ) {
				$flex = self::align_flex( $align );
				$css->set_selector( $wrap );
				if ( 'stacked' === $layout ) {
					$css->add_property( 'align-items', $flex );
				} else {
					$css->add_property( 'justify-content', $flex );
				}
				$css->add_property( 'text-align', $align );
			}

			// Star size (width + height on each star box).
			$size = $attrs['starSize'][ $device ] ?? [];
			if ( ! empty( $size['value'] ) ) {
				$value = CSS_Helpers::with_unit( $size['value'], $size['unit'] ?? 'px' );
				$css->set_selector( $star )->add_property( 'width', $value )->add_property( 'height', $value );
			}

			// Gap between stars.
			$gap = $attrs['gap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $stars )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Gap between the title and the stars (on the wrapper flex row/column).
			$title_gap = $attrs['titleGap'][ $device ] ?? [];
			if ( ! empty( $title_gap['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'gap', CSS_Helpers::with_unit( $title_gap['value'], $title_gap['unit'] ?? 'px' ) );
			}

			// Title typography.
			CSS_Helpers::add_typography( $css, $title, $attrs['titleTypography'][ $device ] ?? [] );

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

		// Colours — light at the base, dark under the dark-mode branch.
		self::add_color( $css, $fill, $attrs['color'] ?? '' );          // marked (filled) stars.
		self::add_color( $css, $base, $attrs['unmarkedColor'] ?? '' );  // unmarked (empty) stars.
		self::add_color( $css, $title, $attrs['titleColor'] ?? '' );    // title.

		// Dark colour for the wrapper border (light value + geometry emitted above).
		CSS_Helpers::dark_color( $css, $wrap, 'border-color', CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );

		// Wrapper background (base, light) + dark.
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
		self::add_wrapper_dark( $css, $wrap, $background );

		// Box shadow on the wrapper (light + dark).
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $wrap )->add_property( 'box-shadow', $shadow );
		}
		self::add_shadow_dark( $css, $wrap, $attrs['boxShadow'] ?? [] );
	}

	/**
	 * Emit a solid colour: light at the base, dark under the dark branch. SVG
	 * stars fill with `currentColor`, so setting `color` tints them.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param mixed       $color    Colour pair.
	 */
	private static function add_color( $css, $selector, $color ) {
		$light = CSS_Helpers::light( $color );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( 'color', $light );
		}
		CSS_Helpers::dark_color( $css, $selector, 'color', CSS_Helpers::dark( $color ) );
	}

	/**
	 * Dark-mode declarations for the wrapper background (colour or gradient).
	 *
	 * @param CSS_Builder $css        Builder.
	 * @param string      $wrap       Wrapper selector.
	 * @param array       $background Background attribute.
	 */
	private static function add_wrapper_dark( $css, $wrap, $background ) {
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
	}

	/**
	 * Dark-mode box shadow on the wrapper, mirroring the light shadow with the
	 * dark shadow colour.
	 *
	 * @param CSS_Builder $css    Builder.
	 * @param string      $wrap   Wrapper selector.
	 * @param array       $shadow Box shadow attribute.
	 */
	private static function add_shadow_dark( $css, $wrap, $shadow ) {
		if ( empty( $shadow['enabled'] ) ) {
			return;
		}
		$shadow_dark = CSS_Helpers::dark( $shadow['color'] ?? '' );
		if ( '' === $shadow_dark ) {
			return;
		}
		$value = CSS_Helpers::box_shadow( $shadow, $shadow_dark );
		if ( '' === $value ) {
			return;
		}
		CSS_Helpers::add_dark_mode(
			$css,
			$wrap,
			function ( $css ) use ( $value ) {
				$css->add_property( 'box-shadow', $value );
			}
		);
	}
}
