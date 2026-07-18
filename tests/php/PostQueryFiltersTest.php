<?php
/**
 * Tests for the Post Grid query core — the visitor-filter contract.
 *
 * These cover the part of the feature that has to be right even when someone edits
 * the URL by hand: what a visitor's search/term params are allowed to do to the
 * query. The load-bearing claim is that a visitor can only ever NARROW the author's
 * taxonomy constraint, never escape it — so that gets tested from both sides
 * (a subset narrows, a term outside the constraint is ignored and the author's
 * clause survives intact).
 *
 * Also pinned here: the paging offset in load-more mode, which used to be dropped
 * (page 2 returned page 1's posts, so the button appended duplicates).
 *
 * @package Flexa\Block
 */

use PHPUnit\Framework\TestCase;
use Flexa\Block\Post_Query;

/**
 * @covers \Flexa\Block\Post_Query
 */
class PostQueryFiltersTest extends TestCase {

	private const GID = 'abc123';

	/**
	 * A minimal config, as config() would produce it.
	 *
	 * @param array $overrides Values to override.
	 * @return array
	 */
	private function cfg( array $overrides = [] ): array {
		return array_merge(
			[
				'postType'       => 'post',
				'perPage'        => 6,
				'offset'         => 0,
				'orderBy'        => 'date',
				'order'          => 'DESC',
				'taxonomy'       => '',
				'terms'          => '',
				'searchScope'    => 'title_excerpt',
				'paginationType' => 'numbered',
			],
			$overrides
		);
	}

	/**
	 * A slug→id resolver standing in for the taxonomy tables.
	 *
	 * @return callable
	 */
	private function resolver(): callable {
		$map = [
			'category' => [
				'news'  => 10,
				'tips'  => 11,
				'promo' => 12,
			],
			'post_tag' => [
				'php' => 20,
				'js'  => 21,
			],
		];
		return static function ( string $taxonomy, array $slugs ) use ( $map ): array {
			$ids = [];
			foreach ( $slugs as $slug ) {
				if ( isset( $map[ $taxonomy ][ $slug ] ) ) {
					$ids[] = $map[ $taxonomy ][ $slug ];
				}
			}
			return $ids;
		};
	}

	/* ---------------------------------------------------------------------
	 * parse_filters()
	 * ------------------------------------------------------------------ */

	public function testParsesSearchAndCommaSeparatedTerms(): void {
		$filters = Post_Query::parse_filters(
			[
				's_abc123'           => 'hello world',
				'tx_abc123_category' => 'news,tips',
			],
			self::GID,
			[ 'category' ]
		);

		$this->assertSame( 'hello world', $filters['s'] );
		$this->assertSame( [ 'category' => [ 'news', 'tips' ] ], $filters['tax'] );
	}

	public function testParsesTheArrayShapeOfAMultiSelect(): void {
		$filters = Post_Query::parse_filters(
			[ 'tx_abc123_category' => [ 'news', 'tips' ] ],
			self::GID,
			[ 'category' ]
		);

		$this->assertSame( [ 'category' => [ 'news', 'tips' ] ], $filters['tax'] );
	}

	public function testDropsATaxonomyNoFilterBlockOffers(): void {
		// The page renders no post_tag filter, so a hand-crafted post_tag param is
		// not "a filter the UI forgot" — it is a request the page never offered.
		$filters = Post_Query::parse_filters(
			[
				'tx_abc123_category' => 'news',
				'tx_abc123_post_tag' => 'php',
			],
			self::GID,
			[ 'category' ]
		);

		$this->assertArrayHasKey( 'category', $filters['tax'] );
		$this->assertArrayNotHasKey( 'post_tag', $filters['tax'] );
	}

	public function testIgnoresAnotherGridsParams(): void {
		$filters = Post_Query::parse_filters(
			[
				's_other'           => 'hello',
				'tx_other_category' => 'news',
			],
			self::GID,
			[ 'category' ]
		);

		$this->assertSame( '', $filters['s'] );
		$this->assertSame( [], $filters['tax'] );
	}

	public function testTruncatesRatherThanRejectsOversizedInput(): void {
		$filters = Post_Query::parse_filters(
			[
				's_abc123'           => str_repeat( 'a', 250 ),
				'tx_abc123_category' => implode( ',', array_map( static fn ( $i ) => 'term-' . $i, range( 1, 40 ) ) ),
			],
			self::GID,
			[ 'category' ]
		);

		$this->assertSame( Post_Query::MAX_SEARCH_LEN, strlen( $filters['s'] ) );
		$this->assertCount( Post_Query::MAX_TERMS, $filters['tax']['category'] );
	}

