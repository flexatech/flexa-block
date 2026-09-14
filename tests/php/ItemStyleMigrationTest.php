<?php
/**
 * Tests for the one-time item-style migration transform.
 *
 * Only the pure, DB-free surface is exercised here (the harness has no
 * parse_blocks/serialize_blocks/wp_update_post): `migrate_attrs` (the per-block
 * transform) and `migrate_blocks` (the recursive tree walk). The batch runner,
 * cron scheduling and wp_update_post orchestration are thin wrappers verified by
 * running the plugin.
 *
 * @package Flexa\Block
 */

use PHPUnit\Framework\TestCase;
use Flexa\Block\CSS_Generator_Service;
use Flexa\Block\Item_Style_Migration;

/**
 * @covers \Flexa\Block\Item_Style_Migration
 */
class ItemStyleMigrationTest extends TestCase {

	protected function setUp(): void {
		// Real block.json defaults are needed so the transform sees the same
		// effective border/shadow the editor sees (esp. the feeds' non-empty
		// default border). Prime every target block and clear the service's
		// defaults cache so nothing leaks between tests.
		$this->clearDefaultsCache();
		foreach ( Item_Style_Migration::TARGET_BLOCKS as $name ) {
			\WP_Block_Type_Registry::prime_from_block_json( $name );
		}
	}

	protected function tearDown(): void {
		\WP_Block_Type_Registry::reset_primed();
		$this->clearDefaultsCache();
		parent::tearDown();
	}

	private function clearDefaultsCache(): void {
		$prop = new \ReflectionProperty( CSS_Generator_Service::class, 'defaults_cache' );
		$prop->setAccessible( true );
		$prop->setValue( null, [] );
	}

	/** A responsive border with a real value on desktop. */
	private function sampleBorder(): array {
		return [
			'desktop' => [
				'style'  => 'solid',
				'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
				'color'  => [ 'light' => '#e5e7eb', 'dark' => '#374151' ],
				'radius' => [ 'topLeft' => '8', 'topRight' => '8', 'bottomRight' => '8', 'bottomLeft' => '8', 'unit' => 'px' ],
			],
			'tablet'  => [],
			'mobile'  => [],
		];
	}

	/** An enabled box shadow. */
	private function sampleShadow(): array {
		return [
			'enabled'    => true,
			'horizontal' => '0',
			'vertical'   => '6',
			'blur'       => '16',
			'spread'     => '0',
			'color'      => [ 'light' => 'rgba(0,0,0,0.12)', 'dark' => 'rgba(0,0,0,0.6)' ],
			'inset'      => false,
		];
	}

	public function test_custom_border_and_shadow_move_to_item(): void {
		$border = $this->sampleBorder();
		$shadow = $this->sampleShadow();

		$out = Item_Style_Migration::migrate_attrs( 'flexa/post-grid', [
			'blockId'   => 'a',
			'border'    => $border,
			'boxShadow' => $shadow,
		] );

		$this->assertNotNull( $out );
		$this->assertTrue( $out['itemStyleMigrated'] );
		// Values moved onto the per-item attributes. The stored value is the
		// defaults-merged effective one (same as the JS hook), so compare by
		// key/value rather than key order.
		$this->assertSame( $border, $out['itemBorder'] );
		$this->assertEquals( $shadow, $out['itemBoxShadow'] );
		$this->assertTrue( $out['itemBoxShadow']['enabled'] );
		// Legacy attributes cleared so they no longer paint anything.
		$this->assertSame( Item_Style_Migration::EMPTY_BORDER, $out['border'] );
		$this->assertFalse( $out['boxShadow']['enabled'] );
	}

