<?php
/**
 * Tests for the Steps block CSS generator.
 *
 * One assertion per property the generator emits (cross-checked against the
 * inspector controls), plus gating (nothing when the user hasn't chosen),
 * responsive placement and dark-mode branches.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Steps_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Steps_CSS
 */
class StepsCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Steps generator.
	 *
	 * @param array $attrs Steps attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Steps_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_no_css(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_max_width_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'maxWidth' => [ 'desktop' => [ 'value' => '640', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a', 'max-width:640px' );
	}

	public function test_marker_gap_on_item(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'markerGap' => [ 'desktop' => [ 'value' => '20', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__item', 'gap:20px' );
	}

	public function test_item_gap_on_non_last_content(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'itemGap' => [ 'desktop' => [ 'value' => '30', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__item:not(:last-child) .flexa-steps__content', 'padding-bottom:30px' );
	}

	public function test_marker_size_sets_width_and_height(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'markerSize' => [ 'desktop' => [ 'value' => '48', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__marker', 'width:48px' );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__marker', 'height:48px' );
	}

	public function test_content_alignment(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'contentAlign' => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__content', 'text-align:center' );
	}

	public function test_title_typography(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'titleTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '20', 'unit' => 'px' ], 'fontWeight' => '700' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__title', 'font-size:20px' );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__title', 'font-weight:700' );
	}

	public function test_description_typography(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'descriptionTypography' => [ 'desktop' => [ 'textTransform' => 'uppercase' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__description', 'text-transform:uppercase' );
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
		$this->assertCssHas( $css, '.flexa-steps-a', 'padding:5px 5px 5px 5px' );
		$this->assertCssHas( $css, '.flexa-steps-a', 'margin:15px 15px 15px 15px' );
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
		$this->assertCssHas( $css, '.flexa-steps-a', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-steps-a', 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, '.flexa-steps-a', 'border-color:#cccccc' );
		$this->assertCssHas( $css, '.flexa-steps-a', 'border-radius:6px 6px 6px 6px' );
		$this->assertCssHasInDark( $css, '.flexa-steps-a', 'border-color:#333333' );
	}

	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '3' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-steps-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-steps-a', 'z-index:3' );
	}

	public function test_marker_background_and_text_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'markerColor'     => [ 'light' => '#eef2ff', 'dark' => '#1e293b' ],
			'markerTextColor' => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__marker', 'background:#eef2ff' );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__marker', 'color:#2563eb' );
		$this->assertCssHasInDark( $css, '.flexa-steps-a .flexa-steps__marker', 'background:#1e293b' );
		$this->assertCssHasInDark( $css, '.flexa-steps-a .flexa-steps__marker', 'color:#93c5fd' );
	}

	public function test_status_accents_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'doneColor'     => [ 'light' => '#16a34a', 'dark' => '#4ade80' ],
			'activeColor'   => [ 'light' => '#2563eb', 'dark' => '#60a5fa' ],
			'upcomingColor' => [ 'light' => '#9ca3af', 'dark' => '#6b7280' ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__item.is-done .flexa-steps__marker', 'background:#16a34a' );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__item.is-active .flexa-steps__marker', 'background:#2563eb' );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__item.is-upcoming .flexa-steps__marker', 'background:#9ca3af' );
		$this->assertCssHasInDark( $css, '.flexa-steps-a .flexa-steps__item.is-done .flexa-steps__marker', 'background:#4ade80' );
		$this->assertCssHasInDark( $css, '.flexa-steps-a .flexa-steps__item.is-active .flexa-steps__marker', 'background:#60a5fa' );
		$this->assertCssHasInDark( $css, '.flexa-steps-a .flexa-steps__item.is-upcoming .flexa-steps__marker', 'background:#6b7280' );
	}

	public function test_connector_off_hides_line_and_emits_no_style(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'connectorShow'  => false,
			'connectorStyle' => 'dashed',
			'connectorWidth' => [ 'desktop' => [ 'value' => '3', 'unit' => 'px' ] ],
			'connectorColor' => [ 'light' => '#999999', 'dark' => '#555555' ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__connector', 'display:none' );
		$this->assertStringNotContainsString( 'border-left-style', $css );
		$this->assertStringNotContainsString( 'border-left-width', $css );
		$this->assertStringNotContainsString( 'border-left-color', $css );
	}

	public function test_connector_on_emits_style_width_and_colour(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'connectorShow'  => true,
			'connectorStyle' => 'dashed',
			'connectorWidth' => [ 'desktop' => [ 'value' => '3', 'unit' => 'px' ] ],
			'connectorColor' => [ 'light' => '#999999', 'dark' => '#555555' ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__connector', 'border-left-style:dashed' );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__connector', 'border-left-width:3px' );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__connector', 'border-left-color:#999999' );
		$this->assertCssHasInDark( $css, '.flexa-steps-a .flexa-steps__connector', 'border-left-color:#555555' );
		$this->assertStringNotContainsString( 'display:none', $css );
	}

	public function test_title_and_description_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'titleColor'       => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
			'descriptionColor' => [ 'light' => '#444444', 'dark' => '#bbbbbb' ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__title', 'color:#111111' );
		$this->assertCssHas( $css, '.flexa-steps-a .flexa-steps__description', 'color:#444444' );
		$this->assertCssHasInDark( $css, '.flexa-steps-a .flexa-steps__title', 'color:#eeeeee' );
		$this->assertCssHasInDark( $css, '.flexa-steps-a .flexa-steps__description', 'color:#bbbbbb' );
	}

	public function test_wrapper_background_and_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
			'boxShadow'  => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-steps-a', 'background-color:#ffffff' );
		$this->assertStringContainsString( 'box-shadow:0px 2px 8px 0px #000000', $css );
		$this->assertCssHasInDark( $css, '.flexa-steps-a', 'background-color:#000000' );
		$this->assertCssHasInDark( $css, '.flexa-steps-a', 'box-shadow:0px 2px 8px 0px #ffffff' );
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
		$this->assertCssHas( $css, '.flexa-steps-a', 'background-size:cover' );
		$this->assertCssHas( $css, '.flexa-steps-a.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	public function test_tablet_values_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'markerGap' => [ 'tablet' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-steps-a .flexa-steps__item', 'gap:10px' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'    => 'a',
			'titleColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );

		$this->assertCssHasInDark( $css, '.flexa-steps-a .flexa-steps__title', 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
