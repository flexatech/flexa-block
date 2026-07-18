<?php
declare(strict_types=1);
/**
 * Grid block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one CSS Grid instance: track
 * definitions, gaps, auto-flow, item and track alignment, plus the shared
 * container decoration (spacing, width, background, border, shadow, advanced).
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
 * Grid CSS generator.
 */
class Grid_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a grid instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$is_boxed = 'boxed' === ( $attrs['containerType'] ?? 'boxed' );
		$outer    = '.flexa-grid-' . $id;
		$styled   = $is_boxed ? $outer . ' > .flexa-grid__inner' : $outer;

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Grid layout on the styled element.
			$layout = $attrs['layout'][ $device ] ?? [];
			if ( ! empty( $layout ) ) {
				$css->set_selector( $styled );

				$columns = self::grid_template( $layout['columns'] ?? [] );
				if ( '' !== $columns ) {
					$css->add_property( 'grid-template-columns', $columns );
				}
				$rows = self::grid_template( $layout['rows'] ?? [] );
				if ( '' !== $rows ) {
					$css->add_property( 'grid-template-rows', $rows );
				}
				$gap = $layout['gap'] ?? [];
				if ( is_array( $gap ) ) {
					$unit = $gap['unit'] ?? 'px';
					if ( isset( $gap['column'] ) && '' !== (string) $gap['column'] ) {
						$css->add_property( 'column-gap', CSS_Helpers::with_unit( $gap['column'], $unit ) );
					}
					if ( isset( $gap['row'] ) && '' !== (string) $gap['row'] ) {
						$css->add_property( 'row-gap', CSS_Helpers::with_unit( $gap['row'], $unit ) );
					}
				}
				if ( ! empty( $layout['autoFlow'] ) ) {
					$auto_flow = CSS_Helpers::sanitize_enum( $layout['autoFlow'], [ 'row', 'column', 'row dense', 'column dense' ] );
					if ( '' !== $auto_flow ) {
						$css->add_property( 'grid-auto-flow', $auto_flow );
					}
				}
				if ( ! empty( $layout['justifyItems'] ) ) {
					$justify_items = CSS_Helpers::sanitize_enum( $layout['justifyItems'], [ 'start', 'center', 'end', 'stretch' ] );
					if ( '' !== $justify_items ) {
						$css->add_property( 'justify-items', $justify_items );
					}
				}
				if ( ! empty( $layout['alignItems'] ) ) {
					$align_items = CSS_Helpers::sanitize_enum( $layout['alignItems'], [ 'start', 'center', 'end', 'stretch' ] );
					if ( '' !== $align_items ) {
						$css->add_property( 'align-items', $align_items );
					}
				}
				if ( ! empty( $layout['justifyContent'] ) ) {
					$justify_content = CSS_Helpers::sanitize_enum( $layout['justifyContent'], [ 'start', 'center', 'end', 'space-between', 'space-around' ] );
					if ( '' !== $justify_content ) {
						$css->add_property( 'justify-content', $justify_content );
					}
				}
				if ( ! empty( $layout['alignContent'] ) ) {
					$align_content = CSS_Helpers::sanitize_enum( $layout['alignContent'], [ 'start', 'center', 'end', 'space-between', 'space-around' ] );
					if ( '' !== $align_content ) {
						$css->add_property( 'align-content', $align_content );
					}
				}
			}

			// Spacing: padding on styled, margin on outer.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			if ( ! empty( $spacing['padding'] ) ) {
				$padding = CSS_Helpers::spacing_shorthand( $spacing['padding'] );
				if ( '' !== $padding ) {
					$css->set_selector( $styled )->add_property( 'padding', $padding );
				}
			}
			if ( ! empty( $spacing['margin'] ) ) {
				$margin = CSS_Helpers::spacing_shorthand( $spacing['margin'] );
				if ( '' !== $margin ) {
					$css->set_selector( $outer )->add_property( 'margin', $margin );
				}
			}

			// Width: max-width (boxed) or width (full-width).
			$width_attr = $is_boxed ? ( $attrs['widthBoxed'][ $device ] ?? [] ) : ( $attrs['widthFullWidth'][ $device ] ?? [] );
			if ( ! empty( $width_attr['value'] ) ) {
				$value = CSS_Helpers::with_unit( $width_attr['value'], $width_attr['unit'] ?? ( $is_boxed ? 'px' : '%' ) );
				$css->set_selector( $styled )->add_property( $is_boxed ? 'max-width' : 'width', $value );
			}

			// Min height.
			$min_height = $attrs['size'][ $device ]['minHeight'] ?? [];
			if ( ! empty( $min_height['value'] ) ) {
				$css->set_selector( $styled )->add_property( 'min-height', CSS_Helpers::with_unit( $min_height['value'], $min_height['unit'] ?? 'px' ) );
			}

			// Border.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $styled );
				CSS_Helpers::add_border( $css, $border );
			}

			// Advanced layout (z-index / overflow / position).
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $styled );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			// Grid item span (applies on the outer element when this grid is a
			// grid child; ignored by the browser otherwise).
			$span = $attrs['gridSpan'][ $device ] ?? [];
			if ( ! empty( $span['column'] ) ) {
				$css->set_selector( $outer )->add_property( 'grid-column', 'span ' . (int) $span['column'] );
			}
			if ( ! empty( $span['row'] ) ) {
				$css->set_selector( $outer )->add_property( 'grid-row', 'span ' . (int) $span['row'] );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Non-responsive: background + box shadow (base only).
		$background = $attrs['background'] ?? [];
		$lazy_bg    = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
		if ( ! empty( $background ) ) {
			$css->set_selector( $styled );
			CSS_Helpers::add_background( $css, $background, $lazy_bg );

			// Lazy: the image url only applies once the front-end script adds
			// `.flexa-bg-loaded` (see view.js). Until then no image is fetched.
			if ( $lazy_bg ) {
				$css->set_selector( $styled . '.flexa-bg-loaded' )
					->add_property( 'background-image', 'url(' . esc_url_raw( $background['image']['url'] ) . ')' );
			}
		}

		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $styled )->add_property( 'box-shadow', $shadow );
		}

		// Dark mode: background color/gradient, border color, shadow color.
		CSS_Helpers::add_dark_mode(
			$css,
			$styled,
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
	 * Expand a track definition to a grid-template value. A `custom` unit is a
	 * raw value (e.g. "1fr 2fr 1fr"); a numeric `fr` count becomes
	 * `repeat(n, 1fr)`. Mirror of the editor-side gridTemplate() preview builder.
	 *
	 * @param array $track Track { value, unit }.
	 * @return string grid-template value, or '' when empty.
	 */
	private static function grid_template( $track ) {
		if ( ! is_array( $track ) ) {
			return '';
		}
		$value = trim( (string) ( $track['value'] ?? '' ) );
		if ( '' === $value ) {
			return '';
		}
		if ( 'custom' === ( $track['unit'] ?? 'fr' ) ) {
			return $value;
		}
		$count = (int) $value;
		return $count > 0 ? sprintf( 'repeat(%d, 1fr)', $count ) : $value;
	}
}
