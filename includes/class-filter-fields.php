<?php
declare(strict_types=1);
/**
 * Filter Fields — shared markup + state for the post-filter child blocks.
 *
 * The three children (search, taxonomy, reset) each render one control inside the
 * parent's GET form. They all need the same three things, so none of it is copied
 * into three render.php files:
 *
 *   1. WHICH grid they target. The parent provides its `targetGridId` as block
 *      context, but that value may be empty ("auto"), in which case the target is
 *      the first Post Grid on the page — resolved identically here and in the view
 *      script, so the no-JS form posts to the same grid the AJAX path would.
 *   2. The CURRENT state, so a filtered URL renders its controls pre-filled (a
 *      shared or bookmarked link has to come back looking the way it left).
 *   3. The `.flexa-filter-field` shell, so the parent's one CSS generator can style
 *      every child from a single selector.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared helpers for flexa/filter-* blocks.
 */
final class Filter_Fields {

	const WIDTHS = [ '100', '50', 'auto' ];

	/**
	 * Term count past which a multi-choice dropdown offers a box to narrow its own list.
	 * Below it the panel is a glance; above it, it is a scrollbar and a memory test.
	 */
	const MENU_SEARCH_FROM = 12;

	/**
	 * Resolve the grid a filter bar — or one of its children — targets.
	 *
	 * The parent and the children MUST land on the same grid: the parent names it in
	 * the form's `data-` attribute (what the view script drives) while each child bakes
	 * it into its field names (what the grid actually reads). Disagree, and the bar
	 * renders perfectly and filters nothing. So both go through here.
	 *
	 * Two things it has to get right:
	 *
	 *  - The parent does not receive its own `providesContext` — that only flows DOWN.
	 *    It has to read its own attribute, or it would silently fall back to "first
	 *    grid on the page" while its children obeyed the author's choice.
	 *  - A named grid that isn't on the page any more (the author duplicated the grid,
	 *    which re-generates its blockId, or deleted it) is not a target. Falling back to
	 *    the first grid is what keeps a bar working instead of pointing at a ghost.
	 *
	 * @param \WP_Block|null $block Block instance (the parent's own attributes, or the
	 *                              context it provides to a child).
	 * @return string Sanitized blockId, or '' when the page has no grid at all.
	 */
	public static function target( ?\WP_Block $block ): string {
		$raw = '';

		if ( $block instanceof \WP_Block ) {
			// Child: the context. Parent: its own attribute — it gets no context of its own.
			$raw = (string) ( $block->context['flexa/filterTarget'] ?? ( $block->attributes['targetGridId'] ?? '' ) );
		}

		return Post_Query::resolve_target( $raw, (int) get_the_ID() );
	}

