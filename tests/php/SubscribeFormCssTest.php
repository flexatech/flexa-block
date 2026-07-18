<?php
/**
 * Tests for the Subscribe Form block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Subscribe_Form_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Subscribe_Form_CSS
 */
class SubscribeFormCssTest extends CssTestCase {

	private const FORM   = '.flexa-subscribe-form-a';
	private const FIELDS = '.flexa-subscribe-form-a .flexa-subscribe-form__fields';
	private const LABEL  = '.flexa-subscribe-form-a .flexa-field__label';
	private const INPUT  = '.flexa-subscribe-form-a .flexa-field__control';
	private const PH     = '.flexa-subscribe-form-a .flexa-field__control::placeholder';
	private const SUBMIT = '.flexa-subscribe-form-a .flexa-subscribe-form__submit';
	private const HOVER  = '.flexa-subscribe-form-a .flexa-subscribe-form__submit:hover';
	private const OK     = '.flexa-subscribe-form-a .flexa-subscribe-form__message--success';
	private const ERR    = '.flexa-subscribe-form-a .flexa-subscribe-form__message--error';

	/**
	 * Convenience wrapper around the generator.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Subscribe_Form_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// With no styling attributes the generator must invent nothing (guide §6.1).
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_form_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'desktop' => [
				'padding' => [ 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px' ],
				'margin'  => [ 'top' => '10', 'right' => '0', 'bottom' => '10', 'left' => '0', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::FORM, 'padding:20px 20px 20px 20px' );
		$this->assertCssHas( $css, self::FORM, 'margin:10px 0px 10px 0px' );
	}

	public function test_form_border_light_at_base_and_dark_in_media(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [ 'desktop' => [
				'style' => 'solid',
				'width' => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
				'color' => [ 'light' => '#cccccc', 'dark' => '#333333' ],
			] ],
		] );
		$this->assertCssHas( $css, self::FORM, 'border-style:solid' );
		$this->assertCssHas( $css, self::FORM, 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, self::FORM, 'border-color:#cccccc' );
		$this->assertCssHasInDark( $css, self::FORM, 'border-color:#333333' );
	}

	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '3' ] ],
		] );
		$this->assertCssHas( $css, self::FORM, 'overflow:hidden' );
		$this->assertCssHas( $css, self::FORM, 'position:relative' );
		$this->assertCssHas( $css, self::FORM, 'z-index:3' );
	}

	public function test_field_gap_desktop_and_tablet(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'fieldGap' => [
				'desktop' => [ 'value' => '20', 'unit' => 'px' ],
				'tablet'  => [ 'value' => '12', 'unit' => 'px' ],
			],
		] );
		$this->assertCssHas( $css, self::FIELDS, 'gap:20px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::FIELDS, 'gap:12px' );
	}

	public function test_background_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#111111' ] ],
		] );
		$this->assertCssHas( $css, self::FORM, 'background-color:#ffffff' );
		$this->assertCssHasInDark( $css, self::FORM, 'background-color:#111111' );
	}

	public function test_background_none_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'none', 'color' => [ 'light' => '#ffffff', 'dark' => '#111111' ] ],
		] );
		$this->assertStringNotContainsString( 'background-color', $css );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '4', 'blur' => '12', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::FORM, 'box-shadow:0px 4px 12px 0px #000000' );
		$this->assertCssHasInDark( $css, self::FORM, 'box-shadow:0px 4px 12px 0px #ffffff' );
	}

	public function test_box_shadow_disabled_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => false, 'horizontal' => '0', 'vertical' => '4', 'blur' => '12', 'spread' => '0', 'color' => [ 'light' => '#000000' ] ],
		] );
		$this->assertStringNotContainsString( 'box-shadow', $css );
	}

	public function test_label_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'labelColor' => [ 'light' => '#222222', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, self::LABEL, 'color:#222222' );
		$this->assertCssHasInDark( $css, self::LABEL, 'color:#eeeeee' );
	}

	public function test_input_text_placeholder_and_background(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'inputTextColor'        => [ 'light' => '#1a1a1a', 'dark' => '#fafafa' ],
			'inputPlaceholderColor' => [ 'light' => '#999999' ],
			'inputBackground'       => [ 'light' => '#f5f5f5', 'dark' => '#202020' ],
		] );
		$this->assertCssHas( $css, self::INPUT, 'color:#1a1a1a' );
		$this->assertCssHasInDark( $css, self::INPUT, 'color:#fafafa' );
		$this->assertCssHas( $css, self::PH, 'color:#999999' );
		$this->assertCssHas( $css, self::INPUT, 'background-color:#f5f5f5' );
		$this->assertCssHasInDark( $css, self::INPUT, 'background-color:#202020' );
	}

	public function test_input_border_subproperties_and_padding(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'inputBorder'  => [
				'style'  => 'solid',
				'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
				'color'  => [ 'light' => '#bdbdbd', 'dark' => '#444444' ],
				'radius' => [ 'topLeft' => '6', 'topRight' => '6', 'bottomRight' => '6', 'bottomLeft' => '6', 'unit' => 'px' ],
			],
			'inputPadding' => [ 'top' => '10', 'right' => '12', 'bottom' => '10', 'left' => '12', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::INPUT, 'border-style:solid' );
		$this->assertCssHas( $css, self::INPUT, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::INPUT, 'border-color:#bdbdbd' );
		$this->assertCssHas( $css, self::INPUT, 'border-radius:6px 6px 6px 6px' );
		$this->assertCssHasInDark( $css, self::INPUT, 'border-color:#444444' );
		$this->assertCssHas( $css, self::INPUT, 'padding:10px 12px 10px 12px' );
	}

	public function test_submit_colors_padding_radius_and_hover(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'submitTextColor'       => [ 'light' => '#ffffff', 'dark' => '#000000' ],
			'submitBackground'      => [ 'light' => '#2563eb' ],
			'submitTextColorHover'  => [ 'light' => '#f0f0f0' ],
			'submitBackgroundHover' => [ 'light' => '#1d4ed8' ],
			'submitPadding'         => [ 'top' => '12', 'right' => '24', 'bottom' => '12', 'left' => '24', 'unit' => 'px' ],
			'submitRadius'          => [ 'value' => '8', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::SUBMIT, 'color:#ffffff' );
		$this->assertCssHasInDark( $css, self::SUBMIT, 'color:#000000' );
		$this->assertCssHas( $css, self::SUBMIT, 'background-color:#2563eb' );
		$this->assertCssHas( $css, self::SUBMIT, 'padding:12px 24px 12px 24px' );
		$this->assertCssHas( $css, self::SUBMIT, 'border-radius:8px' );
		$this->assertCssHas( $css, self::HOVER, 'color:#f0f0f0' );
		$this->assertCssHas( $css, self::HOVER, 'background-color:#1d4ed8' );
	}

	public function test_message_colors(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'successColor'      => [ 'light' => '#0a7d28' ],
			'successBackground' => [ 'light' => '#e7f6ec' ],
			'errorColor'        => [ 'light' => '#b32020' ],
			'errorBackground'   => [ 'light' => '#fbeaea' ],
		] );
		$this->assertCssHas( $css, self::OK, 'color:#0a7d28' );
		$this->assertCssHas( $css, self::OK, 'background-color:#e7f6ec' );
		$this->assertCssHas( $css, self::ERR, 'color:#b32020' );
		$this->assertCssHas( $css, self::ERR, 'background-color:#fbeaea' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'    => 'a',
			'labelColor' => [ 'light' => '#222222', 'dark' => '#eeeeee' ],
		] );

		$this->assertCssHasInDark( $css, self::LABEL, 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
