<?php
declare(strict_types=1);
/**
 * The module's read abilities.
 *
 * Two of them, and between them they answer the two questions an agent has to
 * answer before it can write anything useful for this plugin: what does this
 * site look like, and what is already on this page.
 *
 * Both are gated on the read toggle in the dashboard. Gated at registration,
 * not inside the callbacks: with read off the abilities do not exist, so a
 * client discovers nothing it then gets refused on. A capability a caller can
 * see but never use is worse than one that was never advertised.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\MCP;

use Flexa\Block\Admin\MCP_Settings;
use Flexa\Block\Block_Manager;
use Flexa\Block\Dark_Mode_Settings;
use Flexa\Block\Global_Styles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers `flexa/get-design-context` and `flexa/get-page-block-tree`.
 */
class Read_Abilities {

	/**
	 * Most block nodes one tree response will carry.
	 *
	 * A long landing page runs to a few hundred nodes, so this leaves room for
	 * the real cases while keeping a pathological post from turning one call
	 * into megabytes. Hitting it is reported rather than hidden.
	 */
	const MAX_NODES = 1000;

	/**
	 * Deepest nesting one tree response will descend.
	 *
	 * Containers inside containers are normal in this plugin and four or five
	 * levels is already unusual. Past this the structure says more about a
	 * broken import than about a page anyone designed.
	 */
	const MAX_DEPTH = 20;

	/**
	 * Register the filter that contributes both abilities.
	 */
	public static function init(): void {
		add_filter( 'flexa_block_mcp_abilities', [ __CLASS__, 'contribute' ] );
	}

	/**
	 * Add the read abilities to the catalogue.
	 *
	 * Takes the value loosely because a filter is handed whatever the previous
	 * callback returned, and the one that returns something other than an array
	 * should cost its own site its abilities rather than this plugin a fatal.
	 *
	 * @param mixed $abilities Abilities so far, keyed by name.
	 * @return array<string, array<string, mixed>>
	 */
	public static function contribute( $abilities ): array {
		$abilities = is_array( $abilities ) ? $abilities : [];

		if ( ! MCP_Settings::allows_read() ) {
			return $abilities;
		}

		$abilities['flexa/get-design-context'] = Ability_Support::read_ability(
			[
				'label'               => __( 'Get design context', 'flexa-block' ),
				'description'         => __( 'Read the site design system: the light and dark design tokens, how dark mode is applied, and which Flexa blocks are available to build with.', 'flexa-block' ),

				// Declared even though the ability takes no arguments. Core
				// refuses any input at all when an ability declares no input
				// schema, and an MCP client that sends an empty arguments
				// object is sending input as far as that check is concerned.
				// The default turns a call with nothing at all into the same
				// empty object, so both shapes work.
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [],
					'default'    => [],
				],
				'output_schema'       => self::design_context_schema(),
				'permission_callback' => Ability_Support::requires( 'edit_posts' ),
				'execute_callback'    => [ __CLASS__, 'design_context' ],
			]
		);

