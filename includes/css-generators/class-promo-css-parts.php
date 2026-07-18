<?php
declare(strict_types=1);
/**
 * Shared "promo content" CSS parts — heading, description, CTA buttons and the
 * content alignment / gap / max-width used by both the Banner and CTA blocks.
 *
 * This is NOT a block generator (it has no BASE_BLOCKS entry); it is a helper the
 * Banner_CSS and Cta_CSS generators call so the identical promo declarations
 * aren't copied between them (guide §4.5). It lives in css-generators/ purely so
 * the directory glob auto-loads it. Mirror of the editor-side PromoContent
 * preview builders.
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
 * Promo content CSS helper.
 */
class Promo_CSS_Parts {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Map a content alignment to its flex cross-axis placement.
	 *
	 * @param string $align left|center|right.
	 * @return string align-items value.
	 */
	private static function align_items( $align ) {
		$map = [
			'left'   => 'flex-start',
			'center' => 'center',
			'right'  => 'flex-end',
		];
		return $map[ $align ] ?? 'center';
	}

	/**
	 * Emit a colour property: light at the base, dark under the dark branch.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param string      $property CSS property (color / background-color).
	 * @param mixed       $color    Colour pair.
	 */
	private static function add_color( $css, $selector, $property, $color ) {
		$light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $color ) );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( $property, $light );
		}
		CSS_Helpers::dark_color( $css, $selector, $property, CSS_Helpers::dark( $color ) );
	}

	/**
	 * Emit the shared promo declarations for one instance.
	 *
	 * @param CSS_Builder $css        Shared builder.
	 * @param string      $wrap       Block wrapper selector (e.g. `.flexa-banner-<id>`).
	 * @param array       $attrs      Block attributes (PromoContentAttributes shape).
	 * @param bool        $with_align When false, skip the content alignment (the CTA
	 *                                split layout places the text/buttons itself).
	 */
	public static function emit( $css, $wrap, $attrs, $with_align = true ) {
		$content = $wrap . ' .flexa-promo__content';
		$heading = $wrap . ' .flexa-promo__heading';
		$desc    = $wrap . ' .flexa-promo__description';
		$btn_p   = $wrap . ' .flexa-promo__button--primary';
		$btn_ph  = $btn_p . ':hover';
		$btn_s   = $wrap . ' .flexa-promo__button--secondary';
		$btn_sh  = $btn_s . ':hover';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Content alignment (text-align cascades to the text; align-items places
			// the text and buttons groups in the column).
			if ( $with_align ) {
				$align = (string) ( $attrs['contentAlign'][ $device ] ?? '' );
				if ( '' !== $align ) {
					$css->set_selector( $content )->add_property( 'text-align', $align );
					$css->set_selector( $content )->add_property( 'align-items', self::align_items( $align ) );
				}
			}

			// Gap between the text and buttons groups.
			$gap = $attrs['contentGap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $content )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			// Content max-width.
			$max = $attrs['contentMaxWidth'][ $device ] ?? [];
			if ( ! empty( $max['value'] ) ) {
				$css->set_selector( $content )->add_property( 'max-width', CSS_Helpers::with_unit( $max['value'], $max['unit'] ?? 'px' ) );
			}

			// Typography on the heading + description.
			CSS_Helpers::add_typography( $css, $heading, $attrs['headingTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $desc, $attrs['descriptionTypography'][ $device ] ?? [] );

			CSS_Helpers::close_device( $css, $device );
		}

		// Text colours (light at base, dark under the dark branch).
		self::add_color( $css, $heading, 'color', $attrs['headingColor'] ?? '' );
		self::add_color( $css, $desc, 'color', $attrs['descriptionColor'] ?? '' );

		// Primary button colours + hover.
		self::add_color( $css, $btn_p, 'color', $attrs['primaryTextColor'] ?? '' );
		self::add_color( $css, $btn_p, 'background-color', $attrs['primaryBgColor'] ?? '' );
		$p_hover = $attrs['primaryHover'] ?? [];
		self::add_color( $css, $btn_ph, 'color', $p_hover['text'] ?? '' );
		self::add_color( $css, $btn_ph, 'background-color', $p_hover['background'] ?? '' );

		// Secondary button colours + hover — only when shown.
		if ( ! empty( $attrs['showSecondary'] ) ) {
			self::add_color( $css, $btn_s, 'color', $attrs['secondaryTextColor'] ?? '' );
			self::add_color( $css, $btn_s, 'background-color', $attrs['secondaryBgColor'] ?? '' );
			$s_hover = $attrs['secondaryHover'] ?? [];
			self::add_color( $css, $btn_sh, 'color', $s_hover['text'] ?? '' );
			self::add_color( $css, $btn_sh, 'background-color', $s_hover['background'] ?? '' );
		}
	}
}
