<?php
/**
 * Tests for the Before / After block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional bits (split position, object-fit, aspect ratio, labels) and
 * light/dark parity.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Before_After_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Before_After_CSS
 */
class BeforeAfterCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Before / After generator.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Before_After_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Defaults only (position 50, object-fit cover, labels on but uncoloured):
		// the base look lives in style.scss, so no per-instance CSS should appear.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'objectFit' => 'cover', 'initialPosition' => 50, 'showLabels' => true ] ) );
	}

	public function test_spacing_padding_and_margin_on_wrapper(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ],
					'margin'  => [ 'top' => '0', 'right' => 'auto', 'bottom' => '0', 'left' => 'auto', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-before-after-a', 'padding:10px 10px 10px 10px' );
		$this->assertCssHas( $css, '.flexa-before-after-a', 'margin:0px auto 0px auto' );
	}

	public function test_max_width_on_frame(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'maxWidth' => [ 'desktop' => [ 'value' => '640', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-before-after-a .flexa-before-after__frame', 'max-width:640px' );
	}

	public function test_alignment_auto_margins_on_frame(): void {
		$left = $this->gen( [ 'blockId' => 'a', 'align' => [ 'desktop' => 'left' ] ] );
		$this->assertCssHas( $left, '.flexa-before-after-a .flexa-before-after__frame', 'margin-right:auto' );

		$center = $this->gen( [ 'blockId' => 'a', 'align' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $center, '.flexa-before-after-a .flexa-before-after__frame', 'margin-left:auto' );
		$this->assertCssHas( $center, '.flexa-before-after-a .flexa-before-after__frame', 'margin-right:auto' );
	}

	public function test_min_height_on_frame(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'size'    => [ 'desktop' => [ 'minHeight' => [ 'value' => '300', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-before-after-a .flexa-before-after__frame', 'min-height:300px' );
	}

	public function test_advanced_layout_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-before-after-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-before-after-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-before-after-a', 'z-index:5' );
	}

	public function test_border_targets_frame_all_sides(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
					'color'  => [ 'light' => '#123456', 'dark' => '#abcdef' ],
					'radius' => [ 'topLeft' => '8', 'topRight' => '8', 'bottomRight' => '8', 'bottomLeft' => '8', 'unit' => 'px' ],
				],
			],
		] );
		$frame = '.flexa-before-after-a .flexa-before-after__frame';
		$this->assertCssHas( $css, $frame, 'border-style:solid' );
		$this->assertCssHas( $css, $frame, 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, $frame, 'border-color:#123456' );
		$this->assertCssHas( $css, $frame, 'border-radius:8px 8px 8px 8px' );
		// Dark border colour lands in the dark branch (full declaration).
		$this->assertCssHasInDark( $css, $frame, 'border-color:#abcdef' );
	}

	public function test_split_position_only_when_not_default(): void {
		$def = $this->gen( [ 'blockId' => 'a', 'initialPosition' => 50 ] );
		$this->assertStringNotContainsString( '--flexa-ba-pos', $def );

		$css = $this->gen( [ 'blockId' => 'a', 'initialPosition' => 30 ] );
		$this->assertCssHas( $css, '.flexa-before-after-a', '--flexa-ba-pos:30%' );
	}

	public function test_handle_size_and_divider_width(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'handleSize'   => [ 'value' => '48', 'unit' => 'px' ],
			'dividerWidth' => [ 'value' => '5', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, '.flexa-before-after-a', '--flexa-ba-handle-size:48px' );
		$this->assertCssHas( $css, '.flexa-before-after-a', '--flexa-ba-line-width:5px' );
	}

	public function test_object_fit_only_when_not_cover(): void {
		$def = $this->gen( [ 'blockId' => 'a', 'objectFit' => 'cover' ] );
		$this->assertStringNotContainsString( 'object-fit', $def );

		$css = $this->gen( [ 'blockId' => 'a', 'objectFit' => 'contain' ] );
		$this->assertCssHas( $css, '.flexa-before-after-a .flexa-before-after__img', 'object-fit:contain' );
	}

	public function test_aspect_ratio_only_when_enabled(): void {
		$off = $this->gen( [ 'blockId' => 'a', 'aspectRatio' => [ 'enabled' => false, 'ratio' => '16/9' ] ] );
		$this->assertStringNotContainsString( 'aspect-ratio', $off );

		$on = $this->gen( [ 'blockId' => 'a', 'aspectRatio' => [ 'enabled' => true, 'ratio' => '4/3' ] ] );
		$this->assertCssHas( $on, '.flexa-before-after-a .flexa-before-after__frame', 'aspect-ratio:4/3' );
	}

	public function test_handle_colour_light_and_dark(): void {
		$css    = $this->gen( [ 'blockId' => 'a', 'handleColor' => [ 'light' => '#ffffff', 'dark' => '#0a0a0a' ] ] );
		$handle = '.flexa-before-after-a .flexa-before-after__handle';
		$this->assertCssHas( $css, $handle, 'color:#ffffff' );
		$this->assertCssHasInDark( $css, $handle, 'color:#0a0a0a' );
	}

	public function test_label_colours_light_and_dark_when_shown(): void {
		$css   = $this->gen( [
			'blockId'         => 'a',
			'showLabels'      => true,
			'labelColor'      => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
			'labelBackground' => [ 'light' => '#ff0000', 'dark' => '#330000' ],
		] );
		$label = '.flexa-before-after-a .flexa-before-after__label';
		$this->assertCssHas( $css, $label, 'color:#111111' );
		$this->assertCssHas( $css, $label, 'background:#ff0000' );
		$this->assertCssHasInDark( $css, $label, 'color:#eeeeee' );
		$this->assertCssHasInDark( $css, $label, 'background:#330000' );
	}

	public function test_label_colours_skipped_when_hidden(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'showLabels' => false,
			'labelColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertStringNotContainsString( '.flexa-before-after__label', $css );
	}

	public function test_background_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#fafafa', 'dark' => '#1a1a1a' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-before-after-a', 'background-color:#fafafa' );
		$this->assertCssHasInDark( $css, '.flexa-before-after-a', 'background-color:#1a1a1a' );
	}

	public function test_background_image_sub_properties(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'  => 'image',
				'image' => [ 'url' => 'https://example.com/bg.jpg', 'position' => 'top left', 'size' => 'contain', 'repeat' => 'repeat-x', 'attachment' => 'fixed' ],
			],
		] );
		$wrap = '.flexa-before-after-a';
		$this->assertCssHas( $css, $wrap, 'background-image:url(https://example.com/bg.jpg)' );
		$this->assertCssHas( $css, $wrap, 'background-position:top left' );
		$this->assertCssHas( $css, $wrap, 'background-size:contain' );
		$this->assertCssHas( $css, $wrap, 'background-repeat:repeat-x' );
		$this->assertCssHas( $css, $wrap, 'background-attachment:fixed' );
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
		$this->assertCssHas( $css, '.flexa-before-after-a.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$frame = '.flexa-before-after-a .flexa-before-after__frame';
		$this->assertCssHas( $css, $frame, 'box-shadow:0px 2px 8px 0px #000000' );
		$this->assertCssHasInDark( $css, $frame, 'box-shadow:0px 2px 8px 0px #ffffff' );
	}

	public function test_tablet_max_width_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'maxWidth' => [ 'tablet' => [ 'value' => '90', 'unit' => '%' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-before-after-a .flexa-before-after__frame', 'max-width:90%' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );
		$css    = $this->gen( [ 'blockId' => 'a', 'handleColor' => [ 'light' => '#ffffff', 'dark' => '#0a0a0a' ] ] );
		$handle = '.flexa-before-after-a .flexa-before-after__handle';
		$this->assertCssHasInDark( $css, $handle, 'color:#0a0a0a', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
