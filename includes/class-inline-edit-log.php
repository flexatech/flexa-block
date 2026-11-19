<?php
/**
 * Structured log of front-end inline edits, shown in the block editor.
 *
 * @package Flexa\Block
 */

declare(strict_types=1);

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Who changed what, when, and from what to what.
 *
 * One entry per saved inline edit, kept in the post meta of the place the text
 * is stored (the page, or the template part / synced pattern shown on it), so a
 * pattern opened in the editor shows who edited it from any page. The values are
 * kept whole, not shortened to a display string: a later "undo" needs the exact
 * previous value. Past a (large) cap they are clipped and flagged.
 */
final class Inline_Edit_Log {

	const META          = '_flexa_block_inline_edit_log';
	const REST_NS       = 'flexa-block/v1';
	const SCRIPT        = 'flexa-block-inline-edit-log';
	const DEFAULT_LIMIT = 200;
	const VALUE_LIMIT   = 20000;
	const GC_HOOK       = 'flexa_block_inline_edit_log_sweep';
	const RETENTION     = 30;

	/**
	 * Hook up the REST route and the editor panel.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'enqueue' ] );
		add_action( self::GC_HOOK, [ __CLASS__, 'sweep' ] );
	}

	/**
	 * Record one saved edit against the post that stores the block.
	 *
	 * @param int                 $store_id Post the content is stored in.
	 * @param array<string,mixed> $fields   See build_entry().
	 */
	public static function record( int $store_id, array $fields ): void {
		if ( $store_id <= 0 ) {
			return;
		}

		/**
		 * Filters how many edits are kept per post. Oldest are dropped first.
		 *
		 * @param int $limit Default 200.
		 * @param int $store_id Post ID.
		 */
		$limit = max( 1, (int) apply_filters( 'flexa_block_inline_edit_log_limit', self::DEFAULT_LIMIT, $store_id ) );

		$list = get_post_meta( $store_id, self::META, true );
		$list = is_array( $list ) ? $list : [];
		$list = self::append( $list, self::build_entry( $fields ), $limit );
		// Dropped here as well as by the daily sweep, so a site with cron turned
		// off still forgets old entries.
		$list = self::prune( $list, self::cutoff() );

		// update_post_meta() expects slashed data and unslashes it.
		update_post_meta( $store_id, self::META, wp_slash( $list ) );
		self::ensure_scheduled();
	}

	/**
	 * Days an entry is kept.
	 *
	 * @return int Days, or 0 to keep entries until the per-post limit drops them.
	 */
	public static function retention_days(): int {
		/**
		 * Filters how many days front-end edits stay in the log.
		 *
		 * @param int $days Default 30. 0 keeps them until the per-post limit drops them.
		 */
		return max( 0, (int) apply_filters( 'flexa_block_inline_edit_log_retention_days', self::RETENTION ) );
	}

	/**
	 * Unix time before which entries are dropped (0 when nothing expires).
	 */
	public static function cutoff(): int {
		$days = self::retention_days();
		return $days > 0 ? time() - ( $days * DAY_IN_SECONDS ) : 0;
	}

	/**
	 * Keep only the entries made at or after a moment.
	 *
	 * Pure: no WordPress calls. An entry whose time cannot be read is kept
	 * rather than lost.
	 *
	 * @param array<int,array<string,mixed>> $items  Entries.
	 * @param int                            $cutoff Unix time; 0 keeps everything.
	 * @return array<int,array<string,mixed>>
	 */
	public static function prune( array $items, int $cutoff ): array {
		if ( $cutoff <= 0 ) {
			return array_values( $items );
		}
		return array_values(
			array_filter(
				$items,
				static function ( $entry ) use ( $cutoff ) {
					$time = strtotime( (string) ( $entry['time'] ?? '' ) . ' UTC' );
					return false === $time || $time >= $cutoff;
				}
			)
		);
	}

	/**
	 * Daily clean-up: drop expired entries from every post and remove empty logs.
	 */
	public static function sweep(): void {
		$cutoff = self::cutoff();
		$left   = 0;

		foreach ( self::post_ids() as $post_id ) {
			$list = get_post_meta( $post_id, self::META, true );
			$list = is_array( $list ) ? $list : [];
			$kept = self::prune( $list, $cutoff );

			if ( ! $kept ) {
				delete_post_meta( $post_id, self::META );
				continue;
			}
			if ( count( $kept ) !== count( $list ) ) {
				update_post_meta( $post_id, self::META, wp_slash( $kept ) );
			}
			++$left;
		}

		// Nothing left to sweep: the event goes away with the last entry, and is
		// scheduled again by the next edit.
		if ( 0 === $left ) {
			self::unschedule();
		}
	}

