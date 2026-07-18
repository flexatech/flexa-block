<?php
/**
 * Tests for the Product Name block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Product_Name_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Product_Name_CSS
 */
class ProductNameCssTest extends CssTestCase {

	private const WRAP    = '.flexa-product-name-a';
	private const CONTENT = '.flexa-product-name-a .flexa-product-name__title';
	private const HOVER   = '.flexa-product-name-a .flexa-product-name__title:hover';

	/**
	 * Convenience wrapper around the Product Name generator.
	 *
	 * @param array $attrs Product name attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Product_Name_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Only a blockId: the theme should style everything, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'htmlTag' => 'h2', 'textType' => 'color' ] ) );
	}

	public function test_alignment_on_wrapper(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
	}

	public function test_alignment_tablet_in_media_query(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'tablet' => 'center' ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'text-align:center' );
	}

	public function test_typography_on_title(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '28', 'unit' => 'px' ], 'fontWeight' => '700', 'lineHeight' => '1.3' ] ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'font-size:28px' );
		$this->assertCssHas( $css, self::CONTENT, 'font-weight:700' );
		$this->assertCssHas( $css, self::CONTENT, 'line-height:1.3' );
	}

	public function test_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '20', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::CONTENT, 'font-size:20px' );
	}

	public function test_text_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'textType'  => 'color',
			'textColor' => [ 'light' => '#222222', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'color:#222222' );
		$this->assertCssHasInDark( $css, self::CONTENT, 'color:#eeeeee' );
	}

	public function test_gradient_text_only_when_type_is_gradient(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'textType'     => 'gradient',
			'textGradient' => [ 'light' => 'linear-gradient(#fff,#000)', 'dark' => 'linear-gradient(#000,#fff)' ],
			'textColor'    => [ 'light' => '#ff0000' ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'background-image:linear-gradient(#fff,#000)' );
		$this->assertCssHas( $css, self::CONTENT, '-webkit-background-clip:text' );
		$this->assertCssHas( $css, self::CONTENT, '-webkit-text-fill-color:transparent' );
		$this->assertStringNotContainsString( '#ff0000', $css );
		$this->assertCssHasInDark( $css, self::CONTENT, 'background-image:linear-gradient(#000,#fff)' );
	}

	public function test_hover_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'textColorHover' => [ 'light' => '#ff0000', 'dark' => '#00ff00' ],
		] );
		$this->assertCssHas( $css, self::HOVER, 'color:#ff0000' );
		$this->assertCssHasInDark( $css, self::HOVER, 'color:#00ff00' );
	}

	public function test_text_stroke(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'textStroke' => [ 'enabled' => true, 'width' => [ 'value' => '1', 'unit' => 'px' ], 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::CONTENT, '-webkit-text-stroke-width:1px' );
		$this->assertCssHas( $css, self::CONTENT, '-webkit-text-stroke-color:#000000' );
		$this->assertCssHasInDark( $css, self::CONTENT, '-webkit-text-stroke-color:#ffffff' );
	}

	public function test_text_stroke_disabled_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'textStroke' => [ 'enabled' => false, 'width' => [ 'value' => '1', 'unit' => 'px' ], 'color' => [ 'light' => '#000000' ] ],
		] );
		$this->assertStringNotContainsString( 'text-stroke', $css );
	}

	public function test_text_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'textShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '4', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'text-shadow:0px 2px 4px #000000' );
		$this->assertCssHasInDark( $css, self::CONTENT, 'text-shadow:0px 2px 4px #ffffff' );
	}

	public function test_blend_mode_on_title(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'blendMode' => 'multiply' ] );
		$this->assertCssHas( $css, self::CONTENT, 'mix-blend-mode:multiply' );
	}

	public function test_spacing_padding_and_margin_on_wrapper(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'desktop' => [
				'padding' => [ 'top' => '10', 'right' => '20', 'bottom' => '10', 'left' => '20', 'unit' => 'px' ],
				'margin'  => [ 'top' => '0', 'right' => 'auto', 'bottom' => '30', 'left' => 'auto', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:10px 20px 10px 20px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:0px auto 30px auto' );
	}

	public function test_spacing_untouched_emits_nothing(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'spacing' => [ 'desktop' => [] ] ] );
		$this->assertStringNotContainsString( 'padding', $css );
		$this->assertStringNotContainsString( 'margin', $css );
	}

	public function test_background_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#f5f5f5', 'dark' => '#101010' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#f5f5f5' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#101010' );
	}

	public function test_lazy_background_image_emitted_once_after_bg_loaded(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://x/y.jpg', 'position' => 'center center', 'size' => 'cover', 'repeat' => 'no-repeat', 'attachment' => 'scroll' ],
			],
		] );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
		$loaded_pos = strpos( $css, '.flexa-bg-loaded' );
		$this->assertNotFalse( $loaded_pos );
		$this->assertCssHas( $css, self::WRAP . '.flexa-bg-loaded', 'background-image:url(https://x/y.jpg)' );
	}

	public function test_border_on_wrapper_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#cccccc', 'dark' => '#333333' ],
					'radius' => [ 'topLeft' => '6', 'topRight' => '6', 'bottomRight' => '6', 'bottomLeft' => '6', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'border-style:solid' );
		$this->assertCssHas( $css, self::WRAP, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::WRAP, 'border-color:#cccccc' );
		$this->assertCssHas( $css, self::WRAP, 'border-radius:6px 6px 6px 6px' );
		$this->assertCssHasInDark( $css, self::WRAP, 'border-color:#333333' );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '4', 'blur' => '12', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'box-shadow:0px 4px 12px 0px #000000' );
		$this->assertCssHasInDark( $css, self::WRAP, 'box-shadow:0px 4px 12px 0px #ffffff' );
	}

	public function test_advanced_layout_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'overflow:hidden' );
		$this->assertCssHas( $css, self::WRAP, 'position:relative' );
		$this->assertCssHas( $css, self::WRAP, 'z-index:5' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );
		$css = $this->gen( [
			'blockId'   => 'a',
			'textColor' => [ 'light' => '#222222', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::CONTENT, 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
