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

	public function test_open_and_close_device(): void {
		// Non-desktop devices wrap in their media query…
		$css = new CSS_Builder();
		CSS_Helpers::open_device( $css, 'tablet' );
		$css->set_selector( '.t' )->add_property( 'color', '#111111' );
		CSS_Helpers::close_device( $css, 'tablet' );
		$this->assertStringContainsString( '@media (max-width: 1024px)', $css->get_output() );

		// …desktop is a no-op (declarations land at the base).
		$css = new CSS_Builder();
		CSS_Helpers::open_device( $css, 'desktop' );
		$css->set_selector( '.t' )->add_property( 'color', '#111111' );
		CSS_Helpers::close_device( $css, 'desktop' );
		$this->assertStringNotContainsString( '@media', $css->get_output() );
	}

	public function test_add_typography_emits_each_field(): void {
		$css = new CSS_Builder();
		CSS_Helpers::add_typography( $css, '.t', [
			'fontSize'      => [ 'value' => '18', 'unit' => 'px' ],
			'fontWeight'    => '700',
			'letterSpacing' => [ 'value' => '1', 'unit' => 'px' ],
			'textTransform' => 'uppercase',
			'lineHeight'    => '1.5',
		] );
		$out = $css->get_output();
		$this->assertStringContainsString( 'font-size:18px', $out );
		$this->assertStringContainsString( 'font-weight:700', $out );
		$this->assertStringContainsString( 'letter-spacing:1px', $out );
		$this->assertStringContainsString( 'text-transform:uppercase', $out );
		$this->assertStringContainsString( 'line-height:1.5', $out );
	}

	public function test_add_typography_empty_emits_nothing(): void {
		$css = new CSS_Builder();
		CSS_Helpers::add_typography( $css, '.t', [] );
		$this->assertSame( '', $css->get_output() );
	}

	public function test_dark_color(): void {
		// A dark value lands inside the dark branch (default settings → media query).
		$css = new CSS_Builder();
		CSS_Helpers::dark_color( $css, '.t', 'color', '#eeeeee' );
		$out = $css->get_output();
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $out );
		$this->assertStringContainsString( 'color:#eeeeee', $out );

		// An empty value emits nothing at all.
		$css = new CSS_Builder();
		CSS_Helpers::dark_color( $css, '.t', 'color', '' );
		$this->assertSame( '', $css->get_output() );
	}

	public function test_add_text_fill_solid_color(): void {
		$css = new CSS_Builder();
		CSS_Helpers::add_text_fill( $css, '.t', 'color', [ 'light' => '#111111', 'dark' => '#eeeeee' ], '' );
		$out = $css->get_output();
		$this->assertStringContainsString( 'color:#111111', $out );                       // light at base
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark)', $out ); // dark branch
		$this->assertStringContainsString( 'color:#eeeeee', $out );
	}

	public function test_add_text_fill_gradient_paints_through_text(): void {
		$css = new CSS_Builder();
		CSS_Helpers::add_text_fill( $css, '.t', 'gradient', '', [ 'light' => 'linear-gradient(90deg,#f00,#00f)' ] );
		$out = $css->get_output();
		$this->assertStringContainsString( 'background-image:linear-gradient(90deg,#f00,#00f)', $out );
		$this->assertStringContainsString( '-webkit-background-clip:text', $out );
		$this->assertStringContainsString( 'background-clip:text', $out );
		$this->assertStringContainsString( '-webkit-text-fill-color:transparent', $out );
		$this->assertStringContainsString( 'color:transparent', $out );
	}

	public function test_sanitize_color_accepts_valid_shapes(): void {
		$this->assertSame( '#fff', CSS_Helpers::sanitize_color( '#fff' ) );
		$this->assertSame( '#ff00ff', CSS_Helpers::sanitize_color( '#ff00ff' ) );
		$this->assertSame( 'rgba(0, 0, 0, 0.5)', CSS_Helpers::sanitize_color( 'rgba(0, 0, 0, 0.5)' ) );
		$this->assertSame( 'hsl(210, 50%, 40%)', CSS_Helpers::sanitize_color( 'hsl(210, 50%, 40%)' ) );
		$this->assertSame( 'transparent', CSS_Helpers::sanitize_color( 'transparent' ) );
		$this->assertSame( 'currentColor', CSS_Helpers::sanitize_color( 'currentColor' ) );
	}

	public function test_sanitize_color_rejects_values_that_could_break_out_of_a_declaration(): void {
		$this->assertSame( '', CSS_Helpers::sanitize_color( '' ) );
		$this->assertSame( '', CSS_Helpers::sanitize_color( 'red;} </style><script>alert(1)</script>' ) );
		$this->assertSame( '', CSS_Helpers::sanitize_color( 'expression(alert(1))' ) );
		$this->assertSame( '', CSS_Helpers::sanitize_color( 'url(javascript:alert(1))' ) );
	}

	public function test_sanitize_gradient_accepts_valid_shapes(): void {
		$this->assertSame(
			'linear-gradient(90deg,#fff,#000)',
			CSS_Helpers::sanitize_gradient( 'linear-gradient(90deg,#fff,#000)' )
		);
		$this->assertSame(
			'repeating-radial-gradient(circle, #fff 0%, #000 10%)',
			CSS_Helpers::sanitize_gradient( 'repeating-radial-gradient(circle, #fff 0%, #000 10%)' )
		);
	}

	public function test_sanitize_gradient_rejects_non_gradient_values(): void {
		$this->assertSame( '', CSS_Helpers::sanitize_gradient( '' ) );
		$this->assertSame( '', CSS_Helpers::sanitize_gradient( 'red' ) );
		$this->assertSame( '', CSS_Helpers::sanitize_gradient( 'linear-gradient(90deg,#fff,#000);}</style><script>alert(1)</script>' ) );
	}

	public function test_add_background_color_drops_malicious_value(): void {
		$css = $this->emit( function ( $b ) {
			CSS_Helpers::add_background( $b, [
				'type'  => 'color',
				'color' => [ 'light' => 'red}.evil{color:red' ],
			] );
		} );
		$this->assertSame( '', $css );
	}

	public function test_add_typography_drops_unrecognized_font_weight_and_text_transform(): void {
		$css = new CSS_Builder();
		CSS_Helpers::add_typography( $css, '.t', [
			'fontWeight'    => 'expression(alert(1))',
			'textTransform' => 'expression(alert(1))',
		] );
		$this->assertSame( '', $css->get_output() );
	}

	public function test_dark_color_drops_malicious_color_value(): void {
		$css = new CSS_Builder();
		CSS_Helpers::dark_color( $css, '.t', 'color', 'red;}</style><script>alert(1)</script>' );
		$this->assertSame( '', $css->get_output() );
	}

	public function test_sanitize_enum_accepts_only_allow_listed_values(): void {
		$this->assertSame( 'solid', CSS_Helpers::sanitize_enum( 'solid', [ 'solid', 'dashed' ] ) );
		$this->assertSame( '', CSS_Helpers::sanitize_enum( 'solid;}</style>', [ 'solid', 'dashed' ] ) );
		$this->assertSame( 'dashed', CSS_Helpers::sanitize_enum( 'nope', [ 'solid', 'dashed' ], 'dashed' ) );
	}

	public function test_sanitize_position_pair_accepts_keywords_and_lengths(): void {
		$this->assertSame( 'top left', CSS_Helpers::sanitize_position_pair( 'top left' ) );
		$this->assertSame( '10px 50%', CSS_Helpers::sanitize_position_pair( '10px 50%' ) );
		$this->assertSame( 'center', CSS_Helpers::sanitize_position_pair( 'center' ) );
	}

	public function test_sanitize_position_pair_rejects_values_outside_the_shape(): void {
		$this->assertSame( 'center center', CSS_Helpers::sanitize_position_pair( '', 'center center' ) );
		$this->assertSame( 'center center', CSS_Helpers::sanitize_position_pair( 'url(evil)', 'center center' ) );
		$this->assertSame( '', CSS_Helpers::sanitize_position_pair( 'top}.evil{color:red' ) );
	}

	public function test_sanitize_bare_number(): void {
		$this->assertSame( '1.5', CSS_Helpers::sanitize_bare_number( '1.5' ) );
		$this->assertSame( '2', CSS_Helpers::sanitize_bare_number( '2' ) );
		$this->assertSame( '', CSS_Helpers::sanitize_bare_number( 'expression(alert(1))' ) );
	}

	public function test_add_border_drops_unrecognized_style(): void {
		$css = $this->emit( function ( $b ) {
			CSS_Helpers::add_border( $b, [ 'style' => 'solid;}</style><script>alert(1)</script>' ] );
		} );
		$this->assertSame( '', $css );
	}

	public function test_add_background_image_drops_unrecognized_sub_values(): void {
		$css = $this->emit( function ( $b ) {
			CSS_Helpers::add_background( $b, [
				'type'  => 'image',
				'image' => [
					'url'        => 'https://example.com/a.jpg',
					'position'   => 'top}.evil{color:red',
					'size'       => 'expression(alert(1))',
					'repeat'     => 'expression(alert(1))',
					'attachment' => 'expression(alert(1))',
				],
			] );
		} );
		// Every rejected sub-value falls back to its safe default rather than
		// dropping the whole rule.
		$this->assertStringContainsString( 'background-position:center center', $css );
		$this->assertStringContainsString( 'background-size:cover', $css );
		$this->assertStringContainsString( 'background-repeat:no-repeat', $css );
		$this->assertStringContainsString( 'background-attachment:scroll', $css );
	}

	public function test_add_typography_drops_unrecognized_line_height(): void {
		$css = new CSS_Builder();
		CSS_Helpers::add_typography( $css, '.t', [ 'lineHeight' => 'expression(alert(1))' ] );
		$this->assertSame( '', $css->get_output() );
	}

	public function test_add_advanced_layout_emits_overflow_position_and_z_index(): void {
		$css = new CSS_Builder();
		$css->set_selector( '.t' );
		CSS_Helpers::add_advanced_layout( $css, [ 'overflow' => 'hidden', 'position' => 'relative', 'zIndex' => '5' ] );
		$out = $css->get_output();
		$this->assertStringContainsString( 'overflow:hidden', $out );
		$this->assertStringContainsString( 'position:relative', $out );
		$this->assertStringContainsString( 'z-index:5', $out );
	}

	public function test_add_advanced_layout_casts_z_index_to_int_and_drops_bad_keywords(): void {
		$css = new CSS_Builder();
		$css->set_selector( '.t' );
		CSS_Helpers::add_advanced_layout( $css, [
			'overflow' => 'expression(alert(1))',
			'position' => 'expression(alert(1))',
			'zIndex'   => '5;}</style><script>alert(1)</script>',
		] );
		$out = $css->get_output();
		$this->assertStringNotContainsString( 'overflow', $out );
		$this->assertStringNotContainsString( 'position', $out );
		// (int) parses only the leading numeric run, discarding everything after.
		$this->assertStringContainsString( 'z-index:5', $out );
		$this->assertStringNotContainsString( 'script', $out );

		$css2 = new CSS_Builder();
		$css2->set_selector( '.t' );
		CSS_Helpers::add_advanced_layout( $css2, [ 'zIndex' => 'expression(alert(1))' ] );
		$this->assertStringContainsString( 'z-index:0', $css2->get_output() ); // non-numeric casts to 0
	}

	public function test_add_bg_fill(): void {
		// Solid colour: light at base + dark branch.
		$css = new CSS_Builder();
		CSS_Helpers::add_bg_fill( $css, '.t', 'color', [ 'light' => '#f5f5f5', 'dark' => '#202020' ], '' );
		$out = $css->get_output();
		$this->assertStringContainsString( 'background:#f5f5f5', $out );
		$this->assertStringContainsString( 'background:#202020', $out );

		// Gradient becomes a background-image.
		$css = new CSS_Builder();
		CSS_Helpers::add_bg_fill( $css, '.t', 'gradient', '', [ 'light' => 'linear-gradient(0deg,#aaa,#bbb)' ] );
		$this->assertStringContainsString( 'background-image:linear-gradient(0deg,#aaa,#bbb)', $css->get_output() );
	}
}
