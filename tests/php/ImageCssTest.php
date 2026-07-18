<?php
/**
 * Tests for the Image block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Image_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Image_CSS
 */
class ImageCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Image generator.
	 *
	 * @param array $attrs Image attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Image_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_frame_width_and_alignment(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'imageWidth' => [ 'desktop' => [ 'value' => '60', 'unit' => '%' ] ],
			'imageAlign' => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, '.flexa-image-a .flexa-image__frame', 'width:60%' );
		$this->assertCssHas( $css, '.flexa-image-a .flexa-image__frame', 'margin-left:auto' );
		$this->assertCssHas( $css, '.flexa-image-a .flexa-image__frame', 'margin-right:auto' );
	}

	public function test_object_fit_on_image(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'objectFit' => 'contain' ] );
		$this->assertCssHas( $css, '.flexa-image-a .flexa-image__img', 'object-fit:contain' );
	}

	public function test_aspect_ratio_only_when_enabled(): void {
		$off = $this->gen( [ 'blockId' => 'a', 'aspectRatio' => [ 'enabled' => false, 'ratio' => '16/9' ] ] );
		$this->assertStringNotContainsString( 'aspect-ratio', $off );

		$on = $this->gen( [ 'blockId' => 'a', 'aspectRatio' => [ 'enabled' => true, 'ratio' => '16/9' ] ] );
		$this->assertCssHas( $on, '.flexa-image-a .flexa-image__frame', 'aspect-ratio:16/9' );
	}

	public function test_border_targets_frame(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style' => 'solid',
					'width' => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
					'color' => [ 'light' => '#123456', 'dark' => '#abcdef' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-image-a .flexa-image__frame', 'border-color:#123456' );
		$this->assertStringContainsString( '#abcdef', $css );
	}

	public function test_overlay_colour_and_opacity(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'overlay' => [ 'type' => 'color', 'color' => [ 'light' => '#000000', 'dark' => '' ], 'opacity' => 0.4 ],
		] );
		$this->assertCssHas( $css, '.flexa-image-a .flexa-image__overlay', 'background-color:#000000' );
		$this->assertCssHas( $css, '.flexa-image-a .flexa-image__overlay', 'opacity:0.4' );
	}

	public function test_custom_mask_sets_variable(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'mask'    => [ 'shape' => 'custom', 'customImage' => [ 'url' => 'https://example.com/m.svg' ], 'size' => 'cover' ],
		] );
		$this->assertCssHas( $css, '.flexa-image-a .flexa-image__frame', '--flexa-img-mask:url(https://example.com/m.svg)' );
		$this->assertCssHas( $css, '.flexa-image-a .flexa-image__frame', '--flexa-img-mask-size:cover' );
	}

	public function test_caption_colour_and_typography(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'captionColor'      => [ 'light' => '#ffffff', 'dark' => '#111111' ],
			'captionTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-image-a .flexa-image__caption', 'color:#ffffff' );
		$this->assertCssHas( $css, '.flexa-image-a .flexa-image__caption', 'font-size:14px' );
		$this->assertStringContainsString( '#111111', $css );
	}

	public function test_caption_text_align_emitted_when_shown(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'caption' => [ 'show' => true, 'alignment' => 'right' ],
		] );
		$this->assertCssHas( $css, '.flexa-image-a .flexa-image__caption', 'text-align:right' );
	}

	public function test_caption_text_align_skipped_when_hidden(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'caption' => [ 'show' => false, 'alignment' => 'center' ],
		] );
		$this->assertStringNotContainsString( 'text-align', $css );
	}

	public function test_tablet_width_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'imageWidth' => [ 'tablet' => [ 'value' => '80', 'unit' => '%' ] ],
		] );
		$this->assertStringContainsString( '@media (max-width: 1024px)', $css );
	}

	public function test_lazy_background_gates_url_behind_loaded_class(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://example.com/a.jpg', 'size' => 'cover' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-image-a.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	public function test_box_shadow_rendered(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000', 'dark' => '' ] ],
		] );
		$this->assertStringContainsString( 'box-shadow:0px 2px 8px 0px #000', $css );
	}
}
