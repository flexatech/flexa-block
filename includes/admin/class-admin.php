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
		'inline_editor'   => [
			'enabled'         => true,
			'disabled_blocks' => [],
			'roles'           => [ 'editor' ],
		],
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
		// Narrows what gets registered, NOT the catalog itself — see the filter's
		// documentation in Block_Manager::register_blocks().
		add_filter( 'flexa_block_registerable_blocks', [ __CLASS__, 'filter_disabled_blocks' ] );
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
		$ie = is_array( $stored['inline_editor'] ?? null ) ? $stored['inline_editor'] : [];

		return [
			'dark_mode'       => array_merge( self::DEFAULTS['dark_mode'], is_array( $stored['dark_mode'] ?? null ) ? $stored['dark_mode'] : [] ),
			'performance'     => array_merge( self::DEFAULTS['performance'], is_array( $stored['performance'] ?? null ) ? $stored['performance'] : [] ),
			'disabled_blocks' => is_array( $stored['disabled_blocks'] ?? null ) ? array_values( $stored['disabled_blocks'] ) : [],
			'inline_editor'   => [
				'enabled'         => isset( $ie['enabled'] ) ? (bool) $ie['enabled'] : self::DEFAULTS['inline_editor']['enabled'],
				'disabled_blocks' => is_array( $ie['disabled_blocks'] ?? null ) ? array_values( $ie['disabled_blocks'] ) : self::DEFAULTS['inline_editor']['disabled_blocks'],
				'roles'           => is_array( $ie['roles'] ?? null ) ? array_values( $ie['roles'] ) : self::DEFAULTS['inline_editor']['roles'],
			],
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
			'inline_editor'   => self::sanitize_inline_editor( $input['inline_editor'] ?? null, $current['inline_editor'] ),
		];

		update_option( self::OPTION_NAME, $sanitized );

		// Settings affect generated CSS (dark mode, specificity) → drop cached CSS
		// so every post regenerates under the new settings on next view.
		CSS_Generator_Service::flush_all_cache();

		return $sanitized;
	}

	/**
	 * Keep only valid, known, switchable block slugs.
	 *
	 * Locked slugs are excluded even though they are in the catalog: they
	 * describe a block from an add-on the site does not have, so there is nothing
	 * to switch off. Without this, "known slug" alone would accept them and a
	 * bulk action could write a block the site does not own into
	 * `disabled_blocks`, where it would sit until someone bought the add-on and
	 * then wondered why the block arrived switched off.
	 *
	 * @param mixed $slugs Candidate slugs.
	 * @return array<int, string>
	 */
	private static function sanitize_block_slugs( $slugs ): array {
		if ( ! is_array( $slugs ) ) {
			return [];
		}
		$valid  = array_column( Block_Manager::get_block_catalog(), 'slug' );
		$locked = \Flexa\Block\Addon_Blocks::locked_slugs();
		$clean  = [];
		foreach ( $slugs as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( in_array( $slug, $valid, true ) && ! in_array( $slug, $locked, true ) ) {
				$clean[] = $slug;
			}
		}
		return array_values( array_unique( $clean ) );
	}

	/**
	 * Sanitize the inline-editor settings block.
	 *
	 * Falls back to the current values when a key is absent, so a partial save
	 * (e.g. toggling only dark mode) never wipes it.
	 *
	 * @param mixed                $input   Raw inline_editor input (or null).
	 * @param array<string, mixed> $current Current inline_editor settings.
	 * @return array{enabled: bool, disabled_blocks: list<string>, roles: list<string>}
	 */
	private static function sanitize_inline_editor( $input, array $current ): array {
		if ( ! is_array( $input ) ) {
			/** @var array{enabled: bool, disabled_blocks: list<string>, roles: list<string>} $current */
			return $current;
		}

		return [
			'enabled'         => ! empty( $input['enabled'] ),
			'disabled_blocks' => self::sanitize_editable_slugs( $input['disabled_blocks'] ?? [] ),
			'roles'           => self::sanitize_editor_roles( $input['roles'] ?? [] ),
		];
	}

	/**
	 * Keep only valid editable-block slugs (block name minus its namespace).
	 *
	 * @param mixed $slugs Candidate slugs.
	 * @return list<string>
	 */
	private static function sanitize_editable_slugs( $slugs ): array {
		if ( ! is_array( $slugs ) ) {
			return [];
		}
		$valid = array_map(
			[ \Flexa\Block\Inline_Editor::class, 'slug_for' ],
			\Flexa\Block\Inline_Editor::editable_block_names()
		);
		$clean = [];
		foreach ( $slugs as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( in_array( $slug, $valid, true ) ) {
				$clean[] = $slug;
			}
		}
		return array_values( array_unique( $clean ) );
	}

	/**
	 * Keep only real, non-administrator role slugs.
	 *
	 * @param mixed $roles Candidate role slugs.
	 * @return list<string>
	 */
	private static function sanitize_editor_roles( $roles ): array {
		if ( ! is_array( $roles ) ) {
			return [];
		}
		$valid = array_keys( wp_roles()->roles );
		$clean = [];
		foreach ( $roles as $role ) {
			$role = sanitize_key( (string) $role );
			if ( 'administrator' !== $role && in_array( $role, $valid, true ) ) {
				$clean[] = $role;
			}
		}
		return array_values( array_unique( $clean ) );
	}

	/**
	 * Site roles (minus administrator) for the editing settings UI.
	 *
	 * @return list<array{slug: string, name: string}>
	 */
	private static function roles_for_admin(): array {
		$out = [];
		foreach ( wp_roles()->roles as $slug => $data ) {
			if ( 'administrator' === $slug ) {
				continue;
			}
			$out[] = [
				'slug' => (string) $slug,
				'name' => translate_user_role( (string) ( $data['name'] ?? $slug ) ),
			];
		}
		return $out;
	}

	/**
	 * Catalog fields the Blocks dashboard is allowed to receive.
	 *
	 * This list IS the contract for add-ons. The catalog is opened up through the
	 * `flexa_block_blocks` filter, so an add-on's entry arrives here alongside the
	 * core ones — and three of these fields exist only for it:
	 *   - `group`       → overrides the slug→pill mapping in block-groups.ts, which
	 *                     only knows core slugs. Without it an add-on block has no
	 *                     way out of "Other".
	 *   - `badge`       → free-text origin label beside the card title (e.g. "Pro").
	 *                     A string rather than a boolean flag so several add-ons can
	 *                     each label their own blocks.
	 *   - `description` → the card body.
	 * Adding a field the dashboard should see means adding it here first; it is
	 * dropped silently otherwise.
	 *
	 * @var list<string>
	 */
	private const ADMIN_BLOCK_FIELDS = [
		'slug',
		'name',
		'title',
		'description',
		'category',
		'is_core',
		'is_child',
		'is_woo',
		'group',
		'badge',
		'locked',
	];

	/**
	 * Catalog trimmed to the fields the dashboard renders.
	 *
	 * Entries also carry engine-only internals — `generator` (a class FQCN) and
	 * `path` (an absolute filesystem path an add-on points at its own build dir).
	 * Handing the raw catalog to wp_localize_script() printed both into the page,
	 * so every admin screen leaked the server's directory layout. Nothing in the
	 * JS reads them.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function blocks_for_admin(): array {
		$out = [];
		foreach ( Block_Manager::get_block_catalog() as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}
			$out[] = array_intersect_key( $block, array_flip( self::ADMIN_BLOCK_FIELDS ) );
		}
		return $out;
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
	 * Drop the switched-off blocks from the set about to be registered.
	 *
	 * Runs on `flexa_block_registerable_blocks`, deliberately not on
	 * `flexa_block_blocks`: the catalog must keep listing a disabled block so its
	 * card stays on this screen (with the switch off) and so its slug still
	 * validates on the next save. Hooking the catalog also meant add-on blocks
	 * escaped entirely — an add-on appends on the same filter at the same
	 * priority but registers later, so its entries landed after this ran and no
	 * add-on block could ever be switched off.
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

		$style_file = FLEXA_BLOCK_DIR . 'build/admin/index.css';
		if ( file_exists( $style_file ) ) {
			wp_enqueue_style(
				'flexa-block-admin',
				FLEXA_BLOCK_URL . 'build/admin/index.css',
				[ 'wp-components' ],
				$version
			);
			wp_style_add_data( 'flexa-block-admin', 'rtl', 'replace' );
		}

		wp_set_script_translations( 'flexa-block-admin', 'flexa-block', FLEXA_BLOCK_DIR . 'languages' );

		wp_localize_script(
			'flexa-block-admin',
			'flexaBlockAdmin',
			[
				'restUrl'        => esc_url_raw( rest_url( self::REST_NS . '/settings' ) ),
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'version'        => FLEXA_BLOCK_VER,
				'settings'       => self::get_settings(),
				'blocks'         => self::blocks_for_admin(),
				'editableBlocks' => array_map(
					[ \Flexa\Block\Inline_Editor::class, 'slug_for' ],
					\Flexa\Block\Inline_Editor::editable_block_names()
				),
				'roles'          => self::roles_for_admin(),
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
				'blocks'   => self::blocks_for_admin(),
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
