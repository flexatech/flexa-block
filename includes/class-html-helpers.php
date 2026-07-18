<?php
declare(strict_types=1);
/**
 * HTML Helpers — render helpers shared by block render.php files.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static HTML helpers.
 */
class HTML_Helpers {

	/**
	 * Allowed semantic wrapper tags.
	 *
	 * @var array
	 */
	const ALLOWED_TAGS = [ 'div', 'section', 'article', 'aside', 'header', 'footer', 'main', 'nav' ];

	/**
	 * Validate a wrapper tag.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $default    Fallback tag.
	 * @return string
	 */
	public static function get_html_tag( $attributes, $default = 'div' ) {
		$tag = $attributes['htmlTag'] ?? $default;
		return in_array( $tag, self::ALLOWED_TAGS, true ) ? $tag : $default;
	}

	/**
	 * Merge base classes with custom className and responsive-visibility classes.
	 *
	 * @param array $base       Base classes.
	 * @param array $attributes Block attributes.
	 * @return array
	 */
	public static function build_wrapper_classes( $base, $attributes ) {
		$classes = $base;

		if ( ! empty( $attributes['className'] ) ) {
			$classes[] = $attributes['className'];
		}

		$visibility = $attributes['responsiveVisibility'] ?? [];
		if ( ! empty( $visibility['hideOnDesktop'] ) ) {
			$classes[] = 'flexa-hide-desktop';
		}
		if ( ! empty( $visibility['hideOnTablet'] ) ) {
			$classes[] = 'flexa-hide-tablet';
		}
		if ( ! empty( $visibility['hideOnMobile'] ) ) {
			$classes[] = 'flexa-hide-mobile';
		}

		return array_values( array_unique( array_filter( $classes ) ) );
	}

	/**
	 * Sanitise an inline-SVG string for safe echoing (icons).
	 *
	 * Allows only a fixed set of SVG shape tags and geometric/presentation
	 * attributes — no `<script>`, no `on*` handlers, no `href`/`xlink` — so a
	 * stored icon can be printed inline without becoming an XSS vector.
	 *
	 * @param string $svg Raw SVG markup.
	 * @return string Sanitised SVG (or '' when input isn't an <svg>).
	 */
	public static function svg_kses( $svg ) {
		$svg = (string) $svg;
		if ( '' === trim( $svg ) || false === stripos( $svg, '<svg' ) ) {
			return '';
		}

		$shape_attrs = [
			'd'                => true,
			'fill'             => true,
			'fill-rule'        => true,
			'clip-rule'        => true,
			'stroke'           => true,
			'stroke-width'     => true,
			'stroke-linecap'   => true,
			'stroke-linejoin'  => true,
			'stroke-dasharray' => true,
			'opacity'          => true,
			'transform'        => true,
			'cx'               => true,
			'cy'               => true,
			'r'                => true,
			'rx'               => true,
			'ry'               => true,
			'x'                => true,
			'y'                => true,
			'x1'               => true,
			'y1'               => true,
			'x2'               => true,
			'y2'               => true,
			'width'            => true,
			'height'           => true,
			'points'           => true,
		];

		$allowed = [
			'svg'      => [
				'class'       => true,
				'xmlns'       => true,
				'viewbox'     => true,
				'width'       => true,
				'height'      => true,
				'fill'        => true,
				'stroke'      => true,
				'stroke-width' => true,
				'stroke-linecap' => true,
				'stroke-linejoin' => true,
				'role'        => true,
				'aria-hidden' => true,
				'focusable'   => true,
			],
			'g'        => [ 'fill' => true, 'stroke' => true, 'transform' => true, 'opacity' => true ],
			'title'    => [],
			'defs'     => [],
			'path'     => $shape_attrs,
			'circle'   => $shape_attrs,
			'ellipse'  => $shape_attrs,
			'rect'     => $shape_attrs,
			'line'     => $shape_attrs,
			'polyline' => $shape_attrs,
			'polygon'  => $shape_attrs,
		];

		$clean = wp_kses( $svg, $allowed );

		// wp_kses lowercases attribute names, but a few SVG attributes are
		// case-sensitive in the browser — restore them, or the icon won't scale.
		return str_ireplace(
			[ 'viewbox=', 'preserveaspectratio=' ],
			[ 'viewBox=', 'preserveAspectRatio=' ],
			$clean
		);
	}

