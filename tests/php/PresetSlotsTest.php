<?php
/**
 * Tests for the preset slot layer.
 *
 * What is covered here is the decision: given what a preset declares and what a
 * caller sent, what does each slot actually become, and which values are refused
 * outright. That is the half of the layer with the security properties, and it
 * needs no WordPress to run.
 *
 * What is NOT covered here is `Preset_Slots::apply()`, which parses block markup
 * and serializes it back. A faithful `parse_blocks()` cannot be stubbed, and a
 * rough one would turn a passing test into a statement about the stub. That half
 * is verified against a real install instead: the markup round trip, the
 * escaping of quotes and `-->` inside an attribute, and the full import through
 * the admin button.
 *
 * @package Flexa\Block
 */

use PHPUnit\Framework\TestCase;
use Flexa\Block\Import\Preset_Slots;

/**
 * @covers \Flexa\Block\Import\Preset_Slots
 */
class PresetSlotsTest extends TestCase {

	/**
	 * A definition declaring one slot of the given type.
	 *
	 * @param string               $type  Slot type.
	 * @param array<string, mixed> $extra Extra declaration keys.
	 * @return array<string, mixed>
	 */
	private function preset( string $type, array $extra = [] ): array {
		return [
			'id'            => 'fixture',
			'slot_contract' => 1,
			'slots'         => [
				'one' => array_merge(
					[
						'type'    => $type,
						'label'   => 'The slot',
						'default' => 'shipped',
					],
					$extra
				),
			],
			'content'       => '<!-- wp:flexa/heading {"content":"{{slot:one}}"} /-->',
		];
	}

	/**
	 * The value one slot resolves to, asserting the call succeeded.
	 *
	 * @param array<string, mixed> $definition Preset definition.
	 * @param mixed                $sent       Value for slot `one`.
	 * @return string
	 */
	private function resolved( array $definition, $sent ): string {
		$values = Preset_Slots::values( $definition, [ 'one' => $sent ] );
		$this->assertIsArray( $values, 'expected the value to be accepted' );
		return (string) $values['one'];
	}

	/**
	 * The error code one slot's value is refused with.
	 *
	 * @param array<string, mixed> $definition Preset definition.
	 * @param array<string, mixed> $raw        Values as a caller would send them.
	 * @return WP_Error
	 */
	private function refused( array $definition, array $raw ): WP_Error {
		$result = Preset_Slots::values( $definition, $raw );
		$this->assertInstanceOf( WP_Error::class, $result, 'expected the value to be refused' );
		return $result;
	}

	protected function setUp(): void {
		$GLOBALS['flexa_test_attachments'] = [];
		$GLOBALS['flexa_test_readable']    = [];
	}

	/**
	 * Reading what a preset declares, and dropping what it got wrong.
	 */
	public function test_a_preset_without_slots_declares_none(): void {
		$this->assertSame( [], Preset_Slots::declared( [ 'id' => 'x' ] ) );
		$this->assertSame(
			[],
			Preset_Slots::declared(
				[
					'id'    => 'x',
					'slots' => [],
				]
			)
		);
	}

	public function test_a_declaration_is_normalized(): void {
		$declared = Preset_Slots::declared( $this->preset( 'text', [ 'help' => 'Hi' ] ) );

		$this->assertSame(
			[
				'type'    => 'text',
				'label'   => 'The slot',
				'default' => 'shipped',
				'max'     => Preset_Slots::TYPES['text'],
				'help'    => 'Hi',
			],
			$declared['one']
		);
	}

	public function test_a_missing_label_falls_back_to_the_key(): void {
		$preset = $this->preset( 'text' );
		unset( $preset['slots']['one']['label'] );

		$this->assertSame( 'one', Preset_Slots::declared( $preset )['one']['label'] );
	}

	public function test_a_declaration_may_tighten_its_limit_but_not_raise_it(): void {
		$this->assertSame( 20, Preset_Slots::declared( $this->preset( 'text', [ 'max' => 20 ] ) )['one']['max'] );

		// Above the type's ceiling, and below one, both get the ceiling: the
		// type is what bounds a slot, and a preset only gets to be stricter.
		$ceiling = Preset_Slots::TYPES['text'];
		$this->assertSame( $ceiling, Preset_Slots::declared( $this->preset( 'text', [ 'max' => 99999 ] ) )['one']['max'] );
		$this->assertSame( $ceiling, Preset_Slots::declared( $this->preset( 'text', [ 'max' => 0 ] ) )['one']['max'] );
	}

