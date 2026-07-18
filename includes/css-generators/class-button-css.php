<?php
declare(strict_types=1);
/**
 * Button block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one button instance. Nothing is
 * emitted unless the user picked a value, so an untouched button keeps the
 * theme's `wp-element-button` styling. Declarations target the anchor via
 * `.flexa-button-<id> .wp-element-button`, which outranks the theme's single
 * `.wp-element-button` selector on specificity.
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
 * Button CSS generator.
 */
class Button_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Size preset padding + font-size, expressed in shared design tokens.
	 * The default 'md' intentionally has no entry: it defers to the theme.
	 *
	 * @var array<string, array{padding:string, font:string}>
	 */
	private static $size_presets = [
		'sm' => [
			'padding' => 'var(--flexa-space-sm, 8px) var(--flexa-space-md, 16px)',
			'font'    => 'var(--flexa-font-size-sm, 0.875rem)',
		],
		'lg' => [
			'padding' => 'var(--flexa-space-md, 16px) var(--flexa-space-xl, 40px)',
			'font'    => 'var(--flexa-font-size-lg, 1.25rem)',
		],
	];

	/**
	 * Hover transform presets → CSS `transform` value.
	 * 'none' has no entry: it emits nothing.
	 *
	 * @var array<string, string>
	 */
	private static $transform_presets = [
		'grow'   => 'scale(1.05)',
		'shrink' => 'scale(0.95)',
		'lift'   => 'translateY(-4px)',
		'push'   => 'translateY(2px)',
	];

	/**
	 * Generate CSS for a button instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$outer = '.flexa-button-' . $id;
		$link  = $outer . ' .wp-element-button';
		$hover = $link . ':hover';

		// Size preset first so explicit typography/spacing below can override it.
		$size = $attrs['sizePreset'] ?? 'md';
		if ( isset( self::$size_presets[ $size ] ) ) {
			$css->set_selector( $link )
				->add_property( 'padding', self::$size_presets[ $size ]['padding'] )
				->add_property( 'font-size', self::$size_presets[ $size ]['font'] );
		}

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			CSS_Helpers::add_typography( $css, $link, $attrs['typography'][ $device ] ?? [] );

			// Spacing: padding on the link, margin on the wrapper.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			if ( ! empty( $spacing['padding'] ) ) {
				$padding = CSS_Helpers::spacing_shorthand( $spacing['padding'] );
				if ( '' !== $padding ) {
					$css->set_selector( $link )->add_property( 'padding', $padding );
				}
			}
			if ( ! empty( $spacing['margin'] ) ) {
				$margin = CSS_Helpers::spacing_shorthand( $spacing['margin'] );
				if ( '' !== $margin ) {
					$css->set_selector( $outer )->add_property( 'margin', $margin );
				}
			}

			// Border on the link.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $link );
				CSS_Helpers::add_border( $css, $border );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Base (non-responsive) colour, background and shadow on the link.
		$text_light = CSS_Helpers::light( $attrs['textColor'] ?? '' );
		if ( '' !== $text_light ) {
			$css->set_selector( $link )->add_property( 'color', $text_light );
		}

		$background = $attrs['background'] ?? [];
		if ( ! empty( $background ) ) {
			$css->set_selector( $link );
			CSS_Helpers::add_background( $css, $background );
		}

		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $link )->add_property( 'box-shadow', $shadow );
		}

		// Hover colours (light at base).
		$hover_attr = $attrs['hover'] ?? [];
		self::add_hover( $css, $hover, $hover_attr, 'light' );

		// Hover transform (motion preset) — same in light & dark, so emit once.
		$transform = is_array( $hover_attr ) ? (string) ( $hover_attr['transform'] ?? 'none' ) : 'none';
		if ( isset( self::$transform_presets[ $transform ] ) ) {
			$css->set_selector( $link )->add_property( 'transition', 'transform 0.2s ease' );
			$css->set_selector( $hover )->add_property( 'transform', self::$transform_presets[ $transform ] );
		}

		// Dark mode for the link (text / background / border / shadow).
		CSS_Helpers::add_dark_mode(
			$css,
			$link,
			function ( $css ) use ( $attrs, $background ) {
				$text_dark = CSS_Helpers::dark( $attrs['textColor'] ?? '' );
				if ( '' !== $text_dark ) {
					$css->add_property( 'color', $text_dark );
				}

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

		// Dark mode for the hover state.
		CSS_Helpers::add_dark_mode(
			$css,
			$hover,
			function ( $css ) use ( $hover_attr ) {
				self::add_hover_declarations( $css, $hover_attr, 'dark' );
			}
		);
	}

	/**
	 * Emit hover colour declarations onto a pre-set hover selector.
	 *
	 * @param CSS_Builder $css        Builder.
	 * @param string      $hover      Hover selector.
	 * @param array       $hover_attr Hover attribute object.
	 * @param string      $mode       'light' or 'dark'.
	 */
	private static function add_hover( $css, $hover, $hover_attr, $mode ) {
		if ( ! is_array( $hover_attr ) || empty( $hover_attr ) ) {
			return;
		}
		$css->set_selector( $hover );
		self::add_hover_declarations( $css, $hover_attr, $mode );
	}

	/**
	 * Add hover text / background / border colour declarations (selector pre-set).
	 *
	 * @param CSS_Builder $css        Builder (selector already set).
	 * @param array       $hover_attr Hover attribute object.
	 * @param string      $mode       'light' or 'dark'.
	 */
	private static function add_hover_declarations( $css, $hover_attr, $mode ) {
		if ( ! is_array( $hover_attr ) ) {
			return;
		}
		$is_dark = 'dark' === $mode;

		$text = $is_dark ? CSS_Helpers::dark( $hover_attr['text'] ?? '' ) : CSS_Helpers::light( $hover_attr['text'] ?? '' );
		if ( '' !== $text ) {
			$css->add_property( 'color', $text );
		}
		$bg = $is_dark ? CSS_Helpers::dark( $hover_attr['background'] ?? '' ) : CSS_Helpers::light( $hover_attr['background'] ?? '' );
		if ( '' !== $bg ) {
			$css->add_property( 'background-color', $bg );
		}
		$border = $is_dark ? CSS_Helpers::dark( $hover_attr['border'] ?? '' ) : CSS_Helpers::light( $hover_attr['border'] ?? '' );
		if ( '' !== $border ) {
			$css->add_property( 'border-color', $border );
		}
	}
}
