<?php
/**
 * Tests for the Star Rating block CSS generator.
 *
 * Mirrors the structure of ContainerCssTest: copy the generator + attributes,
 * reuse genCss()/assertCssHas()/assertCssHasInMedia()/assertCssHasInDark() from
 * CssTestCase. Every add_property() in the generator has at least one assert, and
 * theme-first behaviour (nothing emitted for an untouched rating — including the
 * rating/maxRating defaults) is pinned down.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Star_Rating_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Star_Rating_CSS
 */
class StarRatingCssTest extends CssTestCase {

	private const WRAP  = '.flexa-star-rating-a';
	private const STARS = '.flexa-star-rating-a .flexa-star-rating__stars';
	private const STAR  = '.flexa-star-rating-a .flexa-star-rating__star';
	private const BASE  = '.flexa-star-rating-a .flexa-star-rating__star > svg';
	private const FILL  = '.flexa-star-rating-a .flexa-star-rating__star-fill';
	private const TITLE = '.flexa-star-rating-a .flexa-star-rating__title';

	/**
	 * Convenience wrapper around the Star Rating generator.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Star_Rating_CSS::class, 'generate' ], $attrs );
	}

	// 1. Empty block id → nothing.
	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	// 2. Theme-first: an untouched rating (only the rating/maxRating defaults) must
	// emit no declaration at all — in particular no marked/unmarked colour.
	public function test_untouched_block_emits_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'rating' => 4, 'maxRating' => 5 ] ) );
	}

	// 3. Alignment — inline layout aligns via justify-content (+ text-align).
	public function test_alignment_inline_uses_justify_content(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'ratingLayout' => 'inline',
			'alignment'    => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'justify-content:center' );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
		$this->assertStringNotContainsString( 'align-items:', $css );
	}

	// 3b. Alignment — stacked layout aligns via align-items (+ text-align).
	public function test_alignment_stacked_uses_align_items(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'ratingLayout' => 'stacked',
			'alignment'    => [ 'desktop' => 'right' ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'align-items:flex-end' );
		$this->assertCssHas( $css, self::WRAP, 'text-align:right' );
		$this->assertStringNotContainsString( 'justify-content:', $css );
	}

	// 3c. Star size targets the star box (both width + height).
	public function test_star_size(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'starSize' => [ 'desktop' => [ 'value' => '28', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::STAR, 'width:28px' );
		$this->assertCssHas( $css, self::STAR, 'height:28px' );
	}

	// 3d. Star gap targets the stars row.
	public function test_star_gap(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'gap'     => [ 'desktop' => [ 'value' => '6', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::STARS, 'gap:6px' );
	}

	// 3e. Title gap targets the wrapper flex.
	public function test_title_gap(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'titleGap' => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'gap:12px' );
	}

	// 4 + 9. Responsive: star size + gap land INSIDE the tablet media query.
	public function test_responsive_size_and_gap_in_media(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'starSize' => [ 'tablet' => [ 'value' => '20', 'unit' => 'px' ] ],
			'gap'      => [ 'tablet' => [ 'value' => '4', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::STAR, 'width:20px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::STARS, 'gap:4px' );
	}

	// 9. Title typography emits every sub-property on the title selector.
	public function test_title_typography_sub_properties(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'titleTypography' => [
				'desktop' => [
					'fontSize'      => [ 'value' => '18', 'unit' => 'px' ],
					'fontWeight'    => '700',
					'letterSpacing' => [ 'value' => '1', 'unit' => 'px' ],
					'textTransform' => 'uppercase',
					'lineHeight'    => '1.4',
				],
			],
		] );
		$this->assertCssHas( $css, self::TITLE, 'font-size:18px' );
		$this->assertCssHas( $css, self::TITLE, 'font-weight:700' );
		$this->assertCssHas( $css, self::TITLE, 'letter-spacing:1px' );
		$this->assertCssHas( $css, self::TITLE, 'text-transform:uppercase' );
		$this->assertCssHas( $css, self::TITLE, 'line-height:1.4' );
	}

	// 5. Marked (filled) colour — light on the fill overlay, dark full property:value.
	public function test_marked_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'color'   => [ 'light' => '#f5a623', 'dark' => '#ffd166' ],
		] );
		$this->assertCssHas( $css, self::FILL, 'color:#f5a623' );
		$this->assertCssHasInDark( $css, self::FILL, 'color:#ffd166' );
	}

	// 5b. Unmarked (empty) colour — light on the base svg only, dark full value.
	public function test_unmarked_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'unmarkedColor' => [ 'light' => '#cccccc', 'dark' => '#444444' ],
		] );
		$this->assertCssHas( $css, self::BASE, 'color:#cccccc' );
		$this->assertCssHasInDark( $css, self::BASE, 'color:#444444' );
	}

	// 5c. Title colour — light on the title, dark full value.
	public function test_title_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'titleColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, self::TITLE, 'color:#111111' );
		$this->assertCssHasInDark( $css, self::TITLE, 'color:#eeeeee' );
	}

	// 6. Gating: with no colours picked, neither the fill overlay nor the base-svg
	// nor the title selectors are emitted (marked/unmarked/title colour off).
	public function test_colors_gated_when_unset(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'starSize' => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
		] );
		$this->assertStringNotContainsString( '__star-fill', $css );
		$this->assertStringNotContainsString( '> svg', $css );
		$this->assertStringNotContainsString( '__title', $css );
	}

	// 7. Foundational: wrapper padding + margin.
	public function test_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ],
					'margin'  => [ 'top' => '', 'right' => 'auto', 'bottom' => '', 'left' => 'auto', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:10px 10px 10px 10px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:0 auto 0 auto' );
	}

	// 7b. Foundational: advanced layout (overflow / position / z-index).
	public function test_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '3' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'overflow:hidden' );
		$this->assertCssHas( $css, self::WRAP, 'position:relative' );
		$this->assertCssHas( $css, self::WRAP, 'z-index:3' );
	}

	// 9. Border sub-properties (style / width / color / radius) + dark border-color.
	public function test_border_sub_properties_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
					'color'  => [ 'light' => '#dddddd', 'dark' => '#222222' ],
					'radius' => [ 'topLeft' => '4', 'topRight' => '4', 'bottomRight' => '4', 'bottomLeft' => '4', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'border-style:solid' );
		$this->assertCssHas( $css, self::WRAP, 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, self::WRAP, 'border-color:#dddddd' );
		$this->assertCssHas( $css, self::WRAP, 'border-radius:4px 4px 4px 4px' );
		$this->assertCssHasInDark( $css, self::WRAP, 'border-color:#222222' );
	}

	// 7c. Foundational: wrapper background colour — light at base, dark full value.
	public function test_background_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#ffffff' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#000000' );
	}

	// 9 + 7. Background image sub-properties + url emitted exactly once (non-lazy).
	public function test_background_image_sub_properties(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'  => 'image',
				'image' => [ 'url' => 'https://example.com/a.jpg', 'position' => 'center center', 'size' => 'cover', 'repeat' => 'no-repeat', 'attachment' => 'scroll' ],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-image:url(https://example.com/a.jpg)' );
		$this->assertCssHas( $css, self::WRAP, 'background-position:center center' );
		$this->assertCssHas( $css, self::WRAP, 'background-size:cover' );
		$this->assertCssHas( $css, self::WRAP, 'background-repeat:no-repeat' );
		$this->assertCssHas( $css, self::WRAP, 'background-attachment:scroll' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
		$this->assertStringNotContainsString( 'flexa-bg-loaded', $css );
	}

	// 7. Lazy background gates the url behind .flexa-bg-loaded (emitted once).
	public function test_lazy_background_gates_url(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://example.com/a.jpg', 'size' => 'cover' ],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-size:cover' );
		$this->assertCssHas( $css, self::WRAP . '.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	// 7. Box shadow on the wrapper — light + full dark string.
	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'box-shadow:0px 2px 8px 0px #000000' );
		$this->assertCssHasInDark( $css, self::WRAP, 'box-shadow:0px 2px 8px 0px #ffffff' );
	}

	// 8. Dark mode via the [data-theme="dark"] strategy (not prefers-color-scheme).
	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId' => 'a',
			'color'   => [ 'light' => '#f5a623', 'dark' => '#ffd166' ],
		] );

		$this->assertStringContainsString( '[data-theme="dark"] ' . self::FILL, $css );
		$this->assertCssHasInDark( $css, self::FILL, 'color:#ffd166', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}

	// 6. Gradient background — light at base, dark under the dark branch.
	public function test_gradient_background_light_and_dark(): void {
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
}
