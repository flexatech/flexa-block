<?php
/**
 * Tests for the Countdown block CSS generator.
 *
 * One assertion per property the generator emits (cross-checked against the
 * inspector controls), plus gating (nothing when the user hasn't chosen),
 * responsive placement and dark-mode branches.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Countdown_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Countdown_CSS
 */
class CountdownCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Countdown generator.
	 *
	 * @param array $attrs Countdown attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Countdown_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_no_css(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_alignment_sets_align_items_and_text_align(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'right' ],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a', 'align-items:flex-end' );
		$this->assertCssHas( $css, '.flexa-countdown-a', 'text-align:right' );
	}

	public function test_justify_alignment_spreads_the_timer(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'justify' ],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a', 'align-items:stretch' );
		$this->assertCssHas( $css, '.flexa-countdown-a .flexa-countdown__timer', 'justify-content:space-between' );
	}

	public function test_item_gap_on_timer(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'itemGap' => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a .flexa-countdown__timer', 'gap:24px' );
	}

	public function test_max_width_and_min_height(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'maxWidth' => [ 'desktop' => [ 'value' => '600', 'unit' => 'px' ] ],
			'size'     => [ 'desktop' => [ 'minHeight' => [ 'value' => '200', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a', 'max-width:600px' );
		$this->assertCssHas( $css, '.flexa-countdown-a', 'min-height:200px' );
	}

	public function test_digit_typography(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'digitTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '48', 'unit' => 'px' ], 'fontWeight' => '700' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a .flexa-countdown__digit', 'font-size:48px' );
		$this->assertCssHas( $css, '.flexa-countdown-a .flexa-countdown__digit', 'font-weight:700' );
	}

	public function test_separator_font_size(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'separatorFontSize' => [ 'desktop' => [ 'value' => '32', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a .flexa-countdown__separator', 'font-size:32px' );
	}

	public function test_label_typography(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'labelTypography' => [ 'desktop' => [ 'textTransform' => 'uppercase' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a .flexa-countdown__label', 'text-transform:uppercase' );
	}

	public function test_item_padding_and_radius(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'itemPadding'      => [ 'desktop' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ] ],
			'itemBorderRadius' => [ 'desktop' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a .flexa-countdown__unit', 'padding:10px 10px 10px 10px' );
		$this->assertCssHas( $css, '.flexa-countdown-a .flexa-countdown__unit', 'border-radius:8px' );
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
		$this->assertCssHas( $css, '.flexa-countdown-a', 'padding:5px 5px 5px 5px' );
		$this->assertCssHas( $css, '.flexa-countdown-a', 'margin:15px 15px 15px 15px' );
	}

	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '3' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-countdown-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-countdown-a', 'z-index:3' );
	}

	public function test_digit_colour_light_at_base_and_dark_in_media(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'digitColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a .flexa-countdown__digit', 'color:#111111' );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
		$this->assertStringContainsString( '#eeeeee', $css );
	}

	public function test_label_and_separator_colours(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'labelColor'     => [ 'light' => '#222222', 'dark' => '' ],
			'separatorColor' => [ 'light' => '#999999', 'dark' => '' ],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a .flexa-countdown__label', 'color:#222222' );
		$this->assertCssHas( $css, '.flexa-countdown-a .flexa-countdown__separator', 'color:#999999' );
	}

	public function test_item_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'itemBackground' => [ 'light' => '#f0f0f0', 'dark' => '#202020' ],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a .flexa-countdown__unit', 'background:#f0f0f0' );
		$this->assertStringContainsString( '#202020', $css );
	}

	public function test_wrapper_background_and_box_shadow(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
			'boxShadow'  => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000', 'dark' => '#fff' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a', 'background-color:#ffffff' );
		$this->assertStringContainsString( 'box-shadow:0px 2px 8px 0px #000', $css );
		$this->assertStringContainsString( '#000000', $css );
	}

	public function test_border_light_at_base(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style' => 'solid',
					'width' => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
					'color' => [ 'light' => '#cccccc', 'dark' => '#333333' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-countdown-a', 'border-color:#cccccc' );
		$this->assertStringContainsString( '#333333', $css );
	}

	public function test_tablet_values_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'itemGap' => [ 'tablet' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertStringContainsString( '@media (max-width: 1024px)', $css );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'    => 'a',
			'digitColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );

		$this->assertStringContainsString( '[data-theme="dark"] .flexa-countdown-a .flexa-countdown__digit', $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
		$this->assertStringContainsString( '#eeeeee', $css );
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
		$this->assertCssHas( $css, '.flexa-countdown-a', 'background-size:cover' );
		$this->assertCssHas( $css, '.flexa-countdown-a.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}
}
