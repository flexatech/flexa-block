<?php
declare(strict_types=1);
/**
 * CSS Helpers — convert attribute data into CSS declarations on a CSS_Builder.
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
	 * Validate a CSS color-like value: hex, `rgb()/rgba()/hsl()/hsla()/var()`,
	 * or a bare keyword (`transparent`, `currentColor`, a named color, …).
	 *
	 * CSS has no `esc_*()` escaper, so unlike HTML output this can't be escaped
	 * at print time — it has to be validated at the point the value is accepted.
	 * Anything outside this shape is dropped rather than guessed at.
	 *
	 * @param string $value Raw color value.
	 * @return string Sanitized value, or '' when it isn't a recognizable color.
	 */
	public static function sanitize_color( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^#[0-9a-fA-F]{3,8}$/', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^(?:rgba?|hsla?|var)\([0-9a-zA-Z\s,.\-%#]*\)$/', $value ) ) {
			return $value;
		}
		if ( preg_match( '/^[a-zA-Z]+$/', $value ) ) {
			return $value;
		}
		return '';
	}

	/**
	 * Validate a CSS gradient() function value (linear/radial/conic, optionally
	 * `repeating-`). Anything outside this shape is dropped.
	 *
	 * @param string $value Raw gradient value.
	 * @return string Sanitized value, or '' when it isn't a recognizable gradient.
	 */
	public static function sanitize_gradient( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^(?:repeating-)?(?:linear|radial|conic)-gradient\([0-9a-zA-Z\s,.\-%#()]*\)$/', $value ) ) {
			return $value;
		}
		return '';
	}

	/**
	 * Constrain a value to a fixed allow-list of CSS keywords. Enum-shaped
	 * properties (border-style, display, position, …) have no numeric/color
	 * pattern to validate against, so — unlike sanitize_color()/sanitize_gradient()
	 * — the only correct check is exact membership in the known-valid set.
	 *
	 * @param string $value   Raw value.
	 * @param array  $allowed Allowed keyword list.
	 * @param string $default Fallback when the value isn't in the list.
	 * @return string
	 */
	public static function sanitize_enum( $value, array $allowed, $default = '' ) {
		$value = (string) $value;
		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	/**
	 * Validate a background/mask "position" value: one or two whitespace-
	 * separated tokens, each either a keyword (left/right/top/bottom/center) or
	 * a length/percentage (e.g. `10px`, `50%`, `-1.5em`).
	 *
	 * @param string $value   Raw value.
	 * @param string $default Fallback when the value doesn't match this shape.
	 * @return string
	 */
	public static function sanitize_position_pair( $value, $default = '' ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return $default;
		}
		$keywords = [ 'left', 'right', 'top', 'bottom', 'center' ];
		foreach ( preg_split( '/\s+/', $value ) as $token ) {
			$is_keyword = in_array( $token, $keywords, true );
			$is_length  = (bool) preg_match( '/^-?[0-9]*\.?[0-9]+(?:px|%|em|rem|vh|vw)?$/', $token );
			if ( ! $is_keyword && ! $is_length ) {
				return $default;
			}
		}
		return $value;
	}

	/**
	 * Validate a bare (unitless) CSS number, e.g. a `line-height` value like `1.5`.
	 *
	 * @param string $value   Raw value.
	 * @param string $default Fallback when the value isn't numeric.
	 * @return string
	 */
	public static function sanitize_bare_number( $value, $default = '' ) {
		$value = trim( (string) $value );
		return preg_match( '/^-?[0-9]*\.?[0-9]+$/', $value ) ? $value : $default;
	}

	/**
	 * Append a unit to a numeric value unless it is auto/none/calc or already has one.
	 *
	 * @param string $value Value.
	 * @param string $unit  Unit.
	 * @return string
	 */
	public static function with_unit( $value, $unit = 'px' ) {
		$value = (string) $value;
		if ( '' === $value ) {
			return '';
		}
		if ( in_array( $value, [ 'auto', 'none' ], true ) || preg_match( '/[a-z%)]$/i', $value ) ) {
			return $value;
		}
		return $value . $unit;
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
		$color = self::sanitize_color( '' !== $color_override ? $color_override : self::light( $shadow['color'] ?? '' ) );
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
			$style = self::sanitize_enum( $border['style'], [ 'solid', 'dashed', 'dotted', 'double' ] );
			if ( '' !== $style ) {
				$css->add_property( 'border-style', $style );
			}
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
	 *                                    but NOT the `background-image: url()` — used
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
				$css->add_property( 'background-position', self::sanitize_position_pair( $image['position'] ?? '', 'center center' ) );
				$css->add_property( 'background-size', self::sanitize_enum( $image['size'] ?? '', [ 'cover', 'contain', 'auto' ], 'cover' ) );
				$css->add_property( 'background-repeat', self::sanitize_enum( $image['repeat'] ?? '', [ 'repeat', 'repeat-x', 'repeat-y', 'no-repeat', 'space', 'round' ], 'no-repeat' ) );
				$css->add_property( 'background-attachment', self::sanitize_enum( $image['attachment'] ?? '', [ 'scroll', 'fixed', 'local' ], 'scroll' ) );
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
			$css->set_selector( self::prefix_each( '[data-theme="dark"] ', $selector ) );
			$callback( $css );
		}

		if ( Dark_Mode_Settings::use_color_scheme() ) {
			$css->start_media_query( '@media (prefers-color-scheme: dark)' );
			$css->set_selector( $selector );
			$callback( $css );
			$css->end_media_query();
		}
	}

	/**
	 * Prefix EVERY selector in a group, not just the first one.
	 *
	 * `"[data-theme=dark] " . "a, b"` yields `[data-theme="dark"] a, b` — and that
	 * second selector, now unqualified, applies its DARK colours in light mode too.
	 * (That is exactly how a row of filter pills ended up with a dark background on a
	 * white page.) Splitting on the comma and prefixing each part is the fix.
	 *
	 * @param string $prefix   Prefix to prepend, e.g. `[data-theme="dark"] `.
	 * @param string $selector One selector, or a comma-separated group.
	 * @return string
	 */
	private static function prefix_each( $prefix, $selector ) {
		$parts = array_filter( array_map( 'trim', explode( ',', (string) $selector ) ) );
		if ( empty( $parts ) ) {
			return $prefix . $selector;
		}
		return implode(
			', ',
			array_map(
				static function ( $part ) use ( $prefix ) {
					return $prefix . $part;
				},
				$parts
			)
		);
	}

	/**
	 * Open the media query for a non-desktop device (no-op on desktop).
	 *
	 * Pairs with close_device() around each device pass of a generator loop.
	 *
	 * @param CSS_Builder $css    Builder.
	 * @param string      $device Device key (desktop|tablet|mobile).
	 */
	public static function open_device( $css, $device ) {
		if ( 'desktop' !== $device ) {
			$css->start_media_query( $device );
		}
	}

	/**
	 * Close the media query opened by open_device() (no-op on desktop).
	 *
	 * @param CSS_Builder $css    Builder.
	 * @param string      $device Device key (desktop|tablet|mobile).
	 */
	public static function close_device( $css, $device ) {
		if ( 'desktop' !== $device ) {
			$css->end_media_query();
		}
	}

	/**
	 * Emit typography declarations for one device object onto a selector.
	 *
	 * Mirror of the editor-side applyTypography() preview builder — keep both in
	 * sync so the editor preview matches the front end.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Selector.
	 * @param array       $typo     Typography device object.
	 */
	public static function add_typography( $css, $selector, $typo ) {
		if ( ! is_array( $typo ) || empty( $typo ) ) {
			return;
		}

		$css->set_selector( $selector );

		$font_size = $typo['fontSize'] ?? [];
		if ( ! empty( $font_size['value'] ) ) {
			$css->add_property( 'font-size', self::with_unit( $font_size['value'], $font_size['unit'] ?? 'px' ) );
		}
		$font_weight = (string) ( $typo['fontWeight'] ?? '' );
		if ( in_array( $font_weight, [ 'normal', 'bold', 'bolder', 'lighter', '100', '200', '300', '400', '500', '600', '700', '800', '900' ], true ) ) {
			$css->add_property( 'font-weight', $font_weight );
		}
		$letter = $typo['letterSpacing'] ?? [];
		if ( isset( $letter['value'] ) && '' !== (string) $letter['value'] ) {
			$css->add_property( 'letter-spacing', self::with_unit( $letter['value'], $letter['unit'] ?? 'px' ) );
		}
		$text_transform = (string) ( $typo['textTransform'] ?? '' );
		if ( in_array( $text_transform, [ 'none', 'uppercase', 'lowercase', 'capitalize' ], true ) ) {
			$css->add_property( 'text-transform', $text_transform );
		}
		if ( isset( $typo['lineHeight'] ) && '' !== (string) $typo['lineHeight'] ) {
			$line_height = self::sanitize_bare_number( $typo['lineHeight'] );
			if ( '' !== $line_height ) {
				$css->add_property( 'line-height', $line_height );
			}
		}
	}

	/**
	 * Emit advanced-layout declarations (overflow / position / z-index) for one
	 * device object onto the already-selected element. Shared by every generator
	 * that exposes the Position & Overflow panel.
	 *
	 * @param CSS_Builder $css      Builder (selector already set).
	 * @param array       $advanced Device advancedLayout object.
	 */
	public static function add_advanced_layout( $css, $advanced ) {
		if ( ! is_array( $advanced ) || empty( $advanced ) ) {
			return;
		}
		if ( ! empty( $advanced['overflow'] ) ) {
			$overflow = self::sanitize_enum( $advanced['overflow'], [ 'visible', 'hidden', 'auto', 'scroll' ] );
			if ( '' !== $overflow ) {
				$css->add_property( 'overflow', $overflow );
			}
		}
		if ( ! empty( $advanced['position'] ) ) {
			$position = self::sanitize_enum( $advanced['position'], [ 'static', 'relative', 'absolute', 'fixed', 'sticky' ] );
			if ( '' !== $position ) {
				$css->add_property( 'position', $position );
			}
		}
		// Offsets: emit each side that has a value (an empty side stays `auto`, so
		// only the pinned edges are set — never a `0` shorthand fill).
		$inset = $advanced['inset'] ?? [];
		if ( is_array( $inset ) ) {
			$unit = $inset['unit'] ?? 'px';
			foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
				if ( isset( $inset[ $side ] ) && '' !== (string) $inset[ $side ] ) {
					$css->add_property( $side, self::with_unit( $inset[ $side ], $unit ) );
				}
			}
		}
		if ( isset( $advanced['zIndex'] ) && '' !== (string) $advanced['zIndex'] ) {
			$css->add_property( 'z-index', (int) $advanced['zIndex'] );
		}
	}

	/**
	 * Build a text-shadow value (offset + blur + colour). Returns '' when not
	 * enabled. Note: text-shadow has no spread, unlike box-shadow.
	 *
	 * Mirror of the editor-side applyTextShadow() preview builder.
	 *
	 * @param array  $shadow         Shadow object { enabled, horizontal, vertical, blur, color }.
	 * @param string $color_override Optional colour (used for the dark branch).
	 * @return string
	 */
	public static function text_shadow( $shadow, $color_override = '' ) {
		if ( ! is_array( $shadow ) || empty( $shadow['enabled'] ) ) {
			return '';
		}
		$h     = self::with_unit( $shadow['horizontal'] ?? '0' );
		$v     = self::with_unit( $shadow['vertical'] ?? '0' );
		$blur  = self::with_unit( $shadow['blur'] ?? '0' );
		$color = self::sanitize_color( '' !== $color_override ? $color_override : self::light( $shadow['color'] ?? '' ) );
		if ( '' === $color ) {
			$color = 'rgba(0,0,0,0.3)';
		}
		return trim( "$h $v $blur $color" );
	}

	/**
	 * Emit a text stroke (width + colour) onto a selector — light at the base,
	 * dark under the dark-mode branch. Nothing is emitted when disabled or when
	 * no width is set.
	 *
	 * Mirror of the editor-side applyTextStroke() preview builder.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param array       $stroke   Stroke object { enabled, width:{value,unit}, color:{light,dark} }.
	 */
	public static function add_text_stroke( $css, $selector, $stroke ) {
		if ( ! is_array( $stroke ) || empty( $stroke['enabled'] ) ) {
			return;
		}
		$width = self::with_unit( $stroke['width']['value'] ?? '', $stroke['width']['unit'] ?? 'px' );
		if ( '' === $width ) {
			return;
		}
		$css->set_selector( $selector )->add_property( '-webkit-text-stroke-width', $width );

		$light = self::sanitize_color( self::light( $stroke['color'] ?? '' ) );
		if ( '' !== $light ) {
			$css->add_property( '-webkit-text-stroke-color', $light );
		}
		self::dark_color( $css, $selector, '-webkit-text-stroke-color', self::dark( $stroke['color'] ?? '' ) );
	}

	/**
	 * Emit one dark-mode declaration when a dark value is present.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Selector.
	 * @param string      $property CSS property.
	 * @param string      $value    Dark value ('' → nothing emitted).
	 */
	public static function dark_color( $css, $selector, $property, $value ) {
		if ( '' === $value ) {
			return;
		}

		// Validate by property shape: gradients here are always `background-image`;
		// composite values (box-shadow, text-shadow) are pre-sanitized by the
		// helper that built them, so only *-color / bare `background` need it here.
		if ( 'background-image' === $property ) {
			$value = self::sanitize_gradient( $value );
		} elseif ( 'background' === $property || false !== strpos( $property, 'color' ) ) {
			$value = self::sanitize_color( $value );
		}
		if ( '' === $value ) {
			return;
		}

		self::add_dark_mode(
			$css,
			$selector,
			function ( $css ) use ( $property, $value ) {
				$css->add_property( $property, $value );
			}
		);
	}

	/**
	 * Emit a text fill: a solid colour, or a gradient painted through the text
	 * via background-clip. Light at the base, dark under the dark-mode branch.
	 *
	 * Mirror of the editor-side applyTextFill() preview builder.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param string      $type     'color' or 'gradient'.
	 * @param mixed       $color    Colour pair.
	 * @param mixed       $gradient Gradient pair.
	 */
	public static function add_text_fill( $css, $selector, $type, $color, $gradient ) {
		if ( 'gradient' === $type ) {
			$light = self::sanitize_gradient( self::light( $gradient ) );
			if ( '' !== $light ) {
				$css->set_selector( $selector )
					->add_property( 'background-image', $light )
					->add_property( '-webkit-background-clip', 'text' )
					->add_property( 'background-clip', 'text' )
					->add_property( '-webkit-text-fill-color', 'transparent' )
					->add_property( 'color', 'transparent' );
			}
			self::dark_color( $css, $selector, 'background-image', self::dark( $gradient ) );
			return;
		}

		$light = self::sanitize_color( self::light( $color ) );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( 'color', $light );
		}
		self::dark_color( $css, $selector, 'color', self::dark( $color ) );
	}

	/**
	 * Emit a background fill: a solid colour, or a gradient background-image.
	 * Light at the base, dark under the dark-mode branch.
	 *
	 * Mirror of the editor-side applyBgFill() preview builder.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param string      $type     'color' or 'gradient'.
	 * @param mixed       $color    Colour pair.
	 * @param mixed       $gradient Gradient pair.
	 */
	public static function add_bg_fill( $css, $selector, $type, $color, $gradient ) {
		if ( 'gradient' === $type ) {
			$light = self::sanitize_gradient( self::light( $gradient ) );
			if ( '' !== $light ) {
				$css->set_selector( $selector )->add_property( 'background-image', $light );
			}
			self::dark_color( $css, $selector, 'background-image', self::dark( $gradient ) );
			return;
		}

		$light = self::sanitize_color( self::light( $color ) );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( 'background', $light );
		}
		self::dark_color( $css, $selector, 'background', self::dark( $color ) );
	}

	/**
	 * Emit a colour pair onto a selector: light value at the base, dark under the
	 * dark-mode branch. Nothing is emitted when neither light nor dark is set.
	 *
	 * @param CSS_Builder $css      Builder.
	 * @param string      $selector Target selector.
	 * @param string      $property CSS property (e.g. `color` / `background`).
	 * @param mixed       $color    Colour pair `{ light, dark }`.
	 */
	public static function color_pair( $css, $selector, $property, $color ) {
		$light = self::sanitize_color( self::light( $color ) );
		if ( '' !== $light ) {
			$css->set_selector( $selector )->add_property( $property, $light );
		}
		self::dark_color( $css, $selector, $property, self::dark( $color ) );
	}

	/**
	 * Emit numbered-pagination styling shared by list blocks (Post Grid, RSS).
	 *
	 * Reads the standard `pagination*` attributes and targets the WP-style
	 * `.page-numbers` links inside `$pager`: alignment on the nav, link text /
	 * background / radius, and the active-page text / background. Light at the
	 * base, dark under the dark-mode branch — nothing emitted unless the user set
	 * a value, so untouched links inherit the theme.
	 *
	 * @param CSS_Builder $css   Builder.
	 * @param array       $attrs Attributes (paginationAlign/Color/ActiveColor/Background/ActiveBackground/Radius).
	 * @param string      $pager Pagination nav selector (e.g. `.flexa-rss-a .flexa-rss__pagination`).
	 */
	public static function add_pagination( $css, $attrs, $pager ) {
		$page_num  = $pager . ' .page-numbers';
		$page_cur  = $pager . ' .page-numbers.current';
		$align_map = [ 'left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end' ];

		$align = self::sanitize_enum( (string) ( $attrs['paginationAlign'] ?? '' ), [ 'left', 'center', 'right' ] );
		if ( '' !== $align ) {
			$css->set_selector( $pager )->add_property( 'justify-content', $align_map[ $align ] );
		}

		self::color_pair( $css, $page_num, 'color', $attrs['paginationColor'] ?? '' );
		self::color_pair( $css, $page_num, 'background', $attrs['paginationBackground'] ?? '' );

		$radius = $attrs['paginationRadius'] ?? [];
		if ( ! empty( $radius['value'] ) ) {
			$css->set_selector( $page_num )->add_property( 'border-radius', self::with_unit( $radius['value'], $radius['unit'] ?? 'px' ) );
		}

		$font = $attrs['paginationFontSize'] ?? [];
		if ( ! empty( $font['value'] ) ) {
			$css->set_selector( $page_num )->add_property( 'font-size', self::with_unit( $font['value'], $font['unit'] ?? 'px' ) );
		}

		self::color_pair( $css, $page_cur, 'color', $attrs['paginationActiveColor'] ?? '' );
		self::color_pair( $css, $page_cur, 'background', $attrs['paginationActiveBackground'] ?? '' );

		// Hover PREVIEWS the active state: pointing at a page shows the colours it will
		// wear once it IS the current page. So there is no third colour pair to set, and
		// no way to configure a hover that contradicts the page it leads to. Only the
		// LINKS react — `.current` is already there, and a page that hovers into its own
		// colours would look inert.
		$page_hover = $pager . ' a.page-numbers:hover, ' . $pager . ' a.page-numbers:focus-visible';
		self::color_pair( $css, $page_hover, 'color', $attrs['paginationActiveColor'] ?? '' );
		self::color_pair( $css, $page_hover, 'background', $attrs['paginationActiveBackground'] ?? '' );
	}

	/**
	 * Emit load-more button styling shared by list blocks (Post Grid, RSS):
	 * alignment on the wrapper, text + background colour on the button. Light at
	 * the base, dark under the dark-mode branch — nothing emitted unless the user
	 * set a value, so an untouched button keeps its `wp-element-button` theme look.
	 *
	 * @param CSS_Builder $css   Builder.
	 * @param array       $attrs Attributes (paginationAlign / loadMoreColor / loadMoreBackground).
	 * @param string      $wrap  Load-more wrapper selector.
	 * @param string      $btn   Load-more button selector.
	 */
	public static function add_loadmore( $css, $attrs, $wrap, $btn ) {
		$align_map = [ 'left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end' ];
		$align     = self::sanitize_enum( (string) ( $attrs['paginationAlign'] ?? '' ), [ 'left', 'center', 'right' ] );
		if ( '' !== $align ) {
			$css->set_selector( $wrap )->add_property( 'justify-content', $align_map[ $align ] );
		}

		self::color_pair( $css, $btn, 'color', $attrs['loadMoreColor'] ?? '' );
		self::color_pair( $css, $btn, 'background', $attrs['loadMoreBackground'] ?? '' );

		$font = $attrs['paginationFontSize'] ?? [];
		if ( ! empty( $font['value'] ) ) {
			$css->set_selector( $btn )->add_property( 'font-size', self::with_unit( $font['value'], $font['unit'] ?? 'px' ) );
		}
	}
}
