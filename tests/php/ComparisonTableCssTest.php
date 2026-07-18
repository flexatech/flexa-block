<?php
/**
 * Tests for the Comparison Table block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional bits (dividers, zebra, column divider) and light/dark parity.
 * Combined selector groups (e.g. "$cell, $label") are asserted on the LAST
 * selector in the group — the only one written directly before the "{".
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Comparison_Table_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Comparison_Table_CSS
 */
class ComparisonTableCssTest extends CssTestCase {

	private const WRAP   = '.flexa-comparison-table-a';
	private const COL    = '.flexa-comparison-table-a .flexa-comparison-table__col';
	private const CORNER = '.flexa-comparison-table-a .flexa-comparison-table__corner';
	private const CELL   = '.flexa-comparison-table-a .flexa-comparison-table__cell';
	private const LABEL  = '.flexa-comparison-table-a .flexa-comparison-table__label';
	private const COL_HI = '.flexa-comparison-table-a .flexa-comparison-table__col.is-highlighted';
	private const BADGE  = '.flexa-comparison-table-a .flexa-comparison-table__badge';
	private const CHECK  = '.flexa-comparison-table-a .flexa-comparison-table__mark--yes';
	private const CROSS  = '.flexa-comparison-table-a .flexa-comparison-table__mark--no';
	private const ZEBRA  = '.flexa-comparison-table-a .flexa-comparison-table__row:nth-child(even) .flexa-comparison-table__label';
	private const CTA    = '.flexa-comparison-table-a .flexa-comparison-table__cta';
	private const CTA_HV = '.flexa-comparison-table-a .flexa-comparison-table__cta:hover';