	/**
	 * Render a standalone IconValue to safe HTML.
	 *
	 * Uploaded SVGs become an <img> (the media library already validated the file);
	 * builtin/library icons print their stored inline SVG through svg_kses. Returns
	 * '' when the icon is unset / `none` / empty. Shared by every block that shows a
	 * single glyph (icon, icon-list, modal trigger) so the resolve logic lives once.
	 *
	 * @param mixed $icon     Icon config (IconValue array).
	 * @param int   $img_size Pixel width/height stamped on uploaded-SVG <img>.
	 * @return string Safe icon HTML or ''.
	 */
	public static function icon_html( $icon, $img_size = 24 ) {
		$icon   = is_array( $icon ) ? $icon : [];
		$source = $icon['source'] ?? 'none';

		if ( 'upload' === $source && '' !== (string) ( $icon['url'] ?? '' ) ) {
			return '<img class="flexa-icon" src="' . esc_url( $icon['url'] ) . '" alt="" width="' . (int) $img_size . '" height="' . (int) $img_size . '" loading="lazy" />';
		}

		if ( 'none' !== $source && '' !== (string) ( $icon['markup'] ?? '' ) ) {
			return self::svg_kses( $icon['markup'] );
		}

		return '';
	}

	/**
	 * Render a neutral "no image" placeholder — a framed picture glyph shown in
	 * place of a missing thumbnail so cards keep a consistent height/layout. Shared
	 * by every media-card block (Post Grid, RSS). Purely decorative (aria-hidden);
	 * inherits `currentColor` and carries only structural styling from the shared
	 * `.flexa-image-placeholder` CSS. An optional inline style (e.g. `aspect-ratio`)
	 * lets the caller match the block's chosen image ratio.
	 *
	 * @param string $extra_class Extra class(es) appended to the wrapper.
	 * @param string $style       Inline style attribute value (already trusted CSS, e.g. `aspect-ratio:16/9`).
	 * @return string Safe placeholder HTML.
	 */
	public static function image_placeholder( $extra_class = '', $style = '' ) {
		$class    = 'flexa-image-placeholder' . ( '' !== $extra_class ? ' ' . $extra_class : '' );
		$style_at = '' !== $style ? ' style="' . esc_attr( $style ) . '"' : '';
		return '<span class="' . esc_attr( $class ) . '"' . $style_at . ' aria-hidden="true">'
			. '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" focusable="false"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>'
			. '</span>';
	}

	/**
	 * Render an inline clock glyph for a feed date/time. Inherits `currentColor` and
	 * sizes to 1em. Mirrors the editor `ClockIcon`. Shared by the feed blocks.
	 *
	 * @param string $class Class(es) for the <svg> (sizing/spacing).
	 * @return string Safe SVG.
	 */
	public static function clock_icon( $class = '' ) {
		$class_attr = '' !== $class ? ' class="' . esc_attr( $class ) . '"' : '';
		return '<svg' . $class_attr . ' viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9"/><path d="M12 7.5V12l3 1.5"/></svg>';
	}

	/**
	 * Render a "media type" badge for a social-feed thumbnail — a small chip marking
	 * a video (play glyph) or an album / carousel (stacked squares). Shared by the
	 * Facebook and Instagram feed blocks; mirrors the editor `MediaBadge` component.
	 * Returns '' for a plain single image.
	 *
	 * @param string $type        Normalised media type: 'video' | 'album' | anything else.
	 * @param string $extra_class Extra class(es) appended to the badge (for positioning).
	 * @return string Safe badge HTML, or ''.
	 */
	public static function media_badge( $type, $extra_class = '' ) {
		$type = (string) $type;
		if ( 'video' === $type ) {
			$svg = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M8 5v14l11-7z"/></svg>';
		} elseif ( 'album' === $type ) {
			$svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M4 16V6a2 2 0 0 1 2-2h10"/></svg>';
		} else {
			return '';
		}
		$class = 'flexa-media-badge' . ( '' !== $extra_class ? ' ' . $extra_class : '' );
		return '<span class="' . esc_attr( $class ) . '" aria-hidden="true">' . $svg . '</span>';
	}

