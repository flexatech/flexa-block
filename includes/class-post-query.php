<?php
declare(strict_types=1);
/**
 * Post Query — the query + card markup behind Post Grid, shared by two callers.
 *
 * The grid renders server side (render.php) AND is re-rendered on demand when a
 * visitor searches or filters it (the /post-query REST route). Both must produce
 * byte-identical markup from identical state — otherwise a filtered page reloaded
 * cold would not match what the AJAX swap produced. So the query build, the card
 * loop and the pagination markup live here once, and both callers go through them.
 *
 * The visitor's contribution to the query is deliberately tiny: a page number, a
 * search string, and term slugs. Everything else (post type, per page, order, the
 * author's own taxonomy constraint) comes from the block's SAVED attributes. And
 * the visitor's terms can only ever NARROW the author's constraint — never escape
 * it. See build_tax_query().
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query + render core for flexa/post-grid.
 */
final class Post_Query {

	const MAX_SEARCH_LEN = 100;
	const MAX_TAXONOMIES = 5;
	const MAX_TERMS      = 20;
	const MAX_PAGE       = 500;
	const MAX_PER_PAGE   = 100;

	const ORDER_BY   = [ 'date', 'title', 'menu_order', 'rand', 'comment_count' ];
	const TITLE_TAGS = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div' ];
	const PAGINATION = [ 'none', 'numbered', 'loadmore' ];

	/**
	 * Where a visitor's search looks.
	 *
	 * WordPress searches the RAW post_content — which, on a block site, contains the
	 * block markup itself. So a visitor searching "container" matches every post that
	 * merely CONTAINS a container block, and a search for "image" or "button" returns
	 * practically the whole blog. `title_excerpt` avoids that by searching only what a
	 * human actually wrote; `all` is WordPress's own behaviour, for sites that want it.
	 */
	const SEARCH_SCOPES = [ 'title_excerpt', 'all' ];

	/** Query var carrying the scope into the posts_search filter. */
	const SCOPE_QUERY_VAR = 'flexa_search_scope';

	/* ---------------------------------------------------------------------
	 * Query-arg keys. One namespace per grid instance, so two grids on one
	 * page filter and page independently — and so a search never lands on the
	 * bare `s` (which would hijack the main query into is_search()).
	 * ------------------------------------------------------------------ */

	/**
	 * The paging query arg for one grid.
	 *
	 * @param string $gid Sanitized blockId.
	 * @return string
	 */
	public static function page_key( string $gid ): string {
		return '' !== $gid ? 'pg_' . $gid : 'pg';
	}

	/**
	 * The search query arg for one grid.
	 *
	 * @param string $gid Sanitized blockId.
	 * @return string
	 */
	public static function search_key( string $gid ): string {
		return 's_' . $gid;
	}

	/**
	 * The term query arg for one grid + taxonomy.
	 *
	 * @param string $gid      Sanitized blockId.
	 * @param string $taxonomy Taxonomy slug.
	 * @return string
	 */
	public static function tax_key( string $gid, string $taxonomy ): string {
		return 'tx_' . $gid . '_' . $taxonomy;
	}

	/* ---------------------------------------------------------------------
	 * Config: block attributes → a sanitized, complete render config.
	 * ------------------------------------------------------------------ */

