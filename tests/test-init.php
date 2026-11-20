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
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

// Composer autoloader (PHPUnit). Present after `composer install`.
if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
	require dirname( __DIR__ ) . '/vendor/autoload.php';
}

/* -----------------------------------------------------------------------------
 * Minimal WordPress stubs — only what the CSS core actually calls.
 * -------------------------------------------------------------------------- */

$GLOBALS['flexa_test_filters'] = [];

if ( ! function_exists( 'add_filter' ) ) {
	/** Minimal filter registration for unit tests. */
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['flexa_test_filters'][ $hook ][ (int) $priority ][] = [ $callback, (int) $accepted_args ];
		return true;
	}
}

if ( ! function_exists( 'remove_filter' ) ) {
	/** Minimal filter removal for unit tests. */
	function remove_filter( $hook, $callback, $priority = 10 ) {
		if ( empty( $GLOBALS['flexa_test_filters'][ $hook ][ (int) $priority ] ) ) {
			return false;
		}
		$removed = false;
		foreach ( $GLOBALS['flexa_test_filters'][ $hook ][ (int) $priority ] as $index => $entry ) {
			if ( $entry[0] === $callback ) {
				unset( $GLOBALS['flexa_test_filters'][ $hook ][ (int) $priority ][ $index ] );
				$removed = true;
			}
		}
		return $removed;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/** Apply callbacks registered by the test filter stubs. */
	function apply_filters( $hook, $value, ...$args ) {
		$priorities = $GLOBALS['flexa_test_filters'][ $hook ] ?? [];
		ksort( $priorities );
		foreach ( $priorities as $callbacks ) {
			foreach ( $callbacks as $entry ) {
				$all   = array_merge( [ $value ], $args );
				$value = call_user_func_array( $entry[0], array_slice( $all, 0, $entry[1] ) );
			}
		}
		return $value;
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	/** Minimal mutable post object used by revision-session tests. */
	class WP_Post {
		/** @var int */
		public $ID = 0;

		/** @var string */
		public $post_type = '';

		/** @var int */
		public $post_parent = 0;

		/** @var string */
		public $post_content = '';

		public function __construct( array $data = [] ) {
			foreach ( $data as $key => $value ) {
				$this->{$key} = $value;
			}
		}
	}
}

/* Revision-session state. Reset by InlineRevisionSessionTest. */
$GLOBALS['flexa_test_posts']             = [];
$GLOBALS['flexa_test_transients']        = [];
$GLOBALS['flexa_test_post_meta']         = [];
$GLOBALS['flexa_test_revision_checks']   = [];
$GLOBALS['flexa_test_revision_snapshots'] = [];
$GLOBALS['flexa_test_update_filters']    = [];
$GLOBALS['flexa_test_user_id']           = 1;
$GLOBALS['flexa_test_revisions_enabled'] = true;
$GLOBALS['flexa_test_update_error']      = null;

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return (int) $GLOBALS['flexa_test_user_id'];
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post_id ) {
		return $GLOBALS['flexa_test_posts'][ (int) $post_id ] ?? null;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		return $GLOBALS['flexa_test_transients'][ (string) $key ]['value'] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $expiration = 0 ) {
		$GLOBALS['flexa_test_transients'][ (string) $key ] = [
			'value'      => $value,
			'expiration' => (int) $expiration,
		];
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $key ) {
		unset( $GLOBALS['flexa_test_transients'][ (string) $key ] );
		return true;
	}
}

if ( ! function_exists( 'add_post_meta' ) ) {
	function add_post_meta( $post_id, $key, $value, $unique = false ) {
		$GLOBALS['flexa_test_post_meta'][ (int) $post_id ][ (string) $key ] = $value;
		return true;
	}
}

/**
 * Real unslashing for the meta stubs (the wp_unslash stub above is a no-op).
 *
 * @param mixed $value Value.
 * @return mixed
 */
function flexa_test_unslash( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'flexa_test_unslash', $value );
	}
	return is_string( $value ) ? stripslashes( $value ) : $value;
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return trim( strip_tags( (string) preg_replace( '@<(script|style)[^>]*?>.*?</\1>@si', '', (string) $text ) ) );
	}
}

if ( ! function_exists( 'get_post_mime_type' ) ) {
	function get_post_mime_type( $post = null ) {
		return $GLOBALS['flexa_test_mime'][ (int) $post ] ?? false;
	}
}

if ( ! function_exists( 'wp_get_attachment_url' ) ) {
	function wp_get_attachment_url( $id ) {
		return $GLOBALS['flexa_test_attachment_urls'][ (int) $id ] ?? false;
	}
}

