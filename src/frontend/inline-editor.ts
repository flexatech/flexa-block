/**
 * Front-end inline editor.
 *
 * Server-side, each editable block's root element is marked with
 * `data-flexa-edit-id` (its blockId) + `data-flexa-edit-name`. This script reads
 * the field registry from `window.flexaBlockInlineEditor`, turns each mapped
 * child element into a click-to-edit `contenteditable` region, and POSTs the new
 * value to the REST endpoint — which rewrites the block attribute server-side, so
 * block markup and generated CSS are preserved. Only authorized users ever
 * receive this script (gated in PHP), so it assumes edit permission.
 */

import './inline-editor.scss';

interface FieldDef {
	selector: string;
	attr: string;
	repeat?: boolean;
	// Object sub-key field (e.g. countdown `labels.days`): writes attr[key],
	// no array index.
	object?: boolean;
	// Matrix cell (comparison table): writes attr[row].values[col]; the column
	// index is read from the cell's own data-flexa-cell.
	cell?: boolean;
	key?: string;
	// When set, the value is every matching element's text within the item,
	// joined by this string (e.g. a `\n`-separated feature list stored as one
	// string attribute). Editing any one line rewrites the whole field.
	join?: string;
}

interface Boot {
	postId: number;
	nonce: string;
	endpoint: string;
	fields: Record< string, FieldDef[] >;
	i18n: {
		editing: string;
		save: string;
		cancel: string;
		saved: string;
		saving: string;
		error: string;
	};
}

interface ActiveEdit {
	el: HTMLElement;
	root: HTMLElement;
	originalHTML: string;
	blockId: string;
	blockName: string;
	attr: string;
	index?: number;
	subindex?: number;
	key?: string;
	selector: string;
	join?: string;
	itemEl: HTMLElement | null;
}

const boot = ( window as unknown as { flexaBlockInlineEditor?: Boot } )
	.flexaBlockInlineEditor;

const EDITABLE_CLASS = 'flexa-fee-editable';
const ACTIVE_CLASS = 'flexa-fee-active';

let active: ActiveEdit | null = null;
let saving = false;

let toolbar: HTMLElement | null = null;
let msgEl: HTMLElement | null = null;
let saveBtn: HTMLButtonElement | null = null;
let cancelBtn: HTMLButtonElement | null = null;

/**
 * Show / hide the editing toolbar.
 *
 * @param show Whether the toolbar should be visible.
 */
function toggleToolbar( show: boolean ): void {
	if ( ! toolbar ) {
		return;
	}
	if ( show ) {
		toolbar.removeAttribute( 'hidden' );
	} else {
		toolbar.setAttribute( 'hidden', '' );
	}
}

type MessageTone = 'info' | 'success' | 'error';

/**
 * Set the toolbar status message and colour it by tone.
 *
 * @param text Message text ('' clears it).
 * @param tone Visual tone: info (default), success (green) or error (red).
 */
function setMessage( text: string, tone: MessageTone = 'info' ): void {
	if ( ! msgEl ) {
		return;
	}
	msgEl.textContent = text;
	msgEl.classList.remove( 'is-success', 'is-error' );
	if ( tone === 'success' ) {
		msgEl.classList.add( 'is-success' );
	} else if ( tone === 'error' ) {
		msgEl.classList.add( 'is-error' );
	}
}

/**
 * Leave edit mode, optionally restoring the original markup.
 *
 * @param restore Whether to put the original HTML back.
 */
function endEdit( restore: boolean ): void {
	if ( ! active ) {
		return;
	}
	if ( restore ) {
		active.el.innerHTML = active.originalHTML;
	}
	active.el.contentEditable = 'false';
	active.el.classList.remove( ACTIVE_CLASS );
	active.root.classList.remove( ACTIVE_CLASS );
	active = null;
	saving = false;
	toggleToolbar( false );
}

/**
 * Enter edit mode for one element.
 *
 * @param el     The editable child element.
 * @param root   The block root carrying the data attributes.
 * @param field  The registry field descriptor.
 * @param index  Position among repeated selectors (repeater fields only).
 */
function startEdit(
	el: HTMLElement,
	root: HTMLElement,
	field: FieldDef,
	itemEl: HTMLElement | null
): void {
	if ( active && active.el === el ) {
		return;
	}
	// Switching straight from another element: discard that unsaved edit.
	if ( active ) {
		endEdit( true );
	}

	// Repeater items carry their exact source index in data-flexa-item.
	let index: number | undefined;
	if ( field.repeat && itemEl ) {
		const raw = itemEl.getAttribute( 'data-flexa-item' );
		index = raw !== null ? parseInt( raw, 10 ) : undefined;
	}
	// Matrix cells also carry a column index on the cell element itself.
	let subindex: number | undefined;
	if ( field.cell ) {
		const raw = el.getAttribute( 'data-flexa-cell' );
		subindex = raw !== null ? parseInt( raw, 10 ) : undefined;
	}

	active = {
		el,
		root,
		originalHTML: el.innerHTML,
		blockId: root.getAttribute( 'data-flexa-edit-id' ) || '',
		blockName: root.getAttribute( 'data-flexa-edit-name' ) || '',
		attr: field.attr,
		index,
		subindex,
		key: field.repeat || field.object ? field.key : undefined,
		selector: field.selector,
		join: field.join,
		itemEl,
	};

	el.contentEditable = 'true';
	el.classList.add( ACTIVE_CLASS );
	root.classList.add( ACTIVE_CLASS );
	setMessage( '' );
	toggleToolbar( true );
	el.focus();
}

