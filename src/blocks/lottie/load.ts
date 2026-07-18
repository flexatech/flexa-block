/**
 * Lottie source loader — shared by the front-end view script and the editor
 * preview so both read the same formats identically.
 *
 * lottie-web only understands a raw animation JSON object. This resolves a source
 * URL to that object, transparently handling BOTH:
 *   - a plain `.json` Lottie file, and
 *   - a `.lottie` (dotLottie) bundle, which is a ZIP containing `manifest.json`,
 *     `animations/<id>.json` and optional `images/*` assets.
 * The format is detected by the ZIP magic bytes, not the extension, so a URL with
 * no/odd extension still resolves correctly. Zip-packed images are inlined into
 * the animation's `assets` as data URIs so the SVG renderer needs no extra fetch.
 */

import { unzipSync, strFromU8 } from 'fflate';

type Json = Record< string, unknown >;

/** Fetch a source URL and resolve it to a lottie-web animation-data object. */
export async function loadLottieData( src: string ): Promise< Json > {
	const res = await fetch( src );
	if ( ! res.ok ) {
		throw new Error( `HTTP ${ res.status }` );
	}
	const bytes = new Uint8Array( await res.arrayBuffer() );

	// ZIP local-file header "PK\x03\x04" → dotLottie bundle.
	if ( bytes[ 0 ] === 0x50 && bytes[ 1 ] === 0x4b && bytes[ 2 ] === 0x03 && bytes[ 3 ] === 0x04 ) {
		return parseDotLottie( bytes );
	}

	return JSON.parse( strFromU8( bytes ) ) as Json;
}

/** Extract the first animation from a dotLottie ZIP and inline its images. */
function parseDotLottie( bytes: Uint8Array ): Json {
	const files = unzipSync( bytes );

	// Prefer the manifest's first animation id; fall back to any animations/*.json.
	let animPath = '';
	if ( files[ 'manifest.json' ] ) {
		try {
			const manifest = JSON.parse( strFromU8( files[ 'manifest.json' ] ) ) as {
				animations?: Array< { id?: string } >;
			};
			const id = manifest.animations?.[ 0 ]?.id;
			if ( id && files[ `animations/${ id }.json` ] ) {
				animPath = `animations/${ id }.json`;
			}
		} catch {
			// Malformed manifest — fall through to the directory scan.
		}
	}
	if ( ! animPath ) {
		animPath = Object.keys( files ).find( ( n ) => /^animations\/.+\.json$/i.test( n ) ) || '';
	}
	if ( ! animPath || ! files[ animPath ] ) {
		throw new Error( 'No animation found in .lottie bundle' );
	}

	const anim = JSON.parse( strFromU8( files[ animPath ] ) ) as Json;
	inlineImages( anim, files );
	return anim;
}

/** Rewrite external image assets to embedded data URIs using the zip's images. */
function inlineImages( anim: Json, files: Record< string, Uint8Array > ): void {
	const assets = anim.assets;
	if ( ! Array.isArray( assets ) ) {
		return;
	}
	for ( const asset of assets as Array< Record< string, unknown > > ) {
		if ( ! asset || typeof asset !== 'object' || asset.e === 1 || typeof asset.p !== 'string' ) {
			continue;
		}
		const name = asset.p;
		const dir = typeof asset.u === 'string' ? asset.u : '';
		const key =
			[ `images/${ name }`, `${ dir }${ name }`.replace( /^\//, '' ), name ].find( ( c ) => files[ c ] ) ||
			Object.keys( files ).find( ( n ) => n.endsWith( `/${ name }` ) );
		if ( key && files[ key ] ) {
			asset.p = `data:${ mimeFor( key ) };base64,${ bytesToBase64( files[ key ] ) }`;
			asset.u = '';
			asset.e = 1;
		}
	}
}

/** Guess an image MIME type from a file name. */
function mimeFor( name: string ): string {
	const ext = name.slice( name.lastIndexOf( '.' ) + 1 ).toLowerCase();
	if ( ext === 'jpg' || ext === 'jpeg' ) {
		return 'image/jpeg';
	}
	if ( ext === 'svg' ) {
		return 'image/svg+xml';
	}
	if ( ext === 'webp' ) {
		return 'image/webp';
	}
	if ( ext === 'gif' ) {
		return 'image/gif';
	}
	return 'image/png';
}

/** Base64-encode raw bytes in chunks (avoids call-stack limits on large images). */
function bytesToBase64( bytes: Uint8Array ): string {
	let binary = '';
	const chunk = 0x8000;
	for ( let i = 0; i < bytes.length; i += chunk ) {
		binary += String.fromCharCode( ...bytes.subarray( i, i + chunk ) );
	}
	return btoa( binary );
}
