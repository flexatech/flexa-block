<?php
/**
 * Tests for the Data Table block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional bits (striped rows, row hover, cell borders, first-column
 * highlight) and light/dark parity. Combined selector groups (e.g. "$th, $cell")
 * are asserted on the LAST selector in the group — the only one written directly
 * before the "{". Per-column alignment is emitted inline in render.php, not here.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Data_Table_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Data_Table_CSS
 */
class DataTableCssTest extends CssTestCase {

	private const WRAP    = '.flexa-data-table-a';
	private const TH       = '.flexa-data-table-a .flexa-data-table__th';
	private const TH_HOVER = '.flexa-data-table-a .flexa-data-table__row:hover .flexa-data-table__th';
	private const CELL    = '.flexa-data-table-a .flexa-data-table__cell';
	private const STRIPED = '.flexa-data-table-a .flexa-data-table__tbody .flexa-data-table__row:nth-child(even)';
	private const HOVER      = '.flexa-data-table-a .flexa-data-table__row:hover';
	private const HOVER_CELL = '.flexa-data-table-a .flexa-data-table__row:hover .flexa-data-table__cell';
	private const FIRST      = '.flexa-data-table-a .flexa-data-table__th:first-child';

	/**
	 * Convenience wrapper around the data-table generator.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Data_Table_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Defaults (header + striped + hover + borders ON) must still emit no CSS:
		// their appearance comes from style.scss/theme, and the generator only
		// emits when the user supplies a colour / width / padding.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_header_typography_on_th(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'headerTypography' => [
				'desktop' => [
					'fontSize'      => [ 'value' => '16', 'unit' => 'px' ],
					'fontWeight'    => '700',
					'letterSpacing' => [ 'value' => '1', 'unit' => 'px' ],
					'textTransform' => 'uppercase',
					'lineHeight'    => '1.3',
				],
			],
		] );
		$this->assertCssHas( $css, self::TH, 'font-size:16px' );
		$this->assertCssHas( $css, self::TH, 'font-weight:700' );
		$this->assertCssHas( $css, self::TH, 'letter-spacing:1px' );
		$this->assertCssHas( $css, self::TH, 'text-transform:uppercase' );
		$this->assertCssHas( $css, self::TH, 'line-height:1.3' );
	}

	public function test_header_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'headerTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::TH, 'font-size:14px' );
	}

	public function test_header_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'headerColor'      => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
			'headerBackground' => [ 'light' => '#f5f5f5', 'dark' => '#222222' ],
		] );
		$this->assertCssHas( $css, self::TH, 'color:#111111' );
		$this->assertCssHas( $css, self::TH, 'background-color:#f5f5f5' );
		$this->assertCssHasInDark( $css, self::TH, 'color:#eeeeee' );
		$this->assertCssHasInDark( $css, self::TH, 'background-color:#222222' );
	}

	public function test_header_hover_text_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'headerColorHover' => [ 'light' => '#7c3aed', 'dark' => '#a78bfa' ],
		] );
		$this->assertCssHas( $css, self::TH_HOVER, 'color:#7c3aed' );
		$this->assertCssHasInDark( $css, self::TH_HOVER, 'color:#a78bfa' );
	}

	public function test_header_hover_text_colour_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( ':hover .flexa-data-table__th', $css );
	}

	public function test_cell_typography_and_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'cellTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ] ] ],
			'cellColor'      => [ 'light' => '#333333', 'dark' => '#cccccc' ],
		] );
		$this->assertCssHas( $css, self::CELL, 'font-size:15px' );
		$this->assertCssHas( $css, self::CELL, 'color:#333333' );
		$this->assertCssHasInDark( $css, self::CELL, 'color:#cccccc' );
	}

	public function test_cell_padding_responsive(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'cellPadding' => [
				'desktop' => [ 'top' => '10', 'right' => '14', 'bottom' => '10', 'left' => '14', 'unit' => 'px' ],
				'tablet'  => [ 'top' => '6', 'right' => '8', 'bottom' => '6', 'left' => '8', 'unit' => 'px' ],
			],
		] );
		// Cell padding applies to header + body cells → combined group ends on the cell.
		$this->assertCssHas( $css, self::CELL, 'padding:10px 14px 10px 14px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::CELL, 'padding:6px 8px 6px 8px' );
	}

	public function test_cell_padding_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'padding:', $css );
	}

	public function test_striped_colour_gated_on(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'striped'      => true,
			'stripedColor' => [ 'light' => '#fafafa', 'dark' => '#181818' ],
		] );
		$this->assertCssHas( $css, self::STRIPED, 'background-color:#fafafa' );
		$this->assertCssHasInDark( $css, self::STRIPED, 'background-color:#181818' );
	}

	public function test_striped_colour_gated_off(): void {
		// Striping toggled off → no zebra rule even when a colour is set.
		$css = $this->gen( [
			'blockId'      => 'a',
			'striped'      => false,
			'stripedColor' => [ 'light' => '#fafafa', 'dark' => '#181818' ],
		] );
		$this->assertStringNotContainsString( ':nth-child(even)', $css );
	}

	public function test_hover_colour_gated_on(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'hoverHighlight' => true,
			'hoverColor'     => [ 'light' => '#eef2ff', 'dark' => '#1e293b' ],
		] );
		$this->assertCssHas( $css, self::HOVER, 'background-color:#eef2ff' );
		$this->assertCssHasInDark( $css, self::HOVER, 'background-color:#1e293b' );
	}

	public function test_hover_colour_gated_off(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'hoverHighlight' => false,
			'hoverColor'     => [ 'light' => '#eef2ff', 'dark' => '#1e293b' ],
		] );
		$this->assertStringNotContainsString( ':hover', $css );
	}

	public function test_cell_hover_text_colour_gated_on(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'hoverHighlight' => true,
			'cellColorHover' => [ 'light' => '#7c3aed', 'dark' => '#a78bfa' ],
		] );
		$this->assertCssHas( $css, self::HOVER_CELL, 'color:#7c3aed' );
		$this->assertCssHasInDark( $css, self::HOVER_CELL, 'color:#a78bfa' );
	}

	public function test_cell_hover_text_colour_gated_off(): void {
		// Row-hover toggled off → no cell-hover colour even when one is set.
		$css = $this->gen( [
			'blockId'        => 'a',
			'hoverHighlight' => false,
			'cellColorHover' => [ 'light' => '#7c3aed', 'dark' => '#a78bfa' ],
		] );
		$this->assertStringNotContainsString( ':hover', $css );
	}

	public function test_max_width_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'maxWidth' => [ 'value' => '720', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'max-width:720px' );
	}

	public function test_max_width_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'max-width', $css );
	}

	public function test_legacy_responsive_mode_attribute_is_ignored(): void {
		// `responsiveMode` was removed (Stack dropped; the table always scrolls).
		// Content saved with it must still generate nothing from that attribute.
		$css = $this->gen( [ 'blockId' => 'a', 'responsiveMode' => 'stack' ] );
		$this->assertSame( '', $css );
	}

	public function test_cell_borders_gated_on(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'showCellBorders' => true,
			'cellBorderColor' => [ 'light' => '#e5e7eb', 'dark' => '#374151' ],
			'cellBorderWidth' => [ 'value' => '2', 'unit' => 'px' ],
		] );
		// Borders apply to header + body cells → combined group ends on the cell.
		$this->assertCssHas( $css, self::CELL, 'border-style:solid' );
		$this->assertCssHas( $css, self::CELL, 'border-width:2px' );
		$this->assertCssHas( $css, self::CELL, 'border-color:#e5e7eb' );
		$this->assertCssHasInDark( $css, self::CELL, 'border-color:#374151' );
	}

	public function test_cell_borders_gated_off(): void {
		// Toggle off → no cell border even when a colour + width are set.
		$css = $this->gen( [
			'blockId'         => 'a',
			'showCellBorders' => false,
			'cellBorderColor' => [ 'light' => '#e5e7eb', 'dark' => '#374151' ],
			'cellBorderWidth' => [ 'value' => '2', 'unit' => 'px' ],
		] );
		$this->assertStringNotContainsString( 'border-style:solid', $css );
	}

	public function test_first_column_highlight_gated_on(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'firstColumnHighlight'  => true,
			'firstColumnBackground' => [ 'light' => '#f9fafb', 'dark' => '#111827' ],
			'firstColumnColor'      => [ 'light' => '#111827', 'dark' => '#f9fafb' ],
		] );
		// The first-column selector group ends on the th:first-child selector.
		$this->assertCssHas( $css, self::FIRST, 'background-color:#f9fafb' );
		$this->assertCssHas( $css, self::FIRST, 'color:#111827' );
		$this->assertCssHasInDark( $css, self::FIRST, 'background-color:#111827' );
		$this->assertCssHasInDark( $css, self::FIRST, 'color:#f9fafb' );
	}

	public function test_first_column_highlight_gated_off(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'firstColumnHighlight'  => false,
			'firstColumnBackground' => [ 'light' => '#f9fafb', 'dark' => '#111827' ],
		] );
		$this->assertStringNotContainsString( ':first-child', $css );
	}

	public function test_wrapper_spacing_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '24', 'right' => '16', 'bottom' => '24', 'left' => '16', 'unit' => 'px' ],
					'margin'  => [ 'top' => '20', 'right' => '0', 'bottom' => '20', 'left' => '0', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:24px 16px 24px 16px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:20px 0px 20px 0px' );
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

	public function test_wrapper_border_all_sub_properties(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
					'color'  => [ 'light' => '#e5e7eb', 'dark' => '#374151' ],
					'radius' => [ 'topLeft' => '10', 'topRight' => '10', 'bottomRight' => '10', 'bottomLeft' => '10', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'border-style:solid' );
		$this->assertCssHas( $css, self::WRAP, 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, self::WRAP, 'border-color:#e5e7eb' );
		$this->assertCssHas( $css, self::WRAP, 'border-radius:10px 10px 10px 10px' );
		$this->assertCssHasInDark( $css, self::WRAP, 'border-color:#374151' );
	}

	public function test_wrapper_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#ffffff' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#000000' );
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

	public function test_wrapper_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000', 'dark' => '#fff' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'box-shadow:0px 2px 8px 0px #000' );
		$this->assertStringContainsString( 'box-shadow:0px 2px 8px 0px #fff', $css );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'     => 'a',
			'headerColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::TH, 'color:#eeeeee', true );
		$this->assertStringContainsString( '[data-theme="dark"] ' . self::TH, $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