	/**
	 * Build custom data-* attributes from htmlAttributes.customAttributes.
	 *
	 * Only `data-*` attribute names are emitted. Anything else — event handlers
	 * (`onclick`, `onmouseover`, …), `style`, `href`, etc. — is dropped, so a
	 * stored custom attribute can never become an inline-script XSS vector.
	 *
	 * @param array $attributes Block attributes.
	 * @return string Leading-space-prefixed attribute string or ''.
	 */
	public static function build_data_attrs( $attributes ) {
		$html = '';
		$custom = $attributes['htmlAttributes']['customAttributes'] ?? [];
		if ( is_array( $custom ) ) {
			foreach ( $custom as $pair ) {
				$key = isset( $pair['key'] ) ? sanitize_key( $pair['key'] ) : '';
				$val = isset( $pair['value'] ) ? (string) $pair['value'] : '';
				// Restrict to data-* names; sanitize_key() alone would let
				// through `onclick`/`style`/etc. and reopen an XSS hole.
				if ( '' === $key || 0 !== strpos( $key, 'data-' ) ) {
					continue;
				}
				$html .= ' ' . $key . '="' . esc_attr( $val ) . '"';
			}
		}
		return $html;
	}

	/**
	 * Inline formatting allowed inside a promo heading / button label.
	 *
	 * @var array
	 */
	const PROMO_INLINE_ALLOWED = [
		'strong' => [],
		'em'     => [],
		'b'      => [],
		'i'      => [],
		'br'     => [],
		'span'   => [],
	];

	/**
	 * Build the shared "promo content" markup — a heading, a description and a
	 * primary + optional secondary CTA button. Shared by the Banner and CTA
	 * render.php files so the identical markup isn't copied (guide §4.5). Returns
	 * '' when nothing is filled in.
	 *
	 * @param array $attributes Block attributes (PromoContentAttributes shape).
	 * @return string
	 */
	public static function promo_content_html( $attributes ) {
		$text_tags   = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' ];
		$heading_tag = in_array( $attributes['headingTag'] ?? 'h2', $text_tags, true ) ? $attributes['headingTag'] : 'h2';

		$heading     = (string) ( $attributes['heading'] ?? '' );
		$description = (string) ( $attributes['description'] ?? '' );

		$desc_allowed = self::PROMO_INLINE_ALLOWED + [ 'a' => [ 'href' => true, 'target' => true, 'rel' => true ] ];

		$heading_html = '' !== trim( $heading )
			? '<' . $heading_tag . ' class="flexa-promo__heading">' . wp_kses( $heading, self::PROMO_INLINE_ALLOWED ) . '</' . $heading_tag . '>'
			: '';
		$description_html = '' !== trim( $description )
			? '<p class="flexa-promo__description">' . wp_kses( $description, $desc_allowed ) . '</p>'
			: '';

		$text_html = ( '' !== $heading_html || '' !== $description_html )
			? '<div class="flexa-promo__text">' . $heading_html . $description_html . '</div>'
			: '';

		$primary_html   = self::promo_button_html( $attributes, 'primary' );
		$secondary_html = ! empty( $attributes['showSecondary'] ) ? self::promo_button_html( $attributes, 'secondary' ) : '';
		$buttons_html   = ( '' !== $primary_html || '' !== $secondary_html )
			? '<div class="flexa-promo__buttons">' . $primary_html . $secondary_html . '</div>'
			: '';

		if ( '' === $text_html && '' === $buttons_html ) {
			return '';
		}

		return '<div class="flexa-promo__content">' . $text_html . $buttons_html . '</div>';
	}