	/**
	 * Sanitize the grid's saved attributes into the config both renderers use.
	 *
	 * @param array $attrs Block attributes.
	 * @return array
	 */
	public static function config( array $attrs ): array {
		$post_type = sanitize_key( (string) ( $attrs['postType'] ?? 'post' ) );
		if ( '' === $post_type || ! post_type_exists( $post_type ) ) {
			$post_type = 'post';
		}

		$taxonomy = sanitize_key( (string) ( $attrs['taxonomy'] ?? '' ) );
		if ( '' !== $taxonomy && ! taxonomy_exists( $taxonomy ) ) {
			$taxonomy = '';
		}

		$image_size  = sanitize_key( (string) ( $attrs['imageSize'] ?? 'large' ) );
		$image_ratio = trim( (string) ( $attrs['imageRatio'] ?? '' ) );

		$read_more  = trim( (string) ( $attrs['readMoreText'] ?? '' ) );
		$load_more  = trim( (string) ( $attrs['loadMoreText'] ?? '' ) );
		$prev_label = trim( (string) ( $attrs['prevLabel'] ?? '' ) );
		$next_label = trim( (string) ( $attrs['nextLabel'] ?? '' ) );

		return [
			// Query.
			'postType'       => $post_type,
			'perPage'        => max( 1, min( self::MAX_PER_PAGE, (int) ( $attrs['postsPerPage'] ?? 6 ) ) ),
			'offset'         => max( 0, (int) ( $attrs['offset'] ?? 0 ) ),
			'orderBy'        => in_array( $attrs['orderBy'] ?? 'date', self::ORDER_BY, true ) ? (string) $attrs['orderBy'] : 'date',
			'order'          => 'asc' === strtolower( (string) ( $attrs['order'] ?? 'desc' ) ) ? 'ASC' : 'DESC',
			'taxonomy'       => $taxonomy,
			'terms'          => (string) ( $attrs['terms'] ?? '' ),
			'excludeCurrent' => ! empty( $attrs['excludeCurrent'] ),
			'searchScope'    => in_array( $attrs['searchScope'] ?? 'title_excerpt', self::SEARCH_SCOPES, true ) ? (string) $attrs['searchScope'] : 'title_excerpt',
			// Whether the "N posts found" line is SHOWN. It is always rendered — see
			// render_status() — because a screen reader has nothing else to go on.
			'showResultCount' => ! empty( $attrs['showResultCount'] ),
			'resultCountText' => trim( (string) ( $attrs['resultCountText'] ?? '' ) ),

			// Pagination.
			'paginationType' => in_array( $attrs['paginationType'] ?? 'numbered', self::PAGINATION, true ) ? (string) $attrs['paginationType'] : 'numbered',
			'loadMoreText'   => '' !== $load_more ? $load_more : __( 'Load more', 'flexa-block' ),
			'prevLabel'      => '' !== $prev_label ? $prev_label : __( '‹', 'flexa-block' ),
			'nextLabel'      => '' !== $next_label ? $next_label : __( '›', 'flexa-block' ),

			// Card elements.
			'showImage'      => false !== ( $attrs['showImage'] ?? true ),
			'showTitle'      => false !== ( $attrs['showTitle'] ?? true ),
			'titleTag'       => in_array( $attrs['titleTag'] ?? 'h3', self::TITLE_TAGS, true ) ? (string) $attrs['titleTag'] : 'h3',
			'showMeta'       => false !== ( $attrs['showMeta'] ?? true ),
			'showAuthor'     => false !== ( $attrs['showAuthor'] ?? true ),
			'showAvatar'     => ! empty( $attrs['showAvatar'] ),
			'avatarSize'     => max( 12, min( 96, (int) ( $attrs['avatarSize'] ?? 24 ) ) ),
			'showDate'       => false !== ( $attrs['showDate'] ?? true ),
			'showComments'   => ! empty( $attrs['showComments'] ),
			'showTaxonomy'   => ! empty( $attrs['showTaxonomy'] ),
			'showExcerpt'    => false !== ( $attrs['showExcerpt'] ?? true ),
			'excerptLength'  => max( 0, (int) ( $attrs['excerptLength'] ?? 20 ) ),
			'showReadMore'   => false !== ( $attrs['showReadMore'] ?? true ),
			'readMoreText'   => '' !== $read_more ? $read_more : __( 'Read more', 'flexa-block' ),
			'imageSize'      => '' !== $image_size ? $image_size : 'large',
			// Inline aspect-ratio for the "no image" placeholder so it matches the chosen ratio.
			'ratioStyle'     => preg_match( '#^[0-9]+\s*/\s*[0-9]+$#', $image_ratio ) ? 'aspect-ratio:' . $image_ratio : '',
		];
	}

	/* ---------------------------------------------------------------------
	 * Visitor filters. Pure functions (no WordPress state) so the never-widen
	 * guarantee below is unit-testable.
	 * ------------------------------------------------------------------ */