	public function testDeduplicatesTermSlugs(): void {
		$filters = Post_Query::parse_filters(
			[ 'tx_abc123_category' => 'news,news,tips' ],
			self::GID,
			[ 'category' ]
		);

		$this->assertSame( [ 'news', 'tips' ], $filters['tax']['category'] );
	}

	/* ---------------------------------------------------------------------
	 * intersect_terms()
	 * ------------------------------------------------------------------ */

	public function testIntersectKeepsASubset(): void {
		$this->assertSame( [ 10 ], Post_Query::intersect_terms( [ 10 ], [ 10, 11 ] ) );
	}

	public function testIntersectNarrowsASuperset(): void {
		$this->assertSame( [ 10, 11 ], Post_Query::intersect_terms( [ 10, 11, 12 ], [ 10, 11 ] ) );
	}

	public function testIntersectOfDisjointSetsIsEmpty(): void {
		$this->assertSame( [], Post_Query::intersect_terms( [ 12 ], [ 10, 11 ] ) );
	}

	/* ---------------------------------------------------------------------
	 * build_tax_query() — the never-widen guarantee.
	 * ------------------------------------------------------------------ */

	public function testNoConstraintAndNoFilterProducesNoTaxQuery(): void {
		$tax = Post_Query::build_tax_query( $this->cfg(), [ 's' => '', 'tax' => [] ], $this->resolver() );
		$this->assertSame( [], $tax );
	}

	public function testVisitorFilterAloneBecomesTheOnlyClause(): void {
		$tax = Post_Query::build_tax_query(
			$this->cfg(),
			[ 's' => '', 'tax' => [ 'category' => [ 'news' ] ] ],
			$this->resolver()
		);

		$this->assertCount( 1, $tax );
		$this->assertSame( 'category', $tax[0]['taxonomy'] );
		$this->assertSame( [ 10 ], $tax[0]['terms'] );
	}

	public function testVisitorFilterOnTheAuthorsTaxonomyIsIntersected(): void {
		$cfg = $this->cfg( [ 'taxonomy' => 'category', 'terms' => '10,11' ] );

		$tax = Post_Query::build_tax_query(
			$cfg,
			[ 's' => '', 'tax' => [ 'category' => [ 'news' ] ] ],
			$this->resolver()
		);

		$this->assertSame( 'AND', $tax['relation'] );
		$this->assertSame( [ 10, 11 ], $tax[0]['terms'], 'The author clause is always present.' );
		$this->assertSame( [ 10 ], $tax[1]['terms'], 'The visitor clause narrows within it.' );
	}

	public function testATermOutsideTheAuthorsConstraintIsIgnored(): void {
		// The whole point: a hand-crafted URL asking for `promo` (which the author's
		// query excludes) must not widen the result set — and must not blank it
		// either. The visitor clause is dropped and the author's clause stands.
		$cfg = $this->cfg( [ 'taxonomy' => 'category', 'terms' => '10,11' ] );

		$tax = Post_Query::build_tax_query(
			$cfg,
			[ 's' => '', 'tax' => [ 'category' => [ 'promo' ] ] ],
			$this->resolver()
		);

		$this->assertCount( 1, $tax );
		$this->assertSame( [ 10, 11 ], $tax[0]['terms'] );
		$this->assertArrayNotHasKey( 'relation', $tax );
	}

	public function testAFilterOnADifferentTaxonomyIsAndedOn(): void {
		$cfg = $this->cfg( [ 'taxonomy' => 'category', 'terms' => '10' ] );

		$tax = Post_Query::build_tax_query(
			$cfg,
			[ 's' => '', 'tax' => [ 'post_tag' => [ 'php' ] ] ],
			$this->resolver()
		);

		$this->assertSame( 'AND', $tax['relation'] );
		$this->assertSame( 'category', $tax[0]['taxonomy'] );
		$this->assertSame( 'post_tag', $tax[1]['taxonomy'] );
		$this->assertSame( [ 20 ], $tax[1]['terms'] );
	}

	public function testAuthorTermsGivenAsSlugsAreResolved(): void {
		$cfg = $this->cfg( [ 'taxonomy' => 'category', 'terms' => 'news,tips' ] );

		$tax = Post_Query::build_tax_query( $cfg, [ 's' => '', 'tax' => [] ], $this->resolver() );

		$this->assertSame( [ 10, 11 ], $tax[0]['terms'] );
	}

	/* ---------------------------------------------------------------------
	 * build_args()
	 * ------------------------------------------------------------------ */

	public function testSearchBecomesTheQuerySearchArg(): void {
		$args = Post_Query::build_args( $this->cfg(), [ 's' => 'hello', 'tax' => [] ], 1 );
		$this->assertSame( 'hello', $args['s'] );
	}

