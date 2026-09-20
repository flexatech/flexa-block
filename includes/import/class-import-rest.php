<?php
declare(strict_types=1);
/**
 * Import REST API.
 *
 * Three routes under `flexa-block/v1`: list what can be imported (with each
 * item's current imported state), import one item, and remove a previously
 * imported item. Every route verifies the capability to create/delete the
 * content it touches — the admin page gate is never trusted on its own.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST controller for the import engine.
 */
class Import_REST {

	const REST_NS = 'flexa-block/v1';

	/**
	 * Register routes.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NS,
			'/imports',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'list_imports' ],
				'permission_callback' => [ __CLASS__, 'can_import' ],
			]
		);

		register_rest_route(
			self::REST_NS,
			'/imports/import',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'import' ],
				'permission_callback' => [ __CLASS__, 'can_import' ],
				'args'                => [
					'source' => [ 'required' => true ],
					'id'     => [ 'required' => true ],
				],
			]
		);

		register_rest_route(
			self::REST_NS,
			'/imports/cleanup',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'cleanup' ],
				'permission_callback' => [ __CLASS__, 'can_import' ],
				'args'                => [
					'source' => [ 'required' => true ],
					'id'     => [ 'required' => true ],
				],
			]
		);
	}

	/**
	 * Only users who can create page content may drive the importer.
	 *
	 * @return bool
	 */
	public static function can_import(): bool {
		return current_user_can( 'edit_pages' );
	}

	/**
	 * GET: every source and its items, each annotated with imported state.
	 *
	 * @return \WP_REST_Response
	 */
	public static function list_imports(): \WP_REST_Response {
		$out = [];
		foreach ( Import_Registry::sources() as $source ) {
			$key   = $source->key();
			$items = [];
			foreach ( $source->items() as $item ) {
				$item_id  = (string) ( $item['id'] ?? '' );
				$existing = '' !== $item_id ? Content_Importer::find_existing( $key, $item_id ) : 0;

				$item['imported']         = $existing > 0;
				$item['imported_post_id'] = $existing;
				$item['edit_link']        = $existing > 0 ? (string) get_edit_post_link( $existing, 'raw' ) : '';
				$items[]                  = $item;
			}
			$out[] = [
				'key'   => $key,
				'label' => $source->label(),
				'items' => $items,
			];
		}

		return rest_ensure_response( [ 'sources' => $out ] );
	}

	/**
	 * POST: import one source item into a new post.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function import( \WP_REST_Request $request ) {
		$source = self::resolve_source( $request );
		if ( is_wp_error( $source ) ) {
			return $source;
		}
		$item_id = sanitize_key( (string) $request->get_param( 'id' ) );

		$definition = $source->definition( $item_id );
		if ( null === $definition ) {
			return new \WP_Error( 'flexa_import_unknown', __( 'That item could not be found.', 'flexa-block' ), [ 'status' => 404 ] );
		}
		$definition['id'] = $item_id;

		$result = Content_Importer::import( $source->key(), $definition );
		if ( is_wp_error( $result ) ) {
			$result->add_data( [ 'status' => 500 ] );
			return $result;
		}

		return rest_ensure_response(
			array_merge( [ 'imported' => true ], $result )
		);
	}

	/**
	 * POST: remove every post imported from a source item.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function cleanup( \WP_REST_Request $request ) {
		$source = self::resolve_source( $request );
		if ( is_wp_error( $source ) ) {
			return $source;
		}
		$item_id = sanitize_key( (string) $request->get_param( 'id' ) );

		$result = Import_Cleanup::remove_item( $source->key(), $item_id );

		return rest_ensure_response(
			array_merge( [ 'removed' => true ], $result )
		);
	}

	/**
	 * Resolve and validate the requested source.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return Import_Source|\WP_Error
	 */
	private static function resolve_source( \WP_REST_Request $request ) {
		$key    = sanitize_key( (string) $request->get_param( 'source' ) );
		$source = Import_Registry::source( $key );
		if ( null === $source ) {
			return new \WP_Error( 'flexa_import_source', __( 'Unknown import source.', 'flexa-block' ), [ 'status' => 400 ] );
		}
		return $source;
	}
}
