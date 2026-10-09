<?php
declare(strict_types=1);
/**
 * Preset slots: the few values a user fills in before importing a preset.
 *
 * A preset is fixed markup. A slot is one spot in it the importer is allowed to
 * change: the heading of a contact page, the address the form sends to. Nothing
 * else about the preset is negotiable, which is the whole point. The caller
 * cannot reach a block, an attribute or a selector; it can only hand over values
 * for the slots the preset itself declared.
 *
 * How a slot is marked: the preset puts `{{slot:key}}` in the attribute value
 * where the text belongs. No block paths, no indices. An index path would mean
 * that reordering two sections in a preset silently retargets a slot, and the
 * result of that mistake is a page with its heading in the footer and no error
 * anywhere. A token sits on the value it replaces and survives any reordering.
 * It also matches what this plugin already does for media, where a placeholder
 * string in the markup is swapped for a sideloaded URL.
 *
 * Why substitution happens on the parsed block tree rather than on the markup
 * string: a block's attributes live as JSON inside an HTML comment, so a value
 * carrying a quote, a brace or a newline would break the comment if it were
 * pasted in as text. Replacing on the tree and letting `serialize_blocks()`
 * re-encode puts the escaping in core's hands. This is safe here for one
 * specific reason: every block in this plugin is dynamic and no attribute uses
 * `source`, so all the content a preset would want to parameterise is in
 * attributes, and none of it is in the saved markup.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Declares, validates and applies preset slots.
 */
class Preset_Slots {

	/**
	 * Slot contract version this code speaks.
	 *
	 * A preset declares the version it was written against. Raising this number
	 * is the only way to change what a declaration or a token means, and the
	 * check below is what stops a later Flexa Block from reading an older
	 * preset's slots as something they never were. See `declared()`.
	 */
	const CONTRACT = 1;

	/** Post meta: slot contract version a post was imported under. */
	const CONTRACT_META = '_flexa_slot_contract';

	/** Post meta: the slot keys that were actually filled in. */
	const FILLED_META = '_flexa_slot_keys';

	/**
	 * Slot types and the longest value each accepts.
	 *
	 * A preset may tighten a limit per slot; a `max` above the ceiling gets the
	 * ceiling instead. The numbers are character counts, not bytes, so a name in
	 * Vietnamese or Japanese is not worth a third of a name in English.
	 */
	const TYPES = [
		'text'      => 300,
		'multiline' => 2000,
		'url'       => 500,
		'email'     => 254,
		'phone'     => 40,
		'media'     => 0,
	];

	/**
	 * Derived forms a token may ask for, and the types that offer them.
	 *
	 * One entry, and it exists because a phone number appears twice on a contact
	 * page: once as text the visitor reads, once inside a `tel:` URL that cannot
	 * hold the spaces and brackets the text wants. Without this the two would
	 * disagree, and a contact page whose phone link dials the wrong number is
	 * worse than one with no link.
	 */
	const VARIANTS = [
		'tel' => [ 'phone' ],
	];

	/** Longest a single request's slot values may be, in bytes, all slots together. */
	const MAX_TOTAL_BYTES = 8192;

	/** Most slots one preset may declare. */
	const MAX_SLOTS = 25;

	/**
	 * Matches one token: `{{slot:key}}` or `{{slot:key|variant}}`.
	 */
	const TOKEN_RE = '/\{\{slot:([a-z0-9_]+)(?:\|([a-z0-9_]+))?\}\}/';

	/**
	 * The slots a preset declares, normalized, or an empty array.
	 *
	 * A declaration is dropped rather than repaired. A preset ships inside a
	 * plugin, so a malformed one is a bug to be seen in testing, and the loud
	 * version of that is a slot that does not appear in the import form at all.
	 * Repairing it quietly would hide the bug until a user's page came out wrong.
	 *
	 * Every surviving declaration has a default. That is what keeps a preset
	 * importable with no input at all: the admin import button, which sends
	 * nothing, still produces exactly the page the preset ships.
	 *
	 * Returns nothing for a preset whose declared contract this code does not
	 * speak, which is how a slot is withdrawn: it is offered to nobody and
	 * accepted from nobody. Substitution still happens, from the defaults, so
	 * such a preset imports as its author wrote it. See `apply()`.
	 *
	 * @param array<string, mixed> $definition Full preset definition.
	 * @return array<string, array{type:string, label:string, default:string, max:int, help:string}>
	 */
	public static function declared( array $definition ): array {
		return self::supported( $definition ) ? self::normalize( $definition ) : [];
	}

