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
	 * Stub: accept an http(s)/mailto/tel URL, reject anything else.
	 *
	 * Closer to WordPress than a pass-through needs to be, because the slot
	 * validator reads '' as "that is not a web address" and a pass-through would
	 * make that branch untestable. Only the scheme is checked: that is the part
	 * the plugin's own rule depends on.
	 *
	 * @param string            $url       URL.
	 * @param array<int,string> $protocols Allowed schemes.
	 * @return string
	 */
	function esc_url_raw( $url, $protocols = [] ) {
		$url = trim( (string) $url );
		if ( '' === $url || ! $protocols ) {
			return $url;
		}

		foreach ( $protocols as $scheme ) {
			if ( 0 === stripos( $url, $scheme . ':' ) ) {
				return $url;
			}
		}

		return '';
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	/**
	 * Stub: like sanitize_text_field, but newlines survive.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function sanitize_textarea_field( $value ) {
		return flexa_test_sanitize_text( $value, true );
	}
}

if ( ! function_exists( 'is_email' ) ) {
	/**
	 * Stub: WordPress's shape check, near enough — something, an @, a dotted
	 * domain.
	 *
	 * @param string $email Address.
	 * @return string|false
	 */
	function is_email( $email ) {
		$email = (string) $email;
		return preg_match( '/^[^@\s]+@[^@\s.]+(\.[^@\s.]+)+$/', $email ) ? $email : false;
	}
}

if ( ! function_exists( 'wp_list_pluck' ) ) {
	/**
	 * Stub: collect one field from each row, keys preserved.
	 *
	 * @param array<string|int, array<string, mixed>> $list  Rows.
	 * @param string                                  $field Field to take.
	 * @return array<string|int, mixed>
	 */
	function wp_list_pluck( $list, $field ) {
		$out = [];
		foreach ( (array) $list as $key => $row ) {
			$out[ $key ] = is_array( $row ) ? ( $row[ $field ] ?? null ) : null;
		}
		return $out;
	}
}

/*
 * Attachment and capability stubs for the media slot type.
 *
 * Driven by two globals rather than a mocking library, which this suite does not
 * have: `$flexa_test_attachments` maps an id to whether it is an image, and
 * `$flexa_test_readable` lists the ids the current user may read. An id in
 * neither is a post that does not exist, which is its own test case.
 */
$GLOBALS['flexa_test_attachments'] = [];
$GLOBALS['flexa_test_readable']    = [];

if ( ! function_exists( 'get_post_type' ) ) {
	/**
	 * Stub: 'attachment' for a registered test id, false otherwise.
	 *
	 * @param int $post Post ID.
	 * @return string|false
	 */
	function get_post_type( $post = 0 ) {
		return isset( $GLOBALS['flexa_test_attachments'][ (int) $post ] ) ? 'attachment' : false;
	}
}

if ( ! function_exists( 'wp_attachment_is_image' ) ) {
	/**
	 * Stub: whatever the test registered for this id.
	 *
	 * @param int $post Attachment ID.
	 * @return bool
	 */
	function wp_attachment_is_image( $post = 0 ) {
		return ! empty( $GLOBALS['flexa_test_attachments'][ (int) $post ] );
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * Stub: only 'read_post' is asked about here, and only for the ids the test
	 * put in the readable list.
	 *
	 * @param string $capability Capability.
	 * @param mixed  ...$args    Capability arguments.
	 * @return bool
	 */
	function current_user_can( $capability, ...$args ) {
		if ( 'read_post' !== $capability ) {
			return false;
		}
		return in_array( (int) ( $args[0] ?? 0 ), $GLOBALS['flexa_test_readable'], true );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Stub: enough of WP_Error for the slot layer, which only ever sets one
	 * code, one message and a status.
	 */
	class WP_Error {

		/** @var string */
		private $code;

		/** @var string */
		private $message;

		/** @var array<string, mixed> */
		private $data;

		/**
		 * @param string               $code    Error code.
		 * @param string               $message Error message.
		 * @param array<string, mixed> $data    Error data.
		 */
		public function __construct( $code = '', $message = '', $data = [] ) {
			$this->code    = (string) $code;
			$this->message = (string) $message;
			$this->data    = is_array( $data ) ? $data : [];
		}

		/** @return string */
		public function get_error_code() {
			return $this->code;
		}

		/** @return string */
		public function get_error_message() {
			return $this->message;
		}

		/** @return array<string, mixed> */
		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Stub: is this a WP_Error.
	 *
	 * @param mixed $thing Value to check.
	 * @return bool
	 */
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
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

/**
 * Shared body for the two text sanitizers, close to WordPress on the two points
 * the plugin leans on.
 *
 * First, `<script>alert(1)</script>Hi` has to come out as `Hi`. `strip_tags()`
 * alone leaves `alert(1)Hi`, because it removes the tags and keeps what was
 * between them; WordPress drops the content of script and style outright. A stub
 * without that would have the slot layer looking like it leaks script text when
 * in production it does not.
 *
 * Second, runs of whitespace collapse to one space, and a single-line field
 * loses its newlines on the way. That is what makes a one-line slot one line.
 *
 * Not copied: WordPress also strips percent-encoded octets. Nothing here depends
 * on it, and a half-copy of an internal is worse than an honest omission.
 *
 * @param string $value         Raw value.
 * @param bool   $keep_newlines Whether newlines survive.
 * @return string
 */
function flexa_test_sanitize_text( $value, $keep_newlines = false ) {
	$value = (string) $value;

	if ( false !== strpos( $value, '<' ) ) {
		$value = (string) preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $value );
		$value = strip_tags( $value );
	}

	$value = (string) preg_replace( $keep_newlines ? '/[\t ]+/' : '/[\r\n\t ]+/', ' ', $value );

	return trim( $value );
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Stub: plain single-line text.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	function sanitize_text_field( $value ) {
		return flexa_test_sanitize_text( $value, false );
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
require_once $flexa_inc . 'class-item-style-migration.php';
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
require_once $flexa_inc . 'css-generators/class-product-description-css.php';
require_once $flexa_inc . 'css-generators/class-product-stock-css.php';
require_once $flexa_inc . 'css-generators/class-product-excerpt-css.php';
require_once $flexa_inc . 'css-generators/class-product-add-to-cart-css.php';
require_once $flexa_inc . 'class-woo-helpers.php';
require_once $flexa_inc . 'css-generators/class-product-field-css.php';
require_once $flexa_inc . 'css-generators/class-product-meta-css.php';
require_once $flexa_inc . 'css-generators/class-product-related-css.php';

/*
 * The import engine's slot layer. Only the one class: it is written so that
 * everything except markup substitution is decidable without WordPress, and
 * substitution is covered against a real install instead.
 */
require_once $flexa_inc . 'import/class-preset-slots.php';

// Shared base class for block CSS tests (kept outside the scanned suite dir).
require_once __DIR__ . '/CssTestCase.php';
