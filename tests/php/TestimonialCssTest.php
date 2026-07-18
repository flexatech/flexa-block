<?php
/**
 * Tests for the Testimonial block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional bits (alignment, border), responsive values inside media queries
 * and light/dark parity.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Testimonial_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Testimonial_CSS
 */
class TestimonialCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Testimonial generator.
	 *
	 * @param array $attrs Testimonial attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Testimonial_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Theme-first: an untouched card (only a blockId, plus the JSON defaults for
		// background/boxShadow/border/alignment which are all "empty") emits no CSS.
		$css = $this->gen( [
			'blockId'    => 'a',
			'alignment'  => [ 'desktop' => '', 'tablet' => '', 'mobile' => '' ],
			'background' => [ 'type' => 'none' ],
			'boxShadow'  => [ 'enabled' => false ],
			'border'     => [ 'desktop' => [ 'style' => '' ] ],
		] );
		$this->assertSame( '', $css );
	}

	public function test_alignment_sets_text_align_and_items(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'text-align:center' );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'align-items:center' );
	}

	public function test_alignment_absent_when_empty(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => '' ],
		] );
		$this->assertStringNotContainsString( 'text-align:', $css );
		$this->assertStringNotContainsString( 'align-items:', $css );
	}

	public function test_rating_size_and_spacing(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'ratingSize'    => [ 'desktop' => [ 'value' => '22', 'unit' => 'px' ] ],
			'ratingSpacing' => [ 'desktop' => [ 'value' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__star', 'width:22px' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__star', 'height:22px' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__rating', 'margin-bottom:16px' );
	}

	public function test_title_typography_and_spacing(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'titleTypography' => [
				'desktop' => [
					'fontSize'      => [ 'value' => '20', 'unit' => 'px' ],
					'fontWeight'    => '700',
					'letterSpacing' => [ 'value' => '1', 'unit' => 'px' ],
					'textTransform' => 'uppercase',
				],
			],
			'titleSpacing'    => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__title', 'font-size:20px' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__title', 'font-weight:700' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__title', 'letter-spacing:1px' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__title', 'text-transform:uppercase' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__title', 'margin-bottom:12px' );
	}

	public function test_quote_typography_and_spacing(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'quoteTypography' => [ 'desktop' => [ 'lineHeight' => '1.6' ] ],
			'quoteSpacing'    => [ 'desktop' => [ 'value' => '18', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__quote', 'line-height:1.6' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__quote', 'margin-bottom:18px' );
	}

	public function test_author_avatar_gap_and_typography(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'authorImageSize' => [ 'desktop' => [ 'value' => '48', 'unit' => 'px' ] ],
			'authorGap'       => [ 'desktop' => [ 'value' => '10', 'unit' => 'px' ] ],
			'nameTypography'  => [ 'desktop' => [ 'fontWeight' => '600' ] ],
			'jobTypography'   => [ 'desktop' => [ 'fontSize' => [ 'value' => '12', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__avatar', 'width:48px' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__avatar', 'height:48px' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__author', 'gap:10px' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__name', 'font-weight:600' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__job', 'font-size:12px' );
	}

	public function test_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'ratingColor' => [ 'light' => '#f5a623', 'dark' => '#ffcc00' ],
			'titleColor'  => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
			'quoteColor'  => [ 'light' => '#333333', 'dark' => '#cccccc' ],
			'nameColor'   => [ 'light' => '#000000', 'dark' => '#ffffff' ],
			'jobColor'    => [ 'light' => '#777777', 'dark' => '#aaaaaa' ],
		] );
		// Light at the base.
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__rating', 'color:#f5a623' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__title', 'color:#111111' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__quote', 'color:#333333' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__name', 'color:#000000' );
		$this->assertCssHas( $css, '.flexa-testimonial-a .flexa-testimonial__job', 'color:#777777' );
		// Dark under the dark-mode branch — full property:value, never a bare hex.
		$this->assertCssHasInDark( $css, '.flexa-testimonial-a .flexa-testimonial__rating', 'color:#ffcc00' );
		$this->assertCssHasInDark( $css, '.flexa-testimonial-a .flexa-testimonial__title', 'color:#eeeeee' );
		$this->assertCssHasInDark( $css, '.flexa-testimonial-a .flexa-testimonial__quote', 'color:#cccccc' );
		$this->assertCssHasInDark( $css, '.flexa-testimonial-a .flexa-testimonial__name', 'color:#ffffff' );
		$this->assertCssHasInDark( $css, '.flexa-testimonial-a .flexa-testimonial__job', 'color:#aaaaaa' );
	}

	public function test_border_outlines_card_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#dddddd', 'dark' => '#444444' ],
					'radius' => [ 'topLeft' => '12', 'topRight' => '12', 'bottomRight' => '12', 'bottomLeft' => '12', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'border-color:#dddddd' );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'border-radius:12px 12px 12px 12px' );
		$this->assertCssHasInDark( $css, '.flexa-testimonial-a', 'border-color:#444444' );
	}

	public function test_border_absent_when_no_style(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [ 'desktop' => [ 'style' => '' ] ],
		] );
		$this->assertStringNotContainsString( 'border-style:', $css );
	}

	public function test_wrapper_spacing_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '24', 'right' => '16', 'bottom' => '24', 'left' => '16', 'unit' => 'px' ],
					'margin'  => [ 'top' => '32', 'right' => '0', 'bottom' => '32', 'left' => '0', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'padding:24px 16px 24px 16px' );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'margin:32px 0px 32px 0px' );
	}

	public function test_min_height_on_card(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'size'    => [ 'desktop' => [ 'minHeight' => [ 'value' => '320', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'min-height:320px' );
	}

	public function test_advanced_layout_on_card(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'z-index:5' );
	}

	public function test_wrapper_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'background-color:#ffffff' );
		// Full declaration in dark — "#000000" not a bare hex, so it can't match the light value.
		$this->assertCssHasInDark( $css, '.flexa-testimonial-a', 'background-color:#000000' );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '4', 'blur' => '12', 'spread' => '0', 'color' => [ 'light' => '#000', 'dark' => '#fff' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-testimonial-a', 'box-shadow:0px 4px 12px 0px #000' );
		$this->assertCssHasInDark( $css, '.flexa-testimonial-a', 'box-shadow:0px 4px 12px 0px #fff' );
	}

	public function test_lazy_background_image_prints_url_once_behind_loaded_class(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://example.com/bg.jpg', 'position' => 'center center', 'repeat' => 'no-repeat', 'size' => 'cover' ],
			],
		] );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
		$this->assertCssHas( $css, '.flexa-testimonial-a.flexa-bg-loaded', 'background-image:url(https://example.com/bg.jpg)' );
	}

	public function test_tablet_values_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'ratingSize' => [ 'tablet' => [ 'value' => '18', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-testimonial-a .flexa-testimonial__star', 'width:18px' );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'    => 'a',
			'titleColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHasInDark( $css, '.flexa-testimonial-a .flexa-testimonial__title', 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
