<?php
/**
 * Tests for the Icon block CSS generator.
 *
 * Mirrors the ContainerCssTest structure (CssTestCase base) and follows the
 * nine-point checklist: empty-blockId, theme-first untouched, scoped base
 * declarations, responsive-in-media, dark full property:value, gating on/off,
 * foundational attrs, the data-theme branch and helper sub-properties.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Icon_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Icon_CSS
 */
class IconCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Icon generator.
	 *
	 * @param array $attrs Icon attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Icon_CSS::class, 'generate' ], $attrs );
	}

	// --- 1 & 2: guards ------------------------------------------------------

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Only a blockId + the theme-first default shape — no user value.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a', 'shape' => 'square' ] ) );
	}

	// --- 3 & 4: glyph size (scoped + responsive) ----------------------------

	public function test_size_sets_width_and_height_on_glyph(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'iconSize' => [ 'desktop' => [ 'value' => '48', 'unit' => 'px' ] ] ] );
		$this->assertCssHas( $css, '.flexa-icon-a .flexa-icon', 'width:48px' );
		$this->assertCssHas( $css, '.flexa-icon-a .flexa-icon', 'height:48px' );
	}

	public function test_size_responsive_goes_into_media_query(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'iconSize' => [ 'tablet' => [ 'value' => '32', 'unit' => 'px' ] ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-icon-a .flexa-icon', 'width:32px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-icon-a .flexa-icon', 'height:32px' );
	}

	// --- 5: alignment (auto margins on the shrink-to-fit wrapper) -----------

	public function test_alignment_center_sets_auto_margins_on_wrapper(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, '.flexa-icon-a', 'margin-left:auto' );
		$this->assertCssHas( $css, '.flexa-icon-a', 'margin-right:auto' );
	}

	public function test_alignment_right_sets_left_auto_margin(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => 'right' ] ] );
		$this->assertCssHas( $css, '.flexa-icon-a', 'margin-left:auto' );
		$this->assertCssHas( $css, '.flexa-icon-a', 'margin-right:0' );
	}

	public function test_alignment_unset_emits_no_margins(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'desktop' => '' ] ] );
		$this->assertStringNotContainsString( 'margin-left', $css );
		$this->assertStringNotContainsString( 'text-align', $css );
	}

	public function test_alignment_responsive_goes_into_media_query(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'alignment' => [ 'mobile' => 'right' ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', '.flexa-icon-a', 'margin-left:auto' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', '.flexa-icon-a', 'margin-right:0' );
	}

	// --- 6 & 9: frame border width (sub-properties + gating) -----------------

	public function test_border_width_sets_style_and_width_on_inner(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'iconBorderWidth' => [ 'desktop' => [ 'value' => '2', 'unit' => 'px' ] ] ] );
		$this->assertCssHas( $css, '.flexa-icon-a .flexa-icon__inner', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-icon-a .flexa-icon__inner', 'border-width:2px' );
	}

	public function test_border_width_unset_emits_no_border(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'iconBorderWidth' => [ 'desktop' => [ 'value' => '', 'unit' => 'px' ] ] ] );
		$this->assertStringNotContainsString( 'border-width', $css );
		$this->assertStringNotContainsString( 'border-style', $css );
	}

	// --- 6: frame padding (responsive) --------------------------------------

	public function test_padding_on_inner(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'iconPadding' => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ] ] ] );
		$this->assertCssHas( $css, '.flexa-icon-a .flexa-icon__inner', 'padding:12px' );
	}

	public function test_padding_responsive_goes_into_media_query(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'iconPadding' => [ 'tablet' => [ 'value' => '8', 'unit' => 'px' ] ] ] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-icon-a .flexa-icon__inner', 'padding:8px' );
	}

	// --- 6: shape → border-radius (gating) ----------------------------------

	public function test_shape_rounded_emits_border_radius(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'shape' => 'rounded' ] );
		$this->assertCssHas( $css, '.flexa-icon-a .flexa-icon__inner', 'border-radius:8px' );
	}

	public function test_shape_circle_emits_border_radius(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'shape' => 'circle' ] );
		$this->assertCssHas( $css, '.flexa-icon-a .flexa-icon__inner', 'border-radius:50%' );
	}

	public function test_shape_square_emits_no_border_radius(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'shape' => 'square' ] );
		$this->assertStringNotContainsString( 'border-radius', $css );
	}

	// --- 3, 5, 9: icon colour (glyph) light + dark --------------------------

	public function test_icon_color_light_at_base_and_dark_in_media(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'iconColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ] ] );
		$this->assertCssHas( $css, '.flexa-icon-a .flexa-icon', 'color:#111111' );
		$this->assertCssHasInDark( $css, '.flexa-icon-a .flexa-icon', 'color:#eeeeee' );
	}

	public function test_icon_color_hover_light_and_dark(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'iconColorHover' => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ] ] );
		$this->assertCssHas( $css, '.flexa-icon-a:hover .flexa-icon', 'color:#2563eb' );
		$this->assertCssHasInDark( $css, '.flexa-icon-a:hover .flexa-icon', 'color:#93c5fd' );
	}

	public function test_icon_color_unset_emits_nothing(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'iconColor' => [ 'light' => '', 'dark' => '' ] ] );
		$this->assertSame( '', $css );
	}

	// --- frame background (inner) light + dark ------------------------------

	public function test_icon_background_light_and_dark(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'iconBackground' => [ 'light' => '#f3f4f6', 'dark' => '#1f2937' ] ] );
		$this->assertCssHas( $css, '.flexa-icon-a .flexa-icon__inner', 'background:#f3f4f6' );
		$this->assertCssHasInDark( $css, '.flexa-icon-a .flexa-icon__inner', 'background:#1f2937' );
	}

	public function test_icon_background_hover_light_and_dark(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'iconBackgroundHover' => [ 'light' => '#e5e7eb', 'dark' => '#374151' ] ] );
		$this->assertCssHas( $css, '.flexa-icon-a:hover .flexa-icon__inner', 'background:#e5e7eb' );
		$this->assertCssHasInDark( $css, '.flexa-icon-a:hover .flexa-icon__inner', 'background:#374151' );
	}

	// --- frame border colour (inner) light + dark ---------------------------

	public function test_icon_border_color_light_and_dark(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'iconBorderColor' => [ 'light' => '#cccccc', 'dark' => '#444444' ] ] );
		$this->assertCssHas( $css, '.flexa-icon-a .flexa-icon__inner', 'border-color:#cccccc' );
		$this->assertCssHasInDark( $css, '.flexa-icon-a .flexa-icon__inner', 'border-color:#444444' );
	}

	public function test_icon_border_color_hover_light_and_dark(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'iconBorderColorHover' => [ 'light' => '#2563eb', 'dark' => '#1e3a8a' ] ] );
		$this->assertCssHas( $css, '.flexa-icon-a:hover .flexa-icon__inner', 'border-color:#2563eb' );
		$this->assertCssHasInDark( $css, '.flexa-icon-a:hover .flexa-icon__inner', 'border-color:#1e3a8a' );
	}

	// --- 7: foundational — spacing padding + margin -------------------------

	public function test_foundational_spacing_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'desktop' => [
				'padding' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ],
				'margin'  => [ 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-a', 'padding:10px 10px 10px 10px' );
		$this->assertCssHas( $css, '.flexa-icon-a', 'margin:20px 20px 20px 20px' );
	}

	public function test_foundational_spacing_responsive_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'mobile' => [ 'padding' => [ 'top' => '5', 'right' => '5', 'bottom' => '5', 'left' => '5', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', '.flexa-icon-a', 'padding:5px 5px 5px 5px' );
	}

	// --- 7 & 9: foundational — border sub-properties + dark ------------------

	public function test_foundational_border_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [ 'desktop' => [
				'style'  => 'solid',
				'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
				'color'  => [ 'light' => '#cccccc', 'dark' => '#333333' ],
				'radius' => [ 'topLeft' => '4', 'topRight' => '4', 'bottomRight' => '4', 'bottomLeft' => '4', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-a', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-icon-a', 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, '.flexa-icon-a', 'border-color:#cccccc' );
		$this->assertCssHas( $css, '.flexa-icon-a', 'border-radius:4px 4px 4px 4px' );
		$this->assertCssHasInDark( $css, '.flexa-icon-a', 'border-color:#333333' );
	}

	// --- 7: foundational — advanced layout ----------------------------------

	public function test_foundational_advanced_layout(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-icon-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-icon-a', 'z-index:5' );
	}

	// --- 7 & 9: foundational — background (colour / gradient / image) --------

	public function test_foundational_background_color_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-a', 'background-color:#ffffff' );
		$this->assertCssHasInDark( $css, '.flexa-icon-a', 'background-color:#000000' );
	}

	public function test_foundational_gradient_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'gradient',
				'gradient' => [ 'light' => 'linear-gradient(0deg,#aaa,#bbb)', 'dark' => 'linear-gradient(0deg,#111,#222)' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-icon-a', 'background-image:linear-gradient(0deg,#aaa,#bbb)' );
		$this->assertCssHasInDark( $css, '.flexa-icon-a', 'background-image:linear-gradient(0deg,#111,#222)' );
	}

	public function test_non_lazy_background_image_emits_url_inline(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'image', 'image' => [ 'url' => 'https://example.com/a.jpg', 'size' => 'cover' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-a', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertStringNotContainsString( 'flexa-bg-loaded', $css );
	}

	public function test_background_image_is_eager_even_when_lazy_requested(): void {
		// The Icon block ships no view.js, so lazyLoad cannot gate the url behind a
		// `.flexa-bg-loaded` class (nothing would ever add it). The url is emitted
		// eagerly on the base selector regardless of the lazyLoad flag.
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'image',
				'lazyLoad' => true,
				'image'    => [ 'url' => 'https://example.com/a.jpg', 'size' => 'cover' ],
			],
		] );
		$this->assertCssHas( $css, '.flexa-icon-a', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertStringNotContainsString( 'flexa-bg-loaded', $css );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	// --- 7 & 9: foundational — box shadow light + dark ----------------------

	public function test_foundational_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-icon-a', 'box-shadow:0px 2px 8px 0px #000000' );
		$this->assertCssHasInDark( $css, '.flexa-icon-a', 'box-shadow:0px 2px 8px 0px #ffffff' );
	}

	// --- 8: data-theme dark-mode branch -------------------------------------

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [ 'blockId' => 'a', 'iconColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ] ] );

		$this->assertCssHas( $css, '.flexa-icon-a .flexa-icon', 'color:#111111' );
		$this->assertCssHasInDark( $css, '.flexa-icon-a .flexa-icon', 'color:#eeeeee', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
