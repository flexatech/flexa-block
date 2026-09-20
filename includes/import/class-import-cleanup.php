<?php
declare(strict_types=1);
/**
 * Import cleanup.
 *
 * Removes content the importer created, and only that content — every candidate
 * is verified to carry the import markers and to be deletable by the current
 * user before it is touched. Removal is a trash (recoverable), never a hard
 * delete, because imported content becomes the user's own the moment it lands.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trashes posts created by the import engine.
 */
class Import_Cleanup {

	/**
	 * Trash every post imported from a given source item.
	 *
	 * @param string $source_key Owning source key.
	 * @param string $item_id    Item id.
	 * @return array{deleted: list<int>, skipped: list<int>}
	 */
	public static function remove_item( string $source_key, string $item_id ): array {
		$ids = get_posts(
			[
				'post_type'      => 'any',
				'post_status'    => [ 'draft', 'publish', 'pending', 'private', 'future' ],
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Admin-only cleanup lookup by provenance meta; the only way to find every post imported from this source item, and it runs once per cleanup, not on the front end.
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'   => Content_Importer::SOURCE_META,
						'value' => $source_key,
					],
					[
						'key'   => Content_Importer::ID_META,
						'value' => $item_id,
					],
				],
			]
		);

		$deleted = [];
		$skipped = [];
		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( self::remove_post( $id ) ) {
				$deleted[] = $id;
			} else {
				$skipped[] = $id;
			}
		}

		return [
			'deleted' => $deleted,
			'skipped' => $skipped,
		];
	}

	/**
	 * Trash a single imported post, guarding markers and capability.
	 *
	 * @param int $post_id Post ID.
	 * @return bool True when trashed.
	 */
	public static function remove_post( int $post_id ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}
		// Refuse anything the engine did not create.
		if ( '' === (string) get_post_meta( $post_id, Content_Importer::SOURCE_META, true ) ) {
			return false;
		}
		if ( ! current_user_can( 'delete_post', $post_id ) ) {
			return false;
		}
		return (bool) wp_trash_post( $post_id );
	}
}
