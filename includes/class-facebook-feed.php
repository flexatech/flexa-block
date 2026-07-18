<?php
declare(strict_types=1);
/**
 * Facebook Feed — Graph API fetch + normalise, and the editor preview REST route.
 *
 * The Facebook Feed block's render.php (front end) and its editor preview both
 * need the same normalised posts. Facebook's Graph API can't be called from the
 * browser (the Page access token must stay server-side), so:
 *   1. `posts()` calls the Graph API (via the cached Remote_Feed helper) and
 *      normalises each post to a plain array — used by render.php, and
 *   2. a REST route (`flexa-block/v1/facebook-feed-preview`, editor-capability
 *      gated) returns those posts as JSON so edit.tsx can show the REAL feed
 *      while the user styles the block.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Facebook Graph API feed helper + editor preview endpoint.
 */
final class Facebook_Feed {

	const REST_NS     = 'flexa-block/v1';
	const GRAPH_BASE  = 'https://graph.facebook.com/v19.0/';

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Fetch a Page's posts and normalise them to plain arrays.
	 *
	 * @param string $token     Page access token.
	 * @param string $page_id   Facebook Page id (or "me").
	 * @param int    $count     Max posts (clamped 1–50).
	 * @param bool   $newest_first Keep Graph order (newest first) or reverse it.
	 * @param int    $cache_min Cache lifetime in minutes (clamped 5–1440).
	 * @return array<int, array<string, mixed>> Posts: id, message, permalink, timestamp, image, likes, comments, shares, page_name, page_avatar.
	 */
	public static function posts( string $token, string $page_id, int $count, bool $newest_first, int $cache_min ): array {
		$token   = trim( $token );
		$page_id = trim( '' !== $page_id ? $page_id : 'me' );
		if ( '' === $token ) {
			return [];
		}
		$count     = max( 1, min( 50, $count ) );
		$cache_min = max( 5, min( 1440, $cache_min ) );

		// Page name + avatar (one small lookup, shared across posts).
		$page = self::fetch_page( $token, $page_id, $cache_min );

		$fields = 'id,message,created_time,permalink_url,full_picture,shares,reactions.summary(true),comments.summary(true),attachments{media_type,subattachments}';
		$url    = self::GRAPH_BASE . rawurlencode( $page_id ) . '/posts?' . http_build_query(
			[
				'fields'       => $fields,
				'limit'        => $count,
				'access_token' => $token,
			]
		);

		$cache_key = 'flexa_fb_posts_' . md5( $page_id . '|' . $count . '|' . $token );
		$data      = Remote_Feed::get_json( $url, $cache_key, $cache_min );
		if ( is_wp_error( $data ) || empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return [];
		}

		$raw = $data['data'];
		if ( ! $newest_first ) {
			$raw = array_reverse( $raw );
		}

		$out = [];
		foreach ( $raw as $post ) {
			if ( ! is_array( $post ) ) {
				continue;
			}
			// Media type from the first attachment: video, a multi-photo album
			// (subattachments), or a plain image — normalised to the badge vocabulary.
			$attach     = $post['attachments']['data'][0] ?? [];
			$att_type   = (string) ( $attach['media_type'] ?? '' );
			$has_sub    = ! empty( $attach['subattachments']['data'] );
			$media_type = 'video' === $att_type ? 'video' : ( ( $has_sub || 'album' === $att_type ) ? 'album' : 'image' );

			$out[] = [
				'id'          => (string) ( $post['id'] ?? '' ),
				'message'     => (string) ( $post['message'] ?? '' ),
				'permalink'   => (string) ( $post['permalink_url'] ?? '' ),
				'timestamp'   => isset( $post['created_time'] ) ? (int) strtotime( (string) $post['created_time'] ) : 0,
				'image'       => (string) ( $post['full_picture'] ?? '' ),
				'type'        => $media_type,
				'likes'       => (int) ( $post['reactions']['summary']['total_count'] ?? 0 ),
				'comments'    => (int) ( $post['comments']['summary']['total_count'] ?? 0 ),
				'shares'      => (int) ( $post['shares']['count'] ?? 0 ),
				'page_name'   => $page['name'],
				'page_avatar' => $page['avatar'],
			];
		}
		return $out;
	}

	/**
	 * Fetch the Page's display name + avatar URL (cached).
	 *
	 * @param string $token     Page access token.
	 * @param string $page_id   Page id.
	 * @param int    $cache_min Cache lifetime in minutes.
	 * @return array{name: string, avatar: string}
	 */
	private static function fetch_page( string $token, string $page_id, int $cache_min ): array {
		$url = self::GRAPH_BASE . rawurlencode( $page_id ) . '?' . http_build_query(
			[
				'fields'       => 'name,picture.type(square)',
				'access_token' => $token,
			]
		);
		$cache_key = 'flexa_fb_page_' . md5( $page_id . '|' . $token );
		$data      = Remote_Feed::get_json( $url, $cache_key, $cache_min );
		if ( is_wp_error( $data ) ) {
			return [ 'name' => '', 'avatar' => '' ];
		}
		return [
			'name'   => (string) ( $data['name'] ?? '' ),
			'avatar' => (string) ( $data['picture']['data']['url'] ?? '' ),
		];
	}

	/**
	 * Register the editor preview route.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NS,
			'/facebook-feed-preview',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'rest_preview' ],
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => [
					// The token is NOT a parameter — it is read server-side from the
					// admin-only Feed_Tokens store so it never reaches the browser.
					'pageId' => [ 'type' => 'string', 'default' => 'me', 'sanitize_callback' => 'sanitize_text_field' ],
					'count'  => [ 'type' => 'integer', 'default' => 6, 'sanitize_callback' => 'absint' ],
					'sort'   => [ 'type' => 'string', 'default' => 'newest', 'sanitize_callback' => 'sanitize_key' ],
					'cache'  => [ 'type' => 'integer', 'default' => 30, 'sanitize_callback' => 'absint' ],
				],
			]
		);
	}

	/**
	 * Return normalised posts as JSON for the editor preview.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public static function rest_preview( $request ) {
		$token = Feed_Tokens::get( 'facebook' );
		if ( '' === $token ) {
			// Not configured — no token to fetch with. The editor shows demo data.
			return rest_ensure_response( [ 'items' => [], 'configured' => false ] );
		}
		$posts = self::posts(
			$token,
			(string) $request->get_param( 'pageId' ),
			(int) $request->get_param( 'count' ),
			'oldest' !== (string) $request->get_param( 'sort' ),
			(int) $request->get_param( 'cache' )
		);

		$date_format = trim( (string) get_option( 'date_format' ) . ' ' . (string) get_option( 'time_format' ) );
		$out         = array_map(
			static function ( $post ) use ( $date_format ) {
				return [
					'id'         => $post['id'],
					'message'    => $post['message'],
					'permalink'  => $post['permalink'],
					'date'       => $post['timestamp'] > 0 ? date_i18n( $date_format, $post['timestamp'] ) : '',
					'image'      => $post['image'],
					'type'       => $post['type'],
					'likes'      => $post['likes'],
					'comments'   => $post['comments'],
					'shares'     => $post['shares'],
					'pageName'   => $post['page_name'],
					'pageAvatar' => $post['page_avatar'],
				];
			},
			$posts
		);

		return rest_ensure_response( [ 'items' => $out ] );
	}
}
