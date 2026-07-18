/**
 * Social Share block — the shareable-network list (editor side).
 *
 * Brand artwork + labels come from the shared catalog (`@utils`); this block
 * only narrows it to the networks that expose a web share endpoint. Instagram
 * has an icon but no URL-share intent, so it is intentionally not offered here.
 * The actual share URLs are built at render time in render.php.
 *
 * @package Flexa\Block
 */

import { getPlatform, type SocialPlatform } from '@utils';

/** Network keys that can receive a shared page URL, in default order. */
export const SHARE_KEYS = [ 'facebook', 'x', 'linkedin', 'pinterest' ] as const;

/** The shareable platforms, resolved against the shared brand catalog. */
export const SHARE_NETWORKS: SocialPlatform[] = SHARE_KEYS
	.map( ( key ) => getPlatform( key ) )
	.filter( ( p ): p is SocialPlatform => Boolean( p ) );
