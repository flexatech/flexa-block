<?php
/**
 * Tests for grouping front-end inline saves into revision sessions.
 *
 * @package Flexa\Block
 */

use Flexa\Block\Inline_Revision_Session;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Flexa\Block\Inline_Revision_Session
 */
class InlineRevisionSessionTest extends TestCase {

	/** @var WP_Post */
	private $post;

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['flexa_test_filters']            = [];
		$GLOBALS['flexa_test_posts']              = [];
		$GLOBALS['flexa_test_transients']         = [];
		$GLOBALS['flexa_test_post_meta']          = [];
		$GLOBALS['flexa_test_revision_checks']    = [];
		$GLOBALS['flexa_test_revision_snapshots'] = [];
		$GLOBALS['flexa_test_update_filters']     = [];
		$GLOBALS['flexa_test_user_id']            = 7;
		$GLOBALS['flexa_test_revisions_enabled']  = true;
		$GLOBALS['flexa_test_update_error']       = null;

		$this->post = new WP_Post(
			[
				'ID'           => 42,
				'post_type'    => 'page',
				'post_parent'  => 0,
				'post_content' => 'before',
			]
		);
		$GLOBALS['flexa_test_posts'][42] = $this->post;
	}

	protected function tearDown(): void {
		$GLOBALS['flexa_test_filters'] = [];
		parent::tearDown();
	}

	public function test_first_save_creates_a_baseline_and_suppresses_only_its_automatic_revision(): void {
		$result = Inline_Revision_Session::update_post(
			$this->post,
			[ 'ID' => 42, 'post_content' => 'first edit' ]
		);

		$this->assertSame( 42, $result );
		$this->assertCount( 1, $GLOBALS['flexa_test_revision_snapshots'] );
		$this->assertSame( 'before', $GLOBALS['flexa_test_revision_snapshots'][9000] );
		$this->assertSame( [ false ], $GLOBALS['flexa_test_revision_checks'] );
		$this->assertSame(
			[
				'target_general' => 0,
				'target_dynamic' => 0,
				'other_general'  => 10,
				'other_dynamic'  => 10,
			],
			$GLOBALS['flexa_test_update_filters'][0]
		);
		$this->assertSame( 10, apply_filters( 'wp_revisions_to_keep', 10, $this->post ) );
		$this->assertSame( 10, apply_filters( 'wp_page_revisions_to_keep', 10, $this->post ) );
		$this->assertSame( 600, $GLOBALS['flexa_test_transients']['flexa_ie_rev_42_7']['expiration'] );
		$this->assertSame(
			'front-end-inline',
			$GLOBALS['flexa_test_post_meta'][9000][ Inline_Revision_Session::REVISION_META ]['source']
		);
	}

	public function test_repeated_saves_reuse_the_same_immutable_baseline(): void {
		Inline_Revision_Session::update_post( $this->post, [ 'ID' => 42, 'post_content' => 'first edit' ] );
		Inline_Revision_Session::update_post( $this->post, [ 'ID' => 42, 'post_content' => 'second edit' ] );

		$this->assertCount( 1, $GLOBALS['flexa_test_revision_snapshots'] );
		$this->assertSame( 'before', $GLOBALS['flexa_test_revision_snapshots'][9000] );
		$this->assertSame( 'second edit', $this->post->post_content );
		$this->assertCount( 2, $GLOBALS['flexa_test_update_filters'] );
	}

	public function test_sessions_are_separate_per_user(): void {
		Inline_Revision_Session::update_post( $this->post, [ 'ID' => 42, 'post_content' => 'user seven' ] );
		$GLOBALS['flexa_test_user_id'] = 8;
		Inline_Revision_Session::update_post( $this->post, [ 'ID' => 42, 'post_content' => 'user eight' ] );

		$this->assertCount( 2, $GLOBALS['flexa_test_revision_snapshots'] );
		$this->assertArrayHasKey( 'flexa_ie_rev_42_7', $GLOBALS['flexa_test_transients'] );
		$this->assertArrayHasKey( 'flexa_ie_rev_42_8', $GLOBALS['flexa_test_transients'] );
		$this->assertSame( 'before', $GLOBALS['flexa_test_revision_snapshots'][9000] );
		$this->assertSame( 'user seven', $GLOBALS['flexa_test_revision_snapshots'][9001] );
	}

	public function test_an_expired_session_starts_a_new_baseline(): void {
		$short_window = static function () {
			return 5;
		};
		add_filter( 'flexa_block_inline_revision_session_window', $short_window );
		Inline_Revision_Session::update_post( $this->post, [ 'ID' => 42, 'post_content' => 'first edit' ] );

		$GLOBALS['flexa_test_transients']['flexa_ie_rev_42_7']['value']['last_activity'] -= 6;
		Inline_Revision_Session::update_post( $this->post, [ 'ID' => 42, 'post_content' => 'after pause' ] );

		$this->assertCount( 2, $GLOBALS['flexa_test_revision_snapshots'] );
		$this->assertSame( 'first edit', $GLOBALS['flexa_test_revision_snapshots'][9001] );
		$this->assertSame( 5, $GLOBALS['flexa_test_transients']['flexa_ie_rev_42_7']['expiration'] );
	}

	public function test_disabled_revisions_keep_core_update_behaviour(): void {
		$GLOBALS['flexa_test_revisions_enabled'] = false;

		$result = Inline_Revision_Session::update_post(
			$this->post,
			[ 'ID' => 42, 'post_content' => 'edit' ]
		);

		$this->assertSame( 42, $result );
		$this->assertSame( 10, $GLOBALS['flexa_test_update_filters'][0]['target_general'] );
		$this->assertSame( 10, $GLOBALS['flexa_test_update_filters'][0]['target_dynamic'] );
		$this->assertSame( [], $GLOBALS['flexa_test_transients'] );
	}

	public function test_filters_are_restored_when_the_update_fails(): void {
		$GLOBALS['flexa_test_update_error'] = new WP_Error( 'save_failed', 'No write.' );

		$result = Inline_Revision_Session::update_post(
			$this->post,
			[ 'ID' => 42, 'post_content' => 'edit' ]
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 10, apply_filters( 'wp_revisions_to_keep', 10, $this->post ) );
		$this->assertSame( 10, apply_filters( 'wp_page_revisions_to_keep', 10, $this->post ) );
	}
}
