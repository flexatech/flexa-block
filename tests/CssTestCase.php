<?php
/**
 * Shared base class for block CSS-generator tests.
 *
 * Every block reuses CSS_Builder + CSS_Helpers, so every block test can reuse
 * these two helpers. To test a new block, extend this class and call genCss()
 * with the block's generator + attributes, then assert with assertCssHas().
 *
 * @package Flexa\Block
 */

use PHPUnit\Framework\TestCase;
use Flexa\Block\CSS_Builder;
use Flexa\Block\Dark_Mode_Settings;

/**
 * Base test case providing a generator runner and a CSS assertion helper.
 */
abstract class CssTestCase extends TestCase {

	/**
	 * Reset the Dark_Mode_Settings cache after every test so dark-mode overrides
	 * in one test never leak into the next.
	 */
	protected function tearDown(): void {
		$this->setDarkMode( null );
		parent::tearDown();
	}

	/**
	 * Run a block CSS generator and return the rendered (minified) CSS string.
	 *
	 * @param callable $generator A callable like [ Container_CSS::class, 'generate' ].
	 * @param array    $attrs     Block attributes to feed the generator.
	 * @return string Minified CSS.
	 */
	protected function genCss( callable $generator, array $attrs ): string {
		$css = new CSS_Builder();
		$generator( $attrs, $css );
		return $css->get_output();
	}

	/**
	 * Force Dark_Mode_Settings into a given state (or null to fall back to defaults).
	 *
	 * Dark_Mode_Settings caches settings in a private static property; we set it via
	 * reflection so tests can exercise the prefers-color-scheme vs [data-theme] branches.
	 *
	 * @param array|null $settings e.g. [ 'enabled' => true, 'colorScheme' => false, 'dataTheme' => true ].
	 */
	protected function setDarkMode( ?array $settings ): void {
		$prop = new \ReflectionProperty( Dark_Mode_Settings::class, 'cache' );
		$prop->setAccessible( true );
		$prop->setValue( null, $settings );
	}

	/**
	 * Assert that the CSS contains a declaration inside a given selector block.
	 *
	 * Example: assertCssHas( $css, '.flexa-container-a', 'display:flex' )
	 *
	 * @param string $css      Full CSS string.
	 * @param string $selector CSS selector to look for.
	 * @param string $decl     Declaration substring, e.g. "display:flex".
	 */
	protected function assertCssHas( string $css, string $selector, string $decl ): void {
		$pattern = '/' . preg_quote( $selector, '/' ) . '\s*\{[^}]*' . preg_quote( $decl, '/' ) . '/';
		$this->assertMatchesRegularExpression(
			$pattern,
			$css,
			"Expected CSS to contain:\n  $selector { ... $decl ... }\nGot:\n  $css"
		);
	}
}
