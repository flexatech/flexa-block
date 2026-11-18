<?php
declare(strict_types=1);
/**
 * The module's edit path: read a page's editable fields, change some of them.
 *
 * Gated on a toggle of its own, separate from the write toggle and off by
 * default. Creating a draft adds something to a site; this changes something a
 * person made, and a site should opt into that knowingly. Both abilities are
 * behind the toggle, including the one that only reads, for the reason the
 * write path gives: a client should not discover a door that is not there.
 *
 * The work is done by {@see Page_Editor}.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\MCP;

use Flexa\Block\Admin\MCP_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers `flexa/get-editable-fields` and `flexa/edit-page-blocks`.
 */
class Edit_Abilities {

	/**
	 * Register the filter that contributes the edit path.
	 */
	public static function init(): void {
		add_filter( 'flexa_block_mcp_abilities', [ __CLASS__, 'contribute' ] );
	}

	/**
	 * Add the edit abilities to the catalogue.
	 *
	 * @param mixed $abilities Abilities so far, keyed by name.
	 * @return array<string, array<string, mixed>>
	 */
	public static function contribute( $abilities ): array {
		$abilities = is_array( $abilities ) ? $abilities : [];

		if ( ! MCP_Settings::allows_edit() ) {
			return $abilities;
		}

		$post_id = [
			'type'        => 'integer',
			'minimum'     => 1,
			'description' => __( 'The page or post, by ID.', 'flexa-block' ),
		];

		$abilities['flexa/get-editable-fields'] = Ability_Support::read_ability(
			[
				'label'               => __( 'Get editable fields', 'flexa-block' ),
				'description'         => __( 'List the text, link and email fields of each block on a page that can be changed with flexa/edit-page-blocks, with their current values, and the page\'s modified_gmt to send back with the change. Says whether the page can be edited right now and, if not, why. For a published page, note explains that changes go onto a draft copy for review, and if a copy already exists its fields and modified_gmt are the ones returned.', 'flexa-block' ),
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [ 'post_id' => $post_id ],
					'required'   => [ 'post_id' ],
				],
				'output_schema'       => self::fields_schema(),
				'permission_callback' => [ __CLASS__, 'can_edit' ],
				'execute_callback'    => [ __CLASS__, 'get_editable_fields' ],
			]
		);

