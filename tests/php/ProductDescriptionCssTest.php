<?php
/**
 * Tests for the Product Description block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Product_Description_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Product_Description_CSS
 */
class ProductDescriptionCssTest extends CssTestCase {

	private const WRAP    = '.flexa-product-description-a';
	private const TITLE   = '.flexa-product-description-a .flexa-product-description__title';
	private const CONTENT = '.flexa-product-description-a .flexa-product-description__content';
	private const TOGGLE  = '.flexa-product-description-a .flexa-product-description__toggle';
	private const HEADING = '.flexa-product-description-a .flexa-product-description__content h2, .flexa-product-description-a .flexa-product-description__content h3, .flexa-product-description-a .flexa-product-description__content h4';
	private const LINK    = '.flexa-product-description-a .flexa-product-description__content a';
	private const HOVER   = '.flexa-product-description-a .flexa-product-description__content a:hover';
	private const LIST    = '.flexa-product-description-a .flexa-product-description__content ul, .flexa-product-description-a .flexa-product-description__content ol';
	private const CELL    = '.flexa-product-description-a .flexa-product-description__content th, .flexa-product-description-a .flexa-product-description__content td';

	/**
	 * Convenience wrapper around the Product Description generator.
	 *
	 * @param array $attrs Product-description attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Product_Description_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Only a blockId: the theme should style everything, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'source' => 'long', 'titleTag' => 'h3' ] ) );
	}

	public function test_alignment_on_wrapper(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
	}

	public function test_alignment_tablet_in_media_query(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'tablet' => 'right' ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'text-align:right' );
	}

	public function test_body_typography_on_content(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '16', 'unit' => 'px' ], 'fontWeight' => '400' ] ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'font-size:16px' );
		$this->assertCssHas( $css, self::CONTENT, 'font-weight:400' );
	}

	public function test_body_typography_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [ 'mobile' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::CONTENT, 'font-size:14px' );
	}

	public function test_text_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'textColor' => [ 'light' => '#333333', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, self::CONTENT, 'color:#333333' );
		$this->assertCssHasInDark( $css, self::CONTENT, 'color:#eeeeee' );
	}

	public function test_title_colour_and_typography_when_shown(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'showTitle'       => true,
			'titleColor'      => [ 'light' => '#111111', 'dark' => '#fafafa' ],
			'titleTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '22', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, self::TITLE, 'color:#111111' );
		$this->assertCssHasInDark( $css, self::TITLE, 'color:#fafafa' );
		$this->assertCssHas( $css, self::TITLE, 'font-size:22px' );
	}

	public function test_title_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'showTitle'       => true,
			'titleTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '20', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::TITLE, 'font-size:20px' );
	}

	public function test_title_gated_off_when_hidden(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'showTitle'       => false,
			'titleColor'      => [ 'light' => '#111111', 'dark' => '#fafafa' ],
			'titleTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '22', 'unit' => 'px' ] ] ],
		] );
		$this->assertStringNotContainsString( 'flexa-product-description__title', $css );
	}

	public function test_inner_heading_colour_and_typography(): void {
		$css = $this->gen( [
			'blockId'                => 'a',
			'innerHeadingColor'      => [ 'light' => '#0a0a0a', 'dark' => '#f0f0f0' ],
			'innerHeadingTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '19', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, self::HEADING, 'color:#0a0a0a' );
		$this->assertCssHas( $css, self::HEADING, 'font-size:19px' );
		$this->assertCssHasInDark( $css, self::HEADING, 'color:#f0f0f0' );
	}

	public function test_link_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'linkColor' => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
		] );
		$this->assertCssHas( $css, self::LINK, 'color:#2563eb' );
		$this->assertCssHasInDark( $css, self::LINK, 'color:#93c5fd' );
	}

	public function test_link_hover_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'linkColorHover' => [ 'light' => '#1d4ed8', 'dark' => '#bfdbfe' ],
		] );
		$this->assertCssHas( $css, self::HOVER, 'color:#1d4ed8' );
		$this->assertCssHasInDark( $css, self::HOVER, 'color:#bfdbfe' );
	}

	public function test_list_style_type_on_lists(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'listStyle' => 'square' ] );
		$this->assertCssHas( $css, self::LIST, 'list-style-type:square' );
	}

	public function test_unknown_list_style_is_dropped(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'listStyle' => 'url(evil.png)' ] );
		$this->assertStringNotContainsString( 'list-style-type', $css );
	}

	public function test_list_indent_on_lists(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'listIndent' => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::LIST, 'padding-left:24px' );
	}

	public function test_list_indent_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'listIndent' => [ 'tablet' => [ 'value' => '18', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::LIST, 'padding-left:18px' );
	}

	public function test_table_cell_padding_on_cells(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'tableCellPadding' => [ 'desktop' => [ 'top' => '6', 'right' => '10', 'bottom' => '6', 'left' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::CELL, 'padding:6px 10px 6px 10px' );
	}

	public function test_table_cell_padding_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'tableCellPadding' => [ 'mobile' => [ 'top' => '4', 'right' => '6', 'bottom' => '4', 'left' => '6', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::CELL, 'padding:4px 6px 4px 6px' );
	}

	public function test_table_border_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'tableBorderColor' => [ 'light' => '#dddddd', 'dark' => '#3a3a3a' ],
		] );
		$this->assertCssHas( $css, self::CELL, 'border-color:#dddddd' );
		$this->assertCssHasInDark( $css, self::CELL, 'border-color:#3a3a3a' );
	}

	public function test_clamp_lines_when_enabled(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'enableClamp' => true, 'clampLines' => 3 ] );
		$this->assertCssHas( $css, self::CONTENT, '-webkit-line-clamp:3' );
	}

	public function test_clamp_lines_gated_off_when_disabled(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'enableClamp' => false, 'clampLines' => 3 ] );
		$this->assertStringNotContainsString( '-webkit-line-clamp', $css );
	}

	public function test_read_more_colour_when_clamped(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'enableClamp'   => true,
			'readMoreColor' => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
		] );
		$this->assertCssHas( $css, self::TOGGLE, 'color:#2563eb' );
		$this->assertCssHasInDark( $css, self::TOGGLE, 'color:#93c5fd' );
	}

	public function test_read_more_colour_gated_off_when_not_clamped(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'enableClamp'   => false,
			'readMoreColor' => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
		] );
		$this->assertStringNotContainsString( 'flexa-product-description__toggle', $css );
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

	public function test_background_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#f5f5f5', 'dark' => '#101010' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#f5f5f5' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#101010' );
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

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );
		$css = $this->gen( [
			'blockId'   => 'a',
			'textColor' => [ 'light' => '#333333', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::CONTENT, 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
