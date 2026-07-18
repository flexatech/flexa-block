<?php
/**
 * Tests for the Tabs block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional bits (indicator colour-vs-gradient, content background, border) and
 * light/dark parity.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Tabs_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Tabs_CSS
 */
class TabsCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Tabs generator.
	 *
	 * @param array $attrs Tabs attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Tabs_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Theme-first: an untouched block (defaults only) must emit no declarations.
		$css = $this->gen( [
			'blockId'    => 'a',
			'tabStyle'   => 'underline',
			'tabAlign'   => 'left',
			'background' => [ 'type' => 'none', 'color' => [ 'light' => '', 'dark' => '' ] ],
			'boxShadow'  => [ 'enabled' => false ],
			'border'     => [ 'desktop' => [ 'style' => '', 'width' => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ], 'color' => [ 'light' => '', 'dark' => '' ], 'radius' => [] ] ],
			'tabColor'   => [ 'light' => '', 'dark' => '' ],
		] );
		$this->assertSame( '', $css );
	}

	public function test_max_width_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'maxWidth' => [ 'desktop' => [ 'value' => '800', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a', 'max-width:800px' );
	}

	public function test_tab_gap_and_icon_gap(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'tabGap'  => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ] ],
			'iconGap' => [ 'desktop' => [ 'value' => '6', 'unit' => 'px' ] ],
		] );
		// Tab gap sits on the nav; icon gap sits on the tab.
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__nav', 'gap:12px' );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__tab', 'gap:6px' );
	}

	public function test_icon_size(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'iconSize' => [ 'desktop' => [ 'value' => '20', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__icon', 'width:20px' );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__icon', 'height:20px' );
	}

	public function test_tab_and_content_padding(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'tabPadding'     => [ 'desktop' => [ 'top' => '10', 'right' => '16', 'bottom' => '10', 'left' => '16', 'unit' => 'px' ] ],
			'contentPadding' => [ 'desktop' => [ 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__tab', 'padding:10px 16px 10px 16px' );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__panel', 'padding:20px 20px 20px 20px' );
	}

	public function test_tab_typography(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'tabTypography' => [
				'desktop' => [
					'fontSize'      => [ 'value' => '18', 'unit' => 'px' ],
					'fontWeight'    => '600',
					'letterSpacing' => [ 'value' => '1', 'unit' => 'px' ],
					'textTransform' => 'uppercase',
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__tab', 'font-size:18px' );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__tab', 'font-weight:600' );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__tab', 'letter-spacing:1px' );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__tab', 'text-transform:uppercase' );
	}

	public function test_content_typography(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'contentTypography' => [ 'desktop' => [ 'lineHeight' => '1.7' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__panel', 'line-height:1.7' );
	}

	public function test_tab_colours_normal_hover_active_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'tabColor'       => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
			'tabHoverColor'  => [ 'light' => '#2563eb' ],
			'tabActiveColor' => [ 'light' => '#1d4ed8', 'dark' => '#93c5fd' ],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__tab', 'color:#111111' );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__tab:hover', 'color:#2563eb' );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__tab.is-active', 'color:#1d4ed8' );
		// Dark values are emitted with the FULL declaration inside the dark branch.
		$this->assertCssHasInDark( $css, '.flexa-tabs-a .flexa-tabs__tab', 'color:#eeeeee' );
		$this->assertCssHasInDark( $css, '.flexa-tabs-a .flexa-tabs__tab.is-active', 'color:#93c5fd' );
	}

	public function test_active_indicator_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'            => 'a',
			'tabActiveIndicator' => [ 'light' => '#2563eb', 'dark' => '#60a5fa' ],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a', '--flexa-tabs-indicator:#2563eb' );
		$this->assertCssHasInDark( $css, '.flexa-tabs-a', '--flexa-tabs-indicator:#60a5fa' );
	}

	public function test_active_indicator_gradient_replaces_colour(): void {
		$css = $this->gen( [
			'blockId'                    => 'a',
			'tabActiveIndicatorType'     => 'gradient',
			'tabActiveIndicatorGradient' => [ 'light' => 'linear-gradient(90deg,#f00,#00f)' ],
			'tabActiveIndicator'         => [ 'light' => '#2563eb' ],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a', '--flexa-tabs-indicator:linear-gradient(90deg,#f00,#00f)' );
		// The solid colour must NOT be emitted when the gradient type is selected.
		$this->assertStringNotContainsString( '#2563eb', $css );
	}

	public function test_content_colour_and_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'contentColor'      => [ 'light' => '#333333', 'dark' => '#dddddd' ],
			'contentBackground' => [ 'light' => '#f0f0f0', 'dark' => '#222222' ],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__panel', 'color:#333333' );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__panel', 'background:#f0f0f0' );
		$this->assertCssHasInDark( $css, '.flexa-tabs-a .flexa-tabs__panel', 'color:#dddddd' );
		$this->assertCssHasInDark( $css, '.flexa-tabs-a .flexa-tabs__panel', 'background:#222222' );
	}

	public function test_content_background_gradient_replaces_colour(): void {
		$css = $this->gen( [
			'blockId'                   => 'a',
			'contentBackgroundType'     => 'gradient',
			'contentBackgroundGradient' => [ 'light' => 'linear-gradient(0deg,#aaa,#bbb)' ],
			'contentBackground'         => [ 'light' => '#f0f0f0' ],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__panel', 'background-image:linear-gradient(0deg,#aaa,#bbb)' );
		$this->assertStringNotContainsString( 'background:#f0f0f0', $css );
	}

	public function test_border_outlines_content_panel_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
					'color'  => [ 'light' => '#dddddd', 'dark' => '#444444' ],
					'radius' => [ 'topLeft' => '8', 'topRight' => '8', 'bottomRight' => '8', 'bottomLeft' => '8', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__panel', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__panel', 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__panel', 'border-color:#dddddd' );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__panel', 'border-radius:8px 8px 8px 8px' );
		// Dark border colour in the dark branch (full declaration).
		$this->assertCssHasInDark( $css, '.flexa-tabs-a .flexa-tabs__panel', 'border-color:#444444' );
	}

	public function test_border_absent_when_no_style(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [ 'desktop' => [ 'style' => '' ] ],
		] );
		$this->assertStringNotContainsString( 'border-style:', $css );
	}

	public function test_tablet_values_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'tabGap'  => [ 'tablet' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		// Assert the value lives INSIDE the tablet media query, not just anywhere.
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-tabs-a .flexa-tabs__nav', 'gap:8px' );
	}

	public function test_wrapper_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a', 'background-color:#ffffff' );
		// Assert the FULL dark declaration (a bare '#000' would match nothing useful).
		$this->assertCssHasInDark( $css, '.flexa-tabs-a', 'background-color:#000000' );
	}

	public function test_box_shadow_on_panel_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000', 'dark' => '#fff' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a .flexa-tabs__panel', 'box-shadow:0px 2px 8px 0px #000' );
		$this->assertCssHasInDark( $css, '.flexa-tabs-a .flexa-tabs__panel', 'box-shadow:0px 2px 8px 0px #fff' );
	}

	public function test_wrapper_spacing_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '24', 'right' => '16', 'bottom' => '24', 'left' => '16', 'unit' => 'px' ],
					'margin'  => [ 'top' => '32', 'right' => '0', 'bottom' => '32', 'left' => '0', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a', 'padding:24px 16px 24px 16px' );
		$this->assertCssHas( $css, '.flexa-tabs-a', 'margin:32px 0px 32px 0px' );
	}

	public function test_advanced_layout_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '10' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-tabs-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-tabs-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-tabs-a', 'z-index:10' );
	}

	public function test_lazy_background_image_prints_url_once_behind_loaded_class(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://example.com/bg.jpg', 'position' => 'center center', 'repeat' => 'no-repeat', 'size' => 'cover' ],
			],
		] );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
		$this->assertCssHas( $css, '.flexa-tabs-a.flexa-bg-loaded', 'background-image:url(https://example.com/bg.jpg)' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'  => 'a',
			'tabColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, '.flexa-tabs-a .flexa-tabs__tab', 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
