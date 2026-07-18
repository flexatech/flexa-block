/**
 * ItemListPanel — a reusable "repeatable item list" editor (shared across blocks).
 *
 * Owns the list mechanics every list block needs: an accordion of item cards
 * (one open at a time), a collapsible header (preview + title), reorder ↑ / ↓,
 * remove, and an Add button — all wrapped in a PanelBody. The block supplies only
 * what is item-specific: a `newItem` factory, a `renderHeader` (the collapsed
 * card's preview + title) and a `renderBody` (the item's own controls).
 *
 * Hoisted from the social-icon block so social-icon and counter (and any future
 * list block) share the same card chrome + styling instead of each re-rolling it
 * (guide §4.4). Card styling lives in components/editor.scss (`.flexa-item-card`).
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { PanelBody, Button } from '@wordpress/components';

/** The collapsed card header for one item. */
export interface ItemHeader {
	/** Small icon/thumbnail preview shown before the title (optional). */
	preview?: JSX.Element;
	/** Header label (e.g. the platform name, or "Stat 1"). */
	title: string;
}

interface ItemListPanelProps< T > {
	/** PanelBody title, e.g. "Icons" / "Stats". */
	title: string;
	/** The item array. */
	items: T[];
	/** Called with the next array on any add / edit / move / remove. */
	onChange: ( items: T[] ) => void;
	/** Factory for a fresh item (used by the Add button). */
	newItem: () => T;
	/** Label for the Add button, e.g. "Add icon" / "Add stat". */
	addLabel: string;
	/** Build the collapsed header (preview + title) for an item. */
	renderHeader: ( item: T, index: number ) => ItemHeader;
	/** Render an item's own controls; `update` shallow-merges a patch into it. */
	renderBody: ( item: T, index: number, update: ( patch: Partial< T > ) => void ) => JSX.Element;
	/** Whether the PanelBody starts open (default true). */
	initialOpen?: boolean;
	/** Don't allow removing below this many items (default 0). */
	minItems?: number;
}

/**
 * Accordion editor for a repeatable list of items.
 */
export function ItemListPanel< T extends object >( {
	title,
	items,
	onChange,
	newItem,
	addLabel,
	renderHeader,
	renderBody,
	initialOpen = true,
	minItems = 0,
}: ItemListPanelProps< T > ): JSX.Element {
	const list: T[] = Array.isArray( items ) ? items : [];
	const [ openIndex, setOpenIndex ] = useState< number >( -1 );

	const update = ( index: number, patch: Partial< T > ): void => {
		onChange( list.map( ( it, i ) => ( i === index ? { ...it, ...patch } : it ) ) );
	};
	const move = ( index: number, dir: -1 | 1 ): void => {
		const target = index + dir;
		if ( target < 0 || target >= list.length ) {
			return;
		}
		const next = [ ...list ];
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		onChange( next );
		setOpenIndex( target );
	};
	const remove = ( index: number ): void => {
		onChange( list.filter( ( _, i ) => i !== index ) );
		setOpenIndex( -1 );
	};
	const add = (): void => {
		onChange( [ ...list, newItem() ] );
		setOpenIndex( list.length );
	};

	return (
		<PanelBody title={ title } initialOpen={ initialOpen }>
			{ list.map( ( item, index ) => {
				const head = renderHeader( item, index );
				const isOpen = openIndex === index;
				return (
					<div key={ index } className={ 'flexa-item-card' + ( isOpen ? ' is-open' : '' ) }>
						<div className="flexa-item-card__head">
							<button
								type="button"
								className="flexa-item-card__toggle"
								aria-expanded={ isOpen }
								onClick={ () => setOpenIndex( isOpen ? -1 : index ) }
							>
								{ head.preview && <span className="flexa-item-card__preview">{ head.preview }</span> }
								<span className="flexa-item-card__title">{ head.title }</span>
							</button>
							<span className="flexa-item-card__tools">
								<Button size="small" icon="arrow-up-alt2" label={ __( 'Move up', 'flexa-block' ) } disabled={ index === 0 } onClick={ () => move( index, -1 ) } />
								<Button size="small" icon="arrow-down-alt2" label={ __( 'Move down', 'flexa-block' ) } disabled={ index === list.length - 1 } onClick={ () => move( index, 1 ) } />
								<Button size="small" icon="no-alt" label={ __( 'Remove', 'flexa-block' ) } isDestructive disabled={ list.length <= minItems } onClick={ () => remove( index ) } />
							</span>
						</div>
						{ isOpen && <div className="flexa-item-card__body">{ renderBody( item, index, ( patch ) => update( index, patch ) ) }</div> }
					</div>
				);
			} ) }
			<Button className="flexa-item-card__add" variant="secondary" onClick={ add }>
				{ addLabel }
			</Button>
		</PanelBody>
	);
}