	public function testSearchIsScopedToTitleAndExcerptByDefault(): void {
		// WordPress searches the raw post_content, which on a block site contains the
		// block markup — so an unscoped search for "container" matches every post that
		// merely CONTAINS a container block. The scope query var is what stops that.
		$args = Post_Query::build_args( $this->cfg(), [ 's' => 'container', 'tax' => [] ], 1 );

		$this->assertSame( 'title_excerpt', $args[ Post_Query::SCOPE_QUERY_VAR ] );
	}

	public function testTheScopeIsDroppedWhenTheAuthorAsksForAFullSearch(): void {
		$args = Post_Query::build_args(
			$this->cfg( [ 'searchScope' => 'all' ] ),
			[ 's' => 'container', 'tax' => [] ],
			1
		);

		$this->assertArrayNotHasKey( Post_Query::SCOPE_QUERY_VAR, $args );
	}

	public function testNoSearchMeansNoScopeQueryVar(): void {
		$args = Post_Query::build_args( $this->cfg(), [ 's' => '', 'tax' => [] ], 1 );
		$this->assertArrayNotHasKey( Post_Query::SCOPE_QUERY_VAR, $args );
	}

	public function testNumberedPaginationAdvancesTheOffset(): void {
		$args = Post_Query::build_args( $this->cfg( [ 'perPage' => 6 ] ), [ 's' => '', 'tax' => [] ], 3 );
		$this->assertSame( 12, $args['offset'] );
	}

	public function testLoadMorePaginationAdvancesTheOffset(): void {
		// Regression: load-more used to share the un-paged branch, so page 2 returned
		// page 1's posts and the button appended duplicate cards.
		$args = Post_Query::build_args(
			$this->cfg( [ 'paginationType' => 'loadmore', 'perPage' => 6 ] ),
			[ 's' => '', 'tax' => [] ],
			3
		);

		$this->assertSame( 12, $args['offset'] );
	}

	public function testWithoutPaginationTheOffsetNeverAdvances(): void {
		$args = Post_Query::build_args(
			$this->cfg( [ 'paginationType' => 'none', 'perPage' => 6, 'offset' => 2 ] ),
			[ 's' => '', 'tax' => [] ],
			3
		);

		$this->assertSame( 2, $args['offset'] );
	}

	public function testTheAuthorsBaseOffsetIsAddedToThePagingOffset(): void {
		$args = Post_Query::build_args( $this->cfg( [ 'perPage' => 6, 'offset' => 2 ] ), [ 's' => '', 'tax' => [] ], 2 );
		$this->assertSame( 8, $args['offset'] );
	}

	public function testPerPageAndPageAreClamped(): void {
		$args = Post_Query::build_args( $this->cfg( [ 'perPage' => 5000 ] ), [ 's' => '', 'tax' => [] ], 99999 );

		$this->assertSame( Post_Query::MAX_PER_PAGE, $args['posts_per_page'] );
		$this->assertSame( ( Post_Query::MAX_PAGE - 1 ) * Post_Query::MAX_PER_PAGE, $args['offset'] );
	}

	public function testTheQueryIsAlwaysLimitedToPublishedPosts(): void {
		$args = Post_Query::build_args( $this->cfg(), [ 's' => '', 'tax' => [] ], 1 );
		$this->assertSame( 'publish', $args['post_status'] );
	}

	/* ---------------------------------------------------------------------
	 * Query-arg keys (the contract the view scripts mirror).
	 * ------------------------------------------------------------------ */

	public function testQueryArgKeysAreNamespacedPerGrid(): void {
		$this->assertSame( 'pg_abc123', Post_Query::page_key( self::GID ) );
		$this->assertSame( 's_abc123', Post_Query::search_key( self::GID ) );
		$this->assertSame( 'tx_abc123_category', Post_Query::tax_key( self::GID, 'category' ) );
	}

	/* ---------------------------------------------------------------------
	 * The allow-list is built from the SAVED filter blocks — and Gutenberg does
	 * not save an attribute that still equals its block.json default.
	 * ------------------------------------------------------------------ */

	/**
	 * The Category field's `taxonomy` defaults to `category`, so it serializes as a
	 * bare `<!-- wp:flexa/filter-taxonomy -->` with NO taxonomy attribute at all.
	 * Reading those parsed attrs raw yields null, `category` never reaches the
	 * allow-list, and every `tx_<gid>_category` param the page itself renders is
	 * dropped — the filter looks completely dead while search still works. So the
	 * defaults have to be merged in before the taxonomy is read.
	 */
	public function testTaxonomyDefaultSurvivesAnAttributeGutenbergOmitsOnSave(): void {
		WP_Block_Type_Registry::prime_from_block_json( 'flexa/filter-taxonomy' );

		$saved  = []; // Exactly what parse_blocks() hands back for a default Category field.
		$merged = Flexa\Block\CSS_Generator_Service::merge_defaults( 'flexa/filter-taxonomy', $saved );

		$this->assertSame( 'category', $merged['taxonomy'] ?? null );
	}