	/**
	 * Schedule the daily sweep if it is not already.
	 */
	public static function ensure_scheduled(): void {
		if ( ! wp_next_scheduled( self::GC_HOOK ) ) {
			wp_schedule_event( time() + 300, 'daily', self::GC_HOOK );
		}
	}

	/**
	 * Remove the daily sweep (also used on deactivation).
	 */
	public static function unschedule(): void {
		$next = wp_next_scheduled( self::GC_HOOK );
		if ( $next ) {
			wp_unschedule_event( (int) $next, self::GC_HOOK );
		}
	}

	/**
	 * IDs of every post that has a log, whatever its type or status.
	 *
	 * @return int[]
	 */
	public static function post_ids(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Meta lookup across all post types.
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", self::META ) );
		return array_map( 'intval', (array) $ids );
	}

	/**
	 * Every entry on the site, newest first, optionally filtered.
	 *
	 * Pure on its input so it can be tested: pass the per-post lists.
	 *
	 * @param array<int,array<int,array<string,mixed>>> $by_post Post ID => entries.
	 * @param string                                    $user    Only this person's name ('' for all).
	 * @param string                                    $block   Only this block slug ('' for all).
	 * @param string                                    $search  Text to find, case-insensitive, in the before/after
	 *                                                           text (HTML ignored), person, block, field and title.
	 * @param array<int,string>                         $titles  Store ID => title, so a title can be searched.
	 * @return array<int,array<string,mixed>>
	 */
	public static function merge( array $by_post, string $user = '', string $block = '', string $search = '', array $titles = [] ): array {
		$search = trim( $search );
		$all = [];
		foreach ( $by_post as $post_id => $items ) {
			foreach ( $items as $entry ) {
				$entry['store_id'] = (int) ( $entry['store_id'] ?? 0 ) ?: (int) $post_id;
				$entry['block']    = self::slug( (string) ( $entry['block_name'] ?? '' ) );
				if ( '' !== $user && (string) ( $entry['user_name'] ?? '' ) !== $user ) {
					continue;
				}
				if ( '' !== $block && $entry['block'] !== $block ) {
					continue;
				}
				if ( '' !== $search && ! self::matches( $entry, $search, (string) ( $titles[ $entry['store_id'] ] ?? '' ) ) ) {
					continue;
				}
				$all[] = $entry;
			}
		}
		usort(
			$all,
			static function ( $a, $b ) {
				return strcmp( (string) ( $b['time'] ?? '' ), (string) ( $a['time'] ?? '' ) );
			}
		);
		return $all;
	}

	/**
	 * Whether a post's log holds an entry with this ID.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $entry_id Entry ID.
	 */
	public static function has_entry( int $post_id, string $entry_id ): bool {
		if ( '' === $entry_id ) {
			return false;
		}
		$list = get_post_meta( $post_id, self::META, true );
		return is_array( $list ) && in_array( $entry_id, array_column( $list, 'id' ), true );
	}

	/**
	 * Entries for a post, newest first.
	 *
	 * @param int $post_id Post ID.
	 * @return array<int,array<string,mixed>>
	 */
	public static function entries( int $post_id ): array {
		$list = get_post_meta( $post_id, self::META, true );
		return is_array( $list ) ? array_reverse( array_values( $list ) ) : [];
	}

