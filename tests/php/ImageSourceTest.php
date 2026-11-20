<?php
/**
 * Tests for reading a block's image from an attachment ID.
 *
 * @package Flexa\Block
 */

use Flexa\Block\Image_Source;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Flexa\Block\Image_Source
 */
class ImageSourceTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['flexa_test_attachments']     = [ 10 => true, 11 => false ];
		$GLOBALS['flexa_test_readable']        = [ 10, 11 ];
		$GLOBALS['flexa_test_caps']            = [ 'upload_files' => true ];
		$GLOBALS['flexa_test_attachment_urls'] = [
			10 => 'https://example.test/wp-content/uploads/photo.jpg',
			11 => 'https://example.test/wp-content/uploads/doc.pdf',
		];
		$GLOBALS['flexa_test_attachment_meta'] = [ 10 => [ 'width' => 1600, 'height' => 900 ] ];
		$GLOBALS['flexa_test_post_meta']       = [ 10 => [ '_wp_attachment_image_alt' => '  A team photo  ' ] ];
	}

	protected function tearDown(): void {
		$GLOBALS['flexa_test_attachments']     = [];
		$GLOBALS['flexa_test_readable']        = [];
		$GLOBALS['flexa_test_caps']            = [];
		$GLOBALS['flexa_test_attachment_urls'] = [];
		$GLOBALS['flexa_test_attachment_meta'] = [];
		parent::tearDown();
	}

	public function test_an_image_attachment_yields_what_the_block_editor_would_store(): void {
		$this->assertSame(
			[
				'id'     => 10,
				'url'    => 'https://example.test/wp-content/uploads/photo.jpg',
				'alt'    => 'A team photo',
				'width'  => 1600,
				'height' => 900,
			],
			Image_Source::describe( 10 )
		);
	}

	public function test_sizes_are_null_when_the_library_has_none(): void {
		$GLOBALS['flexa_test_attachment_meta'] = [];

		$info = Image_Source::describe( 10 );

		$this->assertNull( $info['width'] );
		$this->assertNull( $info['height'] );
	}

	/**
	 * @return array<string,array{0:int}>
	 */
	public function not_usable(): array {
		return [
			'zero'                  => [ 0 ],
			'negative'              => [ -4 ],
			'no such attachment'    => [ 999 ],
			'attachment not image'  => [ 11 ],
		];
	}

	/**
	 * @dataProvider not_usable
	 */
	public function test_anything_that_is_not_an_image_attachment_is_refused( int $id ): void {
		$result = Image_Source::describe( $id );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'flexa_image_invalid', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	public function test_choosing_from_the_library_needs_the_upload_capability(): void {
		$GLOBALS['flexa_test_caps'] = [ 'upload_files' => false ];

		$result = Image_Source::describe( 10 );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'flexa_image_forbidden', $result->get_error_code() );
		$this->assertSame( 403, $result->get_error_data()['status'] );
	}

	public function test_an_image_the_person_may_not_read_is_refused(): void {
		$GLOBALS['flexa_test_readable'] = [];

		$result = Image_Source::describe( 10 );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 403, $result->get_error_data()['status'] );
	}

	public function test_an_attachment_without_a_file_is_refused(): void {
		$GLOBALS['flexa_test_attachment_urls'] = [];

		$this->assertInstanceOf( WP_Error::class, Image_Source::describe( 10 ) );
	}

	public function test_alt_text_is_plain_text_and_capped(): void {
		$this->assertSame( 'Hello world', Image_Source::clean_alt( "  Hello\nworld  " ) );
		$this->assertSame( 500, mb_strlen( Image_Source::clean_alt( str_repeat( 'a', 900 ) ) ) );
	}
}
