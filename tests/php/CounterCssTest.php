<?php
/**
 * Tests for the Counter block CSS generator.
 *
 * One assertion per property the generator emits (cross-checked against the
 * inspector controls), plus gating (nothing when the user hasn't chosen),
 * responsive placement and dark-mode branches.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Counter_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Counter_CSS
 */
class CounterCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Counter generator.
	 *
	 * @param array $attrs Counter attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Counter_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_no_css(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_columns_grid_on_list(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'columns' => [ 'desktop' => [ 'value' => '4', 'unit' => '' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__list', 'grid-template-columns:repeat(4, minmax(0, 1fr))' );
	}

	public function test_gap_between_stats_on_list(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'gap'     => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__list', 'gap:24px' );
	}

	public function test_alignment_sets_align_items_and_text_align(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'left' ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__item', 'align-items:flex-start' );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__item', 'text-align:left' );
	}

	public function test_content_gap_on_item(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'contentGap' => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__item', 'gap:12px' );
	}

	public function test_icon_size(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'iconSize' => [ 'desktop' => [ 'value' => '48', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__icon', 'width:48px' );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__icon', 'height:48px' );
	}

	public function test_number_typography(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'numberTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '48', 'unit' => 'px' ], 'fontWeight' => '700' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__number', 'font-size:48px' );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__number', 'font-weight:700' );
	}

	public function test_label_typography(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'labelTypography' => [ 'desktop' => [ 'textTransform' => 'uppercase' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__label', 'text-transform:uppercase' );
	}

	public function test_item_padding_and_radius(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'itemPadding'      => [ 'desktop' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ] ],
			'itemBorderRadius' => [ 'desktop' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__item', 'padding:10px 10px 10px 10px' );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__item', 'border-radius:8px' );
	}

	public function test_spacing_padding_and_margin_on_wrapper(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '5', 'right' => '5', 'bottom' => '5', 'left' => '5', 'unit' => 'px' ],
					'margin'  => [ 'top' => '15', 'right' => '15', 'bottom' => '15', 'left' => '15', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a', 'padding:5px 5px 5px 5px' );
		$this->assertCssHas( $css, '.flexa-counter-a', 'margin:15px 15px 15px 15px' );
	}

	public function test_border_light_at_base_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
					'color'  => [ 'light' => '#cccccc', 'dark' => '#333333' ],
					'radius' => [ 'topLeft' => '6', 'topRight' => '6', 'bottomRight' => '6', 'bottomLeft' => '6', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-counter-a', 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, '.flexa-counter-a', 'border-color:#cccccc' );
		$this->assertCssHas( $css, '.flexa-counter-a', 'border-radius:6px 6px 6px 6px' );
		$this->assertCssHasInDark( $css, '.flexa-counter-a', 'border-color:#333333' );
	}

	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '3' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-counter-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-counter-a', 'z-index:3' );
	}

	public function test_number_colour_light_at_base_and_dark(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'numberColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__number', 'color:#111111' );
		$this->assertCssHasInDark( $css, '.flexa-counter-a .flexa-counter__number', 'color:#eeeeee' );
	}

	public function test_label_and_icon_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'labelColor' => [ 'light' => '#222222', 'dark' => '#dddddd' ],
			'iconColor'  => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__label', 'color:#222222' );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__icon', 'color:#2563eb' );
		$this->assertCssHasInDark( $css, '.flexa-counter-a .flexa-counter__label', 'color:#dddddd' );
		$this->assertCssHasInDark( $css, '.flexa-counter-a .flexa-counter__icon', 'color:#93c5fd' );
	}

	public function test_item_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'itemBackground' => [ 'light' => '#f0f0f0', 'dark' => '#202020' ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__item', 'background:#f0f0f0' );
		$this->assertCssHasInDark( $css, '.flexa-counter-a .flexa-counter__item', 'background:#202020' );
	}

	public function test_separator_off_emits_nothing_for_divider(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'showSeparator'  => false,
			'separatorWidth' => [ 'desktop' => [ 'value' => '40', 'unit' => 'px' ] ],
			'separatorColor' => [ 'light' => '#999999', 'dark' => '' ],
		] );
		$this->assertStringNotContainsString( 'border-top-style', $css );
		$this->assertStringNotContainsString( 'flexa-counter__separator', $css );
	}

	public function test_separator_on_emits_style_width_weight_and_colour(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'showSeparator'   => true,
			'separatorStyle'  => 'dashed',
			'separatorWidth'  => [ 'desktop' => [ 'value' => '40', 'unit' => 'px' ] ],
			'separatorWeight' => [ 'desktop' => [ 'value' => '3', 'unit' => 'px' ] ],
			'separatorColor'  => [ 'light' => '#999999', 'dark' => '#555555' ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__separator', 'border-top-style:dashed' );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__separator', 'width:40px' );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__separator', 'border-top-width:3px' );
		$this->assertCssHas( $css, '.flexa-counter-a .flexa-counter__separator', 'border-top-color:#999999' );
		$this->assertCssHasInDark( $css, '.flexa-counter-a .flexa-counter__separator', 'border-top-color:#555555' );
	}

	public function test_wrapper_background_and_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
			'boxShadow'  => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-counter-a', 'background-color:#ffffff' );
		$this->assertStringContainsString( 'box-shadow:0px 2px 8px 0px #000000', $css );
		$this->assertCssHasInDark( $css, '.flexa-counter-a', 'background-color:#000000' );
		$this->assertCssHasInDark( $css, '.flexa-counter-a', 'box-shadow:0px 2px 8px 0px #ffffff' );
	}

	public function test_tablet_values_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'gap'     => [ 'tablet' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-counter-a .flexa-counter__list', 'gap:10px' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'     => 'a',
			'numberColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );

		$this->assertCssHasInDark( $css, '.flexa-counter-a .flexa-counter__number', 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
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
		$this->assertCssHas( $css, '.flexa-counter-a', 'background-size:cover' );
		$this->assertCssHas( $css, '.flexa-counter-a.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}
}
