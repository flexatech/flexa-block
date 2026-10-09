<?php
declare(strict_types=1);
/**
 * The one way an ability is allowed to create content.
 *
 * `Content_Importer::import()` exists for the admin import button, and it does
 * something there that it must not do here: it drops KSES around the insert
 * (see the `kses_remove_filters()` call in that file). That is correct for its
 * own caller, where the markup is a file this plugin ships and the trigger is a
 * capability-gated click. It is not correct for a request that arrived over the
 * network from a program, so no ability calls the importer directly. They all
 * come through here, and this class is what stands between a caller and that
 * window.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\MCP;

use Flexa\Block\Admin\MCP_Settings;
use Flexa\Block\Import\Content_Importer;
use Flexa\Block\Import\Import_Registry;
use Flexa\Block\Import\Preset_Slots;
use Flexa\Block\Import\Sample_Source;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates one draft page from one preset, on behalf of an ability.
 *
 * Its job is to keep anything a caller sent away from the code that parses
 * markup. In order of how much each part is relied on:
 *
 *  1. A caller never supplies markup. It names a preset, and the markup is the
 *     file this plugin ships. The only thing a caller contributes is the value
 *     of a slot, and a slot value is substituted into a block *attribute*,
 *     never into saved HTML. Nothing a caller sends is ever parsed as markup,
 *     which is the actual answer to the KSES question rather than a filter
 *     applied to make one.
 *  2. Caller text that contains markup at all is refused outright, before
 *     anything is written. Not stripped: refused. `Preset_Slots` would strip it
 *     with `sanitize_text_field()` and carry on, and a check placed after that
 *     would be theatre because there would never be anything left to find. So
 *     the check runs on the raw value, and a caller who sent a tag hears that
 *     its request was rejected instead of silently getting a page with a
 *     heading missing half its words.
 *  3. Every part of the request is validated before the first write. Slot
 *     values go through `Preset_Slots::values()` here, which is the same
 *     validation the importer will run again when it does the substitution.
 *     Running it twice is cheap and buys the property that matters: a request
 *     with one bad value does not reach `wp_insert_post()` at all, so there is
 *     no half-made draft for anyone to clean up.
 *
 * `post_status` is pinned to draft. Not defaulted, pinned: the input schema
 * admits no other value, this class refuses one anyway, and the created post is
 * read back and put back to draft if anything on the site moved it. Publishing
 * is a person's decision, and an agent that wants a page live can ask for one.
 */
class Draft_Writer {

	/** Longest title a caller may give the new page, in characters. */
	const MAX_TITLE_CHARS = 120;

	/** Longest idempotency key a caller may send, in characters. */
	const MAX_KEY_CHARS = 64;

	/**
	 * Largest whole request this will accept, in bytes of encoded JSON.
	 *
	 * `Preset_Slots` already caps the slot values at 8192 bytes together, which
	 * is the part that ends up in the page. This is the outer figure, covering
	 * the keys, the title and anything else the request carries, and its job is
	 * to stop a megabyte of nonsense from being decoded and walked before the
	 * inner limits get their turn.
	 */
	const MAX_REQUEST_BYTES = 16384;

	/** Transient prefix for an idempotency record. */
	const KEY_PREFIX = 'flexa_mcp_idem_';

	/**
	 * How long a record for a caller-supplied key is kept, in seconds.
	 *
	 * A day. A client that sends its own key is stating that this request has
	 * an identity, and the answer should still be the same one an hour later
	 * when a person asks it to try again.
	 */
	const EXPLICIT_TTL = DAY_IN_SECONDS;

	/**
	 * How long a record for a key we derived ourselves is kept, in seconds.
	 *
	 * Five minutes, and much shorter than the explicit one on purpose. With no
	 * key from the caller there is nothing to tell a retry apart from a second
	 * request that happens to look identical, and the two deserve opposite
	 * answers. Five minutes covers a retry storm, which is the failure worth
	 * preventing, and expires long before someone legitimately asks for a
	 * second copy of the same page.
	 */
	const DERIVED_TTL = 300;