	/**
	 * Read the visitor's filters out of a request bag.
	 *
	 * Anything not on the allow-list is dropped, and everything else is clamped
	 * rather than rejected — a hand-crafted URL should degrade to a sane query,
	 * not an error page.
	 *
	 * @param array  $input     Request values ($_GET, or the REST params).
	 * @param string $gid       Sanitized blockId of the grid.
	 * @param array  $allowed   Taxonomy slugs a filter block actually exposes.
	 * @return array{s:string, tax:array<string, array<int,string>>}
	 */
	public static function parse_filters( array $input, string $gid, array $allowed ): array {
		$filters = [
			's'   => '',
			'tax' => [],
		];
		if ( '' === $gid ) {
			return $filters;
		}

		$search = $input[ self::search_key( $gid ) ] ?? '';
		if ( is_string( $search ) && '' !== trim( $search ) ) {
			$search        = sanitize_text_field( wp_unslash( $search ) );
			$filters['s']  = self::substr( $search, self::MAX_SEARCH_LEN );
		}

		foreach ( array_slice( array_values( $allowed ), 0, self::MAX_TAXONOMIES ) as $taxonomy ) {
			$taxonomy = sanitize_key( (string) $taxonomy );
			if ( '' === $taxonomy ) {
				continue;
			}
			$raw = $input[ self::tax_key( $gid, $taxonomy ) ] ?? null;
			if ( null === $raw ) {
				continue;
			}

			// Single-select posts one CSV value; multi-select posts `name[]`.
			$values = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
			$slugs  = [];
			foreach ( $values as $value ) {
				if ( ! is_scalar( $value ) ) {
					continue;
				}
				$slug = sanitize_title( wp_unslash( (string) $value ) );
				if ( '' !== $slug && ! in_array( $slug, $slugs, true ) ) {
					$slugs[] = $slug;
				}
				if ( count( $slugs ) >= self::MAX_TERMS ) {
					break;
				}
			}
			if ( ! empty( $slugs ) ) {
				$filters['tax'][ $taxonomy ] = $slugs;
			}
		}

		return $filters;
	}

	/**
	 * Keep only the requested term IDs that the author's constraint allows.
	 *
	 * @param array $requested Term IDs the visitor asked for.
	 * @param array $allowed   Term IDs the author's query permits.
	 * @return array<int, int>
	 */
	public static function intersect_terms( array $requested, array $allowed ): array {
		$requested = array_map( 'intval', $requested );
		$allowed   = array_map( 'intval', $allowed );
		return array_values( array_intersect( $requested, $allowed ) );
	}

	/**
	 * Build the tax_query from the author's constraint + the visitor's filters.
	 *
	 * The two are ANDed, so a visitor clause can only ever narrow the result set.
	 * When the visitor asks for terms the author's constraint excludes, the
	 * intersection is empty and the visitor clause is DROPPED — the author's
	 * clause stands alone. A crafted URL can therefore neither escape the
	 * author's constraint nor blank the grid out.
	 *
	 * @param array    $cfg         Config from config().
	 * @param array    $filters     Filters from parse_filters().
	 * @param callable $slug_to_ids fn(string $taxonomy, string[] $slugs): int[].
	 * @return array WP_Query `tax_query`.
	 */
	public static function build_tax_query( array $cfg, array $filters, callable $slug_to_ids ): array {
		$author_tax = (string) ( $cfg['taxonomy'] ?? '' );
		$author_ids = self::author_term_ids( $cfg, $slug_to_ids );

		$clauses = [];
		if ( '' !== $author_tax && ! empty( $author_ids ) ) {
			$clauses[] = [
				'taxonomy' => $author_tax,
				'field'    => 'term_id',
				'terms'    => $author_ids,
			];
		}

		foreach ( (array) ( $filters['tax'] ?? [] ) as $taxonomy => $slugs ) {
			$ids = array_values( array_unique( array_map( 'intval', (array) $slug_to_ids( (string) $taxonomy, (array) $slugs ) ) ) );
			$ids = array_filter( $ids );
			if ( empty( $ids ) ) {
				continue;
			}

			if ( $taxonomy === $author_tax && ! empty( $author_ids ) ) {
				$ids = self::intersect_terms( $ids, $author_ids );
				if ( empty( $ids ) ) {
					// Outside what the author allows — ignore it, keep their clause.
					continue;
				}
			}

			$clauses[] = [
				'taxonomy' => (string) $taxonomy,
				'field'    => 'term_id',
				'terms'    => array_values( $ids ),
			];
		}

		if ( count( $clauses ) > 1 ) {
			$clauses['relation'] = 'AND';
		}

		return $clauses;
	}

