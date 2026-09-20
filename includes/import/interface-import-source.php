<?php
declare(strict_types=1);
/**
 * Import source contract.
 *
 * A "source" is a named collection of importable content: the free plugin's
 * Sample Data, and (registered by add-ons through `flexa_block_import_sources`)
 * Pro Examples and Starter Templates. The engine never hardcodes those concepts
 * — it walks whatever sources are registered and treats each item uniformly.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A registered collection of importable content items.
 *
 * Implementations must keep {@see items()} cheap: it feeds the admin listing and
 * should return metadata only, never the full block markup. The heavy payload is
 * loaded lazily, one item at a time, through {@see definition()}.
 */
interface Import_Source {

	/**
	 * Machine key, unique across all sources (e.g. `sample`, `example`).
	 *
	 * Stored on imported posts as `_flexa_import_source` so cleanup and the
	 * "already imported" check can find content back to its origin.
	 */
	public function key(): string;

	/**
	 * Human label shown as the section heading in the admin UI.
	 */
	public function label(): string;

	/**
	 * Lightweight metadata for every item in this source.
	 *
	 * Each entry: {
	 *   id: string, title: string, description: string, category: string,
	 *   blocks: list<string>, version: string, requires?: list<string>
	 * }.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function items(): array;

	/**
	 * Full content definition for one item, or null when the id is unknown.
	 *
	 * Shape: {
	 *   id, title, version, post_type?, post_status?, content: string (block
	 *   markup), media?: list<array{placeholder:string,file:string,alt?:string}>
	 * }.
	 *
	 * @param string $id Item id within this source.
	 * @return array<string, mixed>|null
	 */
	public function definition( string $id ): ?array;
}