	public function test_feed_default_border_moves_even_when_unstored(): void {
		// instagram-feed ships a NON-EMPTY default border. A legacy post that used
		// the default has no `border` key at all — the transform must still capture
		// it (via merged defaults) onto itemBorder, else it would jump to the wrapper.
		$default_border = CSS_Generator_Service::merge_defaults( 'flexa/instagram-feed', [] )['border'];
		$this->assertNotSame( '', $default_border['desktop']['style'] ?? '', 'Guard: instagram default border should be non-empty.' );

		$out = Item_Style_Migration::migrate_attrs( 'flexa/instagram-feed', [ 'blockId' => 'a' ] );

		$this->assertNotNull( $out );
		$this->assertTrue( $out['itemStyleMigrated'] );
		$this->assertSame( $default_border, $out['itemBorder'] );
		$this->assertSame( Item_Style_Migration::EMPTY_BORDER, $out['border'] );
	}

	public function test_empty_block_only_sets_flag(): void {
		// post-grid's default border/shadow are empty/disabled → nothing to move,
		// but the flag still flips so the wrapper semantics apply going forward.
		$out = Item_Style_Migration::migrate_attrs( 'flexa/post-grid', [ 'blockId' => 'a' ] );

		$this->assertNotNull( $out );
		$this->assertTrue( $out['itemStyleMigrated'] );
		$this->assertArrayNotHasKey( 'itemBorder', $out );
		$this->assertArrayNotHasKey( 'itemBoxShadow', $out );
		$this->assertArrayNotHasKey( 'border', $out );
		$this->assertArrayNotHasKey( 'boxShadow', $out );
	}

	public function test_disabled_shadow_not_moved(): void {
		$out = Item_Style_Migration::migrate_attrs( 'flexa/post-grid', [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => false, 'horizontal' => '0', 'vertical' => '4', 'color' => [ 'light' => 'rgba(0,0,0,0.1)' ] ],
		] );

		$this->assertNotNull( $out );
		$this->assertArrayNotHasKey( 'itemBoxShadow', $out );
		// The stored (disabled) shadow is left untouched.
		$this->assertFalse( $out['boxShadow']['enabled'] );
	}

	public function test_already_migrated_returns_null(): void {
		$out = Item_Style_Migration::migrate_attrs( 'flexa/post-grid', [
			'blockId'           => 'a',
			'itemStyleMigrated' => true,
			'border'            => $this->sampleBorder(),
		] );
		$this->assertNull( $out );
	}

	public function test_non_target_block_returns_null(): void {
		$out = Item_Style_Migration::migrate_attrs( 'flexa/container', [
			'blockId' => 'a',
			'border'  => $this->sampleBorder(),
		] );
		$this->assertNull( $out );
	}

	public function test_nested_target_block_is_migrated(): void {
		$blocks = [
			[
				'blockName'   => 'flexa/container',
				'attrs'       => [ 'blockId' => 'c' ],
				'innerBlocks' => [
					[
						'blockName'   => 'flexa/post-grid',
						'attrs'       => [ 'blockId' => 'a', 'border' => $this->sampleBorder() ],
						'innerBlocks' => [],
					],
				],
			],
		];

		list( $out, $changed ) = Item_Style_Migration::migrate_blocks( $blocks );

		$this->assertTrue( $changed );
		$nested = $out[0]['innerBlocks'][0]['attrs'];
		$this->assertTrue( $nested['itemStyleMigrated'] );
		$this->assertSame( $this->sampleBorder(), $nested['itemBorder'] );
		$this->assertSame( Item_Style_Migration::EMPTY_BORDER, $nested['border'] );
		// The container itself is untouched.
		$this->assertArrayNotHasKey( 'itemStyleMigrated', $out[0]['attrs'] );
	}

	public function test_tree_with_nothing_to_migrate_reports_unchanged(): void {
		$blocks = [
			[
				'blockName'   => 'flexa/post-grid',
				'attrs'       => [ 'blockId' => 'a', 'itemStyleMigrated' => true ],
				'innerBlocks' => [],
			],
			[
				'blockName'   => 'core/paragraph',
				'attrs'       => [],
				'innerBlocks' => [],
			],
		];

		list( , $changed ) = Item_Style_Migration::migrate_blocks( $blocks );
		$this->assertFalse( $changed );
	}
}
