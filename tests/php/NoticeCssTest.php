<?php
/**
 * Tests for the Notice block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional icon position / colours / typography and light/dark parity. Nothing
 * is emitted unless the user picks a value (theme-first) — an untouched notice
 * keeps the theme + the subtle per-type accent from style.scss.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Notice_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Notice_CSS
 */
class NoticeCssTest extends CssTestCase {

	private const WRAP    = '.flexa-notice-a';
	private const INNER   = '.flexa-notice-a .flexa-notice__inner';
	private const ICON    = '.flexa-notice-a .flexa-notice__icon';
	private const BODY    = '.flexa-notice-a .flexa-notice__body';
	private const TITLE   = '.flexa-notice-a .flexa-notice__title';
	private const CONTENT = '.flexa-notice-a .flexa-notice__content';

	/**
	 * Convenience wrapper around the notice generator.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Notice_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Everything left at its (empty) defaults + the default icon position ("left")
		// must inherit the theme and the base style.scss — no declarations emitted.
		$this->assertSame( '', $this->gen( [
			'blockId'      => 'a',
			'noticeType'   => 'info',
			'iconPosition' => 'left',
			'showIcon'     => true,
			'showTitle'    => true,
			'alignment'    => [ 'desktop' => '', 'tablet' => '', 'mobile' => '' ],
			'iconSize'     => [ 'desktop' => [], 'tablet' => [], 'mobile' => [] ],
			'iconColor'    => [ 'light' => '', 'dark' => '' ],
			'titleColor'   => [ 'light' => '', 'dark' => '' ],
			'contentColor' => [ 'light' => '', 'dark' => '' ],
			'spacing'      => [ 'desktop' => [], 'tablet' => [], 'mobile' => [] ],
			'background'   => [ 'type' => 'none' ],
			'boxShadow'    => [ 'enabled' => false ],
		] ) );
	}

	public function test_icon_position_top_sets_flex_direction(): void {
		$top = $this->gen( [ 'blockId' => 'a', 'iconPosition' => 'top' ] );
		$this->assertCssHas( $top, self::INNER, 'flex-direction:column' );

		// "left" is the style.scss default row, so the generator emits nothing.
		$left = $this->gen( [ 'blockId' => 'a', 'iconPosition' => 'left' ] );
		$this->assertStringNotContainsString( 'flex-direction', $left );
	}

	public function test_alignment_maps_to_text_align_on_body(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, self::BODY, 'text-align:center' );

		$right = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'right' ],
		] );
		$this->assertCssHas( $right, self::BODY, 'text-align:right' );
	}

	public function test_alignment_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'text-align', $css );
	}

	public function test_alignment_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'tablet' => 'center' ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::BODY, 'text-align:center' );
	}

	public function test_icon_size_sets_width_height_font_size(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'iconSize' => [ 'desktop' => [ 'value' => '32', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::ICON, 'width:32px' );
		$this->assertCssHas( $css, self::ICON, 'height:32px' );
		$this->assertCssHas( $css, self::ICON, 'font-size:32px' );
	}

	public function test_icon_size_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( '.flexa-notice__icon{', $css );
	}

	public function test_icon_size_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'iconSize' => [ 'tablet' => [ 'value' => '24', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::ICON, 'font-size:24px' );
	}

	public function test_title_and_message_typography(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'titleTypography'   => [ 'desktop' => [ 'fontSize' => [ 'value' => '18', 'unit' => 'px' ], 'fontWeight' => '700' ] ],
			'contentTypography' => [ 'desktop' => [ 'lineHeight' => '1.6' ] ],
		] );
		$this->assertCssHas( $css, self::TITLE, 'font-size:18px' );
		$this->assertCssHas( $css, self::TITLE, 'font-weight:700' );
		$this->assertCssHas( $css, self::CONTENT, 'line-height:1.6' );
	}

	public function test_title_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'titleTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '16', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::TITLE, 'font-size:16px' );
	}

	public function test_wrapper_spacing_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '16', 'right' => '20', 'bottom' => '16', 'left' => '20', 'unit' => 'px' ],
					'margin'  => [ 'top' => '0', 'right' => 'auto', 'bottom' => '24', 'left' => 'auto', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:16px 20px 16px 20px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:0px auto 24px auto' );
	}

	public function test_spacing_padding_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'tablet' => [ 'padding' => [ 'top' => '8', 'right' => '10', 'bottom' => '8', 'left' => '10', 'unit' => 'px' ] ],
			],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'padding:8px 10px 8px 10px' );
	}

	public function test_border_all_sub_properties_plus_dark(): void {
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

	public function test_icon_title_message_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'iconColor'    => [ 'light' => '#0d6efd', 'dark' => '#66aaff' ],
			'titleColor'   => [ 'light' => '#111827', 'dark' => '#f9fafb' ],
			'contentColor' => [ 'light' => '#374151', 'dark' => '#d1d5db' ],
		] );
		$this->assertCssHas( $css, self::ICON, 'color:#0d6efd' );
		$this->assertCssHas( $css, self::TITLE, 'color:#111827' );
		$this->assertCssHas( $css, self::CONTENT, 'color:#374151' );
		$this->assertCssHasInDark( $css, self::ICON, 'color:#66aaff' );
		$this->assertCssHasInDark( $css, self::TITLE, 'color:#f9fafb' );
		$this->assertCssHasInDark( $css, self::CONTENT, 'color:#d1d5db' );
	}

	public function test_accent_colour_overrides_left_border_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'accentColor' => [ 'light' => '#7c3aed', 'dark' => '#a78bfa' ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'border-left-color:#7c3aed' );
		$this->assertCssHasInDark( $css, self::WRAP, 'border-left-color:#a78bfa' );
	}

	public function test_accent_colour_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'border-left-color', $css );
	}

	public function test_colours_absent_when_unset(): void {
		// Only the title colour is set → no icon / message colour rules.
		$css = $this->gen( [
			'blockId'    => 'a',
			'titleColor' => [ 'light' => '#000000' ],
		] );
		$this->assertStringNotContainsString( '.flexa-notice__icon{', $css );
		$this->assertStringNotContainsString( '.flexa-notice__content{', $css );
	}

	public function test_wrapper_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#eff6ff', 'dark' => '#0f172a' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#eff6ff' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#0f172a' );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
	}

	public function test_wrapper_background_image_sub_properties(): void {
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

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [
				'enabled'    => true,
				'horizontal' => '0',
				'vertical'   => '4',
				'blur'       => '12',
				'spread'     => '0',
				'color'      => [ 'light' => 'rgba(0,0,0,0.12)', 'dark' => 'rgba(0,0,0,0.6)' ],
				'inset'      => false,
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'box-shadow:0px 4px 12px 0px rgba(0,0,0,0.12)' );
		$this->assertCssHasInDark( $css, self::WRAP, 'box-shadow:0px 4px 12px 0px rgba(0,0,0,0.6)' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'    => 'a',
			'titleColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::TITLE, 'color:#eeeeee', true );
		$this->assertStringContainsString( '[data-theme="dark"] ' . self::TITLE, $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
