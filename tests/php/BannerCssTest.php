<?php
/**
 * Tests for the Banner block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Banner_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Banner_CSS
 * @covers \Flexa\Block\CSS_Generators\Promo_CSS_Parts
 */
class BannerCssTest extends CssTestCase {

	private const WRAP    = '.flexa-banner-a';
	private const OVERLAY = '.flexa-banner-a .flexa-banner__overlay';
	private const CONTENT = '.flexa-banner-a .flexa-promo__content';
	private const HEADING = '.flexa-banner-a .flexa-promo__heading';
	private const DESC    = '.flexa-banner-a .flexa-promo__description';
	private const BTN_P   = '.flexa-banner-a .flexa-promo__button--primary';
	private const BTN_PH  = '.flexa-banner-a .flexa-promo__button--primary:hover';
	private const BTN_S   = '.flexa-banner-a .flexa-promo__button--secondary';

	/**
	 * Convenience wrapper around the Banner generator.
	 *
	 * @param array $attrs Banner attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Banner_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Real block.json defaults for an untouched full-width banner → the generator
		// emits nothing (structural min-height / padding / centring live in style.scss).
		$this->assertSame( '', $this->gen( [
			'blockId'        => 'a',
			'containerType'  => 'full-width',
			'verticalAlign'  => '',
			'widthFullWidth' => [ 'desktop' => [], 'tablet' => [], 'mobile' => [] ],
			'size'           => [ 'desktop' => [ 'minHeight' => [ 'value' => '' ] ], 'tablet' => [], 'mobile' => [] ],
			'contentAlign'   => [ 'desktop' => '', 'tablet' => '', 'mobile' => '' ],
			'overlay'        => [ 'type' => 'none' ],
			'background'     => [ 'type' => 'none' ],
			'boxShadow'      => [ 'enabled' => false ],
		] ) );
	}

	public function test_spacing_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'desktop' => [
				'padding' => [ 'top' => '60', 'right' => '24', 'bottom' => '60', 'left' => '24', 'unit' => 'px' ],
				'margin'  => [ 'top' => '20', 'right' => '0', 'bottom' => '20', 'left' => '0', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:60px 24px 60px 24px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:20px 0px 20px 0px' );
	}

	public function test_boxed_width_and_full_width(): void {
		$boxed = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'boxed',
			'widthBoxed'    => [ 'desktop' => [ 'value' => '1000', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $boxed, self::WRAP, 'max-width:1000px' );

		$full = $this->gen( [
			'blockId'        => 'a',
			'containerType'  => 'full-width',
			'widthFullWidth' => [ 'desktop' => [ 'value' => '90', 'unit' => '%' ] ],
		] );
		$this->assertCssHas( $full, self::WRAP, 'width:90%' );
	}

	public function test_min_height_desktop_and_tablet_media(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'size'    => [
				'desktop' => [ 'minHeight' => [ 'value' => '480', 'unit' => 'px' ] ],
				'tablet'  => [ 'minHeight' => [ 'value' => '360', 'unit' => 'px' ] ],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'min-height:480px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'min-height:360px' );
	}

	public function test_vertical_align(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'verticalAlign' => 'flex-end' ] );
		$this->assertCssHas( $css, self::WRAP, 'justify-content:flex-end' );
	}

	public function test_content_alignment_on_wrapper_and_content(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'contentAlign' => [ 'desktop' => 'left' ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'align-items:flex-start' );
		$this->assertCssHas( $css, self::CONTENT, 'text-align:left' );
		$this->assertCssHas( $css, self::CONTENT, 'align-items:flex-start' );
	}

	public function test_content_alignment_tablet_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'contentAlign' => [ 'tablet' => 'right' ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::CONTENT, 'text-align:right' );
	}

	public function test_content_gap_and_max_width(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'contentGap'      => [ 'desktop' => [ 'value' => '28', 'unit' => 'px' ] ],
			'contentMaxWidth' => [ 'desktop' => [ 'value' => '640', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'gap:28px' );
		$this->assertCssHas( $css, self::CONTENT, 'max-width:640px' );
	}

	public function test_heading_and_description_typography(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'headingTypography'     => [ 'desktop' => [ 'fontSize' => [ 'value' => '40', 'unit' => 'px' ] ] ],
			'descriptionTypography' => [ 'tablet' => [ 'lineHeight' => '1.7' ] ],
		] );
		$this->assertCssHas( $css, self::HEADING, 'font-size:40px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::DESC, 'line-height:1.7' );
	}

	public function test_heading_and_description_colors_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'headingColor'     => [ 'light' => '#111827', 'dark' => '#f9fafb' ],
			'descriptionColor' => [ 'light' => '#374151', 'dark' => '#d1d5db' ],
		] );
		$this->assertCssHas( $css, self::HEADING, 'color:#111827' );
		$this->assertCssHasInDark( $css, self::HEADING, 'color:#f9fafb' );
		$this->assertCssHas( $css, self::DESC, 'color:#374151' );
		$this->assertCssHasInDark( $css, self::DESC, 'color:#d1d5db' );
	}

	public function test_primary_button_colours_and_hover(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'primaryTextColor' => [ 'light' => '#ffffff', 'dark' => '#e5e7eb' ],
			'primaryBgColor'   => [ 'light' => '#2563eb', 'dark' => '#1d4ed8' ],
			'primaryHover'     => [
				'text'       => [ 'light' => '#f9fafb', 'dark' => '#ffffff' ],
				'background' => [ 'light' => '#1e40af', 'dark' => '#1e3a8a' ],
			],
		] );
		$this->assertCssHas( $css, self::BTN_P, 'color:#ffffff' );
		$this->assertCssHasInDark( $css, self::BTN_P, 'color:#e5e7eb' );
		$this->assertCssHas( $css, self::BTN_P, 'background-color:#2563eb' );
		$this->assertCssHasInDark( $css, self::BTN_P, 'background-color:#1d4ed8' );
		$this->assertCssHas( $css, self::BTN_PH, 'color:#f9fafb' );
		$this->assertCssHas( $css, self::BTN_PH, 'background-color:#1e40af' );
	}

	public function test_secondary_button_hidden_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'            => 'a',
			'showSecondary'      => false,
			'secondaryTextColor' => [ 'light' => '#ff0000' ],
		] );
		$this->assertStringNotContainsString( 'flexa-promo__button--secondary', $css );
	}

	public function test_secondary_button_shown_colours(): void {
		$css = $this->gen( [
			'blockId'            => 'a',
			'showSecondary'      => true,
			'secondaryTextColor' => [ 'light' => '#111827', 'dark' => '#f3f4f6' ],
			'secondaryBgColor'   => [ 'light' => '#e5e7eb' ],
		] );
		$this->assertCssHas( $css, self::BTN_S, 'color:#111827' );
		$this->assertCssHasInDark( $css, self::BTN_S, 'color:#f3f4f6' );
		$this->assertCssHas( $css, self::BTN_S, 'background-color:#e5e7eb' );
	}

	public function test_border_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [ 'desktop' => [
				'style'  => 'solid',
				'width'  => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
				'color'  => [ 'light' => '#cccccc', 'dark' => '#333333' ],
				'radius' => [ 'topLeft' => '12', 'topRight' => '12', 'bottomRight' => '12', 'bottomLeft' => '12', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'border-style:solid' );
		$this->assertCssHas( $css, self::WRAP, 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, self::WRAP, 'border-color:#cccccc' );
		$this->assertCssHas( $css, self::WRAP, 'border-radius:12px 12px 12px 12px' );
		$this->assertCssHasInDark( $css, self::WRAP, 'border-color:#333333' );
	}

	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '3' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'overflow:hidden' );
		$this->assertCssHas( $css, self::WRAP, 'position:relative' );
		$this->assertCssHas( $css, self::WRAP, 'z-index:3' );
	}

	public function test_background_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#0f172a', 'dark' => '#020617' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#0f172a' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#020617' );
	}

	public function test_lazy_background_image_gates_url_behind_loaded_class(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://example.com/hero.jpg', 'size' => 'cover' ],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-size:cover' );
		$this->assertCssHas( $css, self::WRAP . '.flexa-bg-loaded', 'background-image:url(https://example.com/hero.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '6', 'blur' => '20', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'box-shadow:0px 6px 20px 0px #000000' );
		$this->assertCssHasInDark( $css, self::WRAP, 'box-shadow:0px 6px 20px 0px #ffffff' );
	}

	public function test_overlay_none_emits_nothing(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'overlay' => [ 'type' => 'none', 'color' => [ 'light' => '#000000' ] ],
		] );
		$this->assertStringNotContainsString( 'flexa-banner__overlay', $css );
	}

	public function test_overlay_colour_opacity_and_blend(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'overlay' => [ 'type' => 'color', 'color' => [ 'light' => '#000000', 'dark' => '#111111' ], 'opacity' => 40, 'blendMode' => 'multiply' ],
		] );
		$this->assertCssHas( $css, self::OVERLAY, 'background:#000000' );
		$this->assertCssHas( $css, self::OVERLAY, 'opacity:0.4' );
		$this->assertCssHas( $css, self::OVERLAY, 'mix-blend-mode:multiply' );
		$this->assertCssHasInDark( $css, self::OVERLAY, 'background:#111111' );
	}

	public function test_overlay_gradient(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'overlay' => [ 'type' => 'gradient', 'gradient' => [ 'light' => 'linear-gradient(0deg,#000,#333)' ], 'opacity' => 60 ],
		] );
		$this->assertCssHas( $css, self::OVERLAY, 'background-image:linear-gradient(0deg,#000,#333)' );
		$this->assertCssHas( $css, self::OVERLAY, 'opacity:0.6' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'      => 'a',
			'headingColor' => [ 'light' => '#111827', 'dark' => '#f3f4f6' ],
		] );

		$this->assertCssHasInDark( $css, self::HEADING, 'color:#f3f4f6', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
