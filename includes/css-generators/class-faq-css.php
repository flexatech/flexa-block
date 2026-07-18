<?php
declare(strict_types=1);
/**
 * FAQ block — server-side CSS generator.
 *
 * Produces responsive, dark-mode-aware CSS for one FAQ instance. Nothing is
 * emitted unless the user picked a value, so an untouched FAQ keeps the theme's
 * typography. Declarations target the list / item / question / answer / icon
 * inside the block's own `.flexa-faq-<id>` wrapper.
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
 * FAQ CSS generator.
 */
class Faq_CSS {

	/**
	 * Devices in cascade order.
	 *
	 * @var array
	 */
	private static $devices = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * Generate CSS for a FAQ instance.
	 *
	 * @param array       $attrs Merged attributes.
	 * @param CSS_Builder $css   Shared builder.
	 */
	public static function generate( $attrs, $css ) {
		$id = $attrs['blockId'] ?? '';
		if ( '' === $id ) {
			return;
		}

		$wrap        = '.flexa-faq-' . $id;
		$list        = $wrap . ' .flexa-faq__list';
		$item        = $wrap . ' .flexa-faq__item';
		$item_on     = $wrap . ' .flexa-faq__item[data-open]';
		$question    = $wrap . ' .flexa-faq__question';
		// "Active" styling fires on an open item OR when hovering the question, so
		// hovering a closed question previews the open colours.
		$q_open      = $item_on . ' .flexa-faq__question, ' . $question . ':hover';
		// Text fills target the question-text span (not the whole button) so a
		// gradient's transparent text-fill can't wipe out the sibling icon colour.
		$q_text      = $wrap . ' .flexa-faq__question-text';
		$q_text_on   = $item_on . ' .flexa-faq__question-text, ' . $question . ':hover .flexa-faq__question-text';
		// The answer's padding + text/typography live on a padding-free-collapsing
		// content box (see .flexa-faq__answer-content) so padding never leaves a
		// sliver when the accordion is closed.
		$answer      = $wrap . ' .flexa-faq__answer-content';
		$answer_wrap = $wrap . ' .flexa-faq__answer';
		$icon        = $wrap . ' .flexa-faq__icon';
		$icon_on     = $item_on . ' .flexa-faq__icon, ' . $question . ':hover .flexa-faq__icon';

		foreach ( self::$devices as $device ) {
			CSS_Helpers::open_device( $css, $device );

			// Row gap between items.
			$row_gap = $attrs['rowGap'][ $device ] ?? [];
			if ( ! empty( $row_gap['value'] ) ) {
				$css->set_selector( $list )->add_property( 'row-gap', CSS_Helpers::with_unit( $row_gap['value'], $row_gap['unit'] ?? 'px' ) );
			}

			// Max width on the wrapper.
			$max_width = $attrs['maxWidth'][ $device ] ?? [];
			if ( ! empty( $max_width['value'] ) ) {
				$css->set_selector( $wrap )->add_property( 'max-width', CSS_Helpers::with_unit( $max_width['value'], $max_width['unit'] ?? 'px' ) );
			}

			// Icon size + gap from the question.
			$icon_size = $attrs['iconSize'][ $device ] ?? [];
			if ( ! empty( $icon_size['value'] ) ) {
				$value = CSS_Helpers::with_unit( $icon_size['value'], $icon_size['unit'] ?? 'px' );
				$css->set_selector( $icon )->add_property( 'width', $value )->add_property( 'height', $value );
			}
			$icon_gap = $attrs['iconGap'][ $device ] ?? [];
			if ( ! empty( $icon_gap['value'] ) ) {
				$css->set_selector( $question )->add_property( 'gap', CSS_Helpers::with_unit( $icon_gap['value'], $icon_gap['unit'] ?? 'px' ) );
			}

			// Question + answer typography.
			CSS_Helpers::add_typography( $css, $question, $attrs['questionTypography'][ $device ] ?? [] );
			CSS_Helpers::add_typography( $css, $answer, $attrs['answerTypography'][ $device ] ?? [] );

			// Question + answer padding.
			$q_pad = CSS_Helpers::spacing_shorthand( $attrs['questionPadding'][ $device ] ?? [] );
			if ( '' !== $q_pad ) {
				$css->set_selector( $question )->add_property( 'padding', $q_pad );
			}
			$a_pad = CSS_Helpers::spacing_shorthand( $attrs['answerPadding'][ $device ] ?? [] );
			if ( '' !== $a_pad ) {
				$css->set_selector( $answer )->add_property( 'padding', $a_pad );
			}

			// Border outlines each item (4 sides) plus a matching divider between the
			// question and its answer — five lines per item. Clip the item when a
			// radius is set so the rounded corners hold.
			$border = $attrs['border'][ $device ] ?? [];
			if ( ! empty( $border ) ) {
				$css->set_selector( $item );
				CSS_Helpers::add_border( $css, $border );
				if ( '' !== CSS_Helpers::radius_shorthand( $border['radius'] ?? [] ) ) {
					$css->add_property( 'overflow', 'hidden' );
				}
				self::add_qa_divider( $css, $question, $border );
			}

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

			// Advanced layout (overflow / position / z-index) on the wrapper.
			$advanced = $attrs['advancedLayout'][ $device ] ?? [];
			if ( ! empty( $advanced ) ) {
				$css->set_selector( $wrap );
				CSS_Helpers::add_advanced_layout( $css, $advanced );
			}

			CSS_Helpers::close_device( $css, $device );
		}

		self::add_colors(
			$css,
			$attrs,
			[
				'q_text'      => $q_text,
				'q_text_on'   => $q_text_on,
				'answer'      => $answer,
				'question'    => $question,
				'q_open'      => $q_open,
				'answer_wrap' => $answer_wrap,
				'item'        => $item,
				'icon'        => $icon,
				'icon_on'     => $icon_on,
			]
		);
		// Dark colour for the item border + the question/answer divider (light value
		// + geometry come from the per-device CSS_Helpers::add_border above).
		$border_dark = CSS_Helpers::dark( $attrs['border']['desktop']['color'] ?? '' );
		self::dark_color( $css, $item, 'border-color', $border_dark );
		self::dark_color( $css, $question, 'border-bottom-color', $border_dark );

		// Wrapper background (base, light) — the whole block.
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

		// Box shadow wraps each item (the question + answer pair), not the block, and
		// is clipped so only the bottom drop shadow shows.
		$shadow_cfg = $attrs['boxShadow'] ?? [];
		$shadow     = CSS_Helpers::box_shadow( $shadow_cfg );
		if ( '' !== $shadow ) {
			$css->set_selector( $item )->add_property( 'box-shadow', $shadow );
			if ( empty( $shadow_cfg['inset'] ) ) {
				// Clip tight on the top and sides, extend below far enough to reveal
				// the blur + spread + downward offset → a bottom-only shadow.
				$blur   = (float) ( $shadow_cfg['blur'] ?? 0 );
				$spread = (float) ( $shadow_cfg['spread'] ?? 0 );
				$vert   = (float) ( $shadow_cfg['vertical'] ?? 0 );
				$extend = (int) ceil( $blur + $spread + max( 0, $vert ) + 8 );
				$css->add_property( 'clip-path', 'inset(0 0 -' . $extend . 'px 0)' );
			}
		}

		self::add_wrapper_dark( $css, $wrap, $background );
		self::add_item_shadow_dark( $css, $item, $attrs );
	}

