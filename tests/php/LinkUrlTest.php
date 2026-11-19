<?php
/**
 * Tests for the shared link-address rule.
 *
 * @package Flexa\Block
 */

use Flexa\Block\Link_Url;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Flexa\Block\Link_Url
 */
class LinkUrlTest extends TestCase {

	/**
	 * @return array<string,array{0:string}>
	 */
	public function acceptable(): array {
		return [
			'https'            => [ 'https://example.com/a?b=1&c=2#x' ],
			'http'             => [ 'http://example.com' ],
			'mailto'           => [ 'mailto:hi@example.com' ],
			'tel'              => [ 'tel:+84123456789' ],
			'path'             => [ '/contact' ],
			'anchor'           => [ '#pricing' ],
			'query'            => [ '?page=2' ],
			'path with a colon'  => [ '/a:b' ],
			'protocol relative'  => [ '//cdn.example.com/x' ],
			'query with a colon' => [ '?next=http://x.test' ],
			'upper case scheme' => [ 'HTTPS://EXAMPLE.COM' ],
		];
	}

	/**
	 * @dataProvider acceptable
	 */
	public function test_ordinary_addresses_are_accepted_unchanged( string $url ): void {
		$this->assertSame( $url, Link_Url::clean( $url ) );
	}

	public function test_surrounding_space_is_trimmed_and_empty_means_no_link(): void {
		$this->assertSame( 'https://example.com', Link_Url::clean( "  https://example.com \n" ) );
		$this->assertSame( '', Link_Url::clean( '' ) );
		$this->assertSame( '', Link_Url::clean( "   \t " ) );
	}

	/**
	 * @return array<string,array{0:string}>
	 */
	public function refused(): array {
		return [
			'javascript'                  => [ 'javascript:alert(1)' ],
			'upper case'                  => [ 'JaVaScRiPt:alert(1)' ],
			'leading space inside'        => [ " \tjavascript:alert(1)" ],
			'tab inside the scheme'       => [ "java\tscript:alert(1)" ],
			'newline inside the scheme'   => [ "java\nscript:alert(1)" ],
			'entity newline'              => [ 'java&#x0A;script:alert(1)' ],
			'entity tab'                  => [ 'java&#9;script:alert(1)' ],
			'entity colon'                => [ 'javascript&colon;alert(1)' ],
			'double encoded entity'       => [ 'java&amp;#x0A;script:alert(1)' ],
			'numeric entity letters'      => [ '&#106;avascript:alert(1)' ],
			'data'                        => [ 'data:text/html;base64,PHNjcmlwdD4=' ],
			'vbscript'                    => [ 'vbscript:msgbox(1)' ],
			'file'                        => [ 'file:///etc/passwd' ],
			'ftp'                         => [ 'ftp://example.com/x' ],
			'space inside the scheme'     => [ 'java script:alert(1)' ],
			'colon with no scheme'        => [ 'page:1' ],
			'bare host and port'          => [ 'localhost:3000/x' ],
			'null byte'                   => [ "https://example.com/\0x" ],
			'control character'           => [ "https://example.com/\x07" ],
		];
	}

	/**
	 * @dataProvider refused
	 */
	public function test_other_schemes_are_refused_however_they_are_disguised( string $url ): void {
		$this->assertInstanceOf( WP_Error::class, Link_Url::clean( $url ), $url );
	}

	public function test_an_overlong_address_is_refused(): void {
		$long = 'https://example.com/' . str_repeat( 'a', Link_Url::MAX_LENGTH );

		$result = Link_Url::clean( $long );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'flexa_link_too_long', $result->get_error_code() );
	}

	public function test_a_refusal_says_what_is_allowed_and_carries_a_400(): void {
		$result = Link_Url::clean( 'javascript:alert(1)' );

		$this->assertSame( 'flexa_link_scheme', $result->get_error_code() );
		$this->assertStringContainsString( 'http://', $result->get_error_message() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}
}