	public function test_a_malformed_declaration_is_dropped(): void {
		foreach (
			[
				'unknown type' => [ 'type' => 'colour' ],
				'no type'      => [ 'type' => null ],
				'not an array' => 'just a string',
			] as $label => $spec
		) {
			$preset                 = $this->preset( 'text' );
			$preset['slots']['one'] = is_array( $spec )
				? array_merge( $preset['slots']['one'], $spec )
				: $spec;

			$this->assertSame( [], Preset_Slots::declared( $preset ), $label );
		}
	}

	public function test_a_declaration_without_a_default_is_dropped(): void {
		$preset = $this->preset( 'text' );
		unset( $preset['slots']['one']['default'] );

		// Deliberate: without a default there is no way to import the preset
		// with the field left blank, and the admin's own import button sends
		// nothing at all.
		$this->assertSame( [], Preset_Slots::declared( $preset ) );
	}

	public function test_a_key_that_is_not_a_slug_is_dropped(): void {
		$preset          = $this->preset( 'text' );
		$preset['slots'] = [ 'Not A Key!' => $preset['slots']['one'] ];

		$this->assertSame( [], Preset_Slots::declared( $preset ) );
	}

	public function test_no_more_slots_than_the_cap(): void {
		$preset          = $this->preset( 'text' );
		$spec            = $preset['slots']['one'];
		$preset['slots'] = [];
		for ( $i = 0; $i < Preset_Slots::MAX_SLOTS + 10; $i++ ) {
			$preset['slots'][ 'slot_' . $i ] = $spec;
		}

		$this->assertCount( Preset_Slots::MAX_SLOTS, Preset_Slots::declared( $preset ) );
	}

	/**
	 * The version check that lets a slot be withdrawn without breaking a preset.
	 */
	public function test_slots_without_a_contract_are_not_offered(): void {
		$preset = $this->preset( 'text' );
		unset( $preset['slot_contract'] );

		$this->assertSame( [], Preset_Slots::declared( $preset ) );
	}

	public function test_a_newer_contract_withdraws_the_slots_and_keeps_the_defaults(): void {
		$preset                  = $this->preset( 'text' );
		$preset['slot_contract'] = Preset_Slots::CONTRACT + 1;

		// Nothing is offered to a caller...
		$this->assertSame( [], Preset_Slots::declared( $preset ) );

		// ...and nothing is taken from one either. The preset's own wording is
		// what fills its markup, so the import is the page its author wrote
		// rather than a page with `{{slot:one}}` printed on it.
		$this->assertSame(
			[ 'one' => 'shipped' ],
			Preset_Slots::values( $preset, [ 'one' => 'mine' ] )
		);
	}

	/**
	 * What each type accepts, and what it turns the value into.
	 */
	public function test_text_keeps_plain_text(): void {
		$this->assertSame( 'Talk to us', $this->resolved( $this->preset( 'text' ), 'Talk to us' ) );
	}

	public function test_text_is_trimmed(): void {
		$this->assertSame( 'Talk to us', $this->resolved( $this->preset( 'text' ), "  Talk to us \t" ) );
	}

	public function test_text_loses_newlines_but_multiline_keeps_them(): void {
		$this->assertStringNotContainsString( "\n", $this->resolved( $this->preset( 'text' ), "one\ntwo" ) );
		$this->assertStringContainsString( "\n", $this->resolved( $this->preset( 'multiline' ), "one\ntwo" ) );
	}

	public function test_email_accepts_an_address(): void {
		$this->assertSame( 'hi@example.com', $this->resolved( $this->preset( 'email' ), 'hi@example.com' ) );
	}

	public function test_email_refuses_anything_else(): void {
		foreach ( [ 'hi@example', 'hi at example.com', 'hi@@example.com', '@example.com' ] as $bad ) {
			$error = $this->refused( $this->preset( 'email' ), [ 'one' => $bad ] );
			$this->assertSame( 'flexa_slot_invalid', $error->get_error_code(), $bad );
		}
	}

	public function test_url_accepts_the_schemes_a_preset_can_use(): void {
		foreach ( [ 'https://example.com/x', 'http://example.com', 'mailto:hi@example.com', 'tel:+15550001234' ] as $good ) {
			$this->assertSame( $good, $this->resolved( $this->preset( 'url' ), $good ) );
		}
	}

	public function test_url_refuses_a_scheme_a_preset_cannot_use(): void {
		// javascript: in a block attribute that ends up in an href is the whole
		// reason this type is not just 'text'.
		$error = $this->refused( $this->preset( 'url' ), [ 'one' => 'javascript:alert(1)' ] );
		$this->assertSame( 'flexa_slot_invalid', $error->get_error_code() );
	}

