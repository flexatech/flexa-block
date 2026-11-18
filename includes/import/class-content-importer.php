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
 * A definition may also declare slots, the handful of values a user is allowed
 * to supply before importing. Those are handled by `Preset_Slots`, and this
 * class's only responsibility towards them is the order they are applied in.
 * See `import()`.
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
	 * Post meta: hash of the content this class last wrote to the post.
	 *
	 * How a rebuild tells a draft nobody has touched from one a person has
	 * worked on in the editor. Rebuilding replaces the whole content, so it is
	 * only allowed while the content is still exactly what was written here.
	 * Any save from the editor, even one that changes nothing visible, changes
	 * the serialized markup and with it this hash, which errs on the side of
	 * leaving a person's page alone.
	 */
	const HASH_META = '_flexa_import_hash';

	/**
	 * Import a definition into a new post.
	 *
	 * Imports as a draft by default so nothing goes live on the user's site
	 * without a deliberate publish. blockIds in the markup are regenerated so a
	 * second import never collides with the first (per-instance CSS is scoped by
	 * that id), and the free CSS engine regenerates each block's CSS when the new
	 * post is saved below.
	 *
	 * The order of the three passes over the markup is deliberate and is the one
	 * thing here that must not be rearranged. blockIds first, then media, then
	 * slots: the caller's own text goes in last, so no later pass ever reads it.
	 * Reverse slots and media and a slot value containing a media placeholder
	 * would be swapped for an attachment URL; reverse slots and blockIds and a
	 * value containing `"blockId":"…"` would reshuffle a real block's id. Going
	 * last, user input is only ever data.
	 *
	 * @param string               $source_key Owning source key.
	 * @param array<string, mixed> $definition Full item definition.
	 * @param array<string, mixed> $slots      Slot values from the caller, if any.
	 * @return array{post_id:int, edit_link:string, view_link:string, warnings:list<string>}|\WP_Error
	 */
	public static function import( string $source_key, array $definition, array $slots = [] ) {
		Import_Manager::load_preset_slots();

		$id = sanitize_key( (string) ( $definition['id'] ?? '' ) );
		if ( '' === $id ) {
			return new \WP_Error( 'flexa_import_id', __( 'Import item is missing an id.', 'flexa-block' ) );
		}

		$warnings = [];

		$content = self::build( $source_key, $definition, $slots, $warnings );
		if ( is_wp_error( $content ) ) {
			return $content;
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
		self::stamp( $post_id, $definition, $slots );

		return [
			'post_id'   => $post_id,
			'edit_link' => (string) get_edit_post_link( $post_id, 'raw' ),
			'view_link' => self::view_link( $post_id ),
			'warnings'  => $warnings,
		];
	}

	/**
	 * Rebuild an existing post from its preset, with new slot values.
	 *
	 * The whole content is regenerated from the preset file, exactly as an
	 * import would produce it, and written over the post's content. That is
	 * why this is only safe for a post nobody has edited by hand, and why the
	 * caller has to establish that first with {@see untouched()}. It does not
	 * check on its own: the caller also has to decide status, permissions and
	 * what the input may contain, and those decisions belong in one place.
	 *
	 * Core saves a revision on the way, so the previous version stays one
	 * click away in the editor.
	 *
	 * @param int                  $post_id    Post to rebuild.
	 * @param string               $source_key Owning source key.
	 * @param array<string, mixed> $definition Full item definition, with `id`.
	 * @param array<string, mixed> $slots      Slot values, already merged.
	 * @param string               $title      Title to set.
	 * @return list<string>|\WP_Error Warnings, or the first failure.
	 */
	public static function rebuild( int $post_id, string $source_key, array $definition, array $slots, string $title ) {
		Import_Manager::load_preset_slots();

		$warnings = [];

		$content = self::build( $source_key, $definition, $slots, $warnings );
		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$postarr = [
			'ID'           => $post_id,
			'post_title'   => sanitize_text_field( $title ),
			'post_status'  => 'draft',
			'post_content' => $content,
		];

		// The same trusted-markup window as `import()`, for the same reason: the
		// markup is this plugin's own file, and caller values only ever reach
		// it as block attributes, which `serialize_blocks()` encodes.
		kses_remove_filters();
		$result = wp_update_post( wp_slash( $postarr ), true );
		kses_init_filters();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		self::stamp( $post_id, $definition, $slots );

		return $warnings;
	}

	/**
	 * Whether a post's content is still exactly what this class last wrote.
	 *
	 * A post with no recorded hash counts as touched. That covers every post
	 * imported before the hash existed, and the safe answer for those is the
	 * one that leaves them alone.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function untouched( int $post_id ): bool {
		$stored = (string) get_post_meta( $post_id, self::HASH_META, true );

		return '' !== $stored && hash_equals( $stored, self::content_hash( $post_id ) );
	}

	/**
	 * Generate a preset's markup with fresh blockIds, media and slot values.
	 *
	 * The order of the three passes is the one documented on `import()`.
	 *
	 * @param string               $source_key Owning source key.
	 * @param array<string, mixed> $definition Full item definition.
	 * @param array<string, mixed> $slots      Slot values from the caller.
	 * @param list<string>         $warnings   Collected warnings, by reference.
	 * @return string|\WP_Error
	 */
	private static function build( string $source_key, array $definition, array $slots, array &$warnings ) {
		$content = self::regenerate_block_ids( (string) ( $definition['content'] ?? '' ) );

		$media = is_array( $definition['media'] ?? null ) ? $definition['media'] : [];
		if ( $media ) {
			$content = self::resolve_media( $content, $media, $source_key, $warnings );
		}

		// Bad slot values abort the import rather than degrading it. A post is
		// not cheap to undo by hand, and a user who mistyped the address their
		// contact form sends to is better served by the message than by a draft
		// that looks right and silently goes nowhere.
		return Preset_Slots::apply( $definition, $content, $slots, $warnings );
	}

	/**
	 * Record what a post was built from, after an import or a rebuild.
	 *
	 * @param int                  $post_id    Post ID.
	 * @param array<string, mixed> $definition Full item definition.
	 * @param array<string, mixed> $slots      Slot values the post was built with.
	 * @return void
	 */
	private static function stamp( int $post_id, array $definition, array $slots ): void {
		update_post_meta( $post_id, self::VERSION_META, sanitize_text_field( (string) ( $definition['version'] ?? '1.0.0' ) ) );

		// Which slot contract this page was built under, which of its slots the
		// user actually filled, and with what. A later version that changes what
		// a slot means needs the first two to tell a customised page from one
		// taken as it shipped; a rebuild needs the values to change one field
		// without losing the others.
		$declared = Preset_Slots::declared( $definition );
		if ( $declared ) {
			$values = Preset_Slots::values( $definition, $slots );

			update_post_meta( $post_id, Preset_Slots::CONTRACT_META, Preset_Slots::CONTRACT );
			update_post_meta( $post_id, Preset_Slots::FILLED_META, Preset_Slots::filled_keys( $declared, $slots ) );
			update_post_meta(
				$post_id,
				Preset_Slots::VALUES_META,
				is_wp_error( $values ) ? [] : Preset_Slots::filled_values( $declared, $slots, $values )
			);
		}

		// Read back from the database rather than hashed from what was sent:
		// filters on the way in may have changed the content, and the question
		// later is whether the stored content has changed since.
		clean_post_cache( $post_id );
		update_post_meta( $post_id, self::HASH_META, self::content_hash( $post_id ) );
	}

	/**
	 * Hash of a post's stored content.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function content_hash( int $post_id ): string {
		$post = get_post( $post_id );

		return $post instanceof \WP_Post ? md5( (string) $post->post_content ) : '';
	}

	/**
	 * Where to send a user who wants to see an imported post on the front end.
	 *
	 * Imports land as drafts, and a draft has no public URL: its permalink 404s
	 * for everyone, including its author. Only the preview URL renders it, and
	 * only for a user who can edit it — so hand back the preview link until the
	 * post is actually public, and its real permalink once it is.
	 *
	 * @param int $post_id Post ID.
	 * @return string The link, or '' when the post is gone.
	 */
	public static function view_link( int $post_id ): string {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		if ( in_array( $post->post_status, [ 'publish', 'private' ], true ) ) {
			return (string) get_permalink( $post );
		}

		return (string) get_preview_post_link( $post );
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
