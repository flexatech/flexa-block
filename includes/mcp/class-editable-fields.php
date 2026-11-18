<?php
declare(strict_types=1);
/**
 * Which attributes of which blocks an agent may change, and what they hold.
 *
 * An allowlist, and short on purpose. Every entry is a piece of content a
 * person would describe in words: a heading, a button's label and link, the
 * address a form sends to. Nothing about how a block looks is here: no colour,
 * spacing, typography, class name, icon markup or custom CSS. Those are where a
 * value can carry markup or script, and they are not what anyone asks an
 * assistant to "change on my page".
 *
 * A field is addressed by a dotted path into the block's attributes. `*` stands
 * for "any entry of this list", so `items.*.text` covers the text of every row
 * of an icon list, and a caller names one row as `items.2.text`.
 *
 * Only Flexa blocks. Core blocks keep their text in saved HTML rather than in
 * attributes, and changing it would mean editing markup, which this module
 * never does.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The editable-field allowlist and the helpers that read it.
 */
class Editable_Fields {

	/**
	 * Fields per block: path pattern => [ type, label, line breaks become <br> ].
	 *
	 * Types are the slot types, so a value is validated exactly as a preset
	 * field would be. Every text attribute listed here is rendered through
	 * `wp_kses()`, so plain text is always valid in it; the third flag marks the
	 * few that are paragraphs, where a line break should survive as one.
	 *
	 * @var array<string, array<string, array{0:string, 1:string, 2?:bool}>>
	 */
	const MAP = [
		'flexa/heading'        => [
			'content'           => [ 'text', 'Heading' ],
			'subheadingContent' => [ 'text', 'Subheading' ],
			'url'               => [ 'url', 'Heading link' ],
		],
		'flexa/text'           => [
			'content' => [ 'multiline', 'Text', true ],
		],
		'flexa/button'         => [
			'text' => [ 'text', 'Button text' ],
			'url'  => [ 'url', 'Button link' ],
		],
		'flexa/info-box'       => [
			'prefix'      => [ 'text', 'Prefix' ],
			'title'       => [ 'text', 'Title' ],
			'description' => [ 'multiline', 'Description' ],
			'buttonText'  => [ 'text', 'Button text' ],
			'buttonUrl'   => [ 'url', 'Button link' ],
		],
		'flexa/icon-list'      => [
			'items.*.text'     => [ 'text', 'List item text' ],
			'items.*.link.url' => [ 'url', 'List item link' ],
		],
		'flexa/cta'            => [
			'heading'       => [ 'text', 'Heading' ],
			'description'   => [ 'multiline', 'Description' ],
			'primaryText'   => [ 'text', 'Primary button text' ],
			'primaryUrl'    => [ 'url', 'Primary button link' ],
			'secondaryText' => [ 'text', 'Secondary button text' ],
			'secondaryUrl'  => [ 'url', 'Secondary button link' ],
		],
		'flexa/banner'         => [
			'heading'       => [ 'text', 'Heading' ],
			'description'   => [ 'multiline', 'Description' ],
			'primaryText'   => [ 'text', 'Primary button text' ],
			'primaryUrl'    => [ 'url', 'Primary button link' ],
			'secondaryText' => [ 'text', 'Secondary button text' ],
			'secondaryUrl'  => [ 'url', 'Secondary button link' ],
		],
		'flexa/faq'            => [
			'items.*.question' => [ 'text', 'Question' ],
			'items.*.answer'   => [ 'multiline', 'Answer', true ],
		],
		'flexa/testimonial'    => [
			'title'      => [ 'text', 'Title' ],
			'quote'      => [ 'multiline', 'Quote' ],
			'authorName' => [ 'text', 'Author name' ],
		],
		'flexa/counter'        => [
			'items.*.label'        => [ 'text', 'Counter label' ],
			'items.*.number.value' => [ 'text', 'Counter number' ],
		],
		'flexa/team-member'    => [
			'name' => [ 'text', 'Name' ],
			'role' => [ 'text', 'Role' ],
		],
		'flexa/steps'          => [
			'items.*.title'       => [ 'text', 'Step title' ],
			'items.*.description' => [ 'multiline', 'Step description' ],
		],
		'flexa/notice'         => [
			'title'   => [ 'text', 'Title' ],
			'content' => [ 'multiline', 'Message' ],
		],
		'flexa/timeline'       => [
			'items.*.title'       => [ 'text', 'Event title' ],
			'items.*.description' => [ 'multiline', 'Event description' ],
			'items.*.date'        => [ 'text', 'Event date' ],
		],
		'flexa/subscribe-form' => [
			'submitText'     => [ 'text', 'Submit button text' ],
			'toEmail'        => [ 'email', 'Send messages to' ],
			'emailSubject'   => [ 'text', 'Email subject' ],
			'successMessage' => [ 'text', 'Success message' ],
			'errorMessage'   => [ 'text', 'Error message' ],
			'redirectUrl'    => [ 'url', 'Redirect after sending' ],
		],
		'flexa/google-map'     => [
			'location' => [ 'text', 'Map location' ],
		],
		'flexa/image'          => [
			'altText' => [ 'text', 'Image description (alt text)' ],
		],
	];

