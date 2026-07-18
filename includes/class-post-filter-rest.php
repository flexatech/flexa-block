<?php
declare(strict_types=1);
/**
 * Post Filter REST — re-render one Post Grid for a visitor's search/filter.
 *
 * The filter bar could simply re-fetch the whole page and swap the grid out of
 * it (that is what pagination did before this route existed), but on a heavy page
 * that means re-rendering the header, menus and every other block to update one
 * grid. This route renders ONLY the grid.
 *
 * The request supplies a post ID, a blockId and the visitor's filters — nothing
 * else. Every query setting is re-read from the block as it was SAVED
 * (Block_Locator), and the markup comes from the same Post_Query the front end
 * renders through, so a filtered URL loaded cold produces byte-identical output.
 *
 * The route is public on purpose: it renders published posts that are already
 * public, and a visitor filtering a blog is not a state change. There is nothing
 * to authenticate and nothing to protect with a nonce.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST endpoint backing the post-filter blocks.
 */
final class Post_Filter_REST {

	const REST_NS = 'flexa-block/v1';

	/**
	 * How long a rendered grid is reused for.
	 *
	 * A visitor typing into the search box fires one WP_Query per pause (300ms), and
	 * everyone typing the same word gets the same markup — so it is rendered once. Short
	 * enough that a published post shows up almost at once; long enough to flatten the
	 * burst a single visitor's typing produces. The key carries the post's modified time
	 * and the plugin version, so an edit or an upgrade invalidates it without waiting.
	 *
	 * Set `flexa_block/post_query/cache_ttl` to 0 to render every request.
	 */
	const CACHE_TTL = 60;

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Register the /post-query route.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NS,
			'/post-query',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'permission_callback' => '__return_true',
				'callback'            => [ __CLASS__, 'query' ],
				'args'                => [
					'post_id'  => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
						'validate_callback' => [ __CLASS__, 'validate_post' ],
					],
					'block_id' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_html_class',
					],
					'page'     => [
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					],
					's'        => [
						'type'    => 'string',
						'default' => '',
					],
					'tax'      => [
						'type'    => 'object',
						'default' => [],
					],
				],
			]
		);
	}

	/**
	 * Only let the route render a post a visitor could already read.
	 *
	 * @param mixed $value Post ID.
	 * @return bool
	 */
	public static function validate_post( $value ): bool {
		$post = get_post( absint( $value ) );
		if ( ! $post || 'publish' !== $post->post_status || '' !== $post->post_password ) {
			return false;
		}
		if ( function_exists( 'is_post_publicly_viewable' ) && ! is_post_publicly_viewable( $post ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Render one grid with the visitor's filters applied.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function query( \WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'post_id' );
		$gid     = sanitize_html_class( (string) $request->get_param( 'block_id' ) );

		if ( '' === $gid ) {
			return new \WP_Error( 'flexa_block_bad_block', __( 'Missing block id.', 'flexa-block' ), [ 'status' => 400 ] );
		}

		$attrs = Block_Locator::find_attrs( 'flexa/post-grid', $gid, $post_id );
		if ( null === $attrs ) {
			return new \WP_Error( 'flexa_block_no_grid', __( 'Post Grid not found.', 'flexa-block' ), [ 'status' => 404 ] );
		}

		$cfg = Post_Query::config( $attrs );

		// Re-key the request into the same shape the front end reads from $_GET, so
		// both paths run through one parser (and one set of caps).
		$allowed = Post_Query::allowed_taxonomies( $post_id, $gid );
		$input   = self::request_bag( $request, $gid, $allowed );
		$filters = Post_Query::parse_filters( $input, $gid, $allowed );

		$paged = max( 1, (int) $request->get_param( 'page' ) );

		// Same post, same grid, same filters, same page → same markup, for everyone.
		// So render it once and hand out the copy. Cached AFTER parse_filters, never
		// before: the key is built from the SANITIZED filters, so a hand-crafted URL
		// can't mint an unbounded number of cache entries out of junk it was going to
		// have dropped anyway.
		$ttl   = (int) apply_filters( 'flexa_block/post_query/cache_ttl', self::CACHE_TTL, $post_id, $gid );
		$key   = self::cache_key( $post_id, $gid, $filters, $paged );
		$cached = $ttl > 0 ? get_transient( $key ) : false;
		if ( is_array( $cached ) ) {
			return rest_ensure_response( $cached );
		}

		$result = Post_Query::run( $cfg, $filters, $paged, $post_id );

		// Pagination links must point at the PAGE (carrying the active filters), not
		// at this route — they have to keep working when JavaScript fails and the
		// browser follows them as plain links.
		$base_url = self::filtered_url( $post_id, $gid, $filters );

		$cards = Post_Query::render_cards( $result['query'], $cfg );

		$payload = [
			'grid'       => Post_Query::render_grid( $cards, ! empty( $cfg['showResultCount'] ) ),
			'pagination' => Post_Query::render_pagination( $cfg, $gid, $result['paged'], $result['totalPages'], $base_url ),
			'status'     => Post_Query::render_status( $result['total'], ! empty( $cfg['showResultCount'] ), (string) ( $cfg['resultCountText'] ?? '' ) ),
			'total'      => $result['total'],
			'page'       => $result['paged'],
			'totalPages' => $result['totalPages'],
			'url'        => self::filtered_url( $post_id, $gid, $filters, $result['paged'] ),
		];

		// A random order is a different grid every time — caching it would freeze the
		// shuffle for a minute and make it look broken.
		if ( $ttl > 0 && 'rand' !== ( $cfg['orderBy'] ?? '' ) ) {
			set_transient( $key, $payload, $ttl );
		}

		return rest_ensure_response( $payload );
	}

	/**
	 * The cache key for one rendered grid.
	 *
	 * Carries everything the markup depends on: the post (and WHEN it last changed, so
	 * an edit invalidates instead of waiting out the TTL), the grid, the visitor's
	 * sanitized filters, the page, the locale (the markup contains translated strings)
	 * and the plugin version (an upgrade changes the markup).
	 *
	 * @param int    $post_id Post the grid lives on.
	 * @param string $gid     Sanitized blockId.
	 * @param array  $filters Parsed filters.
	 * @param int    $paged   Page.
	 * @return string
	 */
	private static function cache_key( int $post_id, string $gid, array $filters, int $paged ): string {
		$parts = [
			$post_id,
			(string) get_post_modified_time( 'U', true, $post_id ),
			$gid,
			$paged,
			get_locale(),
			FLEXA_BLOCK_VER,
			wp_json_encode( $filters ),
		];

		return 'flexa_pq_' . md5( implode( '|', $parts ) );
	}

	/**
	 * Flatten the REST params into the `$_GET`-shaped bag Post_Query::parse_filters
	 * expects, so the REST path and the front-end path share one parser.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @param string           $gid     Sanitized blockId.
	 * @param array            $allowed Allowed taxonomy slugs.
	 * @return array<string, mixed>
	 */
	private static function request_bag( \WP_REST_Request $request, string $gid, array $allowed ): array {
		$bag = [ Post_Query::search_key( $gid ) => (string) $request->get_param( 's' ) ];

		$tax = $request->get_param( 'tax' );
		if ( is_array( $tax ) ) {
			foreach ( $allowed as $taxonomy ) {
				if ( isset( $tax[ $taxonomy ] ) ) {
					$bag[ Post_Query::tax_key( $gid, $taxonomy ) ] = $tax[ $taxonomy ];
				}
			}
		}

		return $bag;
	}

	/**
	 * The front-end URL for a filter state — what the address bar becomes, and what
	 * the pagination links are built from.
	 *
	 * @param int      $post_id Post the grid lives on.
	 * @param string   $gid     Sanitized blockId.
	 * @param array    $filters Parsed filters.
	 * @param int|null $paged   Page to include (omitted when 1).
	 * @return string
	 */
	private static function filtered_url( int $post_id, string $gid, array $filters, ?int $paged = null ): string {
		$url  = (string) get_permalink( $post_id );
		$args = [];

		if ( '' !== (string) $filters['s'] ) {
			$args[ Post_Query::search_key( $gid ) ] = (string) $filters['s'];
		}
		foreach ( (array) $filters['tax'] as $taxonomy => $slugs ) {
			$args[ Post_Query::tax_key( $gid, (string) $taxonomy ) ] = implode( ',', (array) $slugs );
		}
		if ( null !== $paged && $paged > 1 ) {
			$args[ Post_Query::page_key( $gid ) ] = $paged;
		}

		// add_query_arg() encodes the values itself — don't pre-encode them.
		return empty( $args ) ? $url : add_query_arg( $args, $url );
	}
}
