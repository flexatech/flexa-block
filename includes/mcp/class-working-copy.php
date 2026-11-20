<?php
declare(strict_types=1);
/**
 * Draft copies of published pages, and putting a reviewed copy back.
 *
 * An agent never writes to a published page. When it is asked to change one,
 * {@see Page_Editor} makes the change on a working copy instead: a draft that
 * carries the original's content and remembers which post it came from and
 * what that post looked like at the time. A person reviews the copy in the
 * editor and, if it is right, applies it. Applying writes the copy's title and
 * content onto the original, so the original keeps its ID, address, comments
 * and SEO history, and core keeps a revision of what it replaced.
 *
 * Applying is a person's act, never an agent's. It is not an ability; it is a
 * REST route behind a nonce that only the block editor's button calls, and it
 * needs the right to publish the original.
 *
 * Loaded with the module's settings rather than its runtime, so that a copy
 * made while the module was on can still be applied, or recognised as a copy
 * in the page list, after the module has been switched off.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates, finds and applies working copies.
 */
class Working_Copy {

	/** Post meta on a copy: ID of the post it is a copy of. */
	const ORIGINAL_META = '_flexa_working_copy_of';

	/** Post meta on a copy: the original's `post_modified_gmt` when the copy was made. */
	const BASE_META = '_flexa_working_copy_base';

	/** Script handle for the editor panel. */
	const SCRIPT = 'flexa-block-working-copy';

	/** REST namespace, shared with the module's settings route. */
	const REST_NS = 'flexa-block/v1';

	/**
	 * Meta that describes a post's own history or state rather than its page,
	 * and so is not carried onto a copy.
	 *
	 * @var list<string>
	 */
	const SKIP_META = [
		'_edit_lock',
		'_edit_last',
		'_wp_old_slug',
		'_wp_old_date',
		'_wp_desired_post_slug',
		'_wp_trash_meta_status',
		'_wp_trash_meta_time',
		'_encloseme',
		'_pingme',
		'_flexa_import_source',
		'_flexa_import_id',
		'_flexa_import_version',
		'_flexa_import_hash',
		'_flexa_slot_contract',
		'_flexa_slot_keys',
		'_flexa_slot_values',
		// The front-end edit log and review marker belong to one post, not to a copy of it.
		'_flexa_block_inline_edit_log',
		'_flexa_review_copy',
		self::ORIGINAL_META,
		self::BASE_META,
	];

	/**
	 * Meta applied back to the original along with title and content.
	 *
	 * Short on purpose: the template and the featured image are what a person
	 * reviewing a page in the editor can see and change. Anything else the copy
	 * carries came from the original and goes back unchanged by not going back.
	 *
	 * @var list<string>
	 */
	const APPLY_META = [ '_wp_page_template', '_thumbnail_id' ];

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		if ( doing_action( 'rest_api_init' ) ) {
			self::register_routes();
		} else {
			add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
		}

