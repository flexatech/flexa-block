<?php
declare(strict_types=1);
/**
 * Instagram Feed block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one Instagram Feed instance. The
 * grid columns and gap are emitted for the grid/overlay/card layouts; everything
 * else follows the "prefer theme styles" rule — nothing is emitted unless the
 * user picked a value, so an untouched feed keeps the theme's look. Declarations
 * target the grid / item / image / overlay / caption / meta inside the block's
 * own `.flexa-instagram-feed-<id>` wrapper.
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
 * Instagram Feed CSS generator.
 */
class Instagram_Feed_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for an Instagram Feed instance.
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
		$outer    = '.flexa-instagram-feed-' . $id;
		$inner    = $outer . ' > .flexa-instagram-feed__inner';
		$grid     = $outer . ' .flexa-instagram-feed__grid';
		$item     = $outer . ' .flexa-instagram-feed__item';
		$image    = $outer . ' .flexa-instagram-feed__image';
		$overlay  = $outer . ' .flexa-instagram-feed__overlay';
		$body     = $outer . ' .flexa-instagram-feed__body';
		// The text area: the __body wrapper (grid/card/carousel) or the __overlay
		// (overlay layout) — content padding + gap apply to both.
		$content  = $body . ', ' . $overlay;
		$caption  = $outer . ' .flexa-instagram-feed__caption';
		// The byline separator dot shares the Meta typography / colour with the date
		// (__meta is kept LAST so the group is anchored on it).
		$meta     = $outer . ' .flexa-instagram-feed__byline-sep, ' . $outer . ' .flexa-instagram-feed__meta';

		// Item-style migration flag. Before it flips, the wrapper border/shadow
		// still paint each item (legacy behaviour); after, the item uses the
		// dedicated itemBorder/itemBoxShadow and the wrapper carries border/shadow.
		// TODO(remove in vNEXT): item-style migration.
		$migrated = ! empty( $attrs['itemStyleMigrated'] );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Grid columns.
			$columns = $attrs['columns'][ $device ] ?? [];
			if ( isset( $columns['value'] ) && '' !== (string) $columns['value'] ) {
				$count = max( 1, (int) $columns['value'] );
				$css->set_selector( $grid )->add_property( 'grid-template-columns', 'repeat(' . $count . ', minmax(0, 1fr))' );
			}

			// Gap between items (row + column).
			$gap = $attrs['itemGap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $grid )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Content area (text) padding + gap on the __body / __overlay wrapper.
			$content_pad = CSS_Helpers::spacing_shorthand( $attrs['contentPadding'][ $device ] ?? [] );
			if ( '' !== $content_pad ) {
				$css->set_selector( $content )->add_property( 'padding', $content_pad );
			}
			$content_gap = $attrs['contentGap'][ $device ] ?? [];
			if ( ! empty( $content_gap['value'] ) ) {
				$css->set_selector( $content )->add_property( 'gap', CSS_Helpers::with_unit( $content_gap['value'], $content_gap['unit'] ?? 'px' ) );
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

			// Border on each media item (matches per-item background/shadow).
			// Post-migration it comes from itemBorder; legacy content falls back to
			// the wrapper border (which used to paint the item).
			// TODO(remove in vNEXT): drop the fallback, always itemBorder.
			$item_border = ( $migrated ? ( $attrs['itemBorder'] ?? [] ) : ( $attrs['border'] ?? [] ) )[ $device ] ?? [];
			if ( ! empty( $item_border ) ) {
				$css->set_selector( $item );
				CSS_Helpers::add_border( $css, $item_border );
			}

			// Wrapper border (post-migration only): the Style-tab border now frames
			// the whole block. TODO(remove in vNEXT): the migrated gate.
			if ( $migrated ) {
				$wrap_border = $attrs['border'][ $device ] ?? [];
				if ( ! empty( $wrap_border ) ) {
					$css->set_selector( $inner );
					CSS_Helpers::add_border( $css, $wrap_border );
				}
			}

			// Advanced layout (overflow / position / z-index) on the inner element.
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $inner );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Square thumbnails (non-responsive): force a 1:1 aspect ratio + cover fit.
		if ( ! empty( $attrs['squareThumbnail'] ) ) {
			$css->set_selector( $image )
				->add_property( 'aspect-ratio', '1' )
				->add_property( 'object-fit', 'cover' );
		}

		// Caption + meta typography (desktop at the base).
		CSS_Helpers::add_typography( $css, $caption, self::desktop_typo( $attrs, 'captionTypography' ) );
		CSS_Helpers::add_typography( $css, $meta, self::desktop_typo( $attrs, 'metaTypography' ) );

		// Responsive typography (tablet / mobile) inside their media queries.
		foreach ( [ 'tablet', 'mobile' ] as $device ) {
			CSS_Helpers::open_device( $css, $device );
			CSS_Helpers::add_typography( $css, $caption, $attrs['captionTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $meta, $attrs['metaTypography'][ $device ] ?? [] );
			CSS_Helpers::close_device( $css, $device );
		}

		// Colours (light base + dark branch).
		self::add_color( $css, $caption, 'color', $attrs['captionColor'] ?? '' );
		self::add_color( $css, $meta, 'color', $attrs['metaColor'] ?? '' );
		self::add_color( $css, $overlay, 'background', $attrs['overlayColor'] ?? '' );
		self::add_color( $css, $item, 'background', $attrs['cardBackground'] ?? '' );

		// Wrapper background (colour / gradient / image), light + dark. Emitted
		// eagerly (no lazy gating): the Instagram Feed block ships no view.js to add
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

		// Box shadow on each media item (base, light). Post-migration from
		// itemBoxShadow; legacy content falls back to the wrapper boxShadow.
		// TODO(remove in vNEXT): drop the legacy fallback.
		$item_shadow_cfg = $migrated ? ( $attrs['itemBoxShadow'] ?? [] ) : ( $attrs['boxShadow'] ?? [] );
		$shadow          = CSS_Helpers::box_shadow( $item_shadow_cfg );
		if ( '' !== $shadow ) {
			$css->set_selector( $item )->add_property( 'box-shadow', $shadow );
		}

		// Wrapper box shadow (post-migration only).
		// TODO(remove in vNEXT): the migrated gate.
		if ( $migrated ) {
			$wrap_shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
			if ( '' !== $wrap_shadow ) {
				$css->set_selector( $inner )->add_property( 'box-shadow', $wrap_shadow );
			}
		}

		// Dark mode on each media item: border colour + shadow colour, read from
		// the same source as the light rules (itemBorder/itemBoxShadow once
		// migrated, else the legacy wrapper border/boxShadow).
		// TODO(remove in vNEXT): drop the $migrated fallback.
		CSS_Helpers::add_dark_mode(
			$css,
			$item,
			function ( $css ) use ( $attrs, $migrated ) {
				$item_border_cfg = $migrated ? ( $attrs['itemBorder'] ?? [] ) : ( $attrs['border'] ?? [] );
				$border_dark     = CSS_Helpers::sanitize_color( CSS_Helpers::dark( $item_border_cfg['desktop']['color'] ?? '' ) );
				if ( '' !== $border_dark ) {
					$css->add_property( 'border-color', $border_dark );
				}

				$shadow_cfg = $migrated ? ( $attrs['itemBoxShadow'] ?? [] ) : ( $attrs['boxShadow'] ?? [] );
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

		// Dark mode on the wrapper inner (post-migration only): border + shadow.
		// TODO(remove in vNEXT): the migrated gate.
		if ( $migrated ) {
			CSS_Helpers::add_dark_mode(
				$css,
				$inner,
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
