<?php
declare(strict_types=1);
/**
 * Import source registry.
 *
 * Collects every {@see Import_Source} contributed through the
 * `flexa_block_import_sources` filter. The free plugin adds its Sample Data
 * source; Pro appends Examples and Starter Templates on the same filter. Nothing
 * downstream knows which plugin a source came from.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves and caches the registered import sources.
 */
class Import_Registry {

	/**
	 * Resolved sources, keyed by source key. Null until first resolved.
	 *
	 * @var array<string, Import_Source>|null
	 */
	private static $sources = null;

	/**
	 * All registered sources, keyed by their key.
	 *
	 * @return array<string, Import_Source>
	 */
	public static function sources(): array {
		if ( null !== self::$sources ) {
			return self::$sources;
		}

		/**
		 * Register importable content sources.
		 *
		 * Add-ons push their own {@see Import_Source} instances here. Later
		 * duplicates of an existing key are ignored so a source cannot be
		 * silently overridden.
		 *
		 * @param list<Import_Source> $sources Registered sources.
		 */
		$registered = apply_filters( 'flexa_block_import_sources', [] );

		$out = [];
		if ( is_array( $registered ) ) {
			foreach ( $registered as $source ) {
				if ( ! $source instanceof Import_Source ) {
					continue;
				}
				$key = $source->key();
				if ( '' === $key || isset( $out[ $key ] ) ) {
					continue;
				}
				$out[ $key ] = $source;
			}
		}

		self::$sources = $out;
		return $out;
	}

	/**
	 * Look up a single source by key.
	 *
	 * @param string $key Source key.
	 * @return Import_Source|null
	 */
	public static function source( string $key ): ?Import_Source {
		return self::sources()[ $key ] ?? null;
	}
}
