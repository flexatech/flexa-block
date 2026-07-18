<?php
declare(strict_types=1);
/**
 * Block Locator — find a saved block (and its children) by blockId.
 *
 * Several features need the attributes a block was SAVED with, not the ones a
 * browser claims it has: the subscribe form resolves its recipient email this
 * way, and the post filter resolves the target grid's query this way. Trusting
 * the request would let anyone mail themselves the site's posts at
 * posts_per_page=10000, or filter outside the query the author configured.
 *
 * So: parse the post content (or, for FSE, the resolved template) and look the
 * block up by its blockId. The request then only contributes a blockId — every
 * setting comes from the database.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locate saved blocks by name / blockId.
 */
final class Block_Locator {

	/**
	 * Parsed post content, keyed by post ID (per-request).
	 *
	 * @var array<int, array>
	 */
	private static $post_cache = [];

	/**
	 * Parsed template content (per-request).
	 *
	 * @var array|null
	 */
	private static $template_cache = null;

	/**
	 * Find one block's attributes, merged over its block.json defaults.
	 *
	 * @param string $block_name Block name, e.g. `flexa/post-grid`.
	 * @param string $block_id   The block's `blockId` attribute.
	 * @param int    $post_id    Post to search (falls back to the template).
	 * @return array|null Attributes, or null when the block isn't found.
	 */
	public static function find_attrs( string $block_name, string $block_id, int $post_id ): ?array {
		$node = self::find( $block_name, $block_id, $post_id );
		if ( null === $node ) {
			return null;
		}
		return CSS_Generator_Service::merge_defaults( $block_name, $node['attrs'] ?? [] );
	}

	/**
	 * Find one block node (attrs + innerBlocks) by blockId.
	 *
	 * @param string $block_name Block name.
	 * @param string $block_id   Target blockId.
	 * @param int    $post_id    Post to search.
	 * @return array|null Parsed block node.
	 */
	public static function find( string $block_name, string $block_id, int $post_id ): ?array {
		if ( '' === $block_name || '' === $block_id ) {
			return null;
		}
		foreach ( self::sources( $post_id ) as $blocks ) {
			$found = self::search( $blocks, $block_name, $block_id );
			if ( null !== $found ) {
				return $found;
			}
		}
		return null;
	}

	/**
	 * Every block node of one type, in document order.
	 *
	 * @param string $block_name Block name.
	 * @param int    $post_id    Post to search.
	 * @return array<int, array> Parsed block nodes.
	 */
	public static function find_all( string $block_name, int $post_id ): array {
		$out = [];
		foreach ( self::sources( $post_id ) as $blocks ) {
			self::collect( $blocks, $block_name, $out );
		}
		return $out;
	}

	/**
	 * The blockId of the FIRST block of a type on the page — how a filter with no
	 * explicit target resolves "the grid on this page" server side. (The view
	 * script resolves the same thing client side as the first match in the DOM.)
	 *
	 * @param string $block_name Block name.
	 * @param int    $post_id    Post to search.
	 * @return string blockId, or '' when there is none.
	 */
	public static function find_first_id( string $block_name, int $post_id ): string {
		foreach ( self::find_all( $block_name, $post_id ) as $node ) {
			$id = sanitize_html_class( (string) ( $node['attrs']['blockId'] ?? '' ) );
			if ( '' !== $id ) {
				return $id;
			}
		}
		return '';
	}

	/**
	 * Direct + nested children of one type inside a parsed block node.
	 *
	 * @param array  $node       Parent block node.
	 * @param string $child_name Child block name.
	 * @return array<int, array> Parsed child nodes.
	 */
	public static function children( array $node, string $child_name ): array {
		$out = [];
		self::collect( $node['innerBlocks'] ?? [], $child_name, $out );
		return $out;
	}

	/**
	 * Block sources to search, most specific first: the post's own content, then
	 * the FSE template the page renders through.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int, array> Lists of parsed blocks.
	 */
	private static function sources( int $post_id ): array {
		$sources = [];
		if ( $post_id > 0 ) {
			$sources[] = self::post_blocks( $post_id );
		}
		$sources[] = self::template_blocks();
		return $sources;
	}

	/**
	 * Parse (once per request) a post's content.
	 *
	 * @param int $post_id Post ID.
	 * @return array Parsed blocks.
	 */
	private static function post_blocks( int $post_id ): array {
		if ( isset( self::$post_cache[ $post_id ] ) ) {
			return self::$post_cache[ $post_id ];
		}
		$content = get_post_field( 'post_content', $post_id );
		$blocks  = ( is_string( $content ) && '' !== $content ) ? parse_blocks( $content ) : [];

		self::$post_cache[ $post_id ] = $blocks;
		return $blocks;
	}

	/**
	 * Parse (once per request) the block template.
	 *
	 * On the front end WordPress has already resolved which template renders this
	 * request, so use it. Off the front end (a REST call) it hasn't, so fall back
	 * to scanning the theme's templates and parts — the only way to find a grid
	 * that lives in a template rather than in post content.
	 *
	 * @return array Parsed blocks.
	 */
	private static function template_blocks(): array {
		if ( null !== self::$template_cache ) {
			return self::$template_cache;
		}

		$current = $GLOBALS['_wp_current_template_content'] ?? '';
		if ( is_string( $current ) && '' !== $current ) {
			self::$template_cache = parse_blocks( $current );
			return self::$template_cache;
		}

		$blocks = [];
		if ( function_exists( 'get_block_templates' ) ) {
			foreach ( [ 'wp_template', 'wp_template_part' ] as $type ) {
				foreach ( get_block_templates( [], $type ) as $template ) {
					if ( ! empty( $template->content ) ) {
						$blocks = array_merge( $blocks, parse_blocks( $template->content ) );
					}
				}
			}
		}

		self::$template_cache = $blocks;
		return $blocks;
	}

	/**
	 * Depth-first search for one block by name + blockId.
	 *
	 * @param array  $blocks     Parsed blocks.
	 * @param string $block_name Block name.
	 * @param string $block_id   Target blockId.
	 * @return array|null
	 */
	private static function search( array $blocks, string $block_name, string $block_id ): ?array {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if (
				$block_name === ( $block['blockName'] ?? '' )
				&& isset( $block['attrs']['blockId'] )
				&& (string) $block['attrs']['blockId'] === $block_id
			) {
				return $block;
			}
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$found = self::search( $block['innerBlocks'], $block_name, $block_id );
				if ( null !== $found ) {
					return $found;
				}
			}
		}
		return null;
	}

	/**
	 * Depth-first collection of every block of one name.
	 *
	 * @param array  $blocks     Parsed blocks.
	 * @param string $block_name Block name.
	 * @param array  $out        Accumulator (by reference).
	 */
	private static function collect( array $blocks, string $block_name, array &$out ): void {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			if ( $block_name === ( $block['blockName'] ?? '' ) ) {
				$out[] = $block;
			}
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				self::collect( $block['innerBlocks'], $block_name, $out );
			}
		}
	}
}
