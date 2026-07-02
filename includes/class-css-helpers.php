<?php
declare(strict_types=1);
/**
 * CSS Helpers - convert attribute data into CSS declarations on a CSS_Builder.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static helpers shared by CSS generators.
 */
class CSS_Helpers {

	/**
	 * Units accepted for attribute-provided dimension values.
	 *
	 * @var array
	 */
	private static $units = [ 'px', 'em', 'rem', '%', 'vh', 'vw', 'vmin', 'vmax', 'ch', 'ex', 'fr', 'svh', 'dvh', 'lvh', 'deg', 's', 'ms' ];

	/**
	 * Validate a single CSS color value (hex, rgb[a], hsl[a], var(), keyword).
	 *
	 * Attribute values are user-controlled (saved block JSON bypasses KSES), so
	 * anything that is not a recognizable color is dropped rather than emitted.
	 *
	 * @param mixed $value Candidate color.
	 * @return string The color, or '' when invalid.
	 */
	public static function sanitize_color( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^#([0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^(rgb|rgba|hsl|hsla)\(\s*[0-9deg\s.,%\/]+\s*\)$/i', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^var\(\s*--[a-zA-Z0-9_-]+\s*\)$/', $value ) ) {
			return $value;
		}
		// Named colors + keywords (transparent, currentcolor, inherit, ...).
		if ( preg_match( '/^[a-zA-Z]+$/', $value ) ) {
			return $value;
		}
		return '';
	}

	/**
	 * Validate a CSS gradient value.
	 *
	 * @param mixed $value Candidate gradient.
	 * @return string The gradient, or '' when invalid.
	 */
	public static function sanitize_gradient( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		if ( ! preg_match( '/^(repeating-)?(linear|radial|conic)-gradient\(/i', $value ) ) {
			return '';
		}
		// Colors, stops, angles and nested color functions only - no quotes,
		// semicolons, braces or url() payloads.
		if ( preg_match( '/[^a-zA-Z0-9\s.,%#()\/+-]/', $value ) ) {
			return '';
		}
		return $value;
	}

	/**
	 * Keep an attribute value only when it is one of the allowed CSS keywords.
	 *
	 * @param mixed $value   Candidate keyword.
	 * @param array $allowed Allowed keywords (lowercase).
	 * @return string The keyword, or '' when not allowed.
	 */
	public static function keyword( $value, array $allowed ) {
		$value = strtolower( trim( (string) $value ) );
		return in_array( $value, $allowed, true ) ? $value : '';
	}

	/**
	 * Pick the light value from a { light, dark } color object.
	 *
	 * @param mixed $color Color object or string.
	 * @return string
	 */
	public static function light( $color ) {
		if ( is_array( $color ) ) {
			return $color['light'] ?? '';
		}
		return is_string( $color ) ? $color : '';
	}

	/**
	 * Pick the dark value from a { light, dark } color object.
	 *
	 * @param mixed $color Color object.
	 * @return string
	 */
	public static function dark( $color ) {
		return is_array( $color ) ? ( $color['dark'] ?? '' ) : '';
	}

	/**
	 * Append a unit to a numeric value unless it is auto/none/calc or already has one.
	 *
	 * @param string $value Value.
	 * @param string $unit  Unit.
	 * @return string
	 */
	public static function with_unit( $value, $unit = 'px' ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		if ( in_array( $value, [ 'auto', 'none' ], true ) ) {
			return $value;
		}
		// Plain number: append the (allowlisted) unit. Both come from attributes.
		if ( preg_match( '/^-?\d*\.?\d+$/', $value ) ) {
			return $value . ( in_array( $unit, self::$units, true ) ? $unit : 'px' );
		}
		// Already-united values and math functions (calc/min/max/clamp): allow
		// only dimension-safe characters so nothing can escape the declaration.
		if ( preg_match( '/[a-z%)]$/i', $value ) && ! preg_match( '/[^a-zA-Z0-9\s.,%()*\/+-]/', $value ) ) {
			return $value;
		}
		return '';
	}

	/**
	 * Build a 4-side spacing shorthand (top right bottom left), collapsing where possible.
	 *
	 * @param array $box  Box with top/right/bottom/left/unit.
	 * @return string Shorthand or '' when empty.
	 */
	public static function spacing_shorthand( $box ) {
		if ( ! is_array( $box ) ) {
			return '';
		}
		$unit = $box['unit'] ?? 'px';
		$sides = [ 'top', 'right', 'bottom', 'left' ];
		$values = [];
		$has_any = false;
		foreach ( $sides as $side ) {
			$raw = isset( $box[ $side ] ) ? (string) $box[ $side ] : '';
			if ( '' !== $raw ) {
				$has_any = true;
			}
			$values[ $side ] = '' === $raw ? '0' : self::with_unit( $raw, $unit );
		}
		if ( ! $has_any ) {
			return '';
		}
		return sprintf( '%s %s %s %s', $values['top'], $values['right'], $values['bottom'], $values['left'] );
	}

	/**
	 * Build a border-radius shorthand from a 4-corner object.
	 *
	 * @param array $radius Corner object.
	 * @return string
	 */
	public static function radius_shorthand( $radius ) {
		if ( ! is_array( $radius ) ) {
			return '';
		}
		$unit    = $radius['unit'] ?? 'px';
		$corners = [ 'topLeft', 'topRight', 'bottomRight', 'bottomLeft' ];
		$values  = [];
		$has_any = false;
		foreach ( $corners as $corner ) {
			$raw = isset( $radius[ $corner ] ) ? (string) $radius[ $corner ] : '';
			if ( '' !== $raw ) {
				$has_any = true;
			}
			$values[] = '' === $raw ? '0' : self::with_unit( $raw, $unit );
		}
		return $has_any ? implode( ' ', $values ) : '';
	}

	/**
	 * Build a box-shadow value. Returns '' when not enabled / empty.
	 *
	 * @param array  $shadow         Shadow object.
	 * @param string $color_override Optional color override.
	 * @return string
	 */
	public static function box_shadow( $shadow, $color_override = '' ) {
		if ( ! is_array( $shadow ) || empty( $shadow['enabled'] ) ) {
			return '';
		}
		$h     = self::with_unit( $shadow['horizontal'] ?? '0' );
		$v     = self::with_unit( $shadow['vertical'] ?? '0' );
		$blur  = self::with_unit( $shadow['blur'] ?? '0' );
		$spread = self::with_unit( $shadow['spread'] ?? '0' );
		$color = '' !== $color_override ? $color_override : self::light( $shadow['color'] ?? '' );
		$color = self::sanitize_color( $color );
		if ( '' === $color ) {
			$color = 'rgba(0,0,0,0.1)';
		}
		$inset = ! empty( $shadow['inset'] ) ? 'inset ' : '';
		return trim( $inset . "$h $v $blur $spread $color" );
	}

	/**
	 * Emit border properties for a device border object onto the builder.
	 *
	 * @param CSS_Builder $css    Builder (selector already set).
	 * @param array       $border Device border object.
	 */
	public static function add_border( $css, $border ) {
		if ( ! is_array( $border ) ) {
			return;
		}
		if ( ! empty( $border['style'] ) ) {
			$css->add_property( 'border-style', self::keyword( $border['style'], [ 'none', 'hidden', 'solid', 'dashed', 'dotted', 'double', 'groove', 'ridge', 'inset', 'outset' ] ) );
		}
		$width = self::spacing_shorthand( $border['width'] ?? [] );
		if ( '' !== $width ) {
			$css->add_property( 'border-width', $width );
		}
		$color = self::sanitize_color( self::light( $border['color'] ?? '' ) );
		if ( '' !== $color ) {
			$css->add_property( 'border-color', $color );
		}
		$radius = self::radius_shorthand( $border['radius'] ?? [] );
		if ( '' !== $radius ) {
			$css->add_property( 'border-radius', $radius );
		}
	}

	/**
	 * Emit background (color / gradient / image) onto the builder selector.
	 *
	 * @param CSS_Builder $css            Builder.
	 * @param array       $background     Background object.
	 * @param bool        $skip_image_url When true, emit image position/size/etc.
	 *                                    but NOT the `background-image: url()` - used
	 *                                    for lazy loading where the url rule is gated
	 *                                    behind a `.flexa-bg-loaded` class elsewhere.
	 */
	public static function add_background( $css, $background, $skip_image_url = false ) {
		if ( ! is_array( $background ) ) {
			return;
		}
		$type = $background['type'] ?? 'none';

		if ( 'classic' === $type || 'color' === $type ) {
			$color = self::sanitize_color( self::light( $background['color'] ?? '' ) );
			if ( '' !== $color ) {
				$css->add_property( 'background-color', $color );
			}
		} elseif ( 'gradient' === $type ) {
			$gradient = self::sanitize_gradient( self::light( $background['gradient'] ?? '' ) );
			if ( '' !== $gradient ) {
				$css->add_property( 'background-image', $gradient );
			}
		} elseif ( 'image' === $type ) {
			$image = $background['image'] ?? [];
			$url   = $image['url'] ?? '';
			if ( '' !== $url ) {
				if ( ! $skip_image_url ) {
					$css->add_property( 'background-image', 'url(' . esc_url_raw( $url ) . ')' );
				}
				// Position/size accept keywords or length pairs ("top left", "50% 50%").
				$position = (string) ( $image['position'] ?? 'center center' );
				$size     = (string) ( $image['size'] ?? 'cover' );
				$css->add_property( 'background-position', preg_match( '/^[a-zA-Z0-9.%\s-]+$/', $position ) ? $position : 'center center' );
				$css->add_property( 'background-size', preg_match( '/^[a-zA-Z0-9.%\s-]+$/', $size ) ? $size : 'cover' );
				$css->add_property( 'background-repeat', self::keyword( $image['repeat'] ?? 'no-repeat', [ 'repeat', 'repeat-x', 'repeat-y', 'no-repeat', 'space', 'round' ] ) );
				$css->add_property( 'background-attachment', self::keyword( $image['attachment'] ?? 'scroll', [ 'scroll', 'fixed', 'local' ] ) );
			}
		}
	}

	/**
	 * Wrap a callback's declarations for dark mode using the enabled method(s).
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Base selector.
	 * @param callable    $callback Receives the builder (selector pre-set) to add dark declarations.
	 */
	public static function add_dark_mode( $css, $selector, $callback ) {
		if ( ! Dark_Mode_Settings::is_enabled() ) {
			return;
		}

		if ( Dark_Mode_Settings::use_data_theme() ) {
			$css->set_selector( '[data-theme="dark"] ' . $selector );
			$callback( $css );
		}

		if ( Dark_Mode_Settings::use_color_scheme() ) {
			$css->start_media_query( '@media (prefers-color-scheme: dark)' );
			$css->set_selector( $selector );
			$callback( $css );
			$css->end_media_query();
		}
	}
}