	/**
	 * Whether any field of this block may be edited.
	 *
	 * @param string $block Block name.
	 * @return bool
	 */
	public static function supports( string $block ): bool {
		return isset( self::MAP[ $block ] );
	}

	/**
	 * The description of one concrete field path, or null when it is not editable.
	 *
	 * A concrete path is matched against the patterns by treating each list
	 * index as `*`. Anything that does not match an entry exactly is refused,
	 * which is the whole allowlist: there is no prefix matching, so permission
	 * for `items.*.text` grants nothing about `items.*.icon`.
	 *
	 * @param string $block Block name.
	 * @param string $path  Dotted path, for example `items.2.text`.
	 * @return array{type:string, label:string, default:string, max:int, br:bool, pattern:string}|null
	 */
	public static function spec( string $block, string $path ): ?array {
		if ( ! isset( self::MAP[ $block ] ) || ! preg_match( '/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)*$/', $path ) ) {
			return null;
		}

		$pattern = (string) preg_replace( '/(?<=^|\.)\d+(?=\.|$)/', '*', $path );
		if ( ! isset( self::MAP[ $block ][ $pattern ] ) ) {
			return null;
		}

		$entry = self::MAP[ $block ][ $pattern ];

		return [
			'type'    => $entry[0],
			'label'   => $entry[1],
			'default' => '',
			'max'     => 0,
			'br'      => ! empty( $entry[2] ),
			'pattern' => $pattern,
		];
	}

	/**
	 * Every concrete editable path a block has, given its attributes.
	 *
	 * A pattern with `*` is expanded over the entries the list actually has.
	 * An attribute that is absent is still listed when its pattern has no `*`:
	 * a block stores nothing for a value equal to its default, so "absent" is
	 * the ordinary state of a field nobody changed, not a missing field.
	 *
	 * @param string    $block Block name.
	 * @param \stdClass $attrs Attributes, with defaults already filled in.
	 * @return list<string>
	 */
	public static function paths( string $block, \stdClass $attrs ): array {
		$out = [];
		foreach ( array_keys( self::MAP[ $block ] ?? [] ) as $pattern ) {
			foreach ( self::expand( explode( '.', $pattern ), $attrs, [] ) as $path ) {
				$out[] = $path;
			}
		}
		return $out;
	}

	/**
	 * Expand one pattern's `*` segments over a decoded value.
	 *
	 * @param list<string> $segments Remaining pattern segments.
	 * @param mixed        $node     Value at the current position.
	 * @param list<string> $prefix   Concrete segments so far.
	 * @return list<string>
	 */
	private static function expand( array $segments, $node, array $prefix ): array {
		if ( ! $segments ) {
			return [ implode( '.', $prefix ) ];
		}

		$head = array_shift( $segments );

		if ( '*' === $head ) {
			if ( ! is_array( $node ) ) {
				return [];
			}
			$out = [];
			foreach ( array_keys( $node ) as $i ) {
				foreach ( self::expand( $segments, $node[ $i ], array_merge( $prefix, [ (string) $i ] ) ) as $path ) {
					$out[] = $path;
				}
			}
			return $out;
		}

		$child = $node instanceof \stdClass && property_exists( $node, $head ) ? $node->{$head} : null;

		// A missing leaf is listed; a missing container below a list is not,
		// because there would be nothing to put the value in.
		if ( null === $child && $segments ) {
			return [];
		}

		return self::expand( $segments, $child, array_merge( $prefix, [ $head ] ) );
	}
}