/**
 * The value to persist for the active edit.
 *
 * Join fields collect every matching element's text within the item; everything
 * else uses the edited element's HTML, minus any required-marker span.
 */
function currentValue(): string {
	if ( ! active ) {
		return '';
	}
	if ( active.join !== undefined && active.itemEl ) {
		return Array.from(
			active.itemEl.querySelectorAll< HTMLElement >( active.selector )
		)
			.map( ( e ) => ( e.textContent || '' ).trim() )
			.filter( ( t ) => t !== '' )
			.join( active.join );
	}
	const clone = active.el.cloneNode( true ) as HTMLElement;
	clone
		.querySelectorAll( '.flexa-field__required' )
		.forEach( ( n ) => n.remove() );
	return clone.innerHTML;
}

/**
 * Persist the active edit to the server.
 */
async function save(): Promise< void > {
	if ( ! active || ! boot || saving ) {
		return;
	}
	saving = true;
	setMessage( boot.i18n.saving );

	const payload: Record< string, unknown > = {
		postId: boot.postId,
		blockId: active.blockId,
		blockName: active.blockName,
		attr: active.attr,
		value: currentValue(),
	};
	if ( active.index !== undefined ) {
		payload.index = active.index;
	}
	if ( active.subindex !== undefined ) {
		payload.subindex = active.subindex;
	}
	if ( active.key !== undefined ) {
		payload.key = active.key;
	}

	try {
		const resp = await fetch( boot.endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': boot.nonce,
			},
			body: JSON.stringify( payload ),
		} );

		if ( ! resp.ok ) {
			const err = await resp.json().catch( () => ( {} ) );
			const detail =
				( err && ( err as { message?: string } ).message ) || '';
			// Fall back to the HTTP status so an opaque failure is still
			// identifiable (e.g. a 403 permission block).
			throw new Error( detail || `HTTP ${ resp.status }` );
		}

		setMessage( boot.i18n.saved, 'success' );
		endEdit( false );
	} catch ( e ) {
		saving = false;
		const message = e instanceof Error ? e.message : boot.i18n.error;
		setMessage( `${ boot.i18n.error }: ${ message }`, 'error' );
	}
}

/**
 * Wire the toolbar buttons once.
 */
function initToolbar(): void {
	toolbar = document.querySelector( '.flexa-fee-toolbar' );
	if ( ! toolbar ) {
		return;
	}
	msgEl = toolbar.querySelector( '.flexa-fee-toolbar__msg' );
	saveBtn = toolbar.querySelector( '.flexa-fee-btn--save' );
	cancelBtn = toolbar.querySelector( '.flexa-fee-btn--cancel' );

	saveBtn?.addEventListener( 'click', () => {
		void save();
	} );
	cancelBtn?.addEventListener( 'click', () => endEdit( true ) );
}

/**
 * Bind click-to-edit on every editable element within a block root.
 *
 * @param root  A `[data-flexa-edit-id]` block root.
 * @param field The registry field descriptor.
 */
function bindField( root: HTMLElement, field: FieldDef ): void {
	const els = field.repeat
		? Array.from( root.querySelectorAll< HTMLElement >( field.selector ) )
		: [ root.querySelector< HTMLElement >( field.selector ) ].filter(
				Boolean
		  ) as HTMLElement[];

	els.forEach( ( el ) => {
		if ( el.classList.contains( EDITABLE_CLASS ) ) {
			return;
		}
		let itemEl: HTMLElement | null = null;
		if ( field.repeat ) {
			itemEl = el.closest< HTMLElement >( '[data-flexa-item]' );
			// No source-index marker → can't map safely; leave it read-only.
			if ( ! itemEl ) {
				return;
			}
			// Matrix cells: only text cells carry data-flexa-cell; skip the
			// boolean check/cross cells (nothing to type into).
			if ( field.cell && ! el.hasAttribute( 'data-flexa-cell' ) ) {
				return;
			}
		}
		el.classList.add( EDITABLE_CLASS );
		el.addEventListener( 'click', ( e ) => {
			// While editing this very element, let the caret move normally.
			if ( active && active.el === el ) {
				return;
			}
			// Editable text often sits inside a link (button, linked heading) —
			// stop navigation and start editing instead.
			e.preventDefault();
			e.stopPropagation();
			startEdit( el, root, field, itemEl );
		} );
	} );
}

/**
 * Wire every editable block on the page.
 */
function init(): void {
	if ( ! boot || ! boot.fields ) {
		return;
	}

	initToolbar();

	const roots = document.querySelectorAll< HTMLElement >(
		'[data-flexa-edit-id]'
	);
	roots.forEach( ( root ) => {
		const name = root.getAttribute( 'data-flexa-edit-name' ) || '';
		const fields = boot.fields[ name ];
		if ( ! fields ) {
			return;
		}
		fields.forEach( ( field ) => bindField( root, field ) );
	} );

	// Escape cancels; Ctrl/Cmd+Enter saves.
	document.addEventListener( 'keydown', ( e ) => {
		if ( ! active ) {
			return;
		}
		if ( e.key === 'Escape' ) {
			e.preventDefault();
			endEdit( true );
		} else if ( ( e.metaKey || e.ctrlKey ) && e.key === 'Enter' ) {
			e.preventDefault();
			void save();
		}
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
