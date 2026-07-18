<?php
/**
 * Tests for the Slider block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Slides_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Slides_CSS
 */
class SlidesCssTest extends CssTestCase {

	private const ROOT   = '.flexa-slides-a';
	private const SLIDE  = '.flexa-slides-a .swiper-slide';
	private const ARROW  = '.flexa-slides-a .flexa-slides__arrow';
	private const ASVG   = '.flexa-slides-a .flexa-slides__arrow svg';
	private const AHOVER = '.flexa-slides-a .flexa-slides__arrow:hover';
	private const BULLET = '.flexa-slides-a .swiper-pagination-bullet';
	private const BULLON = '.flexa-slides-a .swiper-pagination-bullet-active';

	/**
	 * Convenience wrapper around the Slider generator.
	 *
	 * @param array $attrs Slider attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Slides_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_slider_emits_nothing(): void {
		// Only a blockId + a behaviour toggle (JS-only): nothing should be styled.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'autoplay' => true, 'showArrows' => true, 'effect' => 'fade' ] ) );
	}

	public function test_padding_and_margin_on_wrapper(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'desktop' => [
				'padding' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ],
				'margin'  => [ 'top' => '0', 'right' => 'auto', 'bottom' => '0', 'left' => 'auto', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::ROOT, 'padding:10px 10px 10px 10px' );
		$this->assertCssHas( $css, self::ROOT, 'margin:0px auto 0px auto' );
	}

	public function test_slide_height_base_and_responsive(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'minHeight' => [
				'desktop' => [ 'value' => '400', 'unit' => 'px' ],
				'tablet'  => [ 'value' => '300', 'unit' => 'px' ],
			],
		] );
		$this->assertCssHas( $css, self::SLIDE, 'min-height:400px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::SLIDE, 'min-height:300px' );
	}

	public function test_border_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [ 'desktop' => [
				'style' => 'solid',
				'width' => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
				'color' => [ 'light' => '#111827', 'dark' => '#f9fafb' ],
			] ],
		] );
		$this->assertCssHas( $css, self::ROOT, 'border-style:solid' );
		$this->assertCssHas( $css, self::ROOT, 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, self::ROOT, 'border-color:#111827' );
		$this->assertCssHasInDark( $css, self::ROOT, 'border-color:#f9fafb' );
	}

	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, self::ROOT, 'overflow:hidden' );
		$this->assertCssHas( $css, self::ROOT, 'position:relative' );
		$this->assertCssHas( $css, self::ROOT, 'z-index:5' );
	}

	public function test_arrow_size_radius_offset(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'arrowSize'   => [ 'value' => '24', 'unit' => 'px' ],
			'arrowRadius' => [ 'value' => '50', 'unit' => '%' ],
			'arrowOffset' => [ 'value' => '16', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::ASVG, 'width:24px' );
		$this->assertCssHas( $css, self::ASVG, 'height:24px' );
		$this->assertCssHas( $css, self::ARROW, 'border-radius:50%' );
		$this->assertCssHas( $css, '.flexa-slides-a .swiper-button-prev', 'left:16px' );
		$this->assertCssHas( $css, '.flexa-slides-a .swiper-button-next', 'right:16px' );
	}

	public function test_arrow_colors_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'              => 'a',
			'arrowColor'           => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
			'arrowBackground'      => [ 'light' => '#ffffff', 'dark' => '#222222' ],
			'arrowColorHover'      => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
			'arrowBackgroundHover' => [ 'light' => '#f3f4f6', 'dark' => '#333333' ],
		] );
		$this->assertCssHas( $css, self::ARROW, 'color:#111111' );
		$this->assertCssHas( $css, self::ARROW, 'background-color:#ffffff' );
		$this->assertCssHas( $css, self::AHOVER, 'color:#2563eb' );
		$this->assertCssHas( $css, self::AHOVER, 'background-color:#f3f4f6' );
		$this->assertCssHasInDark( $css, self::ARROW, 'color:#eeeeee' );
		$this->assertCssHasInDark( $css, self::ARROW, 'background-color:#222222' );
		$this->assertCssHasInDark( $css, self::AHOVER, 'color:#93c5fd' );
		$this->assertCssHasInDark( $css, self::AHOVER, 'background-color:#333333' );
	}

	public function test_pagination_dots_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'bulletSize'        => [ 'value' => '10', 'unit' => 'px' ],
			'bulletColor'       => [ 'light' => '#cccccc', 'dark' => '#555555' ],
			'bulletColorActive' => [ 'light' => '#2563eb', 'dark' => '#60a5fa' ],
		] );
		$this->assertCssHas( $css, self::BULLET, 'width:10px' );
		$this->assertCssHas( $css, self::BULLET, 'height:10px' );
		$this->assertCssHas( $css, self::BULLET, 'background-color:#cccccc' );
		$this->assertCssHas( $css, self::BULLON, 'background-color:#2563eb' );
		$this->assertCssHasInDark( $css, self::BULLET, 'background-color:#555555' );
		$this->assertCssHasInDark( $css, self::BULLON, 'background-color:#60a5fa' );
	}

	public function test_background_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#2563eb', 'dark' => '#1e40af' ] ],
		] );
		$this->assertCssHas( $css, self::ROOT, 'background-color:#2563eb' );
		$this->assertCssHasInDark( $css, self::ROOT, 'background-color:#1e40af' );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::ROOT, 'box-shadow:0px 2px 8px 0px #000000' );
		$this->assertCssHasInDark( $css, self::ROOT, 'box-shadow:0px 2px 8px 0px #ffffff' );
	}

	public function test_disabled_box_shadow_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => false, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000' ] ],
		] );
		$this->assertStringNotContainsString( 'box-shadow', $css );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );
		$css = $this->gen( [
			'blockId'    => 'a',
			'arrowColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::ARROW, 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
