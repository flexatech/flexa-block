<?php
/**
 * Tests for editing one block's attributes in place, and the field allowlist.
 *
 * The property these protect: changing one field of one block leaves every
 * other byte of the post exactly as it was, including the `{}` and `[]` that a
 * parse-and-reserialize round trip would confuse.
 *
 * @package Flexa\Block
 */

use PHPUnit\Framework\TestCase;
use Flexa\Block\Block_Patcher;
use Flexa\Block\MCP\Editable_Fields;

/**
 * Block patcher and editable-field allowlist.
 *
 * @covers \Flexa\Block\Block_Patcher
 * @covers \Flexa\Block\MCP\Editable_Fields
 */
class BlockPatcherTest extends TestCase {

	/**
	 * A small page: a heading, a container holding an icon list, and a closer.
	 *
	 * The container carries an empty object and an empty list, which is what a
	 * round trip through PHP arrays would turn into two empty lists.
	 *
	 * @return string
	 */
	private function page(): string {
		return '<!-- wp:flexa/heading {"blockId":"fx-aaaa1111","content":"Hello"} /-->' . "\n"
			. '<!-- wp:flexa/container {"blockId":"fx-bbbb2222","padding":{},"gap":[]} -->' . "\n"
			. '<div class="wp-block-flexa-container">'
			. '<!-- wp:flexa/icon-list {"blockId":"fx-cccc3333","items":[{"text":"One","link":{"url":""}},{"text":"Two","link":{"url":"https://a.test"}}]} /-->'
			. '</div>' . "\n"
			. '<!-- /wp:flexa/container -->' . "\n"
			. '<!-- wp:paragraph --><p>Core text</p><!-- /wp:paragraph -->';
	}

	/**
	 * Set one path on a located block and splice it back.
	 *
	 * @param string $content  Content.
	 * @param string $block_id Block id.
	 * @param string $path     Dotted path.
	 * @param mixed  $value    Value.
	 * @return string|WP_Error
	 */
	private function set( string $content, string $block_id, string $path, $value ) {
		$token = Block_Patcher::locate( $content, $block_id );
		$this->assertIsArray( $token );

		$attrs = Block_Patcher::with_value( $token['attrs'], explode( '.', $path ), $value );
		$this->assertInstanceOf( stdClass::class, $attrs );

		return Block_Patcher::replace( $content, $token, $attrs );
	}

	public function test_it_reads_openers_and_void_blocks_but_not_closers(): void {
		$names = array_column( Block_Patcher::tokens( $this->page() ), 'name' );

		$this->assertSame( [ 'flexa/heading', 'flexa/container', 'flexa/icon-list', 'core/paragraph' ], $names );
	}

	public function test_changing_a_field_touches_only_that_delimiter(): void {
		$before  = $this->page();
		$after   = $this->set( $before, 'fx-aaaa1111', 'content', 'Xin chào' );
		$heading = '<!-- wp:flexa/heading {"blockId":"fx-aaaa1111","content":"Hello"} /-->';

		$this->assertIsString( $after );
		$this->assertStringContainsString( '<!-- wp:flexa/heading {"blockId":"fx-aaaa1111","content":"Xin chào"} /-->', $after );
		$this->assertSame( substr( $before, strlen( $heading ) ), substr( $after, strpos( $after, "\n" ) ) );
	}

	public function test_empty_objects_and_lists_elsewhere_survive(): void {
		$after = $this->set( $this->page(), 'fx-cccc3333', 'items.1.text', 'Hai' );

		$this->assertIsString( $after );
		$this->assertStringContainsString( '"padding":{},"gap":[]', $after );
		$this->assertStringContainsString( '"items":[{"text":"One","link":{"url":""}},{"text":"Hai","link":{"url":"https://a.test"}}]', $after );
	}

	public function test_a_nested_list_field_can_be_set(): void {
		$after = $this->set( $this->page(), 'fx-cccc3333', 'items.0.link.url', 'https://b.test' );

		$this->assertIsString( $after );
		$this->assertStringContainsString( '{"text":"One","link":{"url":"https://b.test"}}', $after );
	}

	public function test_a_missing_top_level_attribute_may_be_created(): void {
		// A block stores nothing for a value equal to its default, so setting a
		// field nobody changed yet means adding the key.
		$after = $this->set( $this->page(), 'fx-aaaa1111', 'subheadingContent', 'Sub' );

		$this->assertIsString( $after );
		$this->assertStringContainsString( '"subheadingContent":"Sub"', $after );
	}

	public function test_structure_that_is_not_there_is_not_invented(): void {
		$token = Block_Patcher::locate( $this->page(), 'fx-cccc3333' );
		$this->assertIsArray( $token );

		$this->assertInstanceOf( WP_Error::class, Block_Patcher::with_value( $token['attrs'], [ 'items', '5', 'text' ], 'x' ) );
		$this->assertInstanceOf( WP_Error::class, Block_Patcher::with_value( $token['attrs'], [ 'nope', 'text' ], 'x' ) );
	}

	public function test_a_value_cannot_close_the_comment_it_lives_in(): void {
		$after = $this->set( $this->page(), 'fx-aaaa1111', 'content', 'a --> b <!-- c "d" & e' );

		$this->assertIsString( $after );
		$this->assertCount( 4, Block_Patcher::tokens( $after ) );

		$token = Block_Patcher::locate( $after, 'fx-aaaa1111' );
		$this->assertIsArray( $token );
		$this->assertSame( 'a --> b <!-- c "d" & e', $token['attrs']->content );
	}

