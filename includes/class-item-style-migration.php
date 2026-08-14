<?php
/**
 * One-time server-side migration for the item border/shadow split.
 *
 * The 7 collection blocks (post-grid, rss, taxonomy, instagram-feed,
 * facebook-feed, timeline, faq) used to paint each item with the Style-tab
 * `border`/`boxShadow`. Those attributes now mean "the block wrapper", and a new
 * `itemBorder`/`itemBoxShadow` styles each item. The editor migrates a block on
 * open, but that only persists when a post is re-saved — so already-published
 * content that nobody re-opens would still carry the legacy shape. This routine
 * rewrites that stored content in the background so EVERY post is migrated
 * without any user action, and the visuals stay identical when the temporary
 * PHP fallback is removed in a future version.
 *
 * TODO(remove in vNEXT): delete this class, its require/init in flexa-block.php,
 * and its option rows — the whole item-style migration goes away together.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scans posts and moves legacy wrapper border/shadow onto the per-item
 * attributes for the collection blocks, flipping `itemStyleMigrated`.
 */
final class Item_Style_Migration {

	/** Option holding the completed migration version (absent/older = not done). */
	const OPTION_DONE = 'flexa_block_item_style_migrated';

	/** Option holding the highest post ID processed so far (batch cursor). */
	const OPTION_CURSOR = 'flexa_block_item_style_migration_cursor';

	/** Cron hook that processes one batch and reschedules until complete. */
	const CRON_HOOK = 'flexa_block_item_style_migration_tick';

	/**
	 * Migration schema version. Bumping it is not tied to the plugin version, so
	 * the scan runs exactly once (until done), not on every plugin update.
	 */
	const MIGRATION_VERSION = '1';

	/** Posts processed per cron tick — small so large sites never time out. */
	const BATCH_SIZE = 30;

	/** The collection blocks whose Style-tab border/shadow used to paint items. */
	const TARGET_BLOCKS = [
		'flexa/post-grid',
		'flexa/rss',
		'flexa/taxonomy',
		'flexa/instagram-feed',
		'flexa/facebook-feed',
		'flexa/timeline',
		'flexa/faq',
	];

