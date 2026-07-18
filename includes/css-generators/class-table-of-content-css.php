<?php
declare(strict_types=1);
/**
 * Table of Contents block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one TOC instance. Nothing is
 * emitted unless the user picked a value, so an untouched block keeps the
 * theme's look. Declarations target the title / list / link / marker inside the
 * block's own `.flexa-table-of-content-<id>` wrapper. The per-device item gap
 * and sub-level indent are exposed as CSS variables the stylesheet consumes.
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
 * Table of Contents CSS generator.
 */
class Table_Of_Content_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a TOC instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap   = '.flexa-table-of-content-' . $id;
		$title  = $wrap . ' .flexa-toc__title';
		$list   = $wrap . ' .flexa-toc__list';
		$link   = $wrap . ' .flexa-toc__link';
		$hover  = $wrap . ' .flexa-toc__link:hover';
		$marker = $wrap . ' .flexa-toc__item::marker';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Title + link typography.
			CSS_Helpers::add_typography( $css, $title, $attrs['titleTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $link, $attrs['linkTypography'][ $device ] ?? [] );

			// Item gap + sub-level indent → CSS variables the stylesheet reads.
			$gap = $attrs['itemGap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $wrap )->add_property( '--flexa-toc-gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}
			$indent = $attrs['indent'][ $device ] ?? [];
			if ( ! empty( $indent['value'] ) ) {
				$css->set_selector( $wrap )->add_property( '--flexa-toc-indent', CSS_Helpers::with_unit( $indent['value'], $indent['unit'] ?? 'px' ) );
			}

			// Max width on the wrapper.
			$max_width = $attrs['maxWidth'][ $device ] ?? [];
			if ( ! empty( $max_width['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'max-width', CSS_Helpers::with_unit( $max_width['value'], $max_width['unit'] ?? 'px' ) );
			}

			// List max height → the body scrolls when the list overflows (the title
			// stays fixed above it).
			$list_max_height = $attrs['listMaxHeight'][ $device ] ?? [];
			if ( ! empty( $list_max_height['value'] ) ) {
				$css->set_selector( $wrap . ' .flexa-toc__body' )
					->add_property( 'max-height', CSS_Helpers::with_unit( $list_max_height['value'], $list_max_height['unit'] ?? 'px' ) )
					->add_property( 'overflow-y', 'auto' );
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

			// Border on the wrapper.
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

		// Colours (light base + dark branch): title, link, link hover, marker.
		$title_light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['titleColor'] ?? '' ) );
		if ( '' !== $title_light ) {
			$css->set_selector( $title )->add_property( 'color', $title_light );
		}
		CSS_Helpers::dark_color( $css, $title, 'color', CSS_Helpers::dark( $attrs['titleColor'] ?? '' ) );

		$link_light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['linkColor'] ?? '' ) );
		if ( '' !== $link_light ) {
			$css->set_selector( $link )->add_property( 'color', $link_light );
		}
		CSS_Helpers::dark_color( $css, $link, 'color', CSS_Helpers::dark( $attrs['linkColor'] ?? '' ) );

		$hover_light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['linkHoverColor'] ?? '' ) );
		if ( '' !== $hover_light ) {
			$css->set_selector( $hover )->add_property( 'color', $hover_light );
		}
		CSS_Helpers::dark_color( $css, $hover, 'color', CSS_Helpers::dark( $attrs['linkHoverColor'] ?? '' ) );

		$marker_light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['markerColor'] ?? '' ) );
		if ( '' !== $marker_light ) {
			$css->set_selector( $marker )->add_property( 'color', $marker_light );
		}
		CSS_Helpers::dark_color( $css, $marker, 'color', CSS_Helpers::dark( $attrs['markerColor'] ?? '' ) );

		// Wrapper background (base, light) + lazy image url gated behind the loaded class.
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

		// Box shadow on the wrapper (base, light).
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $wrap )->add_property( 'box-shadow', $shadow );
		}

		// Dark mode on the wrapper: background, border colour, shadow colour.
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

				$shadow_cfg = $attrs['boxShadow'] ?? [];
				if ( ! empty( $shadow_cfg['enabled'] ) ) {
					$shadow_dark = CSS_Helpers::dark( $shadow_cfg['color'] ?? '' );
					if ( '' !== $shadow_dark ) {
						$value = CSS_Helpers::box_shadow( $shadow_cfg, $shadow_dark );
						if ( '' !== $value ) {
							$css->add_property( 'box-shadow', $value );
						}
					}
				}
			}
		);
	}
}
