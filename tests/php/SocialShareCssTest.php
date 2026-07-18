<?php
/**
 * Tests for the Social Share block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can
 * emit has a matching assertCssHas() on the right selector, plus on/off gating
 * for the conditional bits (colour mode tint, button background, direction,
 * background type, border style) and light/dark parity.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Social_Share_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Social_Share_CSS
 */
class SocialShareCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Social Share generator.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Social_Share_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// A freshly inserted block (defaults only) must not emit a single
		// declaration — the brand artwork and theme spacing carry the look.
		$css = $this->gen( [
			'blockId'          => 'a',
			'items'            => [
				[ 'network' => 'facebook' ],
				[ 'network' => 'x' ],
			],
			'direction'        => 'row',
			'alignment'        => [ 'desktop' => '', 'tablet' => '', 'mobile' => '' ],
			'colorMode'        => 'official',
			'tint'             => [ 'light' => '', 'dark' => '' ],
			'buttonBackground' => [ 'light' => '', 'dark' => '' ],
			'background'       => [ 'type' => 'none', 'color' => [ 'light' => '', 'dark' => '' ], 'gradient' => [ 'light' => '', 'dark' => '' ] ],
			'boxShadow'        => [ 'enabled' => false, 'horizontal' => '0', 'vertical' => '4', 'blur' => '12', 'spread' => '0', 'color' => [ 'light' => 'rgba(0,0,0,0.1)', 'dark' => '' ] ],
		] );
		$this->assertSame( '', $css );
	}

	public function test_gap_and_alignment_on_list(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'gap'       => [ 'desktop' => [ 'value' => '20', 'unit' => 'px' ] ],
			'alignment' => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, '.flexa-social-share-a .flexa-social-share__list', 'gap:20px' );
		$this->assertCssHas( $css, '.flexa-social-share-a .flexa-social-share__list', 'justify-content:center' );
	}

	public function test_alignment_right_maps_to_flex_end(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'right' ],
		] );
		$this->assertCssHas( $css, '.flexa-social-share-a .flexa-social-share__list', 'justify-content:flex-end' );
	}

	public function test_direction_column_sets_flex_direction(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'direction' => 'column',
		] );
		$this->assertCssHas( $css, '.flexa-social-share-a .flexa-social-share__list', 'flex-direction:column' );
	}

	public function test_direction_row_emits_no_flex_direction(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'direction' => 'row',
		] );
		$this->assertStringNotContainsString( 'flex-direction', $css );
	}

	public function test_icon_size_is_square(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'iconSize' => [ 'desktop' => [ 'value' => '32', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-social-share-a .flexa-social-share__icon', 'width:32px' );
		$this->assertCssHas( $css, '.flexa-social-share-a .flexa-social-share__icon', 'height:32px' );
	}

	public function test_wrapper_spacing_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '24', 'right' => '16', 'bottom' => '24', 'left' => '16', 'unit' => 'px' ],
					'margin'  => [ 'top' => '32', 'right' => '0', 'bottom' => '32', 'left' => '0', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-social-share-a', 'padding:24px 16px 24px 16px' );
		$this->assertCssHas( $css, '.flexa-social-share-a', 'margin:32px 0px 32px 0px' );
	}

	public function test_border_on_wrapper_with_dark_colour(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#dddddd', 'dark' => '#444444' ],
					'radius' => [ 'topLeft' => '10', 'topRight' => '10', 'bottomRight' => '10', 'bottomLeft' => '10', 'unit' => 'px' ],
				],
			],
		] );
		// add_border emits all four sub-properties.
		$this->assertCssHas( $css, '.flexa-social-share-a', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-social-share-a', 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, '.flexa-social-share-a', 'border-color:#dddddd' );
		$this->assertCssHas( $css, '.flexa-social-share-a', 'border-radius:10px 10px 10px 10px' );
		$this->assertCssHasInDark( $css, '.flexa-social-share-a', 'border-color:#444444' );
	}

	public function test_border_absent_when_no_style(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [ 'desktop' => [ 'style' => '' ] ],
		] );
		$this->assertStringNotContainsString( 'border-style:', $css );
	}

	public function test_background_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#f5f5f5', 'dark' => '#111827' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-social-share-a', 'background-color:#f5f5f5' );
		$this->assertCssHasInDark( $css, '.flexa-social-share-a', 'background-color:#111827' );
	}

	public function test_background_gradient_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'gradient',
				'gradient' => [ 'light' => 'linear-gradient(90deg,#f00,#00f)', 'dark' => 'linear-gradient(90deg,#111,#333)' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-social-share-a', 'background-image:linear-gradient(90deg,#f00,#00f)' );
		$this->assertCssHasInDark( $css, '.flexa-social-share-a', 'background-image:linear-gradient(90deg,#111,#333)' );
	}

	public function test_background_none_emits_nothing_even_with_colours(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'none', 'color' => [ 'light' => '#ff0000', 'dark' => '#00ff00' ] ],
		] );
		$this->assertStringNotContainsString( 'background', $css );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000', 'dark' => '#fff' ] ],
		] );
		// Full value on both branches — a bare '#fff' would match the light '#ffffff'.
		$this->assertCssHas( $css, '.flexa-social-share-a', 'box-shadow:0px 2px 8px 0px #000' );
		$this->assertCssHasInDark( $css, '.flexa-social-share-a', 'box-shadow:0px 2px 8px 0px #fff' );
	}

	public function test_box_shadow_disabled_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => false, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000', 'dark' => '#fff' ] ],
		] );
		$this->assertStringNotContainsString( 'box-shadow', $css );
	}

	public function test_tint_custom_mode_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'colorMode' => 'custom',
			'tint'      => [ 'light' => '#16a34a', 'dark' => '#86efac' ],
		] );
		$this->assertCssHas( $css, '.flexa-social-share-a .flexa-social-share__item', 'color:#16a34a' );
		$this->assertCssHasInDark( $css, '.flexa-social-share-a .flexa-social-share__item', 'color:#86efac' );
	}

	public function test_tint_ignored_in_official_mode(): void {
		// Official artwork carries its own colours — a tint must produce no CSS.
		$css = $this->gen( [
			'blockId'   => 'a',
			'colorMode' => 'official',
			'tint'      => [ 'light' => '#16a34a', 'dark' => '#86efac' ],
		] );
		$this->assertStringNotContainsString( '#16a34a', $css );
		$this->assertStringNotContainsString( '#86efac', $css );
	}

	public function test_button_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'buttonBackground' => [ 'light' => '#2563eb', 'dark' => '#1e3a8a' ],
		] );
		$this->assertCssHas( $css, '.flexa-social-share-a .flexa-social-share__item', 'background-color:#2563eb' );
		$this->assertCssHasInDark( $css, '.flexa-social-share-a .flexa-social-share__item', 'background-color:#1e3a8a' );
	}

	public function test_tablet_values_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'gap'       => [ 'tablet' => [ 'value' => '8', 'unit' => 'px' ] ],
			'iconSize'  => [ 'tablet' => [ 'value' => '20', 'unit' => 'px' ] ],
			'alignment' => [ 'tablet' => 'center' ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-social-share-a .flexa-social-share__list', 'gap:8px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-social-share-a .flexa-social-share__list', 'justify-content:center' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-social-share-a .flexa-social-share__icon', 'width:20px' );
	}

	public function test_mobile_values_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'mobile' => [ 'padding' => [ 'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', '.flexa-social-share-a', 'padding:8px 8px 8px 8px' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'   => 'a',
			'colorMode' => 'custom',
			'tint'      => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, '.flexa-social-share-a .flexa-social-share__item', 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
