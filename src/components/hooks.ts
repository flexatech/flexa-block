/**
 * Shared editor hooks.
 *
 * @package Flexa\Block
 */

import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

import type { BorderDevice, BoxShadowAttr, ResponsiveValue } from '../types';

/** Prefix for ids that outlive the session, so they read as ours at a glance. */
const STABLE_PREFIX = 'fx-';

/** A fresh id: `fx-` + 8 hex characters. Random, never derived from the clientId. */
const freshId = (): string => {
	const bytes = new Uint8Array( 4 );
	window.crypto.getRandomValues( bytes );
	return STABLE_PREFIX + Array.from( bytes, ( byte ) => byte.toString( 16 ).padStart( 2, '0' ) ).join( '' );
};

/**
 * Give the block a `blockId` that survives a reload.
 *
 * `useBlockId` derives the id from the clientId — and the editor mints a NEW clientId
 * every time it parses the post content, so that id is rewritten on every open and
 * changes with every save. For most blocks that only churns a CSS class. For the Post
 * Grid it breaks a reference: the Post Filter stores the grid's blockId as its
 * `targetGridId`, and a target that changes underneath it points at a grid that no
 * longer exists — a filter bar that renders perfectly and filters nothing.
 *
 * So this one is generated ONCE, when the block has no id, and then left alone. The
 * one case that must still re-generate is a duplicate: the copy arrives carrying the
 * original's id. Whoever holds the id first in document order keeps it; a later block
 * claiming the same id is the copy, and takes a new one.
 *
 * Ids already in saved content are kept as they are — they are only unstable while an
 * editor session is rewriting them, and nothing here does that any more.
 *
 * @param clientId      The block's editor clientId.
 * @param blockId       Current blockId attribute value.
 * @param setAttributes Block setAttributes.
 */
export const useStableBlockId = (
	clientId: string,
	blockId: string | undefined,
	setAttributes: ( attrs: { blockId: string } ) => void
): void => {
	const isCopy = useSelect(
		( select: ( store: string ) => any ) => {
			const editor = select( 'core/block-editor' );
			if ( ! blockId || ! editor?.getClientIdsWithDescendants ) {
				return false;
			}
			const name = editor.getBlockName( clientId );
			const holders = editor
				.getClientIdsWithDescendants()
				.filter(
					( id: string ) =>
						editor.getBlockName( id ) === name && editor.getBlockAttributes( id )?.blockId === blockId
				);
			// Same id on two blocks of the same type: the first one in the document owns it.
			return holders.length > 1 && holders[ 0 ] !== clientId;
		},
		[ clientId, blockId ]
	);

	useEffect( () => {
		if ( ! blockId || isCopy ) {
			setAttributes( { blockId: freshId() } );
		}
	}, [ blockId, isCopy, setAttributes ] );
};

/**
 * Keep the block's `blockId` attribute in sync with its clientId.
 *
 * The id is the CSS selector key (`.flexa-<block>-<blockId>`), so it must be
 * stable per instance and re-generated when a block is duplicated (a duplicate
 * gets a new clientId, so the copied blockId no longer matches and is replaced).
 *
 * @param clientId      The block's editor clientId.
 * @param blockId       Current blockId attribute value.
 * @param setAttributes Block setAttributes.
 */
export const useBlockId = (
	clientId: string,
	blockId: string | undefined,
	setAttributes: ( attrs: { blockId: string } ) => void
): void => {
	useEffect( () => {
		const expected = clientId.replace( /[^a-z0-9]/gi, '' ).slice( 0, 8 );
		if ( ! blockId || blockId !== expected ) {
			setAttributes( { blockId: expected } );
		}
	}, [ blockId, clientId, setAttributes ] );
};

// --- Item-style migration -------------------------------------------------
// TODO(remove in vNEXT): the whole block below (constants, helpers and the
// `useMigrateItemStyle` hook) is a one-time bridge. The collection blocks used
// to apply the wrapper `border`/`boxShadow` to each item; those attributes now
// mean "the block wrapper" and a new `itemBorder`/`itemBoxShadow` styles each
// item. This hook moves any legacy value onto the item once, on first open, so
// existing content keeps its look. Delete it (and the PHP fallback) once the
// grace period is over.

const MIGRATE_DEVICES: Array< 'desktop' | 'tablet' | 'mobile' > = [ 'desktop', 'tablet', 'mobile' ];

/** The block.json empty-border default — used to clear `border` after moving. */
const EMPTY_BORDER: ResponsiveValue< BorderDevice > = {
	desktop: {
		style: '',
		width: { top: '', right: '', bottom: '', left: '', unit: 'px' },
		color: { light: '', dark: '' },
		radius: { topLeft: '', topRight: '', bottomRight: '', bottomLeft: '', unit: 'px' },
	},
	tablet: {},
	mobile: {},
};

/** True if a single-device border carries any author-set value. */
const borderDeviceHasValue = ( b?: BorderDevice ): boolean => {
	if ( ! b ) {
		return false;
	}
	if ( b.style ) {
		return true;
	}
	const w = b.width || {};
	if ( w.top || w.right || w.bottom || w.left ) {
		return true;
	}
	const c = b.color || {};
	if ( c.light || c.dark ) {
		return true;
	}
	const r = b.radius || {};
	return !! ( r.topLeft || r.topRight || r.bottomRight || r.bottomLeft );
};

/** True if any device of a responsive border carries a value. */
const borderHasValue = ( border?: ResponsiveValue< BorderDevice > ): boolean =>
	MIGRATE_DEVICES.some( ( d ) => borderDeviceHasValue( border?.[ d ] ) );

/** The subset of a block's attributes this migration touches. */
interface ItemStyleAttrs {
	border?: ResponsiveValue< BorderDevice >;
	boxShadow?: BoxShadowAttr;
	itemBorder?: ResponsiveValue< BorderDevice >;
	itemBoxShadow?: BoxShadowAttr;
	itemStyleMigrated?: boolean;
}

/**
 * One-time bridge for the collection blocks (post-grid, rss, taxonomy, the
 * feeds, timeline, faq). Runs once when a block that predates the split is
 * opened: it moves the legacy wrapper `border`/`boxShadow` (which used to paint
 * each item) onto the new per-item `itemBorder`/`itemBoxShadow`, clears the
 * legacy values so `border`/`boxShadow` are free to style the wrapper, and sets
 * `itemStyleMigrated` so the front-end PHP switches from the legacy fallback to
 * the per-item attributes. Blocks with nothing to move are still marked migrated
 * so the wrapper semantics apply going forward.
 *
 * @param attributes    The block's attributes (only the style fields are read).
 * @param setAttributes Block setAttributes.
 */
export const useMigrateItemStyle = (
	attributes: ItemStyleAttrs,
	setAttributes: ( attrs: Partial< ItemStyleAttrs > ) => void
): void => {
	const { border, boxShadow, itemStyleMigrated } = attributes;

	useEffect( () => {
		if ( itemStyleMigrated ) {
			return;
		}
		const patch: Partial< ItemStyleAttrs > = { itemStyleMigrated: true };
		if ( borderHasValue( border ) ) {
			patch.itemBorder = border;
			patch.border = EMPTY_BORDER;
		}
		if ( boxShadow?.enabled ) {
			patch.itemBoxShadow = boxShadow;
			patch.boxShadow = { ...boxShadow, enabled: false };
		}
		setAttributes( patch );
		// Only re-run if the block flips back to un-migrated (it won't); the
		// legacy values are captured on the first pass.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ itemStyleMigrated ] );
};
