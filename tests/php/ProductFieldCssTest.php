<?php
/**
 * Tests for the Product Field block CSS generator.
 *
 * Product Field is one block with three inserter variations (SKU / Categories /
 * Tags), so this file covers the whole surface: the wrapper foundation, the
 * label and value styling, the term chips a taxonomy field draws, and the
 * gating that keeps term rules out of a SKU field.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Product_Field_CSS;
use Flexa\Block\CSS_Generators\Product_Meta_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Product_Field_CSS
 */
class ProductFieldCssTest extends CssTestCase {

	private const WRAP  = '.flexa-product-field-a';
	private const LABEL = '.flexa-product-field-a .flexa-product-field__label';
	private const VALUE = '.flexa-product-field-a .flexa-product-field__value';
	private const TERMS = '.flexa-product-field-a .flexa-product-field__terms';
	private const TERM  = '.flexa-product-field-a .flexa-product-field__term';
	private const HOVER = '.flexa-product-field-a .flexa-product-field__term:hover';

	/**
	 * Convenience wrapper around the Product Field generator.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Product_Field_CSS::class, 'generate' ], $attrs );
	}

	/**
	 * A taxonomy field — every term rule is gated behind this.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	private function genTerms( array $attrs ): string {
		return $this->gen( array_merge( [ 'field' => 'categories' ], $attrs ) );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_field_emits_nothing(): void {
		// Only a blockId: the theme should style everything, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'field' => 'sku' ] ) );
	}

	public function test_alignment_on_wrapper(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
	}

	public function test_label_gap_on_wrapper_and_value(): void {
		// One control spaces the label from the value AND the value from the
		// copy button, so both selectors carry it.
		$css = $this->gen( [
			'blockId'  => 'a',
			'labelGap' => [ 'desktop' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'column-gap:8px' );
		$this->assertCssHas( $css, self::VALUE, 'gap:8px' );
	}

	public function test_label_gap_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'labelGap' => [ 'mobile' => [ 'value' => '4', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::WRAP, 'column-gap:4px' );
	}

	public function test_label_typography_and_colour(): void {
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

	public function test_label_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'labelTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '12', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::LABEL, 'font-size:12px' );
	}

	public function test_value_typography_and_colour_on_sku_field(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'field'           => 'sku',
			'valueTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ] ] ],
			'valueColor'      => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, self::VALUE, 'font-size:15px' );
		$this->assertCssHas( $css, self::VALUE, 'color:#111111' );
		$this->assertCssHasInDark( $css, self::VALUE, 'color:#eeeeee' );
	}

	public function test_value_styling_reaches_terms_on_a_taxonomy_field(): void {
		$css = $this->genTerms( [
			'blockId'         => 'a',
			'valueTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ] ] ],
			'valueColor'      => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, self::TERM, 'font-size:15px' );
		$this->assertCssHas( $css, self::TERM, 'color:#111111' );
		$this->assertCssHasInDark( $css, self::TERM, 'color:#eeeeee' );
	}

	public function test_term_rules_gated_off_on_a_sku_field(): void {
		// A SKU field renders no terms, so term rules would be dead weight.
		$css = $this->gen( [
			'blockId'    => 'a',
			'field'      => 'sku',
			'itemColor'  => [ 'light' => '#2563eb', 'dark' => '' ],
			'itemRadius' => [ 'desktop' => [ 'value' => '8', 'unit' => 'px' ] ],
			'termGap'    => [ 'desktop' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertStringNotContainsString( '__term', $css );
	}

	public function test_term_gap_on_terms_row(): void {
		$css = $this->genTerms( [
			'blockId' => 'a',
			'termGap' => [ 'desktop' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::TERMS, 'gap:10px' );
	}

	public function test_term_gap_tablet_in_media_query(): void {
		$css = $this->genTerms( [
			'blockId' => 'a',
			'termGap' => [ 'tablet' => [ 'value' => '6', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::TERMS, 'gap:6px' );
	}

	public function test_term_box(): void {
		$css = $this->genTerms( [
			'blockId'         => 'a',
			'itemPadding'     => [ 'desktop' => [ 'top' => '4', 'right' => '10', 'bottom' => '4', 'left' => '10', 'unit' => 'px' ] ],
			'itemRadius'      => [ 'desktop' => [ 'value' => '999', 'unit' => 'px' ] ],
			'itemBorderWidth' => [ 'desktop' => [ 'value' => '1', 'unit' => 'px' ] ],
			'itemBorderColor' => [ 'light' => '#dddddd', 'dark' => '#444444' ],
		] );
		$this->assertCssHas( $css, self::TERM, 'padding:4px 10px 4px 10px' );
		$this->assertCssHas( $css, self::TERM, 'border-radius:999px' );
		// A single width drives a solid outline.
		$this->assertCssHas( $css, self::TERM, 'border-style:solid' );
		$this->assertCssHas( $css, self::TERM, 'border-width:1px' );
		$this->assertCssHas( $css, self::TERM, 'border-color:#dddddd' );
		$this->assertCssHasInDark( $css, self::TERM, 'border-color:#444444' );
	}

	public function test_term_box_mobile_in_media_query(): void {
		$css = $this->genTerms( [
			'blockId'    => 'a',
			'itemRadius' => [ 'mobile' => [ 'value' => '4', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::TERM, 'border-radius:4px' );
	}

	public function test_term_colours_normal_and_hover(): void {
		$css = $this->genTerms( [
			'blockId'             => 'a',
			'itemColor'           => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
			'itemBackground'      => [ 'light' => '#eff6ff', 'dark' => '#1e3a8a' ],
			'itemColorHover'      => [ 'light' => '#1d4ed8', 'dark' => '#bfdbfe' ],
			'itemBackgroundHover' => [ 'light' => '#dbeafe', 'dark' => '#1e40af' ],
		] );
		$this->assertCssHas( $css, self::TERM, 'color:#2563eb' );
		$this->assertCssHas( $css, self::TERM, 'background:#eff6ff' );
		$this->assertCssHas( $css, self::HOVER, 'color:#1d4ed8' );
		$this->assertCssHas( $css, self::HOVER, 'background:#dbeafe' );
		$this->assertCssHasInDark( $css, self::TERM, 'color:#93c5fd' );
		$this->assertCssHasInDark( $css, self::HOVER, 'background:#1e40af' );
	}

	public function test_term_colour_overrides_the_value_colour(): void {
		// Terms inherit the value colour so a linked category matches a plain
		// SKU beside it — unless the term box says otherwise, which wins.
		$css = $this->genTerms( [
			'blockId'    => 'a',
			'valueColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
			'itemColor'  => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
		] );
		$this->assertCssHas( $css, self::VALUE, 'color:#111111' );
		$this->assertCssHas( $css, self::TERM, 'color:#2563eb' );
		$this->assertCssHasInDark( $css, self::TERM, 'color:#93c5fd' );
	}

	public function test_foundation_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'spacing'        => [ 'desktop' => [ 'padding' => [ 'top' => '10', 'right' => '20', 'bottom' => '10', 'left' => '20', 'unit' => 'px' ] ] ],
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
			'background'     => [ 'type' => 'classic', 'color' => [ 'light' => '#f5f5f5', 'dark' => '#101010' ] ],
			'boxShadow'      => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '4', 'blur' => '12', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:10px 20px 10px 20px' );
		$this->assertCssHas( $css, self::WRAP, 'overflow:hidden' );
		$this->assertCssHas( $css, self::WRAP, 'z-index:5' );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#f5f5f5' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#101010' );
		$this->assertCssHas( $css, self::WRAP, 'box-shadow:0px 4px 12px 0px #000000' );
	}

	public function test_a_field_rule_lands_after_its_parent_list_rule(): void {
		// PRECEDENCE. Both generators emit the same weight (0,2,0) for a label
		// colour, so whichever is written last wins. The generator service walks
		// a block before recursing into its inner blocks, which puts the field
		// after the list — the field must be the one that shows.
		$css = $this->genCss(
			static function ( $attrs, $builder ): void {
				Product_Meta_CSS::generate( [ 'blockId' => 'p', 'labelColor' => [ 'light' => '#111111', 'dark' => '' ] ], $builder );
				Product_Field_CSS::generate( [ 'blockId' => 'a', 'labelColor' => [ 'light' => '#2563eb', 'dark' => '' ] ], $builder );
			},
			[]
		);

		$parent = strpos( $css, '.flexa-product-meta-p .flexa-product-field__label' );
		$child  = strpos( $css, '.flexa-product-field-a .flexa-product-field__label' );
		$this->assertNotFalse( $parent );
		$this->assertNotFalse( $child );
		$this->assertGreaterThan( $parent, $child, 'The field rule must be written after the list rule so it wins.' );
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
