<?php
/**
 * Tests for the Team Member block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Team_Member_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Team_Member_CSS
 */
class TeamMemberCssTest extends CssTestCase {

	private const WRAP     = '.flexa-team-member-a';
	private const IMAGE    = '.flexa-team-member-a .flexa-team-member__image';
	private const CONTENT  = '.flexa-team-member-a .flexa-team-member__content';
	private const NAME     = '.flexa-team-member-a .flexa-team-member__name';
	private const ROLE     = '.flexa-team-member-a .flexa-team-member__role';
	private const BIO      = '.flexa-team-member-a .flexa-team-member__bio';
	private const SOCIAL   = '.flexa-team-member-a .flexa-team-member__social';
	private const LINK     = '.flexa-team-member-a .flexa-team-member__social-link';
	private const LINK_HOV = '.flexa-team-member-a .flexa-team-member__social-link:hover';

	/**
	 * Convenience wrapper around the Team Member generator.
	 *
	 * @param array $attrs Team member attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Team_Member_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_block_emits_nothing(): void {
		// A block whose styling is all left at its (empty) defaults must inherit the
		// theme + the base style.scss — the generator emits no declarations.
		$this->assertSame( '', $this->gen( [
			'blockId'       => 'a',
			'imagePosition' => 'top',
			'imageShape'    => 'circle',
			'alignment'     => [ 'desktop' => '', 'tablet' => '', 'mobile' => '' ],
			'mediaGap'      => [ 'desktop' => [], 'tablet' => [], 'mobile' => [] ],
			'maxWidth'      => [ 'desktop' => [], 'tablet' => [], 'mobile' => [] ],
			'background'    => [ 'type' => 'none' ],
			'boxShadow'     => [ 'enabled' => false ],
		] ) );
	}

	public function test_alignment_photo_top_sets_wrap_and_content(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'imagePosition' => 'top',
			'alignment'     => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
		$this->assertCssHas( $css, self::WRAP, 'align-items:center' );
		$this->assertCssHas( $css, self::CONTENT, 'align-items:center' );
	}

	public function test_alignment_photo_left_skips_wrap_cross_axis(): void {
		$css = $this->gen( [
			'blockId'       => 'a',
			'imagePosition' => 'left',
			'alignment'     => [ 'desktop' => 'right' ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:right' );
		$this->assertCssHas( $css, self::CONTENT, 'align-items:flex-end' );
		// The row layout keeps the photo pinned to the top → no wrap align-items.
		$this->assertDoesNotMatchRegularExpression(
			'/' . preg_quote( self::WRAP, '/' ) . '\s*\{[^}]*align-items/',
			$css
		);
	}

	public function test_media_gap_and_max_width(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'mediaGap' => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ] ],
			'maxWidth' => [ 'desktop' => [ 'value' => '360', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'gap:24px' );
		$this->assertCssHas( $css, self::WRAP, 'max-width:360px' );
	}

	public function test_media_gap_tablet_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'mediaGap' => [ 'tablet' => [ 'value' => '12', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::WRAP, 'gap:12px' );
	}

	public function test_image_width(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'imageWidth' => [ 'desktop' => [ 'value' => '140', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::IMAGE, 'width:140px' );
	}

	public function test_image_width_mobile_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'imageWidth' => [ 'mobile' => [ 'value' => '80', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::IMAGE, 'width:80px' );
	}

	public function test_typography_on_text_parts(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'nameTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '22', 'unit' => 'px' ] ] ],
			'roleTypography' => [ 'desktop' => [ 'fontWeight' => '600' ] ],
			'bioTypography'  => [ 'desktop' => [ 'lineHeight' => '1.6' ] ],
		] );
		$this->assertCssHas( $css, self::NAME, 'font-size:22px' );
		$this->assertCssHas( $css, self::ROLE, 'font-weight:600' );
		$this->assertCssHas( $css, self::BIO, 'line-height:1.6' );
	}

	public function test_name_typography_tablet_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'nameTypography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '18', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::NAME, 'font-size:18px' );
	}

	public function test_element_bottom_spacing(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'nameSpacing' => [ 'desktop' => [ 'value' => '8', 'unit' => 'px' ] ],
			'roleSpacing' => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ] ],
			'bioSpacing'  => [ 'desktop' => [ 'value' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::NAME, 'margin-bottom:8px' );
		$this->assertCssHas( $css, self::ROLE, 'margin-bottom:12px' );
		$this->assertCssHas( $css, self::BIO, 'margin-bottom:16px' );
	}

	public function test_role_spacing_tablet_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId'     => 'a',
			'roleSpacing' => [ 'tablet' => [ 'value' => '6', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', self::ROLE, 'margin-bottom:6px' );
	}

	public function test_social_size_and_gap(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'socialSize' => [ 'desktop' => [ 'value' => '28', 'unit' => 'px' ] ],
			'socialGap'  => [ 'desktop' => [ 'value' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, self::LINK, 'font-size:28px' );
		$this->assertCssHas( $css, self::SOCIAL, 'gap:16px' );
	}

	public function test_social_gap_mobile_goes_into_media_query(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'socialGap' => [ 'mobile' => [ 'value' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::SOCIAL, 'gap:10px' );
	}

	public function test_wrapper_padding_and_margin(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'desktop' => [
				'padding' => [ 'top' => '20', 'right' => '20', 'bottom' => '20', 'left' => '20', 'unit' => 'px' ],
				'margin'  => [ 'top' => '10', 'right' => '0', 'bottom' => '10', 'left' => '0', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:20px 20px 20px 20px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:10px 0px 10px 0px' );
	}

	public function test_border_light_and_dark(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [ 'desktop' => [
				'style'  => 'solid',
				'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
				'color'  => [ 'light' => '#cccccc', 'dark' => '#333333' ],
				'radius' => [ 'topLeft' => '8', 'topRight' => '8', 'bottomRight' => '8', 'bottomLeft' => '8', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'border-style:solid' );
		$this->assertCssHas( $css, self::WRAP, 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, self::WRAP, 'border-color:#cccccc' );
		$this->assertCssHas( $css, self::WRAP, 'border-radius:8px 8px 8px 8px' );
		$this->assertCssHasInDark( $css, self::WRAP, 'border-color:#333333' );
	}

	public function test_text_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'nameColor' => [ 'light' => '#111827', 'dark' => '#f3f4f6' ],
			'roleColor' => [ 'light' => '#6b7280', 'dark' => '#9ca3af' ],
			'bioColor'  => [ 'light' => '#374151', 'dark' => '#d1d5db' ],
		] );
		$this->assertCssHas( $css, self::NAME, 'color:#111827' );
		$this->assertCssHasInDark( $css, self::NAME, 'color:#f3f4f6' );
		$this->assertCssHas( $css, self::ROLE, 'color:#6b7280' );
		$this->assertCssHasInDark( $css, self::ROLE, 'color:#9ca3af' );
		$this->assertCssHas( $css, self::BIO, 'color:#374151' );
		$this->assertCssHasInDark( $css, self::BIO, 'color:#d1d5db' );
	}

	public function test_social_colours_and_hover(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'socialColor'      => [ 'light' => '#374151', 'dark' => '#9ca3af' ],
			'socialHoverColor' => [ 'light' => '#2563eb', 'dark' => '#93c5fd' ],
		] );
		$this->assertCssHas( $css, self::LINK, 'color:#374151' );
		$this->assertCssHasInDark( $css, self::LINK, 'color:#9ca3af' );
		$this->assertCssHas( $css, self::LINK_HOV, 'color:#2563eb' );
		$this->assertCssHasInDark( $css, self::LINK_HOV, 'color:#93c5fd' );
	}

	public function test_background_colour_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#ffffff' );
		$this->assertCssHasInDark( $css, self::WRAP, 'background-color:#000000' );
	}

	public function test_background_none_ignored(): void {
		// Type 'none' must not emit a background even if a colour value lingers.
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'none', 'color' => [ 'light' => '#ff0000', 'dark' => '#00ff00' ] ],
		] );
		$this->assertStringNotContainsString( 'background-color', $css );
	}

	public function test_gradient_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [
				'type'     => 'gradient',
				'gradient' => [ 'light' => 'linear-gradient(0deg,#aaa,#bbb)', 'dark' => 'linear-gradient(0deg,#111,#222)' ],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-image:linear-gradient(0deg,#aaa,#bbb)' );
		$this->assertStringContainsString( 'linear-gradient(0deg,#111,#222)', $css );
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
		$this->assertCssHas( $css, self::WRAP, 'background-size:cover' );
		$this->assertCssHas( $css, self::WRAP . '.flexa-bg-loaded', 'background-image:url(https://example.com/a.jpg)' );
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
	}

	public function test_box_shadow_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'box-shadow:0px 2px 8px 0px #000000' );
		$this->assertCssHasInDark( $css, self::WRAP, 'box-shadow:0px 2px 8px 0px #ffffff' );
	}

	public function test_box_shadow_disabled_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'boxShadow' => [ 'enabled' => false, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000000' ] ],
		] );
		$this->assertStringNotContainsString( 'box-shadow', $css );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'   => 'a',
			'nameColor' => [ 'light' => '#111827', 'dark' => '#f3f4f6' ],
		] );

		$this->assertCssHasInDark( $css, self::NAME, 'color:#f3f4f6', true );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}
}
