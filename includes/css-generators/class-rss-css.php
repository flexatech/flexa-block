<?php
declare(strict_types=1);
/**
 * RSS block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one RSS instance. Grid columns and
 * gaps are emitted for the grid layout; everything else follows the "prefer theme
 * styles" rule — nothing is emitted unless the user picked a value, so untouched
 * cards keep the theme's look. Declarations target the grid / card / image /
 * title / meta / excerpt / button inside the block's own `.flexa-rss-<id>`
 * wrapper.
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
 * RSS CSS generator.
 */
class Rss_CSS {

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
	 * Generate CSS for an RSS instance.
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
		$is_grid  = 'list' !== ( $attrs['feedLayout'] ?? 'grid' );
		$outer    = '.flexa-rss-' . $id;
		$inner    = $outer . ' > .flexa-rss__inner';
		$grid     = $outer . ' .flexa-rss__grid';
		$item     = $outer . ' .flexa-rss__item';
		$body     = $outer . ' .flexa-rss__body';
		$image    = $outer . ' .flexa-rss__image';
		$title    = $outer . ' .flexa-rss__title';
		$meta     = $outer . ' .flexa-rss__meta';
		$excerpt  = $outer . ' .flexa-rss__excerpt';
		$readmore = $outer . ' .flexa-rss__readmore';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Grid columns + column gap (grid layout only).
			if ( $is_grid ) {
				$columns = $attrs['columns'][ $device ] ?? [];
				if ( isset( $columns['value'] ) && '' !== (string) $columns['value'] ) {
					$count = max( 1, (int) $columns['value'] );
					$css->set_selector( $grid )->add_property( 'grid-template-columns', 'repeat(' . $count . ', minmax(0, 1fr))' );
				}
				$col_gap = $attrs['columnGap'][ $device ] ?? [];
				if ( ! empty( $col_gap['value'] ) ) {
					$css->set_selector( $grid )->add_property( 'column-gap', CSS_Helpers::with_unit( $col_gap['value'], $col_gap['unit'] ?? 'px' ) );
				}
			}

			// Row / item gap (both layouts).
			$row_gap = $attrs['rowGap'][ $device ] ?? [];
			if ( ! empty( $row_gap['value'] ) ) {
				$css->set_selector( $grid )->add_property( 'row-gap', CSS_Helpers::with_unit( $row_gap['value'], $row_gap['unit'] ?? 'px' ) );
			}

			// Card content alignment → text-align + flex cross-axis.
			$align = CSS_Helpers::sanitize_enum( (string) ( $attrs['contentAlign'][ $device ] ?? '' ), [ 'left', 'center', 'right' ] );
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

			// Border on each item card (matches per-card background/shadow).
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

		// Thumbnail aspect ratio (non-responsive).
		$image_ratio = trim( (string) ( $attrs['imageRatio'] ?? '' ) );
		if ( '' !== $image_ratio && preg_match( '#^[0-9]+\s*/\s*[0-9]+$#', $image_ratio ) ) {
			$css->set_selector( $image )
				->add_property( 'aspect-ratio', $image_ratio )
				->add_property( 'object-fit', 'cover' )
				->add_property( 'width', '100%' );
		}

		// Equal-height cards (grid layout).
		if ( $is_grid && false !== ( $attrs['equalHeight'] ?? true ) ) {
			$css->set_selector( $item )->add_property( 'height', '100%' );
		}

		// Card + button typography (title / meta / excerpt / read-more).
		CSS_Helpers::add_typography( $css, $title, self::desktop_typo( $attrs, 'titleTypography' ) );
		CSS_Helpers::add_typography( $css, $meta, self::desktop_typo( $attrs, 'metaTypography' ) );
		CSS_Helpers::add_typography( $css, $excerpt, self::desktop_typo( $attrs, 'excerptTypography' ) );
		CSS_Helpers::add_typography( $css, $readmore, self::desktop_typo( $attrs, 'buttonTypography' ) );

