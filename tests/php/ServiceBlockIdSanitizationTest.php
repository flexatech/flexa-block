<?php
/**
 * Security test: CSS_Generator_Service must sanitize the user-controlled
 * `blockId` attribute before it becomes part of a CSS selector printed inline
 * via wp_add_inline_style(). A crafted id must not be able to break out of the
 * <style> block (stored XSS).
 *
 * @package Flexa\Block
 */

use PHPUnit\Framework\TestCase;
use Flexa\Block\CSS_Generator_Service;

/**
 * @covers \Flexa\Block\CSS_Generator_Service
 */
class ServiceBlockIdSanitizationTest extends TestCase {

	protected function setUp(): void {
		CSS_Generator_Service::reset_generators();
	}

	protected function tearDown(): void {
		CSS_Generator_Service::reset_generators();
		parent::tearDown();
	}

	public function test_malicious_block_id_cannot_break_out_of_style(): void {
		$blocks = [
			[
				'blockName'   => 'flexa/container',
				'attrs'       => [
					'blockId'       => '</style><script>alert(1)</script>',
					'containerType' => 'full-width',
					'layout'        => [ 'desktop' => [ 'display' => 'flex' ] ],
				],
				'innerBlocks' => [],
			],
		];

		$css = CSS_Generator_Service::generate_for_blocks( $blocks );

		// No angle brackets survive — the <style> block cannot be escaped.
		$this->assertStringNotContainsString( '<', $css );
		$this->assertStringNotContainsString( '</style>', $css );
		$this->assertStringNotContainsString( '<script', $css );

		// The id is reduced to its class-safe characters and still drives a valid
		// selector (matching what render.php emits for the class), and real CSS is
		// still generated.
		$this->assertStringContainsString( '.flexa-container-stylescriptalert1script', $css );
		$this->assertStringContainsString( 'display:flex', $css );
	}

	public function test_block_id_that_sanitizes_to_empty_emits_nothing(): void {
		// An id made entirely of stripped characters leaves no usable selector, so
		// the block is skipped rather than emitting `.flexa-container-{…}`.
		$blocks = [
			[
				'blockName'   => 'flexa/container',
				'attrs'       => [
					'blockId'       => '<>/()',
					'containerType' => 'full-width',
					'layout'        => [ 'desktop' => [ 'display' => 'flex' ] ],
				],
				'innerBlocks' => [],
			],
		];

		$this->assertSame( '', CSS_Generator_Service::generate_for_blocks( $blocks ) );
	}
}
