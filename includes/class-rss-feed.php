<?php
declare(strict_types=1);
/**
 * RSS Feed — shared feed fetch + normalise, and the editor preview REST route.
 *
 * The RSS block's render.php (front end) and its editor preview both need the
 * same feed items. An external feed can't be fetched from the browser (CORS), so:
 *   1. `items()` fetches + caches the feed with WordPress `fetch_feed()` (SimplePie)
 *      and normalises each entry to a plain array — used by render.php, and
 *   2. a REST route (`flexa-block/v1/rss-preview`, editor-capability gated) returns
 *      those normalised items as JSON so edit.tsx can show the REAL feed live while
 *      the user styles the block.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feed fetch helper + editor preview endpoint.
 */
final class Rss_Feed {

	const REST_NS = 'flexa-block/v1';

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Fetch a feed and normalise its entries to plain arrays (items only).
	 *
	 * @param string $url          Feed URL.
	 * @param int    $count        Max items (clamped 1–100).
	 * @param bool   $newest_first Keep feed order (newest first) or reverse it.
	 * @param int    $cache_min    Cache lifetime in minutes (clamped 5–1440).
	 * @return array<int, array<string, mixed>> Items: title, link, timestamp, author, source, image, description.
	 */
	public static function items( string $url, int $count, bool $newest_first, int $cache_min ): array {
		return self::query( $url, $count, $newest_first, $cache_min )['items'];
	}

	/**
	 * Fetch a feed: normalised entries plus the feed's TOTAL item count (so the
	 * editor can cap the "items to show" slider at what the feed actually offers).
	 *
	 * @param string $url          Feed URL.
	 * @param int    $count        Max items to return (clamped 1–100).
	 * @param bool   $newest_first Keep feed order (newest first) or reverse it.
	 * @param int    $cache_min    Cache lifetime in minutes (clamped 5–1440).
	 * @return array{items: array<int, array<string, mixed>>, total: int}
	 */
	public static function query( string $url, int $count, bool $newest_first, int $cache_min ): array {
		$url = esc_url_raw( trim( $url ) );
		if ( '' === $url || ! preg_match( '#^https?://#i', $url ) ) {
			return [ 'items' => [], 'total' => 0 ];
		}
		$count     = max( 1, min( 100, $count ) );
		$cache_min = max( 5, min( 1440, $cache_min ) );

		$cache_cb = static function () use ( $cache_min ) {
			return $cache_min * MINUTE_IN_SECONDS;
		};
		add_filter( 'wp_feed_cache_transient_lifetime', $cache_cb );
		$feed = fetch_feed( $url );
		remove_filter( 'wp_feed_cache_transient_lifetime', $cache_cb );

		if ( is_wp_error( $feed ) ) {
			return [ 'items' => [], 'total' => 0 ];
		}

		$source   = (string) $feed->get_title();
		$total    = (int) $feed->get_item_quantity();
		$quantity = $feed->get_item_quantity( $count );
		$raw      = $feed->get_items( 0, $quantity );
		if ( ! $newest_first ) {
			$raw = array_reverse( $raw );
		}

		$out = [];
		foreach ( $raw as $item ) {
			// Thumbnail from the item's enclosure / media, if it points at an image.
			$img       = '';
			$enclosure = $item->get_enclosure();
			if ( $enclosure ) {
				$candidate = (string) ( $enclosure->get_thumbnail() ? $enclosure->get_thumbnail() : $enclosure->get_link() );
				$type      = (string) $enclosure->get_type();
				if ( '' !== $candidate && ( 0 === strpos( $type, 'image' ) || preg_match( '#\.(jpe?g|png|gif|webp|avif)(\?.*)?$#i', $candidate ) ) ) {
					$img = esc_url_raw( $candidate );
				}
			}

			$author = $item->get_author();
			$name   = $author ? trim( (string) $author->get_name() ) : '';

			$out[] = [
				'title'       => (string) $item->get_title(),
				'link'        => (string) $item->get_permalink(),
				'timestamp'   => (int) $item->get_date( 'U' ),
				'author'      => $name,
				'source'      => $source,
				'image'       => $img,
				'description' => wp_strip_all_tags( html_entity_decode( (string) $item->get_description(), ENT_QUOTES, 'UTF-8' ) ),
			];
		}
		return [ 'items' => $out, 'total' => $total ];
	}

	/**
	 * Register the editor preview route.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NS,
			'/rss-preview',
			[
				'methods'             => 'GET',
				'callback'            => [ __CLASS__, 'rest_preview' ],
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => [
					'url'   => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					],
					'items' => [
						'type'              => 'integer',
						'default'           => 5,
						'sanitize_callback' => 'absint',
					],
					'sort'  => [
						'type'              => 'string',
						'default'           => 'newest',
						'sanitize_callback' => 'sanitize_key',
					],
					'cache' => [
						'type'              => 'integer',
						'default'           => 60,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * Return normalised feed items as JSON for the editor preview.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public static function rest_preview( $request ) {
		$result = self::query(
			(string) $request->get_param( 'url' ),
			(int) $request->get_param( 'items' ),
			'oldest' !== (string) $request->get_param( 'sort' ),
			(int) $request->get_param( 'cache' )
		);
		$items = $result['items'];

		$date_format = (string) get_option( 'date_format' );
		$out         = array_map(
			static function ( $item ) use ( $date_format ) {
				return [
					'title'   => $item['title'],
					'link'    => $item['link'],
					'date'    => $item['timestamp'] > 0 ? date_i18n( $date_format, $item['timestamp'] ) : '',
					'author'  => $item['author'],
					'source'  => $item['source'],
					'image'   => $item['image'],
					'excerpt' => $item['description'],
				];
			},
			$items
		);

		return rest_ensure_response( [ 'items' => $out, 'total' => (int) $result['total'] ] );
	}
}
