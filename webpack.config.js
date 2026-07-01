/**
 * Webpack configuration for Flexa Block.
 *
 * Builds each block under src/blocks/<slug>/ into build/blocks/<slug>/.
 * Block.json and render.php are copied automatically by the default
 * @wordpress/scripts copy plugin; index.asset.php is generated per entry.
 *
 * Entry points are written in TypeScript (.tsx / .ts); Babel
 * (@wordpress/babel-preset-default) strips the types during the build, while
 * `npm run typecheck` (tsc) verifies them separately.
 */

const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const fs = require( 'fs' );

const SCRIPT_EXTENSIONS = [ '.tsx', '.ts', '.jsx', '.js' ];

/**
 * Resolve the first existing entry file for a base path (without extension).
 *
 * @param {string} base Absolute path without extension, e.g. ".../index".
 * @return {string|null} The matching file path, or null.
 */
const resolveEntry = ( base ) => {
	for ( const ext of SCRIPT_EXTENSIONS ) {
		const candidate = `${ base }${ ext }`;
		if ( fs.existsSync( candidate ) ) {
			return candidate;
		}
	}
	return null;
};

const blocksDir = path.resolve( process.cwd(), 'src', 'blocks' );

const blockEntries = {};
if ( fs.existsSync( blocksDir ) ) {
	fs.readdirSync( blocksDir )
		.filter( ( folder ) => fs.statSync( path.join( blocksDir, folder ) ).isDirectory() )
		.forEach( ( block ) => {
			const indexPath = resolveEntry( path.join( blocksDir, block, 'index' ) );
			if ( indexPath ) {
				blockEntries[ `blocks/${ block }/index` ] = indexPath;
			}
			const viewPath = resolveEntry( path.join( blocksDir, block, 'view' ) );
			if ( viewPath ) {
				blockEntries[ `blocks/${ block }/view` ] = viewPath;
			}
		} );
}

const adminEntry = {};
const adminIndex = resolveEntry( path.join( process.cwd(), 'src', 'admin', 'index' ) );
if ( adminIndex ) {
	adminEntry[ 'admin/index' ] = adminIndex;
}

module.exports = {
	...defaultConfig,
	entry: {
		...blockEntries,
		...adminEntry,
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( process.cwd(), 'build' ),
	},
	resolve: {
		...defaultConfig.resolve,
		extensions: [
			...SCRIPT_EXTENSIONS,
			...( defaultConfig.resolve?.extensions || [] ),
		],
		alias: {
			...( defaultConfig.resolve?.alias || {} ),
			'@components': path.resolve( process.cwd(), 'src/components' ),
			'@hooks': path.resolve( process.cwd(), 'src/hooks' ),
			'@utils': path.resolve( process.cwd(), 'src/utils' ),
		},
	},
};