	/**
	 * Build one promo CTA button (`<a>`), reading the prefixed attributes
	 * (`primary*` / `secondary*`). Returns '' when the label is empty.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $kind       'primary' or 'secondary'.
	 * @return string
	 */
	private static function promo_button_html( $attributes, $kind ) {
		$prefix = 'secondary' === $kind ? 'secondary' : 'primary';
		$text   = (string) ( $attributes[ $prefix . 'Text' ] ?? '' );
		if ( '' === trim( $text ) ) {
			return '';
		}

		$url     = (string) ( $attributes[ $prefix . 'Url' ] ?? '' );
		$new_tab = '_blank' === ( $attributes[ $prefix . 'Target' ] ?? '' );
		$rel     = trim( (string) ( $attributes[ $prefix . 'Rel' ] ?? '' ) );

		$link_attrs = 'class="wp-element-button flexa-promo__button flexa-promo__button--' . $prefix . '"';
		if ( '' !== $url ) {
			$link_attrs .= ' href="' . esc_url( $url ) . '"';
		}
		if ( $new_tab ) {
			$link_attrs .= ' target="_blank"';
			if ( '' === $rel ) {
				$rel = 'noopener noreferrer';
			}
		}
		if ( '' !== $rel ) {
			$rel         = trim( (string) preg_replace( '/[^a-z0-9 _-]/i', '', $rel ) );
			$link_attrs .= ' rel="' . esc_attr( $rel ) . '"';
		}

		// Resolve the button icon to inline SVG (builtin/library) or an <img> (upload).
		$icon_cfg = is_array( $attributes[ $prefix . 'Icon' ] ?? null ) ? $attributes[ $prefix . 'Icon' ] : [];
		$icon_pos = 'before' === ( $icon_cfg['position'] ?? 'after' ) ? 'before' : 'after';
		$icon_svg = '';
		if ( 'upload' === ( $icon_cfg['source'] ?? 'none' ) && '' !== (string) ( $icon_cfg['url'] ?? '' ) ) {
			$icon_svg = '<img class="flexa-icon" src="' . esc_url( $icon_cfg['url'] ) . '" alt="" width="20" height="20" loading="lazy" />';
		} elseif ( 'none' !== ( $icon_cfg['source'] ?? 'none' ) && '' !== (string) ( $icon_cfg['markup'] ?? '' ) ) {
			$icon_svg = self::svg_kses( $icon_cfg['markup'] );
		}

		$label = '<span class="flexa-promo__button-text">' . wp_kses( $text, self::PROMO_INLINE_ALLOWED ) . '</span>';

		$inner = '';
		if ( '' !== $icon_svg && 'before' === $icon_pos ) {
			$inner .= $icon_svg;
		}
		$inner .= $label;
		if ( '' !== $icon_svg && 'after' === $icon_pos ) {
			$inner .= $icon_svg;
		}

		return '<a ' . $link_attrs . '>' . $inner . '</a>';
	}

	/**
	 * Allowed native input types for a subscribe-form field.
	 *
	 * @var array
	 */
	const FIELD_INPUT_TYPES = [ 'text', 'email', 'tel', 'url', 'number', 'date' ];

	/**
	 * Slugify a label into a safe field key (a-z 0-9 and underscores).
	 *
	 * Mirrors the editor-side deriveFieldName() so the front-end name matches the
	 * hint shown in the inspector.
	 *
	 * @param string $label Raw label text.
	 * @return string
	 */
	public static function slugify_field_name( $label ) {
		$slug = strtolower( (string) $label );
		$slug = preg_replace( '/[^a-z0-9]+/', '_', $slug );
		return trim( (string) $slug, '_' );
	}

	/**
	 * Resolve a field's submitted `name`: the explicit `fieldName`, else derived
	 * from the label, else the block-supplied fallback.
	 *
	 * @param array  $attributes Field attributes.
	 * @param string $fallback   Fallback key when nothing else resolves.
	 * @return string
	 */
	public static function subscribe_field_name( $attributes, $fallback ) {
		$name = trim( (string) ( $attributes['fieldName'] ?? '' ) );
		if ( '' === $name ) {
			$name = self::slugify_field_name( (string) ( $attributes['label'] ?? '' ) );
		}
		if ( '' === $name ) {
			$name = $fallback;
		}
		return $name;
	}