	/**
	 * Values from front-end editing are HTML that has already been through the
	 * field's kses allowlist. They have to land in the attribute JSON intact and
	 * read back as the same string, without anything in them being able to end
	 * the comment that holds the JSON.
	 *
	 * @return array<string, array{0: string}>
	 */
	public function html_values(): array {
		return [
			'inline formatting' => [ 'Xin chào <strong>bạn</strong> và <em>bạn bè</em>' ],
			'a link'            => [ 'Đọc <a href="https://example.com/a?x=1&amp;y=2" target="_blank" rel="noopener">thêm</a>' ],
			'a line break'      => [ 'Dòng một<br>Dòng hai' ],
			'a comment closer'  => [ 'a --> b <!-- c' ],
			'quotes and slash'  => [ 'He said "hi" and \\ left' ],
		];
	}

	/**
	 * @dataProvider html_values
	 *
	 * @param string $value Value to store.
	 */
	public function test_an_html_value_round_trips_and_stays_inside_its_comment( string $value ): void {
		$after = $this->set( $this->page(), 'fx-aaaa1111', 'content', $value );

		$this->assertIsString( $after );
		$this->assertCount( 4, Block_Patcher::tokens( $after ) );

		$token = Block_Patcher::locate( $after, 'fx-aaaa1111' );
		$this->assertIsArray( $token );
		$this->assertSame( $value, $token['attrs']->content );

		// The delimiter line holds no raw angle bracket or comment closer.
		$line = strtok( $after, "\n" );
		$this->assertIsString( $line );
		$json = substr( $line, strlen( '<!-- wp:flexa/heading ' ), -strlen( ' /-->' ) );
		$this->assertStringNotContainsString( '<', $json );
		$this->assertStringNotContainsString( '>', $json );
		$this->assertStringNotContainsString( '--', $json );
	}

	public function test_an_html_value_in_a_list_item_leaves_its_siblings_alone(): void {
		$after = $this->set( $this->page(), 'fx-cccc3333', 'items.0.text', '<strong>Một</strong>' );

		$this->assertIsString( $after );
		$token = Block_Patcher::locate( $after, 'fx-cccc3333' );
		$this->assertIsArray( $token );
		$this->assertSame( '<strong>Một</strong>', $token['attrs']->items[0]->text );
		$this->assertSame( 'Two', $token['attrs']->items[1]->text );
		$this->assertStringContainsString( '"padding":{},"gap":[]', $after );
	}

	public function test_block_ids_lists_every_block_in_the_content_including_nested(): void {
		$this->assertSame(
			[
				'fx-aaaa1111' => true,
				'fx-bbbb2222' => true,
				'fx-cccc3333' => true,
			],
			Block_Patcher::block_ids( $this->page() )
		);
	}

	public function test_block_ids_does_not_see_a_block_only_referenced_by_a_synced_pattern(): void {
		// A synced pattern is stored as a reference; its blocks live in another
		// post, so their ids are not this post's.
		$this->assertSame( [], Block_Patcher::block_ids( '<!-- wp:block {"ref":42} /-->' ) );
	}

	public function test_an_unknown_block_id_is_refused(): void {
		$this->assertInstanceOf( WP_Error::class, Block_Patcher::locate( $this->page(), 'fx-nope0000' ) );
	}

	public function test_a_duplicated_block_id_is_refused_rather_than_guessed(): void {
		$page = $this->page() . "\n" . '<!-- wp:flexa/heading {"blockId":"fx-aaaa1111","content":"Again"} /-->';

		$result = Block_Patcher::locate( $page, 'fx-aaaa1111' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'flexa_block_duplicate_block', $result->get_error_code() );
	}

	public function test_the_encoding_matches_cores_escaping(): void {
		$attrs          = new stdClass();
		$attrs->content = 'a--b <c> & "d"';

		$this->assertSame( '{"content":"a\\u002d\\u002db \\u003cc\\u003e \\u0026 \\u0022d\\u0022"}', Block_Patcher::encode( $attrs ) );
	}

	/**
	 * The allowlist.
	 */
	public function test_only_listed_fields_are_editable(): void {
		$this->assertNotNull( Editable_Fields::spec( 'flexa/heading', 'content' ) );
		$this->assertNotNull( Editable_Fields::spec( 'flexa/icon-list', 'items.3.text' ) );
		$this->assertNotNull( Editable_Fields::spec( 'flexa/icon-list', 'items.0.link.url' ) );

		$this->assertNull( Editable_Fields::spec( 'flexa/heading', 'textColor' ) );
		$this->assertNull( Editable_Fields::spec( 'flexa/icon-list', 'items.0.icon.markup' ) );
		$this->assertNull( Editable_Fields::spec( 'flexa/icon-list', 'items.*.text' ) );
		$this->assertNull( Editable_Fields::spec( 'flexa/heading', 'content.x' ) );
		$this->assertNull( Editable_Fields::spec( 'core/paragraph', 'content' ) );
	}

	public function test_paths_expand_over_the_entries_a_list_has(): void {
		$token = Block_Patcher::locate( $this->page(), 'fx-cccc3333' );
		$this->assertIsArray( $token );

		$this->assertSame(
			[ 'items.0.text', 'items.1.text', 'items.0.link.url', 'items.1.link.url' ],
			Editable_Fields::paths( 'flexa/icon-list', $token['attrs'] )
		);
	}

	public function test_a_field_nobody_set_is_still_listed(): void {
		$token = Block_Patcher::locate( $this->page(), 'fx-aaaa1111' );
		$this->assertIsArray( $token );

		$this->assertSame( [ 'content', 'subheadingContent', 'url' ], Editable_Fields::paths( 'flexa/heading', $token['attrs'] ) );
	}
}
