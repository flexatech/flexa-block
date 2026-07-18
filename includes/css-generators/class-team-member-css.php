<?php
declare(strict_types=1);
/**
 * Team Member block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one team-member card. Nothing is
 * emitted unless the user picked a value, so an untouched card keeps the theme's
 * typography and the base layout / photo shape from style.scss. Declarations
 * target the photo / name / role / bio / social parts inside the block's own
 * `.flexa-team-member-<id>` wrapper.
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
 * Team Member CSS generator.
 */
class Team_Member_CSS {

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
	 * Generate CSS for a team-member instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap    = '.flexa-team-member-' . $id;
		$image   = $wrap . ' .flexa-team-member__image';
		$content = $wrap . ' .flexa-team-member__content';
		$name    = $wrap . ' .flexa-team-member__name';
		$role    = $wrap . ' .flexa-team-member__role';
		$bio     = $wrap . ' .flexa-team-member__bio';
		$social  = $wrap . ' .flexa-team-member__social';
		$link    = $wrap . ' .flexa-team-member__social-link';
		$link_h  = $link . ':hover';

		$is_top = 'top' === ( $attrs['imagePosition'] ?? 'top' );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Content alignment — text-align on the wrapper, cross-axis on the
			// content column (and on the wrapper too for the photo-top column).
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( '' !== $align ) {
				$css->set_selector( $wrap )->add_property( 'text-align', $align );
				if ( $is_top ) {
					$css->set_selector( $wrap )->add_property( 'align-items', self::align_items( $align ) );
				}
				$css->set_selector( $content )->add_property( 'align-items', self::align_items( $align ) );
			}

			// Gap between the photo and the content column.
			$media_gap = $attrs['mediaGap'][ $device ] ?? [];
			if ( ! empty( $media_gap['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'gap', CSS_Helpers::with_unit( $media_gap['value'], $media_gap['unit'] ?? 'px' ) );
			}

			// Card max width.
			$max_width = $attrs['maxWidth'][ $device ] ?? [];
			if ( ! empty( $max_width['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'max-width', CSS_Helpers::with_unit( $max_width['value'], $max_width['unit'] ?? 'px' ) );
			}

			// Photo width.
			$image_width = $attrs['imageWidth'][ $device ] ?? [];
			if ( ! empty( $image_width['value'] ) ) {
				$css->set_selector( $image )->add_property( 'width', CSS_Helpers::with_unit( $image_width['value'], $image_width['unit'] ?? 'px' ) );
			}

			// Typography on the text elements.
			CSS_Helpers::add_typography( $css, $name, $attrs['nameTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $role, $attrs['roleTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $bio, $attrs['bioTypography'][ $device ] ?? [] );

			// Element bottom spacing.
			self::add_spacing( $css, $name, $attrs['nameSpacing'][ $device ] ?? [] );
			self::add_spacing( $css, $role, $attrs['roleSpacing'][ $device ] ?? [] );
			self::add_spacing( $css, $bio, $attrs['bioSpacing'][ $device ] ?? [] );

			// Social: icon size (1em glyph) + gap between icons.
			$social_size = $attrs['socialSize'][ $device ] ?? [];
			if ( ! empty( $social_size['value'] ) ) {
				$css->set_selector( $link )->add_property( 'font-size', CSS_Helpers::with_unit( $social_size['value'], $social_size['unit'] ?? 'px' ) );
			}
			$social_gap = $attrs['socialGap'][ $device ] ?? [];
			if ( ! empty( $social_gap['value'] ) ) {
				$css->set_selector( $social )->add_property( 'gap', CSS_Helpers::with_unit( $social_gap['value'], $social_gap['unit'] ?? 'px' ) );
			}

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
		self::add_color( $css, $name, $attrs['nameColor'] ?? '' );
		self::add_color( $css, $role, $attrs['roleColor'] ?? '' );
		self::add_color( $css, $bio, $attrs['bioColor'] ?? '' );
		self::add_color( $css, $link, $attrs['socialColor'] ?? '' );
		self::add_color( $css, $link_h, $attrs['socialHoverColor'] ?? '' );

		// Dark colour for the card border (light value + geometry emitted above).
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
	 * Emit a `margin-bottom` for an element's bottom-spacing value.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param array       $length   Length value { value, unit }.
	 */
	private static function add_spacing( $css, $selector, $length ) {
		if ( ! is_array( $length ) || empty( $length['value'] ) ) {
			return;
		}
		$css->set_selector( $selector )->add_property( 'margin-bottom', CSS_Helpers::with_unit( $length['value'], $length['unit'] ?? 'px' ) );
	}

	/**
	 * Emit a solid text colour: light at the base, dark under the dark branch.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param mixed       $color    Colour pair.
	 */
	private static function add_color( $css, $selector, $color ) {
		$light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $color ) );
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
