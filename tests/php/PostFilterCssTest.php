<?php
/**
 * Tests for the Post Filter block CSS generator.
 *
 * The bar's layout is structural (always emitted), while its colours, borders and
 * typography follow the theme-first rule — nothing is emitted unless the user picks
 * a value, so an untouched filter bar inherits the theme. Both halves are pinned
 * here, together with the light/dark parity and the responsive branches.
 *
 * Every label, control and button in the bar is styled from this one scope, so the
 * child selectors are asserted here too. A child CAN override its own box — see
 * FilterFieldCssTest — but that is the exception; this scope is the rule.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Post_Filter_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Post_Filter_CSS
 */
class PostFilterCssTest extends CssTestCase {

	private const ROOT   = '.flexa-post-filter-a';
	private const FIELDS = '.flexa-post-filter-a .flexa-post-filter__fields';
	private const LABEL  = '.flexa-post-filter-a .flexa-filter-field__label';

	// A pill is a control at rest, so the Control colours are emitted onto the input
	// and the pill together, under one grouped selector.
	private const CONTROL      = '.flexa-post-filter-a .flexa-filter-field__control, .flexa-post-filter-a .flexa-filter-field__options--pills .flexa-filter-field__option-text';
	private const CONTROL_ONLY = '.flexa-post-filter-a .flexa-filter-field__control';
	private const PILL         = '.flexa-post-filter-a .flexa-filter-field__options--pills .flexa-filter-field__option-text';
	private const PILL_CHECKED       = '.flexa-post-filter-a .flexa-filter-field__options--pills .flexa-filter-field__option-input:checked + .flexa-filter-field__option-text';
	private const PILL_HOVER = '.flexa-post-filter-a .flexa-filter-field__options--pills .flexa-filter-field__option:hover .flexa-filter-field__option-input:not(:checked) + .flexa-filter-field__option-text';
	// The submit button and the BUTTON variant of reset are styled as one group, so
	// the bar's two actions always match. The LINK variant is deliberately excluded —
	// a fill on it would turn a small "clear filters" link into a slab of accent.
	private const SUBMIT       = '.flexa-post-filter-a .flexa-post-filter__submit, .flexa-post-filter-a .flexa-filter-reset__control.wp-element-button';
	private const SUBMIT_HOVER = '.flexa-post-filter-a .flexa-post-filter__submit:hover, .flexa-post-filter-a .flexa-filter-reset__control.wp-element-button:hover';
	private const RESET_LINK   = '.flexa-post-filter-a .flexa-filter-reset__control--link';

