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
	 * Load the SDK and initialise it once.
	 */
	public static function init(): void {
		if ( ! apply_filters( 'flexa-block/deactivation_survey/enabled', true ) ) {
			return;
		}

		$sdk = FLEXA_BLOCK_DIR . 'libraries/deactivation-intelligence/src/class-deactivation-intelligence.php';
		if ( ! is_readable( $sdk ) ) {
			return;
		}
		require_once $sdk;

		if ( ! class_exists( \Deactivation_Intelligence::class ) ) {
			return;
		}

		\Deactivation_Intelligence::init(
			apply_filters(
				'flexa-block/deactivation_survey/config',
				array(
					'product'     => 'flexa-block',   // Must match the dashboard product slug.
					'tier'        => 'free',           // 'free' | 'pro'.
					'version'     => FLEXA_BLOCK_VER,
					'plugin_file' => FLEXA_BLOCK_BASENAME,
					'api_url'     => self::API_URL,
				)
			)
		);
	}
}
