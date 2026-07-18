<?php
declare(strict_types=1);
/**
 * Taxonomy block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one taxonomy list instance.
 * Nothing is emitted unless the user picked a value, so an untouched block keeps
 * the theme's typography and the structural defaults from style.scss.
 * Declarations target the list / item / link / count / affix parts inside the
 * block's own `.flexa-taxonomy-<id>` wrapper.
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
 * Taxonomy CSS generator.
 */
class Taxonomy_CSS {

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
	 * Generate CSS for a taxonomy instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$display = CSS_Helpers::sanitize_enum( (string) ( $attrs['displayStyle'] ?? 'list' ), [ 'list', 'inline', 'dropdown', 'grid' ], 'list' );

		$wrap    = '.flexa-taxonomy-' . $id;
		$list    = $wrap . ' .flexa-taxonomy__list';
		$item    = $wrap . ' .flexa-taxonomy__item';
		$item_on = $item . ':hover';
		$link    = $wrap . ' .flexa-taxonomy__link';
		$link_on = $link . ':hover';
		$count   = $wrap . ' .flexa-taxonomy__count';
		$prefix  = $wrap . ' .flexa-taxonomy__prefix';
		$suffix  = $wrap . ' .flexa-taxonomy__suffix';
		$sep     = $wrap . ' .flexa-taxonomy__separator';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Grid columns → grid-template-columns on the list (grid display only).
			if ( 'grid' === $display ) {
				$columns = $attrs['columns'][ $device ] ?? [];
				if ( ! empty( $columns['value'] ) ) {
					$n = max( 1, (int) $columns['value'] );
					$css->set_selector( $list )->add_property( 'grid-template-columns', 'repeat(' . $n . ', minmax(0, 1fr))' );
				}
			}

			// Alignment → text-align + justify-content on the list.
			$align = CSS_Helpers::sanitize_enum( (string) ( $attrs['alignment'][ $device ] ?? '' ), [ 'left', 'center', 'right' ] );
			if ( '' !== $align ) {
				$css->set_selector( $list )->add_property( 'text-align', $align );
				$css->set_selector( $list )->add_property( 'justify-content', self::$align_map[ $align ] );
			}

			// Gap between term chips.
			$gap = $attrs['gap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $list )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Item padding → padding on the link (the chip's clickable body).
			$padding = CSS_Helpers::spacing_shorthand( $attrs['itemPadding'][ $device ] ?? [] );
			if ( '' !== $padding ) {
				$css->set_selector( $link )->add_property( 'padding', $padding );
			}

			// Term typography on the link.
			CSS_Helpers::add_typography( $css, $link, $attrs['typography'][ $device ] ?? [] );

			// Prefix / suffix icon size → font-size + width.
			self::add_icon_size( $css, $prefix, $attrs['prefixIconSize'][ $device ] ?? [] );
			self::add_icon_size( $css, $suffix, $attrs['suffixIconSize'][ $device ] ?? [] );

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

			// Border — on each term chip (not the wrapper).
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $item );
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

		// Item border radius (non-responsive) on each chip.
		$radius = $attrs['itemBorderRadius'] ?? [];
		if ( ! empty( $radius['value'] ) ) {
			$css->set_selector( $item )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
		}

		// --- Colours (light at the base, dark under the dark-mode branch). ---
		CSS_Helpers::color_pair( $css, $link, 'color', $attrs['itemColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $item, 'background', $attrs['itemBackground'] ?? '' );
		// Hover: chip + link text colour, and the chip background.
		CSS_Helpers::color_pair( $css, $item_on, 'color', $attrs['itemHoverColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $link_on, 'color', $attrs['itemHoverColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $item_on, 'background', $attrs['itemHoverBackground'] ?? '' );
		CSS_Helpers::color_pair( $css, $count, 'color', $attrs['countColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $prefix, 'color', $attrs['prefixColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $suffix, 'color', $attrs['suffixColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $sep, 'color', $attrs['separatorColor'] ?? '' );
		// Prefix / suffix icon colour on chip hover.
		CSS_Helpers::color_pair( $css, $item_on . ' .flexa-taxonomy__prefix', 'color', $attrs['iconHoverColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $item_on . ' .flexa-taxonomy__suffix', 'color', $attrs['iconHoverColor'] ?? '' );

		// --- Foundational background (base, light) + lazy image url gated behind the
		//     loaded class, then the wrapper dark branch. ---
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

		// Box shadow (light) on each term chip.
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $item )->add_property( 'box-shadow', $shadow );
		}

		// Dark mode — wrapper: background colour / gradient only.
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

		// Dark mode — chip: border colour + shadow colour (border/shadow live on the item).
		CSS_Helpers::add_dark_mode(
			$css,
			$item,
			function ( $css ) use ( $attrs ) {
				$border_dark = CSS_Helpers::sanitize_color( CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );
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
	 * Emit an affix icon size (font-size + width) onto a selector when set.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector (prefix / suffix).
	 * @param array       $size     LengthValue { value, unit }.
	 */
	private static function add_icon_size( $css, $selector, $size ) {
		if ( empty( $size['value'] ) ) {
			return;
		}
		$value = CSS_Helpers::with_unit( $size['value'], $size['unit'] ?? 'px' );
		$css->set_selector( $selector )
			->add_property( 'font-size', $value )
			->add_property( 'width', $value );
	}
}
