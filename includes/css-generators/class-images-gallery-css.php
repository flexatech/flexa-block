<?php
declare(strict_types=1);
/**
 * Images Gallery block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one gallery instance. Nothing is
 * emitted unless the user picked a value. Declarations target the items / item /
 * media / image / overlay / caption inside the block's own
 * `.flexa-images-gallery-<id>` wrapper.
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
 * Images Gallery CSS generator.
 */
class Images_Gallery_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a gallery instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap    = '.flexa-images-gallery-' . $id;
		$items   = $wrap . ' .flexa-images-gallery__items';
		$item    = $wrap . ' .flexa-images-gallery__item';
		$media   = $wrap . ' .flexa-images-gallery__media';
		$image   = $wrap . ' .flexa-images-gallery__image';
		$overlay = $wrap . ' .flexa-images-gallery__overlay';
		$caption = $wrap . ' .flexa-images-gallery__caption';

		$layout = CSS_Helpers::sanitize_enum( $attrs['galleryLayout'] ?? 'grid', [ 'grid', 'masonry', 'tiled' ], 'grid' );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			$gap     = $attrs['gap'][ $device ] ?? [];
			$gap_css = ( isset( $gap['value'] ) && '' !== (string) $gap['value'] ) ? CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) : '';

			$columns = $attrs['columns'][ $device ] ?? [];
			$count   = ( isset( $columns['value'] ) && '' !== (string) $columns['value'] ) ? max( 1, (int) $columns['value'] ) : 0;

			if ( 'masonry' === $layout ) {
				if ( $count > 0 ) {
					$css->set_selector( $items )->add_property( 'column-count', (string) $count );
				}
				if ( '' !== $gap_css ) {
					$css->set_selector( $items )->add_property( 'column-gap', $gap_css );
					$css->set_selector( $item )->add_property( 'margin-bottom', $gap_css );
				}
			} elseif ( 'tiled' === $layout ) {
				if ( '' !== $gap_css ) {
					$css->set_selector( $items )->add_property( 'gap', $gap_css );
				}
				$height = $attrs['tiledHeight'][ $device ] ?? [];
				if ( isset( $height['value'] ) && '' !== (string) $height['value'] ) {
					$css->set_selector( $item )->add_property( 'height', CSS_Helpers::with_unit( $height['value'], $height['unit'] ?? 'px' ) );
				}
			} else {
				if ( $count > 0 ) {
					$css->set_selector( $items )->add_property( 'grid-template-columns', 'repeat(' . $count . ', minmax(0, 1fr))' );
				}
				if ( '' !== $gap_css ) {
					$css->set_selector( $items )->add_property( 'gap', $gap_css );
				}
			}

			// Per-image corner radius on the media box (clips the image).
			$radius = $attrs['imageRadius'][ $device ] ?? [];
			if ( isset( $radius['value'] ) && '' !== (string) $radius['value'] ) {
				$css->set_selector( $media )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
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

			// Caption typography.
			CSS_Helpers::add_typography( $css, $caption, $attrs['captionTypography'][ $device ] ?? [] );

			CSS_Helpers::close_device( $css, $device );
		}

		// Grid aspect-ratio lock on the media box (non-responsive).
		$aspect = $attrs['aspectRatio'] ?? [];
		if ( 'grid' === $layout && ! empty( $aspect['enabled'] ) && ! empty( $aspect['ratio'] ) ) {
			$css->set_selector( $media )->add_property( 'aspect-ratio', $aspect['ratio'] );
			$css->set_selector( $image )->add_property( 'height', '100%' );
		}

		// Per-image box shadow (light).
		$shadow = CSS_Helpers::box_shadow( $attrs['imageShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $media )->add_property( 'box-shadow', $shadow );
		}

		// Overlay colour / gradient + opacity (light), plus the hover opacity.
		self::add_overlay( $css, $overlay, $item, $attrs['overlay'] ?? [] );

		// Caption text alignment (only meaningful when captions are shown).
		$caption_attr = $attrs['caption'] ?? [];
		if ( ! empty( $caption_attr['show'] ) ) {
			$cap_align = (string) ( $caption_attr['alignment'] ?? '' );
			if ( in_array( $cap_align, [ 'left', 'center', 'right' ], true ) ) {
				$css->set_selector( $caption )->add_property( 'text-align', $cap_align );
			}
		}

		// Caption colours (light).
		$cap_colour = CSS_Helpers::light( $attrs['captionColor'] ?? '' );
		if ( '' !== $cap_colour ) {
			$css->set_selector( $caption )->add_property( 'color', $cap_colour );
		}
		$cap_bg = CSS_Helpers::light( $attrs['captionBackground'] ?? '' );
		if ( '' !== $cap_bg ) {
			$css->set_selector( $caption )->add_property( 'background-color', $cap_bg );
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
		$wrap_shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $wrap_shadow ) {
			$css->set_selector( $wrap )->add_property( 'box-shadow', $wrap_shadow );
		}

		// Dark mode.
		self::add_dark( $css, $wrap, $media, $overlay, $caption, $attrs, $background );
	}

	/**
	 * Overlay colour / gradient + resting opacity (light) and the hover opacity.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Overlay selector (resting).
	 * @param string      $item     Item selector (for `:hover`).
	 * @param array       $overlay  Overlay attribute.
	 */
	private static function add_overlay( $css, $selector, $item, $overlay ) {
		$type = $overlay['type'] ?? 'none';
		if ( 'none' === $type ) {
			return;
		}

		$css->set_selector( $selector );
		if ( 'color' === $type ) {
			$colour = CSS_Helpers::sanitize_color( CSS_Helpers::light( $overlay['color'] ?? '' ) );
			if ( '' !== $colour ) {
				$css->add_property( 'background-color', $colour );
			}
		} elseif ( 'gradient' === $type ) {
			$gradient = CSS_Helpers::sanitize_gradient( CSS_Helpers::light( $overlay['gradient'] ?? '' ) );
			if ( '' !== $gradient ) {
				$css->add_property( 'background-image', $gradient );
			}
		}

		$opacity = isset( $overlay['opacity'] ) ? (string) $overlay['opacity'] : '0.3';
		$css->set_selector( $selector )->add_property( 'opacity', $opacity );

		$hover = isset( $overlay['hoverOpacity'] ) ? (string) $overlay['hoverOpacity'] : '0.5';
		$css->set_selector( $item . ':hover .flexa-images-gallery__overlay' )->add_property( 'opacity', $hover );
	}

	/**
	 * Dark-mode declarations for the overlay / caption / media / wrapper.
	 *
	 * @param CSS_Builder $css        Builder.
	 * @param string      $wrap       Wrapper selector.
	 * @param string      $media      Media selector.
	 * @param string      $overlay    Overlay selector.
	 * @param string      $caption    Caption selector.
	 * @param array       $attrs      Attributes.
	 * @param array       $background Background attribute.
	 */
	private static function add_dark( $css, $wrap, $media, $overlay, $caption, $attrs, $background ) {
		// Overlay dark colour / gradient.
		$ov = $attrs['overlay'] ?? [];
		if ( 'none' !== ( $ov['type'] ?? 'none' ) ) {
			if ( 'gradient' === ( $ov['type'] ?? '' ) ) {
				CSS_Helpers::dark_color( $css, $overlay, 'background-image', CSS_Helpers::dark( $ov['gradient'] ?? '' ) );
			} else {
				CSS_Helpers::dark_color( $css, $overlay, 'background-color', CSS_Helpers::dark( $ov['color'] ?? '' ) );
			}
		}

		// Caption dark colours.
		$cap_dark    = CSS_Helpers::dark( $attrs['captionColor'] ?? '' );
		$cap_bg_dark = CSS_Helpers::dark( $attrs['captionBackground'] ?? '' );
		if ( '' !== $cap_dark || '' !== $cap_bg_dark ) {
			CSS_Helpers::add_dark_mode(
				$css,
				$caption,
				function ( $css ) use ( $cap_dark, $cap_bg_dark ) {
					if ( '' !== $cap_dark ) {
						$css->add_property( 'color', $cap_dark );
					}
					if ( '' !== $cap_bg_dark ) {
						$css->add_property( 'background-color', $cap_bg_dark );
					}
				}
			);
		}

		// Per-image shadow dark colour.
		$image_shadow = $attrs['imageShadow'] ?? [];
		if ( ! empty( $image_shadow['enabled'] ) ) {
			$shadow_dark = CSS_Helpers::dark( $image_shadow['color'] ?? '' );
			if ( '' !== $shadow_dark ) {
				$value = CSS_Helpers::box_shadow( $image_shadow, $shadow_dark );
				if ( '' !== $value ) {
					CSS_Helpers::add_dark_mode(
						$css,
						$media,
						function ( $css ) use ( $value ) {
							$css->add_property( 'box-shadow', $value );
						}
					);
				}
			}
		}

		// Wrapper background / border / shadow dark.
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