	/**
	 * Whether this code speaks the contract a preset declares.
	 *
	 * A preset that declares slots has to say which contract it wrote them
	 * against. One this code does not know is not guessed at: the declarations
	 * may mean something there that they do not mean here, and acting on them
	 * would be inventing the author's intent. In practice this can only happen
	 * with an add-on's preset on an older Flexa Block.
	 *
	 * @param array<string, mixed> $definition Full preset definition.
	 * @return bool
	 */
	private static function supported( array $definition ): bool {
		$contract = isset( $definition['slot_contract'] ) ? (int) $definition['slot_contract'] : 0;
		return $contract >= 1 && $contract <= self::CONTRACT;
	}

	/**
	 * Read a preset's declarations without asking about the contract.
	 *
	 * Separate from `declared()` for one case: a preset whose contract is out of
	 * range still has tokens in its markup, and those have to be replaced by
	 * something. Read under this contract's rules they give up their defaults,
	 * which is the preset as shipped. Any declaration that does not parse here
	 * is dropped, and its token is swept to nothing with a warning.
	 *
	 * @param array<string, mixed> $definition Full preset definition.
	 * @return array<string, array{type:string, label:string, default:string, max:int, help:string}>
	 */
	private static function normalize( array $definition ): array {
		$slots = $definition['slots'] ?? null;
		if ( ! is_array( $slots ) || ! $slots ) {
			return [];
		}

		$out = [];
		foreach ( $slots as $key => $spec ) {
			if ( count( $out ) >= self::MAX_SLOTS ) {
				break;
			}

			$key = is_string( $key ) ? $key : '';
			if ( ! preg_match( '/^[a-z0-9_]+$/', $key ) || ! is_array( $spec ) ) {
				continue;
			}

			$type = isset( $spec['type'] ) ? (string) $spec['type'] : '';
			if ( ! isset( self::TYPES[ $type ] ) ) {
				continue;
			}

			// No default means no safe way to import without input, so the slot
			// does not exist. A preset that genuinely has nothing to fall back
			// on declares an empty string and says so in its help text.
			if ( ! array_key_exists( 'default', $spec ) ) {
				continue;
			}

			$ceiling = self::TYPES[ $type ];
			$max     = isset( $spec['max'] ) ? (int) $spec['max'] : $ceiling;
			if ( $max < 1 || $max > $ceiling ) {
				$max = $ceiling;
			}

			$out[ $key ] = [
				'type'    => $type,
				'label'   => isset( $spec['label'] ) ? (string) $spec['label'] : $key,
				'default' => 'media' === $type ? (string) (int) $spec['default'] : (string) $spec['default'],
				'max'     => $max,
				'help'    => isset( $spec['help'] ) ? (string) $spec['help'] : '',
			];
		}

		return $out;
	}

	/**
	 * Apply the caller's slot values to a preset's markup.
	 *
	 * The single gate. Declaration reading, validation and substitution all
	 * happen here, so there is no call sequence a caller can get wrong and no
	 * way to reach substitution with values nothing checked.
	 *
	 * @param array<string, mixed> $definition Full preset definition.
	 * @param string               $content    Preset block markup.
	 * @param array<string, mixed> $raw        Values as the caller sent them.
	 * @param list<string>         $warnings   Collected warnings, by reference.
	 * @return string|\WP_Error Markup with slots filled, or the first failure.
	 */
	public static function apply( array $definition, string $content, array $raw, array &$warnings ) {
		$declared = self::normalize( $definition );
		if ( ! $declared ) {
			// No slots at all: hand the markup back untouched rather than
			// round-tripping it through the parser. A preset without slots must
			// import byte for byte as it always did.
			if ( $raw ) {
				$warnings[] = __( 'This preset takes no slot values; the ones supplied were ignored.', 'flexa-block' );
			}
			return $content;
		}

		$values = self::values( $definition, $raw );
		if ( is_wp_error( $values ) ) {
			return $values;
		}

		if ( ! self::supported( $definition ) ) {
			$warnings[] = __( 'This preset was built for a newer version of Flexa Block, so its fields were skipped and its own wording kept.', 'flexa-block' );
		}

		$blocks = parse_blocks( $content );
		$blocks = self::fill( $blocks, $declared, $values, $warnings );

		// A token in saved markup is reached through both innerHTML and
		// innerContent, so one mistake in a preset otherwise reports itself
		// twice. The reader needs to know it once.
		$warnings = array_values( array_unique( $warnings ) );

		return serialize_blocks( $blocks );
	}

