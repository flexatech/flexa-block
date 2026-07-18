<?php
declare(strict_types=1);
/**
 * Asset Loader — editor script, save-time CSS hooks, and conditional frontend CSS.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles editor assets and frontend CSS output.
 */
class Asset_Loader {

	/**
	 * Cached page CSS.
	 *
	 * @var string|null
	 */
	private static $page_css = null;

	/**
	 * Hook registration.
	 */
	public static function init() {
		// Editor JS/CSS are enqueued by register_block_type() from build/blocks/container/block.json.

		// Expose runtime settings (e.g. dark mode on/off) to the block editor.
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'editor_settings' ] );

		// Output generated CSS in the head (after block styles register).
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'output_page_css' ], 20 );

		// Save-time CSS generation.
		add_action( 'save_post', [ __CLASS__, 'generate_post_css' ], 20, 2 );
		add_action( 'rest_after_insert_wp_template', [ __CLASS__, 'generate_template_css' ], 20, 1 );
		add_action( 'rest_after_insert_wp_template_part', [ __CLASS__, 'generate_template_css' ], 20, 1 );
	}

	/**
	 * Expose runtime settings to the editor as `window.flexaBlockEditor`.
	 *
	 * Lets controls (e.g. the light/dark color picker) react to the dark-mode
	 * setting — when dark mode is off, the dark swatch is hidden.
	 */
	public static function editor_settings() {
		$data = wp_json_encode(
			[
				'darkModeEnabled' => Dark_Mode_Settings::is_enabled(),
			]
		);
		wp_add_inline_script( 'wp-blocks', 'window.flexaBlockEditor = ' . $data . ';', 'before' );
	}

	/**
	 * Output generated CSS inline in the head for the current page.
	 */
	public static function output_page_css() {
		$css = self::get_page_css();
		if ( '' === $css ) {
			return;
		}

		// Always attach to a dedicated, always-enqueued handle rather than a
		// specific block's style handle. A block's style is only *enqueued* when
		// that block is on the page, so keying off (say) the Container style
		// would silently drop the inline CSS on a page that has only a Button.
		$handle = 'flexa-block-inline';
		if ( ! wp_style_is( $handle, 'enqueued' ) ) {
			wp_register_style( $handle, false, [], FLEXA_BLOCK_VER );
			wp_enqueue_style( $handle );
		}

		wp_add_inline_style( $handle, $css );

		// The page has Flexa blocks, so load the shared scroll-entrance observer.
		// It no-ops when no block on the page opted into an animation.
		self::enqueue_animation_script();
	}

	/**
	 * Enqueue the shared animation observer (built from src/frontend/animation.ts).
	 */
	private static function enqueue_animation_script() {
		$handle = 'flexa-block-animation';
		if ( wp_script_is( $handle, 'enqueued' ) ) {
			return;
		}

		$rel   = 'build/frontend/animation.js';
		if ( ! file_exists( FLEXA_BLOCK_DIR . $rel ) ) {
			return;
		}

		$asset_path = FLEXA_BLOCK_DIR . 'build/frontend/animation.asset.php';
		$asset      = file_exists( $asset_path ) ? require $asset_path : [ 'dependencies' => [], 'version' => FLEXA_BLOCK_VER ];

		wp_enqueue_script(
			$handle,
			FLEXA_BLOCK_URL . $rel,
			$asset['dependencies'] ?? [],
			$asset['version'] ?? FLEXA_BLOCK_VER,
			true
		);
	}

	/**
	 * Collect CSS for the current request (singular + block templates).
	 *
	 * @return string
	 */
	private static function get_page_css() {
		if ( null !== self::$page_css ) {
			return self::$page_css;
		}

		$css = '';

		// FSE templates (header/footer + current template).
		$css .= self::get_template_css();

		if ( is_singular() ) {
			$post_id = get_queried_object_id();
			if ( $post_id ) {
				$css .= self::css_for_post( $post_id );
			}
		} elseif ( is_archive() || is_home() || is_search() ) {
			global $wp_query;
			if ( ! empty( $wp_query->posts ) ) {
				foreach ( $wp_query->posts as $post ) {
					$css .= self::css_for_post( $post->ID );
				}
			}
		}

		// Prepend shared design tokens so block CSS can reference --flexa-* vars,
		// and append the static scroll-entrance animation stylesheet.
		if ( '' !== $css ) {
			$css = Global_Styles::css() . $css . Animations::css();
		}

		self::$page_css = $css;
		return $css;
	}

	/**
	 * Get (or generate on demand) CSS for a single post.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function css_for_post( $post_id ) {
		$css = CSS_Generator_Service::get_post_css( $post_id );

		if ( '' !== $css ) {
			if ( CSS_Generator_Service::needs_regeneration( $post_id ) ) {
				$css = CSS_Generator_Service::generate_for_post( $post_id );
			}
			return $css;
		}

		if ( CSS_Generator_Service::has_blocks( $post_id ) ) {
			return CSS_Generator_Service::generate_for_post( $post_id );
		}

		return '';
	}

	/**
	 * Get CSS for the current FSE template and common parts.
	 *
	 * Covers the header/footer template parts AND the *current* page template
	 * (e.g. the WooCommerce single-product template). Blocks placed directly in a
	 * block-theme template live in the template's markup — not in the queried
	 * post's content — so without this their per-instance CSS would never be
	 * emitted. The current template is generated fresh from its resolved markup so
	 * edits take effect immediately.
	 *
	 * @return string
	 */
	private static function get_template_css() {
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return '';
		}

		$css   = '';
		$parts = [ 'header', 'footer' ];

		foreach ( $parts as $slug ) {
			$part = get_block_template( get_stylesheet() . '//' . $slug, 'wp_template_part' );
			if ( $part && ! empty( $part->wp_id ) ) {
				$css .= self::css_for_post( $part->wp_id );
			}
		}

		// The current block template's own markup (set by core in
		// locate_block_template() before wp_head runs). Header/footer are only
		// *references* here, so they aren't generated twice.
		$template_content = $GLOBALS['_wp_current_template_content'] ?? '';
		if ( is_string( $template_content ) && '' !== $template_content ) {
			$css .= CSS_Generator_Service::generate_for_blocks( parse_blocks( $template_content ) );
		}

		return $css;
	}

	/**
	 * Generate CSS on post save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post.
	 */
	public static function generate_post_css( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$allowed = [ 'post', 'page', 'wp_template', 'wp_template_part' ];
		if ( ! in_array( $post->post_type, $allowed, true ) ) {
			return;
		}

		if ( CSS_Generator_Service::has_blocks( $post_id ) ) {
			CSS_Generator_Service::generate_for_post( $post_id );
		} else {
			CSS_Generator_Service::clear_cache( $post_id );
		}
	}

	/**
	 * Generate CSS for FSE templates saved via REST.
	 *
	 * @param \WP_Post $post Template post.
	 */
	public static function generate_template_css( $post ) {
		if ( empty( $post->ID ) ) {
			return;
		}
		if ( CSS_Generator_Service::has_blocks( $post->ID ) ) {
			CSS_Generator_Service::generate_for_post( $post->ID );
		} else {
			CSS_Generator_Service::clear_cache( $post->ID );
		}
	}
}
