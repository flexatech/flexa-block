<?php
declare(strict_types=1);
/**
 * Animations — Flatsome-style scroll-entrance effects for Flexa blocks.
 *
 * A block opts in via its shared `animation` attribute (type + duration + delay).
 * Rather than touching every block's render.php or CSS generator, this service:
 *   1. hooks `render_block` once to mark the block's root element with
 *      `data-flexa-animate` + `.flexa-anim--<type>` (+ duration/delay data attrs),
 *   2. ships one static stylesheet that hides the element and defines the "from"
 *      transform per effect, and
 *   3. relies on the shared frontend observer (build/frontend/animation.js) to
 *      flip `data-flexa-animated="true"` when the element enters the viewport.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scroll-entrance animation renderer + stylesheet.
 */
final class Animations {

	/**
	 * Whitelisted effect names (must match ANIMATION_OPTIONS in panels.tsx and the
	 * `.flexa-anim--<type>` selectors in css()).
	 *
	 * @var string[]
	 */
	const TYPES = [
		'fadeIn',
		'fadeInUp',
		'fadeInDown',
		'fadeInLeft',
		'fadeInRight',
		'zoomIn',
		'scaleIn',
		'blurIn',
		'bounceIn',
		'bounceInUp',
		'bounceInDown',
		'bounceInLeft',
		'bounceInRight',
		'flipInX',
		'flipInY',
	];

	/**
	 * Whitelisted speed presets.
	 *
	 * @var string[]
	 */
	const DURATIONS = [ 'slow', 'normal', 'fast' ];

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_filter( 'render_block', [ __CLASS__, 'inject' ], 10, 2 );
	}

	/**
	 * Mark a block's root element with the animation contract when it opts in.
	 *
	 * Only Flexa blocks with a valid, non-"none" `animation.type` are touched. All
	 * values are whitelist-checked before being written, so a tampered attribute
	 * can never inject arbitrary markup.
	 *
	 * @param string $content Rendered block HTML.
	 * @param array  $block   Parsed block (name + attrs).
	 * @return string
	 */
	public static function inject( $content, $block ) {
		if ( ! is_string( $content ) || '' === trim( $content ) ) {
			return $content;
		}
		$name = $block['blockName'] ?? '';
		if ( 0 !== strpos( (string) $name, 'flexa/' ) ) {
			return $content;
		}

		$anim = $block['attrs']['animation'] ?? [];
		if ( ! is_array( $anim ) ) {
			return $content;
		}

		$type = (string) ( $anim['type'] ?? 'none' );
		if ( 'none' === $type || ! in_array( $type, self::TYPES, true ) ) {
			return $content;
		}

		if ( ! class_exists( '\WP_HTML_Tag_Processor' ) ) {
			return $content;
		}

		$processor = new \WP_HTML_Tag_Processor( $content );
		if ( ! $processor->next_tag() ) {
			return $content;
		}

		$processor->add_class( 'flexa-anim' );
		$processor->add_class( 'flexa-anim--' . $type );
		$processor->set_attribute( 'data-flexa-animate', '' );

		$duration = (string) ( $anim['duration'] ?? 'normal' );
		if ( in_array( $duration, self::DURATIONS, true ) && 'normal' !== $duration ) {
			$processor->set_attribute( 'data-flexa-anim-duration', $duration );
		}

		$delay = (string) ( $anim['delay'] ?? '' );
		if ( '' !== $delay && ctype_digit( $delay ) ) {
			$ms = (int) $delay;
			if ( $ms > 0 && $ms <= 60000 ) {
				// Pass the delay as a merged inline custom property so ANY value
				// works — a fixed attribute-selector list can't cover an arbitrary
				// range. Merge with any existing style so block styles aren't lost.
				$style = $processor->get_attribute( 'style' );
				$style = is_string( $style ) ? rtrim( trim( $style ), ';' ) : '';
				$decl  = '--flexa-anim-delay:' . $ms . 'ms';
				$processor->set_attribute( 'style', '' !== $style ? $style . ';' . $decl : $decl );
			}
		}

		return $processor->get_updated_html();
	}

	/**
	 * The static animation stylesheet (minified).
	 *
	 * Flatsome-style: the element starts hidden (`opacity:0`) with a "from"
	 * transform/filter per effect and transitions to its natural state once the
	 * observer sets `data-flexa-animated="true"`. Speed presets and delay steps are
	 * plain attribute selectors, so no per-block CSS is generated. Honors
	 * `prefers-reduced-motion` by showing everything immediately.
	 *
	 * @return string
	 */
	public static function css(): string {
		return <<<'CSS'
[data-flexa-animate]{opacity:0;will-change:transform,opacity,filter;backface-visibility:hidden;transition:transform var(--flexa-anim-dur,.7s) cubic-bezier(.2,.6,.2,1),opacity var(--flexa-anim-dur,.7s) ease,filter var(--flexa-anim-dur,.7s) ease;transition-delay:var(--flexa-anim-delay,0s)}
.flexa-anim--fadeInUp{transform:translate3d(0,40px,0)}
.flexa-anim--fadeInDown{transform:translate3d(0,-40px,0)}
.flexa-anim--fadeInLeft{transform:translate3d(-40px,0,0)}
.flexa-anim--fadeInRight{transform:translate3d(40px,0,0)}
.flexa-anim--zoomIn{transform:scale(.85)}
.flexa-anim--scaleIn{transform:scale(.6)}
.flexa-anim--blurIn{filter:blur(12px)}
.flexa-anim--flipInX{transform:perspective(400px) rotateX(90deg)}
.flexa-anim--flipInY{transform:perspective(400px) rotateY(90deg)}
.flexa-anim--bounceIn{transform:scale(.6)}
.flexa-anim--bounceInUp{transform:translate3d(0,120px,0)}
.flexa-anim--bounceInDown{transform:translate3d(0,-120px,0)}
.flexa-anim--bounceInLeft{transform:translate3d(-160px,0,0)}
.flexa-anim--bounceInRight{transform:translate3d(160px,0,0)}
[class*=flexa-anim--bounceIn]{transition-timing-function:cubic-bezier(.28,.84,.42,1.5)}
[data-flexa-anim-duration=slow]{--flexa-anim-dur:1.2s}
[data-flexa-anim-duration=fast]{--flexa-anim-dur:.4s}
[data-flexa-animated=true]{opacity:1!important;transform:none!important;filter:none!important}
@media (prefers-reduced-motion:reduce){[data-flexa-animate]{opacity:1!important;transform:none!important;filter:none!important;transition:none!important}}
CSS;
	}
}
