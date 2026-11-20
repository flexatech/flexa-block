<?php
/**
 * Tests for resolving a chosen icon to what a block stores.
 *
 * @package Flexa\Block
 */

use Flexa\Block\Icon_Source;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Flexa\Block\Icon_Source
 */
class IconSourceTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Icon_Source::reset();
		$GLOBALS['flexa_test_filters']         = [];
		$GLOBALS['flexa_test_attachments']     = [ 20 => false, 21 => false ];
		$GLOBALS['flexa_test_mime']            = [ 20 => 'image/svg+xml', 21 => 'application/pdf' ];
		$GLOBALS['flexa_test_readable']        = [ 20, 21 ];
		$GLOBALS['flexa_test_caps']            = [ 'upload_files' => true ];
		$GLOBALS['flexa_test_attachment_urls'] = [ 20 => 'https://example.test/u/logo.svg' ];
	}

	protected function tearDown(): void {
		Icon_Source::reset();
		$GLOBALS['flexa_test_filters']         = [];
		$GLOBALS['flexa_test_attachments']     = [];
		$GLOBALS['flexa_test_mime']            = [];
		$GLOBALS['flexa_test_readable']        = [];
		$GLOBALS['flexa_test_caps']            = [];
		$GLOBALS['flexa_test_attachment_urls'] = [];
		parent::tearDown();
	}

	public function test_the_generated_catalog_has_both_sources(): void {
		$catalog = Icon_Source::catalog();

		$this->assertArrayHasKey( 'check', $catalog['builtin'] );
		$this->assertGreaterThan( 100, count( $catalog['library'] ) );
		$this->assertStringStartsWith( '<svg class="flexa-icon"', $catalog['builtin']['check'] );
	}

	public function test_a_catalog_icon_is_stored_with_the_catalog_markup(): void {
		$info = Icon_Source::describe( 'builtin', 'check', 0 );

		$this->assertSame( 'builtin', $info['source'] );
		$this->assertSame( 'check', $info['name'] );
		$this->assertSame( Icon_Source::catalog()['builtin']['check'], $info['markup'] );
		$this->assertSame( '', $info['url'] );
		$this->assertNull( $info['id'] );
	}

	/**
	 * @return array<string,array{0:string,1:string}>
	 */
	public function unknown(): array {
		return [
			'name not in builtin'     => [ 'builtin', 'not-an-icon' ],
			'library name in builtin' => [ 'builtin', 'wp-arrowRight' ],
			'empty name'              => [ 'library', '' ],
			'unknown source'          => [ 'svg', '<svg onload=alert(1)>' ],
			'empty source'            => [ '', 'check' ],
		];
	}

	/**
	 * @dataProvider unknown
	 */
	public function test_anything_outside_the_catalog_is_refused( string $source, string $name ): void {
		$result = Icon_Source::describe( $source, $name, 0 );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	public function test_no_icon_clears_everything(): void {
		$this->assertSame(
			[
				'source' => 'none',
				'name'   => '',
				'markup' => '',
				'url'    => '',
				'id'     => null,
			],
			Icon_Source::describe( 'none', 'whatever', 99 )
		);
	}

	public function test_an_uploaded_svg_is_accepted_and_its_address_read_from_the_library(): void {
		$info = Icon_Source::describe( 'upload', '', 20 );

		$this->assertSame( 'https://example.test/u/logo.svg', $info['url'] );
		$this->assertSame( 20, $info['id'] );
		$this->assertSame( '', $info['markup'] );
	}

	public function test_an_upload_that_is_not_an_image_or_not_allowed_is_refused(): void {
		$this->assertInstanceOf( WP_Error::class, Icon_Source::describe( 'upload', '', 21 ) );
		$this->assertInstanceOf( WP_Error::class, Icon_Source::describe( 'upload', '', 0 ) );

		$GLOBALS['flexa_test_caps'] = [ 'upload_files' => false ];
		$this->assertSame( 403, Icon_Source::describe( 'upload', '', 20 )->get_error_data()['status'] );
	}

	public function test_only_icons_that_can_be_stored_again_can_be_restored(): void {
		$this->assertTrue( Icon_Source::can_restore( 'none', '', 0 ) );
		$this->assertTrue( Icon_Source::can_restore( 'builtin', 'check', 0 ) );
		$this->assertTrue( Icon_Source::can_restore( 'upload', '', 20 ) );
		$this->assertFalse( Icon_Source::can_restore( 'builtin', 'star', 0 ) );
		$this->assertFalse( Icon_Source::can_restore( 'upload', '', 0 ) );
	}

	public function test_the_catalog_can_be_extended_with_a_filter(): void {
		add_filter(
			'flexa_block_inline_icon_catalog',
			static function ( $catalog ) {
				$catalog['builtin']['brand'] = '<svg class="flexa-icon"></svg>';
				return $catalog;
			}
		);

		$this->assertSame( 'brand', Icon_Source::describe( 'builtin', 'brand', 0 )['name'] );
	}
}
