<?php
declare(strict_types=1);
/**
 * Product Details block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one product-details instance.
 * Nothing is emitted unless the user picked a value, so an untouched block keeps
 * the theme's typography. Layout/background/border/shadow target the wrapper
 * `.flexa-product-detail-<id>`; tab-title typography / colours / padding target
 * `.flexa-product-detail__tab` (active state on `.is-active`); the gap targets the
 * `.flexa-product-detail__nav`; and content typography / colour / padding target
 * `.flexa-product-detail__content`.
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
 * Product Details CSS generator.
 */
class Product_Detail_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a product-details instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap       = '.flexa-product-detail-' . $id;
		$nav        = $wrap . ' .flexa-product-detail__nav';
		$tab        = $wrap . ' .flexa-product-detail__tab';
		$tab_active = $wrap . ' .flexa-product-detail__tab.is-active';
		$content    = $wrap . ' .flexa-product-detail__content';

		// Reviews-tab elements (WooCommerce markup inside `.flexa-product-detail__reviews`).
		$reviews   = $wrap . ' .flexa-product-detail__reviews';
		$rv_title  = $reviews . ' .woocommerce-Reviews-title';
		$rv_author = $reviews . ' .woocommerce-review__author';
		$rv_date   = $reviews . ' .woocommerce-review__published-date';
		$rv_stars  = $reviews . ' .star-rating';
		$rv_text   = $reviews . ' .description';

		// Additional information tab — the WooCommerce product-attributes table.
		$attr_table = $wrap . ' .woocommerce-product-attributes';
		$attr_label = $attr_table . ' .woocommerce-product-attributes-item__label';
		$attr_value = $attr_table . ' .woocommerce-product-attributes-item__value';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Spacing: padding + margin on the wrapper.
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

			// Border on the wrapper.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_border( $css, $border );
			}

			// Advanced layout (overflow / position / z-index) on the wrapper.
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			// Tab-title typography + padding.
			CSS_Helpers::add_typography( $css, $tab, $attrs['tabTitleTypography'][ $device ] ?? [] );
			$tab_pad = CSS_Helpers::spacing_shorthand( $attrs['tabTitlePadding'][ $device ] ?? [] );
			if ( '' !== $tab_pad ) {
				$css->set_selector( $tab )->add_property( 'padding', $tab_pad );
			}

			// Gap between tab titles on the nav.
			$tab_gap = $attrs['tabGap'][ $device ] ?? [];
			if ( ! empty( $tab_gap['value'] ) ) {
				$css->set_selector( $nav )->add_property( 'column-gap', CSS_Helpers::with_unit( $tab_gap['value'], $tab_gap['unit'] ?? 'px' ) );
			}

			// Content typography + padding.
			CSS_Helpers::add_typography( $css, $content, $attrs['contentTypography'][ $device ] ?? [] );

			// Reviews title typography.
			CSS_Helpers::add_typography( $css, $rv_title, $attrs['reviewsTitleTypography'][ $device ] ?? [] );

			// Additional info table — label / value typography + cell padding.
			CSS_Helpers::add_typography( $css, $attr_label, $attrs['additionalLabelTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $attr_value, $attrs['additionalValueTypography'][ $device ] ?? [] );
			$attr_pad = CSS_Helpers::spacing_shorthand( $attrs['additionalCellPadding'][ $device ] ?? [] );
			if ( '' !== $attr_pad ) {
				$css->set_selector( $attr_label )->add_property( 'padding', $attr_pad );
				$css->set_selector( $attr_value )->add_property( 'padding', $attr_pad );
			}
			$content_pad = CSS_Helpers::spacing_shorthand( $attrs['contentPadding'][ $device ] ?? [] );
			if ( '' !== $content_pad ) {
				$css->set_selector( $content )->add_property( 'padding', $content_pad );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Background + box shadow on the wrapper (base, light).
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

		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $wrap )->add_property( 'box-shadow', $shadow );
		}

		// Tab-title colours (light + dark) — normal + active states.
		CSS_Helpers::color_pair( $css, $tab, 'color', $attrs['tabTitleColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $tab, 'background-color', $attrs['tabTitleBg'] ?? '' );
		CSS_Helpers::color_pair( $css, $tab_active, 'color', $attrs['tabActiveColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $tab_active, 'background-color', $attrs['tabActiveBg'] ?? '' );

		// Content text colour (light + dark).
		CSS_Helpers::color_pair( $css, $content, 'color', $attrs['contentColor'] ?? '' );

		// Reviews-tab element colours (light + dark) — title, author, date, stars, text.
		CSS_Helpers::color_pair( $css, $rv_title, 'color', $attrs['reviewsTitleColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $rv_author, 'color', $attrs['reviewAuthorColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $rv_date, 'color', $attrs['reviewDateColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $rv_stars, 'color', $attrs['reviewStarsColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $rv_text, 'color', $attrs['reviewTextColor'] ?? '' );

		// Additional info table — label / value colours + cell border colour (light + dark).
		CSS_Helpers::color_pair( $css, $attr_label, 'color', $attrs['additionalLabelColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $attr_value, 'color', $attrs['additionalValueColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $attr_label, 'border-color', $attrs['additionalBorderColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $attr_value, 'border-color', $attrs['additionalBorderColor'] ?? '' );

		// Star size — WooCommerce star-rating is font-based, so font-size scales it.
		$stars_size = $attrs['reviewStarsSize'] ?? [];
		if ( ! empty( $stars_size['value'] ) ) {
			$css->set_selector( $rv_stars )->add_property( 'font-size', CSS_Helpers::with_unit( $stars_size['value'], $stars_size['unit'] ?? 'px' ) );
		}

		// Reviews-list pagination (shared with Post Grid / RSS): colours, radius,
		// font-size, alignment for the numbered links and the load-more button.
		CSS_Helpers::add_pagination( $css, $attrs, $wrap . ' .flexa-pagination' );
		CSS_Helpers::add_loadmore( $css, $attrs, $wrap . ' .flexa-pagination-loadmore', $wrap . ' .flexa-pagination-loadmore__btn' );

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