	/**
	 * Which declared slots the caller actually filled in.
	 *
	 * Recorded on the imported post so a later version can tell a page that was
	 * customised at import time from one that took the preset as it shipped.
	 *
	 * @param array<string, array<string, mixed>> $declared Normalized declarations.
	 * @param array<string, mixed>                $raw      Values as the caller sent them.
	 * @return list<string>
	 */
	public static function filled_keys( array $declared, array $raw ): array {
		$keys = [];
		foreach ( $declared as $key => $spec ) {
			if ( isset( $raw[ $key ] ) && '' !== trim( (string) $raw[ $key ] ) ) {
				$keys[] = $key;
			}
		}
		return $keys;
	}

	/**
	 * What each of a preset's slots will actually be set to.
	 *
	 * Rejects rather than repairs: a value over its limit, an address that is
	 * not an address, an attachment the caller may not read. Truncating a
	 * heading to fit, or dropping a bad email on the floor, would hand back a
	 * page that looks finished and is not, and the caller would have no way to
	 * know. A blank value is not a failure, it means "keep what the preset has",
	 * and so every key the preset declares comes back with something in it.
	 *
	 * Public because this is the half of the work that can be tested on its own.
	 * `apply()` has to parse and re-serialize block markup, which takes a real
	 * WordPress; deciding what a value becomes takes nothing. Exposing it is not
	 * a way around validation either: this *is* the validation, and `apply()`
	 * calls it rather than offering a path that skips it.
	 *
	 * @param array<string, mixed> $definition Full preset definition.
	 * @param array<string, mixed> $raw        Values as the caller sent them.
	 * @return array<string, string>|\WP_Error
	 */
	public static function values( array $definition, array $raw ) {
		$declared = self::normalize( $definition );
		if ( ! $declared ) {
			return [];
		}

		// Contract out of range: the slots are withdrawn, so nothing the caller
		// sent is looked at and every slot takes the preset's own wording. The
		// markup's tokens still have to become something, and the author's text
		// is the one answer that cannot be wrong.
		if ( ! self::supported( $definition ) ) {
			return wp_list_pluck( $declared, 'default' );
		}

		return self::resolve( $declared, $raw );
	}

	/**
	 * Validate the caller's values against declarations already read.
	 *
	 * @param array<string, array<string, mixed>> $declared Normalized declarations.
	 * @param array<string, mixed>                $raw      Values as the caller sent them.
	 * @return array<string, string>|\WP_Error
	 */
	private static function resolve( array $declared, array $raw ) {
		$total = 0;
		foreach ( $raw as $value ) {
			if ( is_scalar( $value ) ) {
				$total += strlen( (string) $value );
			}
		}
		if ( $total > self::MAX_TOTAL_BYTES ) {
			return new \WP_Error(
				'flexa_slot_payload',
				sprintf(
					/* translators: %s: size limit in bytes */
					__( 'Those slot values are too large; the limit is %s bytes in total.', 'flexa-block' ),
					number_format_i18n( self::MAX_TOTAL_BYTES )
				),
				[ 'status' => 400 ]
			);
		}

		$unknown = array_diff( array_keys( $raw ), array_keys( $declared ) );
		if ( $unknown ) {
			return new \WP_Error(
				'flexa_slot_unknown',
				sprintf(
					/* translators: %s: comma-separated slot keys */
					__( 'This preset has no slot named %s.', 'flexa-block' ),
					implode( ', ', array_map( 'sanitize_key', $unknown ) )
				),
				[ 'status' => 400 ]
			);
		}

		$values = [];
		foreach ( $declared as $key => $spec ) {
			$sent = $raw[ $key ] ?? null;

			if ( null === $sent || ( is_string( $sent ) && '' === trim( $sent ) ) ) {
				$values[ $key ] = $spec['default'];
				continue;
			}

			if ( ! is_scalar( $sent ) ) {
				return self::reject( $spec, __( 'that is not a value this slot can take', 'flexa-block' ) );
			}

			$clean = self::clean( $spec, (string) $sent );
			if ( is_wp_error( $clean ) ) {
				return $clean;
			}

			$values[ $key ] = $clean;
		}

		return $values;
	}

