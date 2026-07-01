<?php
declare(strict_types=1);
/**
 * Block Manager — registers blocks and the editor category.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registry and registration for Flexa blocks.
 */
class Block_Manager {

	/**
	 * Block registry. Order defines inserter order.
	 *
	 * @var array
	 */
	const BASE_BLOCKS = [
		[
			'slug'        => 'container',
			'name'        => 'flexa/container',
			'title'       => 'Container',
			'description' => 'Section wrapper with per-device flex layout, background, borders, spacing and light/dark colors.',
			'category'    => 'layout',
			'is_core'     => true,
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Container_CSS',
		],
		[
			'slug'        => 'button',
			'name'        => 'flexa/button',
			'title'       => 'Button',
			'description' => 'Call-to-action link styled as a button, with fill/outline/ghost styles, size, icon and light/dark colors.',
			'category'    => 'layout',
			'is_core'     => true,
			'generator'   => 'Flexa\\Block\\CSS_Generators\\Button_CSS',
		],
	];

	/**
	 * Cached block list (BASE_BLOCKS after filtering).
	 *
	 * @var array|null
	 */
	private static $blocks = null;

	/**
	 * Registered block results.
	 *
	 * @var array
	 */
	private static $registered = [];

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_blocks' ], 5 );
		add_filter( 'block_categories_all', [ __CLASS__, 'register_category' ], 10, 1 );
	}

	/**
	 * Add the "Flexa" inserter category.
	 *
	 * @param array $categories Existing categories.
	 * @return array
	 */
	public static function register_category( $categories ) {
		return array_merge(
			[
				[
					'slug'  => 'flexa',
					'title' => __( 'Flexa', 'flexa-block' ),
					'icon'  => null,
				],
			],
			$categories
		);
	}

	/**
	 * Get the block catalog (BASE_BLOCKS, filterable by add-ons).
	 *
	 * Add-ons can register their own blocks:
	 *   add_filter( 'flexa_block_blocks', function ( $blocks ) {
	 *       $blocks[] = [
	 *           'slug'        => 'hero',
	 *           'title'       => 'Hero',
	 *           'description' => '…',
	 *           'category'    => 'layout',
	 *           'path'        => FLEXA_BLOCK_PRO_DIR . 'build/blocks/hero',
	 *       ];
	 *       return $blocks;
	 *   } );
	 *
	 * @return array
	 */
	public static function get_blocks() {
		if ( null === self::$blocks ) {
			self::$blocks = apply_filters( 'flexa_block_blocks', self::BASE_BLOCKS );
		}
		return self::$blocks;
	}

	/**
	 * Register all blocks from the blocks/ directory.
	 */
	public static function register_blocks() {
		$build_dir  = FLEXA_BLOCK_DIR . 'build/blocks/';
		$blocks_dir = is_dir( $build_dir ) ? $build_dir : FLEXA_BLOCK_DIR . 'src/blocks/';

		foreach ( self::get_blocks() as $block ) {
			if ( empty( $block['slug'] ) ) {
				continue;
			}

			// Add-ons may register from their own directory via an explicit 'path'.
			$path = ! empty( $block['path'] ) ? $block['path'] : $blocks_dir . $block['slug'];

			if ( file_exists( $path . '/block.json' ) ) {
				$result = register_block_type( $path );
				if ( $result ) {
					self::$registered[ $block['slug'] ] = $result;
				}
			}
		}

		do_action( 'flexa_block_blocks_registered', self::$registered );
	}

	/**
	 * Get the catalog metadata (slug/title/description/category) for all blocks.
	 *
	 * @return array
	 */
	public static function get_block_catalog() {
		return self::get_blocks();
	}

	/**
	 * Get registered block slugs.
	 *
	 * @return array
	 */
	public static function get_registered_blocks() {
		return array_keys( self::$registered );
	}
}