	/**
	 * Resolve the author's `terms` attribute (term IDs, or slugs) to term IDs.
	 *
	 * @param array    $cfg         Config from config().
	 * @param callable $slug_to_ids fn(string $taxonomy, string[] $slugs): int[].
	 * @return array<int, int>
	 */
	private static function author_term_ids( array $cfg, callable $slug_to_ids ): array {
		$taxonomy = (string) ( $cfg['taxonomy'] ?? '' );
		$raw      = trim( (string) ( $cfg['terms'] ?? '' ) );
		if ( '' === $taxonomy || '' === $raw ) {
			return [];
		}

		$list = array_values( array_filter( array_map( 'trim', explode( ',', $raw ) ) ) );
		if ( empty( $list ) ) {
			return [];
		}

		// The editor stores term IDs; tolerate slugs from hand-edited content.
		$numeric = count( $list ) === count( array_filter( $list, 'is_numeric' ) );
		if ( $numeric ) {
			return array_values( array_filter( array_map( 'intval', $list ) ) );
		}

		return array_values( array_filter( array_map( 'intval', (array) $slug_to_ids( $taxonomy, $list ) ) ) );
	}

	/**
	 * Build the WP_Query args.
	 *
	 * @param array $cfg       Config from config().
	 * @param array $filters   Filters from parse_filters().
	 * @param int   $paged     Requested page (1-based).
	 * @param array $tax_query Result of build_tax_query().
	 * @return array
	 */
	public static function build_args( array $cfg, array $filters, int $paged, array $tax_query = [] ): array {
		$per_page = max( 1, min( self::MAX_PER_PAGE, (int) ( $cfg['perPage'] ?? 6 ) ) );
		$paged    = max( 1, min( self::MAX_PAGE, $paged ) );
		$offset   = max( 0, (int) ( $cfg['offset'] ?? 0 ) );

		// Every paged mode advances the window — numbered swaps the cards, load-more
		// appends them. (Load-more used to share the un-paged branch, so page 2
		// returned page 1's posts and the button appended duplicates.)
		$paginated = 'none' !== ( $cfg['paginationType'] ?? 'numbered' );

		$args = [
			'post_type'           => $cfg['postType'] ?? 'post',
			'post_status'         => 'publish',
			'orderby'             => $cfg['orderBy'] ?? 'date',
			'order'               => $cfg['order'] ?? 'DESC',
			'posts_per_page'      => $per_page,
			'offset'              => $offset + ( $paginated ? ( $paged - 1 ) * $per_page : 0 ),
			'ignore_sticky_posts' => true,
		];

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$search = trim( (string) ( $filters['s'] ?? '' ) );
		if ( '' !== $search ) {
			$args['s'] = $search;

			// Carried into the posts_search filter, which narrows the LIKE to what a
			// human wrote. Without it WordPress searches the raw post_content — block
			// markup included — and "container" matches every post built with one.
			$scope = (string) ( $cfg['searchScope'] ?? 'title_excerpt' );
			if ( 'all' !== $scope ) {
				$args[ self::SCOPE_QUERY_VAR ] = 'title_excerpt';
			}
		}

		return $args;
	}

	/**
	 * Replace WordPress's search clause with one that only looks at the title and the
	 * excerpt. Applies to OUR query only — it's keyed off a query var no other query
	 * sets, and it's added and removed around the WP_Query in run().
	 *
	 * @param string    $search The SQL search clause WordPress built.
	 * @param \WP_Query $query  The query being built.
	 * @return string
	 */
	public static function filter_search_clause( $search, $query ): string {
		if ( 'title_excerpt' !== $query->get( self::SCOPE_QUERY_VAR ) ) {
			return (string) $search;
		}

		$term = trim( (string) $query->get( 's' ) );
		if ( '' === $term ) {
			return (string) $search;
		}

		global $wpdb;
		$like = '%' . $wpdb->esc_like( $term ) . '%';

		return $wpdb->prepare(
			" AND ({$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.post_excerpt LIKE %s) ", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb.
			$like,
			$like
		);
	}

	/* ---------------------------------------------------------------------
	 * Running the query.
	 * ------------------------------------------------------------------ */

