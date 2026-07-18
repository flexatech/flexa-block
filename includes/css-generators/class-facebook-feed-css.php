<?php
declare(strict_types=1);
/**
 * Facebook Feed block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one Facebook Feed instance. Grid
 * columns (or masonry column-count) and the gap between posts are emitted for the
 * relevant layouts; everything else follows the "prefer theme styles" rule —
 * nothing is emitted unless the user picked a value, so untouched cards keep the
 * theme's look. Declarations target the grid / card / header / message / meta
 * inside the block's own `.flexa-facebook-feed-<id>` wrapper.
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
 * Facebook Feed CSS generator.
 */
class Facebook_Feed_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a Facebook Feed instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$is_boxed   = 'full-width' !== ( $attrs['containerType'] ?? 'boxed' );
		$layout     = (string) ( $attrs['feedLayout'] ?? 'grid' );
		$is_grid    = 'grid' === $layout;
		$is_masonry = 'masonry' === $layout;

		$outer     = '.flexa-facebook-feed-' . $id;
		$inner     = $outer . ' > .flexa-facebook-feed__inner';
		$grid      = $outer . ' .flexa-facebook-feed__grid';
		$item      = $outer . ' .flexa-facebook-feed__item';
		$page_name = $outer . ' .flexa-facebook-feed__page-name';
		$message   = $outer . ' .flexa-facebook-feed__message';
		$timestamp = $outer . ' .flexa-facebook-feed__timestamp';
		$actions   = $outer . ' .flexa-facebook-feed__actions';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Column count (grid → grid-template-columns; masonry → column-count).
			if ( $is_grid || $is_masonry ) {
				$columns = $attrs['columns'][ $device ] ?? [];
				if ( isset( $columns['value'] ) && '' !== (string) $columns['value'] ) {
					$count = max( 1, (int) $columns['value'] );
					if ( $is_masonry ) {
						$css->set_selector( $grid )->add_property( 'column-count', (string) $count );
					} else {
						$css->set_selector( $grid )->add_property( 'grid-template-columns', 'repeat(' . $count . ', minmax(0, 1fr))' );
					}
				}
			}

			// Gap between posts. Masonry (CSS columns) takes column-gap on the grid
			// plus a matching bottom margin on each card for vertical rhythm.
			$item_gap = $attrs['itemGap'][ $device ] ?? [];
			if ( ! empty( $item_gap['value'] ) ) {
				$gap = CSS_Helpers::with_unit( $item_gap['value'], $item_gap['unit'] ?? 'px' );
				if ( $is_masonry ) {
					$css->set_selector( $grid )->add_property( 'column-gap', $gap );
					$css->set_selector( $item )->add_property( 'margin-bottom', $gap );
				} else {
					$css->set_selector( $grid )->add_property( 'gap', $gap );
				}
			}

			// Content: padding inside each card + gap between its parts (header /
			// image / message / actions).
			$content_pad = CSS_Helpers::spacing_shorthand( $attrs['contentPadding'][ $device ] ?? [] );
			if ( '' !== $content_pad ) {
				$css->set_selector( $item )->add_property( 'padding', $content_pad );
			}
			$content_gap = $attrs['contentGap'][ $device ] ?? [];
			if ( ! empty( $content_gap['value'] ) ) {
				$css->set_selector( $item )->add_property( 'gap', CSS_Helpers::with_unit( $content_gap['value'], $content_gap['unit'] ?? 'px' ) );
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

		// Card typography (desktop base): header / message / meta.
		CSS_Helpers::add_typography( $css, $page_name, $attrs['headerTypography']['desktop'] ?? [] );
		CSS_Helpers::add_typography( $css, $message, $attrs['messageTypography']['desktop'] ?? [] );
		CSS_Helpers::add_typography( $css, $timestamp, $attrs['metaTypography']['desktop'] ?? [] );
		CSS_Helpers::add_typography( $css, $actions, $attrs['metaTypography']['desktop'] ?? [] );

		// Responsive typography (tablet / mobile) inside their media queries.
		foreach ( [ 'tablet', 'mobile' ] as $device ) {
			CSS_Helpers::open_device( $css, $device );
			CSS_Helpers::add_typography( $css, $page_name, $attrs['headerTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $message, $attrs['messageTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $timestamp, $attrs['metaTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $actions, $attrs['metaTypography'][ $device ] ?? [] );
			CSS_Helpers::close_device( $css, $device );
		}

		// Card colours (light base + dark branch).
		CSS_Helpers::color_pair( $css, $page_name, 'color', $attrs['headerColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $message, 'color', $attrs['messageColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $timestamp, 'color', $attrs['metaColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $actions, 'color', $attrs['metaColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $item, 'background', $attrs['cardBackground'] ?? '' );

		// Pagination styling. Numbered nav reuses the shared `.page-numbers` styler;
		// the load-more button uses the alignment + button colours.
		$pag_type = (string) ( $attrs['paginationType'] ?? 'none' );
		if ( 'numbered' === $pag_type ) {
			CSS_Helpers::add_pagination( $css, $attrs, $outer . ' .flexa-pagination' );
		} elseif ( 'loadmore' === $pag_type ) {
			CSS_Helpers::add_loadmore( $css, $attrs, $outer . ' .flexa-pagination-loadmore', $outer . ' .flexa-pagination-loadmore__btn' );
		}

		// Wrapper background (colour / gradient / image), light + dark. Emitted
		// eagerly (no lazy gating): the block's view.js adds no `.flexa-bg-loaded`
		// class, so an image url must apply on the base selector.
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

		// Box shadow on each post card (base, light) — matches per-card border/bg.
		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $item )->add_property( 'box-shadow', $shadow );
		}

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
}
