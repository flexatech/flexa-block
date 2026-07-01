<?php
/**
 * Tests for the Button block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Button_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Button_CSS
 */
class ButtonCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Button generator.
	 *
	 * @param array $attrs Button attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Button_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_fill_colors_on_base_selector(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'variant'   => 'fill',
			'textColor' => [ 'light' => '#ffffff', 'dark' => '' ],
			'bgColor'   => [ 'light' => '#2563eb', 'dark' => '' ],
		] );
		$this->assertCssHas( $css, '.flexa-button-a', 'color:#ffffff' );
		$this->assertCssHas( $css, '.flexa-button-a', 'background-color:#2563eb' );
	}

	public function test_outline_paints_border_not_only_background(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'variant' => 'outline',
			'bgColor' => [ 'light' => '#2563eb', 'dark' => '' ],
		] );
		$this->assertCssHas( $css, '.flexa-button-a', 'border-color:#2563eb' );
	}

	public function test_radius_uses_unit(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'radius'  => [ 'value' => '999', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, '.flexa-button-a', 'border-radius:999px' );
	}

	public function test_hover_colors_target_hover_selector(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'variant'      => 'fill',
			'hoverBgColor' => [ 'light' => '#1d4ed8', 'dark' => '' ],
		] );
		$this->assertCssHas( $css, '.flexa-button-a:hover', 'background-color:#1d4ed8' );
	}

	public function test_padding_on_base_and_tablet_in_media(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [ 'padding' => [ 'top' => '10', 'right' => '20', 'bottom' => '10', 'left' => '20', 'unit' => 'px' ] ],
				'tablet'  => [ 'padding' => [ 'top' => '8', 'right' => '14', 'bottom' => '8', 'left' => '14', 'unit' => 'px' ] ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-button-a', 'padding:10px 20px 10px 20px' );
		$this->assertStringContainsString( '@media (max-width: 1024px)', $css );
	}

	public function test_margin_on_base_selector(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'desktop' => [ 'margin' => [ 'top' => '0', 'right' => 'auto', 'bottom' => '0', 'left' => 'auto', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-button-a', 'margin:0px auto 0px auto' );
	}

	public function test_dark_mode_emits_prefers_color_scheme(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'variant' => 'fill',
			'bgColor' => [ 'light' => '#2563eb', 'dark' => '#0b1220' ],
		] );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
		$this->assertStringContainsString( '#0b1220', $css );
	}
}
