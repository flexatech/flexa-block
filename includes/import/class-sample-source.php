<?php
declare(strict_types=1);
/**
 * Sample Data source (free plugin).
 *
 * Discovers each sample from `samples/<id>.php` — a file that returns a content
 * definition array. Samples are ready-made pages (a full landing page, a contact
 * page) that let users see the blocks working together and start from something
 * real; they deliberately ship no bundled media so the free plugin stays
 * WordPress.org-clean (icons are inline SVG in attributes). Pro extends the same
 * engine with its own sources (Examples and Starter Templates).
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides the free plugin's bundled samples.
 */
class Sample_Source implements Import_Source {

	/** Source key stored on imported posts. */
	const KEY = 'sample';

	/**
	 * Cached raw definitions, keyed by id. Null until first scan.
	 *
	 * @var array<string, array<string, mixed>>|null
	 */
	private $cache = null;

	/**
	 * {@inheritDoc}
	 */
	public function key(): string {
		return self::KEY;
	}

	/**
	 * {@inheritDoc}
	 */
	public function label(): string {
		return __( 'Sample Data', 'flexa-block' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function items(): array {
		$out = [];
		foreach ( $this->all() as $def ) {
			$out[] = [
				'id'          => (string) $def['id'],
				'title'       => (string) $def['title'],
				'description' => (string) ( $def['description'] ?? '' ),
				'category'    => (string) ( $def['category'] ?? 'content' ),
				'blocks'      => array_values( array_map( 'strval', (array) ( $def['blocks'] ?? [] ) ) ),
				'version'     => (string) ( $def['version'] ?? '1.0.0' ),
			];
		}
		return $out;
	}

	/**
	 * {@inheritDoc}
	 */
	public function definition( string $id ): ?array {
		$all = $this->all();
		return $all[ $id ] ?? null;
	}

	/**
	 * Load and validate every sample definition once per request.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function all(): array {
		if ( null !== $this->cache ) {
			return $this->cache;
		}

		$out = [];
		$dir = FLEXA_BLOCK_DIR . 'samples';
		if ( is_dir( $dir ) ) {
			foreach ( (array) glob( $dir . '/*.php' ) as $file ) {
				$def = require $file;
				if ( ! is_array( $def ) ) {
					continue;
				}
				$id = sanitize_key( (string) ( $def['id'] ?? '' ) );
				// The id is also the source of truth for cleanup, so the filename
				// must not be trusted over it: pin the array's id and skip blanks.
				if ( '' === $id ) {
					continue;
				}
				$def['id']    = $id;
				$out[ $id ]   = $def;
			}
		}

		$this->cache = $out;
		return $out;
	}
}