	/**
	 * Convenience wrapper around the post-filter generator.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Post_Filter_CSS::class, 'generate' ], array_merge( [ 'blockId' => 'a' ], $attrs ) );
	}

	public function testEmitsNothingWithoutABlockId(): void {
		$css = $this->genCss( [ Post_Filter_CSS::class, 'generate' ], [ 'direction' => 'row' ] );
		$this->assertSame( '', $css );
	}

	public function testLayoutIsAlwaysEmitted(): void {
		$css = $this->gen( [] );

		$this->assertCssHas( $css, self::ROOT, 'display:flex' );
		$this->assertCssHas( $css, self::ROOT, 'flex-direction:row' );
	}

	public function testColumnDirectionAppliesToTheBarAndTheFields(): void {
		$css = $this->gen( [ 'direction' => 'column' ] );

		$this->assertCssHas( $css, self::ROOT, 'flex-direction:column' );
		$this->assertCssHas( $css, self::FIELDS, 'flex-direction:column' );
	}

	public function testAlignIsEmittedOnTheBarAndTheFields(): void {
		$css = $this->gen( [ 'align' => 'center' ] );

		$this->assertCssHas( $css, self::ROOT, 'align-items:center' );
		$this->assertCssHas( $css, self::FIELDS, 'align-items:center' );
	}

	public function testAnUnknownAlignValueIsDropped(): void {
		$css = $this->gen( [ 'align' => 'space-invaders' ] );
		$this->assertStringNotContainsString( 'align-items:space-invaders', $css );
	}

	public function testGapIsEmittedPerDevice(): void {
		$css = $this->gen(
			[
				'gap' => [
					'desktop' => [ 'value' => '12', 'unit' => 'px' ],
					'mobile'  => [ 'value' => '6', 'unit' => 'px' ],
				],
			]
		);

		$this->assertCssHas( $css, self::ROOT, 'gap:12px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::ROOT, 'gap:6px' );
	}

	public function testSpacingIsEmittedOnTheBar(): void {
		$css = $this->gen(
			[
				'spacing' => [
					'desktop' => [
						'padding' => [ 'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8', 'unit' => 'px' ],
						'margin'  => [ 'top' => '0', 'right' => '0', 'bottom' => '24', 'left' => '0', 'unit' => 'px' ],
					],
				],
			]
		);

		$this->assertCssHas( $css, self::ROOT, 'padding:8px 8px 8px 8px' );
		$this->assertCssHas( $css, self::ROOT, 'margin:0px 0px 24px 0px' );
	}

	public function testNoColoursAreEmittedWhenNoneArePicked(): void {
		// Theme-first: an untouched bar produces layout only.
		$css = $this->gen( [] );

		$this->assertStringNotContainsString( 'color:', $css );
		$this->assertStringNotContainsString( 'background', $css );
	}

	public function testLabelColourIsEmittedLightAndDark(): void {
		$css = $this->gen( [ 'labelColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ] ] );

		$this->assertCssHas( $css, self::LABEL, 'color:#111111' );
		$this->assertCssHasInDark( $css, self::LABEL, 'color:#eeeeee' );
	}

	public function testControlColoursAreEmittedLightAndDark(): void {
		$css = $this->gen(
			[
				'controlColor'            => [ 'light' => '#222222', 'dark' => '#dddddd' ],
				'controlBackground'       => [ 'light' => '#ffffff', 'dark' => '#000000' ],
				'controlPlaceholderColor' => [ 'light' => '#999999', 'dark' => '#777777' ],
			]
		);

		$this->assertCssHas( $css, self::CONTROL, 'color:#222222' );
		$this->assertCssHas( $css, self::CONTROL, 'background-color:#ffffff' );
		$this->assertCssHas( $css, self::CONTROL_ONLY . '::placeholder', 'color:#999999' );

		$this->assertCssHasInDark( $css, self::CONTROL, 'color:#dddddd' );
		$this->assertCssHasInDark( $css, self::CONTROL, 'background-color:#000000' );
	}

	public function testControlBorderIsEmittedWithADarkBorderColour(): void {
		$css = $this->gen(
			[
				'controlBorder' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#cccccc', 'dark' => '#444444' ],
					'radius' => [ 'topLeft' => '4', 'topRight' => '4', 'bottomRight' => '4', 'bottomLeft' => '4', 'unit' => 'px' ],
				],
			]
		);

		$this->assertCssHas( $css, self::CONTROL_ONLY, 'border-style:solid' );
		$this->assertCssHas( $css, self::CONTROL_ONLY, 'border-color:#cccccc' );
		$this->assertCssHas( $css, self::CONTROL_ONLY, 'border-radius:4px 4px 4px 4px' );
		$this->assertCssHasInDark( $css, self::CONTROL, 'border-color:#444444' );

		// A pill takes the border, but never its radius — a chip with a 4px radius is
		// a button, not a chip.
		$this->assertCssHas( $css, self::PILL, 'border-color:#cccccc' );
		$this->assertStringNotContainsString(
			'.flexa-filter-field__option-text{border-style:solid;border-width:1px 1px 1px 1px;border-color:#cccccc;border-radius',
			$css
		);
	}

	public function testControlPaddingIsEmitted(): void {
		$css = $this->gen(
			[ 'controlPadding' => [ 'top' => '8', 'right' => '12', 'bottom' => '8', 'left' => '12', 'unit' => 'px' ] ]
		);

		$this->assertCssHas( $css, self::CONTROL_ONLY, 'padding:8px 12px 8px 12px' );

		// The search box makes room for its magnifier instead of running the text under
		// it, and the icon sits at the author's own left padding.
		$this->assertCssHas(
			$css,
			'.flexa-post-filter-a .flexa-filter-field__search-wrap .flexa-filter-field__control',
			'padding-left:calc(12px + 26px)'
		);
		$this->assertCssHas( $css, '.flexa-post-filter-a .flexa-filter-field__search-icon', 'left:12px' );
	}

	public function testFocusDrawsOneLineNotTwo(): void {
		// The border carries the accent and a halo hugs it. Emitting an outline as well
		// puts a second frame outside the border — two visible lines around one box.
		$css = $this->gen( [ 'buttonBackground' => [ 'light' => '#0891b2', 'dark' => '' ] ] );

		$focus = self::CONTROL_ONLY . ':focus, ' . self::CONTROL_ONLY . ':focus-visible';

		$this->assertCssHas( $css, $focus, 'border-color:#0891b2' );
		$this->assertCssHas( $css, $focus, 'box-shadow:0 0 0 3px color-mix(in srgb, #0891b2 22%, transparent)' );
		$this->assertStringNotContainsString( 'outline-color', $css );
	}

	/** A named focus colour wins over the accent — border AND halo, light and dark. */
	public function testTheFocusColourCanBeSetOnItsOwn(): void {
		$css = $this->gen(
			[
				'buttonBackground'   => [ 'light' => '#0891b2', 'dark' => '#22d3ee' ],
				'controlBorderFocus' => [ 'light' => '#dc2626', 'dark' => '#f87171' ],
			]
		);

		$focus = self::CONTROL_ONLY . ':focus, ' . self::CONTROL_ONLY . ':focus-visible';

		$this->assertCssHas( $css, $focus, 'border-color:#dc2626' );
		$this->assertCssHas( $css, $focus, 'box-shadow:0 0 0 3px color-mix(in srgb, #dc2626 22%, transparent)' );
		$this->assertCssHasInDark( $css, $focus, 'border-color:#f87171' );
		$this->assertStringNotContainsString( '#0891b2 22%', $css );
	}