	/**
	 * Resolve term slugs to IDs within one taxonomy (the WordPress resolver
	 * build_tax_query() is injected with).
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @param array  $slugs    Term slugs.
	 * @return array<int, int>
	 */
	public static function resolve_slugs( string $taxonomy, array $slugs ): array {
		if ( '' === $taxonomy || empty( $slugs ) || ! taxonomy_exists( $taxonomy ) ) {
			return [];
		}
		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'slug'       => array_slice( array_map( 'strval', $slugs ), 0, self::MAX_TERMS ),
				'hide_empty' => false,
				'fields'     => 'ids',
				'number'     => self::MAX_TERMS,
			]
		);
		if ( is_wp_error( $terms ) ) {
			return [];
		}
		return array_values( array_map( 'intval', (array) $terms ) );
	}

	/**
	 * The term IDs the author's query constrains a taxonomy to — what a filter
	 * control must limit its options to, so the UI can only offer what the server
	 * would actually honour.
	 *
	 * @param array  $cfg      Config from config().
	 * @param string $taxonomy Taxonomy the control filters by.
	 * @return array<int, int> Empty when the author places no constraint on it.
	 */
	public static function constrained_terms( array $cfg, string $taxonomy ): array {
		if ( $taxonomy !== (string) ( $cfg['taxonomy'] ?? '' ) ) {
			return [];
		}
		return self::author_term_ids( $cfg, [ __CLASS__, 'resolve_slugs' ] );
	}

	/**
	 * Run the grid's query.
	 *
	 * @param array    $cfg        Config from config().
	 * @param array    $filters    Filters from parse_filters().
	 * @param int      $paged      Requested page.
	 * @param int|null $current_id Post to exclude for `excludeCurrent`. Defaults to
	 *                             the queried object — which REST has none of, so the
	 *                             route passes the post the grid lives on explicitly.
	 * @return array{query:\WP_Query, total:int, totalPages:int, paged:int}
	 */
	public static function run( array $cfg, array $filters, int $paged, ?int $current_id = null ): array {
		$tax_query = self::build_tax_query( $cfg, $filters, [ __CLASS__, 'resolve_slugs' ] );
		$args      = self::build_args( $cfg, $filters, $paged, $tax_query );

		if ( ! empty( $cfg['excludeCurrent'] ) ) {
			$current = null !== $current_id ? $current_id : (int) get_queried_object_id();
			if ( $current > 0 ) {
				// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- single current post excluded from the grid; negligible cost.
				$args['post__not_in'] = [ $current ];
			}
		}

		/**
		 * Filter the Post Grid query args before they run.
		 *
		 * @param array $args    WP_Query args.
		 * @param array $cfg     Sanitized block config.
		 * @param array $filters Visitor filters.
		 */
		$args = apply_filters( 'flexa_block_post_query_args', $args, $cfg, $filters );

		// Scoped search: hooked only for the duration of this one query.
		$scoped = isset( $args[ self::SCOPE_QUERY_VAR ] );
		if ( $scoped ) {
			add_filter( 'posts_search', [ __CLASS__, 'filter_search_clause' ], 10, 2 );
		}

		$query = new \WP_Query( $args );

		if ( $scoped ) {
			remove_filter( 'posts_search', [ __CLASS__, 'filter_search_clause' ], 10 );
		}

		$per_page    = max( 1, (int) $args['posts_per_page'] );
		$total       = max( 0, (int) $query->found_posts - max( 0, (int) ( $cfg['offset'] ?? 0 ) ) );
		$total_pages = (int) ceil( $total / $per_page );

		return [
			'query'      => $query,
			'total'      => $total,
			'totalPages' => $total_pages,
			'paged'      => max( 1, min( self::MAX_PAGE, $paged ) ),
		];
	}

	/**
	 * The requested page for one grid, read from a request bag.
	 *
	 * @param array  $input Request values ($_GET or REST params).
	 * @param string $gid   Sanitized blockId.
	 * @return int
	 */
	public static function paged( array $input, string $gid ): int {
		$key = self::page_key( $gid );
		if ( isset( $input[ $key ] ) ) {
			return max( 1, min( self::MAX_PAGE, absint( wp_unslash( $input[ $key ] ) ) ) );
		}
		$qv = (int) get_query_var( 'paged' );
		return $qv > 1 ? $qv : max( 1, (int) get_query_var( 'page' ) );
	}

	/**
	 * The grid a filter bar drives, from whatever it has saved as its target.
	 *
	 * Three places have to agree on this — the bar's form attribute, its children's
	 * field names, and the grid's own allow-list — and they only agree if they resolve
	 * the target the same way. Hence one function, called by all of them:
	 *
	 *  - Empty ("auto") → the first Post Grid on the page.
	 *  - A grid that ISN'T on the page → also the first Post Grid. An author who
	 *    duplicates their grid gets a NEW blockId on the copy, so the bar's saved target
	 *    starts pointing at a grid that no longer exists. Read raw, that renders a bar
	 *    whose fields are named after a ghost: every control works, nothing filters.
	 *
	 * @param string $raw     Saved `targetGridId` (may be empty or stale).
	 * @param int    $post_id Post being rendered.
	 * @return string Sanitized blockId, or '' when the page has no grid at all.
	 */
	public static function resolve_target( string $raw, int $post_id ): string {
		$target = sanitize_html_class( $raw );
		if ( '' !== $target && null !== Block_Locator::find_attrs( 'flexa/post-grid', $target, $post_id ) ) {
			return $target;
		}
		return Block_Locator::find_first_id( 'flexa/post-grid', $post_id );
	}

	/**
	 * Taxonomies a visitor is allowed to filter this grid by: exactly those a
	 * `flexa/filter-taxonomy` block on the page actually exposes for it.
	 *
	 * This is what makes the URL contract safe to leave open — a param naming a
	 * taxonomy no filter block renders is not "a filter the UI forgot", it's a
	 * request the page never offered, and it's dropped.
	 *
	 * @param int    $post_id Post being rendered.
	 * @param string $gid     Sanitized blockId of the grid.
	 * @return array<int, string> Taxonomy slugs.
	 */
	public static function allowed_taxonomies( int $post_id, string $gid ): array {
		if ( '' === $gid ) {
			return [];
		}

		$allowed = [];

		foreach ( Block_Locator::find_all( 'flexa/post-filter', $post_id ) as $node ) {
			// Resolved, not read raw: a bar whose saved target no longer exists drives the
			// first grid on the page (see resolve_target), and this allow-list has to say
			// the same thing the bar's fields do — or the page renders a filter it then
			// refuses to honour.
			if ( self::resolve_target( (string) ( $node['attrs']['targetGridId'] ?? '' ), $post_id ) !== $gid ) {
				continue;
			}

			foreach ( Block_Locator::children( $node, 'flexa/filter-taxonomy' ) as $child ) {
				// Gutenberg omits any attribute still equal to its block.json default,
				// so the Category field — `taxonomy` defaults to `category` — saves with
				// NO taxonomy attribute at all. Reading the raw parsed attrs would drop
				// `category` from the allow-list and silently ignore every category
				// filter the page itself renders. Merge the defaults first, exactly as
				// Block_Locator::find_attrs() does for the grid.
				$attrs    = CSS_Generator_Service::merge_defaults( 'flexa/filter-taxonomy', $child['attrs'] ?? [] );
				$taxonomy = sanitize_key( (string) ( $attrs['taxonomy'] ?? '' ) );
				if ( '' !== $taxonomy && taxonomy_exists( $taxonomy ) && ! in_array( $taxonomy, $allowed, true ) ) {
					$allowed[] = $taxonomy;
				}
				if ( count( $allowed ) >= self::MAX_TAXONOMIES ) {
					return $allowed;
				}
			}
		}

		return $allowed;
	}

	/* ---------------------------------------------------------------------
	 * Markup. Both callers render through these, so a cold reload of a
	 * filtered URL produces exactly what the AJAX swap produced.
	 * ------------------------------------------------------------------ */

	/**
	 * Render the cards for a run query.
	 *
	 * @param \WP_Query $query The query from run().
	 * @param array     $cfg   Config from config().
	 * @return string
	 */
	public static function render_cards( \WP_Query $query, array $cfg ): string {
		if ( ! $query->have_posts() ) {
			return '';
		}

		$cards = '';
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id   = (int) get_the_ID();
			$permalink = (string) get_permalink( $post_id );

			$cards .= '<article class="flexa-post-grid__item">'
				. self::card_image( $post_id, $permalink, $cfg )
				. '<div class="flexa-post-grid__body">'
					. self::card_meta( $post_id, $cfg )
					. self::card_title( $post_id, $permalink, $cfg )
					. self::card_excerpt( $post_id, $cfg )
					. self::card_read_more( $permalink, $cfg )
				. '</div>'
				. '</article>';
		}
		wp_reset_postdata();

		return $cards;
	}

	/**
	 * The featured image, linked — or a neutral placeholder so cards stay uniform.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $permalink Post URL.
	 * @param array  $cfg       Config.
	 * @return string
	 */
	private static function card_image( int $post_id, string $permalink, array $cfg ): string {
		if ( empty( $cfg['showImage'] ) ) {
			return '';
		}

		if ( has_post_thumbnail( $post_id ) ) {
			$thumb = get_the_post_thumbnail(
				$post_id,
				(string) $cfg['imageSize'],
				[
					'class'   => 'flexa-post-grid__image',
					'loading' => 'lazy',
				]
			);
			if ( '' !== $thumb ) {
				return '<a class="flexa-post-grid__image-link" href="' . esc_url( $permalink ) . '" tabindex="-1" aria-hidden="true">'
					. '<span class="flexa-post-grid__image-wrap">' . $thumb . '</span></a>';
			}
		}

		return '<span class="flexa-post-grid__image-wrap">' . HTML_Helpers::image_placeholder( '', (string) $cfg['ratioStyle'] ) . '</span>';
	}

	/**
	 * The meta line: author, date, comments, terms.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $cfg     Config.
	 * @return string
	 */
	private static function card_meta( int $post_id, array $cfg ): string {
		if ( empty( $cfg['showMeta'] ) ) {
			return '';
		}

		$parts = [];

		if ( ! empty( $cfg['showAuthor'] ) ) {
			$inner = '';
			if ( ! empty( $cfg['showAvatar'] ) ) {
				$inner .= get_avatar( get_the_author_meta( 'ID' ), (int) $cfg['avatarSize'], '', get_the_author(), [ 'class' => 'flexa-post-grid__avatar' ] );
			}
			$inner  .= esc_html( get_the_author() );
			$parts[] = '<span class="flexa-post-grid__author">' . $inner . '</span>';
		}

		if ( ! empty( $cfg['showDate'] ) ) {
			$parts[] = '<time class="flexa-post-grid__date" datetime="' . esc_attr( get_the_date( 'c', $post_id ) ) . '">' . esc_html( get_the_date( '', $post_id ) ) . '</time>';
		}

		if ( ! empty( $cfg['showComments'] ) ) {
			$number = (int) get_comments_number( $post_id );
			/* translators: %d: number of comments on the post. */
			$label   = sprintf( _n( '%d comment', '%d comments', $number, 'flexa-block' ), $number );
			$parts[] = '<span class="flexa-post-grid__comments">' . esc_html( $label ) . '</span>';
		}

		if ( ! empty( $cfg['showTaxonomy'] ) ) {
			$taxonomy = '' !== $cfg['taxonomy'] ? (string) $cfg['taxonomy'] : ( 'post' === $cfg['postType'] ? 'category' : '' );
			if ( '' !== $taxonomy ) {
				$terms = get_the_term_list( $post_id, $taxonomy, '', ', ' );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					$parts[] = '<span class="flexa-post-grid__terms">' . wp_kses(
						$terms,
						[
							'a' => [
								'href' => true,
								'rel'  => true,
							],
						]
					) . '</span>';
				}
			}
		}

		if ( empty( $parts ) ) {
			return '';
		}

		return '<div class="flexa-post-grid__meta">'
			. implode( '<span class="flexa-post-grid__meta-sep" aria-hidden="true">·</span>', $parts )
			. '</div>';
	}

	/**
	 * The linked title.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $permalink Post URL.
	 * @param array  $cfg       Config.
	 * @return string
	 */
	private static function card_title( int $post_id, string $permalink, array $cfg ): string {
		if ( empty( $cfg['showTitle'] ) ) {
			return '';
		}
		$tag = (string) $cfg['titleTag'];
		return '<' . $tag . ' class="flexa-post-grid__title"><a href="' . esc_url( $permalink ) . '">'
			. esc_html( get_the_title( $post_id ) ) . '</a></' . $tag . '>';
	}

	/**
	 * The excerpt, trimmed to plain text.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $cfg     Config.
	 * @return string
	 */
	private static function card_excerpt( int $post_id, array $cfg ): string {
		$length = (int) $cfg['excerptLength'];
		if ( empty( $cfg['showExcerpt'] ) || $length <= 0 ) {
			return '';
		}

		$raw     = has_excerpt( $post_id )
			? get_the_excerpt( $post_id )
			: wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) );
		$trimmed = wp_trim_words( $raw, $length, '…' );

		if ( '' === trim( $trimmed ) ) {
			return '';
		}
		return '<div class="flexa-post-grid__excerpt">' . esc_html( $trimmed ) . '</div>';
	}

	/**
	 * The read-more button.
	 *
	 * @param string $permalink Post URL.
	 * @param array  $cfg       Config.
	 * @return string
	 */
	private static function card_read_more( string $permalink, array $cfg ): string {
		if ( empty( $cfg['showReadMore'] ) ) {
			return '';
		}
		return '<a class="flexa-post-grid__readmore wp-element-button" href="' . esc_url( $permalink ) . '">'
			. esc_html( (string) $cfg['readMoreText'] ) . '</a>';
	}

	/**
	 * Wrap the cards in the grid — or render the empty state.
	 *
	 * @param string $cards       Card markup from render_cards().
	 * @param bool   $count_shown The result count is visible, so it already says there
	 *                            are none. "0 posts found" above "No posts found." is
	 *                            the same fact twice, in two different voices, and reads
	 *                            like two separate problems.
	 * @return string
	 */
	public static function render_grid( string $cards, bool $count_shown = false ): string {
		if ( '' === $cards ) {
			// The paragraph stays even when it says nothing: it is the node the view
			// script swaps back when results return. Remove it and the grid has nothing
			// to come back into.
			return $count_shown
				? '<p class="flexa-post-grid__empty" hidden></p>'
				: '<p class="flexa-post-grid__empty">' . esc_html__( 'No posts found.', 'flexa-block' ) . '</p>';
		}
		return '<div class="flexa-post-grid__grid">' . $cards . '</div>';
	}

	/**
	 * Render the pagination.
	 *
	 * Numbered pagination is real server-rendered links carrying every current
	 * query arg (so a filtered grid stays filtered when you page it, with or
	 * without JavaScript). The view script upgrades them to AJAX.
	 *
	 * @param array  $cfg         Config from config().
	 * @param string $gid         Sanitized blockId.
	 * @param int    $paged       Current page.
	 * @param int    $total_pages Total pages.
	 * @param string $base_url    URL the links are built from (defaults to the current request).
	 * @return string
	 */
	public static function render_pagination( array $cfg, string $gid, int $paged, int $total_pages, string $base_url = '' ): string {
		if ( $total_pages <= 1 ) {
			return '';
		}

		$key  = self::page_key( $gid );
		$type = (string) $cfg['paginationType'];

		if ( 'numbered' === $type ) {
			$base = '' !== $base_url
				? add_query_arg( $key, '%#%', $base_url )
				: add_query_arg( $key, '%#%' );

			$links = paginate_links(
				[
					'base'      => esc_url_raw( $base ),
					'format'    => '',
					'current'   => $paged,
					'total'     => $total_pages,
					'type'      => 'plain',
					'prev_text' => esc_html( (string) $cfg['prevLabel'] ),
					'next_text' => esc_html( (string) $cfg['nextLabel'] ),
				]
			);
			if ( ! $links ) {
				return '';
			}
			return '<nav class="flexa-pagination" aria-label="' . esc_attr__( 'Posts navigation', 'flexa-block' ) . '">' . $links . '</nav>';
		}

		if ( 'loadmore' === $type ) {
			if ( $paged >= $total_pages ) {
				return '';
			}
			$next = '' !== $base_url
				? add_query_arg( $key, $paged + 1, $base_url )
				: add_query_arg( $key, $paged + 1 );

			return '<div class="flexa-pagination-loadmore"'
				. ' data-next="' . esc_url( $next ) . '"'
				. ' data-page="' . esc_attr( (string) $paged ) . '"'
				. ' data-total="' . esc_attr( (string) $total_pages ) . '"'
				. ' data-key="' . esc_attr( $key ) . '">'
				. '<button type="button" class="flexa-pagination-loadmore__btn wp-element-button">'
				. esc_html( (string) $cfg['loadMoreText'] ) . '</button></div>';
		}

		return '';
	}

	/**
	 * The screen-reader result count that announces a filter/search change.
	 *
	 * @param int $total Matching posts.
	 * @return string
	 */
	public static function render_status( int $total, bool $visible = false, string $text = '' ): string {
		/* translators: %s: number of posts found. */
		$template = '' !== trim( $text ) ? $text : __( '%s posts found', 'flexa-block' );

		// The count is ALWAYS in the DOM and always a live region — a visitor who just
		// filtered has to be told what happened, and for a screen-reader user this line
		// is the only thing that says it. The author's toggle only decides whether it is
		// also shown; it can never remove it.
		$class = 'flexa-post-grid__status' . ( $visible ? '' : ' screen-reader-text' );

		return '<p class="' . esc_attr( $class ) . '" role="status" aria-live="polite"'
			. ' data-template="' . esc_attr( $template ) . '">'
			. esc_html( str_replace( '%s', number_format_i18n( $total ), $template ) )
			. '</p>';
	}

	/**
	 * Multibyte-safe truncation.
	 *
	 * @param string $value  Value.
	 * @param int    $length Max length.
	 * @return string
	 */
	private static function substr( string $value, int $length ): string {
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, $length );
		}
		return substr( $value, 0, $length );
	}
}
