<?php
/**
 * Tests for Global_Styles - the shared `:root` design-token stylesheet.
 *
 * @package Flexa\Block
 */

use Flexa\Block\Global_Styles;

/**
 * @covers \Flexa\Block\Global_Styles
 */
class GlobalStylesTest extends CssTestCase {

	public function test_root_contains_light_tokens(): void {
		$css = Global_Styles::css();
		$this->assertCssHas( $css, ':root', '--flexa-color-primary:#2563eb' );
		$this->assertCssHas( $css, ':root', '--flexa-space-md:16px' );
		$this->assertCssHas( $css, ':root', '--flexa-font-size-base:1rem' );
	}

	public function test_dark_tokens_emitted_via_prefers_color_scheme_by_default(): void {
		// Default dark settings = enabled + colorScheme (see Dark_Mode_Settings).
		$css = Global_Styles::css();
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $css );
		$this->assertStringContainsString( '--flexa-color-background:#0b0f19', $css );
	}

	public function test_dark_tokens_use_data_theme_when_configured(): void {
		$this->setDarkMode( [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ] );

		$css = Global_Styles::css();
		$this->assertCssHas( $css, '[data-theme="dark"]', '--flexa-color-background:#0b0f19' );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}

	public function test_no_dark_block_when_dark_mode_disabled(): void {
		$this->setDarkMode( [ 'enabled' => false, 'colorScheme' => false, 'dataTheme' => false ] );

		$css = Global_Styles::css();
		$this->assertStringContainsString( '--flexa-color-primary', $css );        // light tokens still present
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );      // but no dark overrides
		$this->assertStringNotContainsString( '[data-theme="dark"]', $css );
	}
}