	/**
	 * Emit light + dark colour/gradient declarations for the text, background and
	 * icon parts. Text and background fills each offer a colour|gradient choice
	 * (see add_text_fill / add_bg_fill); the icon stays a solid colour.
	 *
	 * @param CSS_Builder $css   Builder.
	 * @param array       $attrs Attributes.
	 * @param array       $sel   Selector map (keys: q_text, q_text_on, answer,
	 *                           question, q_open, answer_wrap, item, icon, icon_on).
	 */
	private static function add_colors( $css, $attrs, $sel ) {
		// Text fills — a gradient paints the text via background-clip.
		self::add_text_fill( $css, $sel['q_text'], $attrs['questionColorType'] ?? 'color', $attrs['questionColor'] ?? '', $attrs['questionColorGradient'] ?? '' );
		self::add_text_fill( $css, $sel['q_text_on'], $attrs['questionActiveColorType'] ?? 'color', $attrs['questionActiveColor'] ?? '', $attrs['questionActiveColorGradient'] ?? '' );
		self::add_text_fill( $css, $sel['answer'], $attrs['answerColorType'] ?? 'color', $attrs['answerColor'] ?? '', $attrs['answerColorGradient'] ?? '' );

		// Background fills — a gradient becomes a background-image.
		self::add_bg_fill( $css, $sel['question'], $attrs['questionBackgroundType'] ?? 'color', $attrs['questionBackground'] ?? '', $attrs['questionBackgroundGradient'] ?? '' );
		self::add_bg_fill( $css, $sel['q_open'], $attrs['questionActiveBackgroundType'] ?? 'color', $attrs['questionActiveBackground'] ?? '', $attrs['questionActiveBackgroundGradient'] ?? '' );
		self::add_bg_fill( $css, $sel['answer_wrap'], $attrs['answerBackgroundType'] ?? 'color', $attrs['answerBackground'] ?? '', $attrs['answerBackgroundGradient'] ?? '' );
		self::add_bg_fill( $css, $sel['item'], $attrs['itemBackgroundType'] ?? 'color', $attrs['itemBackground'] ?? '', $attrs['itemBackgroundGradient'] ?? '' );

		// Icon colour (solid only, light + dark).
		$icon_light = CSS_Helpers::light( $attrs['iconColor'] ?? '' );
		if ( '' !== $icon_light ) {
			$css->set_selector( $sel['icon'] )->add_property( 'color', $icon_light );
		}
		self::dark_color( $css, $sel['icon'], 'color', CSS_Helpers::dark( $attrs['iconColor'] ?? '' ) );

		$icon_active_light = CSS_Helpers::light( $attrs['iconActiveColor'] ?? '' );
		if ( '' !== $icon_active_light ) {
			$css->set_selector( $sel['icon_on'] )->add_property( 'color', $icon_active_light );
		}
		self::dark_color( $css, $sel['icon_on'], 'color', CSS_Helpers::dark( $attrs['iconActiveColor'] ?? '' ) );
	}

