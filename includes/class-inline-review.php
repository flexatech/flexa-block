<?php
/**
 * The admin's review queue for front-end changes to published pages.
 *
 * @package Flexa\Block
 */

declare(strict_types=1);

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lists the review copies front-end editing made, and records who approved or
 * discarded them.
 *
 * Applying itself stays with Working_Copy (one way to put a copy live, with its
 * permission, lock and conflict checks). This class adds the queue the Review
 * tab shows, a way to discard a copy, and the stamp each front-end edit gets in
 * the edit log once someone decides about it.
 */
final class Inline_Review {

	const REST_NS = 'flexa-block/v1';

	/**
	 * Hook up the routes and the decision stamps.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
		// Applied from anywhere (the Review tab, the editor panel): stamp and
		// keep the edits on the page before the copy is deleted.
		add_action( 'flexa_block_working_copy_applied', [ __CLASS__, 'on_applied' ], 10, 2 );

		// Near-live: WordPress's own Heartbeat (about every 15 s on the Review tab,
		// slower elsewhere) asks whether the queue changed. No extra connection,
		// no outside service.
		add_filter( 'heartbeat_received', [ __CLASS__, 'heartbeat' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_badge' ] );
	}

	/**
	 * One string that changes whenever the queue does (a copy added, changed,
	 * applied or discarded).
	 *
	 * @param list<\WP_Post>|null $copies Pending copies, if already read.
	 */
	public static function signature( ?array $copies = null ): string {
		$parts = [];
		foreach ( null === $copies ? self::pending() : $copies as $copy ) {
			$parts[] = $copy->ID . ':' . $copy->post_modified_gmt;
		}
		sort( $parts );
		return md5( implode( '|', $parts ) );
	}

	/**
	 * Answer the Heartbeat: how many copies wait, and the queue's signature.
	 *
	 * @param array<string,mixed> $response Heartbeat response.
	 * @param array<string,mixed> $data     What the page sent.
	 * @return array<string,mixed>
	 */
	public static function heartbeat( $response, $data ) {
		if ( ! is_array( $response ) || ! is_array( $data ) || empty( $data['flexa_review'] ) || ! current_user_can( 'manage_options' ) ) {
			return $response;
		}
		$copies                   = self::pending();
		$response['flexa_review'] = [
			'count' => count( $copies ),
			'stamp' => self::signature( $copies ),
		];
		return $response;
	}

	/**
	 * Keep the menu's count current on every admin screen, from the Heartbeat.
	 *
	 * A few lines of script on the Heartbeat WordPress already runs; only for
	 * people who see the queue, and only while review is on or anything waits.
	 */
	public static function enqueue_badge(): void {
		if ( ! current_user_can( 'manage_options' ) || ( ! Inline_Editor::review_enabled() && 0 === self::count() ) ) {
			return;
		}
		wp_enqueue_script( 'heartbeat' );
		wp_add_inline_script(
			'heartbeat',
			"jQuery(function($){\n" .
			"\t$(document).on('heartbeat-send.flexaReview',function(e,data){data.flexa_review=1;});\n" .
			"\t$(document).on('heartbeat-tick.flexaReview',function(e,data){\n" .
			"\t\tvar r=data&&data.flexa_review;if(!r){return;}\n" .
			"\t\tvar name=$('#toplevel_page_flexa-block .wp-menu-name');\n" .
			"\t\tname.find('.awaiting-mod').remove();\n" .
			"\t\tif(r.count>0){name.append(' <span class=\"awaiting-mod count-'+r.count+'\"><span class=\"pending-count\">'+r.count+'</span></span>');}\n" .
			"\t\t$(document).trigger('flexa-review-tick',[r]);\n" .
			"\t});\n" .
			'});'
		);
	}

	/**
	 * Load the working-copy class on demand.
	 */
	private static function load(): bool {
		if ( ! class_exists( '\Flexa\Block\MCP\Working_Copy' ) && defined( 'FLEXA_BLOCK_DIR' ) ) {
			require_once FLEXA_BLOCK_DIR . 'includes/mcp/class-working-copy.php';
		}
		return class_exists( '\Flexa\Block\MCP\Working_Copy' );
	}

	/**
	 * Open review copies (newest change first).
	 *
	 * @return list<\WP_Post>
	 */
	public static function pending(): array {
		$copies = get_posts(
			[
				'post_type'      => 'any',
				'post_status'    => [ 'draft', 'pending' ],
				'posts_per_page' => 100,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- The marker is the only way to tell a review copy apart; the queue is small.
				'meta_key'       => Inline_Editor::REVIEW_META,
			]
		);

		return array_values(
			array_filter(
				$copies,
				static function ( $copy ) {
					$of = (int) get_post_meta( $copy->ID, '_flexa_working_copy_of', true );
					return $of > 0 && get_post( $of ) instanceof \WP_Post && 'trash' !== get_post_status( $of );
				}
			)
		);
	}

	/**
	 * How many copies wait for review (for the badge).
	 */
	public static function count(): int {
		return count( self::pending() );
	}

	/**
	 * Stamp a set of log entries with a decision.
	 *
	 * @param string $decision "approved" or "discarded".
	 * @return array<string,mixed>
	 */
	private static function stamp( string $decision ): array {
		$user = wp_get_current_user();
		return [
			'review'        => $decision,
			'reviewed_by'   => (int) $user->ID,
			'reviewed_name' => (string) $user->display_name,
			'reviewed_role' => (string) ( $user->roles ? reset( $user->roles ) : '' ),
			'reviewed_at'   => gmdate( 'Y-m-d H:i:s' ),
		];
	}

