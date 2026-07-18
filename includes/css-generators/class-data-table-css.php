<?php
declare(strict_types=1);
/**
 * Data Table block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one data-table instance. Nothing
 * is emitted unless the user picked a value (colours/borders default to '' and
 * the toggles only unlock their colours), so an untouched table keeps the
 * theme's typography and the structural defaults from style.scss. Per-column
 * text alignment is emitted inline in render.php (data-driven), never here.
 * Every declaration targets the header / cell parts inside the block's own
 * `.flexa-data-table-<id>` wrapper.
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
 * Data Table CSS generator.
 */
class Data_Table_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a data-table instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap        = '.flexa-data-table-' . $id;
		$th          = $wrap . ' .flexa-data-table__th';
		$th_hover    = $wrap . ' .flexa-data-table__row:hover .flexa-data-table__th';
		$cell        = $wrap . ' .flexa-data-table__cell';
		$th_cell     = $th . ', ' . $cell;
		$striped_even = $wrap . ' .flexa-data-table__tbody .flexa-data-table__row:nth-child(even)';
		$hover       = $wrap . ' .flexa-data-table__row:hover';
		$cell_hover  = $wrap . ' .flexa-data-table__row:hover .flexa-data-table__cell';
		$first       = $wrap . ' .flexa-data-table__cell:first-child, ' . $wrap . ' .flexa-data-table__th:first-child';

		// --- Responsive: typography + cell padding + foundational box. ---
		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

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

			// Header + cell typography.
			CSS_Helpers::add_typography( $css, $th, $attrs['headerTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $cell, $attrs['cellTypography'][ $device ] ?? [] );

			// Cell padding (applies to header cells and body cells alike).
			$cell_pad = CSS_Helpers::spacing_shorthand( $attrs['cellPadding'][ $device ] ?? [] );
			if ( '' !== $cell_pad ) {
				$css->set_selector( $th_cell )->add_property( 'padding', $cell_pad );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// --- Max width (non-responsive): caps the wrapper so a wider table scrolls
		//     horizontally inside .flexa-data-table__scroll. ---
		$max_width = $attrs['maxWidth'] ?? [];
		if ( ! empty( $max_width['value'] ) ) {
			$css->set_selector( $wrap )->add_property( 'max-width', CSS_Helpers::with_unit( $max_width['value'], $max_width['unit'] ?? 'px' ) );
		}

		// --- Header colours (light at the base, dark under the dark-mode branch). ---
		$header_bg_light = CSS_Helpers::light( $attrs['headerBackground'] ?? '' );
		if ( '' !== $header_bg_light ) {
			$css->set_selector( $th )->add_property( 'background-color', $header_bg_light );
		}
		CSS_Helpers::dark_color( $css, $th, 'background-color', CSS_Helpers::dark( $attrs['headerBackground'] ?? '' ) );

		$header_light = CSS_Helpers::light( $attrs['headerColor'] ?? '' );
		if ( '' !== $header_light ) {
			$css->set_selector( $th )->add_property( 'color', $header_light );
		}
		CSS_Helpers::dark_color( $css, $th, 'color', CSS_Helpers::dark( $attrs['headerColor'] ?? '' ) );

		// Header text colour on hover (independent of the body row-hover toggle).
		$header_hover_light = CSS_Helpers::light( $attrs['headerColorHover'] ?? '' );
		if ( '' !== $header_hover_light ) {
			$css->set_selector( $th_hover )->add_property( 'color', $header_hover_light );
		}
		CSS_Helpers::dark_color( $css, $th_hover, 'color', CSS_Helpers::dark( $attrs['headerColorHover'] ?? '' ) );

		// --- Cell text colour. ---
		$cell_light = CSS_Helpers::light( $attrs['cellColor'] ?? '' );
		if ( '' !== $cell_light ) {
			$css->set_selector( $cell )->add_property( 'color', $cell_light );
		}
		CSS_Helpers::dark_color( $css, $cell, 'color', CSS_Helpers::dark( $attrs['cellColor'] ?? '' ) );

		// --- Striped rows (only when striping is on and a colour is set). ---
		if ( false !== ( $attrs['striped'] ?? true ) ) {
			$striped_light = CSS_Helpers::light( $attrs['stripedColor'] ?? '' );
			if ( '' !== $striped_light ) {
				$css->set_selector( $striped_even )->add_property( 'background-color', $striped_light );
			}
			CSS_Helpers::dark_color( $css, $striped_even, 'background-color', CSS_Helpers::dark( $attrs['stripedColor'] ?? '' ) );
		}

		// --- Row hover highlight: background + cell text colour (only when hover is
		//     on and a colour is set). ---
		if ( false !== ( $attrs['hoverHighlight'] ?? true ) ) {
			$hover_light = CSS_Helpers::light( $attrs['hoverColor'] ?? '' );
			if ( '' !== $hover_light ) {
				$css->set_selector( $hover )->add_property( 'background-color', $hover_light );
			}
			CSS_Helpers::dark_color( $css, $hover, 'background-color', CSS_Helpers::dark( $attrs['hoverColor'] ?? '' ) );

			$cell_hover_light = CSS_Helpers::light( $attrs['cellColorHover'] ?? '' );
			if ( '' !== $cell_hover_light ) {
				$css->set_selector( $cell_hover )->add_property( 'color', $cell_hover_light );
			}
			CSS_Helpers::dark_color( $css, $cell_hover, 'color', CSS_Helpers::dark( $attrs['cellColorHover'] ?? '' ) );
		}

		// --- Cell borders (only when the toggle is on and a colour or width is set). ---
		if ( false !== ( $attrs['showCellBorders'] ?? true ) ) {
			$border_light = CSS_Helpers::light( $attrs['cellBorderColor'] ?? '' );
			$border_dark  = CSS_Helpers::dark( $attrs['cellBorderColor'] ?? '' );
			$width_attr   = $attrs['cellBorderWidth'] ?? [];
			$width_value  = (string) ( $width_attr['value'] ?? '' );
			if ( '' !== $border_light || '' !== $border_dark || '' !== $width_value ) {
				$width = '' !== $width_value ? CSS_Helpers::with_unit( $width_value, $width_attr['unit'] ?? 'px' ) : '1px';
				$css->set_selector( $th_cell )
					->add_property( 'border-style', 'solid' )
					->add_property( 'border-width', $width );
				if ( '' !== $border_light ) {
					$css->add_property( 'border-color', $border_light );
				}
				CSS_Helpers::dark_color( $css, $th_cell, 'border-color', $border_dark );
			}
		}

		// --- First-column highlight (only when the toggle is on). ---
		if ( ! empty( $attrs['firstColumnHighlight'] ) ) {
			$fc_bg_light = CSS_Helpers::light( $attrs['firstColumnBackground'] ?? '' );
			if ( '' !== $fc_bg_light ) {
				$css->set_selector( $first )->add_property( 'background-color', $fc_bg_light );
			}
			CSS_Helpers::dark_color( $css, $first, 'background-color', CSS_Helpers::dark( $attrs['firstColumnBackground'] ?? '' ) );

			$fc_color_light = CSS_Helpers::light( $attrs['firstColumnColor'] ?? '' );
			if ( '' !== $fc_color_light ) {
				$css->set_selector( $first )->add_property( 'color', $fc_color_light );
			}
			CSS_Helpers::dark_color( $css, $first, 'color', CSS_Helpers::dark( $attrs['firstColumnColor'] ?? '' ) );
		}

		// --- Wrapper background + box shadow (base, light) and their dark branch. ---
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

		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $wrap )->add_property( 'box-shadow', $shadow );
		}

		self::add_wrapper_dark( $css, $wrap, $attrs, $background );
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
