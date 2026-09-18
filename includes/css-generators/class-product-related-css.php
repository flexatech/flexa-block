<?php
declare(strict_types=1);
/**
 * Related Products block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one product-related instance.
 * Nothing is emitted unless the user picked a value, so an untouched block reads
 * as an ordinary theme-styled card grid. The wrapper `.flexa-product-related-<id>`
 * carries alignment / spacing / background / border / shadow; the grid geometry
 * (columns + gaps) targets `.flexa-product-related__list`; the card box (padding,
 * radius, outline, background, shadow, text alignment) targets
 * `.flexa-product-related__item`; and the card parts each get their own selector
 * so a hidden part never emits CSS at all.
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
 * Related Products CSS generator.
 */
class Product_Related_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a product-related instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap    = '.flexa-product-related-' . $id;
		$heading = $wrap . ' .flexa-product-related__heading';
		$list    = $wrap . ' .flexa-product-related__list';
		$item    = $wrap . ' .flexa-product-related__item';
		$image   = $wrap . ' .flexa-product-related__image img';
		$title   = $wrap . ' .flexa-product-related__title';
		$price   = $wrap . ' .flexa-product-related__price';
		$stars   = $wrap . ' .flexa-product-related__rating';
		$button  = $wrap . ' .flexa-product-related__button';

		// Each card part is optional: gate its CSS on the same toggle render.php
		// reads, so a hidden part leaves no orphan rules behind.
		$layout       = CSS_Helpers::sanitize_enum( (string) ( $attrs['relatedLayout'] ?? 'grid' ), [ 'grid', 'list' ], 'grid' );
		$show_heading = ! isset( $attrs['showHeading'] ) || ! empty( $attrs['showHeading'] );
		$show_image   = ! isset( $attrs['showImage'] ) || ! empty( $attrs['showImage'] );
		$show_title   = ! isset( $attrs['showTitle'] ) || ! empty( $attrs['showTitle'] );
		$show_price   = ! isset( $attrs['showPrice'] ) || ! empty( $attrs['showPrice'] );
		$show_rating  = ! empty( $attrs['showRating'] );
		$show_button  = ! empty( $attrs['showButton'] );

		// A ratio like `4/3`; anything else is ignored so a stray value can't leak
		// into the stylesheet.
		$image_ratio = trim( (string) ( $attrs['imageRatio'] ?? '' ) );
		$has_ratio   = '' !== $image_ratio && (bool) preg_match( '#^[0-9]+\s*/\s*[0-9]+$#', $image_ratio );

		$content_align = CSS_Helpers::sanitize_enum( (string) ( $attrs['contentAlign'] ?? '' ), [ 'left', 'center', 'right' ] );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Alignment / spacing / border / advanced layout on the wrapper.
			CSS_Helpers::add_wrapper_device( $css, $wrap, $attrs, $device );

			// Column count only means something in the grid layout — the list
			// layout is a single flex column (see style.scss).
			if ( 'grid' === $layout ) {
				$columns = (string) ( $attrs['columns'][ $device ] ?? '' );
				if ( '' !== $columns ) {
					$count = max( 1, min( 8, (int) $columns ) );
					$css->set_selector( $list )->add_property( 'grid-template-columns', 'repeat(' . $count . ', minmax(0, 1fr))' );
				}
			}

			$row_gap = $attrs['rowGap'][ $device ] ?? [];
			if ( ! empty( $row_gap['value'] ) ) {
				$css->set_selector( $list )->add_property( 'row-gap', CSS_Helpers::with_unit( $row_gap['value'], $row_gap['unit'] ?? 'px' ) );
			}
			$col_gap = $attrs['columnGap'][ $device ] ?? [];
			if ( ! empty( $col_gap['value'] ) ) {
				$css->set_selector( $list )->add_property( 'column-gap', CSS_Helpers::with_unit( $col_gap['value'], $col_gap['unit'] ?? 'px' ) );
			}

			// Card box geometry.
			$padding = CSS_Helpers::spacing_shorthand( $attrs['cardPadding'][ $device ] ?? [] );
			if ( '' !== $padding ) {
				$css->set_selector( $item )->add_property( 'padding', $padding );
			}

			$radius = $attrs['cardRadius'][ $device ] ?? [];
			if ( ! empty( $radius['value'] ) ) {
				$css->set_selector( $item )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
			}

			// The outline is a width + colour pair, so the style comes with the width.
			$border_width = $attrs['cardBorderWidth'][ $device ] ?? [];
			if ( ! empty( $border_width['value'] ) ) {
				$css->set_selector( $item )
					->add_property( 'border-style', 'solid' )
					->add_property( 'border-width', CSS_Helpers::with_unit( $border_width['value'], $border_width['unit'] ?? 'px' ) );
			}

			// Non-responsive values ride the desktop pass so they land at the base
			// rather than being repeated in every media query.
			if ( 'desktop' === $device ) {
				if ( $show_image && $has_ratio ) {
					$css->set_selector( $image )
						->add_property( 'aspect-ratio', $image_ratio )
						->add_property( 'object-fit', 'cover' );
				}

				if ( '' !== $content_align ) {
					$css->set_selector( $item )->add_property( 'text-align', $content_align );
				}
			}

			if ( $show_heading ) {
				CSS_Helpers::add_typography( $css, $heading, $attrs['headingTypography'][ $device ] ?? [] );
			}
			if ( $show_title ) {
				CSS_Helpers::add_typography( $css, $title, $attrs['titleTypography'][ $device ] ?? [] );
			}
			if ( $show_price ) {
				CSS_Helpers::add_typography( $css, $price, $attrs['priceTypography'][ $device ] ?? [] );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Button colours / radius / padding / alignment / width (emits `:hover`).
		if ( $show_button ) {
			CSS_Helpers::add_button( $css, $button, $attrs );
		}

		if ( $show_heading ) {
			CSS_Helpers::color_pair( $css, $heading, 'color', $attrs['headingColor'] ?? '' );
		}
		if ( $show_title ) {
			CSS_Helpers::color_pair( $css, $title, 'color', $attrs['titleColor'] ?? '' );
		}
		if ( $show_price ) {
			CSS_Helpers::color_pair( $css, $price, 'color', $attrs['priceColor'] ?? '' );
		}
		if ( $show_rating ) {
			// Both star rows inherit `color` from the rating element.
			CSS_Helpers::color_pair( $css, $stars, 'color', $attrs['starColor'] ?? '' );
		}

		CSS_Helpers::color_pair( $css, $item, 'background', $attrs['cardBackground'] ?? '' );
		CSS_Helpers::color_pair( $css, $item, 'border-color', $attrs['cardBorderColor'] ?? '' );

		$card_shadow = CSS_Helpers::box_shadow( $attrs['cardShadow'] ?? [] );
		if ( '' !== $card_shadow ) {
			$css->set_selector( $item )->add_property( 'box-shadow', $card_shadow );

			$shadow_dark = CSS_Helpers::dark( $attrs['cardShadow']['color'] ?? '' );
			if ( '' !== $shadow_dark ) {
				$dark_value = CSS_Helpers::box_shadow( $attrs['cardShadow'], $shadow_dark );
				if ( '' !== $dark_value ) {
					CSS_Helpers::dark_color( $css, $item, 'box-shadow', $dark_value );
				}
			}
		}

		// Wrapper background / shadow / dark-mode branch.
		CSS_Helpers::add_wrapper_base( $css, $wrap, $attrs );
	}
}
