/**
 * Tests for shared editor utilities (responsive cascade + value formatting).
 *
 * Run with: npm test
 */

import { effective, withUnit, parseUnit, spacingShorthand } from './index';

describe( 'effective() responsive cascade', () => {
	const group: any = { desktop: { a: 1 }, tablet: { a: 2 }, mobile: { b: 3 } };

	it( 'desktop returns only desktop values', () => {
		expect( effective( group, 'desktop' ) ).toEqual( { a: 1 } );
	} );

	it( 'tablet inherits desktop then overrides', () => {
		expect( effective( group, 'tablet' ) ).toEqual( { a: 2 } );
	} );

	it( 'mobile inherits desktop + tablet then adds its own', () => {
		expect( effective( group, 'mobile' ) ).toEqual( { a: 2, b: 3 } );
	} );

	it( 'handles an empty group gracefully', () => {
		expect( effective( undefined, 'mobile' ) ).toEqual( {} );
	} );
} );

describe( 'withUnit()', () => {
	it( 'appends the unit to a bare number', () => {
		expect( withUnit( 20, 'px' ) ).toBe( '20px' );
	} );
	it( 'keeps an empty value empty', () => {
		expect( withUnit( '', 'px' ) ).toBe( '' );
	} );
	it( 'leaves auto / none untouched', () => {
		expect( withUnit( 'auto' ) ).toBe( 'auto' );
		expect( withUnit( 'none' ) ).toBe( 'none' );
	} );
	it( 'does not double a value that already has a unit', () => {
		expect( withUnit( '50%' ) ).toBe( '50%' );
	} );
} );

describe( 'parseUnit()', () => {
	it( 'splits a value into number + unit', () => {
		expect( parseUnit( '10px' ) ).toEqual( { value: '10', unit: 'px' } );
	} );
	it( 'falls back to the default unit when none is present', () => {
		expect( parseUnit( '10' ) ).toEqual( { value: '10', unit: 'px' } );
	} );
} );

describe( 'spacingShorthand()', () => {
	it( 'builds a 4-side shorthand', () => {
		expect(
			spacingShorthand( { top: '10', right: '5', bottom: '10', left: '5', unit: 'px' } )
		).toBe( '10px 5px 10px 5px' );
	} );
	it( 'returns undefined when nothing is set', () => {
		expect( spacingShorthand( {} ) ).toBeUndefined();
	} );
} );
