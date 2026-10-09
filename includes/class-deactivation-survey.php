<?php
declare(strict_types=1);
/**
 * Deactivation Survey — wires the reusable Deactivation Intelligence client SDK
 * into the plugin.
 *
 * Runs only on the Plugins screen and never blocks or delays deactivation: if
 * the SDK is missing, throws, or the API is down, the native Deactivate link
 * still works. The bundled SDK ships under `libraries/` (outside the plugin's
 * class-file loading), so it is required explicitly.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstraps the deactivation feedback survey.
 */
final class Deactivation_Survey {

	/** Central platform base URL (no trailing slash). */
	const API_URL = 'https://product-intelligence.flexacommerce.com';


	/**
	 * Where the modal's "Get help" link points. One address for every Flexa
	 * plugin, so the link means the same thing wherever a user meets it.
	 */
	const SUPPORT_URL = 'https://flexacommerce.com/support';
	/**
	 * Load the SDK and initialise it once.
	 */
	/**
	 * Put this plugin's copy of the SDK on the ballot.
	 *
	 * Called from the bootstrap at plugin-file load time. The SDK client is a
	 * single global class, so on a site running several Flexa plugins only one
	 * bundled copy can define it, and that copy also serves the JS and CSS for
	 * every product (its enqueue resolves them relative to its own file).
	 * `loader.php` turns the include into an offer: every copy registers its
	 * version, and the newest one is loaded at `plugins_loaded`. Registering
	 * after that point means this copy missed the vote and the site keeps
	 * running whatever older copy a neighbouring plugin shipped.
	 *
	 * Deliberately not gated on the `enabled` filter: a filter cannot be trusted
	 * to exist this early, and offering the file costs nothing. Whether the
	 * survey runs for THIS plugin is decided below; a copy that wins the vote
	 * while disabled here still serves the other products correctly.
	 */
	public static function preload(): void {
		$loader = FLEXA_BLOCK_DIR . 'libraries/deactivation-intelligence/src/loader.php';
		if ( is_readable( $loader ) ) {
			require_once $loader;
		}
	}

	public static function init(): void {
		if ( ! apply_filters( 'flexa-block/deactivation_survey/enabled', true ) ) {
			return;
		}

		if ( ! function_exists( 'deactivation_intelligence_init' ) ) {
			return;
		}

		\deactivation_intelligence_init(
			apply_filters(
				'flexa-block/deactivation_survey/config',
				array(
					'product'     => 'flexa-block',   // Must match the dashboard product slug.
					'tier'        => 'free',           // 'free' | 'pro'.
					'version'     => FLEXA_BLOCK_VER,
					'plugin_file' => FLEXA_BLOCK_BASENAME,
					'api_url'     => self::API_URL,
					'support_url' => self::SUPPORT_URL,
				)
			)
		);
	}
}
