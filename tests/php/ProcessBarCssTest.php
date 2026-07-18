<?php
/**
 * Tests for the Progress Bar block CSS generator.
 *
 * One assertion per property the generator emits (cross-checked against the
 * inspector controls), plus gating (nothing when the user hasn't chosen, line vs
 * ring branches), responsive placement and dark-mode branches.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Process_Bar_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Process_Bar_CSS
 */
class ProcessBarCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Progress Bar generator.
	 *
	 * @param array $attrs Progress Bar attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Process_Bar_CSS::class, 'generate' ], $attrs );
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
			'alignment' => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'align-items:center' );
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'text-align:center' );
	}

	public function test_max_width_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'maxWidth' => [ 'desktop' => [ 'value' => '400', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'max-width:400px' );
	}

	public function test_line_bar_thickness_on_track(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'barType'   => 'line',
			'barHeight' => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__track', 'height:12px' );
	}

	public function test_line_corner_radius_on_track_and_fill(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'barType'   => 'line',
			'barRadius' => [ 'value' => '999', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__track', 'border-radius:999px' );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__fill', 'border-radius:999px' );
	}

	public function test_title_typography(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'titleTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '18', 'unit' => 'px' ], 'fontWeight' => '600' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__title', 'font-size:18px' );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__title', 'font-weight:600' );
	}

	public function test_counter_typography(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'counterTypography' => [ 'desktop' => [ 'textTransform' => 'uppercase' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__counter', 'text-transform:uppercase' );
	}

	public function test_title_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'titleColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__title', 'color:#111111' );
		$this->assertCssHasInDark( $css, '.flexa-process-bar-a .flexa-process-bar__title', 'color:#eeeeee' );
	}

	public function test_counter_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'counterColor' => [ 'light' => '#222222', 'dark' => '#dddddd' ],
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__counter', 'color:#222222' );
		$this->assertCssHasInDark( $css, '.flexa-process-bar-a .flexa-process-bar__counter', 'color:#dddddd' );
	}

	public function test_line_fill_and_track_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'barType'    => 'line',
			'fillColor'  => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
			'trackColor' => [ 'light' => '#e5e7eb', 'dark' => '#374151' ],
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__fill', 'background-color:#2563eb' );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__track', 'background-color:#e5e7eb' );
		$this->assertCssHasInDark( $css, '.flexa-process-bar-a .flexa-process-bar__fill', 'background-color:#93c5fd' );
		$this->assertCssHasInDark( $css, '.flexa-process-bar-a .flexa-process-bar__track', 'background-color:#374151' );
	}

	public function test_solid_fill_emits_no_stripe(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'barType'   => 'line',
			'fillStyle' => 'solid',
			'fillColor' => [ 'light' => '#2563eb', 'dark' => '' ],
		] );
		$this->assertStringNotContainsString( 'repeating-linear-gradient', $css );
		$this->assertStringNotContainsString( 'animation:', $css );
	}

	public function test_striped_fill_emits_stripe_image(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'barType'   => 'line',
			'fillStyle' => 'striped',
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__fill', 'background-image:repeating-linear-gradient(45deg' );
		$this->assertStringNotContainsString( 'animation:', $css );
	}

	public function test_striped_animated_fill_emits_animation(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'barType'   => 'line',
			'fillStyle' => 'striped-animated',
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__fill', 'background-image:repeating-linear-gradient(45deg' );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__fill', 'animation:flexa-process-bar-stripes 1s linear infinite' );
	}

	public function test_circle_size_and_stroke_width_on_ring(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'barType'     => 'circle',
			'circleSize'  => [ 'desktop' => [ 'value' => '160', 'unit' => 'px' ] ],
			'strokeWidth' => [ 'desktop' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__ring', 'width:160px' );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__ring', 'height:160px' );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__ring-track', 'stroke-width:8' );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__ring-fill', 'stroke-width:8' );
	}

	public function test_circle_fill_and_track_stroke_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'barType'    => 'circle',
			'fillColor'  => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
			'trackColor' => [ 'light' => '#e5e7eb', 'dark' => '#374151' ],
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__ring-fill', 'stroke:#2563eb' );
		$this->assertCssHas( $css, '.flexa-process-bar-a .flexa-process-bar__ring-track', 'stroke:#e5e7eb' );
		$this->assertCssHasInDark( $css, '.flexa-process-bar-a .flexa-process-bar__ring-fill', 'stroke:#93c5fd' );
		$this->assertCssHasInDark( $css, '.flexa-process-bar-a .flexa-process-bar__ring-track', 'stroke:#374151' );
		// A ring layout must not emit the line fill's background-color.
		$this->assertStringNotContainsString( 'background-color', $css );
	}

	public function test_circle_line_cap_gating(): void {
		$off = $this->gen( [
			'blockId' => 'a',
			'barType' => 'circle',
		] );
		$this->assertStringNotContainsString( 'stroke-linecap', $off );

		$on = $this->gen( [
			'blockId' => 'a',
			'barType' => 'circle',
			'lineCap' => 'butt',
		] );
		$this->assertCssHas( $on, '.flexa-process-bar-a .flexa-process-bar__ring-fill', 'stroke-linecap:butt' );
		$this->assertCssHas( $on, '.flexa-process-bar-a .flexa-process-bar__ring-track', 'stroke-linecap:butt' );
	}

	public function test_line_layout_emits_no_ring_css(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'barType'    => 'line',
			'fillColor'  => [ 'light' => '#2563eb', 'dark' => '' ],
			'circleSize' => [ 'desktop' => [ 'value' => '160', 'unit' => 'px' ] ],
		] );
		$this->assertStringNotContainsString( 'flexa-process-bar__ring', $css );
		$this->assertStringNotContainsString( 'stroke', $css );
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
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'padding:5px 5px 5px 5px' );
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'margin:15px 15px 15px 15px' );
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
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'border-color:#cccccc' );
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'border-radius:6px 6px 6px 6px' );
		$this->assertCssHasInDark( $css, '.flexa-process-bar-a', 'border-color:#333333' );
	}

	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '3' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'z-index:3' );
	}

	public function test_wrapper_background_and_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
			'boxShadow'  => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'background-color:#ffffff' );
		$this->assertStringContainsString( 'box-shadow:0px 2px 8px 0px #000000', $css );
		$this->assertCssHasInDark( $css, '.flexa-process-bar-a', 'background-color:#000000' );
		$this->assertCssHasInDark( $css, '.flexa-process-bar-a', 'box-shadow:0px 2px 8px 0px #ffffff' );
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
		$this->assertCssHas( $css, '.flexa-process-bar-a', 'background-size:cover' );
		$this->assertCssHas( $css, '.flexa-process-bar-a.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	public function test_tablet_values_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'barType'   => 'line',
			'barHeight' => [ 'tablet' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-process-bar-a .flexa-process-bar__track', 'height:8px' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'    => 'a',
			'barType'    => 'line',
			'fillColor'  => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
		] );

		$this->assertCssHasInDark( $css, '.flexa-process-bar-a .flexa-process-bar__fill', 'background-color:#93c5fd', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