	/**
	 * Render one subscribe-form field: a `.flexa-field` wrapper holding an optional
	 * label and the input (or textarea). Shared by all four field children so the
	 * markup isn't copied (guide §4.5). All user text is escaped here.
	 *
	 * @param array  $attributes Field attributes (SubscribeFieldAttributes shape).
	 * @param string $type       Native input type (used when not multiline).
	 * @param string $fallback   Fallback field name.
	 * @param bool   $multiline  Render a <textarea> instead of an <input>.
	 * @return string
	 */
	public static function subscribe_field( $attributes, $type, $fallback, $multiline = false ) {
		$type        = in_array( $type, self::FIELD_INPUT_TYPES, true ) ? $type : 'text';
		$label       = (string) ( $attributes['label'] ?? '' );
		$show_label  = ( $attributes['showLabel'] ?? true ) !== false;
		$placeholder = (string) ( $attributes['placeholder'] ?? '' );
		$required    = ! empty( $attributes['required'] );
		$name        = self::subscribe_field_name( $attributes, $fallback );
		$block_id    = (string) ( $attributes['blockId'] ?? '' );
		$input_id    = 'flexa-field-' . sanitize_html_class( '' !== $block_id ? $block_id : $name );

		$attr = ' name="' . esc_attr( $name ) . '" id="' . esc_attr( $input_id ) . '" class="flexa-field__control"';
		if ( '' !== $placeholder ) {
			$attr .= ' placeholder="' . esc_attr( $placeholder ) . '"';
		}
		if ( $required ) {
			$attr .= ' required aria-required="true"';
		}
		// Phone: hint the numeric keypad and enforce a lenient phone shape so the
		// browser rejects obvious garbage on submit.
		if ( 'tel' === $type ) {
			$attr .= ' inputmode="tel" pattern="[0-9+()\-.\s]{6,}" title="' . esc_attr__( 'Please enter a valid phone number (digits, spaces, + ( ) - . ).', 'flexa-block' ) . '"';
		}

		if ( $multiline ) {
			$rows    = (int) ( $attributes['rows'] ?? 4 );
			$rows    = max( 2, min( 20, $rows ) );
			$control = '<textarea' . $attr . ' rows="' . $rows . '"></textarea>';
		} else {
			$control = '<input type="' . esc_attr( $type ) . '"' . $attr . ' />';
		}

		$label_html = '';
		if ( $show_label && '' !== trim( $label ) ) {
			$req_mark   = $required ? ' <span class="flexa-field__required">*</span>' : '';
			$label_html = '<label class="flexa-field__label" for="' . esc_attr( $input_id ) . '">' . esc_html( $label ) . $req_mark . '</label>';
		}

		return self::field_shell( $attributes, $label_html . $control );
	}

