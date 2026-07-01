<?php
/**
 * Tests for the Container block CSS generator.
 *
 * This file doubles as the TEMPLATE for future block tests: copy it, swap the
 * generator + attributes, and reuse genCss()/assertCssHas() from CssTestCase.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Container_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Container_CSS
 */
class ContainerCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Container generator.
	 *
	 * @param array $attrs Container attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Container_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_boxed_styles_target_inner_wrapper(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'boxed',
			'spacing'       => [ 'desktop' => [ 'padding' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-container-a > .flexa-container__inner', 'padding:10px 10px 10px 10px' );
	}

	public function test_full_width_layout_targets_outer(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'layout'        => [ 'desktop' => [ 'display' => 'flex', 'justifyContent' => 'center' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-container-a', 'display:flex' );
		$this->assertCssHas( $css, '.flexa-container-a', 'justify-content:center' );
	}

	public function test_gap_uses_unit(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'layout'        => [ 'desktop' => [ 'gap' => [ 'column' => '20', 'row' => '10', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-container-a', 'column-gap:20px' );
		$this->assertCssHas( $css, '.flexa-container-a', 'row-gap:10px' );
	}

	public function test_tablet_values_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'layout'        => [ 'tablet' => [ 'display' => 'block' ] ],
		] );
		$this->assertStringContainsString( '@media (max-width: 1024px)', $css );
	}

	public function test_dark_mode_emits_prefers_color_scheme(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'background'    => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
		$this->assertStringContainsString( '#000000', $css );
	}

	public function test_box_shadow_rendered(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'boxShadow'     => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000', 'dark' => '#fff' ] ],
		] );
		$this->assertStringContainsString( 'box-shadow:0px 2px 8px 0px #000', $css );
	}

	public function test_boxed_uses_max_width(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'boxed',
			'widthBoxed'    => [ 'desktop' => [ 'value' => '1200', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-container-a > .flexa-container__inner', 'max-width:1200px' );
	}

	public function test_full_width_uses_width(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'containerType'  => 'full-width',
			'widthFullWidth' => [ 'desktop' => [ 'value' => '90', 'unit' => '%' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-container-a', 'width:90%' );
	}

	public function test_min_height(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'size'          => [ 'desktop' => [ 'minHeight' => [ 'value' => '400', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-container-a', 'min-height:400px' );
	}

	public function test_margin_on_outer(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'spacing'       => [ 'desktop' => [ 'margin' => [ 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-container-a', 'margin:20px 20px 20px 20px' );
	}

	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'containerType'  => 'full-width',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-container-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-container-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-container-a', 'z-index:5' );
	}

	public function test_border_light_at_base_and_dark_in_media(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'border'        => [
				'desktop' => [
					'style' => 'solid',
					'width' => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color' => [ 'light' => '#cccccc', 'dark' => '#333333' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-container-a', 'border-color:#cccccc' ); // light at base
		$this->assertStringContainsString( '#333333', $css );                       // dark emitted (dark-mode block)
	}

	public function test_data_theme_dark_mode_branch(): void {
		// Switch dark mode to the [data-theme="dark"] strategy (default uses prefers-color-scheme).
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'background'    => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );

		$this->assertStringContainsString( '[data-theme="dark"] .flexa-container-a', $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css ); // color-scheme branch disabled
		$this->assertStringContainsString( '#000000', $css );
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
		$this->assertStringContainsString( 'background-image:linear-gradient(0deg,#aaa,#bbb)', $css ); // base (light)
		$this->assertStringContainsString( 'linear-gradient(0deg,#111,#222)', $css );                  // dark-mode block
	}

	public function test_non_lazy_background_image_emits_url_inline(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'full-width',
			'background'    => [
				'type'  => 'image',
				'image' => [ 'url' => 'https://example.com/a.jpg', 'size' => 'cover' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-container-a', 'background-image:url(https://example.com/a.jpg)' );
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
		// Positioning stays on the base selector, but the url is deferred.
		$this->assertCssHas( $css, '.flexa-container-a', 'background-size:cover' );
		// The image url only applies once .flexa-bg-loaded is added by view.js.
		$this->assertCssHas( $css, '.flexa-container-a.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		// The url must appear exactly once - only behind the loaded class.
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}
}
