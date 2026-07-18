<?php
declare(strict_types=1);
/**
 * Steps block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one Steps (vertical timeline)
 * instance. Nothing is emitted unless the user picked a value, so an untouched
 * block keeps the theme's typography and the currentColor marker/connector from
 * style.scss. Declarations target the marker / connector / title / description
 * inside the block's own `.flexa-steps-<id>` wrapper.
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
 * Steps CSS generator.
 */
class Steps_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Per-status marker accents: attribute name => marker selector suffix.
	 *
	 * @var array<string, string>
	 */
	private static $status_colors = [
		'doneColor'     => '.is-done',
		'activeColor'   => '.is-active',
		'upcomingColor' => '.is-upcoming',
	];

	/**
	 * Generate CSS for a Steps instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap      = '.flexa-steps-' . $id;
		$item      = $wrap . ' .flexa-steps__item';
		$item_nl   = $wrap . ' .flexa-steps__item:not(:last-child) .flexa-steps__content';
		$marker    = $wrap . ' .flexa-steps__marker';
		$connector = $wrap . ' .flexa-steps__connector';
		$content   = $wrap . ' .flexa-steps__content';
		$title     = $wrap . ' .flexa-steps__title';
		$desc      = $wrap . ' .flexa-steps__description';

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

			// Gap between steps (bottom padding on every step but the last).
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

			// Content text alignment.
			$align = (string) ( $attrs['contentAlign'][ $device ] ?? '' );
			if ( '' !== $align ) {
				$align = CSS_Helpers::sanitize_enum( $align, [ 'left', 'center', 'right' ] );
				if ( '' !== $align ) {
					$css->set_selector( $content )->add_property( 'text-align', $align );
				}
			}

			// Title + description typography.
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

		// Marker background + number/icon colour (light at base, dark under branch).
		$marker_bg_light = CSS_Helpers::light( $attrs['markerColor'] ?? '' );
		if ( '' !== $marker_bg_light ) {
			$css->set_selector( $marker )->add_property( 'background', $marker_bg_light );
		}
		CSS_Helpers::dark_color( $css, $marker, 'background', CSS_Helpers::dark( $attrs['markerColor'] ?? '' ) );

		$marker_text_light = CSS_Helpers::light( $attrs['markerTextColor'] ?? '' );
		if ( '' !== $marker_text_light ) {
			$css->set_selector( $marker )->add_property( 'color', $marker_text_light );
		}
		CSS_Helpers::dark_color( $css, $marker, 'color', CSS_Helpers::dark( $attrs['markerTextColor'] ?? '' ) );

		// Per-status marker accents (background).
		foreach ( self::$status_colors as $attr => $suffix ) {
			$selector = $wrap . ' .flexa-steps__item' . $suffix . ' .flexa-steps__marker';
			$light    = CSS_Helpers::light( $attrs[ $attr ] ?? '' );
			if ( '' !== $light ) {
				$css->set_selector( $selector )->add_property( 'background', $light );
			}
			CSS_Helpers::dark_color( $css, $selector, 'background', CSS_Helpers::dark( $attrs[ $attr ] ?? '' ) );
		}

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

		// Title + description colours (light at base, dark under branch).
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