	/** How long a reservation taken out before the insert is held, in seconds. */
	const PENDING_TTL = 120;

	/** Value a reservation stores while the insert is in flight. */
	const PENDING = 'pending';

	/** Operation name the idempotency scope is keyed on. */
	const OPERATION = 'create-page-draft';

	/**
	 * Characters refused anywhere in caller text.
	 *
	 * Angle brackets, and the control characters that have no business in a
	 * heading. Tab, newline and carriage return are left out of the class
	 * because a multiline slot is allowed to contain them. An ampersand is
	 * allowed: a business called "Bánh & Cà Phê" is not an attack, the value
	 * lands in a JSON attribute rather than in markup, and the block escapes it
	 * when it renders.
	 */
	const FORBIDDEN_RE = '/[<>]|[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/';

	/**
	 * Create a draft page from a preset.
	 *
	 * @param array<string, mixed> $input Ability input, already schema-validated.
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function create_draft( array $input ) {
		// Asked again here even though the ability does not exist with write
		// off. This class is the gate, and a gate that trusts its callers to
		// have checked is not a gate.
		if ( ! MCP_Settings::allows_write() ) {
			return new \WP_Error(
				'flexa_mcp_write_disabled',
				__( 'Creating content over MCP is switched off for this site.', 'flexa-block' ),
				[ 'status' => 403 ]
			);
		}

		$size = strlen( (string) wp_json_encode( $input ) );
		if ( $size > self::MAX_REQUEST_BYTES ) {
			return new \WP_Error(
				'flexa_mcp_request_too_large',
				sprintf(
					/* translators: %s: size limit in bytes */
					__( 'That request is too large; the limit is %s bytes.', 'flexa-block' ),
					number_format_i18n( self::MAX_REQUEST_BYTES )
				),
				[ 'status' => 413 ]
			);
		}

		$status = isset( $input['post_status'] ) ? (string) $input['post_status'] : 'draft';
		if ( 'draft' !== $status ) {
			return new \WP_Error(
				'flexa_mcp_publish_refused',
				__( 'This only creates drafts. Publishing a page is left to a person, so ask for a draft and have someone review it.', 'flexa-block' ),
				[ 'status' => 400 ]
			);
		}

		$source_key = sanitize_key( (string) ( $input['source'] ?? '' ) );
		if ( '' === $source_key ) {
			$source_key = Sample_Source::KEY;
		}

		$source = Import_Registry::source( $source_key );
		if ( null === $source ) {
			return new \WP_Error(
				'flexa_mcp_unknown_source',
				__( 'No preset source with that key. Call flexa/list-presets to see what this site has.', 'flexa-block' ),
				[ 'status' => 400 ]
			);
		}

		$preset_id  = sanitize_key( (string) ( $input['preset'] ?? '' ) );
		$definition = '' !== $preset_id ? $source->definition( $preset_id ) : null;
		if ( ! is_array( $definition ) ) {
			return new \WP_Error(
				'flexa_mcp_unknown_preset',
				__( 'No preset with that id in this source. Call flexa/list-presets to see what this site has.', 'flexa-block' ),
				[ 'status' => 404 ]
			);
		}
		$definition['id'] = $preset_id;

		$capable = self::can_create( $definition );
		if ( is_wp_error( $capable ) ) {
			return $capable;
		}

		$slots = isset( $input['slots'] ) && is_array( $input['slots'] ) ? $input['slots'] : [];

		$gated = self::gate_values( $slots );
		if ( is_wp_error( $gated ) ) {
			return $gated;
		}

		$title = isset( $input['title'] ) ? trim( (string) $input['title'] ) : '';
		if ( '' !== $title ) {
			$gated = self::gate_one( __( 'Title', 'flexa-block' ), $title );
			if ( is_wp_error( $gated ) ) {
				return $gated;
			}

			$title = trim( (string) sanitize_text_field( $title ) );
			if ( mb_strlen( $title ) > self::MAX_TITLE_CHARS ) {
				return new \WP_Error(
					'flexa_mcp_title_too_long',
					sprintf(
						/* translators: %s: character limit */
						__( 'Keep the title to %s characters or fewer.', 'flexa-block' ),
						number_format_i18n( self::MAX_TITLE_CHARS )
					),
					[ 'status' => 400 ]
				);
			}
		}

		// The whole request, checked before the first write. A bad media ID, a
		// slot this preset does not declare, a value over its length: all of it
		// surfaces here, with the page not yet created.
		$values = Preset_Slots::values( $definition, $slots );
		if ( is_wp_error( $values ) ) {
			return $values;
		}

		$key = self::requested_key( $input );
		if ( is_wp_error( $key ) ) {
			return $key;
		}

		$scope = self::scope( '' !== $key ? $key : self::derive( $source_key, $preset_id, $title, $slots ) );
		$seen  = get_transient( $scope );

		if ( self::PENDING === $seen ) {
			return new \WP_Error(
				'flexa_mcp_in_progress',
				__( 'An identical request is already being processed. Wait for its answer rather than sending it again.', 'flexa-block' ),
				[ 'status' => 409 ]
			);
		}

		if ( is_numeric( $seen ) && self::alive( (int) $seen ) ) {
			return self::payload(
				(int) $seen,
				true,
				$key,
				[ __( 'This request was already handled, so the page it created is being returned rather than a second copy.', 'flexa-block' ) ]
			);
		}

		$limited = Request_Limits::tap( self::OPERATION, Request_Limits::DRAFTS_PER_HOUR );
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		// Reserved before the insert, so two copies of one request arriving at
		// once produce one page and one 409 rather than two pages. The read
		// above and this write are not one atomic step, so a true dead heat can
		// still get through; the window is the few microseconds between them,
		// and the reservation closes the retry case, which is the one that
		// happens.
		set_transient( $scope, self::PENDING, self::PENDING_TTL );

		if ( '' !== $title ) {
			$definition['title'] = $title;
		}

		// Pinned, not defaulted. A preset is a file in this plugin and could
		// declare `publish`; over this path it does not get to.
		$definition['post_status'] = 'draft';

		$result = Content_Importer::import( $source_key, $definition, $slots );
		if ( is_wp_error( $result ) ) {
			delete_transient( $scope );
			return $result;
		}

		$post_id  = (int) $result['post_id'];
		$warnings = array_merge( $result['warnings'], self::hold_at_draft( $post_id ) );

		set_transient( $scope, $post_id, '' !== $key ? self::EXPLICIT_TTL : self::DERIVED_TTL );

		return self::payload( $post_id, false, $key, $warnings );
	}

	/**
	 * Whether the caller may create the kind of post this preset makes.
	 *
	 * The ability's own permission callback asks for `edit_pages`, which is the
	 * floor for touching this plugin's presets at all. This is the precise
	 * check, and it is here rather than there for a reason: which capability
	 * applies depends on the preset's post type, and a permission callback that
	 * had to resolve a preset to answer would turn an unknown preset id into
	 * "you may not", where the honest answer is "there is no such preset".
	 *
	 * @param array<string, mixed> $definition Preset definition.
	 * @return true|\WP_Error
	 */
	private static function can_create( array $definition ) {
		$type   = self::post_type( $definition );
		$object = get_post_type_object( $type );
		$cap    = $object instanceof \WP_Post_Type ? (string) $object->cap->create_posts : 'edit_pages';

		if ( ! current_user_can( $cap ) ) {
			return new \WP_Error(
				'flexa_mcp_cannot_create',
				__( 'You do not have permission to create this kind of content on this site.', 'flexa-block' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * The post type a preset will produce.
	 *
	 * Mirrors the importer's own narrowing to the two types it accepts, because
	 * the capability check has to be made against the type that will actually
	 * be created, not the one the definition asked for. The importer decides
	 * for real; both resolve the same two values the same way.
	 *
	 * @param array<string, mixed> $definition Preset definition.
	 * @return string
	 */
	private static function post_type( array $definition ): string {
		$type = sanitize_key( (string) ( $definition['post_type'] ?? 'page' ) );

		return in_array( $type, [ 'page', 'post' ], true ) && post_type_exists( $type ) ? $type : 'page';
	}

	/**
	 * Refuse a request whose slot values carry markup or control characters.
	 *
	 * @param array<array-key, mixed> $slots Values as the caller sent them.
	 * @return true|\WP_Error
	 */
	private static function gate_values( array $slots ) {
		foreach ( $slots as $key => $value ) {
			$label = is_string( $key ) ? sanitize_key( $key ) : '';

			if ( is_array( $value ) || is_object( $value ) ) {
				return new \WP_Error(
					'flexa_mcp_slot_shape',
					sprintf(
						/* translators: %s: slot key */
						__( 'The value for %s has to be a single piece of text or a number.', 'flexa-block' ),
						$label
					),
					[ 'status' => 400 ]
				);
			}

			$gated = self::gate_one( $label, (string) $value );
			if ( is_wp_error( $gated ) ) {
				return $gated;
			}
		}

		return true;
	}

	/**
	 * Refuse one piece of caller text that carries markup.
	 *
	 * @param string $label What to call the field in the message.
	 * @param string $value Raw value.
	 * @return true|\WP_Error
	 */
	private static function gate_one( string $label, string $value ) {
		if ( ! preg_match( self::FORBIDDEN_RE, $value ) ) {
			return true;
		}

		return new \WP_Error(
			'flexa_mcp_slot_markup',
			sprintf(
				/* translators: %s: field or slot name */
				__( 'The value for %s has to be plain text. Remove the angle brackets: these fields hold words, and nothing sent here is treated as markup.', 'flexa-block' ),
				$label
			),
			[ 'status' => 400 ]
		);
	}

	/**
	 * The caller's own idempotency key, checked.
	 *
	 * A key outside the allowed characters is refused rather than cleaned up.
	 * Cleaning it could fold two keys a client meant to keep apart into one,
	 * and then hand back the wrong page for the second of them.
	 *
	 * @param array<string, mixed> $input Ability input.
	 * @return string|\WP_Error The key, or '' when none was sent.
	 */
	private static function requested_key( array $input ) {
		$key = isset( $input['idempotency_key'] ) ? trim( (string) $input['idempotency_key'] ) : '';
		if ( '' === $key ) {
			return '';
		}

		if ( strlen( $key ) > self::MAX_KEY_CHARS || ! preg_match( '/^[A-Za-z0-9_.:-]+$/', $key ) ) {
			return new \WP_Error(
				'flexa_mcp_bad_key',
				sprintf(
					/* translators: %s: character limit */
					__( 'An idempotency key may be up to %s characters of letters, digits, and the marks _ . : and -.', 'flexa-block' ),
					number_format_i18n( self::MAX_KEY_CHARS )
				),
				[ 'status' => 400 ]
			);
		}

		return $key;
	}

	/**
	 * A key standing for "this exact request", for a caller that sent none.
	 *
	 * @param string               $source_key Source key.
	 * @param string               $preset_id  Preset id.
	 * @param string               $title      Title override, or ''.
	 * @param array<string, mixed> $slots      Values as the caller sent them.
	 * @return string
	 */
	private static function derive( string $source_key, string $preset_id, string $title, array $slots ): string {
		// Sorted, so the same values in a different order are the same request.
		// A client assembling its arguments from a map has no control over the
		// order they come out in, and two retries differing only in that are
		// plainly the same call.
		ksort( $slots );

		return 'auto:' . md5( (string) wp_json_encode( [ $source_key, $preset_id, $title, $slots ] ) );
	}

	/**
	 * Transient name for one key, scoped to this user and this operation.
	 *
	 * Both halves of the scope matter. Per user, because one caller's key must
	 * never hand back a page created for somebody else. Per operation, because
	 * a client that reuses a request id across different calls would otherwise
	 * get the answer to the wrong one.
	 *
	 * @param string $key Caller key, or a derived one.
	 * @return string
	 */
	private static function scope( string $key ): string {
		return self::KEY_PREFIX . md5( get_current_user_id() . '|' . self::OPERATION . '|' . $key );
	}

	/**
	 * Whether a remembered post is still there to be handed back.
	 *
	 * A record outliving its post is normal: somebody deleted the draft. In
	 * that case the record is dropped and the request is treated as new, which
	 * is better than reporting a post ID that resolves to nothing. A trashed
	 * post counts as gone, matching the importer's own view of the trash.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function alive( int $post_id ): bool {
		$post = $post_id > 0 ? get_post( $post_id ) : null;

		return $post instanceof \WP_Post && 'trash' !== $post->post_status;
	}

	/**
	 * Put the new post back to draft if anything moved it, and say what happened.
	 *
	 * The insert runs through `wp_insert_post`, which means every plugin on
	 * `wp_insert_post_data` sees it and any of them can change the status. The
	 * promise this class makes is that an ability call does not publish, so the
	 * promise is checked rather than assumed.
	 *
	 * And the correction is checked too. A filter forcing a status does it on
	 * every write, including this one, so "put it back" can fail. Tested with
	 * exactly that, and the page did come out published. There is nothing
	 * sensible left to do about it at that point: deleting a page somebody asked
	 * for, because a third plugin published it, trades one surprise for a worse
	 * one. What is left is to say so, in the response, in the words a person
	 * would need to go and look. The status this reports is read back from the
	 * database for the same reason: it has to be what the site did, not what
	 * this class asked for.
	 *
	 * @param int $post_id Post ID.
	 * @return list<string> Warnings to add to the response.
	 */
	private static function hold_at_draft( int $post_id ): array {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post || 'draft' === $post->post_status ) {
			return [];
		}

		wp_update_post(
			[
				'ID'          => $post_id,
				'post_status' => 'draft',
			]
		);

		$after = get_post( $post_id );
		if ( $after instanceof \WP_Post && 'draft' === $after->post_status ) {
			return [ __( 'Something else on this site changed the new page\'s status while it was being created; it was set back to draft.', 'flexa-block' ) ];
		}

		return [
			sprintf(
				/* translators: %s: the status the page ended up with, for example publish */
				__( 'Something else on this site is forcing a status of %s on new pages, and would not let this one stay a draft. The page exists and may be publicly visible. Someone should look at it.', 'flexa-block' ),
				$after instanceof \WP_Post ? $after->post_status : 'unknown'
			),
		];
	}

	/**
	 * What an ability reports about a draft, created or re-reported.
	 *
	 * @param int          $post_id  Post ID.
	 * @param bool         $reused   Whether this is an earlier request's page.
	 * @param string       $key      Caller's idempotency key, or ''.
	 * @param list<string> $warnings Warnings collected on the way here.
	 * @return array<string, mixed>
	 */
	private static function payload( int $post_id, bool $reused, string $key, array $warnings ): array {
		$post   = get_post( $post_id );
		$filled = get_post_meta( $post_id, Preset_Slots::FILLED_META, true );

		return [
			'post_id'         => $post_id,
			'post_type'       => $post instanceof \WP_Post ? $post->post_type : '',
			'status'          => $post instanceof \WP_Post ? $post->post_status : '',

			// The stored title, not `get_the_title()`. That one runs the display
			// filters, which turn an ampersand into `&#038;`, and a client that
			// compares what it sent with what came back would see a title it did
			// not set. This is an API, so it reports the value, not the markup
			// for showing the value.
			'title'           => Ability_Support::bound( $post instanceof \WP_Post ? $post->post_title : '' ),
			'edit_link'       => (string) get_edit_post_link( $post_id, 'raw' ),
			'view_link'       => Content_Importer::view_link( $post_id ),
			'slots_filled'    => is_array( $filled ) ? array_values( array_map( 'strval', $filled ) ) : [],
			'reused'          => $reused,
			'idempotency_key' => $key,
			'drafts_left'     => max( 0, Request_Limits::DRAFTS_PER_HOUR - Request_Limits::used( self::OPERATION ) ),
			'warnings'        => array_values( array_unique( $warnings ) ),
		];
	}
}
