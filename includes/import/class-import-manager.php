<?php
declare(strict_types=1);
/**
 * Import engine bootstrap.
 *
 * Loads the shared import classes, registers the free plugin's Sample Data
 * source, wires the REST routes, and hands the admin app the endpoints it needs.
 * Add-ons (Pro) reuse everything here by registering their own sources on the
 * `flexa_block_import_sources` filter — they do not re-implement the engine.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Glue that boots the import engine.
 */
class Import_Manager {

	/**
	 * Load classes and register hooks.
	 */
	public static function init(): void {
		$dir = FLEXA_BLOCK_DIR . 'includes/import/';
		require_once $dir . 'interface-import-source.php';
		require_once $dir . 'class-import-registry.php';
		require_once $dir . 'class-media-importer.php';
		require_once $dir . 'class-content-importer.php';
		require_once $dir . 'class-import-cleanup.php';
		require_once $dir . 'class-sample-source.php';
		require_once $dir . 'class-import-rest.php';

		add_filter( 'flexa_block_import_sources', [ __CLASS__, 'register_default_source' ] );
		add_action( 'rest_api_init', [ Import_REST::class, 'register_routes' ] );
		// Priority 20: run after Admin::enqueue_assets (10) has enqueued the app,
		// so the handle exists to attach data to.
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'localize_admin' ], 20 );
	}

	/**
	 * Add the free Sample Data source to the registry.
	 *
	 * @param mixed $sources Existing sources.
	 * @return array<int, Import_Source>
	 */
	public static function register_default_source( $sources ): array {
		if ( ! is_array( $sources ) ) {
			$sources = [];
		}
		$sources[] = new Sample_Source();
		return $sources;
	}

	/**
	 * Expose the import endpoints to the already-enqueued admin app.
	 *
	 * Attaches to the free admin script handle only when it is present, mirroring
	 * how the settings data is localized. The nonce middleware is already applied
	 * by the app from the settings bootstrap, so only the URLs are needed here.
	 */
	public static function localize_admin(): void {
		if ( ! wp_script_is( 'flexa-block-admin', 'enqueued' ) ) {
			return;
		}

		wp_localize_script(
			'flexa-block-admin',
			'flexaBlockImports',
			[
				'listUrl'    => esc_url_raw( rest_url( Import_REST::REST_NS . '/imports' ) ),
				'importUrl'  => esc_url_raw( rest_url( Import_REST::REST_NS . '/imports/import' ) ),
				'cleanupUrl' => esc_url_raw( rest_url( Import_REST::REST_NS . '/imports/cleanup' ) ),
			]
		);
	}
}