		$abilities['flexa/get-page-block-tree'] = Ability_Support::read_ability(
			[
				'label'               => __( 'Get page block tree', 'flexa-block' ),
				'description'         => __( 'Read the block structure of one post or page: block names, the attributes stored in the content, and the nesting between them.', 'flexa-block' ),
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'post_id'          => [
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => __( 'ID of the post or page to read.', 'flexa-block' ),
						],
						'include_resolved' => [
							'type'        => 'boolean',
							'default'     => false,
							'description' => __( 'Also return each block\'s attributes with its block type defaults filled in. Off by default: a Flexa block declares a lot of defaults, and measured on a real landing page this makes the response several times larger.', 'flexa-block' ),
						],
					],
					'required'   => [ 'post_id' ],
				],
				'output_schema'       => self::block_tree_schema(),
				'permission_callback' => [ __CLASS__, 'can_read_post' ],
				'execute_callback'    => [ __CLASS__, 'page_block_tree' ],
			]
		);

		return $abilities;
	}

	/**
	 * Whether the caller may read the requested post.
	 *
	 * `read_post` is the one check that covers the cases that matter: a private
	 * post belonging to someone else, a draft, a password-protected post, and a
	 * post ID that does not exist at all. Core maps the last of those to a
	 * denial, which is the right answer: a caller who may not read a post
	 * should not be able to tell a missing ID from a forbidden one.
	 *
	 * @param array<string, mixed> $input Validated ability input.
	 * @return bool
	 */
	public static function can_read_post( $input ): bool {
		$post_id = is_array( $input ) && isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;

		if ( $post_id < 1 ) {
			return false;
		}

		return current_user_can( 'read_post', $post_id );
	}

	/**
	 * The site's design context.
	 *
	 * Design only. No stored plugin settings, no environment or diagnostic
	 * information: an agent that is here to lay out a page has no business
	 * reading either, and an ability that returns them becomes the easiest way
	 * to get them out of the site.
	 *
	 * @return array<string, mixed>
	 */
	public static function design_context(): array {
		return [
			'tokens'    => [
				'light' => Ability_Support::json_object( self::tokens( Global_Styles::get_tokens() ) ),
				'dark'  => Ability_Support::json_object( self::tokens( Global_Styles::get_dark_tokens() ) ),
			],
			'dark_mode' => [
				'enabled'      => Dark_Mode_Settings::is_enabled(),
				'color_scheme' => Dark_Mode_Settings::use_color_scheme(),
				'data_theme'   => Dark_Mode_Settings::use_data_theme(),
			],
			'blocks'    => self::available_blocks(),
		];
	}

	/**
	 * The block structure of one post.
	 *
	 * @param array<string, mixed> $input Validated ability input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function page_block_tree( $input ) {
		$post_id  = is_array( $input ) && isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
		$resolved = is_array( $input ) && ! empty( $input['include_resolved'] );

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return new \WP_Error(
				'flexa_block_post_not_found',
				__( 'No post with that ID.', 'flexa-block' ),
				[ 'status' => 404 ]
			);
		}

		$count     = 0;
		$truncated = false;
		$blocks    = self::walk( parse_blocks( $post->post_content ), $resolved, 0, $count, $truncated );

		return [
			'post'       => [
				'id'           => $post->ID,
				'type'         => $post->post_type,
				'status'       => $post->post_status,
				// The stored title rather than `get_the_title()`, whose display
				// filters would report an ampersand back as `&#038;`.
				'title'        => Ability_Support::bound( $post->post_title ),
				// A draft that was never published carries `0000-00-00 00:00:00`
				// here, because WordPress copies the empty post date into it. A
				// client parsing that as a timestamp gets an invalid date, so the
				// local modification time is converted instead, and the column is
				// only reported as it stands when it holds a real one.
				'modified_gmt' => self::modified_gmt( $post ),
			],
			'blocks'     => $blocks,
			'node_count' => $count,
			'truncated'  => $truncated,
		];
	}

	/**
	 * The UTC modification time of a post, as a time rather than a zero date.
	 *
	 * @param \WP_Post $post Post to read.
	 * @return string
	 */
	private static function modified_gmt( \WP_Post $post ): string {
		if ( '' !== $post->post_modified_gmt && '0000-00-00 00:00:00' !== $post->post_modified_gmt ) {
			return $post->post_modified_gmt;
		}

		if ( '' === $post->post_modified || '0000-00-00 00:00:00' === $post->post_modified ) {
			return '';
		}

		return get_gmt_from_date( $post->post_modified );
	}

	/**
	 * Turn parsed blocks into tree nodes.
	 *
	 * @param array<int, array<string, mixed>> $blocks    Blocks from `parse_blocks()`.
	 * @param bool                             $resolved  Whether to include defaults-filled attributes.
	 * @param int                              $depth     Current depth.
	 * @param int                              $count     Running node count, by reference.
	 * @param bool                             $truncated Set when a cap was hit, by reference.
	 * @return array<int, array<string, mixed>>
	 */
	private static function walk( array $blocks, bool $resolved, int $depth, int &$count, bool &$truncated ): array {
		if ( $depth >= self::MAX_DEPTH ) {
			$truncated = true;
			return [];
		}

		$nodes = [];

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			if ( $count >= self::MAX_NODES ) {
				$truncated = true;
				break;
			}

			$name       = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
			$inner      = isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ? $block['innerBlocks'] : [];
			$inner_html = isset( $block['innerHTML'] ) ? trim( (string) $block['innerHTML'] ) : '';

			// `parse_blocks()` emits a nameless node for every run of text
			// between two blocks, which for most posts means a node per blank
			// line. The ones holding nothing are formatting, not content, and
			// reporting them would bury the real structure.
			if ( '' === $name ) {
				if ( '' === $inner_html ) {
					continue;
				}

				$name = 'core/freeform';
			}

			++$count;

			$attributes = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : [];

			$node = [
				'name'       => $name,

				// What the post content literally stores. A block relies on its
				// block type for everything absent here, so an attribute
				// missing from this list is not unset, it is defaulted, and the
				// two are different things to anyone about to write the post
				// back.
				'attributes' => Ability_Support::json_object(
					(array) Ability_Support::bound_deep( $attributes )
				),
			];

			if ( $resolved ) {
				$node['resolved_attributes'] = Ability_Support::json_object(
					(array) Ability_Support::bound_deep(
						array_merge( self::attribute_defaults( $name ), $attributes )
					)
				);
			}

			// Only on a leaf. A parent's innerHTML contains the serialized
			// markup of everything below it, so carrying it at every level
			// would repeat the whole subtree once per ancestor.
			if ( ! $inner && '' !== $inner_html ) {
				$node['inner_html'] = Ability_Support::bound( $inner_html );
			}

			if ( $inner ) {
				$node['inner_blocks'] = self::walk( $inner, $resolved, $depth + 1, $count, $truncated );
			}

			$nodes[] = $node;
		}

		return $nodes;
	}

	/**
	 * Default attribute values declared by a registered block type.
	 *
	 * An unregistered block name yields none, which is honest: with its type
	 * absent there is nothing to resolve against, and guessing would be worse
	 * than saying so by omission.
	 *
	 * @param string $name Block name.
	 * @return array<string, mixed>
	 */
	private static function attribute_defaults( string $name ): array {
		$type = \WP_Block_Type_Registry::get_instance()->get_registered( $name );
		if ( ! $type instanceof \WP_Block_Type || ! is_array( $type->attributes ) ) {
			return [];
		}

		$defaults = [];
		foreach ( $type->attributes as $key => $definition ) {
			if ( is_array( $definition ) && array_key_exists( 'default', $definition ) ) {
				$defaults[ (string) $key ] = $definition['default'];
			}
		}

		return $defaults;
	}

	/**
	 * Flatten a token map to strings.
	 *
	 * Both token lists pass through a filter before reaching us, so a site can
	 * have put anything in them. A CSS custom property is a string by the time
	 * it is written out, so that is what is reported.
	 *
	 * @param array<string, mixed> $tokens Token map.
	 * @return array<string, string>
	 */
	private static function tokens( array $tokens ): array {
		$out = [];

		foreach ( $tokens as $key => $value ) {
			if ( is_array( $value ) || is_object( $value ) || is_bool( $value ) || null === $value ) {
				continue;
			}

			$out[ (string) $key ] = Ability_Support::bound( (string) $value );
		}

		return $out;
	}

	/**
	 * The blocks an agent can actually put on a page.
	 *
	 * Taken from what reached the block registry, not from the catalogue. The
	 * two differ on purpose: the catalogue is every block the plugin knows
	 * about, while registration is narrowed by `flexa_block_registerable_blocks`
	 * and skips the WooCommerce blocks on a site without WooCommerce. An agent
	 * told about a block that is not registered would write markup the editor
	 * renders as a broken block, so the catalogue is used only for the titles
	 * and descriptions of the blocks that did register.
	 *
	 * The internal CSS generator class on each entry is left out. It is
	 * implementation detail, of no use to a caller, and a map of the plugin's
	 * internals is not something to hand out over an API.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function available_blocks(): array {
		$registered = array_flip( (array) Block_Manager::get_registered_blocks() );
		$blocks     = [];

		foreach ( (array) Block_Manager::get_blocks() as $block ) {
			if ( ! is_array( $block ) || empty( $block['slug'] ) ) {
				continue;
			}

			$slug = (string) $block['slug'];
			if ( ! isset( $registered[ $slug ] ) ) {
				continue;
			}

			$blocks[] = [
				'name'                 => ! empty( $block['name'] ) ? (string) $block['name'] : 'flexa/' . $slug,
				'title'                => Ability_Support::bound( (string) ( $block['title'] ?? $slug ) ),
				'description'          => Ability_Support::bound( (string) ( $block['description'] ?? '' ) ),
				'category'             => (string) ( $block['category'] ?? '' ),
				'child_only'           => ! empty( $block['is_child'] ),
				'requires_woocommerce' => ! empty( $block['is_woo'] ),
			];
		}

		return $blocks;
	}

	/**
	 * Output schema for `flexa/get-design-context`.
	 *
	 * @return array<string, mixed>
	 */
	private static function design_context_schema(): array {
		$token_map = [
			'type'                 => 'object',
			'additionalProperties' => [ 'type' => 'string' ],
		];

		return [
			'type'       => 'object',
			'properties' => [
				'tokens'    => [
					'type'        => 'object',
					'description' => __( 'Design tokens, as CSS custom property names without the leading dashes.', 'flexa-block' ),
					'properties'  => [
						'light' => $token_map,
						'dark'  => array_merge(
							$token_map,
							[ 'description' => __( 'Only the tokens dark mode overrides. Anything absent keeps its light value.', 'flexa-block' ) ]
						),
					],
				],
				'dark_mode' => [
					'type'       => 'object',
					'properties' => [
						'enabled'      => [
							'type'        => 'boolean',
							'description' => __( 'Whether dark mode is switched on at all.', 'flexa-block' ),
						],
						'color_scheme' => [
							'type'        => 'boolean',
							'description' => __( 'Whether the dark tokens follow the visitor\'s system preference.', 'flexa-block' ),
						],
						'data_theme'   => [
							'type'        => 'boolean',
							'description' => __( 'Whether the dark tokens also apply under an explicit data-theme="dark" attribute.', 'flexa-block' ),
						],
					],
				],
				'blocks'    => [
					'type'        => 'array',
					'description' => __( 'Blocks registered on this site and available to build with.', 'flexa-block' ),
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'name'                 => [ 'type' => 'string' ],
							'title'                => [ 'type' => 'string' ],
							'description'          => [ 'type' => 'string' ],
							'category'             => [ 'type' => 'string' ],
							'child_only'           => [
								'type'        => 'boolean',
								'description' => __( 'Whether the block is only valid nested inside a Flexa parent block.', 'flexa-block' ),
							],
							'requires_woocommerce' => [ 'type' => 'boolean' ],
						],
					],
				],
			],
		];
	}

	/**
	 * Output schema for `flexa/get-page-block-tree`.
	 *
	 * The node shape recurses, which JSON Schema expresses with a reference to
	 * the document root. Core validates with `rest_validate_value_from_schema()`,
	 * which does not resolve references, so nesting below the first level goes
	 * unvalidated. That is acceptable here: every node is built by one function
	 * above, so the shape is guaranteed by construction rather than by the
	 * schema, and the schema's job is to tell a client what to expect.
	 *
	 * @return array<string, mixed>
	 */
	private static function block_tree_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post'       => [
					'type'       => 'object',
					'properties' => [
						'id'           => [ 'type' => 'integer' ],
						'type'         => [ 'type' => 'string' ],
						'status'       => [ 'type' => 'string' ],
						'title'        => [ 'type' => 'string' ],
						'modified_gmt' => [ 'type' => 'string' ],
					],
				],
				'blocks'     => [
					'type'        => 'array',
					'description' => __( 'Top-level blocks, in document order.', 'flexa-block' ),
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'name'                => [ 'type' => 'string' ],
							'attributes'          => [
								'type'        => 'object',
								'description' => __( 'Attributes as serialized in the post content. An attribute missing here is not unset, it is taking its block type default. One caveat on nesting: PHP cannot tell an empty JSON object from an empty array once decoded, so a nested value that is empty is reported as [] whichever it was in the content.', 'flexa-block' ),
							],
							'resolved_attributes' => [
								'type'        => 'object',
								'description' => __( 'The same attributes with block type defaults filled in. Present only when include_resolved was requested.', 'flexa-block' ),
							],
							'inner_html'          => [
								'type'        => 'string',
								'description' => __( 'Markup held directly by the block. Reported for blocks with no children only, since a parent repeats the markup of everything below it.', 'flexa-block' ),
							],
							'inner_blocks'        => [
								'type'        => 'array',
								'description' => __( 'Child blocks, same shape as this one.', 'flexa-block' ),
								'items'       => [ 'type' => 'object' ],
							],
						],
					],
				],
				'node_count' => [
					'type'        => 'integer',
					'description' => __( 'Blocks reported, counting every level.', 'flexa-block' ),
				],
				'truncated'  => [
					'type'        => 'boolean',
					'description' => __( 'True when the tree was cut at a size or depth limit, so what came back is incomplete.', 'flexa-block' ),
				],
			],
		];
	}
}
