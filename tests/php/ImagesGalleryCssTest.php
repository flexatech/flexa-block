<?php
/**
 * Tests for the Images Gallery block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Images_Gallery_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Images_Gallery_CSS
 */
class ImagesGalleryCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Images Gallery generator.
	 *
	 * @param array $attrs Gallery attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Images_Gallery_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// A bare gallery (default grid layout, no columns/gap/styles chosen) must
		// emit no declarations, so the theme's own styles win.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'galleryLayout' => 'grid' ] ) );
	}

	/* ---------------------------------------------------------------------
	 * Layout.
	 * ------------------------------------------------------------------ */

	public function test_grid_columns_and_gap(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'galleryLayout' => 'grid',
			'columns' => [ 'desktop' => [ 'value' => '4', 'unit' => '' ] ],
			'gap'     => [ 'desktop' => [ 'value' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__items', 'grid-template-columns:repeat(4, minmax(0, 1fr))' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__items', 'gap:16px' );
	}

	public function test_masonry_uses_column_count_and_item_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'galleryLayout' => 'masonry',
			'columns' => [ 'desktop' => [ 'value' => '3', 'unit' => '' ] ],
			'gap'     => [ 'desktop' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__items', 'column-count:3' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__items', 'column-gap:10px' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__item', 'margin-bottom:10px' );
		// Masonry must not emit the grid track definition.
		$this->assertStringNotContainsString( 'grid-template-columns', $css );
	}

	public function test_tiled_uses_row_height_and_gap(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'galleryLayout' => 'tiled',
			'gap'         => [ 'desktop' => [ 'value' => '8', 'unit' => 'px' ] ],
			'tiledHeight' => [ 'desktop' => [ 'value' => '200', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__items', 'gap:8px' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__item', 'height:200px' );
		$this->assertStringNotContainsString( 'column-count', $css );
		$this->assertStringNotContainsString( 'grid-template-columns', $css );
	}

	public function test_tablet_columns_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'galleryLayout' => 'grid',
			'columns' => [ 'tablet' => [ 'value' => '2', 'unit' => '' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-images-gallery-a .flexa-images-gallery__items', 'grid-template-columns:repeat(2, minmax(0, 1fr))' );
	}

	public function test_mobile_gap_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'galleryLayout' => 'grid',
			'gap'     => [ 'mobile' => [ 'value' => '4', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', '.flexa-images-gallery-a .flexa-images-gallery__items', 'gap:4px' );
	}

	/* ---------------------------------------------------------------------
	 * Per-image styling.
	 * ------------------------------------------------------------------ */

	public function test_image_radius_on_media(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'imageRadius' => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__media', 'border-radius:12px' );
	}

	public function test_aspect_ratio_only_in_grid_when_enabled(): void {
		$off = $this->gen( [ 'blockId' => 'a', 'galleryLayout' => 'grid', 'aspectRatio' => [ 'enabled' => false, 'ratio' => '4/3' ] ] );
		$this->assertStringNotContainsString( 'aspect-ratio', $off );

		// Enabled but not a grid → ignored.
		$tiled = $this->gen( [ 'blockId' => 'a', 'galleryLayout' => 'tiled', 'aspectRatio' => [ 'enabled' => true, 'ratio' => '4/3' ] ] );
		$this->assertStringNotContainsString( 'aspect-ratio', $tiled );

		$on = $this->gen( [ 'blockId' => 'a', 'galleryLayout' => 'grid', 'aspectRatio' => [ 'enabled' => true, 'ratio' => '4/3' ] ] );
		$this->assertCssHas( $on, '.flexa-images-gallery-a .flexa-images-gallery__media', 'aspect-ratio:4/3' );
		$this->assertCssHas( $on, '.flexa-images-gallery-a .flexa-images-gallery__image', 'height:100%' );
	}

	public function test_image_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'imageShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '6', 'blur' => '16', 'spread' => '0', 'color' => [ 'light' => '#111111', 'dark' => '#eeeeee' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__media', 'box-shadow:0px 6px 16px 0px #111111' );
		$this->assertCssHasInDark( $css, '.flexa-images-gallery-a .flexa-images-gallery__media', 'box-shadow:0px 6px 16px 0px #eeeeee' );
	}

	public function test_image_shadow_skipped_when_disabled(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'imageShadow' => [ 'enabled' => false, 'vertical' => '6', 'blur' => '16', 'color' => [ 'light' => '#111111' ] ],
		] );
		$this->assertStringNotContainsString( 'box-shadow', $css );
	}

	/* ---------------------------------------------------------------------
	 * Overlay.
	 * ------------------------------------------------------------------ */

	public function test_overlay_colour_opacity_hover_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'overlay' => [ 'type' => 'color', 'color' => [ 'light' => '#000000', 'dark' => '#222222' ], 'opacity' => 0.4, 'hoverOpacity' => 0.7 ],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__overlay', 'background-color:#000000' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__overlay', 'opacity:0.4' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__item:hover .flexa-images-gallery__overlay', 'opacity:0.7' );
		$this->assertCssHasInDark( $css, '.flexa-images-gallery-a .flexa-images-gallery__overlay', 'background-color:#222222' );
	}

	public function test_overlay_gradient(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'overlay' => [ 'type' => 'gradient', 'gradient' => [ 'light' => 'linear-gradient(0deg, #000, #fff)', 'dark' => '' ], 'opacity' => 0.5 ],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__overlay', 'background-image:linear-gradient(0deg, #000, #fff)' );
	}

	public function test_overlay_skipped_when_none(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'overlay' => [ 'type' => 'none', 'color' => [ 'light' => '#000000' ], 'opacity' => 0.4 ],
		] );
		$this->assertStringNotContainsString( '.flexa-images-gallery__overlay', $css );
	}

	/* ---------------------------------------------------------------------
	 * Caption.
	 * ------------------------------------------------------------------ */

	public function test_caption_colour_typography_and_dark(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'captionColor'      => [ 'light' => '#ffffff', 'dark' => '#101010' ],
			'captionBackground' => [ 'light' => '#333333', 'dark' => '#010101' ],
			'captionTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '13', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__caption', 'color:#ffffff' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__caption', 'background-color:#333333' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__caption', 'font-size:13px' );
		$this->assertCssHasInDark( $css, '.flexa-images-gallery-a .flexa-images-gallery__caption', 'color:#101010' );
		$this->assertCssHasInDark( $css, '.flexa-images-gallery-a .flexa-images-gallery__caption', 'background-color:#010101' );
	}

	public function test_caption_text_align_emitted_when_shown(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'caption' => [ 'show' => true, 'alignment' => 'left' ],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a .flexa-images-gallery__caption', 'text-align:left' );
	}

	public function test_caption_text_align_skipped_when_hidden(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'caption' => [ 'show' => false, 'alignment' => 'left' ],
		] );
		$this->assertStringNotContainsString( 'text-align', $css );
	}

	/* ---------------------------------------------------------------------
	 * Foundational (inherited from the Container family).
	 * ------------------------------------------------------------------ */

	public function test_wrapper_spacing(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ],
					'margin'  => [ 'top' => '20', 'right' => '0', 'bottom' => '20', 'left' => '0', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a', 'padding:10px 10px 10px 10px' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a', 'margin:20px 0px 20px 0px' );
	}

	public function test_wrapper_border_all_subproperties_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
					'color'  => [ 'light' => '#123456', 'dark' => '#abcdef' ],
					'radius' => [ 'topLeft' => '4', 'topRight' => '4', 'bottomRight' => '4', 'bottomLeft' => '4', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a', 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a', 'border-color:#123456' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a', 'border-radius:4px 4px 4px 4px' );
		$this->assertCssHasInDark( $css, '.flexa-images-gallery-a', 'border-color:#abcdef' );
	}

	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-images-gallery-a', 'z-index:5' );
	}

	public function test_wrapper_background_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#f0f0f0', 'dark' => '#0a0a0a' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a', 'background-color:#f0f0f0' );
		$this->assertCssHasInDark( $css, '.flexa-images-gallery-a', 'background-color:#0a0a0a' );
	}

	public function test_lazy_background_gates_url_behind_loaded_class(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://example.com/bg.jpg', 'size' => 'cover' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a.flexa-bg-loaded', 'background-image:url(https://example.com/bg.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	public function test_wrapper_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-images-gallery-a', 'box-shadow:0px 2px 8px 0px #000000' );
		$this->assertCssHasInDark( $css, '.flexa-images-gallery-a', 'box-shadow:0px 2px 8px 0px #ffffff' );
	}

	/* ---------------------------------------------------------------------
	 * data-theme dark-mode branch.
	 * ------------------------------------------------------------------ */

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );
		$css = $this->gen( [
			'blockId'      => 'a',
			'captionColor' => [ 'light' => '#ffffff', 'dark' => '#111111' ],
		] );
		$this->assertCssHasInDark( $css, '.flexa-images-gallery-a .flexa-images-gallery__caption', 'color:#111111', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