	/**
	 * Build one log entry from the facts of a save.
	 *
	 * Pure: no WordPress calls, so it can be tested on its own.
	 *
	 * @param array<string,mixed> $f Keys: user_id, user_name, time_gmt, entry_id, block_name,
	 *                               block_id, attr, index, subindex, key, store_id, store_type,
	 *                               post_id, before, after, revision_id.
	 * @return array<string,mixed>
	 */
	public static function build_entry( array $f ): array {
		$before = self::clip( self::to_string( $f['before'] ?? '' ) );
		$after  = self::clip( self::to_string( $f['after'] ?? '' ) );

		return [
			'id'          => (string) ( $f['entry_id'] ?? '' ),
			'time'        => (string) ( $f['time_gmt'] ?? '' ),
			'user_id'     => (int) ( $f['user_id'] ?? 0 ),
			'user_name'   => (string) ( $f['user_name'] ?? '' ),
			'user_role'   => (string) ( $f['user_role'] ?? '' ),
			'action'      => 'undo' === ( $f['action'] ?? '' ) ? 'undo' : 'edit',
			'kind'        => 'link' === ( $f['kind'] ?? '' ) ? 'link' : 'text',
			'undo_of'     => (string) ( $f['undo_of'] ?? '' ),
			'block_name'  => (string) ( $f['block_name'] ?? '' ),
			'block_id'    => (string) ( $f['block_id'] ?? '' ),
			'attr'        => (string) ( $f['attr'] ?? '' ),
			'index'       => isset( $f['index'] ) ? (int) $f['index'] : null,
			'subindex'    => isset( $f['subindex'] ) ? (int) $f['subindex'] : null,
			'key'         => isset( $f['key'] ) ? (string) $f['key'] : null,
			'store_id'    => (int) ( $f['store_id'] ?? 0 ),
			'store_type'  => (string) ( $f['store_type'] ?? '' ),
			'post_id'     => (int) ( $f['post_id'] ?? 0 ),
			'before'      => $before[0],
			'after'       => $after[0],
			'truncated'   => $before[1] || $after[1],
			'revision_id' => (int) ( $f['revision_id'] ?? 0 ),
		];
	}

