<?php
declare(strict_types=1);
/**
 * Banner block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one banner instance. Nothing is
 * emitted unless the user picked a value, so an untouched banner keeps the theme
 * + the base style.scss (its structural min-height / padding / centred content).
 * The shared promo declarations (heading / description / buttons / content
 * alignment) come from Promo_CSS_Parts; this generator adds the banner-specific
 * wrapper sizing, vertical placement, background and overlay.
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
 * Banner CSS generator.
 */
class Banner_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map a content alignment to its flex cross-axis placement.
	 *
	 * @param string $align left|center|right.
	 * @return string align-items value.
	 */
	private static function align_items( $align ) {
		$map = [
			'left'   => 'flex-start',
			'center' => 'center',
			'right'  => 'flex-end',
		];
		return $map[ $align ] ?? 'center';
	}

	/**
	 * Generate CSS for a banner instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap    = '.flexa-banner-' . $id;
		$overlay = $wrap . ' .flexa-banner__overlay';
		$is_boxed = 'full-width' !== ( $attrs['containerType'] ?? 'full-width' );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

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

			// Section width: max-width (boxed) or width (full-width).
			$width_attr = $is_boxed ? ( $attrs['widthBoxed'][ $device ] ?? [] ) : ( $attrs['widthFullWidth'][ $device ] ?? [] );
			if ( ! empty( $width_attr['value'] ) ) {
				$value = CSS_Helpers::with_unit( $width_attr['value'], $width_attr['unit'] ?? ( $is_boxed ? 'px' : '%' ) );
				$css->set_selector( $wrap )->add_property( $is_boxed ? 'max-width' : 'width', $value );
			}

			// Min height.
			$min_height = $attrs['size'][ $device ]['minHeight'] ?? [];
			if ( ! empty( $min_height['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'min-height', CSS_Helpers::with_unit( $min_height['value'], $min_height['unit'] ?? 'px' ) );
			}

			// Horizontal placement of the (max-width-constrained) content block.
			$align = (string) ( $attrs['contentAlign'][ $device ] ?? '' );
			if ( '' !== $align ) {
				$css->set_selector( $wrap )->add_property( 'align-items', self::align_items( $align ) );
			}

			// Content box max-width: constrain the content wrapper (not the
			// background) and centre it, so a full-bleed banner can still line its
			// content up with the site's boxed grid. Empty = spans the banner.
			$box_width = $attrs['contentBoxWidth'][ $device ] ?? [];
			if ( ! empty( $box_width['value'] ) ) {
				$box_selector = $wrap . ' .flexa-banner__box';
				$css->set_selector( $box_selector )->add_property( 'max-width', CSS_Helpers::with_unit( $box_width['value'], $box_width['unit'] ?? 'px' ) );
				$css->set_selector( $box_selector )->add_property( 'margin-inline', 'auto' );
			}

			// Border.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_border( $css, $border );
			}

			// Advanced layout (z-index / overflow / position).
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Vertical placement of the content within the min-height (non-responsive).
		$vertical = CSS_Helpers::sanitize_enum( $attrs['verticalAlign'] ?? '', [ 'flex-start', 'center', 'flex-end' ] );
		if ( '' !== $vertical ) {
			$css->set_selector( $wrap )->add_property( 'justify-content', $vertical );
		}

		// Wrapper background (base, light) + dark + lazy.
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

		// Box shadow (light).
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $wrap )->add_property( 'box-shadow', $shadow );
		}

		// Dark mode: background, border colour, shadow colour.
		self::add_wrapper_dark( $css, $wrap, $attrs, $background );

		// Overlay (colour / gradient + opacity + blend).
		self::add_overlay( $css, $overlay, $attrs['overlay'] ?? [] );

		// Shared promo content (heading / description / buttons / content align).
		Promo_CSS_Parts::emit( $css, $wrap, $attrs );
	}

	/**
	 * Dark-mode declarations for the wrapper (background / border / shadow).
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

	/**
	 * Overlay declarations: a colour or gradient fill, opacity and blend mode,
	 * light at the base + dark under the dark branch.
	 *
	 * @param CSS_Builder $css     Builder.
	 * @param string      $overlay Overlay selector.
	 * @param array       $attr    Overlay attribute.
	 */
	private static function add_overlay( $css, $overlay, $attr ) {
		$type = $attr['type'] ?? 'none';
		if ( 'color' !== $type && 'gradient' !== $type ) {
			return;
		}

		if ( 'color' === $type ) {
			$light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attr['color'] ?? '' ) );
			if ( '' !== $light ) {
				$css->set_selector( $overlay )->add_property( 'background', $light );
			}
			CSS_Helpers::dark_color( $css, $overlay, 'background', CSS_Helpers::dark( $attr['color'] ?? '' ) );
		} else {
			$light = CSS_Helpers::sanitize_gradient( CSS_Helpers::light( $attr['gradient'] ?? '' ) );
			if ( '' !== $light ) {
				$css->set_selector( $overlay )->add_property( 'background-image', $light );
			}
			CSS_Helpers::dark_color( $css, $overlay, 'background-image', CSS_Helpers::dark( $attr['gradient'] ?? '' ) );
		}

		$opacity = isset( $attr['opacity'] ) ? max( 0, min( 100, (int) $attr['opacity'] ) ) : 50;
		$css->set_selector( $overlay )->add_property( 'opacity', (string) ( $opacity / 100 ) );

		if ( ! empty( $attr['blendMode'] ) ) {
			$blend = CSS_Helpers::sanitize_enum(
				$attr['blendMode'],
				[ 'multiply', 'screen', 'overlay', 'darken', 'lighten', 'color-dodge', 'color-burn', 'hard-light', 'soft-light', 'difference', 'exclusion' ]
			);
			if ( '' !== $blend ) {
				$css->set_selector( $overlay )->add_property( 'mix-blend-mode', $blend );
			}
		}
	}
}
