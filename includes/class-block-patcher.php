<?php
declare(strict_types=1);
/**
 * Change one block's attributes inside saved post content, and nothing else.
 *
 * The obvious way to edit a block is `parse_blocks()`, change the array, and
 * `serialize_blocks()` the whole post back. That round trip is not faithful:
 * attributes are decoded as PHP arrays, which cannot tell `{}` from `[]`, so any
 * empty object anywhere on the page comes back as an empty list. Any write path
 * that re-serializes a page a person built by hand inherits that, which is why
 * every path in this plugin that changes one field of an existing block goes
 * through here: the MCP module's page editor, and front-end inline editing.
 *
 * So this works on the bytes. It finds the opening comment delimiter of the one
 * block being changed, decodes that block's attributes as objects (which keeps
 * `{}` and `[]` apart), changes the requested keys, re-encodes that block's
 * attributes the way core's serializer does, and splices the new delimiter in
 * place of the old one. Every other byte of the post is left exactly as it was.
 * Then it re-reads the result and checks that the block it changed decodes to
 * what was intended and that no other block moved, and refuses otherwise.
 *
 * Deliberately free of WordPress: the delimiter grammar, the encoding and the
 * splice are the parts with the sharp edges, and they can be tested on their
 * own. Deciding which attributes may change, and to what, is not this class's
 * business: each caller keeps its own allowlist and validation, and adds its
 * own advice on top of the errors returned here, which only say what happened.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locates block delimiters and rewrites one block's attributes in place.
 */
class Block_Patcher {

	/**
	 * A block comment delimiter, as core's own parser (`WP_Block_Parser`) reads
	 * one. Kept identical on purpose: if this matched something core does not,
	 * or missed something core does, the block found here would not be the block
	 * WordPress renders.
	 */
	const DELIMITER_RE = '/<!--\s+(?P<closer>\/)?wp:(?P<namespace>[a-z][a-z0-9_-]*\/)?(?P<name>[a-z][a-z0-9_-]*)\s+(?P<attrs>{(?:(?:[^}]+|}+(?=})|(?!}\s+\/?-->).)*+)?}\s+)?(?P<void>\/)?-->/s';

