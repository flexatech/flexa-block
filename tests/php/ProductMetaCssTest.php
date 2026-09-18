<?php
/**
 * Tests for the Product Meta block CSS generator.
 *
 * Product Meta is a container of flexa/product-field rows, so this file covers
 * two things: the list's own foundation, and the rules it emits AT the rows
 * through a descendant selector — the label column that lines them up, the gaps,
 * the divider and the shared label / value styling.
 *
 * Which of the two generators wins when both style a row is pinned down in
 * ProductFieldCssTest::test_a_field_rule_lands_after_its_parent_list_rule().
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Product_Meta_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Product_Meta_CSS
 */
class ProductMetaCssTest extends CssTestCase {

	private const WRAP  = '.flexa-product-meta-a';
	private const ROW   = '.flexa-product-meta-a .flexa-product-field';
	private const ROW2  = '.flexa-product-meta-a > .flexa-product-field + .flexa-product-field';
	private const LABEL = '.flexa-product-meta-a .flexa-product-field__label';
	private const VALUE = '.flexa-product-meta-a .flexa-product-field__value';
	private const TERM  = '.flexa-product-meta-a .flexa-product-field__term';

	/**
	 * Convenience wrapper around the Product Meta generator.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Product_Meta_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_list_emits_nothing(): void {
		// Only a blockId: the theme should style everything, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_alignment_on_wrapper(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
	}

	public function test_alignment_tablet_in_media_query(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'tablet' => 'right' ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'text-align:right' );
	}

	public function test_row_gap_on_wrapper(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'rowGap'  => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'row-gap:12px' );
	}

	public function test_row_gap_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'rowGap'  => [ 'mobile' => [ 'value' => '0.5', 'unit' => 'rem' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::WRAP, 'row-gap:0.5rem' );
	}

	public function test_label_gap_reaches_every_row(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'labelGap' => [ 'desktop' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::ROW, 'column-gap:8px' );
	}

	public function test_label_column_width_reaches_every_label(): void {
		// A fixed width on every row's label is what turns a stack of
		// independent rows into a table whose values line up.
		$css = $this->gen( [
			'blockId'    => 'a',
			'labelWidth' => [ 'desktop' => [ 'value' => '120', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::LABEL, 'flex:0 0 120px' );
	}

	public function test_label_column_width_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'labelWidth' => [ 'tablet' => [ 'value' => '40', 'unit' => '%' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::LABEL, 'flex:0 0 40%' );
	}

	public function test_shared_label_typography_and_colour(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'labelTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ], 'fontWeight' => '600' ] ],
			'labelColor'      => [ 'light' => '#555555', 'dark' => '#aaaaaa' ],
		] );
		$this->assertCssHas( $css, self::LABEL, 'font-size:14px' );
		$this->assertCssHas( $css, self::LABEL, 'font-weight:600' );
		$this->assertCssHas( $css, self::LABEL, 'color:#555555' );
		$this->assertCssHasInDark( $css, self::LABEL, 'color:#aaaaaa' );
	}

	public function test_shared_label_typography_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'labelTypography' => [ 'mobile' => [ 'fontSize' => [ 'value' => '12', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::LABEL, 'font-size:12px' );
	}

	public function test_shared_value_styling_reaches_values_and_terms(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'valueTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ] ] ],
			'valueColor'      => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, self::VALUE, 'font-size:15px' );
		$this->assertCssHas( $css, self::TERM, 'font-size:15px' );
		$this->assertCssHas( $css, self::VALUE, 'color:#111111' );
		$this->assertCssHas( $css, self::TERM, 'color:#111111' );
		$this->assertCssHasInDark( $css, self::VALUE, 'color:#eeeeee' );
		$this->assertCssHasInDark( $css, self::TERM, 'color:#eeeeee' );
	}

	public function test_shared_value_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'valueTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '13', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::VALUE, 'font-size:13px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::TERM, 'font-size:13px' );
	}

	public function test_divider_between_rows_when_enabled(): void {
		// Only BETWEEN rows: the selector must not paint a line above the first.
		$css = $this->gen( [
			'blockId'      => 'a',
			'showDivider'  => true,
			'dividerWidth' => [ 'desktop' => [ 'value' => '1', 'unit' => 'px' ] ],
			'dividerColor' => [ 'light' => '#e0e0e0', 'dark' => '#333333' ],
		] );
		$this->assertCssHas( $css, self::ROW2, 'border-top-width:1px' );
		$this->assertCssHas( $css, self::ROW2, 'border-top-color:#e0e0e0' );
		$this->assertCssHasInDark( $css, self::ROW2, 'border-top-color:#333333' );
	}

	public function test_divider_width_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'showDivider'  => true,
			'dividerWidth' => [ 'tablet' => [ 'value' => '2', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::ROW2, 'border-top-width:2px' );
	}

	public function test_divider_gated_off_when_disabled(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'showDivider'  => false,
			'dividerWidth' => [ 'desktop' => [ 'value' => '1', 'unit' => 'px' ] ],
			'dividerColor' => [ 'light' => '#e0e0e0', 'dark' => '#333333' ],
		] );
		$this->assertStringNotContainsString( 'border-top', $css );
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
			'blockId'    => 'a',
			'valueColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::VALUE, 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