	public function test_phone_keeps_how_it_was_written(): void {
		$this->assertSame( '+84 (28) 3822 1234', $this->resolved( $this->preset( 'phone' ), '+84 (28) 3822 1234' ) );
	}

	public function test_phone_needs_a_digit(): void {
		$error = $this->refused( $this->preset( 'phone' ), [ 'one' => 'call us maybe' ] );
		$this->assertSame( 'flexa_slot_invalid', $error->get_error_code() );
	}

	public function test_media_accepts_an_image_the_caller_can_read(): void {
		$GLOBALS['flexa_test_attachments'] = [ 7 => true ];
		$GLOBALS['flexa_test_readable']    = [ 7 ];

		$this->assertSame( '7', $this->resolved( $this->preset( 'media', [ 'default' => '0' ] ), 7 ) );
	}

	public function test_media_refuses_an_attachment_the_caller_cannot_read(): void {
		// The one that matters. A readable id would otherwise be a way to pull
		// any private attachment's URL out of the library through a preset.
		$GLOBALS['flexa_test_attachments'] = [ 7 => true ];
		$GLOBALS['flexa_test_readable']    = [];

		$error = $this->refused( $this->preset( 'media', [ 'default' => '0' ] ), [ 'one' => 7 ] );
		$this->assertSame( 'flexa_slot_invalid', $error->get_error_code() );
		$this->assertStringContainsString( 'not one you can use', $error->get_error_message() );
	}

	public function test_media_refuses_a_non_image_and_a_missing_attachment(): void {
		$GLOBALS['flexa_test_attachments'] = [ 8 => false ];
		$GLOBALS['flexa_test_readable']    = [ 8, 9 ];

		foreach ( [ 8, 9, 0, -1, 'eight' ] as $bad ) {
			$error = $this->refused( $this->preset( 'media', [ 'default' => '0' ] ), [ 'one' => $bad ] );
			$this->assertSame( 'flexa_slot_invalid', $error->get_error_code(), (string) $bad );
		}
	}

	/**
	 * The two limits: one per slot, one across the request.
	 */
	public function test_a_value_at_the_limit_is_accepted(): void {
		$at = str_repeat( 'a', 20 );
		$this->assertSame( $at, $this->resolved( $this->preset( 'text', [ 'max' => 20 ] ), $at ) );
	}

	public function test_a_value_over_the_limit_is_refused_rather_than_truncated(): void {
		$error = $this->refused( $this->preset( 'text', [ 'max' => 20 ] ), [ 'one' => str_repeat( 'a', 21 ) ] );

		$this->assertSame( 'flexa_slot_invalid', $error->get_error_code() );
		$this->assertSame( 400, $error->get_error_data()['status'] );
		// The message has to name the field, because a dialog with eight inputs
		// is otherwise a guessing game.
		$this->assertStringContainsString( 'The slot', $error->get_error_message() );
		$this->assertStringContainsString( '20', $error->get_error_message() );
	}

	public function test_the_limit_counts_characters_not_bytes(): void {
		// Twenty Vietnamese characters is sixty-odd bytes. A byte limit would
		// refuse this and accept the same sentence in English, which is not a
		// rule anybody would have chosen on purpose.
		$twenty = 'ăăăăăăăăăăăăăăăăăăăă';
		$this->assertSame( 20, mb_strlen( $twenty ) );
		$this->assertSame( $twenty, $this->resolved( $this->preset( 'text', [ 'max' => 20 ] ), $twenty ) );
	}

	public function test_the_whole_payload_is_capped(): void {
		$error = $this->refused(
			$this->preset( 'multiline' ),
			[ 'one' => str_repeat( 'a', Preset_Slots::MAX_TOTAL_BYTES + 1 ) ]
		);

		// Checked before any single value, so a caller cannot get around the
		// ceiling by spreading one payload across many slots.
		$this->assertSame( 'flexa_slot_payload', $error->get_error_code() );
		$this->assertSame( 400, $error->get_error_data()['status'] );
	}

	/**
	 * Keys the caller left out, left blank, or made up.
	 */
	public function test_a_slot_nobody_filled_takes_the_default(): void {
		$this->assertSame(
			[ 'one' => 'shipped' ],
			Preset_Slots::values( $this->preset( 'text' ), [] )
		);
	}

	public function test_a_blank_value_takes_the_default(): void {
		foreach ( [ '', '   ', "\n", "\t " ] as $blank ) {
			$this->assertSame( 'shipped', $this->resolved( $this->preset( 'text' ), $blank ) );
		}
	}

