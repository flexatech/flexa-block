<?php
declare(strict_types=1);
/**
 * Shared scaffolding for the plugin's abilities.
 *
 * Five abilities are planned across the read and write phases, and without a
 * common floor each of them would restate the same category, the same
 * annotations, the same exposure flag and the same shape of permission check.
 * The repetition is not just noise: it is five chances to get an exposure flag
 * or a capability wrong, in five places nobody reviews together.
 *
 * Nothing here decides *whether* an ability exists. That is the caller's job,
 * and for the read abilities it is answered by the read toggle before any of
 * this is reached.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds ability definitions with the plugin's defaults already applied.
 */
class Ability_Support {

	/**
	 * Longest string this plugin will put in an ability response.
	 *
	 * Applied per value rather than to the response as a whole, so one runaway
	 * attribute cannot push a payload to a size that a client has to deal with
	 * by truncating blindly. A value over the limit is cut and marked, which is
	 * information; a response dropped for being too large is not.
	 */
	const MAX_VALUE_LENGTH = 2000;

	/**
	 * Marker appended to a value this plugin shortened.
	 *
	 * Deliberately not valid in the middle of a design token or a block
	 * attribute, so a client can tell a truncation from the real content.
	 */
	const TRUNCATION_MARKER = '…[truncated]';

	/**
	 * Build a read-only ability definition.
	 *
	 * Fills in the three things every read ability of ours agrees on and would
	 * otherwise repeat:
	 *
	 *  - the Flexa category, so a client browsing the registry sees them
	 *    grouped rather than scattered through whatever core and other plugins
	 *    registered;
	 *  - annotations that say plainly this reads and does not write, which is
	 *    what lets a client treat a call as safe to retry;
	 *  - `meta.public`, the single flag that exposes an ability to the outside.
	 *
	 * On that last one: the MCP Adapter resolves exposure from `meta.mcp.public`
	 * first and falls back to `meta.public`. We set only `meta.public`, and do
	 * not also write the MCP-specific flag, so that one value governs both
	 * channels. If a site filters it off expecting everything to go quiet, an
	 * explicit `meta.mcp.public` left behind here would keep MCP serving the
	 * ability anyway. One switch, no surprises.
	 *
	 * @param array<string, mixed> $args Ability arguments; label, description,
	 *                                   execute_callback and permission_callback
	 *                                   are the caller's to supply.
	 * @return array<string, mixed>
	 */
	public static function read_ability( array $args ): array {
		$meta = isset( $args['meta'] ) && is_array( $args['meta'] ) ? $args['meta'] : [];

		$meta['public']      = true;
		$meta['annotations'] = array_merge(
			[
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			],
			isset( $meta['annotations'] ) && is_array( $meta['annotations'] ) ? $meta['annotations'] : []
		);

		$args['meta']     = $meta;
		$args['category'] = $args['category'] ?? MCP_Manager::CATEGORY;

		return $args;
	}

	/**
	 * A permission callback that asks for one capability.
	 *
	 * The transport in front of these abilities only requires `read`, which is
	 * every logged-in user including a subscriber. That is the right floor for
	 * a transport and the wrong one for an ability, so each ability names what
	 * it actually needs and gets it checked here.
	 *
	 * @param string $capability Capability to require.
	 * @return callable
	 */
	public static function requires( string $capability ): callable {
		return static function () use ( $capability ): bool {
			return current_user_can( $capability );
		};
	}

	/**
	 * Shorten a string that is longer than this plugin will return.
	 *
	 * @param string $value Value to bound.
	 * @return string
	 */
	public static function bound( string $value ): string {
		if ( strlen( $value ) <= self::MAX_VALUE_LENGTH ) {
			return $value;
		}

		// Cut on characters rather than bytes so the result is still valid
		// UTF-8; a byte-wise cut can split a multibyte character and produce a
		// string that fails JSON encoding further down.
		return mb_substr( $value, 0, self::MAX_VALUE_LENGTH ) . self::TRUNCATION_MARKER;
	}

	/**
	 * Keep a map encoding as a JSON object even when it is empty.
	 *
	 * PHP cannot tell an empty map from an empty list, and `wp_json_encode()`
	 * resolves the ambiguity as `[]`. Every one of our schemas that declares a
	 * free-form map declares it as an object, and a block with no attributes of
	 * its own is the common case rather than the odd one, so a client that types
	 * its parsing against the schema would break on the first such block. Core's
	 * own validator accepts either, which means nothing catches this: it shows
	 * up in the client.
	 *
	 * @param array<string, mixed> $map Map to encode.
	 * @return array<string, mixed>|\stdClass
	 */
	public static function json_object( array $map ) {
		return $map ? $map : new \stdClass();
	}

	/**
	 * Recursively bound every string in a value.
	 *
	 * Used on data that originates in post content or in a filter, where the
	 * size is whoever-wrote-it's decision rather than ours.
	 *
	 * @param mixed $value Arbitrary value.
	 * @param int   $depth Current recursion depth.
	 * @return mixed
	 */
	public static function bound_deep( $value, int $depth = 0 ) {
		if ( is_string( $value ) ) {
			return self::bound( $value );
		}

		// A structure nested past this is either hostile or broken, and either
		// way the honest answer is to stop rather than to recurse until PHP
		// gives out.
		if ( $depth >= 32 || ! is_array( $value ) ) {
			return is_array( $value ) ? [] : $value;
		}

		$out = [];
		foreach ( $value as $key => $item ) {
			$out[ $key ] = self::bound_deep( $item, $depth + 1 );
		}

		return $out;
	}
}
