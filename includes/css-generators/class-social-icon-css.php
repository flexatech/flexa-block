<?php
declare(strict_types=1);
/**
 * Social Icons block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one Social Icons instance.
 * Nothing is emitted unless the user picked a value, so an untouched block
 * keeps the official brand artwork and the theme's spacing. Colour tints only
 * target `__item--custom` icons — official icons carry their colours in the
 * artwork itself.
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
 * Social Icons CSS generator.
 */
class Social_Icon_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Platform keys the block can render (mirror of render.php's catalog).
	 * Items with an unknown platform are skipped by render.php, so they must
	 * also be skipped here to keep :nth-child positions in sync.
	 *
	 * @var array
	 */
	private static $platforms = [ 'facebook', 'x', 'instagram', 'linkedin', 'pinterest' ];

	/**
	 * Alignment keyword → flex justify-content.
	 *
	 * @var array
	 */
	private static $justify_map = [
		'left'   => 'flex-start',
		'center' => 'center',
		'right'  => 'flex-end',
	];

	/**
	 * Generate CSS for a Social Icons instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap = '.flexa-social-icon-' . $id;
		$list = $wrap . ' .flexa-social-icon__list';
		$icon = $wrap . ' .flexa-social-icon__icon';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Gap between icons.
			$gap = $attrs['gap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $list )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Row alignment.
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( isset( self::$justify_map[ $align ] ) ) {
				$css->set_selector( $list )->add_property( 'justify-content', self::$justify_map[ $align ] );
			}

			// Icon size (square).
			$size = $attrs['iconSize'][ $device ] ?? [];
			if ( ! empty( $size['value'] ) ) {
				$value = CSS_Helpers::with_unit( $size['value'], $size['unit'] ?? 'px' );
				$css->set_selector( $icon )->add_property( 'width', $value )->add_property( 'height', $value );
			}

			// Wrapper spacing: padding + margin.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			$padding = CSS_Helpers::spacing_shorthand( $spacing['padding'] ?? [] );
			if ( '' !== $padding ) {
				$css->set_selector( $wrap )->add_property( 'padding', $padding );
			}
			$margin = CSS_Helpers::spacing_shorthand( $spacing['margin'] ?? [] );
			if ( '' !== $margin ) {
				$css->set_selector( $wrap )->add_property( 'margin', $margin );
			}

			// Wrapper border.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_border( $css, $border );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Dark colour for the wrapper border (light value + geometry above).
		CSS_Helpers::dark_color( $css, $wrap, 'border-color', CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );

		// Wrapper background (colour or gradient), light + dark.
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

		// Wrapper box shadow, light + dark.
		$shadow_cfg = $attrs['boxShadow'] ?? [];
		$shadow     = CSS_Helpers::box_shadow( $shadow_cfg );
		if ( '' !== $shadow ) {
			$css->set_selector( $wrap )->add_property( 'box-shadow', $shadow );
			$shadow_dark = CSS_Helpers::dark( $shadow_cfg['color'] ?? '' );
			if ( '' !== $shadow_dark ) {
				CSS_Helpers::dark_color( $css, $wrap, 'box-shadow', CSS_Helpers::box_shadow( $shadow_cfg, $shadow_dark ) );
			}
		}

		// Per-item tint for each custom-mode icon (targets its rendered position).
		self::add_item_tints( $css, $wrap, $attrs['items'] ?? [] );
	}

	/**
	 * Emit the per-item tint for every custom-mode item, targeting its rendered
	 * position with :nth-child (unknown platforms are skipped, mirroring
	 * render.php).
	 *
	 * @param CSS_Builder $css   Builder.
	 * @param string      $wrap  Wrapper selector.
	 * @param array       $items Items attribute.
	 */
	private static function add_item_tints( $css, $wrap, $items ) {
		if ( ! is_array( $items ) ) {
			return;
		}

		$position = 0;
		foreach ( $items as $item ) {
			if ( ! self::is_renderable( $item ) ) {
				continue;
			}
			$position++;

			// Only tinted items (a brand mark in custom colour mode, or a picked
			// library icon) carry the --custom class the per-item selector targets.
			$is_custom_icon = 'custom' === ( $item['platform'] ?? '' );
			$tinted         = $is_custom_icon || 'custom' === ( $item['colorMode'] ?? 'official' );
			if ( ! $tinted ) {
				continue;
			}

			$selector = $wrap . ' .flexa-social-icon__item--custom:nth-child(' . $position . ')';
			$light    = CSS_Helpers::light( $item['color'] ?? '' );
			if ( '' !== $light ) {
				$css->set_selector( $selector )->add_property( 'color', $light );
			}
			CSS_Helpers::dark_color( $css, $selector, 'color', CSS_Helpers::dark( $item['color'] ?? '' ) );
		}
	}

	/**
	 * Whether an item is rendered by render.php — a known brand platform, or a
	 * custom item that has actually picked an icon. Both files must agree so the
	 * :nth-child positions used for per-item tints line up with the DOM.
	 *
	 * @param mixed $item Item attribute.
	 * @return bool
	 */
	private static function is_renderable( $item ) {
		if ( ! is_array( $item ) ) {
			return false;
		}
		$platform = $item['platform'] ?? '';
		if ( in_array( $platform, self::$platforms, true ) ) {
			return true;
		}
		if ( 'custom' === $platform ) {
			$icon = is_array( $item['icon'] ?? null ) ? $item['icon'] : [];
			return '' !== (string) ( $icon['markup'] ?? '' ) || '' !== (string) ( $icon['url'] ?? '' );
		}
		return false;
	}
}
