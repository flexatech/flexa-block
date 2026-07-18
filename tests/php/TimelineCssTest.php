<?php
/**
 * Tests for the Timeline block CSS generator.
 *
 * One assertion per property the generator emits (cross-checked against the
 * inspector controls), plus gating (nothing when the user hasn't chosen),
 * responsive placement and dark-mode branches.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Timeline_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Timeline_CSS
 */
class TimelineCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Timeline generator.
	 *
	 * @param array $attrs Timeline attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Timeline_CSS::class, 'generate' ], $attrs );
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
		$this->assertCssHas( $css, '.flexa-timeline-a', 'max-width:640px' );
	}

	public function test_marker_gap_on_item(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'markerGap' => [ 'desktop' => [ 'value' => '20', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__item', 'gap:20px' );
	}

	public function test_item_gap_on_non_last_content(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'itemGap' => [ 'desktop' => [ 'value' => '30', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__item:not(:last-child) .flexa-timeline__content', 'padding-bottom:30px' );
	}

	public function test_marker_size_sets_width_and_height(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'markerSize' => [ 'desktop' => [ 'value' => '48', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__marker', 'width:48px' );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__marker', 'height:48px' );
	}

	public function test_content_alignment(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'contentAlign' => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__content', 'text-align:center' );
	}

	public function test_date_typography(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'dateTypography' => [ 'desktop' => [ 'textTransform' => 'uppercase', 'letterSpacing' => [ 'value' => '1', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__date', 'text-transform:uppercase' );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__date', 'letter-spacing:1px' );
	}

	public function test_title_typography(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'titleTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '20', 'unit' => 'px' ], 'fontWeight' => '700' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__title', 'font-size:20px' );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__title', 'font-weight:700' );
	}

	public function test_description_typography(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'descriptionTypography' => [ 'desktop' => [ 'lineHeight' => '1.6' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__description', 'line-height:1.6' );
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
		$this->assertCssHas( $css, '.flexa-timeline-a', 'padding:5px 5px 5px 5px' );
		$this->assertCssHas( $css, '.flexa-timeline-a', 'margin:15px 15px 15px 15px' );
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
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__card', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__card', 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__card', 'border-color:#cccccc' );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__card', 'border-radius:6px 6px 6px 6px' );
		$this->assertCssHasInDark( $css, '.flexa-timeline-a .flexa-timeline__card', 'border-color:#333333' );
	}

	public function test_image_width_per_device(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'imageWidth' => [
				'desktop' => [ 'value' => '60', 'unit' => '%' ],
				'tablet'  => [ 'value' => '80', 'unit' => '%' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__image', 'width:60%' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-timeline-a .flexa-timeline__image', 'width:80%' );
	}

	public function test_image_alignment_center_and_right(): void {
		$center = $this->gen( [ 'blockId' => 'a', 'imageAlign' => 'center' ] );
		$this->assertCssHas( $center, '.flexa-timeline-a .flexa-timeline__image', 'margin-left:auto' );
		$this->assertCssHas( $center, '.flexa-timeline-a .flexa-timeline__image', 'margin-right:auto' );

		$right = $this->gen( [ 'blockId' => 'a', 'imageAlign' => 'right' ] );
		$this->assertCssHas( $right, '.flexa-timeline-a .flexa-timeline__image', 'margin-left:auto' );
		$this->assertCssHas( $right, '.flexa-timeline-a .flexa-timeline__image', 'margin-right:0' );
	}

	public function test_image_alignment_left_emits_nothing(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'imageAlign' => 'left' ] );
		$this->assertStringNotContainsString( 'margin-left:auto', $css );
	}

	public function test_card_padding_on_card_responsive(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'cardPadding' => [
				'desktop' => [ 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px' ],
				'tablet'  => [ 'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8', 'unit' => 'px' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__card', 'padding:16px 16px 16px 16px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-timeline-a .flexa-timeline__card', 'padding:8px 8px 8px 8px' );
	}

	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '3' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-timeline-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-timeline-a', 'z-index:3' );
	}

	public function test_marker_background_and_icon_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'markerColor'     => [ 'light' => '#eef2ff', 'dark' => '#1e293b' ],
			'markerIconColor' => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__marker', 'background:#eef2ff' );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__marker', 'color:#2563eb' );
		$this->assertCssHasInDark( $css, '.flexa-timeline-a .flexa-timeline__marker', 'background:#1e293b' );
		$this->assertCssHasInDark( $css, '.flexa-timeline-a .flexa-timeline__marker', 'color:#93c5fd' );
	}

	public function test_connector_off_hides_line_and_emits_no_style(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'connectorShow'  => false,
			'connectorStyle' => 'dashed',
			'connectorWidth' => [ 'desktop' => [ 'value' => '3', 'unit' => 'px' ] ],
			'connectorColor' => [ 'light' => '#999999', 'dark' => '#555555' ],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__connector', 'display:none' );
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
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__connector', 'border-left-style:dashed' );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__connector', 'border-left-width:3px' );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__connector', 'border-left-color:#999999' );
		$this->assertCssHasInDark( $css, '.flexa-timeline-a .flexa-timeline__connector', 'border-left-color:#555555' );
		$this->assertStringNotContainsString( 'display:none', $css );
	}

	public function test_date_title_description_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'dateColor'        => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
			'titleColor'       => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
			'descriptionColor' => [ 'light' => '#444444', 'dark' => '#bbbbbb' ],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__date', 'color:#2563eb' );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__title', 'color:#111111' );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__description', 'color:#444444' );
		$this->assertCssHasInDark( $css, '.flexa-timeline-a .flexa-timeline__date', 'color:#93c5fd' );
		$this->assertCssHasInDark( $css, '.flexa-timeline-a .flexa-timeline__title', 'color:#eeeeee' );
		$this->assertCssHasInDark( $css, '.flexa-timeline-a .flexa-timeline__description', 'color:#bbbbbb' );
	}

	public function test_wrapper_background_and_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
			'boxShadow'  => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-timeline-a', 'background-color:#ffffff' );
		$this->assertCssHas( $css, '.flexa-timeline-a .flexa-timeline__card', 'box-shadow:0px 2px 8px 0px #000000' );
		$this->assertCssHasInDark( $css, '.flexa-timeline-a', 'background-color:#000000' );
		$this->assertCssHasInDark( $css, '.flexa-timeline-a .flexa-timeline__card', 'box-shadow:0px 2px 8px 0px #ffffff' );
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
		$this->assertCssHas( $css, '.flexa-timeline-a', 'background-size:cover' );
		$this->assertCssHas( $css, '.flexa-timeline-a.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	public function test_tablet_values_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'markerGap' => [ 'tablet' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-timeline-a .flexa-timeline__item', 'gap:10px' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'    => 'a',
			'titleColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );

		$this->assertCssHasInDark( $css, '.flexa-timeline-a .flexa-timeline__title', 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
