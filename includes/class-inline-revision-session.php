<?php
/**
 * Groups front-end inline edits into a single revision session.
 *
 * @package Flexa\Block
 */

declare(strict_types=1);

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keep one immutable "before inline editing" revision for a short edit session.
 */
final class Inline_Revision_Session {

	const TRANSIENT_PREFIX = 'flexa_ie_rev_';
	const REVISION_META    = '_flexa_block_inline_revision_session';
	const DEFAULT_WINDOW   = 600;

	/**
	 * Update a post while grouping its automatic revision into the active session.
	 *
	 * A baseline revision is created before the first write. While that baseline
	 * is active, core's automatic post-update revision is suppressed only for this
	 * post and only for the duration of this call. The baseline is deliberately
	 * never updated: restoring it must return to the content from before the
	 * front-end editing session began.
	 *
	 * @param \WP_Post            $post    Post being edited.
	 * @param array<string,mixed> $postarr Data passed to wp_update_post().
	 * @return int|\WP_Error
	 */
	public static function update_post( \WP_Post $post, array $postarr ) {
		$post_id   = (int) $post->ID;
		$post_type = (string) $post->post_type;
		$session   = self::begin( $post_id );

		// If core could not create a baseline (for example revisions are disabled),
		// leave its normal update behaviour alone rather than suppressing anything.
		if ( null === $session ) {
			return wp_update_post( $postarr, true );
		}

		$suppress = static function ( $number, $candidate ) use ( $post_id ) {
			return $candidate instanceof \WP_Post && (int) $candidate->ID === $post_id
				? 0
				: $number;
		};

		// The post-type-specific filter runs after the general one and can override
		// it, so install the same narrow guard on both hooks.
		add_filter( 'wp_revisions_to_keep', $suppress, PHP_INT_MAX, 2 );
		add_filter( "wp_{$post_type}_revisions_to_keep", $suppress, PHP_INT_MAX, 2 );

		try {
			$result = wp_update_post( $postarr, true );
		} finally {
			remove_filter( 'wp_revisions_to_keep', $suppress, PHP_INT_MAX );
			remove_filter( "wp_{$post_type}_revisions_to_keep", $suppress, PHP_INT_MAX );
		}

		if ( ! is_wp_error( $result ) ) {
			self::touch( $post_id, $session );
		}

		return $result;
	}

	/**
	 * The baseline revision of the current user's active session on a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int Revision ID, or 0 when there is no active session.
	 */
	public static function baseline_id( int $post_id ): int {
		$stored = get_transient( self::key( $post_id, get_current_user_id() ) );
		return is_array( $stored ) ? (int) ( $stored['revision_id'] ?? 0 ) : 0;
	}

	/**
	 * Reuse an active baseline or create a new one from the current content.
	 *
	 * @param int $post_id Post ID.
	 * @return array{revision_id:int,started_at:int,last_activity:int,window:int}|null
	 */
	private static function begin( int $post_id ): ?array {
		$user_id = get_current_user_id();
		$window  = self::window( $post_id, $user_id );
		$key     = self::key( $post_id, $user_id );
		$now     = time();
		$stored  = $window > 0 ? get_transient( $key ) : false;

		if ( is_array( $stored ) && self::is_valid( $stored, $post_id, $window, $now ) ) {
			return [
				'revision_id'   => (int) $stored['revision_id'],
				'started_at'    => (int) $stored['started_at'],
				'last_activity' => (int) $stored['last_activity'],
				'window'        => $window,
			];
		}

		if ( false !== $stored ) {
			delete_transient( $key );
		}

		// Core may skip a snapshot when the latest revision already equals the
		// current post. A new inline session still needs its own restore point.
		$force = static function ( $check, $latest, $candidate ) use ( $post_id ) {
			return $candidate instanceof \WP_Post && (int) $candidate->ID === $post_id
				? false
				: $check;
		};

		add_filter( 'wp_save_post_revision_check_for_changes', $force, PHP_INT_MAX, 3 );
		try {
			$revision_id = wp_save_post_revision( $post_id );
		} finally {
			remove_filter( 'wp_save_post_revision_check_for_changes', $force, PHP_INT_MAX );
		}

		if ( is_wp_error( $revision_id ) || (int) $revision_id <= 0 ) {
			return null;
		}

		$session = [
			'revision_id'   => (int) $revision_id,
			'started_at'    => $now,
			'last_activity' => $now,
			'window'        => $window,
		];

		// add_metadata(), not add_post_meta(): that redirects a revision ID to its parent.
		add_metadata(
			'post',
			(int) $revision_id,
			self::REVISION_META,
			[
				'source'     => 'front-end-inline',
				'post_id'    => $post_id,
				'user_id'    => $user_id,
				'started_at' => $now,
			],
			true
		);

		if ( $window > 0 ) {
			set_transient( $key, $session, $window );
		}

		return $session;
	}

	/**
	 * Extend a successful session using a sliding inactivity window.
	 *
	 * @param int                                                                $post_id Post ID.
	 * @param array{revision_id:int,started_at:int,last_activity:int,window:int} $session Session data.
	 */
	private static function touch( int $post_id, array $session ): void {
		$user_id = get_current_user_id();
		$window  = self::window( $post_id, $user_id );

		if ( $window <= 0 ) {
			return;
		}

		$session['last_activity'] = time();
		$session['window']        = $window;
		set_transient( self::key( $post_id, $user_id ), $session, $window );
	}

	/**
	 * Whether stored session data still points to a usable baseline revision.
	 *
	 * @param mixed $stored  Transient value.
	 * @param int   $post_id Post ID.
	 * @param int   $window  Session window in seconds.
	 * @param int   $now     Current Unix timestamp.
	 * @return bool
	 */
	private static function is_valid( $stored, int $post_id, int $window, int $now ): bool {
		if ( ! is_array( $stored )
			|| empty( $stored['revision_id'] )
			|| empty( $stored['started_at'] )
			|| empty( $stored['last_activity'] )
			|| $now - (int) $stored['last_activity'] > $window ) {
			return false;
		}

		$revision = get_post( (int) $stored['revision_id'] );

		return $revision instanceof \WP_Post
			&& 'revision' === $revision->post_type
			&& (int) $revision->post_parent === $post_id;
	}

	/**
	 * Session inactivity window.
	 *
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID.
	 * @return int Seconds. Zero disables grouping.
	 */
	private static function window( int $post_id, int $user_id ): int {
		/**
		 * Filters the front-end inline revision session inactivity window.
		 *
		 * @param int $seconds Session window in seconds. Default 600 (10 minutes).
		 * @param int $post_id Post ID being edited.
		 * @param int $user_id Current user ID.
		 */
		return max( 0, (int) apply_filters( 'flexa_block_inline_revision_session_window', self::DEFAULT_WINDOW, $post_id, $user_id ) );
	}

	/**
	 * Per-post, per-user transient key.
	 *
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function key( int $post_id, int $user_id ): string {
		return self::TRANSIENT_PREFIX . $post_id . '_' . $user_id;
	}
}
