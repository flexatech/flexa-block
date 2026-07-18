<?php
declare(strict_types=1);
/**
 * Product Image block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one product-image instance.
 * Nothing is emitted unless the user picked a value. Foundation declarations
 * (alignment / spacing / border / advanced layout / background / box shadow)
 * target the wrapper `.flexa-product-image-<id>`; the thumbnails-per-view and gap
 * are exposed as CSS vars (`--flexa-thumb-pv` / `--flexa-thumb-gap`) that
 * style.scss uses to size the carousel; the featured-image height / scale /
 * radius (plus the thumbnail radius) target the inner images.
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
 * Product Image CSS generator.
 */
class Product_Image_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a product-image instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap      = '.flexa-product-image-' . $id;
		$main      = $wrap . ' .flexa-product-image__main';
		$thumbs    = $wrap . ' .flexa-product-image__thumbs';
		$main_img  = $wrap . ' .flexa-product-image__main img';
		$thumb_img = $wrap . ' .flexa-product-image__thumb img';

		// Number of thumbnails visible at once (carousel). Exposed as a CSS var so
		// the thumbnail width can be computed against it in style.scss. style.scss
		// already defaults to 4, so only emit when the user picked something else.
		$per_view = max( 1, (int) ( $attrs['thumbnailsPerView'] ?? 4 ) );
		if ( 4 !== $per_view ) {
			$css->set_selector( $wrap )->add_property( '--flexa-thumb-pv', (string) $per_view );
		}

		// Adaptive height keeps the natural aspect ratio; when off the user sets a
		// fixed height + object-fit scale on the featured image.
		$fixed = ! ( $attrs['adaptiveHeight'] ?? true );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Content alignment on the wrapper.
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( '' !== $align ) {
				$css->set_selector( $wrap )->add_property( 'text-align', $align );
			}

			// Thumbnail gap — exposed as a CSS var so the per-view width calc can
			// subtract the gaps, and used as the actual flex gap in style.scss.
			$gap = $attrs['thumbnailGap'][ $device ] ?? [];
			if ( isset( $gap['value'] ) && '' !== (string) $gap['value'] ) {
				$css->set_selector( $thumbs )->add_property( '--flexa-thumb-gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Featured image fixed height (only when adaptive height is off). Set on
			// both the container and the image so the box keeps its height when a
			// different-sized gallery image is swapped in (no layout jump).
			if ( $fixed ) {
				$height = $attrs['imageHeight'][ $device ] ?? [];
				if ( isset( $height['value'] ) && '' !== (string) $height['value'] ) {
					$dim = CSS_Helpers::with_unit( $height['value'], $height['unit'] ?? 'px' );
					$css->set_selector( $main )->add_property( 'height', $dim );
					$css->set_selector( $main_img )->add_property( 'height', $dim );
				}
			}

			// Corner radius on the featured image and thumbnails.
			$radius = $attrs['imageRadius'][ $device ] ?? [];
			if ( isset( $radius['value'] ) && '' !== (string) $radius['value'] ) {
				$radius_css = CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' );
				$css->set_selector( $main_img )->add_property( 'border-radius', $radius_css );
				$css->set_selector( $thumb_img )->add_property( 'border-radius', $radius_css );
			}

			// Wrapper spacing.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			if ( ! empty( $spacing['padding'] ) ) {
				$padding = CSS_Helpers::spacing_shorthand( $spacing['padding'] );
				if ( '' !== $padding ) {
					$css->set_selector( $wrap )->add_property( 'padding', $padding );
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

			// Wrapper advanced layout (overflow / position / z-index).
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Featured image scale (object-fit) — non-responsive, only when height is
		// fixed (otherwise the image keeps its natural aspect ratio).
		if ( $fixed ) {
			$scale = CSS_Helpers::sanitize_enum( $attrs['imageScale'] ?? '', [ 'none', 'cover', 'contain', 'fill', 'scale-down' ] );
			if ( '' !== $scale ) {
				$css->set_selector( $main_img )->add_property( 'object-fit', $scale );
			}
		}

		// Wrapper background (light) + lazy handling.
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

		// Wrapper box shadow (light).
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $wrap )->add_property( 'box-shadow', $shadow );
		}

		// Dark mode for the wrapper (background / border / shadow).
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
