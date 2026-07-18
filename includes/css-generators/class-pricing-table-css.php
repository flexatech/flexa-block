<?php
declare(strict_types=1);
/**
 * Pricing Table block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one pricing-table instance.
 * Nothing is emitted unless the user picked a value, so an untouched table keeps
 * the theme's typography and the structural defaults from style.scss. Every
 * declaration targets the grid / plan / name / price / feature / badge / button
 * parts inside the block's own `.flexa-pricing-table-<id>` wrapper.
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
 * Pricing Table CSS generator.
 */
class Pricing_Table_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a pricing-table instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap    = '.flexa-pricing-table-' . $id;
		$grid    = $wrap . ' .flexa-pricing-table__grid';
		$plan    = $wrap . ' .flexa-pricing-table__plan';
		$plan_hi = $wrap . ' .flexa-pricing-table__plan.is-highlighted';
		$badge   = $wrap . ' .flexa-pricing-table__badge';
		$name    = $wrap . ' .flexa-pricing-table__name';
		$amount  = $wrap . ' .flexa-pricing-table__amount';
		$period  = $wrap . ' .flexa-pricing-table__period';
		$feature = $wrap . ' .flexa-pricing-table__feature';
		$billing = $wrap . ' .flexa-pricing-table__billing';
		$option  = $billing . ' .flexa-pricing-table__billing-option';

		$is_boxed = 'boxed' === ( $attrs['containerType'] ?? 'full-width' );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Width: max-width when boxed, width when full-width.
			$width = ( $is_boxed ? ( $attrs['widthBoxed'][ $device ] ?? [] ) : ( $attrs['widthFullWidth'][ $device ] ?? [] ) );
			if ( ! empty( $width['value'] ) ) {
				$css->set_selector( $wrap )->add_property(
					$is_boxed ? 'max-width' : 'width',
					CSS_Helpers::with_unit( $width['value'], $width['unit'] ?? ( $is_boxed ? 'px' : '%' ) )
				);
			}

			// Wrapper spacing: padding + margin.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			if ( ! empty( $spacing['padding'] ) ) {
				$pad = CSS_Helpers::spacing_shorthand( $spacing['padding'] );
				if ( '' !== $pad ) {
					$css->set_selector( $wrap )->add_property( 'padding', $pad );
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

			// Advanced layout (overflow / position / z-index) on the wrapper.
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			// Plan grid: column count + gap.
			$columns = $attrs['columns'][ $device ] ?? [];
			if ( isset( $columns['value'] ) && '' !== (string) $columns['value'] ) {
				$count = max( 1, (int) $columns['value'] );
				$css->set_selector( $grid )->add_property( 'grid-template-columns', 'repeat(' . $count . ', minmax(0, 1fr))' );
			}
			$gap = $attrs['gap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $grid )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Typography.
			CSS_Helpers::add_typography( $css, $name, $attrs['nameTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $amount, $attrs['priceTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $feature, $attrs['featureTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $badge, $attrs['badgeTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $option, $attrs['billingTypography'][ $device ] ?? [] );

			CSS_Helpers::close_device( $css, $device );
		}

		// --- Colours (light at the base, dark under the dark-mode branch). ---
		$name_light = CSS_Helpers::light( $attrs['nameColor'] ?? '' );
		if ( '' !== $name_light ) {
			$css->set_selector( $name )->add_property( 'color', $name_light );
		}
		CSS_Helpers::dark_color( $css, $name, 'color', CSS_Helpers::dark( $attrs['nameColor'] ?? '' ) );

		$price_light = CSS_Helpers::light( $attrs['priceColor'] ?? '' );
		if ( '' !== $price_light ) {
			$css->set_selector( $amount )->add_property( 'color', $price_light );
		}
		CSS_Helpers::dark_color( $css, $amount, 'color', CSS_Helpers::dark( $attrs['priceColor'] ?? '' ) );

		$period_light = CSS_Helpers::light( $attrs['periodColor'] ?? '' );
		if ( '' !== $period_light ) {
			$css->set_selector( $period )->add_property( 'color', $period_light );
		}
		CSS_Helpers::dark_color( $css, $period, 'color', CSS_Helpers::dark( $attrs['periodColor'] ?? '' ) );

		$feature_light = CSS_Helpers::light( $attrs['featureColor'] ?? '' );
		if ( '' !== $feature_light ) {
			$css->set_selector( $feature )->add_property( 'color', $feature_light );
		}
		CSS_Helpers::dark_color( $css, $feature, 'color', CSS_Helpers::dark( $attrs['featureColor'] ?? '' ) );

		// Plan cards: the border + shadow every card wears.
		$plan_border = $attrs['planBorder'] ?? [];
		self::add_card_chrome( $css, $plan, $plan_border, $attrs['planBoxShadow'] ?? [] );

		// Highlighted card: the same chrome, with its own overrides laid on top —
		// a border field left empty keeps the plan-card value. Its selector carries
		// the extra `.is-highlighted` class, so these always win over the rules above.
		$hi_border = self::merge_border( $plan_border, $attrs['highlightBorder'] ?? [] );
		$hi_shadow = ! empty( $attrs['highlightBoxShadow']['enabled'] ) ? $attrs['highlightBoxShadow'] : ( $attrs['planBoxShadow'] ?? [] );
		self::add_card_chrome( $css, $plan_hi, $hi_border, $hi_shadow );

		// Highlight accent — outlines the highlighted plan card, overriding any
		// border colour picked above.
		$hi_light = CSS_Helpers::light( $attrs['highlightColor'] ?? '' );
		if ( '' !== $hi_light ) {
			$css->set_selector( $plan_hi )->add_property( 'border-color', $hi_light );
		}
		$hi_dark = CSS_Helpers::dark( $attrs['highlightColor'] ?? '' );
		CSS_Helpers::dark_color( $css, $plan_hi, 'border-color', $hi_dark );

		// Billing switch (labels, track, sliding pill).
		self::add_billing( $css, $attrs, $billing, $option );

		// Badge background — its own colour wins; otherwise it inherits the accent.
		$badge_bg_light = CSS_Helpers::light( $attrs['badgeBackground'] ?? '' );
		$badge_bg_dark  = CSS_Helpers::dark( $attrs['badgeBackground'] ?? '' );
		$badge_bg_light = '' !== $badge_bg_light ? $badge_bg_light : $hi_light;
		$badge_bg_dark  = '' !== $badge_bg_dark ? $badge_bg_dark : $hi_dark;
		if ( '' !== $badge_bg_light ) {
			$css->set_selector( $badge )->add_property( 'background-color', $badge_bg_light );
		}
		CSS_Helpers::dark_color( $css, $badge, 'background-color', $badge_bg_dark );

		// Badge text colour.
		$badge_text_light = CSS_Helpers::light( $attrs['badgeColor'] ?? '' );
		if ( '' !== $badge_text_light ) {
			$css->set_selector( $badge )->add_property( 'color', $badge_text_light );
		}
		CSS_Helpers::dark_color( $css, $badge, 'color', CSS_Helpers::dark( $attrs['badgeColor'] ?? '' ) );

		// Plan CTA button (block-level, base + hover + dark).
		self::add_button( $css, $attrs, $wrap . ' .flexa-pricing-table__cta' );

		// Wrapper background + box shadow (base, light) and their dark branch.
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

		self::add_wrapper_dark( $css, $wrap, $attrs, $background );
	}

	/**
	 * Lay a border override on top of a base border, field by field: a field left
	 * empty keeps the base value. Lets the highlighted card differ from the plan
	 * card in only the parts the user touched.
	 *
	 * @param array $base     Plan-card border.
	 * @param array $override Highlighted-card border.
	 * @return array
	 */
	private static function merge_border( $base, $override ) {
		$merged = $base;
		if ( ! empty( $override['style'] ) ) {
			$merged['style'] = $override['style'];
		}
		if ( '' !== (string) ( $override['width']['top'] ?? '' ) ) {
			$merged['width'] = $override['width'];
		}
		if ( '' !== CSS_Helpers::light( $override['color'] ?? '' ) || '' !== CSS_Helpers::dark( $override['color'] ?? '' ) ) {
			$merged['color'] = $override['color'];
		}
		if ( '' !== (string) ( $override['radius']['topLeft'] ?? '' ) ) {
			$merged['radius'] = $override['radius'];
		}
		return $merged;
	}

	/**
	 * Emit one plan card's border + box shadow (light base, dark branch). Nothing
	 * is emitted unless the user set a value, so an untouched card keeps the
	 * hairline border from style.scss.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Card selector.
	 * @param array       $border   Border attribute.
	 * @param array       $shadow   Box-shadow attribute.
	 */
	private static function add_card_chrome( $css, $selector, $border, $shadow ) {
		if ( ! empty( $border ) && is_array( $border ) ) {
			$css->set_selector( $selector );
			CSS_Helpers::add_border( $css, $border );
			CSS_Helpers::dark_color( $css, $selector, 'border-color', CSS_Helpers::dark( $border['color'] ?? '' ) );
		}

		$value = CSS_Helpers::box_shadow( $shadow );
		if ( '' !== $value ) {
			$css->set_selector( $selector )->add_property( 'box-shadow', $value );

			$shadow_dark = CSS_Helpers::dark( $shadow['color'] ?? '' );
			if ( '' !== $shadow_dark ) {
				CSS_Helpers::dark_color( $css, $selector, 'box-shadow', CSS_Helpers::box_shadow( $shadow, $shadow_dark ) );
			}
		}
	}

	/**
	 * Emit the billing switch styling: placement, track fill + radius, the option
	 * labels' colour and the selected option's colour + fill. The selected fill
	 * paints both the sliding pill (once view.js measures it) and the no-JS
	 * `.is-active` fallback, so one attribute covers both states.
	 *
	 * @param CSS_Builder $css     Builder.
	 * @param array       $attrs   Attributes.
	 * @param string      $billing Switch selector.
	 * @param string      $option  Billing-option selector.
	 */
	private static function add_billing( $css, $attrs, $billing, $option ) {
		if ( empty( $attrs['billingToggle'] ) ) {
			return;
		}

		$active    = $option . '.is-active';
		$indicator = $billing . ' .flexa-pricing-table__billing-indicator';
		// The static fill is only drawn before view.js measures the pill (style.scss
		// drops it once `is-indicator-ready` lands) — scope its colour the same way
		// so the two are never painted at once.
		$active_fallback = $billing . ':not(.is-indicator-ready) .flexa-pricing-table__billing-option.is-active';

		$align = CSS_Helpers::sanitize_enum( $attrs['billingAlign'] ?? '', [ 'flex-start', 'center', 'flex-end' ] );
		if ( '' !== $align ) {
			$css->set_selector( $billing )->add_property( 'align-self', $align );
		}

		$track_light = CSS_Helpers::light( $attrs['billingBackground'] ?? '' );
		if ( '' !== $track_light ) {
			$css->set_selector( $billing )->add_property( 'background-color', $track_light );
		}
		CSS_Helpers::dark_color( $css, $billing, 'background-color', CSS_Helpers::dark( $attrs['billingBackground'] ?? '' ) );

		// One radius for the track, the options and the pill so they stay concentric.
		$radius = $attrs['billingRadius'] ?? [];
		if ( ! empty( $radius['value'] ) ) {
			$value = CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' );
			foreach ( [ $billing, $option, $indicator ] as $selector ) {
				$css->set_selector( $selector )->add_property( 'border-radius', $value );
			}
		}

		$label_light = CSS_Helpers::light( $attrs['billingColor'] ?? '' );
		if ( '' !== $label_light ) {
			$css->set_selector( $option )->add_property( 'color', $label_light );
		}
		CSS_Helpers::dark_color( $css, $option, 'color', CSS_Helpers::dark( $attrs['billingColor'] ?? '' ) );

		$active_light = CSS_Helpers::light( $attrs['billingActiveColor'] ?? '' );
		if ( '' !== $active_light ) {
			$css->set_selector( $active )->add_property( 'color', $active_light );
		}
		CSS_Helpers::dark_color( $css, $active, 'color', CSS_Helpers::dark( $attrs['billingActiveColor'] ?? '' ) );

		$fill_light = CSS_Helpers::light( $attrs['billingActiveBackground'] ?? '' );
		$fill_dark  = CSS_Helpers::dark( $attrs['billingActiveBackground'] ?? '' );
		foreach ( [ $indicator, $active_fallback ] as $selector ) {
			if ( '' !== $fill_light ) {
				$css->set_selector( $selector )->add_property( 'background-color', $fill_light );
			}
			CSS_Helpers::dark_color( $css, $selector, 'background-color', $fill_dark );
		}
	}

	/**
	 * Emit the plan CTA button styling (base + :hover + dark). One appearance
	 * covers every plan's CTA. Nothing is emitted unless the user set a value, so
	 * an untouched button keeps its `wp-element-button` theme styling.
	 *
	 * @param CSS_Builder $css   Builder.
	 * @param array       $attrs Attributes.
	 * @param string      $cta   CTA button selector.
	 */
	private static function add_button( $css, $attrs, $cta ) {
		$hover = $cta . ':hover';

		// Text colour (base + hover).
		$text_light = CSS_Helpers::light( $attrs['buttonTextColor'] ?? '' );
		if ( '' !== $text_light ) {
			$css->set_selector( $cta )->add_property( 'color', $text_light );
		}
		CSS_Helpers::dark_color( $css, $cta, 'color', CSS_Helpers::dark( $attrs['buttonTextColor'] ?? '' ) );

		$text_hover_light = CSS_Helpers::light( $attrs['buttonTextColorHover'] ?? '' );
		if ( '' !== $text_hover_light ) {
			$css->set_selector( $hover )->add_property( 'color', $text_hover_light );
		}
		CSS_Helpers::dark_color( $css, $hover, 'color', CSS_Helpers::dark( $attrs['buttonTextColorHover'] ?? '' ) );

		// Background — a solid colour or a gradient (base + hover), each light + dark.
		CSS_Helpers::add_bg_fill(
			$css,
			$cta,
			$attrs['buttonBackgroundType'] ?? 'color',
			$attrs['buttonBackground'] ?? '',
			$attrs['buttonBackgroundGradient'] ?? ''
		);
		CSS_Helpers::add_bg_fill(
			$css,
			$hover,
			$attrs['buttonBackgroundHoverType'] ?? 'color',
			$attrs['buttonBackgroundHover'] ?? '',
			$attrs['buttonBackgroundHoverGradient'] ?? ''
		);

		// Corner radius.
		$radius = $attrs['buttonRadius'] ?? [];
		if ( ! empty( $radius['value'] ) ) {
			$css->set_selector( $cta )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
		}

		// Padding.
		$padding = CSS_Helpers::spacing_shorthand( $attrs['buttonPadding'] ?? [] );
		if ( '' !== $padding ) {
			$css->set_selector( $cta )->add_property( 'padding', $padding );
		}

		// Full-width mode.
		if ( 'full' === ( $attrs['buttonWidth'] ?? 'auto' ) ) {
			$css->set_selector( $cta )
				->add_property( 'display', 'block' )
				->add_property( 'width', '100%' )
				->add_property( 'text-align', 'center' );
		}
	}

	/**
	 * Dark-mode declarations for the wrapper's background, border colour and box
	 * shadow (gathered under one dark-mode branch).
	 *
	 * @param CSS_Builder $css        Builder.
	 * @param string      $wrap       Wrapper selector.
	 * @param array       $attrs      Attributes.
	 * @param array       $background Background attribute.
	 */
	private static function add_wrapper_dark( $css, $wrap, $attrs, $background ) {
		CSS_Helpers::add_dark_mode(
			$css,
			$wrap,
			function ( $css ) use ( $attrs, $background ) {
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

				$border_dark = CSS_Helpers::sanitize_color( CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );
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
