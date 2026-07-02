<?php
/**
 * PHPUnit "init" file (a.k.a. bootstrap) for Flexa Block.
 *
 * Note: this is NOT the Bootstrap CSS framework - it is simply the file PHPUnit
 * runs once *before* any test. Its only jobs:
 *   1. Define ABSPATH so the plugin's `if ( ! defined( 'ABSPATH' ) ) exit;`
 *      guards do not abort when we include the source files.
 *   2. Stub the few WordPress functions the CSS core touches, so the pure
 *      CSS-generation logic can be tested without a full WordPress install.
 *   3. Load the classes under test + the shared test base class.
 *
 * @package Flexa\Block
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

// Composer autoloader (PHPUnit). Present after `composer install`.
if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
	require dirname( __DIR__ ) . '/vendor/autoload.php';
}

/* -----------------------------------------------------------------------------
 * Minimal WordPress stubs - only what the CSS core actually calls.
 * -------------------------------------------------------------------------- */

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Stub: return the value unchanged (no filters in the test environment).
	 *
	 * @param string $hook  Hook name (ignored).
	 * @param mixed  $value Value to return.
	 * @return mixed
	 */
	function apply_filters( $hook, $value ) {
		return $value;
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Stub: pass the URL through untouched.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	function esc_url_raw( $url ) {
		return $url;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Stub: always return the supplied default, so Dark_Mode_Settings falls back
	 * to its built-in defaults (enabled + prefers-color-scheme).
	 *
	 * @param string $name    Option name (ignored).
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function get_option( $name, $default = false ) {
		return $default;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Stub: lowercase and keep only key-safe characters.
	 *
	 * @param string $key Raw key.
	 * @return string
	 */
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}

if ( ! function_exists( 'sanitize_html_class' ) ) {
	/**
	 * Stub: mirror WP core - strip percent-encoded octets, then anything that
	 * is not alphanumeric, underscore or dash.
	 *
	 * @param string $classname Raw class name.
	 * @param string $fallback  Fallback when nothing survives.
	 * @return string
	 */
	function sanitize_html_class( $classname, $fallback = '' ) {
		$sanitized = preg_replace( '/%[a-fA-F0-9][a-fA-F0-9]/', '', (string) $classname );
		$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', $sanitized );
		return ( '' === $sanitized && '' !== $fallback ) ? sanitize_html_class( $fallback ) : $sanitized;
	}
}

if ( ! class_exists( 'WP_Block_Type_Registry' ) ) {
	/**
	 * Stub: minimal block-type registry so CSS_Generator_Service::get_block_defaults
	 * can run without WordPress. Returns no registered type → empty defaults, which
	 * is enough to exercise the service's block-processing/error-isolation logic.
	 */
	class WP_Block_Type_Registry {
		/**
		 * @return self
		 */
		public static function get_instance(): self {
			return new self();
		}

		/**
		 * @param string $name Block name (ignored).
		 * @return null
		 */
		public function get_registered( $name ) {
			return null;
		}
	}
}

/* -----------------------------------------------------------------------------
 * Load the plugin core under test.
 * -------------------------------------------------------------------------- */

$flexa_inc = dirname( __DIR__ ) . '/includes/';
require_once $flexa_inc . 'class-css-builder.php';
require_once $flexa_inc . 'class-css-helpers.php';
require_once $flexa_inc . 'class-dark-mode-settings.php';
require_once $flexa_inc . 'class-global-styles.php';
require_once $flexa_inc . 'class-block-manager.php';
require_once $flexa_inc . 'class-css-generator-service.php';
require_once $flexa_inc . 'css-generators/class-container-css.php';
require_once $flexa_inc . 'css-generators/class-button-css.php';

// Shared base class for block CSS tests (kept outside the scanned suite dir).
require_once __DIR__ . '/CssTestCase.php';