	/** Setting only the dark value must not silently drag the accent back into dark mode. */
	public function testADarkOnlyFocusColourStillTakesOver(): void {
		$css = $this->gen(
			[
				'buttonBackground'   => [ 'light' => '#0891b2', 'dark' => '#22d3ee' ],
				'controlBorderFocus' => [ 'light' => '', 'dark' => '#f87171' ],
			]
		);

		$focus = self::CONTROL_ONLY . ':focus, ' . self::CONTROL_ONLY . ':focus-visible';

		$this->assertCssHasInDark( $css, $focus, 'border-color:#f87171' );
		$this->assertStringNotContainsString( '#22d3ee 30%', $css );
	}

	public function testTheResetLinkTakesTheAccentAsInkNotAsAFill(): void {
		$css = $this->gen(
			[
				'buttonColor'      => [ 'light' => '#ffffff', 'dark' => '' ],
				'buttonBackground' => [ 'light' => '#0891b2', 'dark' => '' ],
			]
		);

		// The link gets the accent as its text colour...
		$this->assertCssHas( $css, self::RESET_LINK, 'color:#0891b2' );

		// ...and never as a background, which would paint a slab across the bar.
		$this->assertStringNotContainsString( '.flexa-filter-reset__control--link{color:#0891b2;background', $css );
	}

	public function testHoverPreviewsThePillsYouCanStillPick(): void {
		// Hover belongs to the UNSELECTED chips — the ones a click would change. The
		// chip that is already active has nothing to preview, so it must not react.
		$css = $this->gen( [ 'buttonBackgroundHover' => [ 'light' => '#0e7490', 'dark' => '' ] ] );

		$this->assertCssHas( $css, self::PILL_HOVER, 'background-color:#0e7490' );
		$this->assertStringNotContainsString( ':checked + .flexa-filter-field__option-text{background:#0e7490', $css );
	}

	public function testTheSelectedPillTakesTheButtonColours(): void {
		// A chosen chip and the submit button are the same accent — so an author sets
		// it once, in one panel, and the whole bar agrees.
		$css = $this->gen(
			[
				'buttonColor'      => [ 'light' => '#ffffff', 'dark' => '' ],
				'buttonBackground' => [ 'light' => '#0d6efd', 'dark' => '' ],
			]
		);

		$this->assertCssHas( $css, self::PILL_CHECKED, 'color:#ffffff' );
		$this->assertCssHas( $css, self::PILL_CHECKED, 'background-color:#0d6efd' );
	}

