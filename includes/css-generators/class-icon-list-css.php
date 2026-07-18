<?php
declare(strict_types=1);
/**
 * Icon List block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one icon-list instance. Nothing
 * is emitted unless the user picked a value, so an untouched list keeps the
 * theme's typography and the base layout from style.scss. Declarations target
 * the list / item / icon / text parts inside the block's own
 * `.flexa-icon-list-<id>` wrapper.
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
 * Icon List CSS generator.
 */
class Icon_List_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map a content-alignment keyword to a flex main-axis value (the row is a
	 * flex row, so alignment moves the icon + text horizontally).
	 *
	 * @var array<string, string>
	 */
	private static $justify = [
		'left'   => 'flex-start',
		'center' => 'center',
		'right'  => 'flex-end',
	];

	/**
	 * Frame corner radius per icon shape.
	 *
	 * @var array<string, string>
	 */
	private static $shape_radius = [
		'square'  => '0',
		'rounded' => '8px',
		'circle'  => '50%',
	];

	/**
	 * Generate CSS for an icon-list instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap       = '.flexa-icon-list-' . $id;
		$list       = $wrap . ' .flexa-icon-list__list';
		$item       = $wrap . ' .flexa-icon-list__item';
		$icon       = $wrap . ' .flexa-icon-list__icon';
		$icon_hover = $item . ':hover .flexa-icon-list__icon';
		$text       = $wrap . ' .flexa-icon-list__text';
		$text_hover = $item . ':hover .flexa-icon-list__text';

		$is_grid = 'grid' === ( $attrs['view'] ?? 'list' );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Grid column count (grid view only).
			if ( $is_grid ) {
				$columns = $attrs['columns'][ $device ] ?? [];
				if ( isset( $columns['value'] ) && '' !== (string) $columns['value'] ) {
					$count = max( 1, (int) $columns['value'] );
					$css->set_selector( $list )->add_property( 'grid-template-columns', 'repeat(' . $count . ', minmax(0, 1fr))' );
				}
			}

			// Gap between items.
			$gap = $attrs['gap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $list )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Gap between icon and text.
			$icon_gap = $attrs['iconGap'][ $device ] ?? [];
			if ( ! empty( $icon_gap['value'] ) ) {
				$css->set_selector( $item )->add_property( 'gap', CSS_Helpers::with_unit( $icon_gap['value'], $icon_gap['unit'] ?? 'px' ) );
			}

			// Content alignment (horizontal placement of the icon + text row).
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( '' !== $align && isset( self::$justify[ $align ] ) ) {
				$css->set_selector( $item )->add_property( 'justify-content', self::$justify[ $align ] );
			}

			// Icon size (font-size scales the 1em glyph) + frame padding.
			$icon_size = $attrs['iconSize'][ $device ] ?? [];
			if ( ! empty( $icon_size['value'] ) ) {
				$css->set_selector( $icon )->add_property( 'font-size', CSS_Helpers::with_unit( $icon_size['value'], $icon_size['unit'] ?? 'px' ) );
			}
			$icon_pad = $attrs['iconPadding'][ $device ] ?? [];
			if ( ! empty( $icon_pad['value'] ) ) {
				$css->set_selector( $icon )->add_property( 'padding', CSS_Helpers::with_unit( $icon_pad['value'], $icon_pad['unit'] ?? 'px' ) );
			}

			// Text typography.
			CSS_Helpers::add_typography( $css, $text, $attrs['typography'][ $device ] ?? [] );

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

		// Icon frame shape (border-radius) — only when a framed / stacked view is
		// active, so an untouched or plain-glyph list stays square/unshaped.
		$icon_view = (string) ( $attrs['iconView'] ?? 'default' );
		if ( 'default' !== $icon_view ) {
			$shape = (string) ( $attrs['iconShape'] ?? 'square' );
			if ( isset( self::$shape_radius[ $shape ] ) ) {
				$css->set_selector( $icon )->add_property( 'border-radius', self::$shape_radius[ $shape ] );
			}
		}

		// Icon colours (light at the base, dark under the dark-mode branch) + hover.
		self::add_color( $css, $icon, 'color', $attrs['iconColor'] ?? '' );
		self::add_color( $css, $icon_hover, 'color', $attrs['iconColorHover'] ?? '' );
		self::add_color( $css, $icon, 'background', $attrs['iconBackground'] ?? '' );
		self::add_color( $css, $icon_hover, 'background', $attrs['iconBackgroundHover'] ?? '' );
		self::add_color( $css, $icon, 'border-color', $attrs['iconBorderColor'] ?? '' );

		// Text colours + hover.
		self::add_color( $css, $text, 'color', $attrs['textColor'] ?? '' );
		self::add_color( $css, $text_hover, 'color', $attrs['textColorHover'] ?? '' );

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

		// Box shadow on the wrapper (light).
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $wrap )->add_property( 'box-shadow', $shadow );
		}

		// Dark mode for the wrapper background / border / shadow.
		self::add_wrapper_dark( $css, $wrap, $attrs, $background );
	}

	/**
	 * Emit a colour property: light at the base, dark under the dark branch.
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

	/**
	 * Dark-mode declarations for the wrapper's background, border colour and box
	 * shadow (gathered under one dark-mode branch).
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
}
