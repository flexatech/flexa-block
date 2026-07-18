<?php
/**
 * Tests for the Modal block CSS generator.
 *
 * Mirrors the ContainerCssTest structure and the guide's §3.1 nine-point
 * checklist: empty id, untouched-emits-nothing, scoped base asserts on the
 * trigger + box, responsive values inside media queries, full property:value
 * dark-mode asserts, gating for conditional attributes (trigger hover / close
 * position), the foundational spacing/border/shadow set and the data-theme
 * branch.
 *
 * @package Flexa\Block
 */

use Flexa\Block\CSS_Generators\Modal_CSS;

/**
 * @covers \Flexa\Block\CSS_Generators\Modal_CSS
 */
class ModalCssTest extends CssTestCase {

	/**
	 * Convenience wrapper around the Modal generator.
	 *
	 * @param array $attrs Modal attributes.
	 * @return string
	 */
	private function gen( array $attrs ): string {
		return $this->genCss( [ Modal_CSS::class, 'generate' ], $attrs );
	}

	// 1. Empty block id → no CSS at all.
	public function test_empty_block_id_outputs_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => '' ] ) );
	}

	// 2. Theme-first: an untouched block (only an id) emits no declarations.
	public function test_untouched_block_emits_nothing(): void {
		$this->assertSame( '', $this->gen( [ 'blockId' => 'a' ] ) );
	}

	// 3. Base declarations are scoped to the trigger and the box selectors.
	public function test_trigger_and_box_base_are_scoped(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'triggerTextColor'  => [ 'light' => '#111111', 'dark' => '' ],
			'triggerBackground' => [ 'light' => '#eeeeee', 'dark' => '' ],
			'triggerRadius'     => [ 'value' => '8', 'unit' => 'px' ],
			'triggerIconSize'   => [ 'value' => '40', 'unit' => 'px' ],
			'modalBackground'   => [ 'light' => '#ffffff', 'dark' => '' ],
			'modalRadius'       => [ 'value' => '12', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__trigger', 'color:#111111' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__trigger', 'background:#eeeeee' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__trigger', 'border-radius:8px' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__trigger', '--flexa-modal-icon-size:40px' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__box', 'background:#ffffff' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__box', 'border-radius:12px' );
	}

	// 4. Responsive: modalWidth / modalMaxHeight land INSIDE the media query.
	public function test_modal_width_and_max_height_are_responsive(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'modalWidth'     => [
				'desktop' => [ 'value' => '600', 'unit' => 'px' ],
				'tablet'  => [ 'value' => '90', 'unit' => '%' ],
			],
			'modalMaxHeight' => [
				'mobile' => [ 'value' => '80', 'unit' => 'vh' ],
			],
		] );
		// Desktop at the base.
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__box', 'width:600px' );
		// Tablet inside its media query.
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-modal-a .flexa-modal__box', 'width:90%' );
		// Mobile inside its media query.
		$this->assertCssHasInMedia( $css, '@media (max-width: 767px)', '.flexa-modal-a .flexa-modal__box', 'max-height:80vh' );
	}

	// 4b. Trigger / box padding are responsive too.
	public function test_padding_is_responsive(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'triggerPadding' => [ 'desktop' => [ 'top' => '12', 'right' => '20', 'bottom' => '12', 'left' => '20', 'unit' => 'px' ] ],
			'modalPadding'   => [ 'tablet' => [ 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px' ] ],
		] );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__trigger', 'padding:12px 20px 12px 20px' );
		$this->assertCssHasInMedia( $css, '@media (max-width: 1024px)', '.flexa-modal-a .flexa-modal__box', 'padding:16px 16px 16px 16px' );
	}

	// 5. Dark mode: light at the base, dark as a FULL property:value in the branch.
	public function test_modal_background_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'         => 'a',
			'modalBackground' => [ 'light' => '#ffffff', 'dark' => '#111827' ],
		] );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__box', 'background:#ffffff' );          // light at base
		$this->assertCssHasInDark( $css, '.flexa-modal-a .flexa-modal__box', 'background:#111827' );     // dark in branch
	}

	public function test_overlay_and_close_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'        => 'a',
			'overlayColor'   => [ 'light' => '#000000', 'dark' => '#0a0a0a' ],
			'closeIconColor' => [ 'light' => '#333333', 'dark' => '#eeeeee' ],
			'closeIconSize'  => [ 'value' => '28', 'unit' => 'px' ],
		] );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__overlay', 'background-color:#000000' );
		$this->assertCssHasInDark( $css, '.flexa-modal-a .flexa-modal__overlay', 'background-color:#0a0a0a' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__close', 'color:#333333' );
		$this->assertCssHasInDark( $css, '.flexa-modal-a .flexa-modal__close', 'color:#eeeeee' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__close', 'font-size:28px' );
	}

	// 6. Gating: trigger hover only emits when a hover colour is set.
	public function test_trigger_hover_gating(): void {
		$off = $this->gen( [ 'blockId' => 'a', 'triggerTextColor' => [ 'light' => '#111111', 'dark' => '' ] ] );
		$this->assertStringNotContainsString( '.flexa-modal__trigger:hover', $off );

		$on = $this->gen( [
			'blockId'                => 'a',
			'triggerTextColorHover'  => [ 'light' => '#2563eb', 'dark' => '' ],
			'triggerBackgroundHover' => [ 'light' => '#f0f0f0', 'dark' => '' ],
		] );
		$this->assertCssHas( $on, '.flexa-modal-a .flexa-modal__trigger:hover', 'color:#2563eb' );
		$this->assertCssHas( $on, '.flexa-modal-a .flexa-modal__trigger:hover', 'background:#f0f0f0' );
	}

	// 6b. Gating: close-position CSS only emits for the non-default "outside".
	public function test_close_position_gating(): void {
		$inside = $this->gen( [ 'blockId' => 'a', 'closePosition' => 'inside' ] );
		$this->assertStringNotContainsString( 'top:-44px', $inside );

		$outside = $this->gen( [ 'blockId' => 'a', 'closePosition' => 'outside' ] );
		$this->assertCssHas( $outside, '.flexa-modal-a .flexa-modal__close', 'top:-44px' );
		$this->assertCssHas( $outside, '.flexa-modal-a .flexa-modal__close', 'right:0' );
	}

	// 7. Foundational wrapper spacing (padding + margin) on the wrapper.
	public function test_wrapper_spacing(): void {
		$css = $this->gen( [
			'blockId' => 'a',
			'spacing' => [
				'desktop' => [
					'padding' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ],
					'margin'  => [ 'top' => '20', 'right' => '0', 'bottom' => '20', 'left' => '0', 'unit' => 'px' ],
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-modal-a', 'padding:10px 10px 10px 10px' );
		$this->assertCssHas( $css, '.flexa-modal-a', 'margin:20px 0px 20px 0px' );
	}

	// 7b. Trigger alignment maps to text-align on the wrapper.
	public function test_trigger_alignment(): void {
		$css = $this->gen( [ 'blockId' => 'a', 'triggerAlign' => [ 'desktop' => 'center' ] ] );
		$this->assertCssHas( $css, '.flexa-modal-a', 'text-align:center' );

		// Untouched alignment must not emit anything.
		$none = $this->gen( [ 'blockId' => 'a', 'triggerAlign' => [ 'desktop' => '' ] ] );
		$this->assertStringNotContainsString( 'text-align', $none );
	}

	// 8. Border sub-properties + box shadow (light + dark) on the modal box.
	public function test_box_border_and_shadow(): void {
		$css = $this->gen( [
			'blockId'   => 'a',
			'border'    => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => '#cccccc', 'dark' => '#444444' ],
					'radius' => [ 'topLeft' => '6', 'topRight' => '6', 'bottomRight' => '6', 'bottomLeft' => '6', 'unit' => 'px' ],
				],
			],
			'boxShadow' => [ 'enabled' => true, 'horizontal' => '0', 'vertical' => '10', 'blur' => '40', 'spread' => '0', 'color' => [ 'light' => '#000000', 'dark' => '#ffffff' ], 'inset' => false ],
		] );
		// Border sub-properties.
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__box', 'border-style:solid' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__box', 'border-width:1px 1px 1px 1px' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__box', 'border-color:#cccccc' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__box', 'border-radius:6px 6px 6px 6px' );
		$this->assertCssHasInDark( $css, '.flexa-modal-a .flexa-modal__box', 'border-color:#444444' );
		// Box shadow light + dark (full value).
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__box', 'box-shadow:0px 10px 40px 0px #000000' );
		$this->assertCssHasInDark( $css, '.flexa-modal-a .flexa-modal__box', 'box-shadow:0px 10px 40px 0px #ffffff' );
	}

	// 8b. Trigger colours light + dark (the trigger's own dark branch).
	public function test_trigger_colours_light_and_dark(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'triggerTextColor'  => [ 'light' => '#111111', 'dark' => '#f9fafb' ],
			'triggerBackground' => [ 'light' => '#2563eb', 'dark' => '#1e3a8a' ],
		] );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__trigger', 'color:#111111' );
		$this->assertCssHasInDark( $css, '.flexa-modal-a .flexa-modal__trigger', 'color:#f9fafb' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__trigger', 'background:#2563eb' );
		$this->assertCssHasInDark( $css, '.flexa-modal-a .flexa-modal__trigger', 'background:#1e3a8a' );
	}

	// 9. Data-theme dark-mode strategy → [data-theme="dark"] selectors, no media.
	public function test_data_theme_dark_mode_branch(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = $this->gen( [
			'blockId'         => 'a',
			'modalBackground' => [ 'light' => '#ffffff', 'dark' => '#111827' ],
		] );

		$this->assertStringContainsString( '[data-theme="dark"] .flexa-modal-a .flexa-modal__box', $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
		$this->assertCssHasInDark( $css, '.flexa-modal-a .flexa-modal__box', 'background:#111827', true );
	}

	// 9b. Typography sub-properties emit on the trigger.
	public function test_trigger_typography_sub_properties(): void {
		$css = $this->gen( [
			'blockId'           => 'a',
			'triggerTypography' => [
				'desktop' => [
					'fontSize'      => [ 'value' => '18', 'unit' => 'px' ],
					'fontWeight'    => '700',
					'letterSpacing' => [ 'value' => '1', 'unit' => 'px' ],
					'textTransform' => 'uppercase',
					'lineHeight'    => '1.4',
				],
			],
		] );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__trigger', 'font-size:18px' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__trigger', 'font-weight:700' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__trigger', 'letter-spacing:1px' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__trigger', 'text-transform:uppercase' );
		$this->assertCssHas( $css, '.flexa-modal-a .flexa-modal__trigger', 'line-height:1.4' );
	}
}
