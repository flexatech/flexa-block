<?php
/**
 * Tests for the Pricing Table block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional bits (columns, gap, button, highlight, badge fallback) and
 * light/dark parity.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Pricing_Table_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Pricing_Table_CSS
 */
class PricingTableCssTest extends CssTestCase {

	private const WRAP      = '.flexa-pricing-table-a';
	private const GRID      = '.flexa-pricing-table-a .flexa-pricing-table__grid';
	private const PLAN      = '.flexa-pricing-table-a .flexa-pricing-table__plan';
	private const PLAN_HI   = '.flexa-pricing-table-a .flexa-pricing-table__plan.is-highlighted';
	private const BILLING   = '.flexa-pricing-table-a .flexa-pricing-table__billing';
	private const OPTION    = '.flexa-pricing-table-a .flexa-pricing-table__billing .flexa-pricing-table__billing-option';
	private const INDICATOR = '.flexa-pricing-table-a .flexa-pricing-table__billing .flexa-pricing-table__billing-indicator';
	private const BADGE   = '.flexa-pricing-table-a .flexa-pricing-table__badge';
	private const NAME    = '.flexa-pricing-table-a .flexa-pricing-table__name';
	private const AMOUNT  = '.flexa-pricing-table-a .flexa-pricing-table__amount';
	private const PERIOD  = '.flexa-pricing-table-a .flexa-pricing-table__period';
	private const FEATURE = '.flexa-pricing-table-a .flexa-pricing-table__feature';
	private const CTA     = '.flexa-pricing-table-a .flexa-pricing-table__cta';
	private const CTA_HV  = '.flexa-pricing-table-a .flexa-pricing-table__cta:hover';

