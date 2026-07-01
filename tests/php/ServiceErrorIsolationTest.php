<?php
/**
 * Tests that CSS_Generator_Service isolates a failing block generator so one
 * broken block can never abort CSS for the rest of the post (nor the save).
 *
 * @package Flexa\Block
 */

use PHPUnit\Framework\TestCase;
use Flexa\Block\CSS_Generator_Service;

/**
 * A deliberately broken generator: throws the moment it is asked to generate.
 */
class Flexa_Broken_Generator {
	/**
	 * @param array       $attrs Attributes (ignored).
	 * @param object      $css   CSS builder (ignored).
	 * @return void
	 */
	public static function generate( $attrs, $css ): void {
		throw new \RuntimeException( 'boom from a broken block' );
	}
}

/**
 * @covers \Flexa\Block\CSS_Generator_Service
 */
class ServiceErrorIsolationTest extends TestCase {

	protected function setUp(): void {
		// Force the cached generator map to a known set: the real container
		// generator plus a deliberately broken one.
		$prop = new \ReflectionProperty( CSS_Generator_Service::class, 'generators_cache' );
		$prop->setAccessible( true );
		$prop->setValue(
			null,
			[
				'flexa/container' => \Flexa\Block\CSS_Generators\Container_CSS::class,
				'flexa/broken'    => Flexa_Broken_Generator::class,
			]
		);
	}

	protected function tearDown(): void {
		CSS_Generator_Service::reset_generators();
		parent::tearDown();
	}

	/** A broken block earlier in the list must not stop a valid block after it. */
	public function test_failing_block_does_not_abort_following_blocks(): void {
		$blocks = [
			[
				'blockName'   => 'flexa/broken',
				'attrs'       => [ 'blockId' => 'x1' ],
				'innerBlocks' => [],
			],
			[
				'blockName'   => 'flexa/container',
				'attrs'       => [
					'blockId'       => 'ok',
					'containerType' => 'full-width',
					'layout'        => [ 'desktop' => [ 'display' => 'flex' ] ],
				],
				'innerBlocks' => [],
			],
		];

		$css = CSS_Generator_Service::generate_for_blocks( $blocks );

		// The valid container still produced its CSS despite the earlier failure.
		$this->assertStringContainsString( '.flexa-container-ok', $css );
		$this->assertStringContainsString( 'display:flex', $css );
	}

	/** A lone broken block must return an empty string, not throw. */
	public function test_single_failing_block_returns_without_throwing(): void {
		$blocks = [
			[
				'blockName'   => 'flexa/broken',
				'attrs'       => [ 'blockId' => 'x1' ],
				'innerBlocks' => [],
			],
		];

		$this->assertSame( '', CSS_Generator_Service::generate_for_blocks( $blocks ) );
	}
}