		// Responsive typography (tablet / mobile) inside their media queries.
		foreach ( [ 'tablet', 'mobile' ] as $device ) {
			CSS_Helpers::open_device( $css, $device );
			CSS_Helpers::add_typography( $css, $title, $attrs['titleTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $meta, $attrs['metaTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $excerpt, $attrs['excerptTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $readmore, $attrs['buttonTypography'][ $device ] ?? [] );
			CSS_Helpers::close_device( $css, $device );
		}

		// Card colours (light base + dark branch).
		self::add_color( $css, $title, 'color', $attrs['titleColor'] ?? '' );
		self::add_color( $css, $meta, 'color', $attrs['metaColor'] ?? '' );
		self::add_color( $css, $excerpt, 'color', $attrs['excerptColor'] ?? '' );
		self::add_color( $css, $item, 'background', $attrs['cardBackground'] ?? '' );

		// Read-more button (text/background base + hover, radius, padding).
		self::add_button( $css, $attrs, $readmore );

		// Pagination styling. Numbered nav reuses the shared `.page-numbers` styler
		// (same as Post Grid); the load-more button only takes the alignment.
		$pag_type = (string) ( $attrs['paginationType'] ?? 'none' );
		if ( 'numbered' === $pag_type ) {
			CSS_Helpers::add_pagination( $css, $attrs, $outer . ' .flexa-pagination' );
		} elseif ( 'loadmore' === $pag_type ) {
			CSS_Helpers::add_loadmore( $css, $attrs, $outer . ' .flexa-pagination-loadmore', $outer . ' .flexa-pagination-loadmore__btn' );
		}

		// Wrapper background (colour / gradient / image), light + dark. Emitted
		// eagerly (no lazy gating): the RSS block ships no view.js to add
		// `.flexa-bg-loaded`, so an image url must apply on the base selector.
		$background = $attrs['background'] ?? [];
		if ( ! empty( $background ) ) {
			$css->set_selector( $inner );
			CSS_Helpers::add_background( $css, $background );

			$type = $background['type'] ?? 'none';
			if ( 'classic' === $type || 'color' === $type ) {
				CSS_Helpers::dark_color( $css, $inner, 'background-color', CSS_Helpers::dark( $background['color'] ?? '' ) );
			} elseif ( 'gradient' === $type ) {
				CSS_Helpers::dark_color( $css, $inner, 'background-image', CSS_Helpers::dark( $background['gradient'] ?? '' ) );
			}
		}

		// Box shadow on each item card (base, light) — matches per-card border/bg.
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $item )->add_property( 'box-shadow', $shadow );
		}

		// Dark mode on each item card: border colour + shadow colour.
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
	 * padding, alignment, full-width). Nothing is emitted unless the user set a
	 * value, so an untouched button keeps its `wp-element-button` theme styling.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param array       $attrs    Attributes.
	 * @param string      $readmore Read-more button selector.
	 */
	private static function add_button( $css, $attrs, $readmore ) {
		$hover = $readmore . ':hover';

		self::add_color( $css, $readmore, 'color', $attrs['buttonTextColor'] ?? '' );
		self::add_color( $css, $hover, 'color', $attrs['buttonTextColorHover'] ?? '' );
		self::add_color( $css, $readmore, 'background', $attrs['buttonBackground'] ?? '' );
		self::add_color( $css, $hover, 'background', $attrs['buttonBackgroundHover'] ?? '' );

		$radius = $attrs['buttonRadius'] ?? [];
		if ( ! empty( $radius['value'] ) ) {
			$css->set_selector( $readmore )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
		}

		$padding = CSS_Helpers::spacing_shorthand( $attrs['buttonPadding'] ?? [] );
		if ( '' !== $padding ) {
			$css->set_selector( $readmore )->add_property( 'padding', $padding );
		}

		$align = (string) ( $attrs['buttonAlign'] ?? '' );
		if ( isset( self::$align_map[ $align ] ) ) {
			$css->set_selector( $readmore )->add_property( 'align-self', self::$align_map[ $align ] );
		}

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
