<?php
declare(strict_types=1);
/**
 * Breadcrumb block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one breadcrumb instance. Nothing
 * is emitted unless the user picked a value, so an untouched breadcrumb keeps the
 * theme's typography and the structural defaults from style.scss. Declarations
 * target the list / link / current / separator parts inside the block's own
 * `.flexa-breadcrumb-<id>` wrapper.
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
 * Breadcrumb CSS generator.
 */
class Breadcrumb_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map a content-alignment keyword to its flex justify-content value.
	 *
	 * @var array
	 */
	private static $align_map = [
		'left'   => 'flex-start',
		'center' => 'center',
		'right'  => 'flex-end',
	];

	/**
	 * Generate CSS for a breadcrumb instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap      = '.flexa-breadcrumb-' . $id;
		$list      = $wrap . ' .flexa-breadcrumb__list';
		$item      = $wrap . ' .flexa-breadcrumb__item';
		$link      = $wrap . ' .flexa-breadcrumb__link';
		$link_on   = $link . ':hover';
		$current   = $wrap . ' .flexa-breadcrumb__current';
		$current_on = $current . ':hover';
		$sep       = $wrap . ' .flexa-breadcrumb__separator';
		$sep_on    = $sep . ':hover';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Trail typography (inherited by links, current + separators).
			CSS_Helpers::add_typography( $css, $wrap, $attrs['typography'][ $device ] ?? [] );

			// Max width on the wrapper.
			$max_width = $attrs['maxWidth'][ $device ] ?? [];
			if ( ! empty( $max_width['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'max-width', CSS_Helpers::with_unit( $max_width['value'], $max_width['unit'] ?? 'px' ) );
			}

			// Alignment → justify-content on the list.
			$align = CSS_Helpers::sanitize_enum( (string) ( $attrs['alignment'][ $device ] ?? '' ), [ 'left', 'center', 'right' ] );
			if ( '' !== $align ) {
				$css->set_selector( $list )->add_property( 'justify-content', self::$align_map[ $align ] );
			}

			// Gap between crumbs (on the list and inside each item).
			$gap = $attrs['itemGap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$value = CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' );
				$css->set_selector( $list )->add_property( 'gap', $value );
				$css->set_selector( $item )->add_property( 'gap', $value );
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

		// --- Colours (light at the base, dark under the dark-mode branch). Each is
		//     a plain colour pair with a matching hover selector. ---
		self::add_color( $css, $link, $attrs['linkColor'] ?? '' );
		self::add_color( $css, $link_on, $attrs['linkColorHover'] ?? '' );
		self::add_color( $css, $current, $attrs['currentColor'] ?? '' );
		self::add_color( $css, $current_on, $attrs['currentColorHover'] ?? '' );
		self::add_color( $css, $sep, $attrs['separatorColor'] ?? '' );
		self::add_color( $css, $sep_on, $attrs['separatorColorHover'] ?? '' );

		// Wrapper border dark colour (light value + geometry come from the loop).
		CSS_Helpers::dark_color( $css, $wrap, 'border-color', CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );

		// Wrapper background (base, light) + lazy image url gated behind the loaded
		// class, then the dark background branch.
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
	}

	/**
	 * Emit a solid colour onto a selector: light at the base, dark under the
	 * dark-mode branch. Nothing is emitted when neither light nor dark is set.
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
	 * Dark-mode declarations for the wrapper background.
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
			}
		);
	}
}
