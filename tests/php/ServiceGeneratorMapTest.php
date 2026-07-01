<?php
/**
 * Tests that CSS_Generator_Service derives its generator map from the block
 * catalog (Block_Manager) rather than a hard-coded list — this is what lets a
 * new block be wired up by declaring it in ONE place.
 *
 * @package Flexa\Block
 */

use PHPUnit\Framework\TestCase;
use Flexa\Block\CSS_Generator_Service;
use Flexa\Block\CSS_Generators\Container_CSS;

/**
 * @covers \Flexa\Block\CSS_Generator_Service
 */
class ServiceGeneratorMapTest extends TestCase {

	protected function tearDown(): void {
		CSS_Generator_Service::reset_generators();
		parent::tearDown();
	}

	public function test_map_is_built_from_block_catalog(): void {
		CSS_Generator_Service::reset_generators();

		$map = CSS_Generator_Service::get_generators();

		// The container block declares its generator in Block_Manager::BASE_BLOCKS.
		$this->assertArrayHasKey( 'flexa/container', $map );
		$this->assertSame( Container_CSS::class, $map['flexa/container'] );
	}

	public function test_map_is_cached_between_calls(): void {
		CSS_Generator_Service::reset_generators();
		$first  = CSS_Generator_Service::get_generators();
		$second = CSS_Generator_Service::get_generators();
		$this->assertSame( $first, $second );
	}
}
