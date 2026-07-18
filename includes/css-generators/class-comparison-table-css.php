<?php
declare(strict_types=1);
/**
 * Comparison Table block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one comparison-table instance.
 * Nothing is emitted unless the user picked a value, so an untouched table keeps
 * the theme's typography and the structural defaults from style.scss. Every
 * declaration targets the table / header / cell / mark parts inside the block's
 * own `.flexa-comparison-table-<id>` wrapper.
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
 * Comparison Table CSS generator.
 */
class Comparison_Table_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a comparison-table instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap       = '.flexa-comparison-table-' . $id;
		$col        = $wrap . ' .flexa-comparison-table__col';
		$corner     = $wrap . ' .flexa-comparison-table__corner';
		$label      = $wrap . ' .flexa-comparison-table__label';
		$cell       = $wrap . ' .flexa-comparison-table__cell';
		$col_hi     = $wrap . ' .flexa-comparison-table__col.is-highlighted';
		$badge      = $wrap . ' .flexa-comparison-table__badge';
		$check      = $wrap . ' .flexa-comparison-table__mark--yes';
		$cross      = $wrap . ' .flexa-comparison-table__mark--no';
		$body_cells = $cell . ', ' . $label;
		$all_cells  = $col . ', ' . $cell . ', ' . $label;
		$head_cells = $col . ', ' . $corner;
		$zebra_even = $wrap . ' .flexa-comparison-table__row:nth-child(even) .flexa-comparison-table__cell, '
			. $wrap . ' .flexa-comparison-table__row:nth-child(even) .flexa-comparison-table__label';

		$is_boxed = 'boxed' === ( $attrs['containerType'] ?? 'full-width' );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Width: max-width when boxed, width when full-width.
			$width = ( $is_boxed ? ( $attrs['widthBoxed'][ $device ] ?? [] ) : ( $attrs['widthFullWidth'][ $device ] ?? [] ) );
			if ( ! empty( $width['value'] ) ) {
				$css->set_selector( $wrap )->add_property(
					$is_boxed ? 'max-width' : 'width',
					CSS_Helpers::with_unit( $width['value'], $width['unit'] ?? ( $is_boxed ? 'px' : '%' ) )
				);
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

			// Header + cell + badge typography.
			CSS_Helpers::add_typography( $css, $col, $attrs['headerTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $body_cells, $attrs['cellTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $badge, $attrs['badgeTypography'][ $device ] ?? [] );

			// Cell padding (applies to header cells and body cells alike).
			$cell_pad = CSS_Helpers::spacing_shorthand( $attrs['cellPadding'][ $device ] ?? [] );
			if ( '' !== $cell_pad ) {
				$css->set_selector( $all_cells )->add_property( 'padding', $cell_pad );
			}

			// Cell alignment.
			$align = CSS_Helpers::sanitize_enum( (string) ( $attrs['cellAlign'][ $device ] ?? '' ), [ 'left', 'center', 'right' ] );
			if ( '' !== $align ) {
				$css->set_selector( $body_cells )->add_property( 'text-align', $align );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// --- Colours (light at the base, dark under the dark-mode branch). ---
		$header_light = CSS_Helpers::light( $attrs['headerColor'] ?? '' );
		if ( '' !== $header_light ) {
			$css->set_selector( $col )->add_property( 'color', $header_light );
		}
		CSS_Helpers::dark_color( $css, $col, 'color', CSS_Helpers::dark( $attrs['headerColor'] ?? '' ) );

		$header_bg_light = CSS_Helpers::light( $attrs['headerBackground'] ?? '' );
		if ( '' !== $header_bg_light ) {
			$css->set_selector( $head_cells )->add_property( 'background-color', $header_bg_light );
		}
		CSS_Helpers::dark_color( $css, $head_cells, 'background-color', CSS_Helpers::dark( $attrs['headerBackground'] ?? '' ) );

		$cell_light = CSS_Helpers::light( $attrs['cellColor'] ?? '' );
		if ( '' !== $cell_light ) {
			$css->set_selector( $body_cells )->add_property( 'color', $cell_light );
		}
		CSS_Helpers::dark_color( $css, $body_cells, 'color', CSS_Helpers::dark( $attrs['cellColor'] ?? '' ) );

		// Spotlight accent — paints the highlighted column header.
		$hi_light = CSS_Helpers::light( $attrs['highlightColor'] ?? '' );
		if ( '' !== $hi_light ) {
			$css->set_selector( $col_hi )->add_property( 'background-color', $hi_light );
		}
		$hi_dark = CSS_Helpers::dark( $attrs['highlightColor'] ?? '' );
		CSS_Helpers::dark_color( $css, $col_hi, 'background-color', $hi_dark );

		// Spotlight accent bar — the inset top bar on the highlighted column header.
		// Emitted only when the user set a colour or thickness; otherwise the
		// style.scss default (#06b6d4, 3px) stands. Each unset half falls back to
		// that default so setting just one still produces a complete bar.
		$bar_light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['spotlightBarColor'] ?? '' ) );
		$bar_dark  = CSS_Helpers::sanitize_color( CSS_Helpers::dark( $attrs['spotlightBarColor'] ?? '' ) );
		$bar_wattr = $attrs['spotlightBarWidth'] ?? [];
		$bar_wval  = (string) ( $bar_wattr['value'] ?? '' );
		if ( '' !== $bar_light || '' !== $bar_dark || '' !== $bar_wval ) {
			$bar_width = '' !== $bar_wval ? CSS_Helpers::with_unit( $bar_wval, $bar_wattr['unit'] ?? 'px' ) : '3px';
			$css->set_selector( $col_hi )->add_property( 'box-shadow', 'inset 0 ' . $bar_width . ' 0 0 ' . ( '' !== $bar_light ? $bar_light : '#06b6d4' ) );
			if ( '' !== $bar_dark ) {
				CSS_Helpers::dark_color( $css, $col_hi, 'box-shadow', 'inset 0 ' . $bar_width . ' 0 0 ' . $bar_dark );
			}
		}

		// Badge background — its own colour wins; otherwise it inherits the accent.
		$badge_bg_light = CSS_Helpers::light( $attrs['badgeBackground'] ?? '' );
		$badge_bg_dark  = CSS_Helpers::dark( $attrs['badgeBackground'] ?? '' );
		$badge_bg_light = '' !== $badge_bg_light ? $badge_bg_light : $hi_light;
		$badge_bg_dark  = '' !== $badge_bg_dark ? $badge_bg_dark : $hi_dark;
		if ( '' !== $badge_bg_light ) {
			$css->set_selector( $badge )->add_property( 'background-color', $badge_bg_light );
		}
		CSS_Helpers::dark_color( $css, $badge, 'background-color', $badge_bg_dark );

		// Badge text colour.
		$badge_text_light = CSS_Helpers::light( $attrs['badgeColor'] ?? '' );
		if ( '' !== $badge_text_light ) {
			$css->set_selector( $badge )->add_property( 'color', $badge_text_light );
		}
		CSS_Helpers::dark_color( $css, $badge, 'color', CSS_Helpers::dark( $attrs['badgeColor'] ?? '' ) );

		// Badge corner radius + padding.
		$badge_radius = $attrs['badgeRadius'] ?? [];
		if ( ! empty( $badge_radius['value'] ) ) {
			$css->set_selector( $badge )->add_property( 'border-radius', CSS_Helpers::with_unit( $badge_radius['value'], $badge_radius['unit'] ?? 'px' ) );
		}
		$badge_padding = CSS_Helpers::spacing_shorthand( $attrs['badgePadding'] ?? [] );
		if ( '' !== $badge_padding ) {
			$css->set_selector( $badge )->add_property( 'padding', $badge_padding );
		}

		// Check + cross mark colours.
		$check_light = CSS_Helpers::light( $attrs['checkColor'] ?? '' );
		if ( '' !== $check_light ) {
			$css->set_selector( $check )->add_property( 'color', $check_light );
		}
		CSS_Helpers::dark_color( $css, $check, 'color', CSS_Helpers::dark( $attrs['checkColor'] ?? '' ) );

		$cross_light = CSS_Helpers::light( $attrs['crossColor'] ?? '' );
		if ( '' !== $cross_light ) {
			$css->set_selector( $cross )->add_property( 'color', $cross_light );
		}
		CSS_Helpers::dark_color( $css, $cross, 'color', CSS_Helpers::dark( $attrs['crossColor'] ?? '' ) );

		// Zebra stripe colour (only when striping is on and a colour is set).
		if ( false !== ( $attrs['zebra'] ?? true ) ) {
			$zebra_light = CSS_Helpers::light( $attrs['zebraColor'] ?? '' );
			if ( '' !== $zebra_light ) {
				$css->set_selector( $zebra_even )->add_property( 'background-color', $zebra_light );
			}
			CSS_Helpers::dark_color( $css, $zebra_even, 'background-color', CSS_Helpers::dark( $attrs['zebraColor'] ?? '' ) );
		}

		// Dividers (row + column) — colour/thickness override the style.scss hairline.
		self::add_dividers( $css, $attrs, $all_cells );

		// Column CTA button (block-level, base + hover + dark).
		self::add_button( $css, $attrs, $wrap . ' .flexa-comparison-table__cta' );

		// Wrapper background + box shadow (base, light) and their dark branch.
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
	 * Emit the row + column divider lines. Each is drawn only when its toggle is on
	 * AND the user supplied a divider colour or thickness (otherwise the hairline
	 * from style.scss stands). The width defaults to 1px when only a colour is set.
	 *
	 * @param CSS_Builder $css       Builder.
	 * @param array       $attrs     Attributes.
	 * @param string      $all_cells Combined header + body cell selector.
	 */
	private static function add_dividers( $css, $attrs, $all_cells ) {
		$divider     = $attrs['dividerColor'] ?? '';
		$light       = CSS_Helpers::light( $divider );
		$dark        = CSS_Helpers::dark( $divider );
		$width_attr  = $attrs['dividerWidth'] ?? [];
		$width_value = (string) ( $width_attr['value'] ?? '' );
		$has_value   = '' !== $light || '' !== $dark || '' !== $width_value;
		if ( ! $has_value ) {
			return;
		}
		$width = '' !== $width_value ? CSS_Helpers::with_unit( $width_value, $width_attr['unit'] ?? 'px' ) : '1px';

		if ( false !== ( $attrs['showRowDivider'] ?? true ) ) {
			$css->set_selector( $all_cells )
				->add_property( 'border-bottom-style', 'solid' )
				->add_property( 'border-bottom-width', $width );
			if ( '' !== $light ) {
				$css->add_property( 'border-bottom-color', $light );
			}
			CSS_Helpers::dark_color( $css, $all_cells, 'border-bottom-color', $dark );
		}

		if ( ! empty( $attrs['showColumnDivider'] ) ) {
			$css->set_selector( $all_cells )
				->add_property( 'border-right-style', 'solid' )
				->add_property( 'border-right-width', $width );
			if ( '' !== $light ) {
				$css->add_property( 'border-right-color', $light );
			}
			CSS_Helpers::dark_color( $css, $all_cells, 'border-right-color', $dark );
		}
	}

	/**
	 * Emit the column CTA button styling (base + :hover + dark). One appearance
	 * covers every column's CTA. Nothing is emitted unless the user set a value, so
	 * an untouched button keeps its `wp-element-button` theme styling.
	 *
	 * @param CSS_Builder $css  Builder.
	 * @param array       $attrs Attributes.
	 * @param string      $cta  CTA button selector.
	 */
	private static function add_button( $css, $attrs, $cta ) {
		$hover = $cta . ':hover';

		// Text colour (base + hover).
		$text_light = CSS_Helpers::light( $attrs['buttonTextColor'] ?? '' );
		if ( '' !== $text_light ) {
			$css->set_selector( $cta )->add_property( 'color', $text_light );
		}
		CSS_Helpers::dark_color( $css, $cta, 'color', CSS_Helpers::dark( $attrs['buttonTextColor'] ?? '' ) );

		$text_hover_light = CSS_Helpers::light( $attrs['buttonTextColorHover'] ?? '' );
		if ( '' !== $text_hover_light ) {
			$css->set_selector( $hover )->add_property( 'color', $text_hover_light );
		}
		CSS_Helpers::dark_color( $css, $hover, 'color', CSS_Helpers::dark( $attrs['buttonTextColorHover'] ?? '' ) );

		// Background — a solid colour or a gradient (base + hover), each light + dark.
		// add_bg_fill emits `background` for a colour or `background-image` for a
		// gradient, at the base, plus the dark branch — one call covers both modes.
		CSS_Helpers::add_bg_fill(
			$css,
			$cta,
			$attrs['buttonBackgroundType'] ?? 'color',
			$attrs['buttonBackground'] ?? '',
			$attrs['buttonBackgroundGradient'] ?? ''
		);
		CSS_Helpers::add_bg_fill(
			$css,
			$hover,
			$attrs['buttonBackgroundHoverType'] ?? 'color',
			$attrs['buttonBackgroundHover'] ?? '',
			$attrs['buttonBackgroundHoverGradient'] ?? ''
		);

		// Corner radius.
		$radius = $attrs['buttonRadius'] ?? [];
		if ( ! empty( $radius['value'] ) ) {
			$css->set_selector( $cta )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
		}

		// Padding.
		$padding = CSS_Helpers::spacing_shorthand( $attrs['buttonPadding'] ?? [] );
		if ( '' !== $padding ) {
			$css->set_selector( $cta )->add_property( 'padding', $padding );
		}

		// Full-width mode.
		if ( 'full' === ( $attrs['buttonWidth'] ?? 'auto' ) ) {
			$css->set_selector( $cta )
				->add_property( 'display', 'block' )
				->add_property( 'width', '100%' )
				->add_property( 'text-align', 'center' );
		}
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
