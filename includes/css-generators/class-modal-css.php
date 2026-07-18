<?php
declare(strict_types=1);
/**
 * Modal block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one modal instance. Nothing is
 * emitted unless the user picked a value (the structural look lives in
 * style.scss). Declarations target the trigger, the modal box, the overlay and
 * the close button inside the block's own `.flexa-modal-<id>` wrapper. The
 * trigger icon size is exposed as the `--flexa-modal-icon-size` custom property.
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
 * Modal CSS generator.
 */
class Modal_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a modal instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap    = '.flexa-modal-' . $id;
		$trigger = $wrap . ' .flexa-modal__trigger';
		$box     = $wrap . ' .flexa-modal__box';
		$overlay = $wrap . ' .flexa-modal__overlay';
		$close   = $wrap . ' .flexa-modal__close';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Wrapper spacing (foundational).
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

			// Trigger alignment (text-align on the wrapper; the trigger is inline).
			$align = CSS_Helpers::sanitize_enum( (string) ( $attrs['triggerAlign'][ $device ] ?? '' ), [ 'left', 'center', 'right' ] );
			if ( '' !== $align ) {
				$css->set_selector( $wrap )->add_property( 'text-align', $align );
			}

			// Trigger typography.
			CSS_Helpers::add_typography( $css, $trigger, $attrs['triggerTypography'][ $device ] ?? [] );

			// Trigger padding.
			$trigger_padding = CSS_Helpers::spacing_shorthand( $attrs['triggerPadding'][ $device ] ?? [] );
			if ( '' !== $trigger_padding ) {
				$css->set_selector( $trigger )->add_property( 'padding', $trigger_padding );
			}

			// Modal box width + max-height.
			$width = $attrs['modalWidth'][ $device ] ?? [];
			if ( ! empty( $width['value'] ) ) {
				$css->set_selector( $box )->add_property( 'width', CSS_Helpers::with_unit( $width['value'], $width['unit'] ?? 'px' ) );
			}
			$max_height = $attrs['modalMaxHeight'][ $device ] ?? [];
			if ( ! empty( $max_height['value'] ) ) {
				$css->set_selector( $box )->add_property( 'max-height', CSS_Helpers::with_unit( $max_height['value'], $max_height['unit'] ?? 'px' ) );
			}

			// Modal box padding.
			$box_padding = CSS_Helpers::spacing_shorthand( $attrs['modalPadding'][ $device ] ?? [] );
			if ( '' !== $box_padding ) {
				$css->set_selector( $box )->add_property( 'padding', $box_padding );
			}

			// Border on the modal box (foundational).
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $box );
				CSS_Helpers::add_border( $css, $border );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// --- Trigger colours (light at base + dark) ----------------------------
		$trigger_color = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['triggerTextColor'] ?? '' ) );
		if ( '' !== $trigger_color ) {
			$css->set_selector( $trigger )->add_property( 'color', $trigger_color );
		}
		CSS_Helpers::dark_color( $css, $trigger, 'color', CSS_Helpers::dark( $attrs['triggerTextColor'] ?? '' ) );

		$trigger_bg = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['triggerBackground'] ?? '' ) );
		if ( '' !== $trigger_bg ) {
			$css->set_selector( $trigger )->add_property( 'background', $trigger_bg );
		}
		CSS_Helpers::dark_color( $css, $trigger, 'background', CSS_Helpers::dark( $attrs['triggerBackground'] ?? '' ) );

		// Trigger hover colours (gated — only when a hover colour is set).
		$trigger_hover      = $trigger . ':hover';
		$trigger_hover_text = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['triggerTextColorHover'] ?? '' ) );
		if ( '' !== $trigger_hover_text ) {
			$css->set_selector( $trigger_hover )->add_property( 'color', $trigger_hover_text );
		}
		CSS_Helpers::dark_color( $css, $trigger_hover, 'color', CSS_Helpers::dark( $attrs['triggerTextColorHover'] ?? '' ) );

		$trigger_hover_bg = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['triggerBackgroundHover'] ?? '' ) );
		if ( '' !== $trigger_hover_bg ) {
			$css->set_selector( $trigger_hover )->add_property( 'background', $trigger_hover_bg );
		}
		CSS_Helpers::dark_color( $css, $trigger_hover, 'background', CSS_Helpers::dark( $attrs['triggerBackgroundHover'] ?? '' ) );

		// Trigger corner radius.
		$trigger_radius = $attrs['triggerRadius'] ?? [];
		if ( ! empty( $trigger_radius['value'] ) ) {
			$css->set_selector( $trigger )->add_property( 'border-radius', CSS_Helpers::with_unit( $trigger_radius['value'], $trigger_radius['unit'] ?? 'px' ) );
		}

		// Trigger icon / image size (custom property, px only).
		$icon_size = $attrs['triggerIconSize']['value'] ?? '';
		if ( '' !== (string) $icon_size ) {
			$css->set_selector( $trigger )->add_property( '--flexa-modal-icon-size', CSS_Helpers::with_unit( $icon_size, 'px' ) );
		}

		// --- Modal box background + radius -------------------------------------
		$box_bg = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['modalBackground'] ?? '' ) );
		if ( '' !== $box_bg ) {
			$css->set_selector( $box )->add_property( 'background', $box_bg );
		}
		CSS_Helpers::dark_color( $css, $box, 'background', CSS_Helpers::dark( $attrs['modalBackground'] ?? '' ) );

		$modal_radius = $attrs['modalRadius'] ?? [];
		if ( ! empty( $modal_radius['value'] ) ) {
			$css->set_selector( $box )->add_property( 'border-radius', CSS_Helpers::with_unit( $modal_radius['value'], $modal_radius['unit'] ?? 'px' ) );
		}

		// Modal box border dark colour (desktop drives it) + box shadow (light + dark).
		CSS_Helpers::dark_color( $css, $box, 'border-color', CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' ) );

		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $box )->add_property( 'box-shadow', $shadow );
		}
		$shadow_attr = $attrs['boxShadow'] ?? [];
		if ( ! empty( $shadow_attr['enabled'] ) ) {
			$shadow_dark = CSS_Helpers::dark( $shadow_attr['color'] ?? '' );
			if ( '' !== $shadow_dark ) {
				CSS_Helpers::dark_color( $css, $box, 'box-shadow', CSS_Helpers::box_shadow( $shadow_attr, $shadow_dark ) );
			}
		}

		// --- Overlay colour (light + dark) -------------------------------------
		$overlay_colour = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['overlayColor'] ?? '' ) );
		if ( '' !== $overlay_colour ) {
			$css->set_selector( $overlay )->add_property( 'background-color', $overlay_colour );
		}
		CSS_Helpers::dark_color( $css, $overlay, 'background-color', CSS_Helpers::dark( $attrs['overlayColor'] ?? '' ) );

		// --- Close button colour / size / position -----------------------------
		$close_colour = CSS_Helpers::sanitize_color( CSS_Helpers::light( $attrs['closeIconColor'] ?? '' ) );
		if ( '' !== $close_colour ) {
			$css->set_selector( $close )->add_property( 'color', $close_colour );
		}
		CSS_Helpers::dark_color( $css, $close, 'color', CSS_Helpers::dark( $attrs['closeIconColor'] ?? '' ) );

		$close_size = $attrs['closeIconSize']['value'] ?? '';
		if ( '' !== (string) $close_size ) {
			$css->set_selector( $close )->add_property( 'font-size', CSS_Helpers::with_unit( $close_size, 'px' ) );
		}

		// Close position (gated — only the non-default "outside" placement emits).
		$close_pos = CSS_Helpers::sanitize_enum( (string) ( $attrs['closePosition'] ?? 'inside' ), [ 'inside', 'outside' ], 'inside' );
		if ( 'outside' === $close_pos ) {
			$css->set_selector( $close )
				->add_property( 'top', '-44px' )
				->add_property( 'right', '0' );
		}
	}
}
