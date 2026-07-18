<?php
declare(strict_types=1);
/**
 * Instagram Feed — Graph API fetch + normalise, and the editor preview REST route.
 *
 * Mirrors Facebook_Feed for the Instagram Basic Display / Graph `me/media`
 * endpoint: the access token stays server-side, so `media()` fetches + caches the
 * media list (via Remote_Feed) and normalises each entry, and a REST route
 * (`flexa-block/v1/instagram-feed-preview`, editor-capability gated) returns them
 * as JSON so edit.tsx can show the real feed while the user styles the block.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Instagram Graph API feed helper + editor preview endpoint.
 */
final class Instagram_Feed {

	const REST_NS    = 'flexa-block/v1';
	const GRAPH_BASE = 'https://graph.instagram.com/';

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Fetch the account's media and normalise them to plain arrays.
	 *
	 * @param string $token     Instagram access token.
	 * @param int    $count     Max images (clamped 1–50).
	 * @param bool   $newest_first Keep API order (newest first) or reverse it.
	 * @param int    $cache_min Cache lifetime in minutes (clamped 5–1440).
	 * @return array<int, array<string, mixed>> Media: id, caption, permalink, timestamp, image, type, username.
	 */
	public static function media( string $token, int $count, bool $newest_first, int $cache_min ): array {
		$token = trim( $token );
		if ( '' === $token ) {
			return [];
		}
		$count     = max( 1, min( 50, $count ) );
		$cache_min = max( 5, min( 1440, $cache_min ) );

		$fields = 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,username';
		$url    = self::GRAPH_BASE . 'me/media?' . http_build_query(
			[
				'fields'       => $fields,
				'limit'        => $count,
				'access_token' => $token,
			]
		);

		$cache_key = 'flexa_ig_media_' . md5( $count . '|' . $token );
		$data      = Remote_Feed::get_json( $url, $cache_key, $cache_min );
		if ( is_wp_error( $data ) || empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return [];
		}

		$raw = $data['data'];
		if ( ! $newest_first ) {
			$raw = array_reverse( $raw );
		}

		$out = [];
		foreach ( $raw as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$raw_type = (string) ( $item['media_type'] ?? 'IMAGE' );
			// Videos expose a still under thumbnail_url; images/albums use media_url.
			$image = 'VIDEO' === $raw_type ? (string) ( $item['thumbnail_url'] ?? '' ) : (string) ( $item['media_url'] ?? '' );
			// Normalise to the shared badge vocabulary.
			$type = 'VIDEO' === $raw_type ? 'video' : ( 'CAROUSEL_ALBUM' === $raw_type ? 'album' : 'image' );
			$out[] = [
				'id'        => (string) ( $item['id'] ?? '' ),
				'caption'   => (string) ( $item['caption'] ?? '' ),
				'permalink' => (string) ( $item['permalink'] ?? '' ),
				'timestamp' => isset( $item['timestamp'] ) ? (int) strtotime( (string) $item['timestamp'] ) : 0,
				'image'     => $image,
				'type'      => $type,
				'username'  => (string) ( $item['username'] ?? '' ),
			];
		}
		return $out;
	}

	/**
	 * Register the editor preview route.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NS,
			'/instagram-feed-preview',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'rest_preview' ],
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => [
					// The token is NOT a parameter — it is read server-side from the
					// admin-only Feed_Tokens store so it never reaches the browser.
					'count' => [ 'type' => 'integer', 'default' => 8, 'sanitize_callback' => 'absint' ],
					'sort'  => [ 'type' => 'string', 'default' => 'newest', 'sanitize_callback' => 'sanitize_key' ],
					'cache' => [ 'type' => 'integer', 'default' => 30, 'sanitize_callback' => 'absint' ],
				],
			]
		);
	}

	/**
	 * Return normalised media as JSON for the editor preview.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public static function rest_preview( $request ) {
		$token = Feed_Tokens::get( 'instagram' );
		if ( '' === $token ) {
			// Not configured — no token to fetch with. The editor shows demo data.
			return rest_ensure_response( [ 'items' => [], 'configured' => false ] );
		}
		$media = self::media(
			$token,
			(int) $request->get_param( 'count' ),
			'oldest' !== (string) $request->get_param( 'sort' ),
			(int) $request->get_param( 'cache' )
		);

		$date_format = trim( (string) get_option( 'date_format' ) . ' ' . (string) get_option( 'time_format' ) );
		$out         = array_map(
			static function ( $item ) use ( $date_format ) {
				return [
					'id'        => $item['id'],
					'caption'   => $item['caption'],
					'permalink' => $item['permalink'],
					'date'      => $item['timestamp'] > 0 ? date_i18n( $date_format, $item['timestamp'] ) : '',
					'image'     => $item['image'],
					'type'      => $item['type'],
					'username'  => $item['username'],
				];
			},
			$media
		);

		return rest_ensure_response( [ 'items' => $out ] );
	}
}