	/** The empty border shape written to `border` once its value moves to `itemBorder`. */
	const EMPTY_BORDER = [
		'desktop' => [
			'style'  => '',
			'width'  => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ],
			'color'  => [ 'light' => '', 'dark' => '' ],
			'radius' => [ 'topLeft' => '', 'topRight' => '', 'bottomRight' => '', 'bottomLeft' => '', 'unit' => 'px' ],
		],
		'tablet'  => [],
		'mobile'  => [],
	];

	/**
	 * Register the cron worker + the lazy scheduler, and the CLI backstop.
	 */
	public static function init(): void {
		add_action( self::CRON_HOOK, [ __CLASS__, 'run_batch' ] );
		// Blocks register on `init` (Block_Manager) so their defaults are known by
		// the time this fires; scheduling here works on both admin and front end.
		add_action( 'init', [ __CLASS__, 'maybe_schedule' ], 20 );

		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( 'flexa-block migrate-item-style', [ __CLASS__, 'cli_migrate' ] );
		}
	}

	/** Whether the migration has finished for good. */
	public static function is_done(): bool {
		return self::MIGRATION_VERSION === get_option( self::OPTION_DONE );
	}

	/** Queue the first batch if the migration is still pending. */
	public static function maybe_schedule(): void {
		if ( self::is_done() ) {
			return;
		}
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + 5, self::CRON_HOOK );
		}
	}

	/**
	 * Process one batch of posts, advance the cursor, and reschedule or finish.
	 */
	public static function run_batch(): void {
		if ( self::is_done() ) {
			return;
		}

		$cursor = (int) get_option( self::OPTION_CURSOR, 0 );
		$ids    = self::find_post_ids( $cursor, self::BATCH_SIZE );

		if ( empty( $ids ) ) {
			self::mark_done();
			return;
		}

		foreach ( $ids as $id ) {
			self::migrate_post( (int) $id );
			$cursor = max( $cursor, (int) $id );
		}
		update_option( self::OPTION_CURSOR, $cursor, false );

		if ( count( $ids ) < self::BATCH_SIZE ) {
			self::mark_done();
		} else {
			wp_schedule_single_event( time() + 5, self::CRON_HOOK );
		}
	}

	/** Mark the migration complete and drop the cursor. */
	private static function mark_done(): void {
		update_option( self::OPTION_DONE, self::MIGRATION_VERSION, false );
		delete_option( self::OPTION_CURSOR );
	}

	/**
	 * WP-CLI backstop: run the whole migration synchronously (for sites with
	 * cron disabled, or to force it). Usage: `wp flexa-block migrate-item-style`.
	 *
	 * @param array $args       Positional args (unused).
	 * @param array $assoc_args Associative args (unused).
	 */
	public static function cli_migrate( $args = [], $assoc_args = [] ): void {
		unset( $args, $assoc_args );
		$cursor  = 0;
		$changed = 0;
		do {
			$ids = self::find_post_ids( $cursor, self::BATCH_SIZE );
			foreach ( $ids as $id ) {
				if ( self::migrate_post( (int) $id ) ) {
					++$changed;
				}
				$cursor = max( $cursor, (int) $id );
			}
		} while ( count( $ids ) === self::BATCH_SIZE );

		self::mark_done();
		\WP_CLI::success( sprintf( '%d post(s) migrated.', $changed ) );
	}

	/**
	 * Find IDs of posts (any type but revisions) that contain a target block and
	 * sit after the cursor, ordered by ID so the cursor makes forward progress.
	 *
	 * @param int $after Only posts with a greater ID.
	 * @param int $limit Batch size.
	 * @return int[]
	 */
	private static function find_post_ids( int $after, int $limit ): array {
		global $wpdb;

		$likes  = [];
		$params = [ $after ];
		foreach ( self::TARGET_BLOCKS as $name ) {
			$likes[]  = 'post_content LIKE %s';
			$params[] = '%' . $wpdb->esc_like( 'wp:' . $name ) . '%';
		}
		$params[] = $limit;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $likes is a fixed set of `post_content LIKE %s` fragments; all values are bound via prepare().
		$sql = "SELECT ID FROM {$wpdb->posts}
			WHERE post_type != 'revision'
			AND post_status NOT IN ( 'trash', 'auto-draft' )
			AND ID > %d
			AND ( " . implode( ' OR ', $likes ) . " )
			ORDER BY ID ASC
			LIMIT %d";
		$ids = $wpdb->get_col( $wpdb->prepare( $sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter -- All values are bound via prepare(); the direct query is intentional for a one-shot CLI migration and needs no caching.
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array_map( 'intval', (array) $ids );
	}

	/**
	 * Migrate a single post's stored blocks. Parses the content, rewrites the
	 * target blocks (including nested ones), and re-saves only if something moved.
	 *
	 * @param int $post_id Post ID.
	 * @return bool Whether the post was changed and re-saved.
	 */
	public static function migrate_post( int $post_id ): bool {
		$post = get_post( $post_id );
		if ( ! $post || '' === (string) $post->post_content ) {
			return false;
		}
		// Cheap reject before the (more expensive) parse.
		if ( false === strpos( $post->post_content, 'wp:flexa/' ) ) {
			return false;
		}

		list( $blocks, $changed ) = self::migrate_blocks( parse_blocks( $post->post_content ) );
		if ( ! $changed ) {
			return false;
		}

		wp_update_post(
			[
				'ID'           => $post_id,
				// serialize_blocks re-emits the comment delimiters from blockName +
				// attrs; wp_slash because wp_update_post runs wp_unslash on input.
				'post_content' => wp_slash( serialize_blocks( $blocks ) ),
			],
			true
		);
		return true;
	}

	/**
	 * Apply the migration to a parsed block tree (pure — no DB). Walks nested
	 * innerBlocks so a target block inside a container/column is migrated too.
	 *
	 * @param array $blocks Parsed blocks from parse_blocks().
	 * @return array{0: array, 1: bool} The (possibly) rewritten blocks and a changed flag.
	 */
	public static function migrate_blocks( array $blocks ): array {
		$changed = false;
		self::walk( $blocks, $changed );
		return [ $blocks, $changed ];
	}

	/**
	 * Recursively rewrite target blocks in place.
	 *
	 * @param array $blocks  Blocks (by reference).
	 * @param bool  $changed Set true when any block is rewritten (by reference).
	 */
	private static function walk( array &$blocks, bool &$changed ): void {
		foreach ( $blocks as &$block ) {
			$name = $block['blockName'] ?? '';
			if ( '' !== $name && in_array( $name, self::TARGET_BLOCKS, true ) ) {
				$new = self::migrate_attrs( $name, $block['attrs'] ?? [] );
				if ( null !== $new ) {
					$block['attrs'] = $new;
					$changed        = true;
				}
			}
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				self::walk( $block['innerBlocks'], $changed );
			}
		}
		unset( $block );
	}

	/**
	 * The core transform — mirrors the JS `useMigrateItemStyle` hook exactly.
	 *
	 * Moves the EFFECTIVE (defaults-merged) `border`/`boxShadow` onto
	 * `itemBorder`/`itemBoxShadow`, clears the legacy values, and flips
	 * `itemStyleMigrated`. Merging block.json defaults is essential: Gutenberg
	 * omits attributes still equal to their default, so a legacy instagram/
	 * facebook post that relied on the block's non-empty default `border` has no
	 * `border` key at all — without the merge that default would silently jump to
	 * the wrapper after the flip.
	 *
	 * @param string $name  Block name (must be a target block).
	 * @param array  $attrs The block's saved attributes.
	 * @return array|null   The rewritten attributes, or null if nothing to do.
	 */
	public static function migrate_attrs( string $name, array $attrs ): ?array {
		if ( ! in_array( $name, self::TARGET_BLOCKS, true ) ) {
			return null;
		}
		if ( ! empty( $attrs['itemStyleMigrated'] ) ) {
			return null;
		}

		$merged = CSS_Generator_Service::merge_defaults( $name, $attrs );
		$out    = $attrs;
		$out['itemStyleMigrated'] = true;

		$border = $merged['border'] ?? [];
		if ( self::border_has_value( $border ) ) {
			$out['itemBorder'] = $border;
			$out['border']     = self::EMPTY_BORDER;
		}

		$shadow = $merged['boxShadow'] ?? [];
		if ( is_array( $shadow ) && ! empty( $shadow['enabled'] ) ) {
			$out['itemBoxShadow'] = $shadow;
			$out['boxShadow']     = array_merge( $shadow, [ 'enabled' => false ] );
		}

		return $out;
	}

	/**
	 * Whether a responsive border carries any author-set value on any device.
	 * Uses a non-empty-string test (not empty()) so a '0' still counts, matching
	 * the JS hook's truthiness.
	 *
	 * @param mixed $border A ResponsiveValue<BorderDevice> array.
	 * @return bool
	 */
	private static function border_has_value( $border ): bool {
		if ( ! is_array( $border ) ) {
			return false;
		}
		foreach ( [ 'desktop', 'tablet', 'mobile' ] as $device ) {
			$b = $border[ $device ] ?? [];
			if ( ! is_array( $b ) || empty( $b ) ) {
				continue;
			}
			if ( self::is_set( $b['style'] ?? null ) ) {
				return true;
			}
			$w = $b['width'] ?? [];
			if ( self::is_set( $w['top'] ?? null ) || self::is_set( $w['right'] ?? null ) || self::is_set( $w['bottom'] ?? null ) || self::is_set( $w['left'] ?? null ) ) {
				return true;
			}
			$c = $b['color'] ?? [];
			if ( self::is_set( $c['light'] ?? null ) || self::is_set( $c['dark'] ?? null ) ) {
				return true;
			}
			$r = $b['radius'] ?? [];
			if ( self::is_set( $r['topLeft'] ?? null ) || self::is_set( $r['topRight'] ?? null ) || self::is_set( $r['bottomRight'] ?? null ) || self::is_set( $r['bottomLeft'] ?? null ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * A scalar value counts as "set" when it is not null and not the empty string.
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private static function is_set( $value ): bool {
		return null !== $value && is_scalar( $value ) && '' !== (string) $value;
	}
}
