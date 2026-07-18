<?php
/**
 * Tests for the Video Popup block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional bits (aspect ratio, scrim/backdrop opacity, alignment) and
 * light/dark parity.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Video_Popup_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Video_Popup_CSS
 */
class VideoPopupCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Video Popup generator.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Video_Popup_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Defaults only (16:9 ratio, scrim 30, backdrop 90, no alignment): the base
		// look lives in style.scss, so no per-instance CSS should appear.
		$this->assertSame(
			'',
			$this->gen( [
				'blockId'        => 'a',
				'aspectRatio'    => '16:9',
				'scrimOpacity'   => 30,
				'overlayOpacity' => 90,
			] )
		);
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
		$this->assertCssHas( $css, '.flexa-video-popup-a', 'padding:10px 10px 10px 10px' );
		$this->assertCssHas( $css, '.flexa-video-popup-a', 'margin:0px auto 0px auto' );
	}

	public function test_max_width_on_trigger(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'maxWidth' => [ 'desktop' => [ 'value' => '640', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-video-popup-a .flexa-video-popup__trigger', 'max-width:640px' );
	}

	public function test_tablet_max_width_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'maxWidth' => [ 'tablet' => [ 'value' => '90', 'unit' => '%' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-video-popup-a .flexa-video-popup__trigger', 'max-width:90%' );
	}

	public function test_alignment_maps_to_align_items(): void {
		$left = $this->gen( [ 'blockId' => 'a', 'align' => [ 'desktop' => 'left' ] ] );
		$this->assertCssHas( $left, '.flexa-video-popup-a', 'align-items:flex-start' );

		$right = $this->gen( [ 'blockId' => 'a', 'align' => [ 'desktop' => 'right' ] ] );
		$this->assertCssHas( $right, '.flexa-video-popup-a', 'align-items:flex-end' );

		// No alignment set → nothing emitted (the style.scss default centres it).
		$none = $this->gen( [ 'blockId' => 'a', 'align' => [ 'desktop' => '' ] ] );
		$this->assertStringNotContainsString( 'align-items', $none );
	}

	public function test_border_targets_trigger_all_sides(): void {
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
		$trigger = '.flexa-video-popup-a .flexa-video-popup__trigger';
		$this->assertCssHas( $css, $trigger, 'border-style:solid' );
		$this->assertCssHas( $css, $trigger, 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, $trigger, 'border-color:#123456' );
		$this->assertCssHas( $css, $trigger, 'border-radius:8px 8px 8px 8px' );
		// Dark border colour lands in the dark branch (full declaration).
		$this->assertCssHasInDark( $css, $trigger, 'border-color:#abcdef' );
	}

	public function test_advanced_layout_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-video-popup-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-video-popup-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-video-popup-a', 'z-index:5' );
	}

	public function test_aspect_ratio_only_when_not_default(): void {
		$def = $this->gen( [ 'blockId' => 'a', 'aspectRatio' => '16:9' ] );
		$this->assertStringNotContainsString( 'aspect-ratio', $def );

		$css = $this->gen( [ 'blockId' => 'a', 'aspectRatio' => '4:3' ] );
		$this->assertCssHas( $css, '.flexa-video-popup-a .flexa-video-popup__media', 'aspect-ratio:4/3' );

		$square = $this->gen( [ 'blockId' => 'a', 'aspectRatio' => '1:1' ] );
		$this->assertCssHas( $square, '.flexa-video-popup-a .flexa-video-popup__media', 'aspect-ratio:1/1' );
	}

	public function test_play_icon_size_custom_property(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'playIconSize' => [ 'value' => '80', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, '.flexa-video-popup-a', '--flexa-vp-icon-size:80px' );
	}

	public function test_play_icon_colour_and_background_light_and_dark(): void {
		$css  = $this->gen( [
			'blockId'            => 'a',
			'playIconColor'      => [ 'light' => '#ffffff', 'dark' => '#0a0a0a' ],
			'playIconBackground' => [ 'light' => '#ff0000', 'dark' => '#330000' ],
		] );
		$play = '.flexa-video-popup-a .flexa-video-popup__play';
		$this->assertCssHas( $css, $play, 'color:#ffffff' );
		$this->assertCssHas( $css, $play, 'background:#ff0000' );
		$this->assertCssHasInDark( $css, $play, 'color:#0a0a0a' );
		$this->assertCssHasInDark( $css, $play, 'background:#330000' );
	}

	public function test_scrim_opacity_only_when_not_default(): void {
		$def = $this->gen( [ 'blockId' => 'a', 'scrimOpacity' => 30 ] );
		$this->assertStringNotContainsString( '.flexa-video-popup__scrim', $def );

		$css = $this->gen( [ 'blockId' => 'a', 'scrimOpacity' => 60 ] );
		$this->assertCssHas( $css, '.flexa-video-popup-a .flexa-video-popup__scrim', 'opacity:0.6' );
	}

	public function test_backdrop_colour_light_and_dark(): void {
		$css      = $this->gen( [
			'blockId'      => 'a',
			'overlayColor' => [ 'light' => '#111111', 'dark' => '#000000' ],
		] );
		$backdrop = '.flexa-video-popup-a .flexa-video-popup__backdrop';
		$this->assertCssHas( $css, $backdrop, 'background-color:#111111' );
		$this->assertCssHasInDark( $css, $backdrop, 'background-color:#000000' );
	}

	public function test_backdrop_opacity_only_when_not_default(): void {
		$def = $this->gen( [ 'blockId' => 'a', 'overlayOpacity' => 90 ] );
		$this->assertStringNotContainsString( '.flexa-video-popup__backdrop', $def );

		$css = $this->gen( [ 'blockId' => 'a', 'overlayOpacity' => 50 ] );
		$this->assertCssHas( $css, '.flexa-video-popup-a .flexa-video-popup__backdrop', 'opacity:0.5' );
	}

	public function test_background_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#fafafa', 'dark' => '#1a1a1a' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-video-popup-a', 'background-color:#fafafa' );
		$this->assertCssHasInDark( $css, '.flexa-video-popup-a', 'background-color:#1a1a1a' );
	}

	public function test_background_image_sub_properties(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'  => 'image',
				'image' => [ 'url' => 'https://example.com/bg.jpg', 'position' => 'top left', 'size' => 'contain', 'repeat' => 'repeat-x', 'attachment' => 'fixed' ],
			],
		] );
		$wrap = '.flexa-video-popup-a';
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
		$this->assertCssHas( $css, '.flexa-video-popup-a.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$trigger = '.flexa-video-popup-a .flexa-video-popup__trigger';
		$this->assertCssHas( $css, $trigger, 'box-shadow:0px 2px 8px 0px #000000' );
		$this->assertCssHasInDark( $css, $trigger, 'box-shadow:0px 2px 8px 0px #ffffff' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );
		$css  = $this->gen( [ 'blockId' => 'a', 'playIconColor' => [ 'light' => '#ffffff', 'dark' => '#0a0a0a' ] ] );
		$play = '.flexa-video-popup-a .flexa-video-popup__play';
		$this->assertCssHasInDark( $css, $play, 'color:#0a0a0a', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
