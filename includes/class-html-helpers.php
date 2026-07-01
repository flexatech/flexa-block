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
	 * Build custom data-* attributes from htmlAttributes.customAttributes.
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
				if ( '' === $key ) {
					continue;
				}
				$html .= ' ' . $key . '="' . esc_attr( $val ) . '"';
			}
		}
		return $html;
	}
}
