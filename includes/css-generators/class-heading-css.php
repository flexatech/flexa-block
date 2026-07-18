<?php
declare(strict_types=1);
/**
 * Heading block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one heading instance. Nothing is
 * emitted unless the user picked a value, so an untouched heading keeps the
 * theme's typography. Declarations target the title / subheading / separator
 * inside the block's own `.flexa-heading-<id>` wrapper.
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
 * Heading CSS generator.
 */
class Heading_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map an alignment keyword to a flex `align-self` value (used to position
	 * the fixed-width separator inside the flex-column wrapper).
	 *
	 * @var array<string, string>
	 */
	private static $align_self = [
		'left'    => 'flex-start',
		'center'  => 'center',
		'right'   => 'flex-end',
		'justify' => 'flex-start',
	];

	/**
	 * Generate CSS for a heading instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap        = '.flexa-heading-' . $id;
		$title       = $wrap . ' .flexa-heading__title';
		$title_hover = $title . ':hover';
		$sub         = $wrap . ' .flexa-heading__subheading';
		$sep         = $wrap . ' .flexa-heading__separator';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Alignment: text-align on the wrapper, align-self on the separator.
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( '' !== $align ) {
				$css->set_selector( $wrap )->add_property( 'text-align', $align );
				if ( isset( self::$align_self[ $align ] ) ) {
					$css->set_selector( $sep )->add_property( 'align-self', self::$align_self[ $align ] );
				}
			}

			// Typography on the title + subheading.
			CSS_Helpers::add_typography( $css, $title, $attrs['typography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $sub, $attrs['subheadingTypography'][ $device ] ?? [] );

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

			CSS_Helpers::close_device( $css, $device );
		}

		// Gap between the title / subheading / separator.
		$gap = $attrs['gap'] ?? [];
		if ( ! empty( $gap['value'] ) ) {
			$css->set_selector( $wrap )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
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

		// Title colour / gradient text, effects and blend mode (light).
		self::add_title_colour( $css, $title, $attrs );

		// Text stroke (light + dark) — shared helper.
		CSS_Helpers::add_text_stroke( $css, $title, $attrs['textStroke'] ?? [] );

		// Hover colour (light).
		$hover_light = CSS_Helpers::light( $attrs['textColorHover'] ?? '' );
		if ( '' !== $hover_light ) {
			$css->set_selector( $title_hover )->add_property( 'color', $hover_light );
		}

		// Subheading colour (light).
		$sub_light = CSS_Helpers::light( $attrs['subheadingColor'] ?? '' );
		if ( '' !== $sub_light ) {
			$css->set_selector( $sub )->add_property( 'color', $sub_light );
		}

		// Separator geometry (light).
		self::add_separator( $css, $sep, $attrs );

		// Dark mode.
		self::add_dark( $css, $wrap, $title, $title_hover, $sub, $sep, $attrs, $background );
	}

	/**
	 * Title colour / gradient text / stroke / shadow / blend mode (light).
	 *
	 * @param CSS_Builder $css   Builder.
	 * @param string      $title Title selector.
	 * @param array       $attrs Attributes.
	 */
	private static function add_title_colour( $css, $title, $attrs ) {
		$type     = $attrs['textType'] ?? 'color';
		$gradient = CSS_Helpers::light( $attrs['textGradient'] ?? '' );
		$colour   = CSS_Helpers::light( $attrs['textColor'] ?? '' );

		if ( 'gradient' === $type && '' !== $gradient ) {
			$css->set_selector( $title )
				->add_property( 'background-image', $gradient )
				->add_property( '-webkit-background-clip', 'text' )
				->add_property( 'background-clip', 'text' )
				->add_property( '-webkit-text-fill-color', 'transparent' )
				->add_property( 'color', 'transparent' );
		} elseif ( 'gradient' !== $type && '' !== $colour ) {
			$css->set_selector( $title )->add_property( 'color', $colour );
		}

		// Text shadow.
		$text_shadow = CSS_Helpers::text_shadow( $attrs['textShadow'] ?? [] );
		if ( '' !== $text_shadow ) {
			$css->set_selector( $title )->add_property( 'text-shadow', $text_shadow );
		}

		// Blend mode.
		if ( ! empty( $attrs['blendMode'] ) ) {
			$blend_mode = CSS_Helpers::sanitize_enum( $attrs['blendMode'], [ 'multiply', 'screen', 'overlay', 'darken', 'lighten', 'color-dodge', 'color-burn', 'hard-light', 'soft-light', 'difference', 'exclusion' ] );
			if ( '' !== $blend_mode ) {
				$css->set_selector( $title )->add_property( 'mix-blend-mode', $blend_mode );
			}
		}
	}

	/**
	 * Separator geometry (width / weight / style / colour / spacing), light.
	 *
	 * @param CSS_Builder $css   Builder.
	 * @param string      $sep   Separator selector.
	 * @param array       $attrs Attributes.
	 */
	private static function add_separator( $css, $sep, $attrs ) {
		$css->set_selector( $sep );

		$width = $attrs['separatorWidth'] ?? [];
		if ( ! empty( $width['value'] ) ) {
			$css->add_property( 'width', CSS_Helpers::with_unit( $width['value'], $width['unit'] ?? 'px' ) );
		}
		$weight = $attrs['separatorWeight'] ?? [];
		if ( ! empty( $weight['value'] ) ) {
			$css->add_property( 'border-top-width', CSS_Helpers::with_unit( $weight['value'], $weight['unit'] ?? 'px' ) );
		}
		if ( ! empty( $attrs['separatorStyle'] ) ) {
			$separator_style = CSS_Helpers::sanitize_enum( $attrs['separatorStyle'], [ 'solid', 'dashed', 'dotted', 'double' ] );
			if ( '' !== $separator_style ) {
				$css->add_property( 'border-top-style', $separator_style );
			}
		}
		$colour = CSS_Helpers::light( $attrs['separatorColor'] ?? '' );
		if ( '' !== $colour ) {
			$css->add_property( 'border-top-color', $colour );
		}

		$spacing = $attrs['separatorSpacing'] ?? [];
		if ( ! empty( $spacing['value'] ) ) {
			$value = CSS_Helpers::with_unit( $spacing['value'], $spacing['unit'] ?? 'px' );
			$prop  = 'top' === ( $attrs['separatorPosition'] ?? 'bottom' ) ? 'margin-bottom' : 'margin-top';
			$css->add_property( $prop, $value );
		}
	}

	/**
	 * Dark-mode declarations for the title / hover / subheading / separator /
	 * wrapper.
	 *
	 * @param CSS_Builder $css         Builder.
	 * @param string      $wrap        Wrapper selector.
	 * @param string      $title       Title selector.
	 * @param string      $title_hover Title hover selector.
	 * @param string      $sub         Subheading selector.
	 * @param string      $sep         Separator selector.
	 * @param array       $attrs       Attributes.
	 * @param array       $background  Background attribute.
	 */
	private static function add_dark( $css, $wrap, $title, $title_hover, $sub, $sep, $attrs, $background ) {
		// Title text / gradient / stroke / shadow.
		CSS_Helpers::add_dark_mode(
			$css,
			$title,
			function ( $css ) use ( $attrs ) {
				$type = $attrs['textType'] ?? 'color';
				if ( 'gradient' === $type ) {
					$gradient = CSS_Helpers::dark( $attrs['textGradient'] ?? '' );
					if ( '' !== $gradient ) {
						$css->add_property( 'background-image', $gradient );
					}
				} else {
					$colour = CSS_Helpers::dark( $attrs['textColor'] ?? '' );
					if ( '' !== $colour ) {
						$css->add_property( 'color', $colour );
					}
				}

				$shadow = $attrs['textShadow'] ?? [];
				if ( ! empty( $shadow['enabled'] ) ) {
					$shadow_dark = CSS_Helpers::dark( $shadow['color'] ?? '' );
					if ( '' !== $shadow_dark ) {
						$value = CSS_Helpers::text_shadow( $shadow, $shadow_dark );
						if ( '' !== $value ) {
							$css->add_property( 'text-shadow', $value );
						}
					}
				}
			}
		);

		// Hover colour.
		$hover_dark = CSS_Helpers::dark( $attrs['textColorHover'] ?? '' );
		if ( '' !== $hover_dark ) {
			CSS_Helpers::add_dark_mode(
				$css,
				$title_hover,
				function ( $css ) use ( $hover_dark ) {
					$css->add_property( 'color', $hover_dark );
				}
			);
		}

		// Subheading colour.
		$sub_dark = CSS_Helpers::dark( $attrs['subheadingColor'] ?? '' );
		if ( '' !== $sub_dark ) {
			CSS_Helpers::add_dark_mode(
				$css,
				$sub,
				function ( $css ) use ( $sub_dark ) {
					$css->add_property( 'color', $sub_dark );
				}
			);
		}

		// Separator colour.
		$sep_dark = CSS_Helpers::dark( $attrs['separatorColor'] ?? '' );
		if ( '' !== $sep_dark ) {
			CSS_Helpers::add_dark_mode(
				$css,
				$sep,
				function ( $css ) use ( $sep_dark ) {
					$css->add_property( 'border-top-color', $sep_dark );
				}
			);
		}

		// Wrapper background / border / shadow.
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