	/**
	 * Emit a text fill: a solid colour, or a gradient painted through the text via
	 * background-clip. Light at the base, dark under the dark-mode branch.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param string      $type     'color' or 'gradient'.
	 * @param mixed       $color    Colour pair.
	 * @param mixed       $gradient Gradient pair.
	 */
	private static function add_text_fill( $css, $selector, $type, $color, $gradient ) {
		if ( 'gradient' === $type ) {
			$light = CSS_Helpers::light( $gradient );
			if ( '' !== $light ) {
				$css->set_selector( $selector )
					->add_property( 'background-image', $light )
					->add_property( '-webkit-background-clip', 'text' )
					->add_property( 'background-clip', 'text' )
					->add_property( '-webkit-text-fill-color', 'transparent' )
					->add_property( 'color', 'transparent' );
			}
			$dark = CSS_Helpers::dark( $gradient );
			if ( '' !== $dark ) {
				self::dark_color( $css, $selector, 'background-image', $dark );
			}
			return;
		}

		$light = CSS_Helpers::light( $color );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( 'color', $light );
		}
		self::dark_color( $css, $selector, 'color', CSS_Helpers::dark( $color ) );
	}

	/**
	 * Emit a background fill: a solid colour, or a gradient background-image.
	 * Light at the base, dark under the dark-mode branch.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param string      $type     'color' or 'gradient'.
	 * @param mixed       $color    Colour pair.
	 * @param mixed       $gradient Gradient pair.
	 */
	private static function add_bg_fill( $css, $selector, $type, $color, $gradient ) {
		if ( 'gradient' === $type ) {
			$light = CSS_Helpers::light( $gradient );
			if ( '' !== $light ) {
				$css->set_selector( $selector )->add_property( 'background-image', $light );
			}
			self::dark_color( $css, $selector, 'background-image', CSS_Helpers::dark( $gradient ) );
			return;
		}

		$light = CSS_Helpers::light( $color );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( 'background', $light );
		}
		self::dark_color( $css, $selector, 'background', CSS_Helpers::dark( $color ) );
	}

	/**
	 * Dark-mode declarations for the wrapper background.
	 *
	 * @param CSS_Builder $css        Builder.
	 * @param string      $wrap       Wrapper selector.
	 * @param array       $background Background attribute.
	 */
	private static function add_wrapper_dark( $css, $wrap, $background ) {
		CSS_Helpers::add_dark_mode(
			$css,
			$wrap,
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

	/**
	 * Dark-mode box shadow on each item, mirroring the light shadow with the dark
	 * shadow colour.
	 *
	 * @param CSS_Builder $css   Builder.
	 * @param string      $item  Item selector.
	 * @param array       $attrs Attributes.
	 */
	private static function add_item_shadow_dark( $css, $item, $attrs ) {
		$shadow = $attrs['boxShadow'] ?? [];
		if ( empty( $shadow['enabled'] ) ) {
			return;
		}
		$shadow_dark = CSS_Helpers::dark( $shadow['color'] ?? '' );
		if ( '' === $shadow_dark ) {
			return;
		}
		$value = CSS_Helpers::box_shadow( $shadow, $shadow_dark );
		if ( '' === $value ) {
			return;
		}
		CSS_Helpers::add_dark_mode(
			$css,
			$item,
			function ( $css ) use ( $value ) {
				$css->add_property( 'box-shadow', $value );
			}
		);
	}

	/**
	 * Emit one dark-mode colour declaration when a dark value is present.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Selector.
	 * @param string      $property CSS property.
	 * @param string      $value    Dark colour value.
	 */
	private static function dark_color( $css, $selector, $property, $value ) {
		if ( '' === $value ) {
			return;
		}
		CSS_Helpers::add_dark_mode(
			$css,
			$selector,
			function ( $css ) use ( $property, $value ) {
				$css->add_property( $property, $value );
			}
		);
	}

	/**
	 * Emit the divider between a question and its answer: a bottom border on the
	 * question that mirrors the item border's style, width and (light) colour, so
	 * one Border control draws both the item outline and the Q/A separator.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $question Question selector.
	 * @param array       $border   Border device object.
	 */
	private static function add_qa_divider( $css, $question, $border ) {
		if ( empty( $border['style'] ) ) {
			return;
		}
		$style = CSS_Helpers::sanitize_enum( $border['style'], [ 'solid', 'dashed', 'dotted', 'double' ] );
		if ( '' === $style ) {
			return;
		}
		$css->set_selector( $question )->add_property( 'border-bottom-style', $style );

		$width = $border['width'] ?? [];
		$value = '';
		foreach ( [ 'bottom', 'top', 'left', 'right' ] as $side ) {
			if ( isset( $width[ $side ] ) && '' !== (string) $width[ $side ] ) {
				$value = (string) $width[ $side ];
				break;
			}
		}
		if ( '' !== $value ) {
			$css->add_property( 'border-bottom-width', CSS_Helpers::with_unit( $value, $width['unit'] ?? 'px' ) );
		}

		$light = CSS_Helpers::light( $border['color'] ?? '' );
		if ( '' !== $light ) {
			$css->add_property( 'border-bottom-color', $light );
		}
	}

}
