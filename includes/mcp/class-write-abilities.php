<?php
declare(strict_types=1);
/**
 * The module's write path: discover a preset, read what it takes, create a draft.
 *
 * Three abilities, and all three are gated on the write toggle, including the
 * two that only read. That is deliberate. Listing presets and reading a
 * preset's schema exist to be the first two steps of creating a draft; with
 * write off there is no third step, and a client that discovers a preset it can
 * never use has been told about a door that is not there. The same reasoning
 * the read abilities use for their own toggle: an advertised capability that
 * always refuses is worse than one that was never advertised.
 *
 * They are still annotated as read-only, because they are. The gate decides
 * whether an ability exists; the annotation describes what it does once it
 * does.
 *
 * None of them touch `Content_Importer` directly. Creating content goes through
 * {@see Draft_Writer}, which is where the validation and the draft pin live.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\MCP;

use Flexa\Block\Admin\MCP_Settings;
use Flexa\Block\Import\Content_Importer;
use Flexa\Block\Import\Import_Manager;
use Flexa\Block\Import\Import_Registry;
use Flexa\Block\Import\Preset_Slots;
use Flexa\Block\Import\Sample_Source;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers `flexa/list-presets`, `flexa/get-preset-schema` and `flexa/create-page-draft`.
 */
class Write_Abilities {

	/** Ability name for the preset listing, also its rate-limit bucket. */
	const LIST = 'list-presets';

	/** Ability name for one preset's schema, also its rate-limit bucket. */
	const SCHEMA = 'get-preset-schema';

	/**
	 * Most presets one listing will report.
	 *
	 * Measured with the Pro add-on active, eighteen presets came to 19 KB, so a
	 * preset costs roughly a kilobyte of response and fifty of them is about as
	 * much as a listing should hand a client in one answer. The free plugin
	 * ships two, so this only matters to a site carrying a large preset
	 * library; such a site narrows the listing with `source`, and is told the
	 * answer was cut rather than quietly receiving part of it.
	 */
	const MAX_PRESETS = 50;

	/**
	 * Register the filter that contributes the write path.
	 */
	public static function init(): void {
		add_filter( 'flexa_block_mcp_abilities', [ __CLASS__, 'contribute' ] );
	}

