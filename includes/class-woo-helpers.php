<?php
declare(strict_types=1);
/**
 * WooCommerce Helpers — shared by the product-* block render.php files.
 *
 * The product blocks (product-image, product-price, product-detail,
 * product-name, product-rating) are dynamic: they read the *current* product
 * on a single-product page rather than user-entered content. This helper
 * resolves that product once, so every block renders the same way and outputs
 * nothing when there is no product in context (i.e. off a single-product page).
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static WooCommerce helpers.
 */
final class Woo_Helpers {

	/**
	 * Whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Resolve the product for the current context.
	 *
	 * Prefers the global `$product` set by WooCommerce inside the single-product
	 * loop; falls back to the queried post. Returns null when WooCommerce is
	 * inactive or there is no product in context — callers should then render
	 * nothing, which keeps these blocks scoped to single-product pages.
	 *
	 * Duck-typed against `WC_Product` by name so this file carries no hard
	 * dependency on the WooCommerce classes (they are absent without the plugin).
	 *
	 * @return object|null A WC_Product instance, or null.
	 */
	public static function current_product() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$product = $GLOBALS['product'] ?? null;
		if ( is_object( $product ) && is_a( $product, 'WC_Product' ) ) {
			return $product;
		}

		$id = function_exists( 'get_the_ID' ) ? get_the_ID() : 0;
		if ( $id ) {
			$resolved = wc_get_product( $id );
			if ( is_object( $resolved ) && is_a( $resolved, 'WC_Product' ) ) {
				return $resolved;
			}
		}

		return null;
	}

	/**
	 * Trim HTML to a word count without throwing the markup away.
	 *
	 * `wp_trim_words()` runs `wp_strip_all_tags()` first, so a capped excerpt
	 * comes back as plain text — links, emphasis and everything else the shop
	 * owner wrote are gone, and any styling aimed at them has nothing to paint.
	 * This walks the tags and the text separately: words are only counted in
	 * text nodes, and whatever tags are still open when the cap is reached get
	 * closed, so the result is balanced markup rather than a cut-off fragment.
	 *
	 * Self-closing and void elements are left alone. Returns the input untouched
	 * when it already fits, so short excerpts gain no ellipsis.
	 *
	 * @param string $html  Source markup.
	 * @param int    $words Maximum number of words. Zero or less returns $html.
	 * @param string $more  Appended when something was cut (default &hellip;).
	 * @return string
	 */
	public static function trim_words_html( string $html, int $words, string $more = null ): string {
		if ( $words <= 0 || '' === trim( $html ) ) {
			return $html;
		}

		if ( null === $more ) {
			/* translators: used at the end of a shortened excerpt. */
			$more = __( '&hellip;', 'flexa-block' );
		}

		// Void elements never go on the stack — they have nothing to close.
		$void = [ 'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr' ];

		$tokens = preg_split( '/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $tokens ) ) {
			return $html;
		}

		$open      = [];
		$out       = '';
		$remaining = $words;
		$cut       = false;

		foreach ( $tokens as $token ) {
			if ( '' === $token ) {
				continue;
			}

			// A tag: pass it through and track whether it opened or closed.
			if ( '<' === $token[0] ) {
				$out .= $token;

				if ( preg_match( '#^</\s*([a-zA-Z0-9-]+)#', $token, $m ) ) {
					$name = strtolower( $m[1] );
					$at   = array_search( $name, array_reverse( $open, true ), true );
					if ( false !== $at ) {
						unset( $open[ $at ] );
						$open = array_values( $open );
					}
				} elseif ( preg_match( '#^<\s*([a-zA-Z0-9-]+)#', $token, $m ) ) {
					$name = strtolower( $m[1] );
					if ( ! in_array( $name, $void, true ) && '/>' !== substr( rtrim( $token ), -2 ) ) {
						$open[] = $name;
					}
				}

				continue;
			}

			// A text node: take words from it until the cap is reached.
			$parts = preg_split( '/(\s+)/u', $token, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
			if ( ! is_array( $parts ) ) {
				continue;
			}

			foreach ( $parts as $part ) {
				if ( '' === trim( $part ) ) {
					// Whitespace costs nothing, but only keep it inside the excerpt.
					if ( $remaining > 0 ) {
						$out .= $part;
					}
					continue;
				}

				// Running out exactly at the last word is not a cut — the excerpt
				// simply fits, and it should gain no ellipsis.
				if ( $remaining <= 0 ) {
					$cut = true;
					break 2;
				}

				$out .= $part;
				--$remaining;
			}
		}

		// Nothing was dropped: hand back the original rather than a rebuilt copy.
		if ( ! $cut ) {
			return $html;
		}

		$out = rtrim( $out ) . $more;

		// Close whatever the cut left open, innermost first.
		foreach ( array_reverse( $open ) as $name ) {
			$out .= '</' . $name . '>';
		}

		return $out;
	}

	/**
	 * Build the markup shared by the taxonomy list blocks (product-categories,
	 * product-tags): an optional label followed by the product's terms, each one
	 * linked to its archive unless the user turned links off.
	 *
	 * The separator is only emitted for the inline layout — the badge and list
	 * layouts space their terms with CSS, so a stray comma would show up as a
	 * loose glyph between chips.
	 *
	 * @param object $product  WC_Product instance.
	 * @param string $taxonomy Taxonomy slug (`product_cat` / `product_tag`).
	 * @param array  $attrs    Block attributes.
	 * @param string $slug     Block slug, used as the CSS class prefix.
	 * @return string Markup, or an empty string when the product has no terms.
	 */
	public static function term_list_html( $product, $taxonomy, $attrs, $slug ): string {
		$terms = get_the_terms( $product->get_id(), $taxonomy );
		if ( ! is_array( $terms ) || empty( $terms ) ) {
			return '';
		}

		$max = (int) ( $attrs['maxTerms'] ?? 0 );
		if ( $max > 0 ) {
			$terms = array_slice( $terms, 0, $max );
		}

		$layout = in_array( $attrs['termLayout'] ?? 'inline', [ 'inline', 'badge', 'list' ], true ) ? $attrs['termLayout'] : 'inline';
		$linked = ! isset( $attrs['linkToArchive'] ) || ! empty( $attrs['linkToArchive'] );
		$target = ! empty( $attrs['linkTarget'] ) ? ' target="_blank" rel="noopener"' : '';
		$sep    = 'inline' === $layout ? (string) ( $attrs['separator'] ?? ', ' ) : '';
		$base   = 'flexa-' . $slug;

		$parts = [];
		foreach ( $terms as $term ) {
			$name = esc_html( $term->name );
			$url  = $linked ? get_term_link( $term ) : '';
			if ( $linked && ! is_wp_error( $url ) && '' !== $url ) {
				$parts[] = '<a class="' . $base . '__term" href="' . esc_url( $url ) . '"' . $target . '>' . $name . '</a>';
			} else {
				$parts[] = '<span class="' . $base . '__term">' . $name . '</span>';
			}
		}

		$glue = '' !== $sep ? '<span class="' . $base . '__sep">' . esc_html( $sep ) . '</span>' : '';
		$html = '<span class="' . $base . '__terms">' . implode( $glue, $parts ) . '</span>';

		if ( ! empty( $attrs['showLabel'] ) ) {
			$label = trim( (string) ( $attrs['labelText'] ?? '' ) );
			if ( '' !== $label ) {
				$html = '<span class="' . $base . '__label">' . esc_html( $label ) . '</span>' . $html;
			}
		}

		return $html;
	}
}
