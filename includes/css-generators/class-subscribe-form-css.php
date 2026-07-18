<?php
declare(strict_types=1);
/**
 * Subscribe Form block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one subscribe form: the form box,
 * the field gap, the labels, the inputs (text / placeholder / background / border
 * / padding), the submit button (+ hover) and the success / error messages.
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
 * Subscribe Form CSS generator.
 */
class Subscribe_Form_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a subscribe form instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$form   = '.flexa-subscribe-form-' . $id;
		$fields = $form . ' .flexa-subscribe-form__fields';
		$label  = $form . ' .flexa-field__label';
		$input  = $form . ' .flexa-field__control';
		$ph     = $input . '::placeholder';
		$submit = $form . ' .flexa-subscribe-form__submit';
		$submit_hover = $submit . ':hover';
		$success = $form . ' .flexa-subscribe-form__message--success';
		$error   = $form . ' .flexa-subscribe-form__message--error';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Form box spacing.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			if ( ! empty( $spacing['padding'] ) ) {
				$padding = CSS_Helpers::spacing_shorthand( $spacing['padding'] );
				if ( '' !== $padding ) {
					$css->set_selector( $form )->add_property( 'padding', $padding );
				}
			}
			if ( ! empty( $spacing['margin'] ) ) {
				$margin = CSS_Helpers::spacing_shorthand( $spacing['margin'] );
				if ( '' !== $margin ) {
					$css->set_selector( $form )->add_property( 'margin', $margin );
				}
			}

			// Form border.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $form );
				CSS_Helpers::add_border( $css, $border );
			}

			// Advanced layout (overflow / position / z-index).
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $form );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			// Gap between fields.
			$gap = $attrs['fieldGap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$css->set_selector( $fields )->add_property( 'gap', CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' ) );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Non-responsive: form background + shadow (base only) + dark, like the Container.
		$background = $attrs['background'] ?? [];
		$lazy_bg    = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
		if ( ! empty( $background ) ) {
			$css->set_selector( $form );
			CSS_Helpers::add_background( $css, $background, $lazy_bg );
			if ( $lazy_bg ) {
				$css->set_selector( $form . '.flexa-bg-loaded' )
					->add_property( 'background-image', 'url(' . esc_url_raw( $background['image']['url'] ) . ')' );
			}
		}

		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $form )->add_property( 'box-shadow', $shadow );
		}

		CSS_Helpers::add_dark_mode(
			$css,
			$form,
			function ( $css ) use ( $attrs, $background ) {
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
				$border_dark = CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' );
				if ( '' !== $border_dark ) {
					$css->add_property( 'border-color', $border_dark );
				}
				$box = $attrs['boxShadow'] ?? [];
				if ( ! empty( $box['enabled'] ) ) {
					$shadow_dark = CSS_Helpers::dark( $box['color'] ?? '' );
					if ( '' !== $shadow_dark ) {
						$value = CSS_Helpers::box_shadow( $box, $shadow_dark );
						if ( '' !== $value ) {
							$css->add_property( 'box-shadow', $value );
						}
					}
				}
			}
		);

		// Label colour.
		self::solid_color( $css, $label, 'color', $attrs['labelColor'] ?? [] );

		// Inputs: text / placeholder / background / border / padding.
		self::solid_color( $css, $input, 'color', $attrs['inputTextColor'] ?? [] );
		self::solid_color( $css, $ph, 'color', $attrs['inputPlaceholderColor'] ?? [] );
		self::solid_color( $css, $input, 'background-color', $attrs['inputBackground'] ?? [] );

		$input_border = $attrs['inputBorder'] ?? [];
		if ( ! empty( $input_border ) ) {
			$css->set_selector( $input );
			CSS_Helpers::add_border( $css, $input_border );
			CSS_Helpers::dark_color( $css, $input, 'border-color', CSS_Helpers::dark( $input_border['color'] ?? '' ) );
		}
		$input_padding = CSS_Helpers::spacing_shorthand( $attrs['inputPadding'] ?? [] );
		if ( '' !== $input_padding ) {
			$css->set_selector( $input )->add_property( 'padding', $input_padding );
		}

		// Submit button.
		self::solid_color( $css, $submit, 'color', $attrs['submitTextColor'] ?? [] );
		self::solid_color( $css, $submit, 'background-color', $attrs['submitBackground'] ?? [] );
		$submit_padding = CSS_Helpers::spacing_shorthand( $attrs['submitPadding'] ?? [] );
		if ( '' !== $submit_padding ) {
			$css->set_selector( $submit )->add_property( 'padding', $submit_padding );
		}
		$submit_radius = $attrs['submitRadius'] ?? [];
		if ( ! empty( $submit_radius['value'] ) ) {
			$css->set_selector( $submit )->add_property( 'border-radius', CSS_Helpers::with_unit( $submit_radius['value'], $submit_radius['unit'] ?? 'px' ) );
		}
		self::solid_color( $css, $submit_hover, 'color', $attrs['submitTextColorHover'] ?? [] );
		self::solid_color( $css, $submit_hover, 'background-color', $attrs['submitBackgroundHover'] ?? [] );

		// Messages.
		self::solid_color( $css, $success, 'color', $attrs['successColor'] ?? [] );
		self::solid_color( $css, $success, 'background-color', $attrs['successBackground'] ?? [] );
		self::solid_color( $css, $error, 'color', $attrs['errorColor'] ?? [] );
		self::solid_color( $css, $error, 'background-color', $attrs['errorBackground'] ?? [] );
	}

	/**
	 * Emit a solid colour pair: light at the base, dark under the dark-mode branch.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param string      $property CSS property (color / background-color).
	 * @param mixed       $pair     Colour pair { light, dark }.
	 */
	private static function solid_color( $css, $selector, $property, $pair ) {
		$light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $pair ) );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( $property, $light );
		}
		CSS_Helpers::dark_color( $css, $selector, $property, CSS_Helpers::dark( $pair ) );
	}
}
