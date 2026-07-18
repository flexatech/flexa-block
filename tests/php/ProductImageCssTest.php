<?php
/**
 * Tests for the Product Image block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Product_Image_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Product_Image_CSS
 */
class ProductImageCssTest extends CssTestCase {

	private const WRAP      = '.flexa-product-image-a';
	private const THUMBS    = '.flexa-product-image-a .flexa-product-image__thumbs';
	private const MAIN      = '.flexa-product-image-a .flexa-product-image__main';
	private const MAIN_IMG  = '.flexa-product-image-a .flexa-product-image__main img';
	private const THUMB_IMG = '.flexa-product-image-a .flexa-product-image__thumb img';

	/**
	 * Convenience wrapper around the Product Image generator.
	 *
	 * @param array $attrs Product-image attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Product_Image_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Only a blockId (adaptive height on by default): no declarations at all.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'galleryPosition' => 'bottom', 'adaptiveHeight' => true ] ) );
	}

	public function test_alignment_on_wrapper(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
	}

	public function test_alignment_tablet_in_media_query(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'tablet' => 'right' ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'text-align:right' );
	}

	public function test_thumbnails_per_view_var_when_not_default(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'thumbnailsPerView' => 5 ] );
		$this->assertCssHas( $css, self::WRAP, '--flexa-thumb-pv:5' );
	}

	public function test_thumbnails_per_view_default_emits_nothing(): void {
		// style.scss already defaults to 4, so the default value emits no var.
		$css = $this->gen( [ 'blockId' => 'a', 'thumbnailsPerView' => 4 ] );
		$this->assertStringNotContainsString( '--flexa-thumb-pv', $css );
	}

	public function test_legacy_thumbnail_columns_and_size_ignored(): void {
		// Columns and the fixed thumbnail size were replaced by a per-view carousel.
		// Content saved with the old attributes must not emit any grid / thumb-size CSS.
		$css = $this->gen( [
			'blockId'          => 'a',
			'thumbnailColumns' => [ 'desktop' => [ 'value' => '5', 'unit' => '' ] ],
			'thumbnailSize'    => [ 'desktop' => [ 'value' => '80', 'unit' => 'px' ] ],
		] );
		$this->assertStringNotContainsString( 'grid-template-columns', $css );
		$this->assertStringNotContainsString( 'repeat(', $css );
		$this->assertStringNotContainsString( 'width:80px', $css );
	}

	public function test_thumbnail_gap_var_desktop(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'thumbnailGap' => [ 'desktop' => [ 'value' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::THUMBS, '--flexa-thumb-gap:16px' );
	}

	public function test_thumbnail_gap_var_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'thumbnailGap' => [ 'tablet' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::THUMBS, '--flexa-thumb-gap:8px' );
	}

	public function test_image_height_when_not_adaptive_on_container_and_image(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'adaptiveHeight' => false,
			'imageHeight'    => [ 'desktop' => [ 'value' => '360', 'unit' => 'px' ] ],
		] );
		// Height on both the container and the image so swapping images doesn't jump.
		$this->assertCssHas( $css, self::MAIN, 'height:360px' );
		$this->assertCssHas( $css, self::MAIN_IMG, 'height:360px' );
	}

	public function test_image_height_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'adaptiveHeight' => false,
			'imageHeight'    => [ 'tablet' => [ 'value' => '240', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::MAIN_IMG, 'height:240px' );
	}

	public function test_image_height_ignored_when_adaptive(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'adaptiveHeight' => true,
			'imageHeight'    => [ 'desktop' => [ 'value' => '360', 'unit' => 'px' ] ],
		] );
		$this->assertStringNotContainsString( 'height:360px', $css );
	}

	public function test_image_scale_object_fit(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'adaptiveHeight' => false,
			'imageScale'     => 'contain',
		] );
		$this->assertCssHas( $css, self::MAIN_IMG, 'object-fit:contain' );
	}

	public function test_image_scale_ignored_when_adaptive(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'adaptiveHeight' => true,
			'imageScale'     => 'contain',
		] );
		$this->assertStringNotContainsString( 'object-fit', $css );
	}

	public function test_image_radius_on_main_and_thumb(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'imageRadius' => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::MAIN_IMG, 'border-radius:12px' );
		$this->assertCssHas( $css, self::THUMB_IMG, 'border-radius:12px' );
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

	public function test_advanced_layout_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'overflow:hidden' );
		$this->assertCssHas( $css, self::WRAP, 'position:relative' );
		$this->assertCssHas( $css, self::WRAP, 'z-index:5' );
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

	public function test_background_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#f5f5f5', 'dark' => '#101010' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#f5f5f5' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#101010' );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '4', 'blur' => '12', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'box-shadow:0px 4px 12px 0px #000000' );
		$this->assertCssHasInDark( $css, self::WRAP, 'box-shadow:0px 4px 12px 0px #ffffff' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#f5f5f5', 'dark' => '#101010' ] ],
		] );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#101010', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
