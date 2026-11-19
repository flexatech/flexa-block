<?php
/**
 * Tests for the front-end edit log.
 *
 * @package Flexa\Block
 */

use Flexa\Block\Inline_Edit_Log;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Flexa\Block\Inline_Edit_Log
 */
class InlineEditLogTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['flexa_test_filters']   = [];
		$GLOBALS['flexa_test_post_meta'] = [];
		$GLOBALS['flexa_test_cron']      = [];
	}

	/**
	 * A save's facts.
	 *
	 * @param array<string,mixed> $over Overrides.
	 * @return array<string,mixed>
	 */
	private function facts( array $over = [] ): array {
		return $over + [
			'entry_id'    => 'e1',
			'time_gmt'    => '2026-11-19 10:00:00',
			'user_id'     => 7,
			'user_name'   => 'Admin',
			'user_role'   => 'administrator',
			'block_name'  => 'flexa/faq',
			'block_id'    => 'abc',
			'attr'        => 'items',
			'index'       => 2,
			'subindex'    => null,
			'key'         => 'question',
			'store_id'    => 42,
			'store_type'  => 'page',
			'post_id'     => 42,
			'before'      => 'Old',
			'after'       => 'New',
			'revision_id' => 9000,
		];
	}

	public function test_an_entry_says_who_when_where_and_before_and_after(): void {
		$e = Inline_Edit_Log::build_entry( $this->facts() );

		$this->assertSame( 7, $e['user_id'] );
		$this->assertSame( 'Admin', $e['user_name'] );
		$this->assertSame( 'administrator', $e['user_role'] );
		$this->assertSame( '2026-11-19 10:00:00', $e['time'] );
		$this->assertSame( 'flexa/faq', $e['block_name'] );
		$this->assertSame( 'abc', $e['block_id'] );
		$this->assertSame( 'items', $e['attr'] );
		$this->assertSame( 2, $e['index'] );
		$this->assertNull( $e['subindex'] );
		$this->assertSame( 'question', $e['key'] );
		$this->assertSame( 'Old', $e['before'] );
		$this->assertSame( 'New', $e['after'] );
		$this->assertSame( 9000, $e['revision_id'] );
		$this->assertFalse( $e['truncated'] );
	}

	public function test_an_undo_is_recorded_as_its_own_entry_pointing_at_the_one_it_reverses(): void {
		Inline_Edit_Log::record( 42, $this->facts( [ 'entry_id' => 'orig' ] ) );
		Inline_Edit_Log::record( 42, $this->facts( [ 'entry_id' => 'u1', 'action' => 'undo', 'undo_of' => 'orig', 'before' => 'New', 'after' => 'Old' ] ) );

		$list = Inline_Edit_Log::entries( 42 );

		$this->assertSame( 'undo', $list[0]['action'] );
		$this->assertSame( 'orig', $list[0]['undo_of'] );
		$this->assertSame( 'edit', $list[1]['action'] );
		$this->assertTrue( Inline_Edit_Log::has_entry( 42, 'orig' ) );
		$this->assertFalse( Inline_Edit_Log::has_entry( 42, 'nope' ) );
		$this->assertFalse( Inline_Edit_Log::has_entry( 42, '' ) );
	}

	public function test_values_are_kept_whole_not_cut_to_a_display_length(): void {
		$long = str_repeat( 'a', 500 ) . '<strong>b</strong>';
		$e    = Inline_Edit_Log::build_entry( $this->facts( [ 'before' => $long ] ) );

		$this->assertSame( $long, $e['before'] );
		$this->assertFalse( $e['truncated'] );
	}

	public function test_a_value_past_the_cap_is_clipped_and_flagged(): void {
		$e = Inline_Edit_Log::build_entry( $this->facts( [ 'after' => str_repeat( 'x', Inline_Edit_Log::VALUE_LIMIT + 50 ) ] ) );

		$this->assertSame( Inline_Edit_Log::VALUE_LIMIT, mb_strlen( $e['after'] ) );
		$this->assertTrue( $e['truncated'] );
	}

	public function test_the_oldest_entries_are_dropped_past_the_limit(): void {
		$list = [];
		foreach ( [ 'a', 'b', 'c', 'd' ] as $id ) {
			$list = Inline_Edit_Log::append( $list, [ 'id' => $id ], 3 );
		}

		$this->assertSame( [ 'b', 'c', 'd' ], array_column( $list, 'id' ) );
	}

	public function test_record_stores_on_the_given_post_and_lists_newest_first(): void {
		Inline_Edit_Log::record( 42, $this->facts( [ 'entry_id' => 'first' ] ) );
		Inline_Edit_Log::record( 42, $this->facts( [ 'entry_id' => 'second' ] ) );

		$this->assertSame( [ 'second', 'first' ], array_column( Inline_Edit_Log::entries( 42 ), 'id' ) );
		$this->assertSame( [], Inline_Edit_Log::entries( 43 ) );
	}

	public function test_html_with_quotes_and_backslashes_reads_back_exactly(): void {
		$html = '<a href="https://x.test/?a=1&amp;b=2" title=\'q\'>C:\dir</a>';
		Inline_Edit_Log::record( 42, $this->facts( [ 'before' => $html, 'after' => $html ] ) );

		$this->assertSame( $html, Inline_Edit_Log::entries( 42 )[0]['before'] );
	}

	public function test_the_limit_can_be_changed_with_a_filter(): void {
		add_filter(
			'flexa_block_inline_edit_log_limit',
			static function () {
				return 2;
			}
		);
		foreach ( [ 'a', 'b', 'c' ] as $id ) {
			Inline_Edit_Log::record( 42, $this->facts( [ 'entry_id' => $id ] ) );
		}

		$this->assertSame( [ 'c', 'b' ], array_column( Inline_Edit_Log::entries( 42 ), 'id' ) );
	}

	public function test_nothing_is_recorded_without_a_post(): void {
		Inline_Edit_Log::record( 0, $this->facts() );

		$this->assertSame( [], $GLOBALS['flexa_test_post_meta'] );
	}

	public function test_entries_older_than_the_cutoff_are_pruned(): void {
		$old   = [ 'id' => 'old', 'time' => gmdate( 'Y-m-d H:i:s', time() - 31 * DAY_IN_SECONDS ) ];
		$fresh = [ 'id' => 'fresh', 'time' => gmdate( 'Y-m-d H:i:s', time() - 29 * DAY_IN_SECONDS ) ];

		$kept = Inline_Edit_Log::prune( [ $old, $fresh ], time() - 30 * DAY_IN_SECONDS );

		$this->assertSame( [ 'fresh' ], array_column( $kept, 'id' ) );
	}

	public function test_a_zero_cutoff_keeps_everything_and_unreadable_times_are_kept(): void {
		$items = [ [ 'id' => 'a', 'time' => '2000-01-01 00:00:00' ], [ 'id' => 'b', 'time' => 'garbage' ] ];

		$this->assertCount( 2, Inline_Edit_Log::prune( $items, 0 ) );
		$this->assertSame( [ 'b' ], array_column( Inline_Edit_Log::prune( $items, time() - DAY_IN_SECONDS ), 'id' ) );
	}

	public function test_retention_defaults_to_30_days_and_can_be_changed(): void {
		$this->assertSame( 30, Inline_Edit_Log::retention_days() );

		add_filter(
			'flexa_block_inline_edit_log_retention_days',
			static function () {
				return 7;
			}
		);
		$this->assertSame( 7, Inline_Edit_Log::retention_days() );
	}

	public function test_record_drops_entries_past_retention(): void {
		$GLOBALS['flexa_test_post_meta'][42][ Inline_Edit_Log::META ] = [
			[ 'id' => 'old', 'time' => gmdate( 'Y-m-d H:i:s', time() - 40 * DAY_IN_SECONDS ) ],
		];

		Inline_Edit_Log::record( 42, $this->facts( [ 'entry_id' => 'new', 'time_gmt' => gmdate( 'Y-m-d H:i:s' ) ] ) );

		$this->assertSame( [ 'new' ], array_column( Inline_Edit_Log::entries( 42 ), 'id' ) );
	}

	public function test_search_finds_text_in_before_after_title_and_person_ignoring_case_and_html(): void {
		$by_post = [
			42 => [
				[ 'id' => 'a', 'time' => '2026-11-01 10:00:00', 'user_name' => 'Ann', 'block_name' => 'flexa/faq', 'before' => 'Price <b>199</b>', 'after' => 'Price <b>249</b>' ],
				[ 'id' => 'b', 'time' => '2026-11-02 10:00:00', 'user_name' => 'Bob', 'block_name' => 'flexa/heading', 'before' => 'Hi', 'after' => 'Hello' ],
			],
		];
		$ids = static function ( array $rows ): array {
			return array_column( $rows, 'id' );
		};

		$this->assertSame( [ 'a' ], $ids( Inline_Edit_Log::merge( $by_post, '', '', '249' ) ) );
		$this->assertSame( [ 'a' ], $ids( Inline_Edit_Log::merge( $by_post, '', '', 'PRICE' ) ) );
		$this->assertSame( [], $ids( Inline_Edit_Log::merge( $by_post, '', '', '<b>' ) ) );
		$this->assertSame( [ 'b' ], $ids( Inline_Edit_Log::merge( $by_post, '', '', 'bob' ) ) );
		$this->assertSame( [ 'b', 'a' ], $ids( Inline_Edit_Log::merge( $by_post, '', '', 'pricing page', [ 42 => 'Our Pricing Page' ] ) ) );
		$this->assertSame( [ 'b', 'a' ], $ids( Inline_Edit_Log::merge( $by_post, '', '', '   ' ) ) );
		$this->assertSame( [ 'b' ], $ids( Inline_Edit_Log::merge( $by_post, 'Bob', '', 'hello' ) ) );
	}

	public function test_merge_lists_all_posts_newest_first_and_filters(): void {
		$by_post = [
			42 => [
				[ 'id' => 'a', 'time' => '2026-11-01 10:00:00', 'user_name' => 'Ann', 'block_name' => 'flexa/faq' ],
				[ 'id' => 'c', 'time' => '2026-11-03 10:00:00', 'user_name' => 'Bob', 'block_name' => 'flexa/heading' ],
			],
			77 => [
				[ 'id' => 'b', 'time' => '2026-11-02 10:00:00', 'user_name' => 'Ann', 'block_name' => 'flexa/heading', 'store_id' => 77 ],
			],
		];

		$this->assertSame( [ 'c', 'b', 'a' ], array_column( Inline_Edit_Log::merge( $by_post ), 'id' ) );
		$this->assertSame( [ 'b', 'a' ], array_column( Inline_Edit_Log::merge( $by_post, 'Ann' ), 'id' ) );
		$this->assertSame( [ 'c', 'b' ], array_column( Inline_Edit_Log::merge( $by_post, '', 'heading' ), 'id' ) );
		$this->assertSame( 42, Inline_Edit_Log::merge( $by_post )[2]['store_id'] );
	}

	public function test_recording_schedules_the_daily_sweep_once_and_unscheduling_removes_it(): void {
		Inline_Edit_Log::record( 42, $this->facts() );
		$first = $GLOBALS['flexa_test_cron'][ Inline_Edit_Log::GC_HOOK ] ?? null;
		Inline_Edit_Log::record( 42, $this->facts( [ 'entry_id' => 'e2' ] ) );

		$this->assertNotNull( $first );
		$this->assertSame( $first, $GLOBALS['flexa_test_cron'][ Inline_Edit_Log::GC_HOOK ] );

		Inline_Edit_Log::unschedule();
		$this->assertArrayNotHasKey( Inline_Edit_Log::GC_HOOK, $GLOBALS['flexa_test_cron'] );
	}
}
