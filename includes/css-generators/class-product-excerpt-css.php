<?php
declare(strict_types=1);
/**
 * Product Excerpt block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one product-excerpt instance.
 * Nothing is emitted unless the user picked a value, so an untouched block keeps
 * the theme's typography. The wrapper `.flexa-product-excerpt-<id>` carries the
 * foundation (alignment / spacing / border / background / shadow); typography,
 * colour and the line clamp target the inner `__content`, and links inside the
 * stored excerpt are recoloured through a descendant selector off that box.
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
 * Product Excerpt CSS generator.
 */
class Product_Excerpt_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a product-excerpt instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap       = '.flexa-product-excerpt-' . $id;
		$content    = $wrap . ' .flexa-product-excerpt__content';
		$link       = $content . ' a';
		$link_hover = $link . ':hover';

		$clamped = ! empty( $attrs['enableClamp'] );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Foundation: alignment, padding / margin, border, advanced layout.
			CSS_Helpers::add_wrapper_device( $css, $wrap, $attrs, $device );

			// Body typography on the excerpt box.
			CSS_Helpers::add_typography( $css, $content, $attrs['typography'][ $device ] ?? [] );

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

		// Body colour and the link colours (light at base, dark branch).
		CSS_Helpers::color_pair( $css, $content, 'color', $attrs['textColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $link, 'color', $attrs['linkColor'] ?? '' );
		CSS_Helpers::color_pair( $css, $link_hover, 'color', $attrs['linkColorHover'] ?? '' );

		// Foundation: background, box shadow and the wrapper's dark-mode branch.
		CSS_Helpers::add_wrapper_base( $css, $wrap, $attrs );
	}
}