	/** With `category` in the allow-list, the visitor's param is honoured... */
	public function testAnAllowedTaxonomyParamReachesTheQuery(): void {
		$filters = Post_Query::parse_filters(
			[ 'tx_' . self::GID . '_category' => 'design' ],
			self::GID,
			[ 'category', 'post_tag' ]
		);

		$this->assertSame( [ 'category' => [ 'design' ] ], $filters['tax'] );
	}

	/** ...and one the page never offered is still dropped, however the URL is written. */
	public function testATaxonomyTheAllowListOmitsIsDropped(): void {
		$filters = Post_Query::parse_filters(
			[
				'tx_' . self::GID . '_category'    => 'design',
				'tx_' . self::GID . '_post_format' => 'aside',
			],
			self::GID,
			[ 'category' ]
		);

		$this->assertSame( [ 'category' => [ 'design' ] ], $filters['tax'] );
		$this->assertArrayNotHasKey( 'post_format', $filters['tax'] );
	}

	/**
	 * A grid id now looks like `fx-1a2b3c4d` — it is generated once and kept, rather
	 * than derived from the editor's clientId (which changes on every reload, taking
	 * the Post Filter's saved target with it). The dash is the part worth pinning: the
	 * query args are built by concatenation, so an id that survives `sanitize_html_class`
	 * but not the arg round-trip would filter nothing.
	 */
	public function testAPrefixedGridIdRoundTripsThroughTheQueryArgs(): void {
		$gid = 'fx-1a2b3c4d';

		$this->assertSame( 'pg_fx-1a2b3c4d', Post_Query::page_key( $gid ) );
		$this->assertSame( 's_fx-1a2b3c4d', Post_Query::search_key( $gid ) );
		$this->assertSame( 'tx_fx-1a2b3c4d_category', Post_Query::tax_key( $gid, 'category' ) );

		$filters = Post_Query::parse_filters(
			[
				Post_Query::search_key( $gid )         => 'hello',
				Post_Query::tax_key( $gid, 'category' ) => 'design',
			],
			$gid,
			[ 'category' ]
		);

		$this->assertSame( 'hello', $filters['s'] );
		$this->assertSame( [ 'category' => [ 'design' ] ], $filters['tax'] );
		$this->assertSame( 3, Post_Query::paged( [ Post_Query::page_key( $gid ) => '3' ], $gid ) );
	}

	/**
	 * The result count is a live region whatever the author picked: a screen-reader user
	 * who just filtered has nothing else to tell them what happened. The toggle only
	 * decides whether it is also SEEN — it must never be able to remove it.
	 */
	public function testTheResultCountIsAlwaysAnnouncedAndOnlyOptionallyShown(): void {
		$hidden = Post_Query::render_status( 12 );
		$shown  = Post_Query::render_status( 12, true );

		foreach ( [ $hidden, $shown ] as $status ) {
			$this->assertStringContainsString( 'role="status"', $status );
			$this->assertStringContainsString( 'aria-live="polite"', $status );
			$this->assertStringContainsString( '12 posts found', $status );
		}

		$this->assertStringContainsString( 'screen-reader-text', $hidden );
		$this->assertStringNotContainsString( 'screen-reader-text', $shown );
	}

	/**
	 * The author's own wording. `data-template` carries it to the view script, which
	 * rewrites the line on every filter — so the count a visitor sees after filtering
	 * is worded exactly like the one they saw before it.
	 */
	public function testTheResultCountWordingCanBeTheAuthorsOwn(): void {
		$status = Post_Query::render_status( 7, true, '%s bài viết' );

		$this->assertStringContainsString( 'data-template="%s bài viết"', $status );
		$this->assertStringContainsString( '>7 bài viết<', $status );
		$this->assertStringNotContainsString( 'posts found', $status );
	}

	/**
	 * "0 posts found" above "No posts found." is one fact told twice, in two voices —
	 * it reads like two separate problems. With the count shown, the empty state shuts
	 * up. It does NOT disappear: it is the node the view script swaps back into when
	 * results return, so it stays in the DOM, hidden and silent.
	 */
	public function testTheEmptyStateSaysNothingWhenTheCountAlreadyDid(): void {
		$quiet = Post_Query::render_grid( '', true );
		$loud  = Post_Query::render_grid( '', false );

		$this->assertStringContainsString( 'flexa-post-grid__empty', $quiet );
		$this->assertStringContainsString( 'hidden', $quiet );
		$this->assertStringNotContainsString( 'No posts found', $quiet );

		$this->assertStringContainsString( 'No posts found', $loud );
	}
}
