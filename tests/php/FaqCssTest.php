<?php
/**
 * Tests for the FAQ block CSS generator.
 *
 * One assertion per generator output: each add_property() the generator can emit
 * has a matching assertCssHas() on the right selector, plus on/off gating for the
 * conditional bits (divider, item border, legacy grid) and light/dark parity.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Faq_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Faq_CSS
 */
class FaqCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the FAQ generator.
	 *
	 * @param array $attrs FAQ attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Faq_CSS::class, 'generate' ], $attrs );
	}

	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	public function test_row_gap_on_list(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'rowGap'  => [ 'desktop' => [ 'value' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__list', 'row-gap:16px' );
	}

	public function test_legacy_grid_attributes_are_ignored(): void {
		// The grid layout was removed — content saved while it existed may still
		// carry these attributes, and they must not produce any CSS.
		$css = $this->gen( [
			'blockId'   => 'a',
			'faqLayout' => 'grid',
			'columns'   => [ 'desktop' => 3 ],
			'columnGap' => [ 'desktop' => [ 'value' => '20', 'unit' => 'px' ] ],
		] );
		$this->assertStringNotContainsString( 'grid-template-columns', $css );
		$this->assertStringNotContainsString( 'column-gap', $css );
	}

	public function test_max_width_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'maxWidth' => [ 'desktop' => [ 'value' => '800', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-faq-a', 'max-width:800px' );
	}

	public function test_icon_size_and_gap(): void {
		$css = $this->gen( [
			'blockId'  => 'a',
			'iconSize' => [ 'desktop' => [ 'value' => '18', 'unit' => 'px' ] ],
			'iconGap'  => [ 'desktop' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__icon', 'width:18px' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__icon', 'height:18px' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question', 'gap:8px' );
	}

	public function test_question_typography_and_padding(): void {
		$css = $this->gen( [
			'blockId'            => 'a',
			'questionTypography' => [
				'desktop' => [
					'fontSize'      => [ 'value' => '20', 'unit' => 'px' ],
					'fontWeight'    => '600',
					'letterSpacing' => [ 'value' => '2', 'unit' => 'px' ],
					'textTransform' => 'uppercase',
				],
			],
			'questionPadding'    => [ 'desktop' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question', 'font-size:20px' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question', 'font-weight:600' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question', 'letter-spacing:2px' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question', 'text-transform:uppercase' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question', 'padding:10px 10px 10px 10px' );
	}

	public function test_answer_typography_and_padding(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'answerTypography' => [ 'desktop' => [ 'lineHeight' => '1.6' ] ],
			'answerPadding'    => [ 'desktop' => [ 'top' => '0', 'right' => '0', 'bottom' => '12', 'left' => '0', 'unit' => 'px' ] ],
		] );
		// Typography + padding live on the padding-free-collapsing content box.
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__answer-content', 'line-height:1.6' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__answer-content', 'padding:0px 0px 12px 0px' );
	}

	public function test_question_colours_light_and_open_state(): void {
		$css = $this->gen( [
			'blockId'             => 'a',
			'questionColor'       => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
			'questionActiveColor' => [ 'light' => '#2563eb' ],
		] );
		// Text fills target the question-text span, not the whole button.
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question-text', 'color:#111111' );
		// The "open" colour also applies on hover (open item OR :hover question).
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question:hover .flexa-faq__question-text', 'color:#2563eb' );
		$this->assertStringContainsString( '.flexa-faq-a .flexa-faq__item[data-open] .flexa-faq__question-text', $css );
		// Dark value is emitted (dark-mode block).
		$this->assertStringContainsString( '#eeeeee', $css );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
	}

	public function test_question_text_gradient_uses_background_clip(): void {
		$css = $this->gen( [
			'blockId'               => 'a',
			'questionColorType'     => 'gradient',
			'questionColorGradient' => [ 'light' => 'linear-gradient(90deg,#f00,#00f)' ],
			'questionColor'         => [ 'light' => '#111111' ],
		] );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question-text', 'background-image:linear-gradient(90deg,#f00,#00f)' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question-text', 'background-clip:text' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question-text', '-webkit-text-fill-color:transparent' );
		// The solid colour must NOT be emitted when gradient is selected.
		$this->assertStringNotContainsString( 'color:#111111', $css );
	}

	public function test_question_background_gradient(): void {
		$css = $this->gen( [
			'blockId'                    => 'a',
			'questionBackgroundType'     => 'gradient',
			'questionBackgroundGradient' => [ 'light' => 'linear-gradient(0deg,#aaa,#bbb)' ],
		] );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question', 'background-image:linear-gradient(0deg,#aaa,#bbb)' );
	}

	public function test_answer_background_targets_answer_region(): void {
		$css = $this->gen( [
			'blockId'          => 'a',
			'answerBackground' => [ 'light' => '#f0f0f0' ],
		] );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__answer', 'background:#f0f0f0' );
	}

	public function test_question_backgrounds(): void {
		$css = $this->gen( [
			'blockId'                  => 'a',
			'questionBackground'       => [ 'light' => '#f5f5f5' ],
			'questionActiveBackground' => [ 'light' => '#e0e7ff' ],
		] );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question', 'background:#f5f5f5' );
		// Active background applies on hover as well as on an open item.
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question:hover', 'background:#e0e7ff' );
		$this->assertStringContainsString( '.flexa-faq-a .flexa-faq__item[data-open] .flexa-faq__question', $css );
	}

	public function test_icon_colours_light_and_open_state(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'iconColor'       => [ 'light' => '#888888' ],
			'iconActiveColor' => [ 'light' => '#000000' ],
		] );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__icon', 'color:#888888' );
		// Active icon colour applies on hover as well as on an open item.
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question:hover .flexa-faq__icon', 'color:#000000' );
		$this->assertStringContainsString( '.flexa-faq-a .flexa-faq__item[data-open] .flexa-faq__icon', $css );
	}

	public function test_item_background_and_answer_colour(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'itemBackground' => [ 'light' => '#fafafa' ],
			'answerColor'    => [ 'light' => '#333333' ],
		] );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__item', 'background:#fafafa' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__answer-content', 'color:#333333' );
	}

	public function test_border_outlines_item_and_divides_question_answer(): void {
		// One Border control draws five lines: the item's 4-side outline plus a
		// divider (bottom border) under the question.
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
					'color'  => [ 'light' => '#dddddd', 'dark' => '#444444' ],
					'radius' => [ 'topLeft' => '8', 'topRight' => '8', 'bottomRight' => '8', 'bottomLeft' => '8', 'unit' => 'px' ],
				],
			],
		] );
		// Item: 4-side outline + radius + clipping.
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__item', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__item', 'border-width:2px 2px 2px 2px' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__item', 'border-color:#dddddd' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__item', 'border-radius:8px 8px 8px 8px' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__item', 'overflow:hidden' );
		// Question: divider (bottom border only) mirroring the item border.
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question', 'border-bottom-style:solid' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question', 'border-bottom-width:2px' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__question', 'border-bottom-color:#dddddd' );
		// Dark colour emitted for the item + the divider.
		$this->assertStringContainsString( '#444444', $css );
		// The answer box gets no border at all.
		$this->assertStringNotContainsString( '.flexa-faq__answer-inner', $css );
	}

	public function test_border_absent_when_no_style(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'border'  => [ 'desktop' => [ 'style' => '' ] ],
		] );
		$this->assertStringNotContainsString( 'border-style:', $css );
	}

	public function test_tablet_values_go_into_media_query(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'rowGap'  => [ 'tablet' => [ 'value' => '8', 'unit' => 'px' ] ],
		] );
		$this->assertStringContainsString( '@media (max-width: 1024px)', $css );
	}

	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'       => 'a',
			'questionColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ],
		] );
		$this->assertStringContainsString( '[data-theme="dark"] .flexa-faq-a .flexa-faq__question', $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
		$this->assertStringContainsString( '#eeeeee', $css );
	}

	public function test_wrapper_background_and_item_shadow(): void {
		$css = $this->gen( [
			'blockId'    => 'a',
			'background' => [ 'type' => 'color', 'color' => [ 'light' => '#ffffff', 'dark' => '#000000' ] ],
			'boxShadow'  => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '2', 'blur' => '8', 'spread' => '0', 'color' => [ 'light' => '#000', 'dark' => '#fff' ] ],
		] );
		// Background is on the whole block; shadow wraps each item.
		$this->assertCssHas( $css, '.flexa-faq-a', 'background-color:#ffffff' );
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__item', 'box-shadow:0px 2px 8px 0px #000' );
		// The shadow is clipped to the bottom only (top/sides clipped, extend below).
		$this->assertCssHas( $css, '.flexa-faq-a .flexa-faq__item', 'clip-path:inset(0 0 -18px 0)' );
		// Dark branch: wrapper background + the full dark shadow value (asserting
		// just "#fff" would match the light "#ffffff" background and prove nothing).
		$this->assertStringContainsString( 'background-color:#000000', $css );
		$this->assertStringContainsString( 'box-shadow:0px 2px 8px 0px #fff', $css );
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
		$this->assertCssHas( $css, '.flexa-faq-a', 'padding:24px 16px 24px 16px' );
		$this->assertCssHas( $css, '.flexa-faq-a', 'margin:32px 0px 32px 0px' );
	}

	public function test_advanced_layout_on_wrapper(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'advancedLayout' => [ 'desktop' => [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '10' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-faq-a', 'overflow:hidden' );
		$this->assertCssHas( $css, '.flexa-faq-a', 'position:relative' );
		$this->assertCssHas( $css, '.flexa-faq-a', 'z-index:10' );
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
		// The URL must be printed exactly once, gated behind the loaded class, so
		// the browser only fetches it after view.js reveals the block.
		$this->assertSame( 1, substr_count( $css, 'background-image:url(' ) );
		$this->assertCssHas( $css, '.flexa-faq-a.flexa-bg-loaded', 'background-image:url(https://example.com/bg.jpg)' );
	}
}
