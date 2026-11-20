/**
 * Build the front-end editor's icon catalog for PHP.
 *
 * The block editor's IconPicker turns a chosen icon into SVG in the browser and
 * stores that markup. The front-end editor does not take SVG from the browser:
 * it sends a source and a name, and the server looks the markup up here. So the
 * markup must be exactly what IconPicker produces, which is why this renders the
 * same elements with the same functions:
 *
 * - builtin: src/shared/builtin-icons.json drawn in IconPicker's stroked frame;
 * - library: every icon @wordpress/icons exports, through its <Icon> at size 24,
 *   then IconPicker's normalizeLibrary().
 *
 * Writes includes/icons/catalog.php. Run by `npm run build` (and on its own with
 * `npm run icons`); the output is committed so a checkout works without a build.
 */

const fs = require( 'fs' );
const path = require( 'path' );
const { createElement, renderToString, isValidElement } = require( '@wordpress/element' );
const wpIcons = require( '@wordpress/icons' );

const root = path.resolve( __dirname, '..' );
const builtin = require( path.join( root, 'src/shared/builtin-icons.json' ) );

/** IconPicker's strokeSvg(), without JSX. */
const strokeSvg = ( paths ) =>
	createElement(
		'svg',
		{
			className: 'flexa-icon',
			viewBox: '0 0 24 24',
			width: '1em',
			height: '1em',
			fill: 'none',
			stroke: 'currentColor',
			strokeWidth: '2',
			strokeLinecap: 'round',
			strokeLinejoin: 'round',
			'aria-hidden': 'true',
			focusable: 'false',
		},
		...paths.map( ( d ) => createElement( 'path', { key: d, d } ) )
	);

/** IconPicker's normalizeLibrary(), unchanged. */
const normalizeLibrary = ( raw ) => {
	let svg = raw.trim();
	svg = svg.replace( /\swidth="[^"]*"/i, '' ).replace( /\sheight="[^"]*"/i, '' );
	svg = svg.replace( /^<svg\b/i, '<svg class="flexa-icon" width="1em" height="1em"' );
	if ( ! /^<svg\b[^>]*\sfill=/i.test( svg ) ) {
		svg = svg.replace( /^<svg\b/i, '<svg fill="currentColor"' );
	}
	return svg;
};

const catalog = { builtin: {}, library: {} };

builtin.forEach( ( icon ) => {
	catalog.builtin[ icon.name ] = renderToString( strokeSvg( icon.paths ) );
} );

Object.entries( wpIcons )
	.filter( ( [ , icon ] ) => isValidElement( icon ) )
	.forEach( ( [ exportName, icon ] ) => {
		catalog.library[ 'wp-' + exportName ] = normalizeLibrary(
			renderToString( createElement( wpIcons.Icon, { icon, size: 24 } ) )
		);
	} );

const phpString = ( s ) => "'" + s.replace( /\\/g, '\\\\' ).replace( /'/g, "\\'" ) + "'";
const phpMap = ( map ) =>
	Object.keys( map )
		.sort()
		.map( ( k ) => `\t\t${ phpString( k ) } => ${ phpString( map[ k ] ) },` )
		.join( '\n' );

const version = require( '@wordpress/icons/package.json' ).version;
const out = `<?php
/**
 * Icon catalog for the front-end editor. GENERATED: do not edit.
 *
 * Built by scripts/build-icon-catalog.cjs from src/shared/builtin-icons.json and
 * @wordpress/icons ${ version }, with the same rendering as the block editor's
 * IconPicker. Regenerate with \`npm run icons\`.
 *
 * @package Flexa\\Block
 */

// phpcs:ignoreFile

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return [
	'builtin' => [
${ phpMap( catalog.builtin ) }
	],
	'library' => [
${ phpMap( catalog.library ) }
	],
];
`;

fs.writeFileSync( path.join( root, 'includes/icons/catalog.php' ), out.replace( /\n/g, '\r\n' ) );
console.log(
	`icon catalog: ${ Object.keys( catalog.builtin ).length } builtin, ${ Object.keys( catalog.library ).length } library`
);