	/**
	 * Whether an entry contains some text, in any of the places a person would look.
	 *
	 * @param array<string,mixed> $entry  Entry.
	 * @param string              $needle Text to find.
	 * @param string              $title  Title of the post that stores it.
	 */
	private static function matches( array $entry, string $needle, string $title ): bool {
		$haystack = [
			strip_tags( (string) ( $entry['before'] ?? '' ) ),
			strip_tags( (string) ( $entry['after'] ?? '' ) ),
			(string) ( $entry['user_name'] ?? '' ),
			(string) ( $entry['block'] ?? '' ),
			(string) ( $entry['attr'] ?? '' ),
			(string) ( $entry['key'] ?? '' ),
			$title,
		];
		foreach ( $haystack as $text ) {
			if ( false !== mb_stripos( html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ), $needle ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Add an entry and drop the oldest past the limit.
	 *
	 * @param array<int,array<string,mixed>> $items Existing entries, oldest first.
	 * @param array<string,mixed>            $entry New entry.
	 * @param int                            $limit Most entries to keep.
	 * @return array<int,array<string,mixed>>
	 */
	public static function append( array $items, array $entry, int $limit ): array {
		$items[] = $entry;
		$limit  = max( 1, $limit );
		if ( count( $items ) > $limit ) {
			$items = array_slice( $items, -$limit );
		}
		return array_values( $items );
	}

	/**
	 * Clip a value to the storage cap.
	 *
	 * @param string $value Value.
	 * @return array{0:string,1:bool} The value and whether it was clipped.
	 */
	public static function clip( string $value ): array {
		/**
		 * Filters the longest value kept whole in the log, in characters.
		 *
		 * @param int $chars Default 20000.
		 */
		$max = max( 100, (int) apply_filters( 'flexa_block_inline_edit_log_value_limit', self::VALUE_LIMIT ) );
		if ( mb_strlen( $value ) <= $max ) {
			return [ $value, false ];
		}
		return [ mb_substr( $value, 0, $max ), true ];
	}

	/**
	 * An entry's time as the site shows dates: site time zone and date format.
	 *
	 * @param string $gmt MySQL-style GMT time stored with the entry.
	 * @return string Formatted time, or the stored text when it cannot be read.
	 */
	public static function display_time( string $gmt ): string {
		$stamp = strtotime( $gmt . ' UTC' );
		if ( false === $stamp ) {
			return $gmt;
		}
		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $stamp );
	}

	/**
	 * The role slug for an entry: the one recorded, else the account's current one.
	 *
	 * @param string          $slug Role slug stored with the entry, may be ''.
	 * @param \WP_User|false $user Current account, if it still exists.
	 * @return string Slug, or ''.
	 */
	private static function role_slug( string $slug, $user ): string {
		if ( '' === $slug && $user instanceof \WP_User && ! empty( $user->roles ) ) {
			$slug = (string) reset( $user->roles );
		}
		return $slug;
	}

	/**
	 * A role's display name ("Administrator"), translated.
	 *
	 * Uses the role recorded when the edit was made, so it stays true after the
	 * person changes role or the account is deleted; entries from before roles
	 * were recorded fall back to the account's current role.
	 *
	 * @param string          $slug Role slug stored with the entry, may be ''.
	 * @param \WP_User|false $user Current account, if it still exists.
	 * @return string Label, or '' when nothing is known.
	 */
	private static function role_label( string $slug, $user ): string {
		$slug = self::role_slug( $slug, $user );
		if ( '' === $slug ) {
			return '';
		}
		$names = wp_roles()->get_names();
		return isset( $names[ $slug ] ) ? translate_user_role( $names[ $slug ] ) : $slug;
	}

	/**
	 * The person's avatar image, if there is one worth showing.
	 *
	 * Empty for an account that no longer exists and when the site has avatars
	 * turned off (Settings → Discussion), so the screen falls back to initials.
	 *
	 * @param int $user_id User ID.
	 * @return string URL, or ''.
	 */
	private static function avatar_url( int $user_id ): string {
		if ( $user_id <= 0 || ! get_userdata( $user_id ) ) {
			return '';
		}
		$url = get_avatar_url( $user_id, [ 'size' => 72 ] );
		return is_string( $url ) ? $url : '';
	}

	/**
	 * A post title as plain text (get_the_title() returns HTML entities).
	 *
	 * @param int $post_id Post ID.
	 */
	private static function plain_title( int $post_id ): string {
		return html_entity_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Block slug from a block name ("flexa/faq" becomes "faq").
	 *
	 * @param string $name Block name.
	 */
	private static function slug( string $name ): string {
		$pos = strrpos( $name, '/' );
		return false === $pos ? $name : substr( $name, $pos + 1 );
	}

	/**
	 * Cast a before/after value to text.
	 *
	 * @param mixed $value Value.
	 */
	private static function to_string( $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}

	// REST.

	/**
	 * Register the read route used by the editor panel.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NS,
			'/inline-edit-log/all',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'handle_all' ],
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
				'args'                => [
					'page'     => [
						'type'    => 'integer',
						'default' => 1,
					],
					'per_page' => [
						'type'    => 'integer',
						'default' => 20,
					],
					'user'     => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'block'    => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'search'   => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);

		register_rest_route(
			self::REST_NS,
			'/inline-edit-log',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'handle_list' ],
				'permission_callback' => [ __CLASS__, 'rest_permission' ],
				'args'                => [
					'postId' => [
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * Only people who can edit the post may read what was changed in it.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool|\WP_Error
	 */
	public static function rest_permission( $request ) {
		$post_id = (int) $request->get_param( 'postId' );
		if ( $post_id > 0 && current_user_can( 'edit_post', $post_id ) ) {
			return true;
		}
		return new \WP_Error(
			'flexa_log_forbidden',
			__( 'You cannot view the edit history of this content.', 'flexa-block' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * List the entries, newest first.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function handle_list( $request ) {
		$post_id = (int) $request->get_param( 'postId' );
		$items   = [];

		foreach ( self::entries( $post_id ) as $entry ) {
			$revision_id = (int) ( $entry['revision_id'] ?? 0 );
			$revision    = $revision_id > 0 ? get_post( $revision_id ) : null;
			$can_view    = $revision instanceof \WP_Post
				&& 'revision' === $revision->post_type
				&& (int) $revision->post_parent === $post_id;

			// The name saved with the entry survives the account being deleted; the
			// current display name is only used when the entry predates it.
			$user = get_userdata( (int) ( $entry['user_id'] ?? 0 ) );

			$entry['block']       = Inline_Editor::slug_for( (string) ( $entry['block_name'] ?? '' ) );
			$entry['role_label']  = self::role_label( (string) ( $entry['user_role'] ?? '' ), $user );
			$entry['role_slug']   = self::role_slug( (string) ( $entry['user_role'] ?? '' ), $user );
			$entry['time_display'] = self::display_time( (string) ( $entry['time'] ?? '' ) );
			$entry['revisionUrl'] = $can_view ? admin_url( 'revision.php?revision=' . $revision_id ) : '';
			if ( '' === (string) ( $entry['user_name'] ?? '' ) && $user ) {
				$entry['user_name'] = $user->display_name;
			}
			$items[] = $entry;
		}

		return rest_ensure_response( [ 'entries' => $items ] );
	}

	/**
	 * Site-wide list for the admin Log tab.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public static function handle_all( $request ) {
		$by_post = [];
		foreach ( self::post_ids() as $post_id ) {
			$list = get_post_meta( $post_id, self::META, true );
			if ( is_array( $list ) && $list ) {
				$by_post[ $post_id ] = self::prune( array_values( $list ), self::cutoff() );
			}
		}

		$everything = self::merge( $by_post );
		$users      = array_values( array_unique( array_filter( array_column( $everything, 'user_name' ) ) ) );
		$blocks     = array_values( array_unique( array_filter( array_column( $everything, 'block' ) ) ) );
		sort( $users );
		sort( $blocks );

		$search = (string) $request->get_param( 'search' );
		$titles = [];
		if ( '' !== trim( $search ) ) {
			foreach ( array_keys( $by_post ) as $store_id ) {
				$titles[ $store_id ] = self::plain_title( (int) $store_id );
			}
		}

		$rows     = self::merge( $by_post, (string) $request->get_param( 'user' ), (string) $request->get_param( 'block' ), $search, $titles );
		$per_page = max( 1, min( 100, (int) $request->get_param( 'per_page' ) ) );
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$total    = count( $rows );
		$items    = [];

		foreach ( array_slice( $rows, ( $page - 1 ) * $per_page, $per_page ) as $entry ) {
			$store_id    = (int) $entry['store_id'];
			$revision_id = (int) ( $entry['revision_id'] ?? 0 );
			$revision    = $revision_id > 0 ? get_post( $revision_id ) : null;
			$viewed      = (int) ( $entry['post_id'] ?? 0 );

			$entry['avatar']      = self::avatar_url( (int) ( $entry['user_id'] ?? 0 ) );
			$role_user            = get_userdata( (int) ( $entry['user_id'] ?? 0 ) );
			$entry['role_label']  = self::role_label( (string) ( $entry['user_role'] ?? '' ), $role_user );
			$entry['role_slug']   = self::role_slug( (string) ( $entry['user_role'] ?? '' ), $role_user );
			$entry['store_title'] = self::plain_title( $store_id );
			$entry['time_display'] = self::display_time( (string) ( $entry['time'] ?? '' ) );
			$entry['editUrl']     = (string) get_edit_post_link( $store_id, 'raw' );
			$entry['viewUrl']     = $viewed > 0 ? (string) get_permalink( $viewed ) : '';
			$entry['revisionUrl'] = ( $revision instanceof \WP_Post && 'revision' === $revision->post_type )
				? admin_url( 'revision.php?revision=' . $revision_id )
				: '';
			$items[]              = $entry;
		}

		return rest_ensure_response(
			[
				'entries'       => $items,
				'total'         => $total,
				'pages'         => (int) max( 1, ceil( $total / $per_page ) ),
				'users'         => $users,
				'blocks'        => $blocks,
				'retentionDays' => self::retention_days(),
			]
		);
	}

	// Editor panel.

	/**
	 * Load the panel for a post the current user can edit.
	 */
	public static function enqueue(): void {
		$post = get_post();
		if ( ! $post instanceof \WP_Post || ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}
		// The history belongs to front-end editing: with it switched off there is
		// nothing for this panel to show.
		if ( ! Inline_Editor::is_enabled() ) {
			return;
		}

		$asset_file = FLEXA_BLOCK_DIR . 'build/editor/inline-edit-log.asset.php';
		$asset      = file_exists( $asset_file ) ? require $asset_file : null;
		if ( ! is_array( $asset ) ) {
			return;
		}

		wp_enqueue_script(
			self::SCRIPT,
			FLEXA_BLOCK_URL . 'build/editor/inline-edit-log.js',
			is_array( $asset['dependencies'] ?? null ) ? $asset['dependencies'] : [],
			is_string( $asset['version'] ?? null ) ? $asset['version'] : FLEXA_BLOCK_VER,
			true
		);
		wp_set_script_translations( self::SCRIPT, 'flexa-block', FLEXA_BLOCK_DIR . 'languages' );

		wp_localize_script(
			self::SCRIPT,
			'flexaBlockInlineEditLog',
			[
				'postId' => (int) $post->ID,
				'path'   => '/' . self::REST_NS . '/inline-edit-log',
			]
		);
	}
}
