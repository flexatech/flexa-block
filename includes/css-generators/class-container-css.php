<?php
declare(strict_types=1);
/**
 * Container block - server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one container instance.
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
 * Container CSS generator.
 */
class Container_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a container instance.
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
		$outer    = '.flexa-container-' . $id;
		$styled   = $is_boxed ? $outer . ' > .flexa-container__inner' : $outer;

		foreach ( self::$devices as $device ) {
			self::open_device( $css, $device );

			// Layout (flex) on the styled element.
			$layout = $attrs['layout'][ $device ] ?? [];
			if ( ! empty( $layout ) ) {
				$css->set_selector( $styled );
				if ( ! empty( $layout['display'] ) ) {
					$css->add_property( 'display', $layout['display'] );
				}
				if ( ! empty( $layout['direction'] ) ) {
					$css->add_property( 'flex-direction', $layout['direction'] );
				}
				if ( ! empty( $layout['justifyContent'] ) ) {
					$css->add_property( 'justify-content', $layout['justifyContent'] );
				}
				if ( ! empty( $layout['alignItems'] ) ) {
					$css->add_property( 'align-items', $layout['alignItems'] );
				}
				if ( ! empty( $layout['wrap'] ) ) {
					$css->add_property( 'flex-wrap', $layout['wrap'] );
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
				if ( ! empty( $advanced['overflow'] ) ) {
					$css->add_property( 'overflow', $advanced['overflow'] );
				}
				if ( ! empty( $advanced['position'] ) ) {
					$css->add_property( 'position', $advanced['position'] );
				}
				if ( isset( $advanced['zIndex'] ) && '' !== (string) $advanced['zIndex'] ) {
					$css->add_property( 'z-index', $advanced['zIndex'] );
				}
			}

			self::close_device( $css, $device );
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

		// Dark mode: background color, border color, shadow color.
		CSS_Helpers::add_dark_mode(
			$css,
			$styled,
			function ( $css ) use ( $attrs, $background ) {
				// Background dark color / gradient.
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

				// Border dark color (desktop only).
				$border_dark = CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' );
				if ( '' !== $border_dark ) {
					$css->add_property( 'border-color', $border_dark );
				}

				// Shadow dark color.
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
	 * Open a media query for non-desktop devices.
	 *
	 * @param CSS_Builder $css    Builder.
	 * @param string      $device Device.
	 */
	private static function open_device( $css, $device ) {
		if ( 'desktop' !== $device ) {
			$css->start_media_query( $device );
		}
	}

	/**
	 * Close a media query for non-desktop devices.
	 *
	 * @param CSS_Builder $css    Builder.
	 * @param string      $device Device.
	 */
	private static function close_device( $css, $device ) {
		if ( 'desktop' !== $device ) {
			$css->end_media_query();
		}
	}
}
