<?php
declare(strict_types=1);
/**
 * Counter block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one counter instance. Nothing is
 * emitted unless the user picked a value, so an untouched counter keeps the
 * theme's typography. Declarations target the list / item / icon / number /
 * label / divider inside the block's own `.flexa-counter-<id>` wrapper.
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
 * Counter CSS generator.
 */
class Counter_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map a content-alignment keyword to a flex cross-axis value (the item is a
	 * flex column, so alignment moves its icon/number/label horizontally).
	 *
	 * @var array<string, string>
	 */
	private static $align_items = [
		'left'   => 'flex-start',
		'center' => 'center',
		'right'  => 'flex-end',
	];

	/**
	 * Generate CSS for a counter instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap   = '.flexa-counter-' . $id;
		$list   = $wrap . ' .flexa-counter__list';
		$item   = $wrap . ' .flexa-counter__item';
		$icon   = $wrap . ' .flexa-counter__icon';
		$number = $wrap . ' .flexa-counter__number';
		$label  = $wrap . ' .flexa-counter__label';
		$sep    = $wrap . ' .flexa-counter__separator';

		$show_separator = ! empty( $attrs['showSeparator'] );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Column grid.
			$columns = $attrs['columns'][ $device ] ?? [];
			if ( isset( $columns['value'] ) && '' !== (string) $columns['value'] ) {
				$count = max( 1, (int) $columns['value'] );
				$css->set_selector( $list )->add_property( 'grid-template-columns', 'repeat(' . $count . ', minmax(0, 1fr))' );
			}

			// Gap between stats.
			$gap = $attrs['gap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $list )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Content alignment inside each stat.
			$align = (string) ( $attrs['alignment'][ $device ] ?? '' );
			if ( '' !== $align ) {
				$css->set_selector( $item )
					->add_property( 'align-items', self::$align_items[ $align ] ?? 'center' )
					->add_property( 'text-align', $align );
			}

			// Gap between icon / number / label.
			$content_gap = $attrs['contentGap'][ $device ] ?? [];
			if ( ! empty( $content_gap['value'] ) ) {
				$css->set_selector( $item )->add_property( 'gap', CSS_Helpers::with_unit( $content_gap['value'], $content_gap['unit'] ?? 'px' ) );
			}

			// Icon size.
			$icon_size = $attrs['iconSize'][ $device ] ?? [];
			if ( ! empty( $icon_size['value'] ) ) {
				$value = CSS_Helpers::with_unit( $icon_size['value'], $icon_size['unit'] ?? 'px' );
				$css->set_selector( $icon )->add_property( 'width', $value )->add_property( 'height', $value );
			}

			// Number + label typography.
			CSS_Helpers::add_typography( $css, $number, $attrs['numberTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $label, $attrs['labelTypography'][ $device ] ?? [] );

			// Divider width + thickness (only when the divider is on).
			if ( $show_separator ) {
				$sep_width = $attrs['separatorWidth'][ $device ] ?? [];
				if ( ! empty( $sep_width['value'] ) ) {
					$css->set_selector( $sep )->add_property( 'width', CSS_Helpers::with_unit( $sep_width['value'], $sep_width['unit'] ?? 'px' ) );
				}
				$sep_weight = $attrs['separatorWeight'][ $device ] ?? [];
				if ( ! empty( $sep_weight['value'] ) ) {
					$css->set_selector( $sep )->add_property( 'border-top-width', CSS_Helpers::with_unit( $sep_weight['value'], $sep_weight['unit'] ?? 'px' ) );
				}
			}

			// Item box padding + corner radius.
			$padding = CSS_Helpers::spacing_shorthand( $attrs['itemPadding'][ $device ] ?? [] );
			if ( '' !== $padding ) {
				$css->set_selector( $item )->add_property( 'padding', $padding );
			}
			$radius = $attrs['itemBorderRadius'][ $device ] ?? [];
			if ( ! empty( $radius['value'] ) ) {
				$css->set_selector( $item )->add_property( 'border-radius', CSS_Helpers::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
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

			CSS_Helpers::close_device( $css, $device );
		}

		// Colours (light at the base, dark under the dark-mode branch).
		$number_light = CSS_Helpers::light( $attrs['numberColor'] ?? '' );
		if ( '' !== $number_light ) {
			$css->set_selector( $number )->add_property( 'color', $number_light );
		}
		CSS_Helpers::dark_color( $css, $number, 'color', CSS_Helpers::dark( $attrs['numberColor'] ?? '' ) );

		$label_light = CSS_Helpers::light( $attrs['labelColor'] ?? '' );
		if ( '' !== $label_light ) {
			$css->set_selector( $label )->add_property( 'color', $label_light );
		}
		CSS_Helpers::dark_color( $css, $label, 'color', CSS_Helpers::dark( $attrs['labelColor'] ?? '' ) );

		$icon_light = CSS_Helpers::light( $attrs['iconColor'] ?? '' );
		if ( '' !== $icon_light ) {
			$css->set_selector( $icon )->add_property( 'color', $icon_light );
		}
		CSS_Helpers::dark_color( $css, $icon, 'color', CSS_Helpers::dark( $attrs['iconColor'] ?? '' ) );

		$item_bg_light = CSS_Helpers::light( $attrs['itemBackground'] ?? '' );
		if ( '' !== $item_bg_light ) {
			$css->set_selector( $item )->add_property( 'background', $item_bg_light );
		}
		CSS_Helpers::dark_color( $css, $item, 'background', CSS_Helpers::dark( $attrs['itemBackground'] ?? '' ) );

		// Divider style + colour (only when the divider is on).
		if ( $show_separator ) {
			$style = CSS_Helpers::sanitize_enum( (string) ( $attrs['separatorStyle'] ?? 'solid' ), [ 'solid', 'dashed', 'dotted', 'double' ], 'solid' );
			$css->set_selector( $sep )->add_property( 'border-top-style', $style );

			$sep_light = CSS_Helpers::light( $attrs['separatorColor'] ?? '' );
			if ( '' !== $sep_light ) {
				$css->set_selector( $sep )->add_property( 'border-top-color', $sep_light );
			}
			CSS_Helpers::dark_color( $css, $sep, 'border-top-color', CSS_Helpers::dark( $attrs['separatorColor'] ?? '' ) );
		}

		// Wrapper background + box shadow (base, light).
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

		// Dark mode for the wrapper background / border / shadow.
		self::add_wrapper_dark( $css, $wrap, $attrs, $background );
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
