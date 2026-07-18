<?php
declare(strict_types=1);
/**
 * Timeline block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one Timeline instance. Nothing is
 * emitted unless the user picked a value, so an untouched block keeps the theme's
 * typography and the currentColor marker/connector from style.scss. Declarations
 * target the marker / connector / date / title / description inside the block's
 * own `.flexa-timeline-<id>` wrapper.
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
 * Timeline CSS generator.
 */
class Timeline_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a Timeline instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap      = '.flexa-timeline-' . $id;
		$item      = $wrap . ' .flexa-timeline__item';
		$item_nl   = $wrap . ' .flexa-timeline__item:not(:last-child) .flexa-timeline__content';
		$marker    = $wrap . ' .flexa-timeline__marker';
		$connector = $wrap . ' .flexa-timeline__connector';
		$content   = $wrap . ' .flexa-timeline__content';
		$card      = $wrap . ' .flexa-timeline__card';
		$image     = $wrap . ' .flexa-timeline__image';
		$date      = $wrap . ' .flexa-timeline__date';
		$title     = $wrap . ' .flexa-timeline__title';
		$desc      = $wrap . ' .flexa-timeline__description';

		// The connector is hidden only when the user explicitly turns it off.
		$connector_hidden = array_key_exists( 'connectorShow', $attrs ) && false === $attrs['connectorShow'];

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Timeline max width.
			$max = $attrs['maxWidth'][ $device ] ?? [];
			if ( ! empty( $max['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'max-width', CSS_Helpers::with_unit( $max['value'], $max['unit'] ?? 'px' ) );
			}

			// Gap between marker and content.
			$marker_gap = $attrs['markerGap'][ $device ] ?? [];
			if ( ! empty( $marker_gap['value'] ) ) {
				$css->set_selector( $item )->add_property( 'gap', CSS_Helpers::with_unit( $marker_gap['value'], $marker_gap['unit'] ?? 'px' ) );
			}

			// Gap between events (bottom padding on every entry but the last).
			$item_gap = $attrs['itemGap'][ $device ] ?? [];
			if ( ! empty( $item_gap['value'] ) ) {
				$css->set_selector( $item_nl )->add_property( 'padding-bottom', CSS_Helpers::with_unit( $item_gap['value'], $item_gap['unit'] ?? 'px' ) );
			}

			// Marker size.
			$marker_size = $attrs['markerSize'][ $device ] ?? [];
			if ( ! empty( $marker_size['value'] ) ) {
				$value = CSS_Helpers::with_unit( $marker_size['value'], $marker_size['unit'] ?? 'px' );
				$css->set_selector( $marker )->add_property( 'width', $value )->add_property( 'height', $value );
			}

			// Connector thickness (skipped when the connector is off).
			if ( ! $connector_hidden ) {
				$conn_width = $attrs['connectorWidth'][ $device ] ?? [];
				if ( ! empty( $conn_width['value'] ) ) {
					$css->set_selector( $connector )->add_property( 'border-left-width', CSS_Helpers::with_unit( $conn_width['value'], $conn_width['unit'] ?? 'px' ) );
				}
			}

			// Per-entry image width.
			$img_w = $attrs['imageWidth'][ $device ] ?? [];
			if ( ! empty( $img_w['value'] ) ) {
				$css->set_selector( $image )->add_property( 'width', CSS_Helpers::with_unit( $img_w['value'], $img_w['unit'] ?? '%' ) );
			}

			// Content text alignment.
			$align = (string) ( $attrs['contentAlign'][ $device ] ?? '' );
			if ( '' !== $align ) {
				$align = CSS_Helpers::sanitize_enum( $align, [ 'left', 'center', 'right' ] );
				if ( '' !== $align ) {
					$css->set_selector( $content )->add_property( 'text-align', $align );
				}
			}

			// Date + title + description typography.
			CSS_Helpers::add_typography( $css, $date, $attrs['dateTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $title, $attrs['titleTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $desc, $attrs['descriptionTypography'][ $device ] ?? [] );

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

			// Inner padding of each event's card.
			$card_pad = CSS_Helpers::spacing_shorthand( $attrs['cardPadding'][ $device ] ?? [] );
			if ( '' !== $card_pad ) {
				$css->set_selector( $card )->add_property( 'padding', $card_pad );
			}

			// Border on each event's card (wraps the text/image; excludes the gap).
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $card );
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

		// Image horizontal alignment (non-responsive) via auto margins. Left is the
		// natural default and emits nothing.
		$image_align = (string) ( $attrs['imageAlign'] ?? '' );
		if ( 'center' === $image_align ) {
			$css->set_selector( $image )->add_property( 'margin-left', 'auto' )->add_property( 'margin-right', 'auto' );
		} elseif ( 'right' === $image_align ) {
			$css->set_selector( $image )->add_property( 'margin-left', 'auto' )->add_property( 'margin-right', '0' );
		}

		// Marker background + icon colour (light at base, dark under branch).
		$marker_bg_light = CSS_Helpers::light( $attrs['markerColor'] ?? '' );
		if ( '' !== $marker_bg_light ) {
			$css->set_selector( $marker )->add_property( 'background', $marker_bg_light );
		}
		CSS_Helpers::dark_color( $css, $marker, 'background', CSS_Helpers::dark( $attrs['markerColor'] ?? '' ) );

		$marker_icon_light = CSS_Helpers::light( $attrs['markerIconColor'] ?? '' );
		if ( '' !== $marker_icon_light ) {
			$css->set_selector( $marker )->add_property( 'color', $marker_icon_light );
		}
		CSS_Helpers::dark_color( $css, $marker, 'color', CSS_Helpers::dark( $attrs['markerIconColor'] ?? '' ) );

		// Connector line: hide it, or emit its style + colour.
		if ( $connector_hidden ) {
			$css->set_selector( $connector )->add_property( 'display', 'none' );
		} else {
			if ( ! empty( $attrs['connectorStyle'] ) ) {
				$style = CSS_Helpers::sanitize_enum( (string) $attrs['connectorStyle'], [ 'solid', 'dashed', 'dotted' ] );
				if ( '' !== $style ) {
					$css->set_selector( $connector )->add_property( 'border-left-style', $style );
				}
			}
			$conn_light = CSS_Helpers::light( $attrs['connectorColor'] ?? '' );
			if ( '' !== $conn_light ) {
				$css->set_selector( $connector )->add_property( 'border-left-color', $conn_light );
			}
			CSS_Helpers::dark_color( $css, $connector, 'border-left-color', CSS_Helpers::dark( $attrs['connectorColor'] ?? '' ) );
		}

		// Date + title + description colours (light at base, dark under branch).
		$date_light = CSS_Helpers::light( $attrs['dateColor'] ?? '' );
		if ( '' !== $date_light ) {
			$css->set_selector( $date )->add_property( 'color', $date_light );
		}
		CSS_Helpers::dark_color( $css, $date, 'color', CSS_Helpers::dark( $attrs['dateColor'] ?? '' ) );

		$title_light = CSS_Helpers::light( $attrs['titleColor'] ?? '' );
		if ( '' !== $title_light ) {
			$css->set_selector( $title )->add_property( 'color', $title_light );
		}
		CSS_Helpers::dark_color( $css, $title, 'color', CSS_Helpers::dark( $attrs['titleColor'] ?? '' ) );

		$desc_light = CSS_Helpers::light( $attrs['descriptionColor'] ?? '' );
		if ( '' !== $desc_light ) {
			$css->set_selector( $desc )->add_property( 'color', $desc_light );
		}
		CSS_Helpers::dark_color( $css, $desc, 'color', CSS_Helpers::dark( $attrs['descriptionColor'] ?? '' ) );

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
			$css->set_selector( $card )->add_property( 'box-shadow', $shadow );
		}

		// Dark mode: background on the wrapper; border colour + shadow on each card.
		self::add_wrapper_dark( $css, $wrap, $card, $attrs, $background );
	}

	/**
	 * Dark-mode declarations. Background dark goes on the wrapper; the border
	 * colour and box shadow are dark-branched on each event's content card (they
	 * are emitted per card in the light base too).
	 *
	 * @param CSS_Builder $css        Builder.
	 * @param string      $wrap       Wrapper selector.
	 * @param string      $content    Per-entry content-card selector.
	 * @param array       $attrs      Attributes.
	 * @param array       $background Background attribute.
	 */
	private static function add_wrapper_dark( $css, $wrap, $content, $attrs, $background ) {
		// Wrapper background (dark).
		CSS_Helpers::add_dark_mode(
			$css,
			$wrap,
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

		// Per-card border colour + shadow (dark).
		CSS_Helpers::add_dark_mode(
			$css,
			$content,
			function ( $css ) use ( $attrs ) {
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
