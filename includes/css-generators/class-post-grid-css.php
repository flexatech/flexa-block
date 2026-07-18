<?php
declare(strict_types=1);
/**
 * Post Grid block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one post-grid instance. The grid
 * columns and gaps are always emitted (a grid needs them), but the card colours
 * and typography follow the "prefer theme styles" rule — nothing is emitted
 * unless the user picked a value, so untouched cards keep the theme's look.
 * Declarations target the grid / card / image / title / meta / excerpt / button
 * inside the block's own `.flexa-post-grid-<id>` wrapper.
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
 * Post Grid CSS generator.
 */
class Post_Grid_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map a content-alignment keyword to its flex cross-axis value.
	 *
	 * @var array
	 */
	private static $align_map = [
		'left'   => 'flex-start',
		'center' => 'center',
		'right'  => 'flex-end',
	];

	/**
	 * Generate CSS for a post-grid instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$is_boxed = 'full-width' !== ( $attrs['containerType'] ?? 'boxed' );
		$outer    = '.flexa-post-grid-' . $id;
		$inner    = $outer . ' > .flexa-post-grid__inner';
		$grid     = $outer . ' .flexa-post-grid__grid';
		$item     = $outer . ' .flexa-post-grid__item';
		$body     = $outer . ' .flexa-post-grid__body';
		$image    = $outer . ' .flexa-post-grid__image';
		$title    = $outer . ' .flexa-post-grid__title';
		$meta     = $outer . ' .flexa-post-grid__meta';
		$excerpt  = $outer . ' .flexa-post-grid__excerpt';
		$readmore = $outer . ' .flexa-post-grid__readmore';
		// The "N posts found" line. Styled only in its SHOWN state: when the author has
		// left it for screen readers only, `screen-reader-text` clips it to a pixel and
		// a font size on it would be a rule about nothing.
		$status = $outer . ' .flexa-post-grid__status:not(.screen-reader-text)';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Grid columns.
			$columns = $attrs['columns'][ $device ] ?? [];
			if ( isset( $columns['value'] ) && '' !== (string) $columns['value'] ) {
				$count = max( 1, (int) $columns['value'] );
				$css->set_selector( $grid )->add_property( 'grid-template-columns', 'repeat(' . $count . ', minmax(0, 1fr))' );
			}

			// Row + column gaps.
			$row_gap = $attrs['rowGap'][ $device ] ?? [];
			if ( ! empty( $row_gap['value'] ) ) {
				$css->set_selector( $grid )->add_property( 'row-gap', CSS_Helpers::with_unit( $row_gap['value'], $row_gap['unit'] ?? 'px' ) );
			}
			$col_gap = $attrs['columnGap'][ $device ] ?? [];
			if ( ! empty( $col_gap['value'] ) ) {
				$css->set_selector( $grid )->add_property( 'column-gap', CSS_Helpers::with_unit( $col_gap['value'], $col_gap['unit'] ?? 'px' ) );
			}

			// Card content alignment → text-align + flex cross-axis. The unset value is
			// block.json's empty object, not '', so cast only what is actually scalar —
			// (string) [] is "Array" plus a PHP warning on every save.
			$align_raw = $attrs['contentAlign'][ $device ] ?? '';
			$align     = CSS_Helpers::sanitize_enum( is_scalar( $align_raw ) ? (string) $align_raw : '', [ 'left', 'center', 'right' ] );
			if ( '' !== $align ) {
				$css->set_selector( $item )
					->add_property( 'text-align', $align )
					->add_property( 'align-items', self::$align_map[ $align ] );
			}

			// Inner content padding on the body (the image stays full-bleed).
			$content_pad = CSS_Helpers::spacing_shorthand( $attrs['contentPadding'][ $device ] ?? [] );
			if ( '' !== $content_pad ) {
				$css->set_selector( $body )->add_property( 'padding', $content_pad );
			}

			// Vertical gap between the meta / title / excerpt / button in the body.
			$content_gap = $attrs['contentGap'][ $device ] ?? [];
			if ( ! empty( $content_gap['value'] ) ) {
				$css->set_selector( $body )->add_property( 'gap', CSS_Helpers::with_unit( $content_gap['value'], $content_gap['unit'] ?? 'px' ) );
			}

			// Wrapper spacing: padding on inner, margin on outer.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			if ( ! empty( $spacing['padding'] ) ) {
				$pad = CSS_Helpers::spacing_shorthand( $spacing['padding'] );
				if ( '' !== $pad ) {
					$css->set_selector( $inner )->add_property( 'padding', $pad );
				}
			}
			if ( ! empty( $spacing['margin'] ) ) {
				$margin = CSS_Helpers::spacing_shorthand( $spacing['margin'] );
				if ( '' !== $margin ) {
					$css->set_selector( $outer )->add_property( 'margin', $margin );
				}
			}

			// Width: max-width (boxed) or width (full-width) on the inner element.
			$width_attr = $is_boxed ? ( $attrs['widthBoxed'][ $device ] ?? [] ) : ( $attrs['widthFullWidth'][ $device ] ?? [] );
			if ( ! empty( $width_attr['value'] ) ) {
				$value = CSS_Helpers::with_unit( $width_attr['value'], $width_attr['unit'] ?? ( $is_boxed ? 'px' : '%' ) );
				$css->set_selector( $inner )->add_property( $is_boxed ? 'max-width' : 'width', $value );
			}

			// Border on each post card (matches per-card background/shadow).
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $item );
				CSS_Helpers::add_border( $css, $border );
			}

			// Advanced layout (overflow / position / z-index) on the inner element.
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $inner );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Featured-image aspect ratio (non-responsive).
		$image_ratio = trim( (string) ( $attrs['imageRatio'] ?? '' ) );
		if ( '' !== $image_ratio && preg_match( '#^[0-9]+\s*/\s*[0-9]+$#', $image_ratio ) ) {
			$css->set_selector( $image )
				->add_property( 'aspect-ratio', $image_ratio )
				->add_property( 'object-fit', 'cover' )
				->add_property( 'width', '100%' );
		}

		// Equal-height cards.
		if ( false !== ( $attrs['equalHeight'] ?? true ) ) {
			$css->set_selector( $item )->add_property( 'height', '100%' );
		}

		// Card + button typography (title / meta / excerpt / read-more).
		CSS_Helpers::add_typography( $css, $title, self::desktop_typo( $attrs, 'titleTypography' ) );
		CSS_Helpers::add_typography( $css, $meta, self::desktop_typo( $attrs, 'metaTypography' ) );
		CSS_Helpers::add_typography( $css, $excerpt, self::desktop_typo( $attrs, 'excerptTypography' ) );
		CSS_Helpers::add_typography( $css, $readmore, self::desktop_typo( $attrs, 'buttonTypography' ) );
		CSS_Helpers::add_typography( $css, $status, self::desktop_typo( $attrs, 'resultCountTypography' ) );

		// Responsive typography (tablet / mobile) inside their media queries.
		foreach ( [ 'tablet', 'mobile' ] as $device ) {
			CSS_Helpers::open_device( $css, $device );
			CSS_Helpers::add_typography( $css, $title, $attrs['titleTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $meta, $attrs['metaTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $excerpt, $attrs['excerptTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $readmore, $attrs['buttonTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $status, $attrs['resultCountTypography'][ $device ] ?? [] );
			CSS_Helpers::close_device( $css, $device );
		}

		// Card colours (light base + dark branch).
		self::add_color( $css, $title, 'color', $attrs['titleColor'] ?? '' );
		self::add_color( $css, $meta, 'color', $attrs['metaColor'] ?? '' );
		self::add_color( $css, $excerpt, 'color', $attrs['excerptColor'] ?? '' );
		self::add_color( $css, $item, 'background', $attrs['cardBackground'] ?? '' );
		self::add_color( $css, $status, 'color', $attrs['resultCountColor'] ?? '' );

		$status_align = CSS_Helpers::sanitize_enum( (string) ( $attrs['resultCountAlign'] ?? '' ), [ 'left', 'center', 'right' ] );
		if ( '' !== $status_align ) {
			$css->set_selector( $status )->add_property( 'text-align', $status_align );
		}

		// Read-more button (text/background base + hover, radius, padding).
		self::add_button( $css, $attrs, $readmore );

		// Pagination (alignment + link/active colours + radius).
		$pag_type = (string) ( $attrs['paginationType'] ?? 'numbered' );
		if ( 'numbered' === $pag_type ) {
			CSS_Helpers::add_pagination( $css, $attrs, $outer . ' .flexa-pagination' );
		} elseif ( 'loadmore' === $pag_type ) {
			CSS_Helpers::add_loadmore( $css, $attrs, $outer . ' .flexa-pagination-loadmore', $outer . ' .flexa-pagination-loadmore__btn' );
		}

		// Wrapper background (base, light) + lazy image url gated behind the loaded class.
		$background = $attrs['background'] ?? [];
		$lazy_bg    = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
		if ( ! empty( $background ) ) {
			$css->set_selector( $inner );
			CSS_Helpers::add_background( $css, $background, $lazy_bg );
			if ( $lazy_bg ) {
				$css->set_selector( $inner . '.flexa-bg-loaded' )
					->add_property( 'background-image', 'url(' . esc_url_raw( $background['image']['url'] ) . ')' );
			}
		}

		// Box shadow on each post card (base, light) — matches per-card border/bg.
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $item )->add_property( 'box-shadow', $shadow );
		}

		// Dark mode on the wrapper inner element: background only.
		CSS_Helpers::add_dark_mode(
			$css,
			$inner,
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

		// Dark mode on each post card: border colour + shadow colour.
		CSS_Helpers::add_dark_mode(
			$css,
			$item,
			function ( $css ) use ( $attrs ) {
				$border_dark = CSS_Helpers::sanitize_color( CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );
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

	/**
	 * Emit the read-more button styling (text/background base + hover, radius,
	 * padding). Nothing is emitted unless the user set a value, so an untouched
	 * button keeps its `wp-element-button` theme styling.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param array       $attrs    Attributes.
	 * @param string      $readmore Read-more button selector.
	 */
	private static function add_button( $css, $attrs, $readmore ) {
		$hover = $readmore . ':hover';

		// Text colour (base + hover), light + dark.
		self::add_color( $css, $readmore, 'color', $attrs['buttonTextColor'] ?? '' );
		self::add_color( $css, $hover, 'color', $attrs['buttonTextColorHover'] ?? '' );

		// Background colour (base + hover), light + dark.
		self::add_color( $css, $readmore, 'background', $attrs['buttonBackground'] ?? '' );
		self::add_color( $css, $hover, 'background', $attrs['buttonBackgroundHover'] ?? '' );

		// Corner radius.
		$radius = $attrs['buttonRadius'] ?? [];
		if ( ! empty( $radius['value'] ) ) {
			$css->set_selector( $readmore )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
		}

		// Padding.
		$padding = CSS_Helpers::spacing_shorthand( $attrs['buttonPadding'] ?? [] );
		if ( '' !== $padding ) {
			$css->set_selector( $readmore )->add_property( 'padding', $padding );
		}

		// Alignment within the card (the button is a flex item that hugs its text).
		$align_map = [
			'left'   => 'flex-start',
			'center' => 'center',
			'right'  => 'flex-end',
		];
		$align = (string) ( $attrs['buttonAlign'] ?? '' );
		if ( isset( $align_map[ $align ] ) ) {
			$css->set_selector( $readmore )->add_property( 'align-self', $align_map[ $align ] );
		}

		// Full-width button stretches across the card and centres its label.
		if ( 'full' === ( $attrs['buttonWidth'] ?? 'auto' ) ) {
			$css->set_selector( $readmore )
				->add_property( 'display', 'flex' )
				->add_property( 'width', '100%' )
				->add_property( 'justify-content', 'center' );
		}
	}

	/**
	 * The desktop device object of a responsive typography attribute.
	 *
	 * @param array  $attrs Attributes.
	 * @param string $key   Attribute name.
	 * @return array
	 */
	private static function desktop_typo( $attrs, $key ) {
		return $attrs[ $key ]['desktop'] ?? [];
	}

	/**
	 * Emit a colour pair onto a selector: light at the base, dark under the
	 * dark-mode branch. Nothing is emitted when neither light nor dark is set.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param string      $property CSS property (`color` or `background`).
	 * @param mixed       $color    Colour pair.
	 */
	private static function add_color( $css, $selector, $property, $color ) {
		$light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $color ) );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( $property, $light );
		}
		CSS_Helpers::dark_color( $css, $selector, $property, CSS_Helpers::dark( $color ) );
	}
}
