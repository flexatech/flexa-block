<?php
/**
 * Tests for CSS_Helpers — pure attribute-to-CSS converters shared by all blocks.
 *
 * @package Flexa\Block
 */

use PHPUnit\Framework\TestCase;
use Flexa\Block\CSS_Builder;
use Flexa\Block\CSS_Helpers;

/**
 * @covers \Flexa\Block\CSS_Helpers
 */
class CssHelpersTest extends TestCase {

	/** Helper: run an emitter (add_background / add_border) on a fresh builder. */
	private function emit( callable $fn ): string {
		$css = new CSS_Builder();
		$css->set_selector( '.t' );
		$fn( $css );
		return $css->get_output();
	}

	public function test_with_unit_appends_unit_only_when_needed(): void {
		$this->assertSame( '20px', CSS_Helpers::with_unit( '20', 'px' ) );
		$this->assertSame( '', CSS_Helpers::with_unit( '', 'px' ) );
		$this->assertSame( 'auto', CSS_Helpers::with_unit( 'auto' ) );
		$this->assertSame( '50%', CSS_Helpers::with_unit( '50%' ) );   // already has a unit
		$this->assertSame( '0px', CSS_Helpers::with_unit( '0', 'px' ) );
	}

	public function test_spacing_shorthand(): void {
		$this->assertSame(
			'10px 5px 10px 5px',
			CSS_Helpers::spacing_shorthand( [ 'top' => '10', 'right' => '5', 'bottom' => '10', 'left' => '5', 'unit' => 'px' ] )
		);
		$this->assertSame( '', CSS_Helpers::spacing_shorthand( [] ) );
		// Missing sides collapse to 0.
		$this->assertSame( '10px 0 0 0', CSS_Helpers::spacing_shorthand( [ 'top' => '10', 'unit' => 'px' ] ) );
	}

	public function test_radius_shorthand(): void {
		$this->assertSame(
			'4px 4px 4px 4px',
			CSS_Helpers::radius_shorthand( [ 'topLeft' => '4', 'topRight' => '4', 'bottomRight' => '4', 'bottomLeft' => '4', 'unit' => 'px' ] )
		);
		$this->assertSame( '', CSS_Helpers::radius_shorthand( [] ) );
	}

	public function test_light_and_dark_pickers(): void {
		$pair = [ 'light' => '#fff', 'dark' => '#000' ];
		$this->assertSame( '#fff', CSS_Helpers::light( $pair ) );
		$this->assertSame( '#000', CSS_Helpers::dark( $pair ) );
		$this->assertSame( '#abc', CSS_Helpers::light( '#abc' ) ); // plain string allowed for light
		$this->assertSame( '', CSS_Helpers::dark( '#abc' ) );      // no dark value in a plain string
	}

	public function test_box_shadow(): void {
		$this->assertSame( '', CSS_Helpers::box_shadow( [] ) );
		$this->assertSame( '', CSS_Helpers::box_shadow( [ 'enabled' => false ] ) );

		$shadow = [
			'enabled'    => true,
			'horizontal' => '2',
			'vertical'   => '4',
			'blur'       => '6',
			'spread'     => '0',
			'color'      => [ 'light' => '#000', 'dark' => '#fff' ],
		];
		$this->assertSame( '2px 4px 6px 0px #000', CSS_Helpers::box_shadow( $shadow ) );
		$this->assertStringStartsWith( 'inset ', CSS_Helpers::box_shadow( array_merge( $shadow, [ 'inset' => true ] ) ) );
	}

	public function test_add_background_color(): void {
		$css = $this->emit( function ( $b ) {
			CSS_Helpers::add_background( $b, [ 'type' => 'color', 'color' => [ 'light' => '#ff0000', 'dark' => '#000000' ] ] );
		} );
		$this->assertStringContainsString( 'background-color:#ff0000', $css ); // emits the LIGHT value at base
		$this->assertStringNotContainsString( '#000000', $css );               // dark handled elsewhere
	}

	public function test_add_background_gradient(): void {
		$css = $this->emit( function ( $b ) {
			CSS_Helpers::add_background( $b, [
				'type'     => 'gradient',
				'gradient' => [ 'light' => 'linear-gradient(90deg,#fff,#000)' ],
			] );
		} );
		$this->assertStringContainsString( 'background-image:linear-gradient(90deg,#fff,#000)', $css );
	}

	public function test_add_background_image(): void {
		$css = $this->emit( function ( $b ) {
			CSS_Helpers::add_background( $b, [
				'type'  => 'image',
				'image' => [
					'url'        => 'https://example.com/a.jpg',
					'position'   => 'top left',
					'size'       => 'contain',
					'repeat'     => 'repeat-x',
					'attachment' => 'fixed',
				],
			] );
		} );
		$this->assertStringContainsString( 'background-image:url(https://example.com/a.jpg)', $css );
		$this->assertStringContainsString( 'background-position:top left', $css );
		$this->assertStringContainsString( 'background-size:contain', $css );
		$this->assertStringContainsString( 'background-repeat:repeat-x', $css );
		$this->assertStringContainsString( 'background-attachment:fixed', $css );
	}

	public function test_add_background_image_uses_defaults(): void {
		$css = $this->emit( function ( $b ) {
			CSS_Helpers::add_background( $b, [ 'type' => 'image', 'image' => [ 'url' => 'https://example.com/b.jpg' ] ] );
		} );
		$this->assertStringContainsString( 'background-position:center center', $css );
		$this->assertStringContainsString( 'background-size:cover', $css );
		$this->assertStringContainsString( 'background-repeat:no-repeat', $css );
		$this->assertStringContainsString( 'background-attachment:scroll', $css );
	}

	public function test_add_border(): void {
		$css = $this->emit( function ( $b ) {
			CSS_Helpers::add_border( $b, [
				'style'  => 'solid',
				'width'  => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
				'color'  => [ 'light' => '#cccccc', 'dark' => '#333333' ],
				'radius' => [ 'topLeft' => '8', 'topRight' => '8', 'bottomRight' => '8', 'bottomLeft' => '8', 'unit' => 'px' ],
			] );
		} );
		$this->assertStringContainsString( 'border-style:solid', $css );
		$this->assertStringContainsString( 'border-width:2px 2px 2px 2px', $css );
		$this->assertStringContainsString( 'border-color:#cccccc', $css );        // light at base
		$this->assertStringContainsString( 'border-radius:8px 8px 8px 8px', $css );
	}
}
