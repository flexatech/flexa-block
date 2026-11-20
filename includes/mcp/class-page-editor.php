<?php
declare(strict_types=1);
/**
 * Read and change the content fields of blocks on an existing draft.
 *
 * This is the path for a page a person has already worked on: one they built
 * themselves, or a draft from a preset that has since been edited by hand.
 * `Draft_Writer::update_draft()` cannot help there, because it rebuilds a page
 * from its preset and would throw that work away. This changes individual
 * fields of individual blocks and leaves everything else as it is.
 *
 * What keeps that safe, in the order a call meets it:
 *
 *  1. Drafts and pending posts are changed in place. A published page is
 *     never written to: the change goes onto a working copy, a draft that a
 *     person reviews and applies (see {@see Working_Copy}). Further changes
 *     to the same published page land on the same copy.
 *  2. Not while someone has the post open in the editor. Two writers on one
 *     post is how changes get lost, whichever of them is the agent.
 *  3. The caller says which version of the post it read
 *     (`expected_modified_gmt`), and a post that has changed since is refused.
 *     A change may also carry `expect`, the value the caller believes a field
 *     has, refused when it does not.
 *  4. Only fields on the allowlist in {@see Editable_Fields}, only plain text,
 *     validated by the same rules as a preset slot.
 *  5. The write touches one block delimiter per changed block and nothing
 *     else, through {@see Block_Patcher}, which re-reads its own result.
 *  6. Core saves a revision, so every change is one click from undone.
 *
 * Every check runs before the first write, and a request either applies in
 * full or not at all.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\MCP;

use Flexa\Block\Admin\MCP_Settings;
use Flexa\Block\Block_Patcher;
use Flexa\Block\Import\Content_Importer;
use Flexa\Block\Import\Import_Manager;
use Flexa\Block\Import\Preset_Slots;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lists and edits the editable fields of blocks on one post.
 */
class Page_Editor {

	/** Operation name, also the rate-limit bucket. */
	const OPERATION = 'edit-page-blocks';

	/** Most changes one request may carry. */
	const MAX_CHANGES = 25;

	/** Most blocks one listing reports. */
	const MAX_BLOCKS = 100;

	/** Post statuses an agent may edit in place. */
	const STATUSES = [ 'draft', 'pending' ];

	/** Post status an agent may change through a working copy. */
	const VIA_COPY = 'publish';

	/** Post types an agent may edit. */
	const TYPES = [ 'page', 'post' ];

	/**
	 * The editable fields of every supported block on one post.
	 *
	 * Answers for any post the caller may edit, including a published one, and
	 * says in `editable` whether `flexa/edit-page-blocks` would accept changes
	 * to it right now and in `reason` why not. A client that reads first learns
	 * the answer before it composes a change.
	 *
	 * @param array<string, mixed> $input Ability input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function fields( array $input ) {
		$asked = self::post( $input );
		if ( is_wp_error( $asked ) ) {
			return $asked;
		}

		// A published page with a working copy is read through the copy: that is
		// where the next change will land, so its values and its modified_gmt
		// are the ones a client has to work against.
		$post = self::target( $asked );

		$blocks    = [];
		$truncated = false;

		foreach ( Block_Patcher::tokens( (string) $post->post_content ) as $token ) {
			$id = isset( $token['attrs']->blockId ) && is_string( $token['attrs']->blockId ) ? $token['attrs']->blockId : '';
			if ( '' === $id || ! Editable_Fields::supports( $token['name'] ) ) {
				continue;
			}

			if ( count( $blocks ) >= self::MAX_BLOCKS ) {
				$truncated = true;
				break;
			}

			$attrs  = self::effective( $token['name'], $token['attrs'] );
			$fields = [];

			foreach ( Editable_Fields::paths( $token['name'], $attrs ) as $path ) {
				$spec = Editable_Fields::spec( $token['name'], $path );
				$read = Block_Patcher::value_at( $attrs, explode( '.', $path ) );
				if ( null === $spec || ( null !== $read[1] && ! is_scalar( $read[1] ) ) ) {
					continue;
				}

				$fields[ $path ] = [
					'type'  => $spec['type'],
					'label' => $spec['label'],
					'value' => Ability_Support::bound( null === $read[1] ? '' : (string) $read[1] ),
				];
			}

			if ( $fields ) {
				$blocks[] = [
					'block_id' => $id,
					'block'    => $token['name'],
					'fields'   => $fields,
				];
			}
		}

		$blocked = self::why_not( $post );

		return [
			'post_id'         => (int) $asked->ID,
			'title'           => Ability_Support::bound( (string) $post->post_title ),
			'status'          => (string) $asked->post_status,
			'modified_gmt'    => (string) $post->post_modified_gmt,
			'editable'        => null === $blocked,
			'reason'          => null === $blocked ? '' : $blocked->get_error_message(),
			'working_copy_id' => $post->ID !== $asked->ID ? (int) $post->ID : 0,
			'note'            => self::note( $asked, $post ),
			'blocks'          => $blocks,
			'truncated'       => $truncated,
			'edits_left'      => self::edits_left(),
		];
	}

	/**
	 * The post a change to this one is written to.
	 *
	 * Itself, unless it is published and already has a working copy, in which
	 * case the copy. A published page without one is returned as is; the copy
	 * is only made once there is a change to put on it.
	 *
	 * @param \WP_Post $post Post the caller named.
	 * @return \WP_Post
	 */
	private static function target( \WP_Post $post ): \WP_Post {
		if ( self::VIA_COPY !== $post->post_status ) {
			return $post;
		}

		$copy = get_post( Working_Copy::find( (int) $post->ID ) );

		return $copy instanceof \WP_Post ? $copy : $post;
	}

