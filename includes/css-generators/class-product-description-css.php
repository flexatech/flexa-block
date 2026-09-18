<?php
declare(strict_types=1);
/**
 * Product Description block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one product-description instance.
 * Nothing is emitted unless the user picked a value, so an untouched block keeps
 * the theme's typography. The wrapper `.flexa-product-description-<id>` carries
 * the foundation (alignment / spacing / border / background / shadow); body
 * typography and colour target the inner `__content`, and the elements nested
 * *inside* the stored description (h2–h4, links, lists, table cells) are styled
 * through descendant selectors off that same content box.
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
 * Product Description CSS generator.
 */
class Product_Description_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a product-description instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap    = '.flexa-product-description-' . $id;
		$title   = $wrap . ' .flexa-product-description__title';
		$content = $wrap . ' .flexa-product-description__content';
		$heading = $content . ' h2, ' . $content . ' h3, ' . $content . ' h4';
		$link    = $content . ' a';
		$hover   = $link . ':hover';
		$list    = $content . ' ul, ' . $content . ' ol';
		$cell    = $content . ' th, ' . $content . ' td';
		$toggle  = $wrap . ' .flexa-product-description__toggle';

		$show_title = ! empty( $attrs['showTitle'] );
		$clamped    = ! empty( $attrs['enableClamp'] );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Foundation: alignment, padding / margin, border, advanced layout.
			CSS_Helpers::add_wrapper_device( $css, $wrap, $attrs, $device );

			// Body typography on the description box.
			CSS_Helpers::add_typography( $css, $content, $attrs['typography'][ $device ] ?? [] );

			// Title typography, only when the title is shown.
			if ( $show_title ) {
				CSS_Helpers::add_typography( $css, $title, $attrs['titleTypography'][ $device ] ?? [] );
			}

			// Headings that live inside the stored description markup.
			CSS_Helpers::add_typography( $css, $heading, $attrs['innerHeadingTypography'][ $device ] ?? [] );

			// Lists: the marker style is one value for every device, so it is
			// emitted once at the base rather than repeated in each media query.
			if ( 'desktop' === $device ) {
				$list_style = CSS_Helpers::sanitize_enum( $attrs['listStyle'] ?? '', [ 'disc', 'circle', 'square', 'decimal', 'none' ] );
				if ( '' !== $list_style ) {
					$css->set_selector( $list )->add_property( 'list-style-type', $list_style );
				}
			}

			$indent = $attrs['listIndent'][ $device ] ?? [];
			if ( ! empty( $indent['value'] ) ) {
				$css->set_selector( $list )->add_property( 'padding-left', CSS_Helpers::with_unit( $indent['value'], $indent['unit'] ?? 'px' ) );
			}

			// Table cell padding.
			$cell_padding = $attrs['tableCellPadding'][ $device ] ?? [];
			if ( ! empty( $cell_padding ) ) {
				$value = CSS_Helpers::spacing_shorthand( $cell_padding );
				if ( '' !== $value ) {
					$css->set_selector( $cell )->add_property( 'padding', $value );
				}
			}

			// Line clamp, only while the clamp is on (style.scss supplies the
			// -webkit-box scaffolding the property needs to take effect).
			if ( $clamped ) {
				$lines = (int) ( $attrs['clampLines'] ?? 0 );
				if ( $lines > 0 ) {
					$css->set_selector( $content )->add_property( '-webkit-line-clamp', (string) $lines );
				}
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Body / title / nested-heading colours (light at base, dark branch).
		CSS_Helpers::color_pair( $css, $content, 'color', $attrs['textColor'] ?? '' );
		if ( $show_title ) {
			CSS_Helpers::color_pair( $css, $title, 'color', $attrs['titleColor'] ?? '' );
		}
		CSS_Helpers::color_pair( $css, $heading, 'color', $attrs['innerHeadingColor'] ?? '' );

		// Links inside the description, normal and hover.
		CSS_Helpers::color_pair( $css, $link, 'color', $attrs['linkColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $hover, 'color', $attrs['linkColorHover'] ?? '' );

		// Read more / less toggle colour, only while the clamp is on.
		if ( $clamped ) {
			CSS_Helpers::color_pair( $css, $toggle, 'color', $attrs['readMoreColor'] ?? '' );
		}

		// Table cell border colour: the border itself comes from the theme, this
		// only recolours it.
		CSS_Helpers::color_pair( $css, $cell, 'border-color', $attrs['tableBorderColor'] ?? '' );

		// Foundation: background, box shadow and the wrapper's dark-mode branch.
		CSS_Helpers::add_wrapper_base( $css, $wrap, $attrs );
	}
}
