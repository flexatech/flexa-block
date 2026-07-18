<?php
/**
 * PHPUnit "init" file (a.k.a. bootstrap) for Flexa Block.
 *
 * Note: this is NOT the Bootstrap CSS framework — it is simply the file PHPUnit
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
 * Minimal WordPress stubs — only what the CSS core actually calls.
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
	 * Stub: mirror WordPress's sanitize_html_class — drop %-encoded octets, then
	 * keep only A-Z a-z 0-9 _ - (so a crafted blockId can't inject CSS/HTML).
	 *
	 * @param string $class    Raw class.
	 * @param string $fallback Value to return when the result is empty.
	 * @return string
	 */
	function sanitize_html_class( $class, $fallback = '' ) {
		$sanitized = preg_replace( '|%[a-fA-F0-9][a-fA-F0-9]|', '', (string) $class );
		$sanitized = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $sanitized );
		if ( '' === $sanitized && '' !== (string) $fallback ) {
			return (string) $fallback;
		}
		return $sanitized;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Stub: strip tags and trim, like WordPress does for plain text input.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function sanitize_text_field( $value ) {
		return trim( strip_tags( (string) $value ) );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	/**
	 * Stub: mirror WordPress's slug shape closely enough for the filter parser —
	 * lowercase, non-slug characters dropped, spaces/underscores to hyphens.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function sanitize_title( $value ) {
		$slug = strtolower( trim( (string) $value ) );
		$slug = preg_replace( '/[\s_]+/', '-', $slug );
		$slug = preg_replace( '/[^a-z0-9\-]/', '', (string) $slug );
		return trim( (string) preg_replace( '/-+/', '-', (string) $slug ), '-' );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Stub: translation is a pass-through — the tests assert the English source.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain (ignored).
	 * @return string
	 */
	function __( $text, $domain = 'default' ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
		unset( $domain );
		return (string) $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Stub: translate (pass-through) and HTML-escape.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain (ignored).
	 * @return string
	 */
	function esc_html__( $text, $domain = 'default' ) {
		unset( $domain );
		return htmlspecialchars( (string) $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * Stub: attribute escaping.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function esc_attr( $value ) {
		return htmlspecialchars( (string) $value, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Stub: HTML escaping.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function esc_html( $value ) {
		return htmlspecialchars( (string) $value, ENT_QUOTES );
	}
}

if ( ! function_exists( 'number_format_i18n' ) ) {
	/**
	 * Stub: locale-aware number formatting, without a locale.
	 *
	 * @param float $number   Number.
	 * @param int   $decimals Decimals.
	 * @return string
	 */
	function number_format_i18n( $number, $decimals = 0 ) {
		return number_format( (float) $number, (int) $decimals );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Stub: a non-negative integer, like WordPress's.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Stub: tests never send magic-quoted input, so pass the value through.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	function wp_unslash( $value ) {
		return $value;
	}
}

if ( ! class_exists( 'WP_Block_Type_Registry' ) ) {
	/**
	 * Stub: minimal block-type registry so CSS_Generator_Service::get_block_defaults
	 * can run without WordPress. Unregistered by default → empty defaults, which is
	 * enough to exercise the service's block-processing/error-isolation logic.
	 *
	 * A test that needs REAL defaults (because the code under test relies on
	 * block.json defaults being merged into saved attributes) primes the block it
	 * cares about with prime_from_block_json(). Priming is opt-in precisely so the
	 * existing generator tests keep seeing the empty-defaults behaviour they assert.
	 */
	class WP_Block_Type_Registry {
		/**
		 * Primed block types, keyed by block name.
		 *
		 * @var array<string, object>
		 */
		private static $primed = [];

		/**
		 * Register a block's real block.json attributes for the current test.
		 *
		 * @param string $name Block name, e.g. `flexa/filter-taxonomy`.
		 */
		public static function prime_from_block_json( string $name ): void {
			$slug = substr( $name, strlen( 'flexa/' ) );
			$json = json_decode( (string) file_get_contents( dirname( __DIR__ ) . '/src/blocks/' . $slug . '/block.json' ), true );

			self::$primed[ $name ] = (object) [ 'attributes' => $json['attributes'] ?? [] ];
		}

		/**
		 * Drop every primed type (call from tearDown so tests stay independent).
		 */
		public static function reset_primed(): void {
			self::$primed = [];
		}

		/**
		 * @return self
		 */
		public static function get_instance(): self {
			return new self();
		}

		/**
		 * @param string $name Block name.
		 * @return object|null
		 */
		public function get_registered( $name ) {
			return self::$primed[ $name ] ?? null;
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
require_once $flexa_inc . 'class-post-query.php';
require_once $flexa_inc . 'css-generators/class-container-css.php';
require_once $flexa_inc . 'css-generators/class-grid-css.php';
require_once $flexa_inc . 'css-generators/class-slides-css.php';
require_once $flexa_inc . 'css-generators/class-slide-css.php';
require_once $flexa_inc . 'css-generators/class-button-css.php';
require_once $flexa_inc . 'css-generators/class-heading-css.php';
require_once $flexa_inc . 'css-generators/class-image-css.php';
require_once $flexa_inc . 'css-generators/class-before-after-css.php';
require_once $flexa_inc . 'css-generators/class-countdown-css.php';
require_once $flexa_inc . 'css-generators/class-faq-css.php';
require_once $flexa_inc . 'css-generators/class-social-icon-css.php';
require_once $flexa_inc . 'css-generators/class-social-share-css.php';
require_once $flexa_inc . 'css-generators/class-testimonial-css.php';
require_once $flexa_inc . 'css-generators/class-counter-css.php';
require_once $flexa_inc . 'css-generators/class-separator-css.php';
require_once $flexa_inc . 'css-generators/class-text-css.php';
require_once $flexa_inc . 'css-generators/class-info-box-css.php';
require_once $flexa_inc . 'css-generators/class-table-of-content-css.php';
require_once $flexa_inc . 'css-generators/class-comparison-table-css.php';
require_once $flexa_inc . 'css-generators/class-breadcrumb-css.php';
require_once $flexa_inc . 'css-generators/class-video-popup-css.php';
require_once $flexa_inc . 'css-generators/class-tabs-css.php';
require_once $flexa_inc . 'css-generators/class-steps-css.php';
require_once $flexa_inc . 'css-generators/class-google-map-css.php';
require_once $flexa_inc . 'css-generators/class-images-gallery-css.php';
require_once $flexa_inc . 'css-generators/class-promo-css-parts.php';
require_once $flexa_inc . 'css-generators/class-banner-css.php';
require_once $flexa_inc . 'css-generators/class-cta-css.php';
require_once $flexa_inc . 'css-generators/class-subscribe-form-css.php';
require_once $flexa_inc . 'css-generators/class-pricing-table-css.php';
require_once $flexa_inc . 'css-generators/class-team-member-css.php';
require_once $flexa_inc . 'css-generators/class-post-grid-css.php';
require_once $flexa_inc . 'css-generators/class-post-filter-css.php';
require_once $flexa_inc . 'css-generators/class-filter-field-css.php';
require_once $flexa_inc . 'css-generators/class-rss-css.php';
require_once $flexa_inc . 'css-generators/class-process-bar-css.php';
require_once $flexa_inc . 'css-generators/class-timeline-css.php';
require_once $flexa_inc . 'css-generators/class-icon-css.php';
require_once $flexa_inc . 'css-generators/class-icon-list-css.php';
require_once $flexa_inc . 'css-generators/class-star-rating-css.php';
require_once $flexa_inc . 'css-generators/class-modal-css.php';
require_once $flexa_inc . 'css-generators/class-lottie-css.php';
require_once $flexa_inc . 'css-generators/class-notice-css.php';
require_once $flexa_inc . 'css-generators/class-facebook-feed-css.php';
require_once $flexa_inc . 'css-generators/class-instagram-feed-css.php';
require_once $flexa_inc . 'css-generators/class-taxonomy-css.php';
require_once $flexa_inc . 'css-generators/class-data-table-css.php';
require_once $flexa_inc . 'css-generators/class-product-name-css.php';
require_once $flexa_inc . 'css-generators/class-product-price-css.php';
require_once $flexa_inc . 'css-generators/class-product-rating-css.php';
require_once $flexa_inc . 'css-generators/class-product-detail-css.php';
require_once $flexa_inc . 'css-generators/class-product-image-css.php';

// Shared base class for block CSS tests (kept outside the scanned suite dir).
require_once __DIR__ . '/CssTestCase.php';
