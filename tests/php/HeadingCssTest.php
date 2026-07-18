<?php
/**
 * Tests for the Heading block CSS generator.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Heading_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Heading_CSS
 */
class HeadingCssTest extends CssTestCase {

	private const WRAP  = '.flexa-heading-a';
	private const TITLE = '.flexa-heading-a .flexa-heading__title';
	private const SUB   = '.flexa-heading-a .flexa-heading__subheading';
	private const SEP   = '.flexa-heading-a .flexa-heading__separator';

	/**
	 * Convenience wrapper around the Heading generator.
	 *
	 * @param array $attrs Heading attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Heading_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_untouched_heading_emits_nothing(): void {
		// Only a blockId: the theme should style everything, so no declarations.
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	public function test_text_color_targets_the_title(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'textColor' => [ 'light' => '#111827', 'dark' => '#f9fafb' ],
		] );
		$this->assertCssHas( $css, self::TITLE, 'color:#111827' );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
		$this->assertStringContainsString( '#f9fafb', $css );
	}

	public function test_gradient_text_only_when_type_is_gradient(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'textType'     => 'gradient',
			'textGradient' => [ 'light' => 'linear-gradient(#fff,#000)', 'dark' => 'linear-gradient(#000,#fff)' ],
			// A stray colour must be ignored while in gradient mode.
			'textColor'    => [ 'light' => '#ff0000' ],
		] );
		$this->assertCssHas( $css, self::TITLE, 'background-image:linear-gradient(#fff,#000)' );
		$this->assertCssHas( $css, self::TITLE, '-webkit-background-clip:text' );
		$this->assertCssHas( $css, self::TITLE, 'color:transparent' );
		$this->assertStringNotContainsString( '#ff0000', $css );
		// Dark gradient in the prefers-color-scheme branch.
		$this->assertStringContainsString( 'linear-gradient(#000,#fff)', $css );
	}

	public function test_color_mode_ignores_gradient(): void {
		$css = $this->gen( [
			'blockId'      => 'a',
			'textType'     => 'color',
			'textColor'    => [ 'light' => '#123456' ],
			'textGradient' => [ 'light' => 'linear-gradient(#fff,#000)' ],
		] );
		$this->assertCssHas( $css, self::TITLE, 'color:#123456' );
		$this->assertStringNotContainsString( 'background-clip', $css );
	}

	public function test_typography_targets_title_and_subheading(): void {
		$css = $this->gen( [
			'blockId'              => 'a',
			'typography'           => [ 'desktop' => [ 'fontSize' => [ 'value' => '32', 'unit' => 'px' ], 'fontWeight' => '700' ] ],
			'subheadingTypography' => [ 'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ] ] ],
		] );
		$this->assertCssHas( $css, self::TITLE, 'font-size:32px' );
		$this->assertCssHas( $css, self::TITLE, 'font-weight:700' );
		$this->assertCssHas( $css, self::SUB, 'font-size:14px' );
	}

	public function test_tablet_typography_in_media_query(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'typography' => [ 'tablet' => [ 'fontSize' => [ 'value' => '20', 'unit' => 'px' ] ] ],
		] );
		$this->assertStringContainsString( '@media (max-width: 1024px)', $css );
		$this->assertStringContainsString( 'font-size:20px', $css );
	}

	public function test_alignment_sets_text_align_and_separator_self(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'alignment' => [ 'desktop' => 'center' ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'text-align:center' );
		$this->assertCssHas( $css, self::SEP, 'align-self:center' );
	}

	public function test_gap_on_wrapper(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'gap'     => [ 'value' => '12', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'gap:12px' );
	}

	public function test_separator_geometry_and_spacing(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'separatorWidth'    => [ 'value' => '80', 'unit' => 'px' ],
			'separatorWeight'   => [ 'value' => '2', 'unit' => 'px' ],
			'separatorStyle'    => 'dashed',
			'separatorColor'    => [ 'light' => '#000000', 'dark' => '#ffffff' ],
			'separatorPosition' => 'top',
			'separatorSpacing'  => [ 'value' => '10', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, self::SEP, 'width:80px' );
		$this->assertCssHas( $css, self::SEP, 'border-top-width:2px' );
		$this->assertCssHas( $css, self::SEP, 'border-top-style:dashed' );
		$this->assertCssHas( $css, self::SEP, 'border-top-color:#000000' );
		// Position "top" → the spacing sits below the separator.
		$this->assertCssHas( $css, self::SEP, 'margin-bottom:10px' );
		$this->assertStringContainsString( '#ffffff', $css );
	}

	public function test_text_stroke(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'textStroke' => [ 'enabled' => true, 'width' => [ 'value' => '1', 'unit' => 'px' ], 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::TITLE, '-webkit-text-stroke-width:1px' );
		$this->assertCssHas( $css, self::TITLE, '-webkit-text-stroke-color:#000000' );
		$this->assertStringContainsString( '#ffffff', $css );
	}

	public function test_text_stroke_disabled_emits_nothing(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'textStroke' => [ 'enabled' => false, 'width' => [ 'value' => '1', 'unit' => 'px' ], 'color' => [ 'light' => '#000000' ] ],
		] );
		$this->assertStringNotContainsString( 'text-stroke', $css );
	}

	public function test_text_shadow(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'textShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '4', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ] ],
		] );
		$this->assertCssHas( $css, self::TITLE, 'text-shadow:0px 2px 4px #000000' );
		$this->assertStringContainsString( '#ffffff', $css );
	}

	public function test_blend_mode(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'blendMode' => 'multiply' ] );
		$this->assertCssHas( $css, self::TITLE, 'mix-blend-mode:multiply' );
	}

	public function test_hover_colour_on_hover_selector(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'textColorHover' => [ 'light' => '#ff0000', 'dark' => '#00ff00' ],
		] );
		$this->assertCssHas( $css, self::TITLE . ':hover', 'color:#ff0000' );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
		$this->assertStringContainsString( '#00ff00', $css );
	}

	public function test_subheading_colour(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'subheadingColor' => [ 'light' => '#555555', 'dark' => '#aaaaaa' ],
		] );
		$this->assertCssHas( $css, self::SUB, 'color:#555555' );
		$this->assertStringContainsString( '#aaaaaa', $css );
	}

	public function test_background_and_border_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'classic', 'color' => [ 'light' => '#eeeeee', 'dark' => '#111111' ] ],
			'border'     => [
				'desktop' => [
					'style' => 'solid',
					'width' => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color' => [ 'light' => '#cccccc' ],
				],
			],
		] );
		$this->assertCssHas( $css, self::WRAP, 'background-color:#eeeeee' );
		$this->assertCssHas( $css, self::WRAP, 'border-color:#cccccc' );
		$this->assertStringContainsString( '#111111', $css );
	}

	public function test_spacing_padding_and_margin_on_wrapper(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [ 'desktop' => [
				'padding' => [ 'top' => '10', 'right' => '20', 'bottom' => '10', 'left' => '20', 'unit' => 'px' ],
				'margin'  => [ 'top' => '0', 'right' => 'auto', 'bottom' => '0', 'left' => 'auto', 'unit' => 'px' ],
			] ],
		] );
		$this->assertCssHas( $css, self::WRAP, 'padding:10px 20px 10px 20px' );
		$this->assertCssHas( $css, self::WRAP, 'margin:0px auto 0px auto' );
	}
}