	/**
	 * Render a choice subscribe-form field: a select dropdown, or a radio /
	 * checkbox group. Shared by those three field children. All user text escaped.
	 *
	 * @param array  $attributes Field attributes (SubscribeFieldAttributes shape).
	 * @param string $variant    'select' | 'radio' | 'checkbox'.
	 * @param string $fallback   Fallback field name.
	 * @return string
	 */
	public static function subscribe_choice_field( $attributes, $variant, $fallback ) {
		$variant     = in_array( $variant, [ 'select', 'radio', 'checkbox' ], true ) ? $variant : 'select';
		$label       = (string) ( $attributes['label'] ?? '' );
		$show_label  = ( $attributes['showLabel'] ?? true ) !== false;
		$placeholder = (string) ( $attributes['placeholder'] ?? '' );
		$required    = ! empty( $attributes['required'] );
		$name        = self::subscribe_field_name( $attributes, $fallback );
		$block_id    = (string) ( $attributes['blockId'] ?? '' );
		$base_id     = 'flexa-field-' . sanitize_html_class( '' !== $block_id ? $block_id : $name );
		$options     = self::normalize_field_options( $attributes['options'] ?? [] );

		$req_mark = $required ? ' <span class="flexa-field__required">*</span>' : '';

		if ( 'select' === $variant ) {
			$req_attr = $required ? ' required aria-required="true"' : '';
			$control  = '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $base_id ) . '" class="flexa-field__control"' . $req_attr . '>';
			if ( '' !== $placeholder ) {
				$control .= '<option value="">' . esc_html( $placeholder ) . '</option>';
			}
			foreach ( $options as $opt ) {
				$control .= '<option value="' . esc_attr( $opt ) . '">' . esc_html( $opt ) . '</option>';
			}
			$control   .= '</select>';
			$label_html = ( $show_label && '' !== trim( $label ) )
				? '<label class="flexa-field__label" for="' . esc_attr( $base_id ) . '">' . esc_html( $label ) . $req_mark . '</label>'
				: '';
			return self::field_shell( $attributes, $label_html . $control );
		}

		// Radio / checkbox group. Native `required` is applied per-radio (a radio
		// group is satisfied by any one), and only to a lone checkbox (a consent
		// box) — never to every box of a multi-choice group, which would force all.
		$input_type = 'radio' === $variant ? 'radio' : 'checkbox';
		$single     = count( $options ) <= 1;
		// "At least one" for a group can't be expressed with native `required`
		// (checkbox) or shows a bare bubble (radio), so view.js validates it —
		// flagged here with data-required.
		$group_required = $required ? ' data-required="1"' : '';
		$group          = '<div class="flexa-field__options flexa-field__options--' . $variant . '" role="group"' . $group_required . '>';
		foreach ( $options as $i => $opt ) {
			$oid      = $base_id . '-' . $i;
			$req_attr = ( $required && ( 'radio' === $variant || $single ) ) ? ' required aria-required="true"' : '';
			$group   .= '<label class="flexa-field__option" for="' . esc_attr( $oid ) . '">'
				. '<input type="' . $input_type . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $oid ) . '" value="' . esc_attr( $opt ) . '" class="flexa-field__choice"' . $req_attr . ' />'
				. '<span>' . esc_html( $opt ) . '</span></label>';
		}
		$group     .= '</div>';
		$legend     = ( $show_label && '' !== trim( $label ) )
			? '<span class="flexa-field__label">' . esc_html( $label ) . $req_mark . '</span>'
			: '';
		return self::field_shell( $attributes, $legend . $group );
	}

	/**
	 * Normalise a choice-options list: trim, drop blanks, cap the count.
	 *
	 * @param mixed $options Raw options (expected string[]).
	 * @return array<int,string>
	 */
	private static function normalize_field_options( $options ) {
		if ( ! is_array( $options ) ) {
			return [];
		}
		$out = [];
		foreach ( $options as $opt ) {
			$opt = trim( (string) $opt );
			if ( '' === $opt ) {
				continue;
			}
			$out[] = $opt;
			if ( count( $out ) >= 30 ) {
				break;
			}
		}
		return $out;
	}

	/**
	 * Render a toggle (on/off switch) subscribe-form field — a single checkbox
	 * styled as a switch that submits "Yes" when on.
	 *
	 * @param array  $attributes Field attributes.
	 * @param string $fallback   Fallback field name.
	 * @return string
	 */
	public static function subscribe_toggle_field( $attributes, $fallback ) {
		$label      = (string) ( $attributes['label'] ?? '' );
		$show_label = ( $attributes['showLabel'] ?? true ) !== false;
		$required   = ! empty( $attributes['required'] );
		$name       = self::subscribe_field_name( $attributes, $fallback );
		$block_id   = (string) ( $attributes['blockId'] ?? '' );
		$input_id   = 'flexa-field-' . sanitize_html_class( '' !== $block_id ? $block_id : $name );
		$req_attr   = $required ? ' required aria-required="true"' : '';
		$req_mark   = $required ? ' <span class="flexa-field__required">*</span>' : '';

		$control = '<span class="flexa-field__toggle">'
			. '<input type="checkbox" name="' . esc_attr( $name ) . '" id="' . esc_attr( $input_id ) . '" value="Yes" class="flexa-field__toggle-input"' . $req_attr . ' />'
			. '<span class="flexa-field__switch-track" aria-hidden="true"></span></span>';

		$label_html = ( $show_label && '' !== trim( $label ) )
			? '<label class="flexa-field__label" for="' . esc_attr( $input_id ) . '">' . esc_html( $label ) . $req_mark . '</label>'
			: '';

		return self::field_shell( $attributes, $label_html . $control );
	}

	/**
	 * Render a file-upload subscribe-form field. The actual file is transferred as
	 * multipart form data by view.js and attached to the notification email by
	 * Form_Handler; this only outputs the input.
	 *
	 * @param array  $attributes Field attributes.
	 * @param string $fallback   Fallback field name.
	 * @return string
	 */
	public static function subscribe_upload_field( $attributes, $fallback ) {
		$label       = (string) ( $attributes['label'] ?? '' );
		$show_label  = ( $attributes['showLabel'] ?? true ) !== false;
		$required    = ! empty( $attributes['required'] );
		$multiple    = ! empty( $attributes['multiple'] );
		$accept      = trim( (string) ( $attributes['accept'] ?? '' ) );
		$name        = self::subscribe_field_name( $attributes, $fallback );
		$field_name  = $multiple ? $name . '[]' : $name;
		$block_id    = (string) ( $attributes['blockId'] ?? '' );
		$input_id    = 'flexa-field-' . sanitize_html_class( '' !== $block_id ? $block_id : $name );

		$attr = ' name="' . esc_attr( $field_name ) . '" id="' . esc_attr( $input_id ) . '" class="flexa-field__control"';
		if ( '' !== $accept ) {
			$attr .= ' accept="' . esc_attr( $accept ) . '"';
		}
		if ( $multiple ) {
			$attr .= ' multiple';
		}
		if ( $required ) {
			$attr .= ' required aria-required="true"';
		}
		$control = '<input type="file"' . $attr . ' />';

		$req_mark   = $required ? ' <span class="flexa-field__required">*</span>' : '';
		$label_html = ( $show_label && '' !== trim( $label ) )
			? '<label class="flexa-field__label" for="' . esc_attr( $input_id ) . '">' . esc_html( $label ) . $req_mark . '</label>'
			: '';

		return self::field_shell( $attributes, $label_html . $control );
	}

	/**
	 * Render a hidden subscribe-form field — an invisible input carrying a fixed
	 * value submitted with the form (e.g. a campaign source tag).
	 *
	 * @param array  $attributes Field attributes.
	 * @param string $fallback   Fallback field name.
	 * @return string
	 */
	public static function subscribe_hidden_field( $attributes, $fallback ) {
		$name  = self::subscribe_field_name( $attributes, $fallback );
		$value = (string) ( $attributes['value'] ?? '' );

		return '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" />';
	}

	/**
	 * Wrap a field's inner markup (label + control) in the `.flexa-field` shell —
	 * width class, blockId class, className / visibility and the block wrapper
	 * attributes. Shared by subscribe_field() and subscribe_choice_field().
	 *
	 * @param array  $attributes Field attributes.
	 * @param string $inner      Already-escaped inner markup.
	 * @return string
	 */
	private static function field_shell( $attributes, $inner ) {
		$width    = in_array( $attributes['width'] ?? '100', [ '100', '50' ], true ) ? $attributes['width'] : '100';
		$block_id = (string) ( $attributes['blockId'] ?? '' );

		$classes = [ 'flexa-field', 'flexa-field--w' . $width ];
		if ( '' !== $block_id ) {
			$classes[] = 'flexa-subscribe-field-' . sanitize_html_class( $block_id );
		}
		$classes = self::build_wrapper_classes( $classes, $attributes );

		$wrapper = get_block_wrapper_attributes( [ 'class' => implode( ' ', $classes ) ] );

		return sprintf( '<div %1$s>%2$s</div>', $wrapper, $inner );
	}
}
