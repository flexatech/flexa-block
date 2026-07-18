<?php
/**
 * Tests for the Separator block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Separator_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Separator_CSS
 */
class SeparatorCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Separator generator.
	 *
	 * @param array $attrs Separator attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Separator_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_separator_emits_nothing(): void {
		// Only a blockId: the base line comes from style.scss, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_line_style_targets_the_line(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'lineStyle' => 'dashed' ] );
		$this->assertCssHas( $css, '.flexa-separator-a .flexa-separator__line', 'border-top-style:dashed' );
	}

	public function test_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'color'   => [ 'light' => '#111827', 'dark' => '#f9fafb' ],
		] );
		$this->assertCssHas( $css, '.flexa-separator-a .flexa-separator__line', 'border-top-color:#111827' );
		$this->assertCssHasInDark( $css, '.flexa-separator-a .flexa-separator__line', 'border-top-color:#f9fafb' );
	}

	public function test_width_desktop_at_base(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'width'   => [ 'desktop' => [ 'value' => '60', 'unit' => '%' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-separator-a .flexa-separator__line', 'width:60%' );
	}

	public function test_width_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'width'   => [ 'tablet' => [ 'value' => '80', 'unit' => '%' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-separator-a .flexa-separator__line', 'width:80%' );
	}

	public function test_thickness_maps_to_border_top_width(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'weight'  => [ 'desktop' => [ 'value' => '4', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-separator-a .flexa-separator__line', 'border-top-width:4px' );
	}

	public function test_thickness_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'weight'  => [ 'mobile' => [ 'value' => '2', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', '.flexa-separator-a .flexa-separator__line', 'border-top-width:2px' );
	}

	public function test_alignment_sets_wrapper_justify_content(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'left' ],
		] );
		$this->assertCssHas( $css, '.flexa-separator-a', 'justify-content:flex-start' );
	}

	public function test_alignment_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'tablet' => 'right' ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-separator-a', 'justify-content:flex-end' );
	}

	public function test_spacing_padding_and_margin_on_wrapper(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '15', 'right' => '0', 'bottom' => '15', 'left' => '0', 'unit' => 'px' ],
					'margin'  => [ 'top' => '20', 'right' => 'auto', 'bottom' => '20', 'left' => 'auto', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-separator-a', 'padding:15px 0px 15px 0px' );
		$this->assertCssHas( $css, '.flexa-separator-a', 'margin:20px auto 20px auto' );
	}

	public function test_untouched_line_has_no_style_or_colour(): void {
		// lineStyle and colour empty → the generator must not emit either.
		$css = $this->gen( [
			'blockId' => 'a',
			'width'   => [ 'desktop' => [ 'value' => '50', 'unit' => '%' ] ],
		] );
		$this->assertStringNotContainsString( 'border-top-style', $css );
		$this->assertStringNotContainsString( 'border-top-color', $css );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );
		$css = $this->gen( [
			'blockId' => 'a',
			'color'   => [ 'light' => '#111827', 'dark' => '#f9fafb' ],
		] );
		$this->assertCssHasInDark( $css, '.flexa-separator-a .flexa-separator__line', 'border-top-color:#f9fafb', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