	/**
	 * Convenience wrapper around the pricing-table generator.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Pricing_Table_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// A block with only its id supplied must emit no CSS: the card look comes
		// from style.scss; the generator only emits when the user picks a value.
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
			'widthBoxed'    => [ 'desktop' => [ 'value' => '1100', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $boxed, self::WRAP, 'max-width:1100px' );
	}

	public function test_columns_and_gap_on_grid(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'columns' => [ 'desktop' => [ 'value' => '3', 'unit' => '' ] ],
			'gap'     => [ 'desktop' => [ 'value' => '32', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::GRID, 'grid-template-columns:repeat(3, minmax(0, 1fr))' );
		$this->assertCssHas( $css, self::GRID, 'gap:32px' );
	}

	public function test_columns_gated_off_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'grid-template-columns', $css );
	}

	public function test_columns_and_gap_tablet_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'columns' => [ 'tablet' => [ 'value' => '2', 'unit' => '' ] ],
			'gap'     => [ 'tablet' => [ 'value' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::GRID, 'grid-template-columns:repeat(2, minmax(0, 1fr))' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::GRID, 'gap:16px' );
	}

	public function test_name_typography_and_colour(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'nameTypography' => [
				'desktop' => [
					'fontSize'      => [ 'value' => '20', 'unit' => 'px' ],
					'fontWeight'    => '700',
					'letterSpacing' => [ 'value' => '1', 'unit' => 'px' ],
					'textTransform' => 'uppercase',
					'lineHeight'    => '1.2',
				],
			],
			'nameColor'      => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, self::NAME, 'font-size:20px' );
		$this->assertCssHas( $css, self::NAME, 'font-weight:700' );
		$this->assertCssHas( $css, self::NAME, 'letter-spacing:1px' );
		$this->assertCssHas( $css, self::NAME, 'text-transform:uppercase' );
		$this->assertCssHas( $css, self::NAME, 'line-height:1.2' );
		$this->assertCssHas( $css, self::NAME, 'color:#111111' );
		$this->assertCssHasInDark( $css, self::NAME, 'color:#eeeeee' );
	}

	public function test_price_typography_and_colours(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'priceTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '40', 'unit' => 'px' ] ] ],
			'priceColor'      => [ 'light' => '#0f172a', 'dark' => '#f8fafc' ],
			'periodColor'     => [ 'light' => '#64748b', 'dark' => '#94a3b8' ],
		] );
		$this->assertCssHas( $css, self::AMOUNT, 'font-size:40px' );
		$this->assertCssHas( $css, self::AMOUNT, 'color:#0f172a' );
		$this->assertCssHasInDark( $css, self::AMOUNT, 'color:#f8fafc' );
		$this->assertCssHas( $css, self::PERIOD, 'color:#64748b' );
		$this->assertCssHasInDark( $css, self::PERIOD, 'color:#94a3b8' );
	}

	public function test_feature_typography_and_colour(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'featureTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ] ] ],
			'featureColor'      => [ 'light' => '#333333', 'dark' => '#cccccc' ],
		] );
		$this->assertCssHas( $css, self::FEATURE, 'font-size:15px' );
		$this->assertCssHas( $css, self::FEATURE, 'color:#333333' );
		$this->assertCssHasInDark( $css, self::FEATURE, 'color:#cccccc' );
	}

	public function test_highlight_accent_on_plan_and_badge(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'highlightColor' => [ 'light' => '#2563eb', 'dark' => '#1e40af' ],
		] );
		// The accent outlines the highlighted plan...
		$this->assertCssHas( $css, self::PLAN_HI, 'border-color:#2563eb' );
		$this->assertCssHasInDark( $css, self::PLAN_HI, 'border-color:#1e40af' );
		// ...and tints the badge when no badge background is set.
		$this->assertCssHas( $css, self::BADGE, 'background-color:#2563eb' );
		$this->assertCssHasInDark( $css, self::BADGE, 'background-color:#1e40af' );
	}

	public function test_badge_dedicated_styling_overrides_accent(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'highlightColor'  => [ 'light' => '#2563eb', 'dark' => '#1e40af' ],
			'badgeBackground' => [ 'light' => '#ec4899', 'dark' => '#be185d' ],
			'badgeColor'      => [ 'light' => '#ffffff', 'dark' => '#f1f5f9' ],
			'badgeTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '12', 'unit' => 'px' ] ] ],
		] );
		// The badge uses its own background, not the highlight accent...
		$this->assertCssHas( $css, self::BADGE, 'background-color:#ec4899' );
		$this->assertCssHasInDark( $css, self::BADGE, 'background-color:#be185d' );
		// ...while the highlighted plan keeps the accent border.
		$this->assertCssHas( $css, self::PLAN_HI, 'border-color:#2563eb' );
		// Badge text colour + typography.
		$this->assertCssHas( $css, self::BADGE, 'color:#ffffff' );
		$this->assertCssHasInDark( $css, self::BADGE, 'color:#f1f5f9' );
		$this->assertCssHas( $css, self::BADGE, 'font-size:12px' );
	}

	public function test_highlight_gated_off_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( 'is-highlighted', $css );
	}

	public function test_plan_card_border_and_shadow_cover_every_card(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'planBorder'    => [
				'style'  => 'solid',
				'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
				'color'  => [ 'light' => '#e5e7eb', 'dark' => '#374151' ],
				'radius' => [ 'topLeft' => '16', 'topRight' => '16', 'bottomRight' => '16', 'bottomLeft' => '16', 'unit' => 'px' ],
			],
			'planBoxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000', 'dark' => '#fff' ] ],
		] );
		$this->assertCssHas( $css, self::PLAN, 'border-style:solid' );
		$this->assertCssHas( $css, self::PLAN, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::PLAN, 'border-color:#e5e7eb' );
		$this->assertCssHas( $css, self::PLAN, 'border-radius:16px 16px 16px 16px' );
		$this->assertCssHasInDark( $css, self::PLAN, 'border-color:#374151' );
		$this->assertCssHas( $css, self::PLAN, 'box-shadow:0px 2px 8px 0px #000' );
		$this->assertCssHasInDark( $css, self::PLAN, 'box-shadow:0px 2px 8px 0px #fff' );

		// The highlighted card inherits the same chrome — nothing was overridden.
		$this->assertCssHas( $css, self::PLAN_HI, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::PLAN_HI, 'box-shadow:0px 2px 8px 0px #000' );
	}

	public function test_highlight_border_and_shadow_override_the_plan_card(): void {
		$css = $this->gen( [
			'blockId'            => 'a',
			'highlightColor'     => [ 'light' => '#2563eb' ],
			'planBorder'         => [
				'style'  => 'solid',
				'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
				'color'  => [ 'light' => '#e5e7eb' ],
				'radius' => [ 'topLeft' => '16', 'topRight' => '16', 'bottomRight' => '16', 'bottomLeft' => '16', 'unit' => 'px' ],
			],
			'highlightBorder'    => [
				'width' => [ 'top' => '3', 'right' => '3', 'bottom' => '3', 'left' => '3', 'unit' => 'px' ],
			],
			'planBoxShadow'      => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000' ] ],
			'highlightBoxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '10', 'blur' => '30', 'spread' => '0', 'color' => [ 'light' => '#111' ] ],
		] );
		// Overridden width + shadow on the highlighted card...
		$this->assertCssHas( $css, self::PLAN_HI, 'border-width:3px 3px 3px 3px' );
		$this->assertCssHas( $css, self::PLAN_HI, 'box-shadow:0px 10px 30px 0px #111' );
		// ...the untouched fields still come from the plan card...
		$this->assertCssHas( $css, self::PLAN_HI, 'border-style:solid' );
		$this->assertCssHas( $css, self::PLAN_HI, 'border-radius:16px 16px 16px 16px' );
		// ...and the accent wins over the inherited border colour.
		$this->assertCssHas( $css, self::PLAN_HI, 'border-color:#2563eb' );
		// The plain cards keep their own width + shadow.
		$this->assertCssHas( $css, self::PLAN, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::PLAN, 'box-shadow:0px 2px 8px 0px #000' );
	}

	public function test_billing_switch_styling_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'                 => 'a',
			'billingToggle'           => true,
			'billingAlign'            => 'flex-start',
			'billingTypography'       => [ 'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ] ] ],
			'billingColor'            => [ 'light' => '#6b7280', 'dark' => '#9ca3af' ],
			'billingActiveColor'      => [ 'light' => '#111827', 'dark' => '#f9fafb' ],
			'billingBackground'       => [ 'light' => '#f3f4f6', 'dark' => '#1f2937' ],
			'billingActiveBackground' => [ 'light' => '#ffffff', 'dark' => '#374151' ],
			'billingRadius'           => [ 'value' => '999', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::BILLING, 'align-self:flex-start' );
		$this->assertCssHas( $css, self::BILLING, 'background-color:#f3f4f6' );
		$this->assertCssHas( $css, self::BILLING, 'border-radius:999px' );
		$this->assertCssHasInDark( $css, self::BILLING, 'background-color:#1f2937' );
		$this->assertCssHas( $css, self::OPTION, 'font-size:14px' );
		$this->assertCssHas( $css, self::OPTION, 'color:#6b7280' );
		$this->assertCssHasInDark( $css, self::OPTION, 'color:#9ca3af' );
		$this->assertCssHas( $css, self::OPTION . '.is-active', 'color:#111827' );
		$this->assertCssHasInDark( $css, self::OPTION . '.is-active', 'color:#f9fafb' );
		// The pill carries the selected fill (the no-JS `.is-active` fallback too).
		$this->assertCssHas( $css, self::INDICATOR, 'background-color:#ffffff' );
		$this->assertCssHasInDark( $css, self::INDICATOR, 'background-color:#374151' );
		$this->assertStringContainsString( ':not(.is-indicator-ready)', $css );
	}

	public function test_billing_switch_gated_off_when_toggle_disabled(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'billingToggle'     => false,
			'billingBackground' => [ 'light' => '#f3f4f6' ],
			'billingRadius'     => [ 'value' => '999', 'unit' => 'px' ],
		] );
		$this->assertStringNotContainsString( '__billing', $css );
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
			'blockId'   => 'a',
			'nameColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, self::NAME, 'color:#eeeeee', true );
		$this->assertStringContainsString( '[data-theme="dark"] ' . self::NAME, $css );
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
		$this->assertCssHas( $css, self::CTA, 'background-image:linear-gradient(90deg,#f00,#00f)' );
		$this->assertCssHas( $css, self::CTA_HV, 'background-image:linear-gradient(90deg,#0f0,#00f)' );
		$this->assertCssHasInDark( $css, self::CTA, 'background-image:linear-gradient(90deg,#800,#008)' );
		$this->assertCssHasInDark( $css, self::CTA_HV, 'background-image:linear-gradient(90deg,#080,#008)' );
		// A solid `background:` must NOT be emitted when gradient is selected.
		$this->assertStringNotContainsString( 'background:#', $css );
	}

	public function test_button_radius_and_padding(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'buttonRadius'  => [ 'value' => '8', 'unit' => 'px' ],
			'buttonPadding' => [ 'top' => '10', 'right' => '20', 'bottom' => '10', 'left' => '20', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::CTA, 'border-radius:8px' );
		$this->assertCssHas( $css, self::CTA, 'padding:10px 20px 10px 20px' );
	}

	public function test_button_unset_emits_nothing(): void {
		// No button values → no CTA rule at all (keeps the theme's button style).
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( '.flexa-pricing-table__cta', $css );
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
		$this->assertStringNotContainsString( '.flexa-pricing-table__cta', $css );
	}
}