if ( ! function_exists( 'wp_get_attachment_metadata' ) ) {
	function wp_get_attachment_metadata( $id ) {
		return $GLOBALS['flexa_test_attachment_meta'][ (int) $id ] ?? false;
	}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook ) {
		return $GLOBALS['flexa_test_cron'][ $hook ] ?? false;
	}
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( $timestamp, $recurrence, $hook ) {
		$GLOBALS['flexa_test_cron'][ $hook ] = $timestamp;
		return true;
	}
}

if ( ! function_exists( 'wp_unschedule_event' ) ) {
	function wp_unschedule_event( $timestamp, $hook ) {
		unset( $GLOBALS['flexa_test_cron'][ $hook ] );
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key = '', $single = false ) {
		return $GLOBALS['flexa_test_post_meta'][ (int) $post_id ][ (string) $key ] ?? '';
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	// Like core, the stored value is the unslashed form of what is passed.
	function update_post_meta( $post_id, $key, $value ) {
		$GLOBALS['flexa_test_post_meta'][ (int) $post_id ][ (string) $key ] = flexa_test_unslash( $value );
		return true;
	}
}

if ( ! function_exists( 'wp_slash' ) ) {
	function wp_slash( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'wp_slash', $value );
		}
		return is_string( $value ) ? addslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'add_metadata' ) ) {
	// Unlike add_post_meta(), stores against the exact ID given (no revision redirect).
	function add_metadata( $meta_type, $object_id, $key, $value, $unique = false ) {
		$GLOBALS['flexa_test_post_meta'][ (int) $object_id ][ (string) $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_save_post_revision' ) ) {
	function wp_save_post_revision( $post_id ) {
		if ( ! $GLOBALS['flexa_test_revisions_enabled'] ) {
			return null;
		}

		$post = get_post( (int) $post_id );
		if ( ! $post instanceof WP_Post ) {
			return null;
		}

		$latest = new WP_Post( [
			'ID'          => 0,
			'post_type'   => 'revision',
			'post_parent' => (int) $post_id,
		] );
		$GLOBALS['flexa_test_revision_checks'][] = apply_filters(
			'wp_save_post_revision_check_for_changes',
			true,
			$latest,
			$post
		);

		$revision_id = 9000 + count( $GLOBALS['flexa_test_revision_snapshots'] );
		$revision    = new WP_Post( [
			'ID'           => $revision_id,
			'post_type'    => 'revision',
			'post_parent'  => (int) $post_id,
			'post_content' => (string) ( $post->post_content ?? '' ),
		] );
		$GLOBALS['flexa_test_posts'][ $revision_id ]              = $revision;
		$GLOBALS['flexa_test_revision_snapshots'][ $revision_id ] = $revision->post_content;
		return $revision_id;
	}
}

if ( ! function_exists( 'wp_update_post' ) ) {
	function wp_update_post( $postarr, $wp_error = false ) {
		$post_id = (int) ( $postarr['ID'] ?? 0 );
		$post    = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return $wp_error ? new WP_Error( 'missing_post', 'Missing post.' ) : 0;
		}

		$other = new WP_Post( [ 'ID' => $post_id + 1, 'post_type' => $post->post_type ] );
		$GLOBALS['flexa_test_update_filters'][] = [
			'target_general' => apply_filters( 'wp_revisions_to_keep', 10, $post ),
			'target_dynamic' => apply_filters( "wp_{$post->post_type}_revisions_to_keep", 10, $post ),
			'other_general'  => apply_filters( 'wp_revisions_to_keep', 10, $other ),
			'other_dynamic'  => apply_filters( "wp_{$post->post_type}_revisions_to_keep", 10, $other ),
		];

		if ( $GLOBALS['flexa_test_update_error'] instanceof WP_Error ) {
			return $GLOBALS['flexa_test_update_error'];
		}
		if ( array_key_exists( 'post_content', $postarr ) ) {
			$post->post_content = $postarr['post_content'];
		}
		return $post_id;
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
		// No scheme to check: WordPress leaves a path, an anchor or a query as it is.
		if ( in_array( $url[0], [ '/', '#', '?' ], true ) ) {
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
		// Capabilities a test grants outright, by name.
		if ( isset( $GLOBALS['flexa_test_caps'][ $capability ] ) ) {
			return (bool) $GLOBALS['flexa_test_caps'][ $capability ];
		}
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

/*
 * Rewriting one block delimiter in place, shared by every path that edits a
 * field of an existing block, and the MCP edit path's field allowlist. Both
 * are WordPress-free.
 */
require_once $flexa_inc . 'class-link-url.php';
require_once $flexa_inc . 'class-image-source.php';
require_once $flexa_inc . 'class-icon-source.php';
require_once $flexa_inc . 'class-block-patcher.php';
require_once $flexa_inc . 'class-inline-revision-session.php';
require_once $flexa_inc . 'class-inline-edit-log.php';
require_once $flexa_inc . 'mcp/class-editable-fields.php';

// Shared base class for block CSS tests (kept outside the scanned suite dir).
require_once __DIR__ . '/CssTestCase.php';