	/**
	 * What a client should tell its user about where a change goes.
	 *
	 * @param \WP_Post $asked  Post the caller named.
	 * @param \WP_Post $target Post a change is written to.
	 * @return string
	 */
	private static function note( \WP_Post $asked, \WP_Post $target ): string {
		if ( self::VIA_COPY !== $asked->post_status ) {
			return '';
		}

		if ( $asked->ID !== $target->ID ) {
			return sprintf(
				/* translators: %d: ID of the working copy */
				__( 'This page is published. It already has a draft copy with changes waiting for review (ID %d); further changes go onto that copy, and visitors keep seeing the published page until a person applies the copy.', 'flexa-block' ),
				(int) $target->ID
			);
		}

		return __( 'This page is published, so changes are not made to it directly. They go onto a draft copy, and visitors keep seeing the published page until a person reviews the copy and applies it in the editor.', 'flexa-block' );
	}

	/**
	 * Apply a set of field changes to one post.
	 *
	 * @param array<string, mixed> $input Ability input.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function edit( array $input ) {
		if ( ! MCP_Settings::allows_edit() ) {
			return new \WP_Error(
				'flexa_mcp_edit_disabled',
				__( 'Editing existing content over MCP is switched off for this site.', 'flexa-block' ),
				[ 'status' => 403 ]
			);
		}

		if ( strlen( (string) wp_json_encode( $input ) ) > Draft_Writer::MAX_REQUEST_BYTES ) {
			return new \WP_Error(
				'flexa_mcp_request_too_large',
				sprintf(
					/* translators: %s: size limit in bytes */
					__( 'That request is too large; the limit is %s bytes.', 'flexa-block' ),
					number_format_i18n( Draft_Writer::MAX_REQUEST_BYTES )
				),
				[ 'status' => 413 ]
			);
		}

		$asked = self::post( $input );
		if ( is_wp_error( $asked ) ) {
			return $asked;
		}

		$post    = self::target( $asked );
		$blocked = self::why_not( $post );
		if ( null !== $blocked ) {
			return $blocked;
		}

		$expected = isset( $input['expected_modified_gmt'] ) ? trim( (string) $input['expected_modified_gmt'] ) : '';
		if ( $expected !== (string) $post->post_modified_gmt ) {
			return new \WP_Error(
				'flexa_mcp_stale',
				__( 'This page has changed since you read it. Read it again with flexa/get-editable-fields and make your changes against what it has now.', 'flexa-block' ),
				[
					'status'       => 409,
					'modified_gmt' => (string) $post->post_modified_gmt,
				]
			);
		}

		$title = Draft_Writer::clean_title( $input );
		if ( is_wp_error( $title ) ) {
			return $title;
		}

		$changes = isset( $input['changes'] ) && is_array( $input['changes'] ) ? array_values( $input['changes'] ) : [];
		if ( ( ! $changes && '' === $title ) || count( $changes ) > self::MAX_CHANGES ) {
			return new \WP_Error(
				'flexa_mcp_bad_changes',
				sprintf(
					/* translators: %s: maximum number of changes */
					__( 'Send a new title, or between 1 and %s field changes, or both.', 'flexa-block' ),
					number_format_i18n( self::MAX_CHANGES )
				),
				[ 'status' => 400 ]
			);
		}

		$content = (string) $post->post_content;
		$plan    = $changes ? self::plan( $content, $changes ) : [];
		if ( is_wp_error( $plan ) ) {
			return $plan;
		}

		$limited = Request_Limits::tap( self::OPERATION, Request_Limits::EDITS_PER_HOUR );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		$report = [];
		foreach ( $plan as $block_id => $sets ) {
			foreach ( $sets as $path => $set ) {
				if ( $set['before'] !== $set['after'] ) {
					$report[] = [
						'block_id' => (string) $block_id,
						'field'    => (string) $path,
						'before'   => Ability_Support::bound( $set['before'] ),
						'after'    => Ability_Support::bound( $set['after'] ),
					];
				}
			}
		}

		$retitle = '' !== $title && $title !== $post->post_title;
		if ( $retitle ) {
			$report[] = [
				'block_id' => '',
				'field'    => 'title',
				'before'   => Ability_Support::bound( (string) $post->post_title ),
				'after'    => Ability_Support::bound( $title ),
			];
		}

		if ( ! $report ) {
			return self::payload( $asked, $post, [], false, [ __( 'Those fields already have those values, so nothing was changed.', 'flexa-block' ) ] );
		}

		$patched = $plan ? self::apply( $content, $plan ) : $content;
		if ( is_wp_error( $patched ) ) {
			return $patched;
		}

		// A published page with no copy yet: make one now, after every check
		// has passed, so a refused request leaves no stray draft behind. Its
		// content is the original's byte for byte, which is what the patch was
		// computed against.
		if ( self::VIA_COPY === $post->post_status ) {
			$copy_id = Working_Copy::create( $post );
			if ( is_wp_error( $copy_id ) ) {
				return $copy_id;
			}
			$copy = get_post( $copy_id );
			if ( ! $copy instanceof \WP_Post ) {
				return new \WP_Error( 'flexa_mcp_copy_failed', __( 'The draft copy could not be created.', 'flexa-block' ), [ 'status' => 500 ] );
			}
			$post = $copy;
		}

		$lock = Draft_Writer::LOCK_PREFIX . $post->ID;
		if ( get_transient( $lock ) ) {
			return new \WP_Error(
				'flexa_mcp_in_progress',
				__( 'This page is already being changed. Wait for that answer before sending another change.', 'flexa-block' ),
				[ 'status' => 409 ]
			);
		}
		set_transient( $lock, 1, Draft_Writer::PENDING_TTL );

		// The markup written is the post's own, with plain-text values inside
		// one block's JSON attributes, encoded the way core encodes them. KSES
		// would have nothing to add, and its block-attribute pass is exactly the
		// parse-and-reserialize round trip the patcher exists to avoid.
		$postarr = [
			'ID'           => (int) $post->ID,
			'post_content' => $patched,
		];
		if ( $retitle ) {
			$postarr['post_title'] = $title;
		}

		kses_remove_filters();
		$result = wp_update_post( wp_slash( $postarr ), true );
		kses_init_filters();
		delete_transient( $lock );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		clean_post_cache( (int) $post->ID );
		$after = get_post( (int) $post->ID );

		return self::payload( $asked, $after instanceof \WP_Post ? $after : $post, $report, true, [] );
	}

	/**
	 * Validate every change and work out the value each field will get.
	 *
	 * Nothing is written here. The result is grouped by block, so each block is
	 * patched once however many of its fields change.
	 *
	 * @param string      $content Post content.
	 * @param list<mixed> $changes Changes as sent.
	 * @return array<string, array<string, array{before:string, after:string}>>|\WP_Error
	 */
	private static function plan( string $content, array $changes ) {
		Import_Manager::load_preset_slots();

		$plan = [];

		foreach ( $changes as $n => $change ) {
			if ( ! is_array( $change ) ) {
				return self::change_error( $n, __( 'each change has to be an object', 'flexa-block' ) );
			}

			$block_id = isset( $change['block_id'] ) ? (string) $change['block_id'] : '';
			$path     = isset( $change['field'] ) ? (string) $change['field'] : '';
			$value    = $change['value'] ?? null;

			if ( ! is_scalar( $value ) ) {
				return self::change_error( $n, __( 'the value has to be a single piece of text or a number', 'flexa-block' ) );
			}
			$value = (string) $value;

			$token = Block_Patcher::locate( $content, $block_id );
			if ( is_wp_error( $token ) ) {
				return self::advise( $token );
			}

			$spec = Editable_Fields::spec( $token['name'], $path );
			if ( null === $spec ) {
				return new \WP_Error(
					'flexa_mcp_field_not_editable',
					sprintf(
						/* translators: 1: field path, 2: block name */
						__( '%1$s is not a field that can be changed on %2$s. Only the fields flexa/get-editable-fields lists can be.', 'flexa-block' ),
						$path,
						$token['name']
					),
					[ 'status' => 400 ]
				);
			}

			if ( preg_match( Draft_Writer::FORBIDDEN_RE, $value ) ) {
				return new \WP_Error(
					'flexa_mcp_slot_markup',
					sprintf(
						/* translators: %s: field label */
						__( 'The value for %s has to be plain text. Remove the angle brackets: nothing sent here is treated as markup.', 'flexa-block' ),
						$spec['label']
					),
					[ 'status' => 400 ]
				);
			}

			$clean = Preset_Slots::check( $spec, $value );
			if ( is_wp_error( $clean ) ) {
				return $clean;
			}
			$clean = self::lines( (string) $clean, $spec['br'] );

			$attrs  = self::effective( $token['name'], $token['attrs'] );
			$read   = Block_Patcher::value_at( $attrs, explode( '.', $path ) );
			$before = null === $read[1] ? '' : $read[1];
			if ( ! is_scalar( $before ) ) {
				return self::change_error( $n, __( 'that field holds something other than text on this block', 'flexa-block' ) );
			}
			$before = (string) $before;

			if ( array_key_exists( 'expect', $change ) && null !== $change['expect'] && (string) $change['expect'] !== $before ) {
				return new \WP_Error(
					'flexa_mcp_expect_mismatch',
					sprintf(
						/* translators: %s: field label */
						__( '%s no longer has the value you expected. Read the page again before changing it.', 'flexa-block' ),
						$spec['label']
					),
					[ 'status' => 409 ]
				);
			}

			if ( isset( $plan[ $block_id ][ $path ] ) ) {
				return self::change_error( $n, __( 'the same field appears twice in one request', 'flexa-block' ) );
			}

			$plan[ $block_id ][ $path ] = [
				'before' => $before,
				'after'  => $clean,
			];
		}

		return $plan;
	}

	/**
	 * Write a validated plan into the content, one block delimiter at a time.
	 *
	 * @param string                                                           $content Post content.
	 * @param array<string, array<string, array{before:string, after:string}>> $plan    Validated plan.
	 * @return string|\WP_Error
	 */
	private static function apply( string $content, array $plan ) {
		foreach ( $plan as $block_id => $sets ) {
			// Located again on the current content: an earlier block's new
			// delimiter may be longer or shorter, which moves every offset after it.
			$token = Block_Patcher::locate( $content, (string) $block_id );
			if ( is_wp_error( $token ) ) {
				return self::advise( $token );
			}

			$effective = self::effective( $token['name'], $token['attrs'] );
			$touched   = [];

			foreach ( $sets as $path => $set ) {
				$segments  = explode( '.', (string) $path );
				$effective = Block_Patcher::with_value( $effective, $segments, $set['after'] );
				if ( is_wp_error( $effective ) ) {
					return self::advise( $effective );
				}
				$touched[ $segments[0] ] = true;
			}

			// Only the top-level attributes a change reached are written. A
			// default filled in for reading stays a default in storage.
			$attrs = clone $token['attrs'];
			foreach ( array_keys( $touched ) as $key ) {
				$attrs->{$key} = $effective->{$key};
			}

			$content = Block_Patcher::replace( $content, $token, $attrs );
			if ( is_wp_error( $content ) ) {
				return self::advise( $content );
			}
		}

		return $content;
	}

	/**
	 * Turn a patcher error into this ability's own code, with advice for a client.
	 *
	 * `Block_Patcher` is shared with front-end editing, so its errors say only
	 * what happened. An MCP client also needs to hear what to do next, and has
	 * been promised `flexa_mcp_*` codes, so both are added here.
	 *
	 * @param \WP_Error $error Error from `Block_Patcher`.
	 * @return \WP_Error
	 */
	private static function advise( \WP_Error $error ): \WP_Error {
		$code = (string) $error->get_error_code();
		if ( 0 !== strpos( $code, 'flexa_block_' ) ) {
			return $error;
		}

		$message = $error->get_error_message();
		if ( 'flexa_block_patch_unsafe' !== $code ) {
			$message .= ' ' . __( 'Read the page again with flexa/get-editable-fields.', 'flexa-block' );
		}

		return new \WP_Error( 'flexa_mcp_' . substr( $code, strlen( 'flexa_block_' ) ), $message, $error->get_error_data() );
	}

	/**
	 * A block's attributes with the defaults of its editable fields filled in.
	 *
	 * A block stores nothing for an attribute equal to its default, so the
	 * value a visitor sees for an untouched button label is in `block.json`,
	 * not in the post. Reading a field has to report that value, and changing
	 * one row of a list nobody has edited has to start from the default list.
	 *
	 * @param string    $block Block name.
	 * @param \stdClass $attrs Stored attributes.
	 * @return \stdClass
	 */
	private static function effective( string $block, \stdClass $attrs ): \stdClass {
		$out  = clone $attrs;
		$type = \WP_Block_Type_Registry::get_instance()->get_registered( $block );
		if ( ! $type instanceof \WP_Block_Type || ! is_array( $type->attributes ) ) {
			return $out;
		}

		foreach ( array_keys( Editable_Fields::MAP[ $block ] ?? [] ) as $pattern ) {
			$key = explode( '.', $pattern )[0];
			if ( property_exists( $out, $key ) || ! isset( $type->attributes[ $key ]['default'] ) ) {
				continue;
			}
			// Through JSON so lists stay lists and maps become objects, the
			// shape every other value here has.
			$out->{$key} = json_decode( (string) wp_json_encode( $type->attributes[ $key ]['default'] ) );
		}

		return $out;
	}

	/**
	 * Line breaks in a cleaned value, made right for the field.
	 *
	 * @param string $value Cleaned value.
	 * @param bool   $br    Whether the field is a paragraph that keeps breaks.
	 * @return string
	 */
	private static function lines( string $value, bool $br ): string {
		$value = str_replace( [ "\r\n", "\r" ], "\n", $value );

		return $br ? str_replace( "\n", '<br>', $value ) : trim( (string) preg_replace( '/\s*\n\s*/', ' ', $value ) );
	}

	/**
	 * The post an input names, if the caller may edit it.
	 *
	 * @param array<string, mixed> $input Ability input.
	 * @return \WP_Post|\WP_Error
	 */
	private static function post( array $input ) {
		$post_id = isset( $input['post_id'] ) ? (int) $input['post_id'] : 0;
		$post    = $post_id > 0 ? get_post( $post_id ) : null;

		// "No such page" for a post the caller cannot even read, so this cannot
		// be used to probe for someone else's private drafts.
		if ( ! $post instanceof \WP_Post || 'trash' === $post->post_status || ! current_user_can( 'read_post', $post_id ) ) {
			return new \WP_Error(
				'flexa_mcp_unknown_post',
				__( 'No page or post with that ID.', 'flexa-block' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! in_array( $post->post_type, self::TYPES, true ) ) {
			return new \WP_Error(
				'flexa_mcp_unsupported_type',
				__( 'Only pages and posts can be edited here.', 'flexa-block' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error(
				'flexa_mcp_cannot_edit',
				__( 'You do not have permission to edit this page.', 'flexa-block' ),
				[ 'status' => 403 ]
			);
		}

		return $post;
	}

	/**
	 * Why this post cannot be edited right now, or null when it can.
	 *
	 * @param \WP_Post $post Post.
	 * @return \WP_Error|null
	 */
	private static function why_not( \WP_Post $post ): ?\WP_Error {
		if ( ! in_array( $post->post_status, self::STATUSES, true ) && self::VIA_COPY !== $post->post_status ) {
			return new \WP_Error(
				'flexa_mcp_not_editable_status',
				sprintf(
					/* translators: %s: post status, for example future */
					__( 'Pages with the status %s cannot be edited here. Drafts, pending pages and published pages can.', 'flexa-block' ),
					$post->post_status
				),
				[ 'status' => 409 ]
			);
		}

		if ( ! function_exists( 'wp_check_post_lock' ) ) {
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}

		$holder = wp_check_post_lock( (int) $post->ID );
		if ( $holder ) {
			$user = get_userdata( (int) $holder );

			return new \WP_Error(
				'flexa_mcp_post_locked',
				sprintf(
					/* translators: %s: display name of the person editing */
					__( '%s has this page open in the editor right now. Changing it at the same time would overwrite one of you, so try again once they have closed it.', 'flexa-block' ),
					$user ? $user->display_name : __( 'Someone', 'flexa-block' )
				),
				[ 'status' => 409 ]
			);
		}

		return null;
	}

	/**
	 * Error for one malformed change, naming which.
	 *
	 * @param int|string $n      Index of the change.
	 * @param string     $reason Lower-case reason clause.
	 * @return \WP_Error
	 */
	private static function change_error( $n, string $reason ): \WP_Error {
		return new \WP_Error(
			'flexa_mcp_bad_change',
			sprintf(
				/* translators: 1: change number, starting at 1, 2: reason */
				__( 'Change %1$s: %2$s.', 'flexa-block' ),
				number_format_i18n( (int) $n + 1 ),
				$reason
			),
			[ 'status' => 400 ]
		);
	}

	/**
	 * Edits the caller may still make this hour.
	 *
	 * @return int
	 */
	private static function edits_left(): int {
		return max( 0, Request_Limits::EDITS_PER_HOUR - Request_Limits::used( self::OPERATION ) );
	}

	/**
	 * What an edit reports.
	 *
	 * Carries the new `modified_gmt`, which is what the next edit has to send.
	 *
	 * @param \WP_Post                                                                $asked    Post the caller named.
	 * @param \WP_Post                                                                $post     Post the edit was written to.
	 * @param list<array{block_id:string, field:string, before:string, after:string}> $changes  What changed.
	 * @param bool                                                                    $written  Whether anything was written.
	 * @param list<string>                                                            $warnings Warnings.
	 * @return array<string, mixed>
	 */
	private static function payload( \WP_Post $asked, \WP_Post $post, array $changes, bool $written, array $warnings ): array {
		$copy = $post->ID !== $asked->ID;

		return [
			'post_id'         => (int) $asked->ID,
			'status'          => (string) $asked->post_status,
			'modified_gmt'    => (string) $post->post_modified_gmt,
			'updated'         => $written,
			'changes'         => $changes,
			'working_copy_id' => $copy ? (int) $post->ID : 0,
			'note'            => $copy
				? sprintf(
					/* translators: %d: ID of the working copy */
					__( 'The page is published, so these changes were made on a draft copy (ID %d), not on the live page. Visitors keep seeing the published page until a person opens the copy in the editor and presses "Apply to original". Tell the person that, and give them the edit link.', 'flexa-block' ),
					(int) $post->ID
				)
				: '',
			'edit_link'       => (string) get_edit_post_link( (int) $post->ID, 'raw' ),
			'view_link'       => Content_Importer::view_link( (int) $post->ID ),
			'edits_left'      => self::edits_left(),
			'warnings'        => $warnings,
		];
	}
}
