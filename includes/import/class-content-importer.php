<?php
declare(strict_types=1);
/**
 * Content importer.
 *
 * Turns a source's content definition (block markup, optional media) into a real
 * WordPress post the user owns and can edit in Gutenberg. Each imported post is
 * tagged with markers tracing it back to the source item, which powers the
 * "already imported" state in the UI and safe cleanup later.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates WordPress content from an import definition.
 */
class Content_Importer {

	/** Post meta: owning source key (e.g. `sample`). */
	const SOURCE_META = '_flexa_import_source';

	/** Post meta: item id within the source. */
	const ID_META = '_flexa_import_id';

	/** Post meta: definition version at import time. */
	const VERSION_META = '_flexa_import_version';

	/**
	 * Import a definition into a new post.
	 *
	 * Imports as a draft by default so nothing goes live on the user's site
	 * without a deliberate publish. blockIds in the markup are regenerated so a
	 * second import never collides with the first (per-instance CSS is scoped by
	 * that id), and the free CSS engine regenerates each block's CSS when the new
	 * post is saved below.
	 *
	 * @param string               $source_key Owning source key.
	 * @param array<string, mixed> $definition Full item definition.
	 * @return array{post_id:int, edit_link:string, view_link:string, warnings:list<string>}|\WP_Error
	 */
	public static function import( string $source_key, array $definition ) {
		$id = sanitize_key( (string) ( $definition['id'] ?? '' ) );
		if ( '' === $id ) {
			return new \WP_Error( 'flexa_import_id', __( 'Import item is missing an id.', 'flexa-block' ) );
		}

		$content  = (string) ( $definition['content'] ?? '' );
		$warnings = [];

		$content = self::regenerate_block_ids( $content );

		$media = is_array( $definition['media'] ?? null ) ? $definition['media'] : [];
		if ( $media ) {
			$content = self::resolve_media( $content, $media, $source_key, $warnings );
		}

		$post_type   = self::sanitize_post_type( (string) ( $definition['post_type'] ?? 'page' ) );
		$post_status = in_array( $definition['post_status'] ?? '', [ 'draft', 'publish' ], true )
			? (string) $definition['post_status']
			: 'draft';

		$postarr = [
			'post_title'   => sanitize_text_field( (string) ( $definition['title'] ?? $id ) ),
			'post_type'    => $post_type,
			'post_status'  => $post_status,
			'post_content' => $content,
		];

		// Block delimiters are HTML comments and generator CSS uses nested
		// selectors; KSES strips both. This is trusted, plugin-bundled markup
		// imported by a capability-gated admin action, so filter it out for the
		// insert only, then restore.
		kses_remove_filters();
		$post_id = wp_insert_post( wp_slash( $postarr ), true );
		kses_init_filters();

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}
		$post_id = (int) $post_id;

		update_post_meta( $post_id, self::SOURCE_META, $source_key );
		update_post_meta( $post_id, self::ID_META, $id );
		update_post_meta( $post_id, self::VERSION_META, sanitize_text_field( (string) ( $definition['version'] ?? '1.0.0' ) ) );

		return [
			'post_id'   => $post_id,
			'edit_link' => (string) get_edit_post_link( $post_id, 'raw' ),
			'view_link' => (string) get_permalink( $post_id ),
			'warnings'  => $warnings,
		];
	}

	/**
	 * First existing post imported from a given source item, if any.
	 *
	 * Trashed copies are ignored so a user who deleted an import can re-import
	 * cleanly. Powers the idempotency check (offer "open existing" vs "import a
	 * fresh copy").
	 *
	 * @param string $source_key Owning source key.
	 * @param string $item_id    Item id.
	 * @return int Post ID, or 0.
	 */
	public static function find_existing( string $source_key, string $item_id ): int {
		$found = get_posts(
			[
				'post_type'      => 'any',
				'post_status'    => [ 'draft', 'publish', 'pending', 'private', 'future' ],
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin-only import lookup by provenance meta; there is no non-meta way to find the post previously imported from this source item, and it runs once per import, not on the front end.
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'   => self::SOURCE_META,
						'value' => $source_key,
					],
					[
						'key'   => self::ID_META,
						'value' => $item_id,
					],
				],
			]
		);
		return $found ? (int) $found[0] : 0;
	}

	/**
	 * Replace each `blockId` in the markup with a fresh, unique id.
	 *
	 * Mirrors the editor's `fx-` + 8 hex scheme so imported blocks look native
	 * and never share a scope class with a previous import.
	 *
	 * @param string $content Block markup.
	 * @return string
	 */
	private static function regenerate_block_ids( string $content ): string {
		return (string) preg_replace_callback(
			'/"blockId":"[^"]*"/',
			static function (): string {
				return '"blockId":"fx-' . bin2hex( random_bytes( 4 ) ) . '"';
			},
			$content
		);
	}

	/**
	 * Sideload each declared media file and swap its placeholder in the markup.
	 *
	 * A failed file leaves its placeholder untouched and records a warning rather
	 * than aborting the whole import — a missing image should not cost the page.
	 *
	 * @param string                            $content    Block markup.
	 * @param list<array<string, mixed>>        $media      Media declarations.
	 * @param string                            $source_key Owning source key.
	 * @param list<string>                      $warnings   Collected warnings (by ref).
	 * @return string
	 */
	private static function resolve_media( string $content, array $media, string $source_key, array &$warnings ): string {
		foreach ( $media as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$placeholder = (string) ( $entry['placeholder'] ?? '' );
			$file        = (string) ( $entry['file'] ?? '' );
			if ( '' === $placeholder || '' === $file ) {
				continue;
			}

			$result = Media_Importer::sideload( $file, (string) ( $entry['alt'] ?? '' ), $source_key );
			if ( is_wp_error( $result ) ) {
				$warnings[] = sprintf(
					/* translators: %s: media file name */
					__( 'Could not import media: %s', 'flexa-block' ),
					basename( $file )
				);
				continue;
			}

			$content = str_replace( $placeholder, esc_url_raw( $result['url'] ), $content );
		}
		return $content;
	}

	/**
	 * Keep the target post type to a known, safe value.
	 *
	 * @param string $type Requested post type.
	 * @return string
	 */
	private static function sanitize_post_type( string $type ): string {
		$type = sanitize_key( $type );
		if ( ! in_array( $type, [ 'page', 'post' ], true ) || ! post_type_exists( $type ) ) {
			return 'page';
		}
		return $type;
	}
}
