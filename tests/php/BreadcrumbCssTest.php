<?php
/**
 * Tests for the Breadcrumb block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional colours and light/dark parity. The trail typography / colours are
 * emitted only when the user supplies a value, so an untouched breadcrumb emits
 * nothing (its appearance comes from style.scss + the theme).
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Breadcrumb_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Breadcrumb_CSS
 */
class BreadcrumbCssTest extends CssTestCase {

	private const WRAP    = '.flexa-breadcrumb-a';
	private const LIST    = '.flexa-breadcrumb-a .flexa-breadcrumb__list';
	private const ITEM    = '.flexa-breadcrumb-a .flexa-breadcrumb__item';
	private const LINK    = '.flexa-breadcrumb-a .flexa-breadcrumb__link';
	private const LINK_ON = '.flexa-breadcrumb-a .flexa-breadcrumb__link:hover';
	private const CURRENT = '.flexa-breadcrumb-a .flexa-breadcrumb__current';
	private const CUR_ON  = '.flexa-breadcrumb-a .flexa-breadcrumb__current:hover';
	private const SEP     = '.flexa-breadcrumb-a .flexa-breadcrumb__separator';
	private const SEP_ON  = '.flexa-breadcrumb-a .flexa-breadcrumb__separator:hover';

	/**
	 * Convenience wrapper around the breadcrumb generator.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Breadcrumb_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// No values picked → no CSS: the structural look comes from style.scss.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_trail_typography_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [
				'desktop' => [
					'fontSize'      => [ 'value' => '14', 'unit' => 'px' ],
					'fontWeight'    => '500',
					'letterSpacing' => [ 'value' => '1', 'unit' => 'px' ],
					'textTransform' => 'uppercase',
					'lineHeight'    => '1.4',
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'font-size:14px' );
		$this->assertCssHas( $css, self::WRAP, 'font-weight:500' );
		$this->assertCssHas( $css, self::WRAP, 'letter-spacing:1px' );
		$this->assertCssHas( $css, self::WRAP, 'text-transform:uppercase' );
		$this->assertCssHas( $css, self::WRAP, 'line-height:1.4' );
	}

	public function test_max_width_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'maxWidth' => [ 'desktop' => [ 'value' => '600', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'max-width:600px' );
	}

	public function test_alignment_maps_to_justify_content(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, self::LIST, 'justify-content:center' );

		$right = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'right' ],
		] );
		$this->assertCssHas( $right, self::LIST, 'justify-content:flex-end' );
	}

	public function test_item_gap_on_list_and_item(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'itemGap' => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::LIST, 'gap:12px' );
		$this->assertCssHas( $css, self::ITEM, 'gap:12px' );
	}

	public function test_link_colour_light_and_dark_plus_hover(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'linkColor'      => [ 'light' => '#1e40af', 'dark' => '#93c5fd' ],
			'linkColorHover' => [ 'light' => '#2563eb', 'dark' => '#bfdbfe' ],
		] );
		$this->assertCssHas( $css, self::LINK, 'color:#1e40af' );
		$this->assertCssHas( $css, self::LINK_ON, 'color:#2563eb' );
		$this->assertCssHasInDark( $css, self::LINK, 'color:#93c5fd' );
		$this->assertCssHasInDark( $css, self::LINK_ON, 'color:#bfdbfe' );
	}

	public function test_current_colour_light_and_dark_plus_hover(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'currentColor'      => [ 'light' => '#111827', 'dark' => '#f3f4f6' ],
			'currentColorHover' => [ 'light' => '#374151', 'dark' => '#e5e7eb' ],
		] );
		$this->assertCssHas( $css, self::CURRENT, 'color:#111827' );
		$this->assertCssHas( $css, self::CUR_ON, 'color:#374151' );
		$this->assertCssHasInDark( $css, self::CURRENT, 'color:#f3f4f6' );
		$this->assertCssHasInDark( $css, self::CUR_ON, 'color:#e5e7eb' );
	}

	public function test_separator_colour_light_and_dark_plus_hover(): void {
		$css = $this->gen( [
			'blockId'             => 'a',
			'separatorColor'      => [ 'light' => '#9ca3af', 'dark' => '#6b7280' ],
			'separatorColorHover' => [ 'light' => '#4b5563', 'dark' => '#d1d5db' ],
		] );
		$this->assertCssHas( $css, self::SEP, 'color:#9ca3af' );
		$this->assertCssHas( $css, self::SEP_ON, 'color:#4b5563' );
		$this->assertCssHasInDark( $css, self::SEP, 'color:#6b7280' );
		$this->assertCssHasInDark( $css, self::SEP_ON, 'color:#d1d5db' );
	}

	public function test_colours_absent_when_unset(): void {
		// Only a link colour is set → no current / separator colour rules appear.
		$css = $this->gen( [
			'blockId'   => 'a',
			'linkColor' => [ 'light' => '#000000' ],
		] );
		$this->assertStringNotContainsString( '__current', $css );
		$this->assertStringNotContainsString( '__separator', $css );
		$this->assertStringNotContainsString( ':hover', $css );
	}

	public function test_tablet_values_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'itemGap'   => [ 'tablet' => [ 'value' => '6', 'unit' => 'px' ] ],
			'alignment' => [ 'tablet' => 'center' ],
			'maxWidth'  => [ 'tablet' => [ 'value' => '400', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::LIST, 'gap:6px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::LIST, 'justify-content:center' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'max-width:400px' );
	}

	public function test_wrapper_spacing_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '8', 'right' => '12', 'bottom' => '8', 'left' => '12', 'unit' => 'px' ],
					'margin'  => [ 'top' => '16', 'right' => '0', 'bottom' => '16', 'left' => '0', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:8px 12px 8px 12px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:16px 0px 16px 0px' );
	}

	public function test_wrapper_border_all_sub_properties(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#e5e7eb', 'dark' => '#374151' ],
					'radius' => [ 'topLeft' => '6', 'topRight' => '6', 'bottomRight' => '6', 'bottomLeft' => '6', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'border-style:solid' );
		$this->assertCssHas( $css, self::WRAP, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::WRAP, 'border-color:#e5e7eb' );
		$this->assertCssHas( $css, self::WRAP, 'border-radius:6px 6px 6px 6px' );
		$this->assertCssHasInDark( $css, self::WRAP, 'border-color:#374151' );
	}

	public function test_advanced_layout_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '3' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'overflow:hidden' );
		$this->assertCssHas( $css, self::WRAP, 'position:relative' );
		$this->assertCssHas( $css, self::WRAP, 'z-index:3' );
	}

	public function test_wrapper_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#ffffff' );
		// Full property:value in dark — a bare "#000" would also match the light hex.
		$this->assertStringContainsString( 'background-color:#000000', $css );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
	}

	public function test_background_image_sub_properties(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'  => 'image',
				'image' => [ 'url' => 'https://example.com/bg.jpg', 'position' => 'top left', 'size' => 'contain', 'repeat' => 'repeat-x', 'attachment' => 'fixed' ],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-image:url(https://example.com/bg.jpg)' );
		$this->assertCssHas( $css, self::WRAP, 'background-position:top left' );
		$this->assertCssHas( $css, self::WRAP, 'background-size:contain' );
		$this->assertCssHas( $css, self::WRAP, 'background-repeat:repeat-x' );
		$this->assertCssHas( $css, self::WRAP, 'background-attachment:fixed' );
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
		$this->assertCssHas( $css, self::WRAP . '.flexa-bg-loaded', 'background-image:url(https://example.com/bg.jpg)' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'   => 'a',
			'linkColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::LINK, 'color:#eeeeee', true );
		$this->assertStringContainsString( '[data-theme="dark"] ' . self::LINK, $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