	/**
	 * The search term currently applied to a grid, for pre-filling the input.
	 *
	 * @param string $gid Sanitized blockId of the grid.
	 * @return string
	 */
	public static function current_search( string $gid ): string {
		if ( '' === $gid ) {
			return '';
		}
		$key = Post_Query::search_key( $gid );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter state.
		if ( ! isset( $_GET[ $key ] ) || ! is_string( $_GET[ $key ] ) ) {
			return '';
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
	}

	/**
	 * The term slugs currently applied to a grid for one taxonomy, for marking the
	 * matching options selected.
	 *
	 * @param string $gid      Sanitized blockId of the grid.
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int, string>
	 */
	public static function current_terms( string $gid, string $taxonomy ): array {
		if ( '' === $gid || '' === $taxonomy ) {
			return [];
		}
		$key = Post_Query::tax_key( $gid, $taxonomy );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only filter state.
		if ( ! isset( $_GET[ $key ] ) ) {
			return [];
		}
		$raw    = wp_unslash( $_GET[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- read-only filter state, sanitized below.
		$values = is_array( $raw ) ? $raw : explode( ',', (string) $raw );

		$slugs = [];
		foreach ( $values as $value ) {
			if ( ! is_scalar( $value ) ) {
				continue;
			}
			$slug = sanitize_title( (string) $value );
			if ( '' !== $slug ) {
				$slugs[] = $slug;
			}
		}
		return $slugs;
	}

	/**
	 * Is anything actually filtering this grid right now?
	 *
	 * What the Reset control exists for. With nothing applied there is nothing to clear,
	 * so the control hides itself — and it can only answer that question from the SAME
	 * state the rest of the bar reads: the URL. (The view script re-answers it on every
	 * keystroke and tick; this is the answer a cold page load, or a no-JS visitor, gets.)
	 *
	 * Only the taxonomies the page actually offers count, exactly as Post_Query counts
	 * them — a hand-written `tx_…` for a taxonomy no filter block renders is ignored by
	 * the query, so it must not light up Reset either.
	 *
	 * @param int    $post_id Post being rendered.
	 * @param string $gid     Sanitized blockId of the grid.
	 * @return bool
	 */
	public static function has_active_filters( int $post_id, string $gid ): bool {
		if ( '' === $gid ) {
			return false;
		}

		if ( '' !== self::current_search( $gid ) ) {
			return true;
		}

		foreach ( Post_Query::allowed_taxonomies( $post_id, $gid ) as $taxonomy ) {
			if ( ! empty( self::current_terms( $gid, (string) $taxonomy ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The DOM id for a child's control (labels have to point at something stable).
	 *
	 * @param array  $attributes Child attributes.
	 * @param string $fallback   Used when the block has no blockId yet.
	 * @return string
	 */
	public static function control_id( array $attributes, string $fallback ): string {
		$block_id = sanitize_html_class( (string) ( $attributes['blockId'] ?? '' ) );
		return 'flexa-filter-' . ( '' !== $block_id ? $block_id : $fallback );
	}

	/**
	 * A child's `<label>` — visually hidden, never omitted, when showLabel is off.
	 *
	 * @param array  $attributes Child attributes.
	 * @param string $for_id     Control id.
	 * @return string
	 */
	public static function label( array $attributes, string $for_id ): string {
		$text = trim( (string) ( $attributes['label'] ?? '' ) );
		if ( '' === $text ) {
			return '';
		}
		$class = 'flexa-filter-field__label';
		if ( false === ( $attributes['showLabel'] ?? true ) ) {
			$class .= ' screen-reader-text';
		}
		return '<label class="' . esc_attr( $class ) . '" for="' . esc_attr( $for_id ) . '">' . esc_html( $text ) . '</label>';
	}

	/**
	 * Wrap a child's markup in the `.flexa-filter-field` shell — width class, the
	 * parent's className/visibility handling and the block wrapper attributes.
	 *
	 * @param array  $attributes  Child attributes.
	 * @param string $inner       Already-escaped inner markup.
	 * @param string $extra_class Modifier class, e.g. `flexa-filter-field--search`.
	 * @return string
	 */
	public static function shell( array $attributes, string $inner, string $extra_class = '' ): string {
		$width = in_array( $attributes['width'] ?? '100', self::WIDTHS, true ) ? (string) $attributes['width'] : '100';

		$classes = [ 'flexa-filter-field', 'flexa-filter-field--w' . $width ];

		// The id class is what Filter_Field_CSS scopes this field's own Background /
		// Border / Shadow to — and, paired with the shell class above, what makes those
		// rules outrank the bar's Control panel for this one field.
		$block_id = sanitize_html_class( (string) ( $attributes['blockId'] ?? '' ) );
		if ( '' !== $block_id ) {
			$classes[] = 'flexa-filter-field-' . $block_id;
		}

		if ( '' !== $extra_class ) {
			$classes[] = $extra_class;
		}
		$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

		$wrapper = get_block_wrapper_attributes( [ 'class' => implode( ' ', $classes ) ] );

		return sprintf( '<div %1$s>%2$s</div>', $wrapper, $inner );
	}
}
