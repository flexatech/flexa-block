<?php
declare(strict_types=1);
/**
 * Testimonial block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one testimonial card. Nothing is
 * emitted unless the user picked a value, so an untouched card keeps the theme's
 * typography and the base layout from style.scss. Declarations target the
 * rating / title / quote / author parts inside the block's own
 * `.flexa-testimonial-<id>` wrapper.
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
 * Testimonial CSS generator.
 */
class Testimonial_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map a content alignment to its flex main-axis placement.
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
		return $map[ $align ] ?? 'flex-start';
	}

	/**
	 * Generate CSS for a testimonial instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap   = '.flexa-testimonial-' . $id;
		$rating = $wrap . ' .flexa-testimonial__rating';
		$star   = $wrap . ' .flexa-testimonial__star';
		$title  = $wrap . ' .flexa-testimonial__title';
		$quote  = $wrap . ' .flexa-testimonial__quote';
		$author = $wrap . ' .flexa-testimonial__author';
		$avatar = $wrap . ' .flexa-testimonial__avatar';
		$name   = $wrap . ' .flexa-testimonial__name';
		$job    = $wrap . ' .flexa-testimonial__job';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Content alignment — card text-align + how children line up.
			$align = $attrs['alignment'][ $device ] ?? '';
			if ( '' !== $align ) {
				$css->set_selector( $wrap )
					->add_property( 'text-align', $align )
					->add_property( 'align-items', self::align_items( $align ) );
			}

			// Rating: spacing below + star size.
			$rating_spacing = $attrs['ratingSpacing'][ $device ] ?? [];
			if ( ! empty( $rating_spacing['value'] ) ) {
				$css->set_selector( $rating )->add_property( 'margin-bottom', CSS_Helpers::with_unit( $rating_spacing['value'], $rating_spacing['unit'] ?? 'px' ) );
			}
			$rating_size = $attrs['ratingSize'][ $device ] ?? [];
			if ( ! empty( $rating_size['value'] ) ) {
				$value = CSS_Helpers::with_unit( $rating_size['value'], $rating_size['unit'] ?? 'px' );
				$css->set_selector( $star )->add_property( 'width', $value )->add_property( 'height', $value );
			}

			// Title: typography + spacing below.
			CSS_Helpers::add_typography( $css, $title, $attrs['titleTypography'][ $device ] ?? [] );
			$title_spacing = $attrs['titleSpacing'][ $device ] ?? [];
			if ( ! empty( $title_spacing['value'] ) ) {
				$css->set_selector( $title )->add_property( 'margin-bottom', CSS_Helpers::with_unit( $title_spacing['value'], $title_spacing['unit'] ?? 'px' ) );
			}

			// Quote: typography + spacing below.
			CSS_Helpers::add_typography( $css, $quote, $attrs['quoteTypography'][ $device ] ?? [] );
			$quote_spacing = $attrs['quoteSpacing'][ $device ] ?? [];
			if ( ! empty( $quote_spacing['value'] ) ) {
				$css->set_selector( $quote )->add_property( 'margin-bottom', CSS_Helpers::with_unit( $quote_spacing['value'], $quote_spacing['unit'] ?? 'px' ) );
			}

			// Author: avatar size, name / role typography, gap to the text.
			$avatar_size = $attrs['authorImageSize'][ $device ] ?? [];
			if ( ! empty( $avatar_size['value'] ) ) {
				$value = CSS_Helpers::with_unit( $avatar_size['value'], $avatar_size['unit'] ?? 'px' );
				$css->set_selector( $avatar )->add_property( 'width', $value )->add_property( 'height', $value );
			}
			CSS_Helpers::add_typography( $css, $name, $attrs['nameTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $job, $attrs['jobTypography'][ $device ] ?? [] );
			$author_gap = $attrs['authorGap'][ $device ] ?? [];
			if ( ! empty( $author_gap['value'] ) ) {
				$css->set_selector( $author )->add_property( 'gap', CSS_Helpers::with_unit( $author_gap['value'], $author_gap['unit'] ?? 'px' ) );
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

			// Min height on the card.
			$min_height = $attrs['size'][ $device ]['minHeight'] ?? [];
			if ( ! empty( $min_height['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'min-height', CSS_Helpers::with_unit( $min_height['value'], $min_height['unit'] ?? 'px' ) );
			}

			// Border (4-side outline + radius) on the card.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_border( $css, $border );
			}

			// Advanced layout (overflow / position / z-index) on the card.
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Colours — light at the base, dark under the dark-mode branch.
		self::add_color( $css, $rating, $attrs['ratingColor'] ?? '' );
		self::add_color( $css, $title, $attrs['titleColor'] ?? '' );
		self::add_color( $css, $quote, $attrs['quoteColor'] ?? '' );
		self::add_color( $css, $name, $attrs['nameColor'] ?? '' );
		self::add_color( $css, $job, $attrs['jobColor'] ?? '' );

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
	 * Emit a solid text colour: light at the base, dark under the dark branch.
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
