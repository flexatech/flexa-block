<?php
/**
 * Tests for the Info Box block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Info_Box_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Info_Box_CSS
 */
class InfoBoxCssTest extends CssTestCase {

	private const WRAP    = '.flexa-info-box-a';
	private const MEDIA   = '.flexa-info-box-a .flexa-info-box__media';
	private const ICON    = '.flexa-info-box-a .flexa-info-box__icon';
	private const IMAGE   = '.flexa-info-box-a .flexa-info-box__image';
	private const CONTENT = '.flexa-info-box-a .flexa-info-box__content';
	private const PREFIX  = '.flexa-info-box-a .flexa-info-box__prefix';
	private const TITLE   = '.flexa-info-box-a .flexa-info-box__title';
	private const SEP     = '.flexa-info-box-a .flexa-info-box__separator';
	private const DESC    = '.flexa-info-box-a .flexa-info-box__description';
	private const BUTTON  = '.flexa-info-box-a .flexa-info-box__button';
	private const BTN_HOV = '.flexa-info-box-a .flexa-info-box__button:hover';

	/**
	 * Convenience wrapper around the Info Box generator.
	 *
	 * @param array $attrs Info box attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Info_Box_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// A block whose styling is all left at its (empty) defaults must inherit the
		// theme + the base style.scss — the generator emits no declarations.
		$this->assertSame( '', $this->gen( [
			'blockId'        => 'a',
			'iconPosition'   => 'top',
			'mediaType'      => 'icon',
			'separatorStyle' => 'solid',
			'showSeparator'  => false,
			'alignment'      => [ 'desktop' => '', 'tablet' => '', 'mobile' => '' ],
			'mediaGap'       => [ 'desktop' => [], 'tablet' => [], 'mobile' => [] ],
			'contentGap'     => [ 'desktop' => [], 'tablet' => [], 'mobile' => [] ],
			'background'     => [ 'type' => 'none' ],
			'boxShadow'      => [ 'enabled' => false ],
		] ) );
	}

	public function test_alignment_icon_top_sets_wrap_and_content(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'iconPosition' => 'top',
			'alignment'    => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
		$this->assertCssHas( $css, self::WRAP, 'align-items:center' );
		$this->assertCssHas( $css, self::CONTENT, 'align-items:center' );
	}

	public function test_alignment_icon_left_skips_wrap_cross_axis(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'iconPosition' => 'left',
			'alignment'    => [ 'desktop' => 'right' ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:right' );
		$this->assertCssHas( $css, self::CONTENT, 'align-items:flex-end' );
		// The row layout keeps the media pinned to the top → no wrap align-items.
		$this->assertDoesNotMatchRegularExpression(
			'/' . preg_quote( self::WRAP, '/' ) . '\s*\{[^}]*align-items/',
			$css
		);
	}

	public function test_gaps_media_and_content(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'mediaGap'   => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
			'contentGap' => [ 'desktop' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'gap:24px' );
		$this->assertCssHas( $css, self::CONTENT, 'gap:10px' );
	}

	public function test_media_gap_tablet_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'mediaGap' => [ 'tablet' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'gap:12px' );
	}

	public function test_icon_size_and_image_width(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'iconSize'   => [ 'desktop' => [ 'value' => '48', 'unit' => 'px' ] ],
			'imageWidth' => [ 'desktop' => [ 'value' => '120', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::ICON, 'font-size:48px' );
		$this->assertCssHas( $css, self::IMAGE, 'width:120px' );
	}

	public function test_media_padding_and_radius(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'mediaPadding' => [ 'desktop' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ] ],
			'mediaRadius'  => [ 'desktop' => [ 'value' => '50', 'unit' => '%' ] ],
		] );
		$this->assertCssHas( $css, self::MEDIA, 'padding:10px 10px 10px 10px' );
		$this->assertCssHas( $css, self::MEDIA, 'border-radius:50%' );
		$this->assertCssHas( $css, self::IMAGE, 'border-radius:50%' );
	}

	public function test_typography_on_text_parts(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'prefixTypography'      => [ 'desktop' => [ 'fontWeight' => '600' ] ],
			'titleTypography'       => [ 'desktop' => [ 'fontSize' => [ 'value' => '22', 'unit' => 'px' ] ] ],
			'descriptionTypography' => [ 'desktop' => [ 'lineHeight' => '1.6' ] ],
		] );
		$this->assertCssHas( $css, self::PREFIX, 'font-weight:600' );
		$this->assertCssHas( $css, self::TITLE, 'font-size:22px' );
		$this->assertCssHas( $css, self::DESC, 'line-height:1.6' );
	}

	public function test_title_typography_tablet_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'titleTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '18', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::TITLE, 'font-size:18px' );
	}

	public function test_wrapper_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'desktop' => [
				'padding' => [ 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px' ],
				'margin'  => [ 'top' => '10', 'right' => '0', 'bottom' => '10', 'left' => '0', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:20px 20px 20px 20px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:10px 0px 10px 0px' );
	}

	public function test_border_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [ 'desktop' => [
				'style' => 'solid',
				'width' => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
				'color' => [ 'light' => '#cccccc', 'dark' => '#333333' ],
				'radius' => [ 'topLeft' => '8', 'topRight' => '8', 'bottomRight' => '8', 'bottomLeft' => '8', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'border-style:solid' );
		$this->assertCssHas( $css, self::WRAP, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::WRAP, 'border-color:#cccccc' );
		$this->assertCssHas( $css, self::WRAP, 'border-radius:8px 8px 8px 8px' );
		$this->assertCssHasInDark( $css, self::WRAP, 'border-color:#333333' );
	}

	public function test_icon_and_media_background_colours(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'iconColor'       => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
			'mediaBackground' => [ 'light' => '#eef2ff', 'dark' => '#1e293b' ],
		] );
		$this->assertCssHas( $css, self::ICON, 'color:#2563eb' );
		$this->assertCssHasInDark( $css, self::ICON, 'color:#93c5fd' );
		$this->assertCssHas( $css, self::MEDIA, 'background-color:#eef2ff' );
		$this->assertCssHasInDark( $css, self::MEDIA, 'background-color:#1e293b' );
	}

	public function test_text_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'prefixColor'      => [ 'light' => '#6b7280', 'dark' => '#9ca3af' ],
			'titleColor'       => [ 'light' => '#111827', 'dark' => '#f3f4f6' ],
			'descriptionColor' => [ 'light' => '#374151', 'dark' => '#d1d5db' ],
		] );
		$this->assertCssHas( $css, self::PREFIX, 'color:#6b7280' );
		$this->assertCssHasInDark( $css, self::PREFIX, 'color:#9ca3af' );
		$this->assertCssHas( $css, self::TITLE, 'color:#111827' );
		$this->assertCssHasInDark( $css, self::TITLE, 'color:#f3f4f6' );
		$this->assertCssHas( $css, self::DESC, 'color:#374151' );
		$this->assertCssHasInDark( $css, self::DESC, 'color:#d1d5db' );
	}

	public function test_separator_hidden_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'showSeparator'  => false,
			'separatorStyle' => 'dashed',
			'separatorColor' => [ 'light' => '#ff0000' ],
		] );
		$this->assertStringNotContainsString( 'border-top-style', $css );
		$this->assertStringNotContainsString( 'border-top-color', $css );
	}

	public function test_separator_shown_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'showSeparator'   => true,
			'separatorWidth'  => [ 'value' => '80', 'unit' => 'px' ],
			'separatorWeight' => [ 'value' => '3', 'unit' => 'px' ],
			'separatorStyle'  => 'dashed',
			'separatorColor'  => [ 'light' => '#e5e7eb', 'dark' => '#4b5563' ],
		] );
		$this->assertCssHas( $css, self::SEP, 'width:80px' );
		$this->assertCssHas( $css, self::SEP, 'border-top-width:3px' );
		$this->assertCssHas( $css, self::SEP, 'border-top-style:dashed' );
		$this->assertCssHas( $css, self::SEP, 'border-top-color:#e5e7eb' );
		$this->assertCssHasInDark( $css, self::SEP, 'border-top-color:#4b5563' );
	}

	public function test_button_colours_and_hover(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'buttonTextColor' => [ 'light' => '#ffffff', 'dark' => '#e5e7eb' ],
			'buttonBgColor'   => [ 'light' => '#2563eb', 'dark' => '#1d4ed8' ],
			'buttonHover'     => [
				'text'       => [ 'light' => '#f9fafb', 'dark' => '#ffffff' ],
				'background' => [ 'light' => '#1e40af', 'dark' => '#1e3a8a' ],
			],
		] );
		$this->assertCssHas( $css, self::BUTTON, 'color:#ffffff' );
		$this->assertCssHasInDark( $css, self::BUTTON, 'color:#e5e7eb' );
		$this->assertCssHas( $css, self::BUTTON, 'background-color:#2563eb' );
		$this->assertCssHasInDark( $css, self::BUTTON, 'background-color:#1d4ed8' );
		$this->assertCssHas( $css, self::BTN_HOV, 'color:#f9fafb' );
		$this->assertCssHasInDark( $css, self::BTN_HOV, 'color:#ffffff' );
		$this->assertCssHas( $css, self::BTN_HOV, 'background-color:#1e40af' );
		$this->assertCssHasInDark( $css, self::BTN_HOV, 'background-color:#1e3a8a' );
	}

	public function test_background_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#ffffff' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#000000' );
	}

	public function test_gradient_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'gradient',
				'gradient' => [ 'light' => 'linear-gradient(0deg,#aaa,#bbb)', 'dark' => 'linear-gradient(0deg,#111,#222)' ],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-image:linear-gradient(0deg,#aaa,#bbb)' );
		$this->assertStringContainsString( 'linear-gradient(0deg,#111,#222)', $css );
	}

	public function test_lazy_background_image_gates_url_behind_loaded_class(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://example.com/a.jpg', 'size' => 'cover' ],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-size:cover' );
		$this->assertCssHas( $css, self::WRAP . '.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'box-shadow:0px 2px 8px 0px #000000' );
		$this->assertCssHasInDark( $css, self::WRAP, 'box-shadow:0px 2px 8px 0px #ffffff' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'    => 'a',
			'titleColor' => [ 'light' => '#111827', 'dark' => '#f3f4f6' ],
		] );

		$this->assertCssHasInDark( $css, self::TITLE, 'color:#f3f4f6', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
