<?php
declare(strict_types=1);
/**
 * Tabs block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one tabs instance. Nothing is
 * emitted unless the user picked a value, so an untouched tabs block keeps the
 * theme's typography and the neutral indicator fallbacks from style.scss.
 * Declarations target the nav / tab / icon / panel inside the block's own
 * `.flexa-tabs-<id>` wrapper.
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
 * Tabs CSS generator.
 */
class Tabs_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a tabs instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap       = '.flexa-tabs-' . $id;
		$nav        = $wrap . ' .flexa-tabs__nav';
		// Both the nav buttons and the mobile accordion headers carry
		// `.flexa-tabs__tab`, so one selector styles them identically.
		$tab        = $wrap . ' .flexa-tabs__tab';
		$tab_hover  = $wrap . ' .flexa-tabs__tab:hover';
		$tab_active = $wrap . ' .flexa-tabs__tab.is-active';
		$icon       = $wrap . ' .flexa-tabs__icon';
		$panel      = $wrap . ' .flexa-tabs__panel';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Max width on the wrapper.
			$max_width = $attrs['maxWidth'][ $device ] ?? [];
			if ( ! empty( $max_width['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'max-width', CSS_Helpers::with_unit( $max_width['value'], $max_width['unit'] ?? 'px' ) );
			}

			// Gap between tabs (nav) and gap between icon + label (tab).
			$tab_gap = $attrs['tabGap'][ $device ] ?? [];
			if ( ! empty( $tab_gap['value'] ) ) {
				$css->set_selector( $nav )->add_property( 'gap', CSS_Helpers::with_unit( $tab_gap['value'], $tab_gap['unit'] ?? 'px' ) );
			}
			$icon_gap = $attrs['iconGap'][ $device ] ?? [];
			if ( ! empty( $icon_gap['value'] ) ) {
				$css->set_selector( $tab )->add_property( 'gap', CSS_Helpers::with_unit( $icon_gap['value'], $icon_gap['unit'] ?? 'px' ) );
			}

			// Icon size.
			$icon_size = $attrs['iconSize'][ $device ] ?? [];
			if ( ! empty( $icon_size['value'] ) ) {
				$value = CSS_Helpers::with_unit( $icon_size['value'], $icon_size['unit'] ?? 'px' );
				$css->set_selector( $icon )->add_property( 'width', $value )->add_property( 'height', $value );
			}

			// Tab + content padding.
			$tab_pad = CSS_Helpers::spacing_shorthand( $attrs['tabPadding'][ $device ] ?? [] );
			if ( '' !== $tab_pad ) {
				$css->set_selector( $tab )->add_property( 'padding', $tab_pad );
			}
			$content_pad = CSS_Helpers::spacing_shorthand( $attrs['contentPadding'][ $device ] ?? [] );
			if ( '' !== $content_pad ) {
				$css->set_selector( $panel )->add_property( 'padding', $content_pad );
			}

			// Tab + content typography.
			CSS_Helpers::add_typography( $css, $tab, $attrs['tabTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $panel, $attrs['contentTypography'][ $device ] ?? [] );

			// Border outlines the content panel box.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $panel );
				CSS_Helpers::add_border( $css, $border );
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

			// Advanced layout (overflow / position / z-index) on the wrapper.
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Tab text colours (solid light/dark) for the normal / hover / active states.
		self::solid_color( $css, $tab, $attrs['tabColor'] ?? '' );
		self::solid_color( $css, $tab_hover, $attrs['tabHoverColor'] ?? '' );
		self::solid_color( $css, $tab_active, $attrs['tabActiveColor'] ?? '' );

		// Active indicator (underline colour / pill or boxed background) as a
		// custom property the style.scss consumes per tab style — light + dark.
		self::add_indicator( $css, $wrap, $attrs );

		// Content text colour (solid) + background fill (colour|gradient).
		self::solid_color( $css, $panel, $attrs['contentColor'] ?? '' );
		CSS_Helpers::add_bg_fill(
			$css,
			$panel,
			$attrs['contentBackgroundType'] ?? 'color',
			$attrs['contentBackground'] ?? '',
			$attrs['contentBackgroundGradient'] ?? ''
		);

		// Border dark colour for the content panel (light value came from add_border).
		CSS_Helpers::dark_color( $css, $panel, 'border-color', CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );

		// Wrapper background (base, light) — the whole block.
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

		// Box shadow on the content panel (light) + dark.
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $panel )->add_property( 'box-shadow', $shadow );
		}

		self::add_wrapper_dark( $css, $wrap, $background );
		self::add_panel_shadow_dark( $css, $panel, $attrs );
	}

	/**
	 * Emit a solid colour (light at the base, dark under the dark-mode branch) on
	 * a selector's `color` property.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param mixed       $pair     Colour pair.
	 */
	private static function solid_color( $css, $selector, $pair ) {
		$light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $pair ) );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( 'color', $light );
		}
		CSS_Helpers::dark_color( $css, $selector, 'color', CSS_Helpers::dark( $pair ) );
	}

	/**
	 * Emit the active-indicator value as the `--flexa-tabs-indicator` custom
	 * property on the wrapper (light + dark). The style.scss maps it to the
	 * underline colour, pill background or boxed background per tab style.
	 *
	 * @param CSS_Builder $css   Builder.
	 * @param string      $wrap  Wrapper selector.
	 * @param array       $attrs Attributes.
	 */
	private static function add_indicator( $css, $wrap, $attrs ) {
		$is_gradient = 'gradient' === ( $attrs['tabActiveIndicatorType'] ?? 'color' );
		if ( $is_gradient ) {
			$light = CSS_Helpers::sanitize_gradient( CSS_Helpers::light( $attrs['tabActiveIndicatorGradient'] ?? '' ) );
			$dark  = CSS_Helpers::sanitize_gradient( CSS_Helpers::dark( $attrs['tabActiveIndicatorGradient'] ?? '' ) );
		} else {
			$light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['tabActiveIndicator'] ?? '' ) );
			$dark  = CSS_Helpers::sanitize_color( CSS_Helpers::dark( $attrs['tabActiveIndicator'] ?? '' ) );
		}

		if ( '' !== $light ) {
			$css->set_selector( $wrap )->add_property( '--flexa-tabs-indicator', $light );
		}
		if ( '' !== $dark ) {
			CSS_Helpers::add_dark_mode(
				$css,
				$wrap,
				function ( $css ) use ( $dark ) {
					$css->add_property( '--flexa-tabs-indicator', $dark );
				}
			);
		}
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

	/**
	 * Dark-mode box shadow on the content panel, mirroring the light shadow with
	 * the dark shadow colour.
	 *
	 * @param CSS_Builder $css   Builder.
	 * @param string      $panel Panel selector.
	 * @param array       $attrs Attributes.
	 */
	private static function add_panel_shadow_dark( $css, $panel, $attrs ) {
		$shadow = $attrs['boxShadow'] ?? [];
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
			$panel,
			function ( $css ) use ( $value ) {
				$css->add_property( 'box-shadow', $value );
			}
		);
	}
}