	/**
	 * A copy was applied: its edits move to the page, marked approved.
	 *
	 * @param int $original_id Original post.
	 * @param int $copy_id     Copy, about to be deleted.
	 */
	public static function on_applied( $original_id, $copy_id ): void {
		Inline_Edit_Log::move( (int) $original_id, (int) $copy_id, self::stamp( 'approved' ) );
	}

	/**
	 * Register the routes.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NS,
			'/inline-review',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'handle_list' ],
				'permission_callback' => static function () {
					return current_user_can( 'manage_options' );
				},
			]
		);

		register_rest_route(
			self::REST_NS,
			'/inline-review/(?P<id>\d+)/discard',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'handle_discard' ],
				'permission_callback' => static function ( \WP_REST_Request $request ) {
					$id = (int) $request['id'];
					return current_user_can( 'manage_options' ) && current_user_can( 'delete_post', $id );
				},
			]
		);
	}

	/**
	 * The queue: each copy with the page it is for, who changed it, and how.
	 *
	 * @return \WP_REST_Response
	 */
	public static function handle_list() {
		if ( ! self::load() ) {
			return rest_ensure_response( [ 'items' => [] ] );
		}

		$items = [];
		foreach ( self::pending() as $copy ) {
			$original_id = (int) get_post_meta( $copy->ID, '_flexa_working_copy_of', true );
			$original    = get_post( $original_id );
			if ( ! $original instanceof \WP_Post ) {
				continue;
			}

			$entries = Inline_Edit_Log::entries( (int) $copy->ID );
			$people  = [];
			$who     = static function ( array $entry ): array {
				$uid  = (int) ( $entry['user_id'] ?? 0 );
				$user = $uid > 0 ? get_userdata( $uid ) : false;
				$role = (string) ( $entry['user_role'] ?? '' );
				return [
					'name'      => (string) ( $entry['user_name'] ?? '' ),
					'avatar'    => $user ? (string) get_avatar_url( $uid, [ 'size' => 64 ] ) : '',
					'roleLabel' => Inline_Edit_Log::role_label( $role, $user ),
					'roleSlug'  => Inline_Edit_Log::role_slug( $role, $user ),
				];
			};
			foreach ( $entries as $entry ) {
				$uid = (int) ( $entry['user_id'] ?? 0 );
				if ( $uid > 0 && ! isset( $people[ $uid ] ) ) {
					$people[ $uid ] = $who( $entry );
				}
			}

			$list = [];
			foreach ( array_reverse( $entries ) as $entry ) {
				$list[] = [
					'id'           => (string) ( $entry['id'] ?? '' ),
					'time_display' => Inline_Edit_Log::display_time( (string) ( $entry['time'] ?? '' ) ),
					'user_name'    => (string) ( $entry['user_name'] ?? '' ),
					'person'       => $who( $entry ),
					'block'        => Inline_Editor::slug_for( (string) ( $entry['block_name'] ?? '' ) ),
					'attr'         => (string) ( $entry['attr'] ?? '' ),
					'kind'         => (string) ( $entry['kind'] ?? 'text' ),
					'action'       => (string) ( $entry['action'] ?? 'edit' ),
					'before'       => (string) ( $entry['before'] ?? '' ),
					'after'        => (string) ( $entry['after'] ?? '' ),
				];
			}

			$reason  = Inline_Editor::review_block_reason( $copy );
			$items[] = [
				'copyId'       => (int) $copy->ID,
				'title'        => html_entity_decode( wp_strip_all_tags( get_the_title( $original ) ), ENT_QUOTES, 'UTF-8' ),
				'viewLink'     => (string) get_permalink( $original ),
				'copyEditLink' => (string) get_edit_post_link( $copy->ID, 'raw' ),
				'modified'     => Inline_Edit_Log::display_time( (string) $copy->post_modified_gmt ),
				'people'       => array_values( $people ),
				'entries'      => $list,
				'conflicted'   => \Flexa\Block\MCP\Working_Copy::conflicted( (int) $copy->ID ),
				'canApply'     => '' === $reason && current_user_can( 'edit_post', $copy->ID ) && current_user_can( 'edit_post', $original_id ),
				'reason'       => $reason,
				'applyPath'    => '/' . self::REST_NS . '/working-copy/' . (int) $copy->ID . '/apply',
				'seen'         => \Flexa\Block\MCP\Working_Copy::fingerprint( $copy ),
				'discardPath'  => '/' . self::REST_NS . '/inline-review/' . (int) $copy->ID . '/discard',
			];
		}

		return rest_ensure_response(
			[
				'items' => $items,
				'stamp' => self::signature(),
			]
		);
	}

	/**
	 * Discard a review copy: its edits stay in the page's log, marked discarded,
	 * and the copy goes to the trash (where it can still be restored).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function handle_discard( \WP_REST_Request $request ) {
		$copy_id = (int) $request['id'];
		$copy    = get_post( $copy_id );
		$of      = (int) get_post_meta( $copy_id, '_flexa_working_copy_of', true );

		if ( ! $copy instanceof \WP_Post || ! get_post_meta( $copy_id, Inline_Editor::REVIEW_META, true ) || $of < 1 ) {
			return new \WP_Error( 'flexa_review_missing', __( 'That copy is not waiting for review any more.', 'flexa-block' ), [ 'status' => 404 ] );
		}

		Inline_Edit_Log::move( $of, $copy_id, self::stamp( 'discarded' ) );
		wp_trash_post( $copy_id );

		return rest_ensure_response( [ 'discarded' => $copy_id ] );
	}
}