	/**
	 * Check and normalize one value against its declared type.
	 *
	 * @param array<string, mixed> $spec  Normalized declaration.
	 * @param string               $value Raw value.
	 * @return string|\WP_Error
	 */
	private static function clean( array $spec, string $value ) {
		$type = (string) $spec['type'];

		if ( 'media' === $type ) {
			return self::clean_media( $spec, $value );
		}

		// Newlines are content in a multiline slot and smuggled layout anywhere
		// else, so a single-line slot loses them before anything else looks at
		// the value.
		$value = 'multiline' === $type
			? (string) sanitize_textarea_field( $value )
			: (string) sanitize_text_field( $value );

		$value = trim( $value );

		if ( '' === $value ) {
			return $spec['default'];
		}

		if ( mb_strlen( $value ) > (int) $spec['max'] ) {
			return self::reject(
				$spec,
				sprintf(
					/* translators: %s: character limit */
					__( 'keep it to %s characters or fewer', 'flexa-block' ),
					number_format_i18n( (int) $spec['max'] )
				)
			);
		}

		if ( 'email' === $type && ! is_email( $value ) ) {
			return self::reject( $spec, __( 'that is not an email address', 'flexa-block' ) );
		}

		if ( 'url' === $type ) {
			$url = esc_url_raw( $value, [ 'http', 'https', 'mailto', 'tel' ] );
			if ( '' === $url ) {
				return self::reject( $spec, __( 'that is not a web address', 'flexa-block' ) );
			}
			$value = $url;
		}

		if ( 'phone' === $type && ! preg_match( '/[0-9]/', $value ) ) {
			return self::reject( $spec, __( 'a phone number needs at least one digit', 'flexa-block' ) );
		}

		return $value;
	}

	/**
	 * Check a media slot's attachment ID.
	 *
	 * Three questions, and all three matter: does the attachment exist, is it an
	 * image, and may this caller read it. The last one is the one that is easy
	 * to forget and the one that turns a preset into a way to pull any private
	 * attachment's URL out of the library.
	 *
	 * @param array<string, mixed> $spec  Normalized declaration.
	 * @param string               $value Raw value.
	 * @return string|\WP_Error
	 */
	private static function clean_media( array $spec, string $value ) {
		$id = (int) $value;
		if ( $id < 1 ) {
			return self::reject( $spec, __( 'that is not a media ID', 'flexa-block' ) );
		}

		if ( 'attachment' !== get_post_type( $id ) || ! wp_attachment_is_image( $id ) ) {
			return self::reject( $spec, __( 'that media ID is not an image in this library', 'flexa-block' ) );
		}

		if ( ! current_user_can( 'read_post', $id ) ) {
			return self::reject( $spec, __( 'that media ID is not one you can use', 'flexa-block' ) );
		}

		return (string) $id;
	}

	/**
	 * One rejection, phrased so the person reading it knows which field to fix.
	 *
	 * @param array<string, mixed> $spec   Normalized declaration.
	 * @param string               $reason Lower-case reason clause.
	 * @return \WP_Error
	 */
	private static function reject( array $spec, string $reason ): \WP_Error {
		return new \WP_Error(
			'flexa_slot_invalid',
			sprintf(
				/* translators: 1: slot label, 2: reason the value was refused */
				__( '%1$s: %2$s.', 'flexa-block' ),
				(string) $spec['label'],
				$reason
			),
			[ 'status' => 400 ]
		);
	}