	/**
	 * Convenience wrapper around the comparison-table generator.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Comparison_Table_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Defaults (striped rows + row dividers ON) must still emit no CSS: their
		// appearance comes from style.scss, and the generator only emits when the
		// user supplies a colour / thickness.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_full_width_and_boxed_width(): void {
		$full = $this->gen( [
			'blockId'        => 'a',
			'containerType'  => 'full-width',
			'widthFullWidth' => [ 'desktop' => [ 'value' => '90', 'unit' => '%' ] ],
		] );
		$this->assertCssHas( $full, self::WRAP, 'width:90%' );

		$boxed = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'boxed',
			'widthBoxed'    => [ 'desktop' => [ 'value' => '1000', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $boxed, self::WRAP, 'max-width:1000px' );
	}

	public function test_header_typography_on_column(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'headerTypography' => [
				'desktop' => [
					'fontSize'      => [ 'value' => '18', 'unit' => 'px' ],
					'fontWeight'    => '700',
					'letterSpacing' => [ 'value' => '1', 'unit' => 'px' ],
					'textTransform' => 'uppercase',
					'lineHeight'    => '1.3',
				],
			],
		] );
		$this->assertCssHas( $css, self::COL, 'font-size:18px' );
		$this->assertCssHas( $css, self::COL, 'font-weight:700' );
		$this->assertCssHas( $css, self::COL, 'letter-spacing:1px' );
		$this->assertCssHas( $css, self::COL, 'text-transform:uppercase' );
		$this->assertCssHas( $css, self::COL, 'line-height:1.3' );
	}

	public function test_cell_typography_padding_and_alignment(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'cellTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ] ] ],
			'cellPadding'    => [ 'desktop' => [ 'top' => '10', 'right' => '14', 'bottom' => '10', 'left' => '14', 'unit' => 'px' ] ],
			'cellAlign'      => [ 'desktop' => 'center' ],
		] );
		// Body cells are the combined "$cell, $label" group → assert on the label.
		$this->assertCssHas( $css, self::LABEL, 'font-size:15px' );
		// Padding applies to header + body cells → still ends on the label selector.
		$this->assertCssHas( $css, self::LABEL, 'padding:10px 14px 10px 14px' );
		$this->assertCssHas( $css, self::LABEL, 'text-align:center' );
	}

	public function test_tablet_alignment_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'cellAlign' => [ 'tablet' => 'right' ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::LABEL, 'text-align:right' );
	}

	public function test_header_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'headerColor'      => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
			'headerBackground' => [ 'light' => '#f5f5f5', 'dark' => '#222222' ],
		] );
		$this->assertCssHas( $css, self::COL, 'color:#111111' );
		// Header background targets "$col, $corner" → assert on the corner.
		$this->assertCssHas( $css, self::CORNER, 'background-color:#f5f5f5' );
		// Dark: full property:value inside the prefers-color-scheme branch.
		$this->assertCssHasInDark( $css, self::COL, 'color:#eeeeee' );
		$this->assertCssHasInDark( $css, self::CORNER, 'background-color:#222222' );
	}

	public function test_cell_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'cellColor' => [ 'light' => '#333333', 'dark' => '#cccccc' ],
		] );
		$this->assertCssHas( $css, self::LABEL, 'color:#333333' );
		$this->assertCssHasInDark( $css, self::LABEL, 'color:#cccccc' );
	}

	public function test_highlight_accent_on_column_and_badge(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'highlightColor' => [ 'light' => '#2563eb', 'dark' => '#1e40af' ],
		] );
		$this->assertCssHas( $css, self::COL_HI, 'background-color:#2563eb' );
		$this->assertCssHas( $css, self::BADGE, 'background-color:#2563eb' );
		$this->assertCssHasInDark( $css, self::COL_HI, 'background-color:#1e40af' );
		$this->assertCssHasInDark( $css, self::BADGE, 'background-color:#1e40af' );
	}

	public function test_spotlight_bar_colour_and_thickness(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'spotlightBarColor' => [ 'light' => '#06b6d4', 'dark' => '#0891b2' ],
			'spotlightBarWidth' => [ 'value' => '5', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::COL_HI, 'box-shadow:inset 0 5px 0 0 #06b6d4' );
		$this->assertCssHasInDark( $css, self::COL_HI, 'box-shadow:inset 0 5px 0 0 #0891b2' );
	}

	public function test_spotlight_bar_falls_back_to_defaults_when_half_set(): void {
		// Only a colour set → thickness falls back to the 3px style.scss default.
		$css = $this->gen( [
			'blockId'           => 'a',
			'spotlightBarColor' => [ 'light' => '#ff0000', 'dark' => '' ],
		] );
		$this->assertCssHas( $css, self::COL_HI, 'box-shadow:inset 0 3px 0 0 #ff0000' );

		// Only a thickness set → colour falls back to the #06b6d4 default.
		$css = $this->gen( [
			'blockId'           => 'a',
			'spotlightBarWidth' => [ 'value' => '8', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::COL_HI, 'box-shadow:inset 0 8px 0 0 #06b6d4' );
	}

	public function test_badge_dedicated_styling_overrides_accent(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'highlightColor'  => [ 'light' => '#2563eb', 'dark' => '#1e40af' ],
			'badgeBackground' => [ 'light' => '#ec4899', 'dark' => '#be185d' ],
			'badgeColor'      => [ 'light' => '#ffffff', 'dark' => '#f1f5f9' ],
			'badgeRadius'     => [ 'value' => '4', 'unit' => 'px' ],
			'badgePadding'    => [ 'top' => '3', 'right' => '12', 'bottom' => '3', 'left' => '12', 'unit' => 'px' ],
		] );
		// The badge uses its own background, not the spotlight accent...
		$this->assertCssHas( $css, self::BADGE, 'background-color:#ec4899' );
		$this->assertCssHasInDark( $css, self::BADGE, 'background-color:#be185d' );
		// ...while the highlighted column keeps the accent.
		$this->assertCssHas( $css, self::COL_HI, 'background-color:#2563eb' );
		// Text colour, radius and padding are emitted on the badge.
		$this->assertCssHas( $css, self::BADGE, 'color:#ffffff' );
		$this->assertCssHasInDark( $css, self::BADGE, 'color:#f1f5f9' );
		$this->assertCssHas( $css, self::BADGE, 'border-radius:4px' );
		$this->assertCssHas( $css, self::BADGE, 'padding:3px 12px 3px 12px' );
	}

	public function test_check_and_cross_mark_colours(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'checkColor' => [ 'light' => '#16a34a', 'dark' => '#22c55e' ],
			'crossColor' => [ 'light' => '#dc2626', 'dark' => '#ef4444' ],
		] );
		$this->assertCssHas( $css, self::CHECK, 'color:#16a34a' );
		$this->assertCssHas( $css, self::CROSS, 'color:#dc2626' );
		$this->assertCssHasInDark( $css, self::CHECK, 'color:#22c55e' );
		$this->assertCssHasInDark( $css, self::CROSS, 'color:#ef4444' );
	}

	public function test_zebra_stripe_colour_gated_on(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'zebra'      => true,
			'zebraColor' => [ 'light' => '#fafafa', 'dark' => '#181818' ],
		] );
		$this->assertCssHas( $css, self::ZEBRA, 'background-color:#fafafa' );
		$this->assertCssHasInDark( $css, self::ZEBRA, 'background-color:#181818' );
	}

	public function test_zebra_stripe_colour_gated_off(): void {
		// Striping toggled off → no zebra rule even when a colour is set.
		$css = $this->gen( [
			'blockId'    => 'a',
			'zebra'      => false,
			'zebraColor' => [ 'light' => '#fafafa', 'dark' => '#181818' ],
		] );
		$this->assertStringNotContainsString( ':nth-child(even)', $css );
	}

	public function test_row_divider_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'dividerColor' => [ 'light' => '#dddddd', 'dark' => '#444444' ],
			'dividerWidth' => [ 'value' => '2', 'unit' => 'px' ],
		] );
		// Row divider draws a bottom border on every cell (combined group → label).
		$this->assertCssHas( $css, self::LABEL, 'border-bottom-style:solid' );
		$this->assertCssHas( $css, self::LABEL, 'border-bottom-width:2px' );
		$this->assertCssHas( $css, self::LABEL, 'border-bottom-color:#dddddd' );
		$this->assertCssHasInDark( $css, self::LABEL, 'border-bottom-color:#444444' );
		// Column divider is off by default → no right border.
		$this->assertStringNotContainsString( 'border-right-style', $css );
	}

	public function test_row_divider_gated_off(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'showRowDivider' => false,
			'dividerColor'   => [ 'light' => '#dddddd' ],
			'dividerWidth'   => [ 'value' => '2', 'unit' => 'px' ],
		] );
		$this->assertStringNotContainsString( 'border-bottom-style', $css );
	}

	public function test_column_divider_gated_on(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'showRowDivider'    => false,
			'showColumnDivider' => true,
			'dividerColor'      => [ 'light' => '#cccccc', 'dark' => '#333333' ],
			'dividerWidth'      => [ 'value' => '1', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::LABEL, 'border-right-style:solid' );
		$this->assertCssHas( $css, self::LABEL, 'border-right-width:1px' );
		$this->assertCssHas( $css, self::LABEL, 'border-right-color:#cccccc' );
		$this->assertCssHasInDark( $css, self::LABEL, 'border-right-color:#333333' );
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

	public function test_advanced_layout_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'overflow:hidden' );
		$this->assertCssHas( $css, self::WRAP, 'position:relative' );
		$this->assertCssHas( $css, self::WRAP, 'z-index:5' );
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

	public function test_wrapper_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000', 'dark' => '#fff' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'box-shadow:0px 2px 8px 0px #000' );
		$this->assertStringContainsString( 'box-shadow:0px 2px 8px 0px #fff', $css );
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
			'blockId'     => 'a',
			'headerColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::COL, 'color:#eeeeee', true );
		$this->assertStringContainsString( '[data-theme="dark"] ' . self::COL, $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}

	public function test_button_colours_base_hover_and_dark(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'buttonTextColor'       => [ 'light' => '#ffffff', 'dark' => '#f0f0f0' ],
			'buttonTextColorHover'  => [ 'light' => '#e5e7eb', 'dark' => '#d1d5db' ],
			'buttonBackground'      => [ 'light' => '#2563eb', 'dark' => '#1e40af' ],
			'buttonBackgroundHover' => [ 'light' => '#1d4ed8', 'dark' => '#1e3a8a' ],
		] );
		// Base — solid colour mode emits `background` (add_bg_fill), not an image.
		$this->assertCssHas( $css, self::CTA, 'color:#ffffff' );
		$this->assertCssHas( $css, self::CTA, 'background:#2563eb' );
		$this->assertStringNotContainsString( 'background-image', $css );
		// Hover.
		$this->assertCssHas( $css, self::CTA_HV, 'color:#e5e7eb' );
		$this->assertCssHas( $css, self::CTA_HV, 'background:#1d4ed8' );
		// Dark (full property:value in the dark branch).
		$this->assertCssHasInDark( $css, self::CTA, 'color:#f0f0f0' );
		$this->assertCssHasInDark( $css, self::CTA, 'background:#1e40af' );
		$this->assertCssHasInDark( $css, self::CTA_HV, 'color:#d1d5db' );
		$this->assertCssHasInDark( $css, self::CTA_HV, 'background:#1e3a8a' );
	}

	public function test_button_background_gradient_base_hover_and_dark(): void {
		$css = $this->gen( [
			'blockId'                       => 'a',
			'buttonBackgroundType'          => 'gradient',
			'buttonBackgroundGradient'      => [ 'light' => 'linear-gradient(90deg,#f00,#00f)', 'dark' => 'linear-gradient(90deg,#800,#008)' ],
			'buttonBackgroundHoverType'     => 'gradient',
			'buttonBackgroundHoverGradient' => [ 'light' => 'linear-gradient(90deg,#0f0,#00f)', 'dark' => 'linear-gradient(90deg,#080,#008)' ],
		] );
		// Gradient mode paints via background-image (base + hover).
		$this->assertCssHas( $css, self::CTA, 'background-image:linear-gradient(90deg,#f00,#00f)' );
		$this->assertCssHas( $css, self::CTA_HV, 'background-image:linear-gradient(90deg,#0f0,#00f)' );
		// Dark gradient.
		$this->assertCssHasInDark( $css, self::CTA, 'background-image:linear-gradient(90deg,#800,#008)' );
		$this->assertCssHasInDark( $css, self::CTA_HV, 'background-image:linear-gradient(90deg,#080,#008)' );
		// A solid `background:` must NOT be emitted when gradient is selected.
		$this->assertStringNotContainsString( 'background:#', $css );
	}

	public function test_button_radius_and_padding(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'buttonRadius'  => [ 'value' => '8', 'unit' => 'px' ],
			'buttonPadding' => [ 'top' => '8', 'right' => '20', 'bottom' => '8', 'left' => '20', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::CTA, 'border-radius:8px' );
		$this->assertCssHas( $css, self::CTA, 'padding:8px 20px 8px 20px' );
	}

	public function test_button_full_width_gated_on(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'buttonWidth' => 'full',
		] );
		$this->assertCssHas( $css, self::CTA, 'display:block' );
		$this->assertCssHas( $css, self::CTA, 'width:100%' );
		$this->assertCssHas( $css, self::CTA, 'text-align:center' );
	}

	public function test_button_auto_width_and_unset_emit_nothing(): void {
		// Default width mode + no button colours → no CTA rule at all.
		$css = $this->gen( [
			'blockId'     => 'a',
			'buttonWidth' => 'auto',
		] );
		$this->assertStringNotContainsString( '.flexa-comparison-table__cta', $css );
	}
}