	/**
	 * Every opening (or void) block delimiter in a post, in document order.
	 *
	 * Closers are skipped: they carry no attributes. Nested blocks appear as
	 * their own entries, so a block inside a container is found the same way
	 * as one at the top level.
	 *
	 * @param string $content Post content.
	 * @return list<array{name:string, written:string, attrs:\stdClass, offset:int, length:int, void:bool}>
	 */
	public static function tokens( string $content ): array {
		if ( ! preg_match_all( self::DELIMITER_RE, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
			return [];
		}

		$out = [];
		foreach ( $matches as $m ) {
			if ( '' !== $m['closer'][0] && -1 !== $m['closer'][1] ) {
				continue;
			}

			$namespace = -1 !== $m['namespace'][1] ? (string) $m['namespace'][0] : '';
			$written   = $namespace . $m['name'][0];
			$json      = isset( $m['attrs'] ) && -1 !== $m['attrs'][1] ? trim( (string) $m['attrs'][0] ) : '';
			$attrs     = '' !== $json ? json_decode( $json ) : null;

			$out[] = [
				'name'    => '' !== $namespace ? $written : 'core/' . $written,
				'written' => $written,
				'attrs'   => $attrs instanceof \stdClass ? $attrs : new \stdClass(),
				'offset'  => (int) $m[0][1],
				'length'  => strlen( (string) $m[0][0] ),
				'void'    => isset( $m['void'] ) && -1 !== $m['void'][1] && '/' === $m['void'][0],
			];
		}

		return $out;
	}

	/**
	 * Every `blockId` that appears in a post's own content, as a lookup set.
	 *
	 * Used to tell a block the post actually contains from one that is only
	 * rendered on its page: a template part, a synced pattern, another post's
	 * content pulled in by a query loop. Only the first can be saved back into
	 * this post.
	 *
	 * @param string $content Post content.
	 * @return array<string, true>
	 */
	public static function block_ids( string $content ): array {
		$ids = [];
		foreach ( self::tokens( $content ) as $token ) {
			if ( isset( $token['attrs']->blockId ) && is_string( $token['attrs']->blockId ) && '' !== $token['attrs']->blockId ) {
				$ids[ $token['attrs']->blockId ] = true;
			}
		}
		return $ids;
	}

	/**
	 * The one block whose `blockId` attribute is the given value.
	 *
	 * Two blocks with the same id is a page someone duplicated a block on
	 * without the editor regenerating it. Picking one would be guessing, so it
	 * is refused and the caller is told why.
	 *
	 * @param string $content  Post content.
	 * @param string $block_id Value of the `blockId` attribute.
	 * @return array{name:string, written:string, attrs:\stdClass, offset:int, length:int, void:bool}|\WP_Error
	 */
	public static function locate( string $content, string $block_id ) {
		$found = [];
		foreach ( self::tokens( $content ) as $token ) {
			if ( isset( $token['attrs']->blockId ) && $block_id === $token['attrs']->blockId ) {
				$found[] = $token;
			}
		}

		if ( ! $found ) {
			return new \WP_Error(
				'flexa_block_unknown_block',
				sprintf(
					/* translators: %s: block id */
					__( 'No block with the id %s on this page. It may have been moved or removed since the page was read.', 'flexa-block' ),
					$block_id
				),
				[ 'status' => 404 ]
			);
		}

		if ( count( $found ) > 1 ) {
			return new \WP_Error(
				'flexa_block_duplicate_block',
				sprintf(
					/* translators: %s: block id */
					__( 'More than one block on this page has the id %s, so it is not clear which one to change. Opening the page in the editor and saving it once gives each block its own id.', 'flexa-block' ),
					$block_id
				),
				[ 'status' => 409 ]
			);
		}

		return $found[0];
	}

	/**
	 * Read a value at a path of keys and list indexes.
	 *
	 * @param mixed        $node Decoded attributes, objects as stdClass.
	 * @param list<string> $path Keys, with list indexes as digit strings.
	 * @return array{0:bool, 1:mixed} Whether the path exists, and its value.
	 */
	public static function value_at( $node, array $path ): array {
		foreach ( $path as $key ) {
			if ( $node instanceof \stdClass && property_exists( $node, $key ) ) {
				$node = $node->{$key};
				continue;
			}
			if ( is_array( $node ) && ctype_digit( $key ) && array_key_exists( (int) $key, $node ) ) {
				$node = $node[ (int) $key ];
				continue;
			}
			return [ false, null ];
		}

		return [ true, $node ];
	}

	/**
	 * A copy of a decoded value with one path set.
	 *
	 * Every container on the way must already exist. Only the last key may be
	 * new, and only on an object: a missing attribute is how a block stores a
	 * value equal to its default, so setting one that is not there yet is
	 * normal. Creating list entries or intermediate objects is not, and would be
	 * inventing structure the block never declared.
	 *
	 * @param mixed        $node  Decoded value.
	 * @param list<string> $path  Keys, with list indexes as digit strings.
	 * @param mixed        $value Value to set.
	 * @return mixed|\WP_Error
	 */
	public static function with_value( $node, array $path, $value ) {
		if ( ! $path ) {
			return $value;
		}

		$key  = array_shift( $path );
		$last = ! $path;

		if ( $node instanceof \stdClass ) {
			if ( ! $last && ! property_exists( $node, $key ) ) {
				return self::bad_path();
			}
			$copy  = clone $node;
			$child = self::with_value( property_exists( $node, $key ) ? $node->{$key} : null, $path, $value );
			if ( $child instanceof \WP_Error ) {
				return $child;
			}
			$copy->{$key} = $child;
			return $copy;
		}

		// Decoded JSON arrays are always lists here: objects decode as stdClass.
		if ( is_array( $node ) && ctype_digit( $key ) && array_key_exists( (int) $key, $node ) ) {
			$child = self::with_value( $node[ (int) $key ], $path, $value );
			if ( $child instanceof \WP_Error ) {
				return $child;
			}
			$node[ (int) $key ] = $child;
			return $node;
		}

		return self::bad_path();
	}

	/**
	 * Replace the attributes of one located block, and check the result.
	 *
	 * @param string                                                                                 $content Post content.
	 * @param array{name:string, written:string, attrs:\stdClass, offset:int, length:int, void:bool} $token   Block, as `locate()` returned it.
	 * @param \stdClass                                                                              $attrs   New attributes for that block.
	 * @return string|\WP_Error New content.
	 */
	public static function replace( string $content, array $token, \stdClass $attrs ) {
		$encoded   = self::encode( $attrs );
		$delimiter = '<!-- wp:' . $token['written'] . ( '{}' === $encoded ? '' : ' ' . $encoded ) . ' ' . ( $token['void'] ? '/' : '' ) . '-->';

		$patched = substr_replace( $content, $delimiter, $token['offset'], $token['length'] );

		// The check that makes the splice trustworthy: read the result back with
		// the same grammar, and require that the same number of blocks start in
		// the same order, that the changed block now decodes to exactly what was
		// meant, and that every other block decodes to what it did before.
		$before = self::tokens( $content );
		$after  = self::tokens( $patched );
		if ( count( $before ) !== count( $after ) ) {
			return self::unsafe();
		}

		foreach ( $before as $i => $old ) {
			$new      = $after[ $i ];
			$expected = $old['offset'] === $token['offset'] ? $attrs : $old['attrs'];

			if ( $old['name'] !== $new['name'] || self::encode( $expected ) !== self::encode( $new['attrs'] ) ) {
				return self::unsafe();
			}
		}

		return $patched;
	}

	/**
	 * Encode attributes the way core's `serialize_block_attributes()` does.
	 *
	 * Reproduced rather than called so this class stays testable without
	 * WordPress, and checked against core's output in the integration tests. The
	 * replacements are what keep a value from closing the comment it lives in.
	 *
	 * @param \stdClass $attrs Attributes.
	 * @return string
	 */
	public static function encode( \stdClass $attrs ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- This class is deliberately WordPress-free so it can be unit tested; wp_json_encode() only adds a UTF-8 sanity retry that decoded post content does not need.
		$json = (string) json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		$json = (string) preg_replace( '/--/', '\\u002d\\u002d', $json );
		$json = (string) preg_replace( '/</', '\\u003c', $json );
		$json = (string) preg_replace( '/>/', '\\u003e', $json );
		$json = (string) preg_replace( '/&/', '\\u0026', $json );
		// Regex: /\\"/.
		return (string) preg_replace( '/\\\\"/', '\\u0022', $json );
	}

	/**
	 * Error for a path that does not lead anywhere in this block.
	 *
	 * @return \WP_Error
	 */
	private static function bad_path(): \WP_Error {
		return new \WP_Error(
			'flexa_block_bad_path',
			__( 'That field does not exist on this block.', 'flexa-block' ),
			[ 'status' => 400 ]
		);
	}

	/**
	 * Error for a splice whose result did not read back as intended.
	 *
	 * @return \WP_Error
	 */
	private static function unsafe(): \WP_Error {
		return new \WP_Error(
			'flexa_block_patch_unsafe',
			__( 'This change could not be made without risking the rest of the page, so nothing was written. Make it in the editor instead.', 'flexa-block' ),
			[ 'status' => 409 ]
		);
	}
}