	/**
	 * Walk the block tree and substitute every token.
	 *
	 * Attributes only. A token anywhere else in a preset is a mistake rather
	 * than a feature: saved markup is escaped HTML, so a value substituted there
	 * would be read as markup instead of as text. Those tokens are swept to
	 * nothing below, with a warning, because a page missing a line of text is
	 * recoverable and a page displaying `{{slot:heading}}` to visitors is
	 * embarrassing.
	 *
	 * @param array<int, array<string, mixed>>    $blocks   Parsed blocks.
	 * @param array<string, array<string, mixed>> $declared Normalized declarations.
	 * @param array<string, string>               $values   Resolved values.
	 * @param list<string>                        $warnings Collected warnings, by reference.
	 * @return array<int, array<string, mixed>>
	 */
	private static function fill( array $blocks, array $declared, array $values, array &$warnings ): array {
		foreach ( $blocks as $i => $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			if ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) {
				$blocks[ $i ]['attrs'] = self::fill_value( $block['attrs'], $declared, $values, $warnings );
			}

			foreach ( [ 'innerHTML', 'innerContent' ] as $raw_key ) {
				if ( ! isset( $block[ $raw_key ] ) ) {
					continue;
				}
				$blocks[ $i ][ $raw_key ] = self::sweep( $block[ $raw_key ], $warnings );
			}

			if ( isset( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$blocks[ $i ]['innerBlocks'] = self::fill( $block['innerBlocks'], $declared, $values, $warnings );
			}
		}

		return $blocks;
	}

	/**
	 * Substitute tokens in one attribute value, however deeply it nests.
	 *
	 * @param mixed                               $value    Attribute value.
	 * @param array<string, array<string, mixed>> $declared Normalized declarations.
	 * @param array<string, string>               $values   Resolved values.
	 * @param list<string>                        $warnings Collected warnings, by reference.
	 * @return mixed
	 */
	private static function fill_value( $value, array $declared, array $values, array &$warnings ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::fill_value( $item, $declared, $values, $warnings );
			}
			return $value;
		}

		if ( ! is_string( $value ) || false === strpos( $value, '{{slot:' ) ) {
			return $value;
		}

		return (string) preg_replace_callback(
			self::TOKEN_RE,
			static function ( array $m ) use ( $declared, $values, &$warnings ): string {
				$key     = $m[1];
				$variant = $m[2] ?? '';

				if ( ! isset( $declared[ $key ] ) ) {
					$warnings[] = sprintf(
						/* translators: %s: slot key found in the preset markup */
						__( 'The preset refers to a slot it does not declare: %s.', 'flexa-block' ),
						$key
					);
					return '';
				}

				$resolved = $values[ $key ] ?? '';

				if ( '' === $variant ) {
					return $resolved;
				}

				$offered = self::VARIANTS[ $variant ] ?? null;
				if ( null === $offered || ! in_array( (string) $declared[ $key ]['type'], $offered, true ) ) {
					$warnings[] = sprintf(
						/* translators: 1: variant name, 2: slot key */
						__( 'The preset asks for an unsupported %1$s form of slot %2$s.', 'flexa-block' ),
						$variant,
						$key
					);
					return $resolved;
				}

				return self::variant( $variant, $resolved );
			},
			$value
		);
	}

	/**
	 * Derive one alternative form of a slot value.
	 *
	 * @param string $variant Variant name.
	 * @param string $value   Resolved value.
	 * @return string
	 */
	private static function variant( string $variant, string $value ): string {
		if ( 'tel' === $variant ) {
			$digits = (string) preg_replace( '/[^0-9]/', '', $value );
			if ( '' === $digits ) {
				return '';
			}
			// A leading + only if the original carried one, since adding one to
			// a local number would make it dial as international.
			return ( 0 === strpos( trim( $value ), '+' ) ? '+' : '' ) . $digits;
		}

		return $value;
	}

	/**
	 * Blank any token left somewhere substitution does not reach.
	 *
	 * @param mixed        $value    Value to sweep.
	 * @param list<string> $warnings Collected warnings, by reference.
	 * @return mixed
	 */
	private static function sweep( $value, array &$warnings ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::sweep( $item, $warnings );
			}
			return $value;
		}

		if ( ! is_string( $value ) || false === strpos( $value, '{{slot:' ) ) {
			return $value;
		}

		$warnings[] = __( 'The preset puts a slot in its markup instead of a block attribute; that spot was left empty.', 'flexa-block' );

		return (string) preg_replace( self::TOKEN_RE, '', $value );
	}
}
