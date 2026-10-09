<?php
declare(strict_types=1);
/**
 * MCP runtime: registers Flexa's abilities with core's Abilities API.
 *
 * Loaded only when an administrator has switched the module on and the site has
 * the API (see `flexa_block_mcp_runtime_enabled()` in the bootstrap). On a site
 * that never opted in, this file is never read from disk.
 *
 * What it does not do: talk MCP. The protocol, the transport, session handling
 * and authentication all belong to the MCP Adapter plugin, which reads core's
 * ability registry and publishes the abilities that mark themselves
 * `meta.mcp.public`. We register abilities and own their permission checks;
 * which of them travel over MCP is the adapter's business. Without the adapter
 * the same abilities are still reachable through core's own abilities REST
 * routes, which is the point of registering them there rather than inventing a
 * second surface.
 *
 * The plugin's own abilities live in their own files and arrive through the
 * filter below like anyone else's, so this class stays a registrar: it has no
 * knowledge of what it registers, and nothing here has to change when an
 * ability is added or a toggle takes one away.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the plugin's abilities, if there are any.
 */
class MCP_Manager {

	/** Category every Flexa ability is filed under. */
	const CATEGORY = 'flexa-block';

	/** Namespace every ability name must carry. */
	const ABILITY_NAMESPACE = 'flexa';

	/**
	 * Register hooks.
	 *
	 * Core wants categories and abilities on two different actions, and in that
	 * order: a category has to exist before an ability can point at it.
	 */
	public static function init(): void {
		add_action( 'wp_abilities_api_categories_init', [ __CLASS__, 'register_category' ] );
		add_action( 'wp_abilities_api_init', [ __CLASS__, 'register_abilities' ] );
	}

	/**
	 * The abilities to register, keyed by full ability name.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function abilities(): array {
		/**
		 * Filters the abilities the MCP module registers.
		 *
		 * The seam other Flexa plugins use to contribute their own, so each one
		 * keeps its abilities next to the code they drive instead of this file
		 * growing a branch per plugin. Keys are full ability names
		 * (`flexa/<verb>-<thing>`); values are the argument array
		 * `wp_register_ability()` takes.
		 *
		 * An entry is dropped unless it carries a `permission_callback`, and
		 * `meta.mcp.public` is never filled in on a contributor's behalf: an
		 * ability reaches external agents only by asking to.
		 *
		 * @param array<string, array<string, mixed>> $abilities Keyed by ability name.
		 */
		return (array) apply_filters( 'flexa_block_mcp_abilities', [] );
	}

	/**
	 * Register the category the abilities are filed under.
	 *
	 * Skipped when nothing would go in it, so a site with the module on but no
	 * abilities registered shows no empty Flexa group to a client browsing the
	 * registry.
	 */
	public static function register_category(): void {
		if ( ! self::abilities() ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			[
				'label'       => __( 'Flexa Block', 'flexa-block' ),
				'description' => __( 'Read the design context of a Flexa Block site and create pages from its presets.', 'flexa-block' ),
			]
		);
	}

	/**
	 * Register each contributed ability.
	 */
	public static function register_abilities(): void {
		foreach ( self::abilities() as $name => $args ) {
			$name = (string) $name;

			if ( ! is_array( $args ) || ! self::is_own_name( $name ) ) {
				continue;
			}

			// An ability with no permission check is a hole, not a feature. A
			// contributor that forgets one gets nothing registered rather than
			// something anyone can run.
			if ( empty( $args['permission_callback'] ) || ! is_callable( $args['permission_callback'] ) ) {
				continue;
			}

			if ( empty( $args['execute_callback'] ) || ! is_callable( $args['execute_callback'] ) ) {
				continue;
			}

			if ( empty( $args['category'] ) ) {
				$args['category'] = self::CATEGORY;
			}

			wp_register_ability( $name, $args );
		}
	}

	/**
	 * Whether a name is one this plugin is entitled to register.
	 *
	 * Guards the filter against an entry that would park an ability under
	 * someone else's namespace, where its owner could neither find nor
	 * unregister it.
	 *
	 * @param string $name Full ability name.
	 * @return bool
	 */
	private static function is_own_name( string $name ): bool {
		return 0 === strpos( $name, self::ABILITY_NAMESPACE . '/' ) && strlen( $name ) > strlen( self::ABILITY_NAMESPACE ) + 1;
	}
}
