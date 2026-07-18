<?php
declare(strict_types=1);
/**
 * Info Box block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one info-box instance. Nothing is
 * emitted unless the user picked a value, so an untouched card keeps the theme's
 * typography and the base layout from style.scss. Declarations target the media /
 * prefix / title / separator / description / button parts inside the block's own
 * `.flexa-info-box-<id>` wrapper.
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
 * Info Box CSS generator.
 */
class Info_Box_CSS {

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
	 * Generate CSS for an info-box instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap    = '.flexa-info-box-' . $id;
		$media   = $wrap . ' .flexa-info-box__media';
		$icon    = $wrap . ' .flexa-info-box__icon';
		$image   = $wrap . ' .flexa-info-box__image';
		$content = $wrap . ' .flexa-info-box__content';
		$prefix  = $wrap . ' .flexa-info-box__prefix';
		$title   = $wrap . ' .flexa-info-box__title';
		$sep     = $wrap . ' .flexa-info-box__separator';
		$desc    = $wrap . ' .flexa-info-box__description';
		$button  = $wrap . ' .flexa-info-box__button';
		$btn_hov = $button . ':hover';

		$icon_position = 'left' === ( $attrs['iconPosition'] ?? 'top' ) ? 'left' : 'top';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Content alignment — text-align on the wrapper, cross-axis on the
			// content column (and on the wrapper too for the icon-top column).
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( '' !== $align ) {
				$css->set_selector( $wrap )->add_property( 'text-align', $align );
				if ( 'top' === $icon_position ) {
					$css->set_selector( $wrap )->add_property( 'align-items', self::align_items( $align ) );
				}
				$css->set_selector( $content )->add_property( 'align-items', self::align_items( $align ) );
			}

			// Gaps: media↔content on the wrapper, element↔element on the content.
			$media_gap = $attrs['mediaGap'][ $device ] ?? [];
			if ( ! empty( $media_gap['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'gap', CSS_Helpers::with_unit( $media_gap['value'], $media_gap['unit'] ?? 'px' ) );
			}
			$content_gap = $attrs['contentGap'][ $device ] ?? [];
			if ( ! empty( $content_gap['value'] ) ) {
				$css->set_selector( $content )->add_property( 'gap', CSS_Helpers::with_unit( $content_gap['value'], $content_gap['unit'] ?? 'px' ) );
			}

			// Media sizing: icon font-size (1em glyph) or image width.
			$icon_size = $attrs['iconSize'][ $device ] ?? [];
			if ( ! empty( $icon_size['value'] ) ) {
				$css->set_selector( $icon )->add_property( 'font-size', CSS_Helpers::with_unit( $icon_size['value'], $icon_size['unit'] ?? 'px' ) );
			}
			$image_width = $attrs['imageWidth'][ $device ] ?? [];
			if ( ! empty( $image_width['value'] ) ) {
				$css->set_selector( $image )->add_property( 'width', CSS_Helpers::with_unit( $image_width['value'], $image_width['unit'] ?? 'px' ) );
			}

			// Media box: padding + radius.
			$media_padding = CSS_Helpers::spacing_shorthand( $attrs['mediaPadding'][ $device ] ?? [] );
			if ( '' !== $media_padding ) {
				$css->set_selector( $media )->add_property( 'padding', $media_padding );
			}
			$media_radius = $attrs['mediaRadius'][ $device ] ?? [];
			if ( ! empty( $media_radius['value'] ) ) {
				$radius = CSS_Helpers::with_unit( $media_radius['value'], $media_radius['unit'] ?? 'px' );
				$css->set_selector( $media )->add_property( 'border-radius', $radius );
				$css->set_selector( $image )->add_property( 'border-radius', $radius );
			}

			// Typography on the text elements.
			CSS_Helpers::add_typography( $css, $prefix, $attrs['prefixTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $title, $attrs['titleTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $desc, $attrs['descriptionTypography'][ $device ] ?? [] );

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

			// Border (4-side outline + radius) on the card.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_border( $css, $border );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Colours — light at the base, dark under the dark-mode branch.
		self::add_color( $css, $icon, 'color', $attrs['iconColor'] ?? '' );
		self::add_color( $css, $media, 'background-color', $attrs['mediaBackground'] ?? '' );
		self::add_color( $css, $prefix, 'color', $attrs['prefixColor'] ?? '' );
		self::add_color( $css, $title, 'color', $attrs['titleColor'] ?? '' );
		self::add_color( $css, $desc, 'color', $attrs['descriptionColor'] ?? '' );

		// Separator geometry (light) + dark colour — only when the divider is shown.
		if ( ! empty( $attrs['showSeparator'] ) ) {
			self::add_separator( $css, $sep, $attrs );
		}

		// CTA button colours (light) + hover + dark.
		self::add_color( $css, $button, 'color', $attrs['buttonTextColor'] ?? '' );
		self::add_color( $css, $button, 'background-color', $attrs['buttonBgColor'] ?? '' );
		$hover = $attrs['buttonHover'] ?? [];
		self::add_color( $css, $btn_hov, 'color', $hover['text'] ?? '' );
		self::add_color( $css, $btn_hov, 'background-color', $hover['background'] ?? '' );

		// Card border dark colour (light value + geometry emitted above).
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

		// Box shadow on the card (light + dark).
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $wrap )->add_property( 'box-shadow', $shadow );
		}
		self::add_shadow_dark( $css, $wrap, $attrs['boxShadow'] ?? [] );
	}

	/**
	 * Emit a colour property: light at the base, dark under the dark branch.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param string      $property CSS property (color / background-color).
	 * @param mixed       $color    Colour pair.
	 */
	private static function add_color( $css, $selector, $property, $color ) {
		$light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $color ) );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( $property, $light );
		}
		CSS_Helpers::dark_color( $css, $selector, $property, CSS_Helpers::dark( $color ) );
	}

	/**
	 * Separator geometry (width / weight / style / colour), light + dark colour.
	 *
	 * @param CSS_Builder $css   Builder.
	 * @param string      $sep   Separator selector.
	 * @param array       $attrs Attributes.
	 */
	private static function add_separator( $css, $sep, $attrs ) {
		$width = $attrs['separatorWidth'] ?? [];
		if ( ! empty( $width['value'] ) ) {
			$css->set_selector( $sep )->add_property( 'width', CSS_Helpers::with_unit( $width['value'], $width['unit'] ?? 'px' ) );
		}
		$weight = $attrs['separatorWeight'] ?? [];
		if ( ! empty( $weight['value'] ) ) {
			$css->set_selector( $sep )->add_property( 'border-top-width', CSS_Helpers::with_unit( $weight['value'], $weight['unit'] ?? 'px' ) );
		}
		if ( ! empty( $attrs['separatorStyle'] ) ) {
			$style = CSS_Helpers::sanitize_enum( $attrs['separatorStyle'], [ 'solid', 'dashed', 'dotted', 'double' ] );
			if ( '' !== $style ) {
				$css->set_selector( $sep )->add_property( 'border-top-style', $style );
			}
		}
		$colour = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['separatorColor'] ?? '' ) );
		if ( '' !== $colour ) {
			$css->set_selector( $sep )->add_property( 'border-top-color', $colour );
		}
		CSS_Helpers::dark_color( $css, $sep, 'border-top-color', CSS_Helpers::dark( $attrs['separatorColor'] ?? '' ) );
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
	 * Dark-mode box shadow on the card, mirroring the light shadow with the dark
	 * shadow colour.
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
