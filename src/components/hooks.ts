/**
 * Shared editor hooks.
 *
 * @package Flexa\Block
 */

import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

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
