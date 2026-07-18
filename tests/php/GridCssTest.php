<?php
/**
 * Tests for the Grid block CSS generator.
 *
 * Mirrors the 9-point checklist: every generator declaration has a matching
 * assert; responsive values are asserted inside their media query; dark values
 * assert full property:value inside the dark branch.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Grid_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Grid_CSS
 */
class GridCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Grid generator.
	 *
	 * @param array $attrs Grid attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Grid_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_columns_fr_count_expands_to_repeat(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'layout'        => [ 'desktop' => [ 'columns' => [ 'value' => '3', 'unit' => 'fr' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'grid-template-columns:repeat(3, 1fr)' );
	}

	public function test_custom_columns_used_verbatim(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'layout'        => [ 'desktop' => [ 'columns' => [ 'value' => '1fr 2fr', 'unit' => 'custom' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'grid-template-columns:1fr 2fr' );
	}

	public function test_rows_expand_and_empty_rows_emit_nothing(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'layout'        => [ 'desktop' => [ 'rows' => [ 'value' => '2', 'unit' => 'fr' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'grid-template-rows:repeat(2, 1fr)' );

		$empty = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'layout'        => [ 'desktop' => [ 'rows' => [ 'value' => '', 'unit' => 'fr' ] ] ],
		] );
		$this->assertStringNotContainsString( 'grid-template-rows', $empty );
	}

	public function test_alignment_and_auto_flow(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'layout'        => [
				'desktop' => [
					'autoFlow'       => 'column',
					'justifyItems'   => 'center',
					'alignItems'     => 'start',
					'justifyContent' => 'space-between',
					'alignContent'   => 'end',
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'grid-auto-flow:column' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'justify-items:center' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'align-items:start' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'justify-content:space-between' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'align-content:end' );
	}

	public function test_gap_uses_unit(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'layout'        => [ 'desktop' => [ 'gap' => [ 'column' => '20', 'row' => '10', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'column-gap:20px' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'row-gap:10px' );
	}

	public function test_tablet_columns_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'layout'        => [ 'tablet' => [ 'columns' => [ 'value' => '1', 'unit' => 'fr' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-grid-a', 'grid-template-columns:repeat(1, 1fr)' );
	}

	public function test_boxed_styles_target_inner_wrapper(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'boxed',
			'spacing'       => [ 'desktop' => [ 'padding' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a > .flexa-grid__inner', 'padding:10px 10px 10px 10px' );
	}

	public function test_boxed_uses_max_width(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'boxed',
			'widthBoxed'    => [ 'desktop' => [ 'value' => '1200', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a > .flexa-grid__inner', 'max-width:1200px' );
	}

	public function test_full_width_uses_width(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'containerType'  => 'full-width',
			'widthFullWidth' => [ 'desktop' => [ 'value' => '90', 'unit' => '%' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'width:90%' );
	}

	public function test_min_height(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'size'          => [ 'desktop' => [ 'minHeight' => [ 'value' => '400', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'min-height:400px' );
	}

	public function test_margin_on_outer(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'boxed',
			'spacing'       => [ 'desktop' => [ 'margin' => [ 'top' => '20', 'right' => 'auto', 'bottom' => '20', 'left' => 'auto', 'unit' => 'px' ] ] ],
		] );
		// Margin lands on the OUTER wrapper, not the inner grid.
		$this->assertCssHas( $css, '.flexa-grid-a', 'margin:20px auto 20px auto' );
	}

	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'containerType'  => 'full-width',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'z-index:5' );
	}

	public function test_border_all_sub_properties_and_dark_color(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'border'        => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#cccccc', 'dark' => '#333333' ],
					'radius' => [ 'topLeft' => '8', 'topRight' => '8', 'bottomRight' => '8', 'bottomLeft' => '8', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'border-color:#cccccc' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'border-radius:8px 8px 8px 8px' );
		$this->assertCssHasInDark( $css, '.flexa-grid-a', 'border-color:#333333' );
	}

	public function test_background_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'background'    => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'background-color:#ffffff' );
		$this->assertCssHasInDark( $css, '.flexa-grid-a', 'background-color:#000000' );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
	}

	public function test_gradient_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'background'    => [
				'type'     => 'gradient',
				'gradient' => [ 'light' => 'linear-gradient(0deg,#aaa,#bbb)', 'dark' => 'linear-gradient(0deg,#111,#222)' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'background-image:linear-gradient(0deg,#aaa,#bbb)' );
		$this->assertCssHasInDark( $css, '.flexa-grid-a', 'background-image:linear-gradient(0deg,#111,#222)' );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'boxShadow'     => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000', 'dark' => '#fff' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'box-shadow:0px 2px 8px 0px #000' );
		$this->assertCssHasInDark( $css, '.flexa-grid-a', 'box-shadow:0px 2px 8px 0px #fff' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'background'    => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );

		$this->assertCssHasInDark( $css, '.flexa-grid-a', 'background-color:#000000', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}

	public function test_non_lazy_background_image_emits_url_inline(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'background'    => [
				'type'  => 'image',
				'image' => [ 'url' => 'https://example.com/a.jpg', 'position' => 'center center', 'size' => 'cover', 'repeat' => 'no-repeat', 'attachment' => 'scroll' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'background-position:center center' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'background-size:cover' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'background-repeat:no-repeat' );
		$this->assertCssHas( $css, '.flexa-grid-a', 'background-attachment:scroll' );
		$this->assertStringNotContainsString( 'flexa-bg-loaded', $css );
	}

	public function test_lazy_background_image_gates_url_behind_loaded_class(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'background'    => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://example.com/a.jpg', 'size' => 'cover' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-grid-a', 'background-size:cover' );
		$this->assertCssHas( $css, '.flexa-grid-a.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}
}
