/**
 * Shared editor hooks.
 *
 * The active device is shared with the editor's own preview switcher so the
 * canvas and the inspector controls stay in sync.
 *
 * @package Flexa\Block
 */

import { useSelect, select as dataSelect, dispatch as dataDispatch } from '@wordpress/data';
import type { DeviceKey, DeviceType } from '../types';

const STORES = [ 'core/edit-site', 'core/edit-post' ];

/**
 * Read the editor preview device type ('Desktop' | 'Tablet' | 'Mobile').
 *
 * @param store A data store API.
 * @return Device type or null.
 */
const readDevice = ( store: any ): string | null => {
	if ( ! store ) {
		return null;
	}
	if ( typeof store.__experimentalGetPreviewDeviceType === 'function' ) {
		return store.__experimentalGetPreviewDeviceType();
	}
	if ( typeof store.getDeviceType === 'function' ) {
		return store.getDeviceType();
	}
	return null;
};

/**
 * Hook: current editor device type ('Desktop' | 'Tablet' | 'Mobile').
 *
 * @return Device type.
 */
export const useDeviceType = (): string => {
	return useSelect( ( select: ( store: string ) => any ) => {
		for ( const name of STORES ) {
			try {
				const device = readDevice( select( name ) );
				if ( device ) {
					return device;
				}
			} catch ( e ) {}
		}
		try {
			const device = readDevice( select( 'core/editor' ) );
			if ( device ) {
				return device;
			}
		} catch ( e ) {}
		return 'Desktop';
	}, [] );
};

/**
 * Set the editor preview device type (and therefore the active inspector device).
 *
 * @param device 'Desktop' | 'Tablet' | 'Mobile'.
 */
export const setDeviceType = ( device: DeviceType | string ): void => {
	const candidates = [ ...STORES, 'core/editor' ];
	for ( const name of candidates ) {
		try {
			const store: any = dataSelect( name );
			if ( ! store ) {
				continue;
			}
			const actions: any = dataDispatch( name );
			if ( typeof store.__experimentalGetPreviewDeviceType === 'function' && actions.__experimentalSetPreviewDeviceType ) {
				actions.__experimentalSetPreviewDeviceType( device );
				return;
			}
			if ( typeof store.getDeviceType === 'function' && actions.setDeviceType ) {
				actions.setDeviceType( device );
				return;
			}
		} catch ( e ) {}
	}
};

/**
 * Map an editor device type to a responsive attribute key.
 *
 * @param deviceType Device type.
 * @return 'desktop' | 'tablet' | 'mobile'.
 */
export const getDeviceKey = ( deviceType: string ): DeviceKey => {
	switch ( deviceType ) {
		case 'Tablet':
			return 'tablet';
		case 'Mobile':
			return 'mobile';
		default:
			return 'desktop';
	}
};