		add_filter( 'display_post_states', [ __CLASS__, 'post_states' ], 10, 2 );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'enqueue' ] );
	}

	/**
	 * The open working copy of a post, or 0.
	 *
	 * @param int $original_id Original post ID.
	 * @return int
	 */
	public static function find( int $original_id ): int {
		$found = get_posts(
			[
				'post_type'      => 'any',
				'post_status'    => [ 'draft', 'pending' ],
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Lookup by provenance meta, once per agent edit or page-list row; there is no other way to find a copy from its original.
				'meta_query'     => [
					[
						'key'   => self::ORIGINAL_META,
						'value' => (string) $original_id,
					],
				],
			]
		);

		return $found ? (int) $found[0] : 0;
	}

	/**
	 * The post a working copy was made from, or 0 when this is not one.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public static function original_of( int $post_id ): int {
		return (int) get_post_meta( $post_id, self::ORIGINAL_META, true );
	}

	/**
	 * Make a working copy of a post.
	 *
	 * The copy is a draft owned by the current user, with the original's title,
	 * content, excerpt, parent, order, terms and page meta. Its content is the
	 * original's byte for byte: the KSES filters are held off for the insert,
	 * because they would re-filter markup the original already has and a copy
	 * that differs from its original before anyone changed it would make the
	 * review meaningless.
	 *
	 * @param \WP_Post $original Post to copy.
	 * @return int|\WP_Error Copy ID.
	 */
	public static function create( \WP_Post $original ) {
		$postarr = [
			'post_type'      => $original->post_type,
			'post_status'    => 'draft',
			'post_title'     => $original->post_title,
			'post_content'   => $original->post_content,
			'post_excerpt'   => $original->post_excerpt,
			'post_parent'    => (int) $original->post_parent,
			'menu_order'     => (int) $original->menu_order,
			'comment_status' => $original->comment_status,
			'ping_status'    => $original->ping_status,
			'post_author'    => get_current_user_id(),
		];

		kses_remove_filters();
		$copy_id = wp_insert_post( wp_slash( $postarr ), true );
		kses_init_filters();

		if ( is_wp_error( $copy_id ) ) {
			return $copy_id;
		}
		$copy_id = (int) $copy_id;

		foreach ( (array) get_post_meta( $original->ID ) as $key => $values ) {
			if ( in_array( $key, self::SKIP_META, true ) || ! is_array( $values ) ) {
				continue;
			}
			foreach ( $values as $value ) {
				add_post_meta( $copy_id, (string) $key, wp_slash( maybe_unserialize( $value ) ) );
			}
		}

		foreach ( get_object_taxonomies( $original->post_type ) as $taxonomy ) {
			$terms = wp_get_object_terms( $original->ID, $taxonomy, [ 'fields' => 'ids' ] );
			if ( is_array( $terms ) && $terms ) {
				wp_set_object_terms( $copy_id, array_map( 'intval', $terms ), $taxonomy );
			}
		}

		update_post_meta( $copy_id, self::ORIGINAL_META, (int) $original->ID );
		update_post_meta( $copy_id, self::BASE_META, (string) $original->post_modified_gmt );

		return $copy_id;
	}

	/**
	 * Write a reviewed copy onto its original and remove the copy.
	 *
	 * Refused when the original changed after the copy was made, unless the
	 * caller says to go ahead: applying would silently undo that change, and
	 * the person pressing the button is the one who should decide that.
	 *
	 * @param int  $copy_id Working copy ID.
	 * @param bool $force   Apply even though the original changed since.
	 * @return array{original_id:int, edit_link:string, view_link:string}|\WP_Error
	 */
	public static function apply( int $copy_id, bool $force = false ) {
		$copy        = get_post( $copy_id );
		$original_id = self::original_of( $copy_id );
		$original    = $original_id > 0 ? get_post( $original_id ) : null;

		if ( ! $copy instanceof \WP_Post || ! $original instanceof \WP_Post || 'trash' === $original->post_status ) {
			return new \WP_Error(
				'flexa_working_copy_missing',
				__( 'The page this copy was made from no longer exists, so there is nothing to apply it to.', 'flexa-block' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! self::can_apply( $copy, $original ) ) {
			return new \WP_Error(
				'flexa_working_copy_forbidden',
				__( 'You need permission to edit and publish the original page to apply this copy.', 'flexa-block' ),
				[ 'status' => 403 ]
			);
		}

		if ( ! function_exists( 'wp_check_post_lock' ) ) {
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}
		$holder = wp_check_post_lock( $original_id );
		if ( $holder ) {
			$user = get_userdata( (int) $holder );

			return new \WP_Error(
				'flexa_working_copy_locked',
				sprintf(
					/* translators: %s: display name of the person editing */
					__( '%s has the original page open in the editor. Apply this copy once they have closed it.', 'flexa-block' ),
					$user ? $user->display_name : __( 'Someone', 'flexa-block' )
				),
				[ 'status' => 409 ]
			);
		}

		if ( ! $force && self::conflicted( $copy_id ) ) {
			return new \WP_Error(
				'flexa_working_copy_conflict',
				__( 'The original page has been changed since this copy was made. Applying the copy would undo those changes.', 'flexa-block' ),
				[ 'status' => 409 ]
			);
		}

		// Title and content only, onto the original as it stands: its status,
		// slug, date and author stay its own. KSES is held off for the same
		// reason as in create(): the markup is the original's own, as reviewed
		// in the editor by someone allowed to publish it.
		kses_remove_filters();
		$result = wp_update_post(
			wp_slash(
				[
					'ID'           => $original_id,
					'post_title'   => $copy->post_title,
					'post_content' => $copy->post_content,
					'post_excerpt' => $copy->post_excerpt,
				]
			),
			true
		);
		kses_init_filters();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		foreach ( self::APPLY_META as $key ) {
			$value = get_post_meta( $copy_id, $key, true );
			if ( '' === $value || null === $value ) {
				delete_post_meta( $original_id, $key );
			} else {
				update_post_meta( $original_id, $key, wp_slash( $value ) );
			}
		}

		/**
		 * Fires after a working copy has been applied, before it is deleted.
		 *
		 * Lets data kept on the copy (the front-end edit log, say) move to the
		 * original.
		 *
		 * @param int $original_id Original post ID.
		 * @param int $copy_id     Working copy ID, about to be deleted.
		 */
		do_action( 'flexa_block_working_copy_applied', $original_id, $copy_id );

		wp_delete_post( $copy_id, true );

		return [
			'original_id' => $original_id,
			'edit_link'   => (string) get_edit_post_link( $original_id, 'raw' ),
			'view_link'   => (string) get_permalink( $original_id ),
		];
	}

	/**
	 * What a copy holds right now, as a short string to compare later.
	 *
	 * @param \WP_Post $copy Working copy.
	 */
	public static function fingerprint( \WP_Post $copy ): string {
		return md5( $copy->post_title . "\n" . $copy->post_content );
	}

	/**
	 * Whether the original changed after the copy was made.
	 *
	 * @param int $copy_id Working copy ID.
	 * @return bool
	 */
	public static function conflicted( int $copy_id ): bool {
		$original = get_post( self::original_of( $copy_id ) );
		$base     = (string) get_post_meta( $copy_id, self::BASE_META, true );

		return $original instanceof \WP_Post && $base !== (string) $original->post_modified_gmt;
	}

	/**
	 * Whether the current user may put a copy onto its original.
	 *
	 * @param \WP_Post $copy     Working copy.
	 * @param \WP_Post $original Original.
	 * @return bool
	 */
	private static function can_apply( \WP_Post $copy, \WP_Post $original ): bool {
		$type    = get_post_type_object( $original->post_type );
		$publish = $type instanceof \WP_Post_Type ? (string) $type->cap->publish_posts : 'publish_pages';

		$allowed = current_user_can( 'edit_post', $copy->ID )
			&& current_user_can( 'edit_post', $original->ID )
			&& current_user_can( $publish );

		/**
		 * Filters whether the current user may apply a working copy.
		 *
		 * Can only narrow the rule above in practice: front-end review uses it to
		 * leave applying to administrators, or to someone other than the editor.
		 *
		 * @param bool     $allowed  Whether they may.
		 * @param \WP_Post $copy     Working copy.
		 * @param \WP_Post $original Original.
		 */
		return (bool) apply_filters( 'flexa_block_working_copy_can_apply', $allowed, $copy, $original );
	}

	/**
	 * Register the apply route.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NS,
			'/working-copy/(?P<id>\d+)/apply',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'rest_apply' ],
				'permission_callback' => static function ( \WP_REST_Request $request ): bool {
					return current_user_can( 'edit_post', (int) $request['id'] );
				},
				'args'                => [
					'force' => [
						'type'              => 'boolean',
						'required'          => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					],
					// The copy's content as the person saw it when deciding (from
					// the review list). Applying is refused if it has changed since,
					// so nothing goes live that nobody looked at.
					'seen'  => [
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);
	}

	/**
	 * POST handler for the apply route.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_apply( \WP_REST_Request $request ) {
		$copy_id = (int) $request['id'];
		if ( self::original_of( $copy_id ) < 1 ) {
			return new \WP_Error(
				'flexa_working_copy_not_copy',
				__( 'This page is not a working copy.', 'flexa-block' ),
				[ 'status' => 400 ]
			);
		}

		$seen = (string) $request->get_param( 'seen' );
		$copy = get_post( $copy_id );
		if ( '' !== $seen && $copy instanceof \WP_Post && ! hash_equals( self::fingerprint( $copy ), $seen ) ) {
			return new \WP_Error(
				'flexa_working_copy_changed',
				__( 'This copy has new changes since you opened the list. Look at them before applying.', 'flexa-block' ),
				[ 'status' => 409 ]
			);
		}

		$result = self::apply( $copy_id, (bool) $request->get_param( 'force' ) );

		return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
	}

	/**
	 * Label working copies, and the pages that have one, in the post list.
	 *
	 * @param mixed $states Post states, as the filter received them.
	 * @param mixed $post   Post.
	 * @return mixed
	 */
	public static function post_states( $states, $post ) {
		if ( ! is_array( $states ) || ! $post instanceof \WP_Post ) {
			return $states;
		}

		$original = self::original_of( $post->ID );
		if ( $original > 0 ) {
			$states['flexa_working_copy'] = sprintf(
				/* translators: %s: title of the original page */
				__( 'Copy with changes for: %s', 'flexa-block' ),
				get_the_title( $original )
			);
		} elseif ( 'publish' === $post->post_status && in_array( $post->post_type, [ 'page', 'post' ], true ) && self::find( $post->ID ) > 0 ) {
			$states['flexa_has_working_copy'] = __( 'Changes waiting for review', 'flexa-block' );
		}

		return $states;
	}

	/**
	 * Load the review panel when the post open in the editor is a working copy.
	 */
	public static function enqueue(): void {
		$post = get_post();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$original_id = self::original_of( $post->ID );
		$original    = $original_id > 0 ? get_post( $original_id ) : null;
		if ( ! $original instanceof \WP_Post ) {
			return;
		}

		$asset_file = FLEXA_BLOCK_DIR . 'build/editor/working-copy.asset.php';
		$asset      = file_exists( $asset_file ) ? require $asset_file : null;
		if ( ! is_array( $asset ) ) {
			return;
		}

		wp_enqueue_script(
			self::SCRIPT,
			FLEXA_BLOCK_URL . 'build/editor/working-copy.js',
			is_array( $asset['dependencies'] ?? null ) ? $asset['dependencies'] : [],
			is_string( $asset['version'] ?? null ) ? $asset['version'] : FLEXA_BLOCK_VER,
			true
		);
		wp_set_script_translations( self::SCRIPT, 'flexa-block', FLEXA_BLOCK_DIR . 'languages' );

		wp_localize_script(
			self::SCRIPT,
			'flexaBlockWorkingCopy',
			/**
			 * Filters what the working-copy panel in the editor receives.
			 *
			 * @param array<string,mixed> $data Panel data.
			 * @param \WP_Post            $post Working copy.
			 */
			apply_filters(
				'flexa_block_working_copy_panel_data',
				[
					'copyId'     => (int) $post->ID,
					'original'   => [
						'id'       => $original_id,
						'title'    => get_the_title( $original ),
						'status'   => (string) $original->post_status,
						'editLink' => (string) get_edit_post_link( $original_id, 'raw' ),
						'viewLink' => (string) get_permalink( $original ),
					],
					'conflicted' => self::conflicted( (int) $post->ID ),
					'canApply'   => self::can_apply( $post, $original ),
					'path'       => '/' . self::REST_NS . '/working-copy/' . (int) $post->ID . '/apply',
				],
				$post
			)
		);
	}
}
