<?php
/**
 * Tests for the Button block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Button_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Button_CSS
 */
class ButtonCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Button generator.
	 *
	 * @param array $attrs Button attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Button_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_button_emits_nothing(): void {
		// Only a blockId: the theme should style everything, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'sizePreset' => 'md' ] ) );
	}

	public function test_declarations_target_the_anchor(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'textColor' => [ 'light' => '#ffffff' ],
		] );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button', 'color:#ffffff' );
	}

	public function test_size_preset_uses_tokens_and_only_for_sm_lg(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'sizePreset' => 'lg' ] );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button', 'padding:var(--flexa-space-md, 16px) var(--flexa-space-xl, 40px)' );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button', 'font-size:var(--flexa-font-size-lg, 1.25rem)' );
	}

	public function test_custom_padding_overrides_size_preset(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'sizePreset' => 'lg',
			'spacing' => [ 'desktop' => [ 'padding' => [ 'top' => '5', 'right' => '10', 'bottom' => '5', 'left' => '10', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button', 'padding:5px 10px 5px 10px' );
	}

	public function test_margin_targets_wrapper(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'desktop' => [ 'margin' => [ 'top' => '0', 'right' => 'auto', 'bottom' => '0', 'left' => 'auto', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-button-a', 'margin:0px auto 0px auto' );
	}

	public function test_typography_font_size_and_weight(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '18', 'unit' => 'px' ], 'fontWeight' => '600', 'textTransform' => 'uppercase' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button', 'font-size:18px' );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button', 'font-weight:600' );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button', 'text-transform:uppercase' );
	}

	public function test_tablet_typography_in_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ] ] ],
		] );
		$this->assertStringContainsString( '@media (max-width: 1024px)', $css );
		$this->assertStringContainsString( 'font-size:14px', $css );
	}

	public function test_background_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#2563eb', 'dark' => '#1e40af' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button', 'background-color:#2563eb' );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
		$this->assertStringContainsString( '#1e40af', $css );
	}

	public function test_hover_colors_on_hover_selector(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'hover'   => [
				'text'       => [ 'light' => '#ffffff' ],
				'background'  => [ 'light' => '#1d4ed8' ],
				'border'      => [ 'light' => '#1d4ed8' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button:hover', 'color:#ffffff' );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button:hover', 'background-color:#1d4ed8' );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button:hover', 'border-color:#1d4ed8' );
	}

	public function test_hover_dark_mode_branch(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'hover'   => [ 'background' => [ 'light' => '#1d4ed8', 'dark' => '#1e3a8a' ] ],
		] );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
		$this->assertStringContainsString( '#1e3a8a', $css );
	}

	public function test_hover_transform_preset_adds_transition_and_transform(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'hover'   => [ 'transform' => 'lift' ],
		] );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button', 'transition:transform 0.2s ease' );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button:hover', 'transform:translateY(-4px)' );
	}

	public function test_hover_transform_none_emits_nothing(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'hover'   => [ 'transform' => 'none' ],
		] );
		$this->assertSame( '', $css );
	}

	public function test_border_light_at_base(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style' => 'solid',
					'width' => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
					'color' => [ 'light' => '#111827', 'dark' => '#f9fafb' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-button-a .wp-element-button', 'border-color:#111827' );
		$this->assertStringContainsString( '#f9fafb', $css );
	}

	public function test_box_shadow(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000' ] ],
		] );
		$this->assertStringContainsString( 'box-shadow:0px 2px 8px 0px #000', $css );
	}
}
