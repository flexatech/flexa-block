<?php
/**
 * Tests for the filter-children CSS generator (search / taxonomy / reset).
 *
 * The bar styles every control at once; this generator is the per-field escape hatch.
 * Two claims are load-bearing and both are pinned here:
 *
 *  - It paints the field's own box, whatever that child renders — input, select,
 *    disclosure summary, reset button, chip.
 *  - It OUTRANKS the bar. The selector carries the shell class as well as the id
 *    class, which is one class more specific than the bar's `.flexa-post-filter-<id>
 *    .flexa-filter-field__control`. Lose that and "the Tag box, but grey" silently
 *    does nothing.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Filter_Field_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Filter_Field_CSS
 */
class FilterFieldCssTest extends CssTestCase {

	// The chip is in the list ONLY under `--pills`: that is the one mode where a chip is
	// the control. Widen it back and a border on a Tag filter frames every term inside
	// the dropdown instead of the dropdown itself.
	private const SURFACE = '.flexa-filter-field.flexa-filter-field-a .flexa-filter-field__control, .flexa-filter-field.flexa-filter-field-a .flexa-filter-reset__control, .flexa-filter-field.flexa-filter-field-a .flexa-filter-field__options--pills .flexa-filter-field__option-text';

	/**
	 * Convenience wrapper around the generator.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Filter_Field_CSS::class, 'generate' ], array_merge( [ 'blockId' => 'a' ], $attrs ) );
	}

	public function testEmitsNothingWithoutABlockId(): void {
		$css = $this->genCss(
			[ Filter_Field_CSS::class, 'generate' ],
			[ 'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#fff' ] ] ]
		);

		$this->assertSame( '', $css );
	}

	/** An untouched field inherits the bar — theme-first, nothing emitted. */
	public function testEmitsNothingWhenTheAuthorSetNothing(): void {
		$this->assertSame( '', $this->gen( [] ) );
	}

	public function testBackgroundPaintsEverySurfaceTheFieldCanRender(): void {
		$css = $this->gen(
			[
				'background' => [
					'type'  => 'classic',
					'color' => [ 'light' => '#f1f5f9', 'dark' => '#0f172a' ],
				],
			]
		);

		$this->assertCssHas( $css, self::SURFACE, 'background-color:#f1f5f9' );
		$this->assertCssHasInDark( $css, self::SURFACE, 'background-color:#0f172a' );
	}

	public function testBorderIsPerDevice(): void {
		$css = $this->gen(
			[
				'border' => [
					'desktop' => [
						'style' => 'solid',
						'width' => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
						'color' => [ 'light' => '#0891b2' ],
					],
					'mobile'  => [ 'style' => 'dashed' ],
				],
			]
		);

		$this->assertCssHas( $css, self::SURFACE, 'border-color:#0891b2' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::SURFACE, 'border-style:dashed' );
	}

	public function testShadowLandsOnTheField(): void {
		$css = $this->gen(
			[
				'boxShadow' => [
					'enabled'    => true,
					'horizontal' => '0',
					'vertical'   => '2',
					'blur'       => '8',
					'spread'     => '0',
					'color'      => [ 'light' => 'rgba(0,0,0,0.2)' ],
				],
			]
		);

		$this->assertCssHas( $css, self::SURFACE, 'box-shadow:0px 2px 8px 0px rgba(0,0,0,0.2)' );
	}

	/**
	 * The whole point of the feature: this rule has to beat the bar's Control panel.
	 * The bar's selector is (0,2,0); the shell class + the id class make this (0,3,0).
	 */
	public function testTheSelectorOutranksTheBar(): void {
		$css = $this->gen( [ 'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#fff' ] ] ] );

		$this->assertStringContainsString( '.flexa-filter-field.flexa-filter-field-a .flexa-filter-field__control', $css );
	}
}
