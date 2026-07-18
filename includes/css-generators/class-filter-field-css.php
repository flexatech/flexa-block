<?php
declare(strict_types=1);
/**
 * Filter children (search / taxonomy / reset) — server-side CSS generator.
 *
 * One generator for all three: they render different controls, but they are the same
 * thing to an author — a field in the bar — and they carry the same three style
 * attributes.
 *
 * The bar's own Control panel styles EVERY control at once, which is what keeps a
 * filter bar internally consistent. This is the escape hatch: it paints ONE field's
 * box, and it has to win, so its selector carries the field's own id class as well as
 * the shell class — one class more specific than the bar's `.flexa-post-filter-<id>
 * .flexa-filter-field__control`. (Source order would do it too — children are
 * processed after their parent — but specificity is the part that stays true if that
 * order ever changes.)
 *
 * "The field's box" is whatever the child actually renders: the search input, the
 * select or the disclosure summary, the reset button, or the chips — which ARE the
 * control in pills mode. A child renders one of them, so the rest of the selector list
 * is inert rather than wrong. Mirrors src/shared/filter-field-style.tsx, which previews
 * the same selector on the canvas.
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
 * Filter field CSS generator (flexa/filter-search, filter-taxonomy, filter-reset).
 */
class Filter_Field_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for one filter child.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap = '.flexa-filter-field.flexa-filter-field-' . $id;

		// The field's box: the input / select / disclosure summary, the reset control —
		// and a chip, but ONLY in pills mode, where the chip IS the control. In a menu or
		// a checkbox list the option is a line in a list, not a box: painting a border on
		// it drew one rectangle around every term in the dropdown.
		$surface = implode(
			', ',
			[
				$wrap . ' .flexa-filter-field__control',
				$wrap . ' .flexa-filter-reset__control',
				$wrap . ' .flexa-filter-field__options--pills .flexa-filter-field__option-text',
			]
		);

		$background = $attrs['background'] ?? [];
		if ( ! empty( $background ) ) {
			$css->set_selector( $surface );
			CSS_Helpers::add_background( $css, $background );
		}

		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $surface )->add_property( 'box-shadow', $shadow );
		}

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $surface );
				CSS_Helpers::add_border( $css, $border );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		self::add_dark_mode( $css, $surface, $attrs );
	}

	/**
	 * Dark-mode branch: the background fill and the border colour, which are the only
	 * two things here an author sets a dark value for (a shadow's dark colour rides
	 * inside box_shadow()).
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param array       $attrs    Merged attributes.
	 */
	private static function add_dark_mode( $css, $selector, $attrs ) {
		CSS_Helpers::add_dark_mode(
			$css,
			$selector,
			function ( $css ) use ( $attrs ) {
				$background = $attrs['background'] ?? [];
				$type       = $background['type'] ?? 'none';

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
			}
		);
	}
}
