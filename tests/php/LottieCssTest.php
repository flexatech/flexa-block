<?php
/**
 * Tests for the Lottie Animation block CSS generator.
 *
 * Mirrors the ContainerCssTest structure: one assert per emitted property, base
 * declarations scoped via assertCssHas(), responsive values checked inside their
 * media query, and dark values asserted as full property:value inside the dark
 * branch. Playback options (loop / speed / reverse / play trigger) are front-end
 * only and must emit no CSS.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Lottie_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Lottie_CSS
 */
class LottieCssTest extends CssTestCase {

	private const WRAP   = '.flexa-lottie-a';
	private const PLAYER = '.flexa-lottie-a .flexa-lottie__player';
	private const HOVER  = '.flexa-lottie-a:hover';

	/**
	 * Convenience wrapper around the Lottie generator.
	 *
	 * @param array $attrs Lottie attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Lottie_CSS::class, 'generate' ], $attrs );
	}

	// 1. Empty id → nothing.
	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	// 2. Theme-first: an untouched block (only a blockId) emits no declarations.
	public function test_untouched_block_emits_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	// 3. Base width + height sit on the player box (scoped to the descendant selector).
	public function test_width_and_height_on_player(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'width'   => [ 'desktop' => [ 'value' => '300', 'unit' => 'px' ] ],
			'height'  => [ 'desktop' => [ 'value' => '200', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::PLAYER, 'width:300px' );
		$this->assertCssHas( $css, self::PLAYER, 'height:200px' );
	}

	// 4. Responsive width + height land inside the tablet media query.
	public function test_width_and_height_responsive_in_media(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'width'   => [ 'tablet' => [ 'value' => '150', 'unit' => 'px' ] ],
			'height'  => [ 'tablet' => [ 'value' => '120', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::PLAYER, 'width:150px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::PLAYER, 'height:120px' );
	}

	// 9b. Alignment maps to justify-content on the wrapper (per device).
	public function test_alignment_justify_content(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'center', 'tablet' => 'right' ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'justify-content:center' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'justify-content:flex-end' );
	}

	public function test_alignment_left_maps_to_flex_start(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'left' ] ] );
		$this->assertCssHas( $css, self::WRAP, 'justify-content:flex-start' );
	}

	// 7. Foundational spacing: padding + margin on the wrapper.
	public function test_spacing_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ],
					'margin'  => [ 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:10px 10px 10px 10px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:20px 20px 20px 20px' );
	}

	// 7. Foundational advanced layout (overflow / position / z-index).
	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'overflow:hidden' );
		$this->assertCssHas( $css, self::WRAP, 'position:relative' );
		$this->assertCssHas( $css, self::WRAP, 'z-index:5' );
	}

	// 5 + 9. Base background colour (light) + dark full declaration.
	public function test_background_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#ffffff' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#000000' );
	}

	// 9. Background image sub-properties (position / size / repeat / attachment + url).
	public function test_background_image_sub_properties(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'  => 'image',
				'image' => [
					'url'        => 'https://example.com/a.jpg',
					'position'   => 'center center',
					'size'       => 'contain',
					'repeat'     => 'repeat-x',
					'attachment' => 'fixed',
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-image:url(https://example.com/a.jpg)' );
		$this->assertCssHas( $css, self::WRAP, 'background-position:center center' );
		$this->assertCssHas( $css, self::WRAP, 'background-size:contain' );
		$this->assertCssHas( $css, self::WRAP, 'background-repeat:repeat-x' );
		$this->assertCssHas( $css, self::WRAP, 'background-attachment:fixed' );
	}

	// 5 + 9. Gradient background: light background-image at base + dark in the dark branch.
	public function test_background_gradient_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'gradient',
				'gradient' => [ 'light' => 'linear-gradient(0deg,#aaa,#bbb)', 'dark' => 'linear-gradient(0deg,#111,#222)' ],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-image:linear-gradient(0deg,#aaa,#bbb)' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-image:linear-gradient(0deg,#111,#222)' );
	}

	// 6. Background gating: type "none" emits no background declaration.
	public function test_background_none_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'none', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertStringNotContainsString( 'background-color', $css );
		$this->assertStringNotContainsString( 'background-image', $css );
	}

	// 5 + 6. Background hover colour, light at base + dark in the dark branch;
	// nothing emitted when unset.
	public function test_background_hover_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'              => 'a',
			'backgroundColorHover' => [ 'light' => '#eeeeee', 'dark' => '#222222' ],
		] );
		$this->assertCssHas( $css, self::HOVER, 'background-color:#eeeeee' );
		$this->assertCssHasInDark( $css, self::HOVER, 'background-color:#222222' );
	}

	public function test_background_hover_unset_emits_no_hover_rule(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'width' => [ 'desktop' => [ 'value' => '300', 'unit' => 'px' ] ] ] );
		$this->assertStringNotContainsString( ':hover', $css );
	}

	// 7 + 9. Foundational border: geometry + light colour at base, dark colour in
	// the dark branch, plus the border sub-properties add_border emits.
	public function test_border_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#cccccc', 'dark' => '#333333' ],
					'radius' => [ 'topLeft' => '4', 'topRight' => '4', 'bottomRight' => '4', 'bottomLeft' => '4', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'border-style:solid' );
		$this->assertCssHas( $css, self::WRAP, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::WRAP, 'border-color:#cccccc' );
		$this->assertCssHas( $css, self::WRAP, 'border-radius:4px 4px 4px 4px' );
		$this->assertCssHasInDark( $css, self::WRAP, 'border-color:#333333' );
	}

	// 7. Foundational box shadow: full value at base + dark colour override in the
	// dark branch.
	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'box-shadow:0px 2px 8px 0px #000000' );
		$this->assertCssHasInDark( $css, self::WRAP, 'box-shadow:0px 2px 8px 0px #ffffff' );
	}

	public function test_box_shadow_disabled_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => false, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '' ] ],
		] );
		$this->assertStringNotContainsString( 'box-shadow', $css );
	}

	// 8. [data-theme="dark"] strategy instead of prefers-color-scheme.
	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );

		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#000000', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}

	// Playback options are read by view.ts only — they must produce no CSS.
	public function test_playback_options_emit_no_css(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'loop'    => false,
			'speed'   => 2.5,
			'reverse' => true,
			'playOn'  => 'hover',
		] );
		$this->assertSame( '', $css );
	}
}
