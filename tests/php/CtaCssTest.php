<?php
/**
 * Tests for the CTA block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Cta_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Cta_CSS
 * @covers \Flexa\Block\CSS_Generators\Promo_CSS_Parts
 */
class CtaCssTest extends CssTestCase {

	private const WRAP    = '.flexa-cta-a';
	private const CONTENT = '.flexa-cta-a .flexa-promo__content';
	private const HEADING = '.flexa-cta-a .flexa-promo__heading';
	private const DESC    = '.flexa-cta-a .flexa-promo__description';
	private const BTN_P   = '.flexa-cta-a .flexa-promo__button--primary';
	private const BTN_PH  = '.flexa-cta-a .flexa-promo__button--primary:hover';
	private const BTN_S   = '.flexa-cta-a .flexa-promo__button--secondary';

	/**
	 * Convenience wrapper around the CTA generator.
	 *
	 * @param array $attrs CTA attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Cta_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		$this->assertSame( '', $this->gen( [
			'blockId'       => 'a',
			'arrangement'   => 'centered',
			'containerType' => 'boxed',
			'widthBoxed'    => [ 'desktop' => [], 'tablet' => [], 'mobile' => [] ],
			'size'          => [ 'desktop' => [ 'minHeight' => [ 'value' => '' ] ], 'tablet' => [], 'mobile' => [] ],
			'contentAlign'  => [ 'desktop' => '', 'tablet' => '', 'mobile' => '' ],
			'background'    => [ 'type' => 'none' ],
			'boxShadow'     => [ 'enabled' => false ],
		] ) );
	}

	public function test_spacing_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'desktop' => [
				'padding' => [ 'top' => '48', 'right' => '24', 'bottom' => '48', 'left' => '24', 'unit' => 'px' ],
				'margin'  => [ 'top' => '16', 'right' => '0', 'bottom' => '16', 'left' => '0', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:48px 24px 48px 24px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:16px 0px 16px 0px' );
	}

	public function test_boxed_width_and_full_width(): void {
		$boxed = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'boxed',
			'widthBoxed'    => [ 'desktop' => [ 'value' => '960', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $boxed, self::WRAP, 'max-width:960px' );

		$full = $this->gen( [
			'blockId'        => 'a',
			'containerType'  => 'full-width',
			'widthFullWidth' => [ 'desktop' => [ 'value' => '100', 'unit' => '%' ] ],
		] );
		$this->assertCssHas( $full, self::WRAP, 'width:100%' );
	}

	public function test_min_height_desktop_and_tablet_media(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'size'    => [
				'desktop' => [ 'minHeight' => [ 'value' => '300', 'unit' => 'px' ] ],
				'tablet'  => [ 'minHeight' => [ 'value' => '240', 'unit' => 'px' ] ],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'min-height:300px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'min-height:240px' );
	}

	public function test_content_alignment_centered_emits(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'arrangement'  => 'centered',
			'contentAlign' => [ 'desktop' => 'left' ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'text-align:left' );
		$this->assertCssHas( $css, self::CONTENT, 'align-items:flex-start' );
	}

	public function test_content_alignment_split_is_skipped(): void {
		// In the split layout the wrapper CSS places the text/buttons, so the
		// generator must NOT emit the content alignment.
		$css = $this->gen( [
			'blockId'      => 'a',
			'arrangement'  => 'split',
			'contentAlign' => [ 'desktop' => 'left' ],
		] );
		$this->assertStringNotContainsString( 'text-align', $css );
		$this->assertStringNotContainsString( 'align-items', $css );
	}

	public function test_content_gap_and_max_width(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'contentGap'      => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
			'contentMaxWidth' => [ 'desktop' => [ 'value' => '720', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'gap:24px' );
		$this->assertCssHas( $css, self::CONTENT, 'max-width:720px' );
	}

	public function test_heading_and_description_typography(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'headingTypography'     => [ 'desktop' => [ 'fontSize' => [ 'value' => '32', 'unit' => 'px' ] ] ],
			'descriptionTypography' => [ 'tablet' => [ 'lineHeight' => '1.6' ] ],
		] );
		$this->assertCssHas( $css, self::HEADING, 'font-size:32px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::DESC, 'line-height:1.6' );
	}

	public function test_heading_and_description_colors_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'headingColor'     => [ 'light' => '#0f172a', 'dark' => '#f8fafc' ],
			'descriptionColor' => [ 'light' => '#475569', 'dark' => '#cbd5e1' ],
		] );
		$this->assertCssHas( $css, self::HEADING, 'color:#0f172a' );
		$this->assertCssHasInDark( $css, self::HEADING, 'color:#f8fafc' );
		$this->assertCssHas( $css, self::DESC, 'color:#475569' );
		$this->assertCssHasInDark( $css, self::DESC, 'color:#cbd5e1' );
	}

	public function test_primary_button_colours_and_hover(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'primaryTextColor' => [ 'light' => '#ffffff', 'dark' => '#e5e7eb' ],
			'primaryBgColor'   => [ 'light' => '#16a34a', 'dark' => '#15803d' ],
			'primaryHover'     => [
				'text'       => [ 'light' => '#f0fdf4' ],
				'background' => [ 'light' => '#166534', 'dark' => '#14532d' ],
			],
		] );
		$this->assertCssHas( $css, self::BTN_P, 'color:#ffffff' );
		$this->assertCssHasInDark( $css, self::BTN_P, 'color:#e5e7eb' );
		$this->assertCssHas( $css, self::BTN_P, 'background-color:#16a34a' );
		$this->assertCssHasInDark( $css, self::BTN_P, 'background-color:#15803d' );
		$this->assertCssHas( $css, self::BTN_PH, 'color:#f0fdf4' );
		$this->assertCssHas( $css, self::BTN_PH, 'background-color:#166534' );
		$this->assertCssHasInDark( $css, self::BTN_PH, 'background-color:#14532d' );
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
			'secondaryTextColor' => [ 'light' => '#0f172a', 'dark' => '#f1f5f9' ],
			'secondaryBgColor'   => [ 'light' => '#e2e8f0' ],
		] );
		$this->assertCssHas( $css, self::BTN_S, 'color:#0f172a' );
		$this->assertCssHasInDark( $css, self::BTN_S, 'color:#f1f5f9' );
		$this->assertCssHas( $css, self::BTN_S, 'background-color:#e2e8f0' );
	}

	public function test_border_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [ 'desktop' => [
				'style'  => 'solid',
				'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
				'color'  => [ 'light' => '#e5e7eb', 'dark' => '#334155' ],
				'radius' => [ 'topLeft' => '16', 'topRight' => '16', 'bottomRight' => '16', 'bottomLeft' => '16', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'border-style:solid' );
		$this->assertCssHas( $css, self::WRAP, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::WRAP, 'border-color:#e5e7eb' );
		$this->assertCssHas( $css, self::WRAP, 'border-radius:16px 16px 16px 16px' );
		$this->assertCssHasInDark( $css, self::WRAP, 'border-color:#334155' );
	}

	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '2' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'overflow:hidden' );
		$this->assertCssHas( $css, self::WRAP, 'position:relative' );
		$this->assertCssHas( $css, self::WRAP, 'z-index:2' );
	}

	public function test_background_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#f8fafc', 'dark' => '#020617' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#f8fafc' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#020617' );
	}

	public function test_gradient_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'gradient',
				'gradient' => [ 'light' => 'linear-gradient(90deg,#aaa,#bbb)', 'dark' => 'linear-gradient(90deg,#111,#222)' ],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-image:linear-gradient(90deg,#aaa,#bbb)' );
		$this->assertStringContainsString( 'linear-gradient(90deg,#111,#222)', $css );
	}

	public function test_lazy_background_image_gates_url_behind_loaded_class(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://example.com/cta.jpg', 'size' => 'cover' ],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-size:cover' );
		$this->assertCssHas( $css, self::WRAP . '.flexa-bg-loaded', 'background-image:url(https://example.com/cta.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '4', 'blur' => '16', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'box-shadow:0px 4px 16px 0px #000000' );
		$this->assertCssHasInDark( $css, self::WRAP, 'box-shadow:0px 4px 16px 0px #ffffff' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'      => 'a',
			'headingColor' => [ 'light' => '#0f172a', 'dark' => '#f8fafc' ],
		] );

		$this->assertCssHasInDark( $css, self::HEADING, 'color:#f8fafc', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