	public function test_a_value_that_sanitizes_to_nothing_takes_the_default(): void {
		// Not a refusal: the user typed something, but none of it was text. The
		// preset's own wording is a better answer than an empty heading.
		$this->assertSame( 'shipped', $this->resolved( $this->preset( 'text' ), '<b></b>' ) );
	}

	public function test_every_declared_slot_comes_back_with_a_value(): void {
		$preset          = $this->preset( 'text' );
		$preset['slots'] = [
			'a' => [
				'type'    => 'text',
				'label'   => 'A',
				'default' => 'da',
			],
			'b' => [
				'type'    => 'text',
				'label'   => 'B',
				'default' => 'db',
			],
		];

		// Nothing may come back missing, because a key with no value leaves a
		// token in the markup with nothing to replace it.
		$this->assertSame(
			[
				'a' => 'mine',
				'b' => 'db',
			],
			Preset_Slots::values( $preset, [ 'a' => 'mine' ] )
		);
	}

	public function test_a_slot_the_preset_never_declared_is_refused(): void {
		$error = $this->refused( $this->preset( 'text' ), [ 'nope' => 'x' ] );

		// Rather than ignored. A caller sending a key nobody declared has the
		// wrong idea about the preset, and silence would let them believe it
		// worked.
		$this->assertSame( 'flexa_slot_unknown', $error->get_error_code() );
		$this->assertStringContainsString( 'nope', $error->get_error_message() );
	}

	public function test_a_value_that_is_not_a_string_is_refused(): void {
		foreach ( [ [ 'a' ], [ 'nested' => [ 1 ] ] ] as $bad ) {
			$error = $this->refused( $this->preset( 'text' ), [ 'one' => $bad ] );
			$this->assertSame( 'flexa_slot_invalid', $error->get_error_code() );
		}
	}

	/**
	 * Values that try to be something other than text.
	 */
	public function test_a_script_tag_does_not_survive_a_text_slot(): void {
		$value = $this->resolved( $this->preset( 'text' ), '<script>alert(1)</script>Hello' );

		$this->assertSame( 'Hello', $value );
		$this->assertStringNotContainsString( '<', $value );
	}

	public function test_markup_does_not_survive_a_text_slot(): void {
		foreach (
			[
				'<b>Bold</b>'                             => 'Bold',
				'<img src=x onerror=alert(1)>Hi'          => 'Hi',
				'<a href="javascript:alert(1)">Click</a>' => 'Click',
				'<svg onload=alert(1)></svg>Text'         => 'Text',
			]
			as $sent => $expected
		) {
			$this->assertSame( $expected, $this->resolved( $this->preset( 'text' ), $sent ) );
		}
	}

	public function test_a_block_delimiter_in_a_slot_stays_text(): void {
		// Block attributes are JSON inside an HTML comment, so a value carrying
		// `-->` is the one that would break out of the comment if it were pasted
		// into markup as a string. It is not: substitution happens on the parsed
		// tree and `serialize_blocks()` does the encoding. Here we only assert
		// that nothing strips or rewrites it on the way, so what reaches the
		// serializer is exactly what the user typed.
		$sent = 'He said "hi" --> and left';
		$this->assertSame( $sent, $this->resolved( $this->preset( 'text' ), $sent ) );
	}

	public function test_a_media_placeholder_in_a_slot_is_just_text(): void {
		// Media placeholders are resolved before slots are applied, so a value
		// shaped like one is never looked at again.
		$sent = '{{flexa-media:hero}}';
		$this->assertSame( $sent, $this->resolved( $this->preset( 'text' ), $sent ) );
	}

	public function test_a_slot_token_inside_a_slot_value_is_not_expanded(): void {
		// Substitution is one pass over the tree, so a value naming another slot
		// cannot make a second one happen.
		$sent = '{{slot:one}}';
		$this->assertSame( $sent, $this->resolved( $this->preset( 'text' ), $sent ) );
	}

	/**
	 * What the importer stores about a slot being filled.
	 */
	public function test_only_the_slots_a_user_filled_are_recorded(): void {
		$declared = Preset_Slots::declared( $this->preset( 'text' ) );

		$this->assertSame( [ 'one' ], Preset_Slots::filled_keys( $declared, [ 'one' => 'mine' ] ) );
		$this->assertSame( [], Preset_Slots::filled_keys( $declared, [] ) );
		$this->assertSame( [], Preset_Slots::filled_keys( $declared, [ 'one' => '  ' ] ) );
	}
}
