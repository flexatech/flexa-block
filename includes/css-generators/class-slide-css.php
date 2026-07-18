<?php
declare(strict_types=1);
/**
 * Slide block — server-side CSS generator (child of flexa/slides).
 *
 * Produces responsive, dark-mode-aware CSS for one slide's content box: padding
 * and margin, vertical/horizontal content alignment, the gap between inner
 * blocks, the content max-width and the slide background. Nothing is emitted
 * unless the user picked a value.
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
 * Slide CSS generator.
 */
class Slide_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a slide instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$slide   = '.flexa-slide-' . $id;
		$content = $slide . ' > .flexa-slide__content';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Spacing on the slide box.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			if ( ! empty( $spacing['padding'] ) ) {
				$padding = CSS_Helpers::spacing_shorthand( $spacing['padding'] );
				if ( '' !== $padding ) {
					$css->set_selector( $slide )->add_property( 'padding', $padding );
				}
			}
			if ( ! empty( $spacing['margin'] ) ) {
				$margin = CSS_Helpers::spacing_shorthand( $spacing['margin'] );
				if ( '' !== $margin ) {
					$css->set_selector( $slide )->add_property( 'margin', $margin );
				}
			}

			// Content alignment on the slide (a column flex box).
			$layout = $attrs['layout'][ $device ] ?? [];
			if ( ! empty( $layout['justifyContent'] ) ) {
				$justify = CSS_Helpers::sanitize_enum( $layout['justifyContent'], [ 'flex-start', 'center', 'flex-end', 'space-between', 'space-around' ] );
				if ( '' !== $justify ) {
					$css->set_selector( $slide )->add_property( 'justify-content', $justify );
				}
			}
			if ( ! empty( $layout['alignItems'] ) ) {
				$align = CSS_Helpers::sanitize_enum( $layout['alignItems'], [ 'flex-start', 'center', 'flex-end', 'stretch' ] );
				if ( '' !== $align ) {
					$css->set_selector( $slide )->add_property( 'align-items', $align );
				}
			}

			// Gap between inner blocks + content max-width on the content wrapper.
			$gap = $layout['gap'] ?? [];
			if ( is_array( $gap ) ) {
				$unit = $gap['unit'] ?? 'px';
				if ( isset( $gap['row'] ) && '' !== (string) $gap['row'] ) {
					$css->set_selector( $content )->add_property( 'row-gap', CSS_Helpers::with_unit( $gap['row'], $unit ) );
				}
				if ( isset( $gap['column'] ) && '' !== (string) $gap['column'] ) {
					$css->set_selector( $content )->add_property( 'column-gap', CSS_Helpers::with_unit( $gap['column'], $unit ) );
				}
			}

			$max_width = $attrs['contentMaxWidth'][ $device ] ?? [];
			if ( ! empty( $max_width['value'] ) ) {
				$css->set_selector( $content )->add_property( 'max-width', CSS_Helpers::with_unit( $max_width['value'], $max_width['unit'] ?? 'px' ) );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Background on the slide (base + dark).
		$background = $attrs['background'] ?? [];
		if ( ! empty( $background ) ) {
			$css->set_selector( $slide );
			CSS_Helpers::add_background( $css, $background );
		}

		CSS_Helpers::add_dark_mode(
			$css,
			$slide,
			function ( $css ) use ( $background ) {
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
			}
		);
	}
}