		$abilities['flexa/edit-page-blocks'] = Ability_Support::write_ability(
			[
				'label'               => __( 'Edit page blocks', 'flexa-block' ),
				'description'         => __( 'Change the title, and text, link and email fields of blocks, on a page, including one a person built or edited by hand. Drafts and pending pages are changed directly. A published page is never changed: the edit goes onto a draft copy (working_copy_id in the answer) that a person reviews and applies in the editor, so tell them that and give them the edit link. Read the page with flexa/get-editable-fields first and send its modified_gmt back; a page changed since is refused. Plain text only. Layout, styling and blocks themselves cannot be changed, added or removed. All changes in one request apply together or not at all, and the previous version is kept as a revision.', 'flexa-block' ),
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'post_id'               => $post_id,
						'expected_modified_gmt' => [
							'type'        => 'string',
							'description' => __( 'The modified_gmt flexa/get-editable-fields returned, or the one the previous edit returned.', 'flexa-block' ),
						],
						'title'                 => [
							'type'        => 'string',
							'maxLength'   => Draft_Writer::MAX_TITLE_CHARS,
							'description' => __( 'Optional. A new title for the page. Plain text.', 'flexa-block' ),
						],
						'changes'               => [
							'type'        => 'array',
							'maxItems'    => Page_Editor::MAX_CHANGES,
							'description' => __( 'The block fields to change. May be left out when only the title changes.', 'flexa-block' ),
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'block_id' => [
										'type'        => 'string',
										'description' => __( 'The block\'s block_id, as flexa/get-editable-fields lists it.', 'flexa-block' ),
									],
									'field'    => [
										'type'        => 'string',
										'description' => __( 'The field\'s path, as flexa/get-editable-fields lists it, for example content or items.2.text.', 'flexa-block' ),
									],
									'value'    => [
										'type'        => [ 'string', 'integer' ],
										'description' => __( 'The new value. Plain text: a value containing < or > is refused.', 'flexa-block' ),
									],
									'expect'   => [
										'type'        => 'string',
										'description' => __( 'Optional. The value you read for this field; the change is refused if the field holds something else now.', 'flexa-block' ),
									],
								],
								'required'   => [ 'block_id', 'field', 'value' ],
							],
						],
					],
					'required'   => [ 'post_id', 'expected_modified_gmt' ],
				],
				'output_schema'       => self::edit_schema(),
				'permission_callback' => [ __CLASS__, 'can_edit' ],
				'execute_callback'    => [ __CLASS__, 'edit_page_blocks' ],
				'meta'                => [
					// Changes content a person made. A revision is kept, and it is
					// still the kind of call a client should be free to confirm.
					'annotations' => [ 'destructive' => true ],
				],
			]
		);

		return $abilities;
	}

	/**
	 * The floor for the edit path. The precise check, `edit_post` on the post
	 * named, is made by {@see Page_Editor} once it knows which post that is.
	 *
	 * @return bool
	 */
	public static function can_edit(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * List one post's editable fields.
	 *
	 * @param mixed $input Validated ability input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function get_editable_fields( $input ) {
		return Page_Editor::fields( is_array( $input ) ? $input : [] );
	}

	/**
	 * Change fields on one post.
	 *
	 * @param mixed $input Validated ability input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function edit_page_blocks( $input ) {
		return Page_Editor::edit( is_array( $input ) ? $input : [] );
	}

	/**
	 * Output schema for `flexa/get-editable-fields`.
	 *
	 * @return array<string, mixed>
	 */
	private static function fields_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'         => [ 'type' => 'integer' ],
				'title'           => [ 'type' => 'string' ],
				'status'          => [ 'type' => 'string' ],
				'modified_gmt'    => [
					'type'        => 'string',
					'description' => __( 'Send this back as expected_modified_gmt.', 'flexa-block' ),
				],
				'editable'        => [
					'type'        => 'boolean',
					'description' => __( 'Whether flexa/edit-page-blocks would accept changes to this page right now.', 'flexa-block' ),
				],
				'reason'          => [
					'type'        => 'string',
					'description' => __( 'Why not, when editable is false.', 'flexa-block' ),
				],
				'working_copy_id' => [
					'type'        => 'integer',
					'description' => __( 'For a published page that already has a draft copy waiting for review: its ID. The fields and modified_gmt are those of the copy. Otherwise 0.', 'flexa-block' ),
				],
				'note'            => [
					'type'        => 'string',
					'description' => __( 'Where changes to this page will go, when that is not the page itself.', 'flexa-block' ),
				],
				'blocks'          => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'block_id' => [ 'type' => 'string' ],
							'block'    => [ 'type' => 'string' ],
							'fields'   => [
								'type'                 => 'object',
								'description'          => __( 'Editable fields keyed by path.', 'flexa-block' ),
								'additionalProperties' => [
									'type'       => 'object',
									'properties' => [
										'type'  => [
											'type' => 'string',
											'enum' => [ 'text', 'multiline', 'url', 'email', 'phone' ],
										],
										'label' => [ 'type' => 'string' ],
										'value' => [ 'type' => 'string' ],
									],
								],
							],
						],
					],
				],
				'truncated'       => [ 'type' => 'boolean' ],
				'edits_left'      => [ 'type' => 'integer' ],
			],
		];
	}

	/**
	 * Output schema for `flexa/edit-page-blocks`.
	 *
	 * @return array<string, mixed>
	 */
	private static function edit_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'         => [ 'type' => 'integer' ],
				'status'          => [ 'type' => 'string' ],
				'modified_gmt'    => [
					'type'        => 'string',
					'description' => __( 'The page\'s new modified_gmt. Send it with the next edit.', 'flexa-block' ),
				],
				'updated'         => [
					'type'        => 'boolean',
					'description' => __( 'False when every field already had the value sent, so nothing was written.', 'flexa-block' ),
				],
				'working_copy_id' => [
					'type'        => 'integer',
					'description' => __( 'The draft copy the changes were made on, when the page is published. Otherwise 0.', 'flexa-block' ),
				],
				'note'            => [
					'type'        => 'string',
					'description' => __( 'What to tell the person, when the changes are waiting on a copy for their review.', 'flexa-block' ),
				],
				'changes'         => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'block_id' => [ 'type' => 'string' ],
							'field'    => [ 'type' => 'string' ],
							'before'   => [ 'type' => 'string' ],
							'after'    => [ 'type' => 'string' ],
						],
					],
				],
				'edit_link'       => [ 'type' => 'string' ],
				'view_link'       => [ 'type' => 'string' ],
				'edits_left'      => [ 'type' => 'integer' ],
				'warnings'        => [
					'type'  => 'array',
					'items' => [ 'type' => 'string' ],
				],
			],
		];
	}
}
