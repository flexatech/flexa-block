<?php
/**
 * Tests for the Instagram Feed block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional square thumbnails, colours and typography, and light/dark parity.
 * The grid columns and gap are structural (emitted when set); the item colours
 * and typography follow the theme-first rule (emitted only when the user picks a
 * value). The Graph API fetch itself is exercised by WordPress, not here — these
 * tests cover the generated CSS only, so they need no live token.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Instagram_Feed_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Instagram_Feed_CSS
 */
class InstagramFeedCssTest extends CssTestCase {

	private const OUTER   = '.flexa-instagram-feed-a';
	private const INNER   = '.flexa-instagram-feed-a > .flexa-instagram-feed__inner';
	private const GRID    = '.flexa-instagram-feed-a .flexa-instagram-feed__grid';
	private const ITEM    = '.flexa-instagram-feed-a .flexa-instagram-feed__item';
	private const IMAGE   = '.flexa-instagram-feed-a .flexa-instagram-feed__image';
	private const OVERLAY = '.flexa-instagram-feed-a .flexa-instagram-feed__overlay';
	private const CAPTION = '.flexa-instagram-feed-a .flexa-instagram-feed__caption';
	private const META    = '.flexa-instagram-feed-a .flexa-instagram-feed__meta';

	/**
	 * Convenience wrapper around the Instagram Feed generator.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Instagram_Feed_CSS::class, 'generate' ], $attrs );
	}

	// --- 1 & 2: guards / theme-first --------------------------------------

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// Theme-first: a bare block (no config passed) emits no declarations at all.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	// --- 3 & 4: grid columns + gap (structural) ---------------------------

	public function test_columns_emit_grid_template(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'columns' => [ 'desktop' => [ 'value' => '4', 'unit' => '' ] ],
		] );
		$this->assertCssHas( $css, self::GRID, 'grid-template-columns:repeat(4, minmax(0, 1fr))' );
	}

	public function test_columns_responsive_in_media_queries(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'columns' => [ 'tablet' => [ 'value' => '3', 'unit' => '' ], 'mobile' => [ 'value' => '2', 'unit' => '' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::GRID, 'grid-template-columns:repeat(3, minmax(0, 1fr))' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::GRID, 'grid-template-columns:repeat(2, minmax(0, 1fr))' );
	}

	public function test_item_gap_on_grid(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'itemGap' => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::GRID, 'gap:12px' );
	}

	public function test_item_gap_responsive_in_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'itemGap' => [ 'tablet' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::GRID, 'gap:8px' );
	}

	// --- 5: square thumbnails (gated, non-responsive) ---------------------

	public function test_square_thumbnail_emits_aspect_ratio_and_fit(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'squareThumbnail' => true ] );
		$this->assertCssHas( $css, self::IMAGE, 'aspect-ratio:1' );
		$this->assertCssHas( $css, self::IMAGE, 'object-fit:cover' );
	}

	public function test_square_thumbnail_absent_when_off(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'squareThumbnail' => false ] );
		$this->assertStringNotContainsString( 'aspect-ratio', $css );
	}

	// --- 6: width ---------------------------------------------------------

	public function test_boxed_width_is_max_width_on_inner(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'containerType' => 'boxed',
			'widthBoxed'    => [ 'desktop' => [ 'value' => '1100', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::INNER, 'max-width:1100px' );
	}

	public function test_full_width_is_width_on_inner(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'containerType'  => 'full-width',
			'widthFullWidth' => [ 'desktop' => [ 'value' => '90', 'unit' => '%' ] ],
		] );
		$this->assertCssHas( $css, self::INNER, 'width:90%' );
	}

	// --- 7: overlay / caption / meta colours ------------------------------

	public function test_overlay_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'overlayColor' => [ 'light' => 'rgba(0,0,0,0.5)', 'dark' => 'rgba(0,0,0,0.7)' ],
		] );
		$this->assertCssHas( $css, self::OVERLAY, 'background:rgba(0,0,0,0.5)' );
		$this->assertCssHasInDark( $css, self::OVERLAY, 'background:rgba(0,0,0,0.7)' );
	}

	public function test_caption_and_meta_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'captionColor' => [ 'light' => '#111827', 'dark' => '#f9fafb' ],
			'metaColor'    => [ 'light' => '#6b7280', 'dark' => '#9ca3af' ],
		] );
		$this->assertCssHas( $css, self::CAPTION, 'color:#111827' );
		$this->assertCssHas( $css, self::META, 'color:#6b7280' );
		$this->assertCssHasInDark( $css, self::CAPTION, 'color:#f9fafb' );
		$this->assertCssHasInDark( $css, self::META, 'color:#9ca3af' );
	}

	public function test_colours_unset_emit_nothing(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'captionColor' => [ 'light' => '', 'dark' => '' ] ] );
		$this->assertStringNotContainsString( 'color:', $css );
	}

	// --- 8: caption / meta typography (desktop + responsive) --------------

	public function test_caption_and_meta_typography(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'captionTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ], 'fontWeight' => '600' ] ],
			'metaTypography'    => [ 'desktop' => [ 'fontSize' => [ 'value' => '12', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, self::CAPTION, 'font-size:15px' );
		$this->assertCssHas( $css, self::CAPTION, 'font-weight:600' );
		$this->assertCssHas( $css, self::META, 'font-size:12px' );
	}

	public function test_caption_typography_tablet_in_media_query(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'captionTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '13', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::CAPTION, 'font-size:13px' );
	}

	// --- 9: card background ------------------------------------------------

	public function test_card_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'cardBackground' => [ 'light' => '#ffffff', 'dark' => '#1f2937' ],
		] );
		$this->assertCssHas( $css, self::ITEM, 'background:#ffffff' );
		$this->assertCssHasInDark( $css, self::ITEM, 'background:#1f2937' );
	}

	public function test_content_padding_and_gap_responsive(): void {
		// Content padding + gap apply to the __body / __overlay group → the group
		// ends on the __overlay selector (asserted here).
		$css = $this->gen( [
			'blockId'        => 'a',
			'contentPadding' => [
				'desktop' => [ 'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'unit' => 'px' ],
				'tablet'  => [ 'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8', 'unit' => 'px' ],
			],
			'contentGap'     => [ 'desktop' => [ 'value' => '6', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::OVERLAY, 'padding:12px 12px 12px 12px' );
		$this->assertCssHas( $css, self::OVERLAY, 'gap:6px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::OVERLAY, 'padding:8px 8px 8px 8px' );
	}

	public function test_content_padding_and_gap_absent_when_unset(): void {
		$css = $this->gen( [ 'blockId' => 'a' ] );
		$this->assertStringNotContainsString( '__body', $css );
	}

	// --- 10: border + box shadow on each item -----------------------------

	public function test_border_all_sub_properties_plus_dark_on_item(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#e5e7eb', 'dark' => '#374151' ],
					'radius' => [ 'topLeft' => '8', 'topRight' => '8', 'bottomRight' => '8', 'bottomLeft' => '8', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::ITEM, 'border-style:solid' );
		$this->assertCssHas( $css, self::ITEM, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::ITEM, 'border-color:#e5e7eb' );
		$this->assertCssHas( $css, self::ITEM, 'border-radius:8px 8px 8px 8px' );
		$this->assertCssHasInDark( $css, self::ITEM, 'border-color:#374151' );
	}

	public function test_box_shadow_light_and_dark_on_item(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '4', 'blur' => '12', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::ITEM, 'box-shadow:0px 4px 12px 0px #000000' );
		$this->assertCssHasInDark( $css, self::ITEM, 'box-shadow:0px 4px 12px 0px #ffffff' );
	}

	// --- 11: foundational spacing / advanced / background -----------------

	public function test_wrapper_spacing_padding_on_inner_margin_on_outer(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px' ],
					'margin'  => [ 'top' => '0', 'right' => 'auto', 'bottom' => '40', 'left' => 'auto', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::INNER, 'padding:20px 20px 20px 20px' );
		$this->assertCssHas( $css, self::OUTER, 'margin:0px auto 40px auto' );
	}

	public function test_advanced_layout_on_inner(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '2' ] ],
		] );
		$this->assertCssHas( $css, self::INNER, 'overflow:hidden' );
		$this->assertCssHas( $css, self::INNER, 'position:relative' );
		$this->assertCssHas( $css, self::INNER, 'z-index:2' );
	}

	public function test_wrapper_background_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#f8fafc', 'dark' => '#0f172a' ] ],
		] );
		$this->assertCssHas( $css, self::INNER, 'background-color:#f8fafc' );
		$this->assertCssHasInDark( $css, self::INNER, 'background-color:#0f172a' );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
	}

	public function test_wrapper_background_image_emits_url_once_eagerly(): void {
		// No view.js ships with this block, so the image url applies on the base
		// selector (not gated behind .flexa-bg-loaded) and appears exactly once.
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'  => 'image',
				'image' => [ 'url' => 'https://example.com/bg.jpg', 'position' => 'top left', 'size' => 'contain', 'repeat' => 'repeat-x', 'attachment' => 'fixed' ],
			],
		] );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
		$this->assertCssHas( $css, self::INNER, 'background-image:url(https://example.com/bg.jpg)' );
		$this->assertCssHas( $css, self::INNER, 'background-position:top left' );
		$this->assertCssHas( $css, self::INNER, 'background-size:contain' );
		$this->assertCssHas( $css, self::INNER, 'background-repeat:repeat-x' );
		$this->assertCssHas( $css, self::INNER, 'background-attachment:fixed' );
		$this->assertStringNotContainsString( 'flexa-bg-loaded', $css );
	}

	// --- 12: data-theme dark-mode branch ----------------------------------

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'      => 'a',
			'captionColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertCssHas( $css, self::CAPTION, 'color:#111111' );
		$this->assertCssHasInDark( $css, self::CAPTION, 'color:#eeeeee', true );
		$this->assertStringContainsString( '[data-theme="dark"] ' . self::CAPTION, $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