	public function testButtonColoursIncludeTheHoverState(): void {
		$css = $this->gen(
			[
				'buttonColor'           => [ 'light' => '#ffffff', 'dark' => '' ],
				'buttonBackground'      => [ 'light' => '#0d6efd', 'dark' => '' ],
				'buttonColorHover'      => [ 'light' => '#eeeeee', 'dark' => '' ],
				'buttonBackgroundHover' => [ 'light' => '#0b5ed7', 'dark' => '' ],
			]
		);

		$this->assertCssHas( $css, self::SUBMIT, 'color:#ffffff' );
		$this->assertCssHas( $css, self::SUBMIT, 'background-color:#0d6efd' );
		$this->assertCssHas( $css, self::SUBMIT_HOVER, 'color:#eeeeee' );
		$this->assertCssHas( $css, self::SUBMIT_HOVER, 'background-color:#0b5ed7' );
	}

	public function testButtonPaddingAndRadiusAreEmitted(): void {
		$css = $this->gen(
			[
				'buttonPadding' => [ 'top' => '10', 'right' => '18', 'bottom' => '10', 'left' => '18', 'unit' => 'px' ],
				'buttonRadius'  => [ 'value' => '6', 'unit' => 'px' ],
			]
		);

		$this->assertCssHas( $css, self::SUBMIT, 'padding:10px 18px 10px 18px' );
		$this->assertCssHas( $css, self::SUBMIT, 'border-radius:6px' );
	}

	public function testTypographyIsEmittedForDesktopAndMobile(): void {
		$css = $this->gen(
			[
				'labelTypography'   => [ 'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ] ] ],
				'controlTypography' => [
					'desktop' => [ 'fontSize' => [ 'value' => '16', 'unit' => 'px' ] ],
					'mobile'  => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ] ],
				],
			]
		);

		$this->assertCssHas( $css, self::LABEL, 'font-size:14px' );
		$this->assertCssHas( $css, self::CONTROL, 'font-size:16px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', self::CONTROL, 'font-size:14px' );
	}

	public function testWrapperBackgroundIsEmittedLightAndDark(): void {
		$css = $this->gen(
			[
				'background' => [
					'type'  => 'color',
					'color' => [ 'light' => '#f5f5f5', 'dark' => '#1a1a1a' ],
				],
			]
		);

		$this->assertCssHas( $css, self::ROOT, 'background-color:#f5f5f5' );
		$this->assertCssHasInDark( $css, self::ROOT, 'background-color:#1a1a1a' );
	}

	public function testBoxShadowIsEmittedOnlyWhenEnabled(): void {
		$off = $this->gen( [ 'boxShadow' => [ 'enabled' => false, 'blur' => '12', 'color' => [ 'light' => '#000000' ] ] ] );
		$this->assertStringNotContainsString( 'box-shadow', $off );

		$on = $this->gen(
			[
				'boxShadow' => [
					'enabled'    => true,
					'horizontal' => '0',
					'vertical'   => '4',
					'blur'       => '12',
					'spread'     => '0',
					'color'      => [ 'light' => '#000000', 'dark' => '' ],
				],
			]
		);
		$this->assertCssHas( $on, self::ROOT, 'box-shadow' );
	}

	public function testEverySelectorInAGroupGetsItsOwnDarkPrefix(): void {
		// Regression. The control and the pill share one grouped selector, and the
		// [data-theme] prefix used to be glued to the FRONT of the whole group:
		//   [data-theme="dark"] .control, .pill { background: #1e293b }
		// The pill, now unqualified, took its DARK background in LIGHT mode — a row of
		// navy chips on a white page.
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [ 'controlBackground' => [ 'light' => '#ffffff', 'dark' => '#1e293b' ] ] );

		// Both halves of the group carry the prefix...
		$dark_group = '[data-theme="dark"] ' . self::CONTROL_ONLY . ', [data-theme="dark"] ' . self::PILL;
		$this->assertCssHas( $css, $dark_group, 'background-color:#1e293b' );

		// ...and the pill never appears un-prefixed next to a prefixed control, which
		// is what leaked the dark background into light mode.
		$this->assertStringNotContainsString( '[data-theme="dark"] ' . self::CONTROL_ONLY . ', ' . self::PILL . '{', $css );
	}

	public function testDarkModeUsesTheDataThemeBranchWhenConfigured(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [ 'labelColor' => [ 'light' => '#111111', 'dark' => '#eeeeee' ] ] );

		$this->assertCssHasInDark( $css, self::LABEL, 'color:#eeeeee', true );
	}
}
