<?php
declare(strict_types=1);
/**
 * Social Share block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one Social Share instance —
 * both inserter variations (page share and product share) run through here,
 * since they differ only in what the links point at, never in how they look.
 * Nothing is emitted unless the user picked a value, so an untouched block
 * keeps the official brand artwork and the theme's spacing. Colour mode, tint,
 * shape and button background are block-level (uniform across every button),
 * so no per-item :nth-child rules are needed. No `:hover` is emitted — the
 * hover motions live in style.scss keyed by the wrapper modifier.
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
 * Social Share CSS generator.
 */
class Social_Share_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a Social Share instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap  = '.flexa-social-share-' . $id;
		$list  = $wrap . ' .flexa-social-share__list';
		$item  = $wrap . ' .flexa-social-share__item';
		$icon  = $wrap . ' .flexa-social-share__icon';
		$label = $wrap . ' .flexa-social-share__label';

		// The label element only exists when the author turned it on, so its
		// typography would otherwise be dead weight in the stylesheet.
		$show_labels = ! empty( $attrs['showLabels'] );

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			CSS_Helpers::add_wrapper_device( $css, $wrap, $attrs, $device );
			CSS_Helpers::add_social_row( $css, $list, $icon, $attrs, $device );

			if ( $show_labels ) {
				CSS_Helpers::add_typography( $css, $label, $attrs['labelTypography'][ $device ] ?? [] );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Column direction (block-level, no cascade).
		if ( 'column' === ( $attrs['direction'] ?? 'row' ) ) {
			$css->set_selector( $list )->add_property( 'flex-direction', 'column' );
		}

		CSS_Helpers::add_social_colors( $css, $item, $attrs );

		CSS_Helpers::add_wrapper_base( $css, $wrap, $attrs );
	}
}
