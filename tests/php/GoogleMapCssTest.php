<?php
/**
 * Tests for the Google Map block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Google_Map_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Google_Map_CSS
 */
class GoogleMapCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Google Map generator.
	 *
	 * @param array $attrs Google Map attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Google_Map_CSS::class, 'generate' ], $attrs );
	}

	private const WRAP  = '.flexa-google-map-a';
	private const FRAME = '.flexa-google-map-a .flexa-google-map__frame';

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_spacing_padding_and_margin_on_wrapper(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ],
					'margin'  => [ 'top' => '20', 'right' => '0', 'bottom' => '20', 'left' => '0', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:10px 10px 10px 10px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:20px 0px 20px 0px' );
	}

	public function test_boxed_width_is_max_width(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'boxed',
			'widthBoxed'    => [ 'desktop' => [ 'value' => '960', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'max-width:960px' );
	}

	public function test_full_width_is_width(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'containerType'  => 'full-width',
			'widthFullWidth' => [ 'desktop' => [ 'value' => '100', 'unit' => '%' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'width:100%' );
		$this->assertStringNotContainsString( 'max-width', $css );
	}

	public function test_height_on_frame(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'height'  => [ 'desktop' => [ 'value' => '500', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::FRAME, 'height:500px' );
	}

	public function test_tablet_height_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'height'  => [ 'tablet' => [ 'value' => '300', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::FRAME, 'height:300px' );
	}

	public function test_mobile_height_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'height'  => [ 'mobile' => [ 'value' => '250', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::FRAME, 'height:250px' );
	}

	public function test_border_targets_frame_with_all_sub_properties(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
					'color'  => [ 'light' => '#123456', 'dark' => '#abcdef' ],
					'radius' => [ 'topLeft' => '8', 'topRight' => '8', 'bottomRight' => '8', 'bottomLeft' => '8', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::FRAME, 'border-style:solid' );
		$this->assertCssHas( $css, self::FRAME, 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, self::FRAME, 'border-color:#123456' );
		$this->assertCssHas( $css, self::FRAME, 'border-radius:8px 8px 8px 8px' );
		$this->assertCssHasInDark( $css, self::FRAME, 'border-color:#abcdef' );
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

	public function test_background_colour_light_and_dark_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#eeeeee', 'dark' => '#222222' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#eeeeee' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#222222' );
	}

	public function test_background_none_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'none', 'color' => [ 'light' => '#eeeeee', 'dark' => '' ] ],
		] );
		$this->assertStringNotContainsString( 'background', $css );
	}

	public function test_background_image_sub_properties(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'  => 'image',
				'image' => [ 'url' => 'https://example.com/bg.jpg', 'position' => 'top left', 'size' => 'contain', 'repeat' => 'repeat', 'attachment' => 'fixed' ],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-image:url(https://example.com/bg.jpg)' );
		$this->assertCssHas( $css, self::WRAP, 'background-position:top left' );
		$this->assertCssHas( $css, self::WRAP, 'background-size:contain' );
		$this->assertCssHas( $css, self::WRAP, 'background-repeat:repeat' );
		$this->assertCssHas( $css, self::WRAP, 'background-attachment:fixed' );
	}

	public function test_lazy_background_gates_url_behind_loaded_class(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://example.com/a.jpg', 'size' => 'cover' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-google-map-a.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	public function test_box_shadow_on_frame_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '4', 'blur' => '12', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::FRAME, 'box-shadow:0px 4px 12px 0px #000000' );
		$this->assertCssHasInDark( $css, self::FRAME, 'box-shadow:0px 4px 12px 0px #ffffff' );
	}

	public function test_box_shadow_disabled_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => false, 'horizontal' => '0', 'vertical' => '4', 'blur' => '12', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '' ] ],
		] );
		$this->assertStringNotContainsString( 'box-shadow', $css );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#eeeeee', 'dark' => '#222222' ] ],
		] );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#222222', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
