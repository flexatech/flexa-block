<?php
declare(strict_types=1);
/**
 * Admin — settings page, REST API, and option-backed behaviour wiring.
 *
 * One small dashboard that lets users toggle dark mode, the CSS specificity
 * boost, and enable/disable individual blocks — stored in a single option and
 * fed back into the engine via filters.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\Admin;

use Flexa\Block\Block_Manager;
use Flexa\Block\CSS_Generator_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin dashboard + settings store for Flexa Block.
 */
class Admin {

	const OPTION_NAME = 'flexa_block_settings';
	const PAGE_SLUG   = 'flexa-block';
	const REST_NS     = 'flexa-block/v1';

	/**
	 * Default settings (deep-merged under stored values).
	 *
	 * @var array<string, mixed>
	 */
	const DEFAULTS = [
		'dark_mode'       => [
			'enabled'     => true,
			'colorScheme' => true,
			'dataTheme'   => false,
		],
		'performance'     => [
			'specificityBoost' => false,
		],
		'disabled_blocks' => [],
	];

	/**
	 * Menu hook suffix, used to scope asset loading.
	 *
	 * @var string
	 */
	private static $hook_suffix = '';

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );

		// Feed stored settings back into the engine.
		add_filter( 'flexa_block_css_specificity_boost', [ __CLASS__, 'filter_specificity_boost' ] );
		add_filter( 'flexa_block_blocks', [ __CLASS__, 'filter_disabled_blocks' ] );
	}

	/* ---------------------------------------------------------------------
	 * Settings store
	 * ------------------------------------------------------------------ */

	/**
	 * Get all settings merged over defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$stored = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}
		return [
			'dark_mode'       => array_merge( self::DEFAULTS['dark_mode'], is_array( $stored['dark_mode'] ?? null ) ? $stored['dark_mode'] : [] ),
			'performance'     => array_merge( self::DEFAULTS['performance'], is_array( $stored['performance'] ?? null ) ? $stored['performance'] : [] ),
			'disabled_blocks' => is_array( $stored['disabled_blocks'] ?? null ) ? array_values( $stored['disabled_blocks'] ) : [],
		];
	}

	/**
	 * Sanitize and persist incoming settings.
	 *
	 * @param array<string, mixed> $input Raw settings from the REST request.
	 * @return array<string, mixed> The stored (sanitized) settings.
	 */
	public static function save_settings( array $input ): array {
		$current = self::get_settings();

		$dark = is_array( $input['dark_mode'] ?? null ) ? $input['dark_mode'] : [];
		$perf = is_array( $input['performance'] ?? null ) ? $input['performance'] : [];

		$sanitized = [
			'dark_mode'       => [
				'enabled'     => ! empty( $dark['enabled'] ),
				'colorScheme' => ! empty( $dark['colorScheme'] ),
				'dataTheme'   => ! empty( $dark['dataTheme'] ),
			],
			'performance'     => [
				'specificityBoost' => ! empty( $perf['specificityBoost'] ),
			],
			'disabled_blocks' => self::sanitize_block_slugs( $input['disabled_blocks'] ?? $current['disabled_blocks'] ),
		];

		update_option( self::OPTION_NAME, $sanitized );

		// Settings affect generated CSS (dark mode, specificity) → drop cached CSS
		// so every post regenerates under the new settings on next view.
		CSS_Generator_Service::flush_all_cache();

		return $sanitized;
	}

	/**
	 * Keep only valid, known block slugs.
	 *
	 * @param mixed $slugs Candidate slugs.
	 * @return array<int, string>
	 */
	private static function sanitize_block_slugs( $slugs ): array {
		if ( ! is_array( $slugs ) ) {
			return [];
		}
		$valid = array_column( Block_Manager::get_block_catalog(), 'slug' );
		$clean = [];
		foreach ( $slugs as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( in_array( $slug, $valid, true ) ) {
				$clean[] = $slug;
			}
		}
		return array_values( array_unique( $clean ) );
	}

	/* ---------------------------------------------------------------------
	 * Engine wiring (filters)
	 * ------------------------------------------------------------------ */

	/**
	 * Resolve the specificity-boost filter from the stored setting.
	 *
	 * @param bool $boost Incoming value.
	 * @return bool
	 */
	public static function filter_specificity_boost( $boost ): bool {
		$settings = self::get_settings();
		return ! empty( $settings['performance']['specificityBoost'] ) ? true : (bool) $boost;
	}

	/**
	 * Remove disabled blocks from the registration catalog.
	 *
	 * @param array<int, array<string, mixed>> $blocks Block catalog.
	 * @return array<int, array<string, mixed>>
	 */
	public static function filter_disabled_blocks( $blocks ): array {
		if ( ! is_array( $blocks ) ) {
			return [];
		}
		$disabled = self::get_settings()['disabled_blocks'];
		if ( empty( $disabled ) ) {
			return $blocks;
		}
		return array_values(
			array_filter(
				$blocks,
				static function ( $block ) use ( $disabled ) {
					return ! in_array( $block['slug'] ?? '', $disabled, true );
				}
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Menu + assets
	 * ------------------------------------------------------------------ */

	/**
	 * Register the top-level admin menu page.
	 */
	public static function register_menu(): void {
		$hook = add_menu_page(
			__( 'Flexa Block', 'flexa-block' ),
			__( 'Flexa Block', 'flexa-block' ),
			'manage_options',
			self::PAGE_SLUG,
			[ __CLASS__, 'render_page' ],
			'dashicons-layout',
			81
		);
		self::$hook_suffix = (string) $hook;
	}

	/**
	 * Render the React mount point.
	 */
	public static function render_page(): void {
		echo '<div class="wrap"><div id="flexa-block-admin"></div></div>';
	}

	/**
	 * Enqueue the admin app only on our page.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public static function enqueue_assets( $hook ): void {
		if ( $hook !== self::$hook_suffix ) {
			return;
		}

		$asset_file = FLEXA_BLOCK_DIR . 'build/admin/index.asset.php';
		$asset      = file_exists( $asset_file ) ? require $asset_file : null;
		if ( ! is_array( $asset ) ) {
			$asset = [];
		}
		$deps    = is_array( $asset['dependencies'] ?? null ) ? $asset['dependencies'] : [ 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n' ];
		$version = is_string( $asset['version'] ?? null ) ? $asset['version'] : FLEXA_BLOCK_VER;

		wp_enqueue_script(
			'flexa-block-admin',
			FLEXA_BLOCK_URL . 'build/admin/index.js',
			$deps,
			$version,
			true
		);
		wp_enqueue_style( 'wp-components' );

		wp_localize_script(
			'flexa-block-admin',
			'flexaBlockAdmin',
			[
				'restUrl'  => esc_url_raw( rest_url( self::REST_NS . '/settings' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'settings' => self::get_settings(),
				'blocks'   => Block_Manager::get_block_catalog(),
			]
		);
	}

	/* ---------------------------------------------------------------------
	 * REST API
	 * ------------------------------------------------------------------ */

	/**
	 * Register GET/POST routes for settings.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NS,
			'/settings',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ __CLASS__, 'rest_get_settings' ],
					'permission_callback' => [ __CLASS__, 'rest_permission' ],
				],
				[
					'methods'             => 'POST',
					'callback'            => [ __CLASS__, 'rest_save_settings' ],
					'permission_callback' => [ __CLASS__, 'rest_permission' ],
				],
			]
		);
	}

	/**
	 * Only administrators may read/write settings.
	 *
	 * @return bool
	 */
	public static function rest_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET handler.
	 *
	 * @return \WP_REST_Response
	 */
	public static function rest_get_settings(): \WP_REST_Response {
		return rest_ensure_response(
			[
				'settings' => self::get_settings(),
				'blocks'   => Block_Manager::get_block_catalog(),
			]
		);
	}

	/**
	 * POST handler.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response
	 */
	public static function rest_save_settings( \WP_REST_Request $request ): \WP_REST_Response {
		$settings = self::save_settings( (array) $request->get_json_params() );

		return rest_ensure_response(
			[
				'saved'    => true,
				'settings' => $settings,
			]
		);
	}
}
