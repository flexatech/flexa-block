<?php
declare(strict_types=1);
/**
 * Post Filter block — server-side CSS generator.
 *
 * ONE generator for the whole bar: the three children (search, taxonomy, reset)
 * have no generator of their own, so every label, input, checkbox and button in the
 * bar is styled from this block's `.flexa-post-filter-<id>` scope. That is what
 * keeps the children down to a handful of attributes each — and gives the author a
 * single place to make the bar match the theme.
 *
 * Following the "prefer theme styles" rule, only the layout is always emitted;
 * colours, borders and typography appear only when the user actually picked a value.
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
 * Post Filter CSS generator.
 */
class Post_Filter_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a post-filter instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$root    = '.flexa-post-filter-' . $id;
		$fields  = $root . ' .flexa-post-filter__fields';
		$label   = $root . ' .flexa-filter-field__label';
		$control = $root . ' .flexa-filter-field__control';
		$option  = $root . ' .flexa-filter-field__option';
		$button = $root . ' .flexa-post-filter__submit';

		// The reset control is a BUTTON or a LINK. Painting the Button background onto
		// the link variant turns a small "clear filters" link into a full-width slab of
		// accent colour — so only the button variant takes the fill; the link variant
		// takes the accent as its text colour, which is what a link wants.
		$reset_button = $root . ' .flexa-filter-reset__control.wp-element-button';
		$reset_link   = $root . ' .flexa-filter-reset__control--link';
		$buttons      = $button . ', ' . $reset_button;

		// Pills are a control at rest and a button when chosen — so they take the
		// Control colours idle and the Button colours selected. That reuse is what
		// lets the whole bar be styled from one panel instead of one per child.
		$pills        = $root . ' .flexa-filter-field__options--pills';
		$pill         = $pills . ' .flexa-filter-field__option-text';
		$pill_checked = $pills . ' .flexa-filter-field__option-input:checked + .flexa-filter-field__option-text';

		$direction = 'column' === ( $attrs['direction'] ?? 'row' ) ? 'column' : 'row';
		$align     = CSS_Helpers::sanitize_enum( (string) ( $attrs['align'] ?? '' ), [ 'flex-start', 'center', 'flex-end', 'stretch' ] );

		$css->set_selector( $root )
			->add_property( 'display', 'flex' )
			->add_property( 'flex-direction', $direction );
		$css->set_selector( $fields )->add_property( 'flex-direction', $direction );

		if ( '' !== $align ) {
			$css->set_selector( $root )->add_property( 'align-items', $align );
			$css->set_selector( $fields )->add_property( 'align-items', $align );
		}

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Gap between the controls (and between the controls and the button).
			$gap = $attrs['gap'][ $device ] ?? [];
			if ( ! empty( $gap['value'] ) ) {
				$value = CSS_Helpers::with_unit( $gap['value'], $gap['unit'] ?? 'px' );
				$css->set_selector( $root )->add_property( 'gap', $value );
				$css->set_selector( $fields )->add_property( 'gap', $value );
			}

			// Wrapper spacing.
			$spacing = $attrs['spacing'][ $device ] ?? [];
			$padding = CSS_Helpers::spacing_shorthand( $spacing['padding'] ?? [] );
			if ( '' !== $padding ) {
				$css->set_selector( $root )->add_property( 'padding', $padding );
			}
			$margin = CSS_Helpers::spacing_shorthand( $spacing['margin'] ?? [] );
			if ( '' !== $margin ) {
				$css->set_selector( $root )->add_property( 'margin', $margin );
			}

			// Wrapper border.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $root );
				CSS_Helpers::add_border( $css, $border );
			}

			// Advanced layout (overflow / position / z-index).
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $root );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			// Responsive typography.
			if ( 'desktop' !== $device ) {
				CSS_Helpers::add_typography( $css, $label, $attrs['labelTypography'][ $device ] ?? [] );
				CSS_Helpers::add_typography( $css, $control . ', ' . $pill, $attrs['controlTypography'][ $device ] ?? [] );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		// Desktop typography (outside any media query).
		CSS_Helpers::add_typography( $css, $label, $attrs['labelTypography']['desktop'] ?? [] );
		CSS_Helpers::add_typography( $css, $control . ', ' . $pill, $attrs['controlTypography']['desktop'] ?? [] );

		// Labels (the checkbox legend and the option labels share the label styling,
		// so a bar reads as one thing rather than three).
		self::add_color( $css, $label, 'color', $attrs['labelColor'] ?? '' );
		self::add_color( $css, $option, 'color', $attrs['labelColor'] ?? '' );

		// Controls (inputs, selects — and pills at rest).
		self::add_color( $css, $control . ', ' . $pill, 'color', $attrs['controlColor'] ?? '' );
		// `background-color`, never the `background` SHORTHAND: the shorthand resets
		// background-image, and the dropdown's caret IS a background-image. (The <select>
		// survived it on specificity; the disclosure summary lost its caret outright.)
		self::add_color( $css, $control . ', ' . $pill, 'background-color', $attrs['controlBackground'] ?? '' );
		self::add_color( $css, $control . '::placeholder', 'color', $attrs['controlPlaceholderColor'] ?? '' );
		// The magnifier is placeholder furniture, so it takes the placeholder's colour.
		self::add_color( $css, $root . ' .flexa-filter-field__search-icon', 'color', $attrs['controlPlaceholderColor'] ?? '' );

		$control_border = $attrs['controlBorder'] ?? [];
		if ( ! empty( $control_border ) ) {
			$css->set_selector( $control );
			CSS_Helpers::add_border( $css, $control_border );

			// Pills keep their own pill shape — a chip with a 4px radius is a button,
			// not a chip — so they take the border style/width/colour but not its radius.
			$css->set_selector( $pill );
			CSS_Helpers::add_border( $css, array_diff_key( $control_border, [ 'radius' => null ] ) );

			CSS_Helpers::add_dark_mode(
				$css,
				$control . ', ' . $pill,
				function ( $css ) use ( $control_border ) {
					$dark = CSS_Helpers::sanitize_color( CSS_Helpers::dark( $control_border['color'] ?? '' ) );
					if ( '' !== $dark ) {
						$css->add_property( 'border-color', $dark );
					}
				}
			);
		}

		$control_padding = CSS_Helpers::spacing_shorthand( $attrs['controlPadding'] ?? [] );
		if ( '' !== $control_padding ) {
			$css->set_selector( $control )->add_property( 'padding', $control_padding );

			// The author's padding lands on EVERY control, search box included — and it
			// would slide the text back under the magnifier. So the search box gets its
			// left padding widened by the icon's width, and the icon is parked at the
			// author's own left padding. (style.scss can't do this: its selector ties
			// with the one above and loses on source order.)
			$pad  = (array) ( $attrs['controlPadding'] ?? [] );
			$left = (string) ( $pad['left'] ?? '' );
			if ( '' !== $left ) {
				$unit  = (string) ( $pad['unit'] ?? 'px' );
				$start = CSS_Helpers::with_unit( $left, $unit );

				$css->set_selector( $root . ' .flexa-filter-field__search-wrap ' . '.flexa-filter-field__control' )
					->add_property( 'padding-left', 'calc(' . $start . ' + 26px)' );
				$css->set_selector( $root . ' .flexa-filter-field__search-icon' )
					->add_property( 'left', $start );
			}
		}

		// Focus: a colour, not a grey ring. The author can name one (Control border →
		// "Border color (focus)"); left empty it borrows the Button background, because a
		// bar that already has an accent should focus in that accent rather than invent a
		// second one.
		//
		// ONE line only. The border carries the colour and a translucent halo hugs it —
		// an outline as well would sit outside the border and read as a second frame.
		$focus = $control . ':focus, ' . $control . ':focus-visible';

		$focus_color = $attrs['controlBorderFocus'] ?? '';
		$has_focus   = '' !== CSS_Helpers::sanitize_color( CSS_Helpers::light( $focus_color ) )
			|| '' !== CSS_Helpers::sanitize_color( CSS_Helpers::dark( $focus_color ) );
		if ( ! $has_focus ) {
			$focus_color = $attrs['buttonBackground'] ?? '';
		}

		$accent = CSS_Helpers::sanitize_color( CSS_Helpers::light( $focus_color ) );

		self::add_color( $css, $focus, 'border-color', $focus_color );
		if ( '' !== $accent ) {
			$css->set_selector( $focus )
				->add_property( 'box-shadow', '0 0 0 3px color-mix(in srgb, ' . $accent . ' 22%, transparent)' );
		}

		$accent_dark = CSS_Helpers::sanitize_color( CSS_Helpers::dark( $focus_color ) );
		if ( '' !== $accent_dark ) {
			CSS_Helpers::add_dark_mode(
				$css,
				$focus,
				static function ( $css ) use ( $accent_dark ) {
					$css->add_property( 'box-shadow', '0 0 0 3px color-mix(in srgb, ' . $accent_dark . ' 30%, transparent)' );
				}
			);
		}

		// The SELECTED pill takes the bar's Button colours, so a chosen chip and the
		// submit button read as the same accent.
		//
		// The HOVER colours go on the chips you can still pick — the unselected ones.
		// A chip that is already active has nothing to preview: hovering it should not
		// change anything, or the bar flickers under a moving mouse.
		$pill_hover = $pills . ' .flexa-filter-field__option:hover .flexa-filter-field__option-input:not(:checked) + .flexa-filter-field__option-text';

		self::add_color( $css, $pill_checked, 'color', $attrs['buttonColor'] ?? '' );
		self::add_color( $css, $pill_checked, 'background-color', $attrs['buttonBackground'] ?? '' );
		self::add_color( $css, $pill_hover, 'color', $attrs['buttonColorHover'] ?? '' );
		self::add_color( $css, $pill_hover, 'background-color', $attrs['buttonBackgroundHover'] ?? '' );

		// Submit button + the BUTTON variant of reset: styled together, so the bar's
		// two actions match.
		self::add_color( $css, $buttons, 'color', $attrs['buttonColor'] ?? '' );
		self::add_color( $css, $buttons, 'background-color', $attrs['buttonBackground'] ?? '' );
		self::add_color( $css, $button . ':hover, ' . $reset_button . ':hover', 'color', $attrs['buttonColorHover'] ?? '' );
		self::add_color( $css, $button . ':hover, ' . $reset_button . ':hover', 'background-color', $attrs['buttonBackgroundHover'] ?? '' );

		// The LINK variant of reset: the accent as ink, never as a fill.
		self::add_color( $css, $reset_link, 'color', $attrs['buttonBackground'] ?? '' );
		self::add_color( $css, $reset_link . ':hover', 'color', $attrs['buttonBackgroundHover'] ?? '' );

		$button_padding = CSS_Helpers::spacing_shorthand( $attrs['buttonPadding'] ?? [] );
		if ( '' !== $button_padding ) {
			$css->set_selector( $buttons )->add_property( 'padding', $button_padding );
		}
		$button_radius = $attrs['buttonRadius'] ?? [];
		if ( ! empty( $button_radius['value'] ) ) {
			$css->set_selector( $buttons )->add_property( 'border-radius', CSS_Helpers::with_unit( $button_radius['value'], $button_radius['unit'] ?? 'px' ) );
		}

		// Wrapper background + shadow.
		$background = $attrs['background'] ?? [];
		if ( ! empty( $background ) ) {
			$css->set_selector( $root );
			CSS_Helpers::add_background( $css, $background, false );
		}

		$shadow = CSS_Helpers::box_shadow( $attrs['boxShadow'] ?? [] );
		if ( '' !== $shadow ) {
			$css->set_selector( $root )->add_property( 'box-shadow', $shadow );
		}

		// Dark mode on the wrapper: background + border colour.
		CSS_Helpers::add_dark_mode(
			$css,
			$root,
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
			}
		);
	}

	/**
	 * Emit a colour pair onto a selector: light at the base, dark under the
	 * dark-mode branch. Nothing is emitted when neither is set.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param string      $property CSS property (`color` or `background`).
	 * @param mixed       $color    Colour pair.
	 */
	private static function add_color( $css, $selector, $property, $color ) {
		$light = CSS_Helpers::sanitize_color( CSS_Helpers::light( $color ) );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( $property, $light );
		}
		CSS_Helpers::dark_color( $css, $selector, $property, CSS_Helpers::dark( $color ) );
	}
}