	/**
	 * Add the write abilities to the catalogue.
	 *
	 * @param mixed $abilities Abilities so far, keyed by name.
	 * @return array<string, array<string, mixed>>
	 */
	public static function contribute( $abilities ): array {
		Import_Manager::load_preset_slots();

		$abilities = is_array( $abilities ) ? $abilities : [];

		if ( ! MCP_Settings::allows_write() ) {
			return $abilities;
		}

		$source_arg = [
			'type'        => 'string',
			'default'     => Sample_Source::KEY,
			'description' => __( 'Which preset source to read. Omit it for the presets this plugin ships.', 'flexa-block' ),
		];

		$abilities['flexa/list-presets'] = Ability_Support::read_ability(
			[
				'label'               => __( 'List presets', 'flexa-block' ),
				'description'         => __( 'List the ready-made pages this site can create: their ids, what each one is for, how many fields it takes, and whether one has been created from it already.', 'flexa-block' ),
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'source' => array_merge( $source_arg, [ 'description' => __( 'Limit the listing to one preset source. Omit it to list every source.', 'flexa-block' ) ] ),
					],
					'default'    => [],
				],
				'output_schema'       => self::list_schema(),
				'permission_callback' => [ __CLASS__, 'can_use_presets' ],
				'execute_callback'    => [ __CLASS__, 'list_presets' ],
			]
		);

		$abilities['flexa/get-preset-schema'] = Ability_Support::read_ability(
			[
				'label'               => __( 'Get preset schema', 'flexa-block' ),
				'description'         => __( 'Read the fields one preset accepts: each field\'s type, what it is called, the text the preset ships with, and how long a value may be. Call this before creating a draft from it.', 'flexa-block' ),
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'source' => $source_arg,
						'preset' => [
							'type'        => 'string',
							'description' => __( 'Preset id, as flexa/list-presets reports it.', 'flexa-block' ),
						],
					],
					'required'   => [ 'preset' ],
				],
				'output_schema'       => self::schema_schema(),
				'permission_callback' => [ __CLASS__, 'can_use_presets' ],
				'execute_callback'    => [ __CLASS__, 'preset_schema' ],
			]
		);

		$abilities['flexa/create-page-draft'] = Ability_Support::write_ability(
			[
				'label'               => __( 'Create page draft', 'flexa-block' ),
				'description'         => __( 'Create a new draft page from one preset, with your own text in its fields. Always a draft: this never publishes, and a person has to review the page and publish it.', 'flexa-block' ),
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'source'          => $source_arg,
						'preset'          => [
							'type'        => 'string',
							'description' => __( 'Preset id, as flexa/list-presets reports it.', 'flexa-block' ),
						],
						'slots'           => [
							'type'                 => 'object',
							'default'              => [],
							'description'          => __( 'Values for the preset\'s fields, keyed by field name as flexa/get-preset-schema reports them. A field left out keeps the text the preset ships with. Plain text only: a value containing < or > is refused rather than stripped. A media field takes an existing attachment ID.', 'flexa-block' ),
							'additionalProperties' => [ 'type' => [ 'string', 'integer' ] ],
						],
						'title'           => [
							'type'        => 'string',
							'maxLength'   => Draft_Writer::MAX_TITLE_CHARS,
							'description' => __( 'Title for the new page. Omit it to use the preset\'s own title.', 'flexa-block' ),
						],
						'post_status'     => [
							'type'        => 'string',
							'enum'        => [ 'draft' ],
							'default'     => 'draft',
							'description' => __( 'Only draft. The field exists so that asking for anything else is refused plainly rather than ignored.', 'flexa-block' ),
						],
						'idempotency_key' => [
							'type'        => 'string',
							'maxLength'   => Draft_Writer::MAX_KEY_CHARS,
							'description' => __( 'Your own id for this request. Send the same key again and the same page comes back instead of a second one. Without a key, an identical request repeated within five minutes is still treated as a retry.', 'flexa-block' ),
						],
					],
					'required'   => [ 'preset' ],
				],
				'output_schema'       => self::draft_schema(),
				'permission_callback' => [ __CLASS__, 'can_use_presets' ],
				'execute_callback'    => [ __CLASS__, 'create_page_draft' ],
			]
		);

		return $abilities;
	}

	/**
	 * The floor for touching this plugin's presets at all.
	 *
	 * `edit_pages`, which is what the admin import route asks for, and the same
	 * answer for all three abilities so that discovery and creation cannot
	 * disagree about who is allowed in. The precise check for creating a given
	 * preset's post type is made by {@see Draft_Writer}, which knows which type
	 * that is.
	 *
	 * @return bool
	 */
	public static function can_use_presets(): bool {
		return current_user_can( 'edit_pages' );
	}

	/**
	 * Every preset this site can create from.
	 *
	 * @param mixed $input Validated ability input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function list_presets( $input = [] ) {
		$limited = Request_Limits::tap( self::LIST, Request_Limits::DISCOVERY_PER_HOUR );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		$only      = is_array( $input ) && isset( $input['source'] ) ? sanitize_key( (string) $input['source'] ) : '';
		$presets   = [];
		$truncated = false;

		foreach ( Import_Registry::sources() as $key => $source ) {
			if ( '' !== $only && $only !== $key ) {
				continue;
			}

			foreach ( $source->items() as $item ) {
				$id = (string) ( $item['id'] ?? '' );
				if ( '' === $id ) {
					continue;
				}

				if ( count( $presets ) >= self::MAX_PRESETS ) {
					$truncated = true;
					break 2;
				}

				$slots = isset( $item['slots'] ) && is_array( $item['slots'] ) ? $item['slots'] : [];

				$presets[] = [
					'source'           => $key,
					'source_label'     => Ability_Support::bound( $source->label() ),
					'id'               => $id,
					'title'            => Ability_Support::bound( (string) ( $item['title'] ?? $id ) ),
					'description'      => Ability_Support::bound( (string) ( $item['description'] ?? '' ) ),
					'category'         => (string) ( $item['category'] ?? '' ),
					'version'          => (string) ( $item['version'] ?? '' ),
					'blocks'           => array_values( array_map( 'strval', (array) ( $item['blocks'] ?? [] ) ) ),
					'fields'           => array_keys( $slots ),
					'existing_post_id' => self::existing( $key, $id ),
				];
			}
		}

		if ( '' !== $only && ! $presets && null === Import_Registry::source( $only ) ) {
			return new \WP_Error(
				'flexa_mcp_unknown_source',
				__( 'No preset source with that key. Omit source to list every one this site has.', 'flexa-block' ),
				[ 'status' => 400 ]
			);
		}

		return [
			'presets'   => $presets,
			'truncated' => $truncated,
			'limits'    => self::limits(),
		];
	}

	/**
	 * What one preset accepts.
	 *
	 * @param mixed $input Validated ability input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function preset_schema( $input ) {
		$limited = Request_Limits::tap( self::SCHEMA, Request_Limits::DISCOVERY_PER_HOUR );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		$input      = is_array( $input ) ? $input : [];
		$source_key = isset( $input['source'] ) ? sanitize_key( (string) $input['source'] ) : '';
		$source_key = '' !== $source_key ? $source_key : Sample_Source::KEY;
		$preset_id  = isset( $input['preset'] ) ? sanitize_key( (string) $input['preset'] ) : '';

		$source = Import_Registry::source( $source_key );
		if ( null === $source ) {
			return new \WP_Error(
				'flexa_mcp_unknown_source',
				__( 'No preset source with that key. Call flexa/list-presets to see what this site has.', 'flexa-block' ),
				[ 'status' => 400 ]
			);
		}

		$definition = '' !== $preset_id ? $source->definition( $preset_id ) : null;
		if ( ! is_array( $definition ) ) {
			return new \WP_Error(
				'flexa_mcp_unknown_preset',
				__( 'No preset with that id in this source. Call flexa/list-presets to see what this site has.', 'flexa-block' ),
				[ 'status' => 404 ]
			);
		}

		$declared = Preset_Slots::declared( $definition );
		$fields   = [];

		foreach ( $declared as $key => $spec ) {
			$fields[ (string) $key ] = [
				'type'       => (string) $spec['type'],
				'label'      => Ability_Support::bound( (string) $spec['label'] ),
				'default'    => Ability_Support::bound( (string) $spec['default'] ),
				'max_length' => (int) $spec['max'],
				'help'       => Ability_Support::bound( (string) $spec['help'] ),
				'required'   => false,
			];
		}

		$contract = isset( $definition['slot_contract'] ) ? (int) $definition['slot_contract'] : 0;
		$notes    = [];

		// A preset written against a contract this code does not speak keeps its
		// own wording and offers nothing. Worth saying out loud: an agent that
		// got an empty field list for a preset the listing said has fields would
		// otherwise have no way to tell that from a bug.
		if ( ! $declared && ! empty( $definition['slots'] ) ) {
			$notes[] = __( 'This preset was written for a newer version of Flexa Block, so its fields are not offered here. Creating a draft from it still works and produces the page as the preset ships it.', 'flexa-block' );
		}

		return [
			'source'           => $source_key,
			'id'               => (string) $definition['id'],
			'title'            => Ability_Support::bound( (string) ( $definition['title'] ?? $preset_id ) ),
			'post_type'        => (string) ( $definition['post_type'] ?? 'page' ),
			'version'          => (string) ( $definition['version'] ?? '' ),
			'slot_contract'    => $contract,
			'contract_spoken'  => Preset_Slots::CONTRACT,
			'fields'           => Ability_Support::json_object( $fields ),
			'existing_post_id' => self::existing( $source_key, (string) $definition['id'] ),
			'limits'           => self::limits(),
			'notes'            => $notes,
		];
	}

	/**
	 * Create one draft page from one preset.
	 *
	 * @param mixed $input Validated ability input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function create_page_draft( $input ) {
		return Draft_Writer::create_draft( is_array( $input ) ? $input : [] );
	}

	/**
	 * The post already created from a preset, when the caller may see it.
	 *
	 * Reported as information, never as a refusal. The admin import panel uses
	 * the same lookup to offer "open the one you have" instead of its import
	 * button, and an agent deserves the same fact: a site usually wants one
	 * contact page, and a client about to create a second should be able to see
	 * that it is doing so. It is not a block, because two drafts from one
	 * preset with different text is a legitimate thing to want, and because
	 * this is not the idempotency check. That one asks whether this request was
	 * already handled; this one asks whether this preset was ever used.
	 *
	 * Suppressed when the caller cannot read the post, so this cannot be used
	 * to find out about someone else's private page.
	 *
	 * @param string $source_key Source key.
	 * @param string $preset_id  Preset id.
	 * @return int Post ID, or 0.
	 */
	private static function existing( string $source_key, string $preset_id ): int {
		$post_id = Content_Importer::find_existing( $source_key, $preset_id );

		return $post_id > 0 && current_user_can( 'read_post', $post_id ) ? $post_id : 0;
	}

	/**
	 * The numbers a client needs in order to stay inside them.
	 *
	 * @return array<string, int>
	 */
	private static function limits(): array {
		return [
			'drafts_per_hour'      => Request_Limits::DRAFTS_PER_HOUR,
			'drafts_left'          => max( 0, Request_Limits::DRAFTS_PER_HOUR - Request_Limits::used( Draft_Writer::OPERATION ) ),
			'max_fields'           => Preset_Slots::MAX_SLOTS,
			'max_field_bytes'      => Preset_Slots::MAX_TOTAL_BYTES,
			'max_request_bytes'    => Draft_Writer::MAX_REQUEST_BYTES,
			'max_title_characters' => Draft_Writer::MAX_TITLE_CHARS,
		];
	}

	/**
	 * Schema shared by every response that reports the limits.
	 *
	 * @return array<string, mixed>
	 */
	private static function limits_schema(): array {
		return [
			'type'        => 'object',
			'description' => __( 'The ceilings this site applies, so a client can stay inside them instead of discovering them by being refused.', 'flexa-block' ),
			'properties'  => [
				'drafts_per_hour'      => [ 'type' => 'integer' ],
				'drafts_left'          => [
					'type'        => 'integer',
					'description' => __( 'Drafts you may still create in the current hour.', 'flexa-block' ),
				],
				'max_fields'           => [ 'type' => 'integer' ],
				'max_field_bytes'      => [
					'type'        => 'integer',
					'description' => __( 'Total size of all field values in one request, in bytes.', 'flexa-block' ),
				],
				'max_request_bytes'    => [ 'type' => 'integer' ],
				'max_title_characters' => [ 'type' => 'integer' ],
			],
		];
	}

	/**
	 * Output schema for `flexa/list-presets`.
	 *
	 * @return array<string, mixed>
	 */
	private static function list_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'presets'   => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'source'           => [ 'type' => 'string' ],
							'source_label'     => [ 'type' => 'string' ],
							'id'               => [ 'type' => 'string' ],
							'title'            => [ 'type' => 'string' ],
							'description'      => [ 'type' => 'string' ],
							'category'         => [ 'type' => 'string' ],
							'version'          => [ 'type' => 'string' ],
							'blocks'           => [
								'type'        => 'array',
								'description' => __( 'Blocks this preset is built from.', 'flexa-block' ),
								'items'       => [ 'type' => 'string' ],
							],
							'fields'           => [
								'type'        => 'array',
								'description' => __( 'Names of the fields this preset accepts. Call flexa/get-preset-schema for what each one takes.', 'flexa-block' ),
								'items'       => [ 'type' => 'string' ],
							],
							'existing_post_id' => [
								'type'        => 'integer',
								'description' => __( 'A page already created from this preset, or 0. Information, not a restriction: creating another is allowed.', 'flexa-block' ),
							],
						],
					],
				],
				'truncated' => [
					'type'        => 'boolean',
					'description' => __( 'True when there were more presets than one listing reports, so this one is incomplete. Ask again for a single source.', 'flexa-block' ),
				],
				'limits'    => self::limits_schema(),
			],
		];
	}

	/**
	 * Output schema for `flexa/get-preset-schema`.
	 *
	 * @return array<string, mixed>
	 */
	private static function schema_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'source'           => [ 'type' => 'string' ],
				'id'               => [ 'type' => 'string' ],
				'title'            => [ 'type' => 'string' ],
				'post_type'        => [ 'type' => 'string' ],
				'version'          => [ 'type' => 'string' ],
				'slot_contract'    => [
					'type'        => 'integer',
					'description' => __( 'Which version of the field contract this preset was written against.', 'flexa-block' ),
				],
				'contract_spoken'  => [
					'type'        => 'integer',
					'description' => __( 'The highest contract version this site understands. A preset above it keeps its own wording and offers no fields.', 'flexa-block' ),
				],
				'fields'           => [
					'type'                 => 'object',
					'description'          => __( 'The fields this preset accepts, keyed by the name to send in slots. Every field is optional: one left out keeps the text the preset ships with.', 'flexa-block' ),
					'additionalProperties' => [
						'type'       => 'object',
						'properties' => [
							'type'       => [
								'type'        => 'string',
								'enum'        => [ 'text', 'multiline', 'url', 'email', 'phone', 'media' ],
								'description' => __( 'What the field holds. A media field takes the ID of an image already in this site\'s library.', 'flexa-block' ),
							],
							'label'      => [ 'type' => 'string' ],
							'default'    => [
								'type'        => 'string',
								'description' => __( 'What the preset uses when the field is left out.', 'flexa-block' ),
							],
							'max_length' => [
								'type'        => 'integer',
								'description' => __( 'Longest value accepted, in characters.', 'flexa-block' ),
							],
							'help'       => [ 'type' => 'string' ],
							'required'   => [ 'type' => 'boolean' ],
						],
					],
				],
				'existing_post_id' => [ 'type' => 'integer' ],
				'limits'           => self::limits_schema(),
				'notes'            => [
					'type'        => 'array',
					'description' => __( 'Anything about this preset a client should know before sending values.', 'flexa-block' ),
					'items'       => [ 'type' => 'string' ],
				],
			],
		];
	}

	/**
	 * Output schema for `flexa/create-page-draft`.
	 *
	 * @return array<string, mixed>
	 */
	private static function draft_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id'         => [ 'type' => 'integer' ],
				'post_type'       => [ 'type' => 'string' ],
				'status'          => [
					'type'        => 'string',
					'description' => __( 'Always draft.', 'flexa-block' ),
				],
				'title'           => [ 'type' => 'string' ],
				'edit_link'       => [
					'type'        => 'string',
					'description' => __( 'Where a person opens the new page in the editor.', 'flexa-block' ),
				],
				'view_link'       => [
					'type'        => 'string',
					'description' => __( 'Preview link. A draft has no public address, so this is a preview URL and works only for someone who can edit the page.', 'flexa-block' ),
				],
				'slots_filled'    => [
					'type'        => 'array',
					'description' => __( 'Which fields took a value from this request. Anything absent kept the preset\'s own text.', 'flexa-block' ),
					'items'       => [ 'type' => 'string' ],
				],
				'reused'          => [
					'type'        => 'boolean',
					'description' => __( 'True when this request had already been handled, so the page reported is the one it created the first time.', 'flexa-block' ),
				],
				'idempotency_key' => [ 'type' => 'string' ],
				'drafts_left'     => [ 'type' => 'integer' ],
				'warnings'        => [
					'type'        => 'array',
					'description' => __( 'Things that went less than perfectly but did not stop the page being created.', 'flexa-block' ),
					'items'       => [ 'type' => 'string' ],
				],
			],
		];
	}
}
