<?php
/**
 * Tests for the Add to Cart block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Product_Add_To_Cart_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Product_Add_To_Cart_CSS
 */
class ProductAddToCartCssTest extends CssTestCase {

	private const WRAP        = '.flexa-product-add-to-cart-a';
	private const FORM        = '.flexa-product-add-to-cart-a form.cart';
	private const QTY         = '.flexa-product-add-to-cart-a .quantity';
	private const QTY_GROUP = '.flexa-product-add-to-cart-a .quantity.flexa-qty';
	private const QTY_INPUT   = '.flexa-product-add-to-cart-a .quantity input.qty';
	private const BUTTON      = '.flexa-product-add-to-cart-a button.single_add_to_cart_button';
	private const BUTTON_HOV  = '.flexa-product-add-to-cart-a button.single_add_to_cart_button:hover';
	private const ICON_BEFORE = '.flexa-product-add-to-cart-a button.single_add_to_cart_button::before';
	private const ICON_AFTER  = '.flexa-product-add-to-cart-a button.single_add_to_cart_button::after';
	private const VAR_LABEL   = '.flexa-product-add-to-cart-a .variations label';
	private const VAR_SELECT  = '.flexa-product-add-to-cart-a .variations select';

	/**
	 * Convenience wrapper around the Add to Cart generator.
	 *
	 * @param array $attrs Add-to-cart attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Product_Add_To_Cart_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Only a blockId: the theme + WooCommerce style everything, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'cartLayout' => 'inline', 'showQuantity' => true ] ) );
	}

	public function test_alignment_on_wrapper(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
	}

	public function test_alignment_tablet_in_media_query(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'tablet' => 'right' ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'text-align:right' );
	}

	public function test_stacked_layout_emits_no_direction(): void {
		// The column direction is style.scss's job, keyed on the wrapper's
		// `--stacked` modifier. It used to be generated here, inside the desktop
		// media query, which left the form reading as a row below 1025px.
		$css = $this->gen( [ 'blockId' => 'a', 'cartLayout' => 'stacked' ] );
		$this->assertStringNotContainsString( 'flex-direction', $css );
	}

	public function test_alignment_drives_justify_content_on_the_form(): void {
		// The form is a flex row, so `text-align` on the wrapper moves nothing.
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, self::FORM, 'justify-content:center' );
	}

	public function test_alignment_also_drives_align_items_when_stacked(): void {
		// Down the page, the same intent has to travel on the cross axis.
		$css = $this->gen( [ 'blockId' => 'a', 'cartLayout' => 'stacked', 'alignment' => [ 'desktop' => 'right' ] ] );
		$this->assertCssHas( $css, self::FORM, 'justify-content:flex-end' );
		$this->assertCssHas( $css, self::FORM, 'align-items:flex-end' );
	}

	public function test_alignment_leaves_align_items_alone_when_inline(): void {
		// style.scss centres the row vertically; overriding that would drop the
		// button and the field out of line with each other.
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'left' ] ] );
		$this->assertStringNotContainsString( 'align-items', $css );
	}

	public function test_inline_layout_emits_no_direction(): void {
		// style.scss already lays the form out as a centred row.
		$css = $this->gen( [ 'blockId' => 'a', 'cartLayout' => 'inline' ] );
		$this->assertStringNotContainsString( 'flex-direction', $css );
		$this->assertStringNotContainsString( 'align-items', $css );
	}

	public function test_gap_on_form(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'gap'     => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::FORM, 'gap:12px' );
	}

	public function test_gap_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'gap'     => [ 'mobile' => [ 'value' => '6', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::FORM, 'gap:6px' );
	}

	public function test_button_typography(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'buttonTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '16', 'unit' => 'px' ], 'fontWeight' => '700' ] ],
		] );
		$this->assertCssHas( $css, self::BUTTON, 'font-size:16px' );
		$this->assertCssHas( $css, self::BUTTON, 'font-weight:700' );
	}

	public function test_button_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'buttonTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::BUTTON, 'font-size:14px' );
	}

	public function test_button_colours_base_and_hover_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'buttonTextColor'       => [ 'light' => '#ffffff', 'dark' => '#f0f0f0' ],
			'buttonTextColorHover'  => [ 'light' => '#eeeeee', 'dark' => '#dddddd' ],
			'buttonBackground'      => [ 'light' => '#1a7f37', 'dark' => '#0d4e21' ],
			'buttonBackgroundHover' => [ 'light' => '#155f29', 'dark' => '#093a18' ],
		] );
		$this->assertCssHas( $css, self::BUTTON, 'color:#ffffff' );
		$this->assertCssHas( $css, self::BUTTON, 'background:#1a7f37' );
		$this->assertCssHas( $css, self::BUTTON_HOV, 'color:#eeeeee' );
		$this->assertCssHas( $css, self::BUTTON_HOV, 'background:#155f29' );
		$this->assertCssHasInDark( $css, self::BUTTON, 'color:#f0f0f0' );
		$this->assertCssHasInDark( $css, self::BUTTON, 'background:#0d4e21' );
		$this->assertCssHasInDark( $css, self::BUTTON_HOV, 'color:#dddddd' );
		$this->assertCssHasInDark( $css, self::BUTTON_HOV, 'background:#093a18' );
	}

	public function test_button_radius_padding_align_and_width(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'buttonRadius'  => [ 'value' => '8', 'unit' => 'px' ],
			'buttonPadding' => [ 'top' => '12', 'right' => '24', 'bottom' => '12', 'left' => '24', 'unit' => 'px' ],
			'buttonAlign'   => 'center',
			'buttonWidth'   => 'full',
		] );
		$this->assertCssHas( $css, self::BUTTON, 'border-radius:8px' );
		$this->assertCssHas( $css, self::BUTTON, 'padding:12px 24px 12px 24px' );
		$this->assertCssHas( $css, self::BUTTON, 'align-self:center' );
		$this->assertCssHas( $css, self::BUTTON, 'display:flex' );
		$this->assertCssHas( $css, self::BUTTON, 'width:100%' );
		$this->assertCssHas( $css, self::BUTTON, 'justify-content:center' );
	}

	public function test_quantity_sizing_border_and_radius(): void {
		$css = $this->gen( [
			'blockId'             => 'a',
			'showQuantity'        => true,
			'quantityWidth'       => [ 'desktop' => [ 'value' => '80', 'unit' => 'px' ] ],
			'quantityHeight'      => [ 'desktop' => [ 'value' => '48', 'unit' => 'px' ] ],
			'quantityBorderWidth' => [ 'desktop' => [ 'value' => '2', 'unit' => 'px' ] ],
			'quantityRadius'      => [ 'desktop' => [ 'value' => '6', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::QTY, 'width:80px' );
		$this->assertCssHas( $css, self::QTY_INPUT, 'width:80px' );
		$this->assertCssHas( $css, self::QTY_INPUT, 'height:48px' );
		// Without the stepper the input keeps its own border, so a page whose
		// view script never ran still shows a bordered field.
		$this->assertCssHas( $css, self::QTY_INPUT, 'border-style:solid' );
		$this->assertCssHas( $css, self::QTY_INPUT, 'border-width:2px' );
		$this->assertCssHas( $css, self::QTY_INPUT, 'border-radius:6px' );
	}

	public function test_quantity_box_moves_to_the_stepper_group(): void {
		// view.js wraps the field with minus / plus, and the box the author
		// styled becomes that group — the input inside it drops its own border.
		$css = $this->gen( [
			'blockId'             => 'a',
			'showQuantity'        => true,
			'quantityBorderWidth' => [ 'desktop' => [ 'value' => '2', 'unit' => 'px' ] ],
			'quantityRadius'      => [ 'desktop' => [ 'value' => '6', 'unit' => 'px' ] ],
			'quantityBorderColor' => [ 'light' => '#cccccc', 'dark' => '#444444' ],
		] );
		$this->assertCssHas( $css, self::QTY_GROUP, 'border-style:solid' );
		$this->assertCssHas( $css, self::QTY_GROUP, 'border-width:2px' );
		$this->assertCssHas( $css, self::QTY_GROUP, 'border-radius:6px' );
		$this->assertCssHas( $css, self::QTY_GROUP, 'border-color:#cccccc' );
		$this->assertCssHasInDark( $css, self::QTY_GROUP, 'border-color:#444444' );
		$this->assertCssHas( $css, self::QTY_GROUP . ' input.qty', 'border-width:0' );
	}

	public function test_quantity_height_stays_off_the_group(): void {
		// The group stretches to the input; setting both would count the border
		// twice and overflow the box the author asked for.
		$css = $this->gen( [
			'blockId'        => 'a',
			'showQuantity'   => true,
			'quantityHeight' => [ 'desktop' => [ 'value' => '48', 'unit' => 'px' ] ],
		] );
		$this->assertStringNotContainsString( '.flexa-qty{height', $css );
	}

	public function test_stepper_buttons_take_the_quantity_colors(): void {
		$css = $this->gen( [
			'blockId'            => 'a',
			'showQuantity'       => true,
			'quantityColor'      => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
			'quantityBackground' => [ 'light' => '#f5f5f5', 'dark' => '#222222' ],
		] );
		$this->assertCssHas( $css, '.flexa-product-add-to-cart-a .flexa-qty__btn', 'color:#111111' );
		$this->assertCssHasInDark( $css, '.flexa-product-add-to-cart-a .flexa-qty__btn', 'color:#eeeeee' );
		$this->assertCssHas( $css, '.flexa-product-add-to-cart-a .flexa-qty__btn', 'background-color:#f5f5f5' );
	}

	public function test_quantity_sizing_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'showQuantity'   => true,
			'quantityWidth'  => [ 'mobile' => [ 'value' => '60', 'unit' => 'px' ] ],
			'quantityHeight' => [ 'mobile' => [ 'value' => '40', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::QTY, 'width:60px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::QTY_INPUT, 'width:60px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::QTY_INPUT, 'height:40px' );
	}

	public function test_quantity_typography(): void {
		$css = $this->gen( [
			'blockId'            => 'a',
			'showQuantity'       => true,
			'quantityTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ], 'fontWeight' => '500' ] ],
		] );
		$this->assertCssHas( $css, self::QTY_INPUT, 'font-size:15px' );
		$this->assertCssHas( $css, self::QTY_INPUT, 'font-weight:500' );
	}

	public function test_quantity_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'            => 'a',
			'showQuantity'       => true,
			'quantityTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '13', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::QTY_INPUT, 'font-size:13px' );
	}

	public function test_quantity_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'             => 'a',
			'showQuantity'        => true,
			'quantityColor'       => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
			'quantityBackground'  => [ 'light' => '#fafafa', 'dark' => '#151515' ],
			'quantityBorderColor' => [ 'light' => '#cccccc', 'dark' => '#444444' ],
		] );
		$this->assertCssHas( $css, self::QTY_INPUT, 'color:#111111' );
		$this->assertCssHas( $css, self::QTY_INPUT, 'background-color:#fafafa' );
		$this->assertCssHas( $css, self::QTY_INPUT, 'border-color:#cccccc' );
		$this->assertCssHasInDark( $css, self::QTY_INPUT, 'color:#eeeeee' );
		$this->assertCssHasInDark( $css, self::QTY_INPUT, 'background-color:#151515' );
		$this->assertCssHasInDark( $css, self::QTY_INPUT, 'border-color:#444444' );
	}

	public function test_quantity_hidden_by_button_only_layout(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'cartLayout'    => 'button-only',
			'showQuantity'  => true,
			'quantityWidth' => [ 'desktop' => [ 'value' => '80', 'unit' => 'px' ] ],
			'quantityColor' => [ 'light' => '#111111' ],
		] );
		$this->assertCssHas( $css, self::QTY, 'display:none' );
		$this->assertStringNotContainsString( 'width:80px', $css );
		$this->assertStringNotContainsString( 'color:#111111', $css );
	}

	public function test_quantity_hidden_by_toggle(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'cartLayout'    => 'inline',
			'showQuantity'  => false,
			'quantityWidth' => [ 'desktop' => [ 'value' => '80', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::QTY, 'display:none' );
		$this->assertStringNotContainsString( 'width:80px', $css );
	}

	public function test_quantity_shown_emits_no_display_none(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'cartLayout'    => 'inline',
			'showQuantity'  => true,
			'quantityWidth' => [ 'desktop' => [ 'value' => '80', 'unit' => 'px' ] ],
		] );
		$this->assertStringNotContainsString( 'display:none', $css );
	}

	public function test_icon_size_and_gap_before(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'showIcon'     => true,
			'iconPosition' => 'before',
			'iconSize'     => [ 'desktop' => [ 'value' => '20', 'unit' => 'px' ] ],
			'iconGap'      => [ 'desktop' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::ICON_BEFORE, 'width:20px' );
		$this->assertCssHas( $css, self::ICON_BEFORE, 'height:20px' );
		$this->assertCssHas( $css, self::ICON_BEFORE, 'margin-right:10px' );
	}

	public function test_icon_size_and_gap_after(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'showIcon'     => true,
			'iconPosition' => 'after',
			'iconSize'     => [ 'desktop' => [ 'value' => '18', 'unit' => 'px' ] ],
			'iconGap'      => [ 'desktop' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::ICON_AFTER, 'width:18px' );
		$this->assertCssHas( $css, self::ICON_AFTER, 'height:18px' );
		$this->assertCssHas( $css, self::ICON_AFTER, 'margin-left:8px' );
	}

	public function test_icon_size_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'showIcon' => true,
			'iconSize' => [ 'mobile' => [ 'value' => '14', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::ICON_BEFORE, 'width:14px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::ICON_BEFORE, 'height:14px' );
	}

	public function test_icon_gated_off_when_hidden(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'showIcon' => false,
			'iconSize' => [ 'desktop' => [ 'value' => '20', 'unit' => 'px' ] ],
			'iconGap'  => [ 'desktop' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertStringNotContainsString( '::before', $css );
		$this->assertStringNotContainsString( '::after', $css );
	}

	public function test_variation_label_colour_and_typography(): void {
		$css = $this->gen( [
			'blockId'                  => 'a',
			'variationLabelColor'      => [ 'light' => '#333333', 'dark' => '#cccccc' ],
			'variationLabelTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ], 'fontWeight' => '600' ] ],
		] );
		$this->assertCssHas( $css, self::VAR_LABEL, 'color:#333333' );
		$this->assertCssHas( $css, self::VAR_LABEL, 'font-size:14px' );
		$this->assertCssHas( $css, self::VAR_LABEL, 'font-weight:600' );
		$this->assertCssHasInDark( $css, self::VAR_LABEL, 'color:#cccccc' );
	}

	public function test_variation_label_typography_mobile_in_media_query(): void {
		$css = $this->gen( [
			'blockId'                  => 'a',
			'variationLabelTypography' => [ 'mobile' => [ 'fontSize' => [ 'value' => '12', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::VAR_LABEL, 'font-size:12px' );
	}

	public function test_variation_select_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'                    => 'a',
			'variationSelectColor'       => [ 'light' => '#222222', 'dark' => '#dddddd' ],
			'variationSelectBackground'  => [ 'light' => '#ffffff', 'dark' => '#101010' ],
			'variationSelectBorderColor' => [ 'light' => '#dddddd', 'dark' => '#333333' ],
		] );
		$this->assertCssHas( $css, self::VAR_SELECT, 'color:#222222' );
		$this->assertCssHas( $css, self::VAR_SELECT, 'background-color:#ffffff' );
		$this->assertCssHas( $css, self::VAR_SELECT, 'border-color:#dddddd' );
		$this->assertCssHasInDark( $css, self::VAR_SELECT, 'color:#dddddd' );
		$this->assertCssHasInDark( $css, self::VAR_SELECT, 'background-color:#101010' );
		$this->assertCssHasInDark( $css, self::VAR_SELECT, 'border-color:#333333' );
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
			'blockId'         => 'a',
			'buttonTextColor' => [ 'light' => '#ffffff', 'dark' => '#f0f0f0' ],
		] );
		$this->assertCssHasInDark( $css, self::BUTTON, 'color:#f0f0f0', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
