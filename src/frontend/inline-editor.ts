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
	// The link that wraps this text, when it can be edited: the URL attribute,
	// its target attribute, and a selector (within the block root) for the <a>.
	link?: LinkDef;
}

interface LinkDef {
	attr: string;
	target: string;
	anchor: string;
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
		// The Undo button after a save, and what is said once it has worked.
		undo?: string;
		undone?: string;
		// The link editor.
		linkEdit?: string;
		linkApply?: string;
		linkInvalid?: string;
		error: string;
		// Warning shown while editing text stored in a shared place, keyed by
		// the root's `data-flexa-edit-scope`.
		scope?: Record< string, string >;
		// Prompt questions for leaving an edit with unsaved changes.
		unsaved?: string;
		discard?: string;
	};
}

type Choice = 'save' | 'discard' | 'stay';

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
	// Template part or synced pattern the block is stored in; 0 when it is the
	// viewed post itself.
	targetId: number;
	link?: LinkDef;
	// The value as it was when editing started, in the same form currentValue()
	// returns, so "has anything changed" compares like with like.
	originalValue: string;
}

/**
 * Every block root stored in the same place (post, template part or pattern).
 *
 * After a save, the store's content has a new version, and every block read
 * from it has to carry that version or the next edit on this page would be
 * refused as a conflict with the person's own save.
 *
 * @param targetId Store's post ID, or 0 for the viewed post.
 */
function rootsInStore( targetId: number ): HTMLElement[] {
	return Array.from(
		document.querySelectorAll< HTMLElement >( '[data-flexa-edit-id]' )
	).filter(
		( r ) =>
			( parseInt(
				r.getAttribute( 'data-flexa-edit-target' ) || '0',
				10
			) || 0 ) === targetId
	);
}

const boot = ( window as unknown as { flexaBlockInlineEditor?: Boot } )
	.flexaBlockInlineEditor;

const EDITABLE_CLASS = 'flexa-fee-editable';
const ACTIVE_CLASS = 'flexa-fee-active';

let active: ActiveEdit | null = null;
let saving = false;

// What the Undo button will do, for a few seconds after a save. See offerUndo().
interface UndoState {
	payload: Record< string, unknown >;
	before: string;
	logId: string;
	el: HTMLElement;
	root: HTMLElement;
	originalHTML: string;
	join?: string;
	targetId: number;
	timer: number;
	// Puts the page back when the text itself is not what changed (a link).
	restore?: () => void;
}

let undoState: UndoState | null = null;
let undoBtn: HTMLButtonElement | null = null;
let linkBtn: HTMLButtonElement | null = null;
let linkDialog: HTMLElement | null = null;
let undoing = false;

const UNDO_WINDOW_MS = 10000;

// The save request in flight, so a second trigger waits for it instead of
// racing it (see save() and settle()).
let inflight: Promise< boolean > | null = null;

let toolbar: HTMLElement | null = null;
let msgEl: HTMLElement | null = null;
let saveBtn: HTMLButtonElement | null = null;
let cancelBtn: HTMLButtonElement | null = null;
let promptEl: HTMLElement | null = null;
let promptMsgEl: HTMLElement | null = null;

// The open "unsaved changes" question, so a second trigger while it is showing
// gets the same answer instead of stacking another prompt.
let pendingAsk: Promise< Choice > | null = null;

// Whether the beforeunload guard is attached. See updateGuard().
let guarded = false;

// Set while re-submitting a form the person chose to leave the edit for, so the
// submit listener does not stop it a second time.
let bypassSubmit = false;

/**
 * Bring a value to the form two values are compared in.
 *
 * Clicking into text and out again can leave a non-breaking space or a trailing
 * <br> behind without the person typing anything; neither should count as a
 * change worth asking about.
 *
 * @param value Value as currentValue() returns it.
 */
function normalize( value: string ): string {
	return value
		.replace( /&nbsp;|\u00a0/g, ' ' )
		.replace( /<br\s*\/?>\s*$/i, '' )
		.replace( /\s+/g, ' ' )
		.trim();
}

/**
 * Whether the active edit holds something that would be lost by leaving it.
 */
function isDirty(): boolean {
	if ( ! active ) {
		return false;
	}
	return (
		saving ||
		normalize( currentValue() ) !== normalize( active.originalValue )
	);
}

/**
 * The browser's own "leave site?" question, for leaving the page entirely.
 *
 * @param e The beforeunload event.
 */
function onBeforeUnload( e: BeforeUnloadEvent ): void {
	e.preventDefault();
	// Still required by some browsers to show the dialog.
	e.returnValue = '';
}

/**
 * Attach the beforeunload guard only while there is something to lose.
 *
 * A permanent listener would ask on every navigation, and in some browsers a
 * page with one cannot be kept in the back/forward cache.
 */
function updateGuard(): void {
	const need = isDirty();
	if ( need && ! guarded ) {
		window.addEventListener( 'beforeunload', onBeforeUnload );
		guarded = true;
	} else if ( ! need && guarded ) {
		window.removeEventListener( 'beforeunload', onBeforeUnload );
		guarded = false;
	}
}

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
 * Make the required-field markers inside an edited element uneditable (or
 * editable again).
 *
 * @param el   The element being edited.
 * @param lock True to lock, false to release.
 */
function lockMarkers( el: HTMLElement, lock: boolean ): void {
	el.querySelectorAll< HTMLElement >( '.flexa-field__required' ).forEach(
		( m ) => {
			if ( lock ) {
				m.contentEditable = 'false';
			} else {
				m.removeAttribute( 'contenteditable' );
			}
		}
	);
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
	lockMarkers( active.el, false );
	if ( linkBtn ) {
		linkBtn.hidden = true;
	}
	active.el.contentEditable = 'false';
	active.el.classList.remove( ACTIVE_CLASS );
	active.root.classList.remove( ACTIVE_CLASS );
	active = null;
	saving = false;
	toggleToolbar( false );
	updateGuard();
}

/**
 * Ask what to do with unsaved changes in a modal dialog.
 *
 * Three answers rather than the browser's two-button confirm(): saving first is
 * the answer people usually want, and confirm() cannot offer it. Escape while
 * the question is open means "keep editing".
 *
 * @param message The question to show.
 */
function ask( message: string ): Promise< Choice > {
	if ( pendingAsk ) {
		return pendingAsk;
	}

	// The toolbar from a cached page may predate the prompt markup.
	if ( ! toolbar || ! promptEl || ! promptMsgEl ) {
		return Promise.resolve(
			// eslint-disable-next-line no-alert
			window.confirm( message ) ? 'discard' : 'stay'
		);
	}

	const bar = toolbar;
	const prompt = promptEl;
	const promptMsg = promptMsgEl;

	pendingAsk = new Promise< Choice >( ( resolve ) => {
		const onClick = ( e: Event ) => {
			// Clicking the backdrop is the safe answer: keep the current edit.
			if ( e.target === prompt ) {
				done( 'stay' );
				return;
			}
			const btn = ( e.target as HTMLElement ).closest< HTMLElement >(
				'[data-choice]'
			);
			if ( btn ) {
				done(
					( btn.getAttribute( 'data-choice' ) as Choice ) || 'stay'
				);
			}
		};
		const onKey = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' ) {
				e.preventDefault();
				e.stopPropagation();
				done( 'stay' );
				return;
			}
			if ( e.key === 'Tab' ) {
				const choices = Array.from(
					prompt.querySelectorAll< HTMLElement >( '[data-choice]' )
				);
				const first = choices[ 0 ];
				const last = choices[ choices.length - 1 ];
				if ( ! first || ! last ) {
					return;
				}
				if (
					e.shiftKey &&
					prompt.ownerDocument.activeElement === first
				) {
					e.preventDefault();
					last.focus();
				} else if (
					! e.shiftKey &&
					prompt.ownerDocument.activeElement === last
				) {
					e.preventDefault();
					first.focus();
				}
			}
		};
		function done( choice: Choice ): void {
			prompt.setAttribute( 'hidden', '' );
			bar.classList.remove( 'is-asking' );
			document.body.classList.remove( 'flexa-fee-modal-open' );
			prompt.removeEventListener( 'click', onClick );
			document.removeEventListener( 'keydown', onKey, true );
			pendingAsk = null;
			resolve( choice );
		}

		promptMsg.textContent = message;
		prompt.removeAttribute( 'hidden' );
		bar.classList.add( 'is-asking' );
		document.body.classList.add( 'flexa-fee-modal-open' );
		toggleToolbar( true );
		prompt.addEventListener( 'click', onClick );
		document.addEventListener( 'keydown', onKey, true );
		prompt.querySelector< HTMLElement >( '[data-choice="save"]' )?.focus();
	} );

	return pendingAsk;
}

/**
 * Settle unsaved changes before something takes the person away from them.
 *
 * Resolves true when it is fine to go ahead: nothing was unsaved, the changes
 * were saved, or the person chose to discard them. Resolves false when they
 * chose to keep editing, or a save they asked for failed (its message is
 * already in the toolbar, and their text is still there).
 *
 * @param message The question to ask, if there is anything to ask about.
 */
async function settle( message: string ): Promise< boolean > {
	// A save already on its way is the answer to "save or discard?": let it
	// finish rather than restoring the old text under a request that may still
	// succeed. If it fails the text is still there and we ask as usual.
	if ( inflight ) {
		if ( await inflight ) {
			return true;
		}
	}
	if ( ! isDirty() ) {
		return true;
	}

	const choice = await ask( message );

	if ( choice === 'save' ) {
		return save();
	}
	if ( choice === 'discard' ) {
		endEdit( true );
		return true;
	}

	active?.el.focus();
	return false;
}

/**
 * Enter edit mode for one element.
 *
 * @param el     The editable child element.
 * @param root   The block root carrying the data attributes.
 * @param field  The registry field descriptor.
 * @param itemEl The repeater item wrapper carrying data-flexa-item, or null.
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
	// A new edit takes the toolbar over from "Saved · Undo".
	clearUndo( false );
	// Switching from another element. Callers settle unsaved changes first
	// (see bindField), so whatever is left here has nothing to lose.
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
		targetId:
			parseInt(
				root.getAttribute( 'data-flexa-edit-target' ) || '0',
				10
			) || 0,
		link: field.link,
		originalValue: '',
	};
	active.originalValue = currentValue();

	// The "*" of a required field is not part of the text: stop the caret from
	// typing into it, or what is typed there is dropped by currentValue().
	lockMarkers( el, true );
	el.contentEditable = 'true';
	el.classList.add( ACTIVE_CLASS );
	root.classList.add( ACTIVE_CLASS );
	// Text stored in a template part or synced pattern is shared: say so before
	// the person types, not after they have saved a change to every page.
	const scope = root.getAttribute( 'data-flexa-edit-scope' ) || '';
	setMessage( ( scope && boot?.i18n.scope?.[ scope ] ) || '' );
	toggleToolbar( true );
	updateLinkButton();
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
 *
 * @return Whether the edit was saved.
 */
function save(): Promise< boolean > {
	if ( inflight ) {
		return inflight;
	}
	inflight = doSave().finally( () => {
		inflight = null;
	} );
	return inflight;
}

interface EditResult {
	version?: string;
	before?: string;
	logId?: string;
	// For a link edit: the state the server stored, as JSON.
	after?: string;
}

/**
 * Send one edit to the server and keep the stored-content version in step.
 *
 * Throws with the server's own message when the save is refused (a conflict,
 * a lock, a permission), falling back to the HTTP status so an opaque failure
 * is still identifiable.
 *
 * @param payload  Request body.
 * @param targetId Store the edit was made in, to carry the new version to.
 */
async function postEdit(
	payload: Record< string, unknown >,
	targetId: number
): Promise< EditResult > {
	if ( ! boot ) {
		throw new Error( '' );
	}
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
		const detail = ( err && ( err as { message?: string } ).message ) || '';
		throw new Error( detail || `HTTP ${ resp.status }` );
	}

	const result = ( await resp.json().catch( () => ( {} ) ) ) as EditResult;
	if ( result.version ) {
		const next = result.version;
		rootsInStore( targetId ).forEach( ( r ) =>
			r.setAttribute( 'data-flexa-edit-version', next )
		);
	}
	return result;
}

/**
 * Stop offering Undo.
 *
 * @param hideToolbar Whether to hide the toolbar too (not when a new edit has
 *                    just taken it over).
 */
function clearUndo( hideToolbar = true ): void {
	if ( undoState ) {
		window.clearTimeout( undoState.timer );
	}
	undoState = null;
	undoing = false;
	if ( undoBtn ) {
		undoBtn.hidden = true;
		undoBtn.disabled = false;
	}
	toolbar?.classList.remove( 'is-undo' );
	if ( hideToolbar && ! active ) {
		toggleToolbar( false );
		setMessage( '' );
	}
}

/**
 * Show "Saved · Undo" for a few seconds after a save.
 *
 * The toolbar is shown again in a reduced form (message and Undo only). Starting
 * another edit, or letting the time run out, ends the offer.
 *
 * @param state What Undo will do.
 */
function offerUndo( state: UndoState ): void {
	if ( ! toolbar || ! undoBtn ) {
		return;
	}
	clearUndo( false );
	state.timer = window.setTimeout( () => clearUndo(), UNDO_WINDOW_MS );
	undoState = state;
	undoBtn.hidden = false;
	toolbar.classList.add( 'is-undo' );
	toggleToolbar( true );
}

/**
 * Put the text back the way it was before the last save.
 *
 * It is an ordinary save of the old value, sent with the page's current content
 * version, so it is refused (and nothing is overwritten) if anyone changed the
 * content in the meantime, and it is logged as an undo of the edit it reverses.
 */
async function runUndo(): Promise< void > {
	const state = undoState;
	if ( ! state || ! boot || undoing ) {
		return;
	}
	undoing = true;
	window.clearTimeout( state.timer );
	if ( undoBtn ) {
		undoBtn.disabled = true;
	}
	setMessage( boot.i18n.saving );

	const payload: Record< string, unknown > = {
		...state.payload,
		value: state.before,
		undoOf: state.logId,
	};
	const version = state.root.getAttribute( 'data-flexa-edit-version' ) || '';
	if ( version ) {
		payload.version = version;
	}

	try {
		await postEdit( payload, state.targetId );

		// A field made of several elements cannot be put back from one string;
		// the page reloads with the restored content instead.
		if ( state.join !== undefined ) {
			window.location.reload();
			return;
		}
		if ( state.restore ) {
			state.restore();
		} else {
			state.el.innerHTML = state.originalHTML;
		}
		undoState = null;
		undoing = false;
		if ( undoBtn ) {
			undoBtn.hidden = true;
		}
		setMessage( boot.i18n.undone || boot.i18n.saved, 'success' );
		window.setTimeout( () => {
			if ( ! undoState ) {
				clearUndo();
			}
		}, 2500 );
	} catch ( e ) {
		// Say why (usually: the content changed since), keep the message up for
		// a while, and let go of the offer: undoing again would fail the same way.
		const message = e instanceof Error ? e.message : boot.i18n.error;
		setMessage( `${ boot.i18n.error }: ${ message }`, 'error' );
		if ( undoBtn ) {
			undoBtn.hidden = true;
		}
		undoState = null;
		undoing = false;
		window.setTimeout( () => {
			if ( ! undoState ) {
				clearUndo();
			}
		}, 8000 );
	}
}

async function doSave(): Promise< boolean > {
	if ( ! active || ! boot || saving ) {
		return false;
	}
	saving = true;
	updateGuard();
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
	if ( active.targetId > 0 ) {
		payload.targetId = active.targetId;
	}
	// Read from the root at save time rather than at startEdit: an earlier
	// save on this page may have moved the store to a newer version since.
	const version = active.root.getAttribute( 'data-flexa-edit-version' ) || '';
	if ( version ) {
		payload.version = version;
	}

	try {
		const result = await postEdit( payload, active.targetId );

		// What Undo needs, taken before the edit ends and `active` is cleared.
		// Nothing to undo when the save changed nothing (no log id then).
		const undo: UndoState | null =
			result.logId && typeof result.before === 'string'
				? {
						payload: {
							postId: payload.postId,
							blockId: payload.blockId,
							blockName: payload.blockName,
							attr: payload.attr,
							...( payload.index !== undefined && {
								index: payload.index,
							} ),
							...( payload.subindex !== undefined && {
								subindex: payload.subindex,
							} ),
							...( payload.key !== undefined && {
								key: payload.key,
							} ),
							...( payload.targetId !== undefined && {
								targetId: payload.targetId,
							} ),
						},
						before: result.before,
						logId: result.logId,
						el: active.el,
						root: active.root,
						originalHTML: active.originalHTML,
						join: active.join,
						targetId: active.targetId,
						timer: 0,
				  }
				: null;

		setMessage( boot.i18n.saved, 'success' );
		endEdit( false );
		if ( undo ) {
			offerUndo( undo );
		}
		return true;
	} catch ( e ) {
		// The edit stays open with the person's text in it: a conflict (the
		// content changed, or someone has it open in the editor) must never
		// cost them what they typed. The server's message says what to do.
		saving = false;
		updateGuard();
		const message = e instanceof Error ? e.message : boot.i18n.error;
		setMessage( `${ boot.i18n.error }: ${ message }`, 'error' );
		return false;
	}
}

interface LinkState {
	url: string;
	newTab: boolean;
}

/**
 * The <a> a text field's link lives on, if the page has it.
 *
 * @param root Block root.
 * @param link Link descriptor from the registry.
 */
function anchorOf( root: HTMLElement, link: LinkDef ): HTMLAnchorElement | null {
	return root.querySelector< HTMLAnchorElement >( link.anchor );
}

/**
 * Show "Edit link" only while editing text that has a link to edit.
 */
function updateLinkButton(): void {
	if ( ! linkBtn ) {
		return;
	}
	linkBtn.hidden = ! (
		active &&
		active.link &&
		anchorOf( active.root, active.link )
	);
	if ( boot?.i18n.linkEdit ) {
		linkBtn.textContent = boot.i18n.linkEdit;
	}
}

/**
 * The link as the page shows it now.
 *
 * @param anchor The <a>.
 */
function linkStateOf( anchor: HTMLAnchorElement ): LinkState {
	return {
		url: anchor.getAttribute( 'href' ) || '',
		newTab: anchor.getAttribute( 'target' ) === '_blank',
	};
}

/**
 * Make an <a> match a link state, as the server would render it.
 *
 * @param anchor The <a>.
 * @param state  Address and whether it opens in a new tab.
 */
function paintLink( anchor: HTMLAnchorElement, state: LinkState ): void {
	if ( state.url ) {
		anchor.setAttribute( 'href', state.url );
	} else {
		anchor.removeAttribute( 'href' );
	}
	if ( state.newTab ) {
		anchor.setAttribute( 'target', '_blank' );
		if ( ! anchor.getAttribute( 'rel' ) ) {
			anchor.setAttribute( 'rel', 'noopener noreferrer' );
		}
	} else {
		anchor.removeAttribute( 'target' );
		if ( anchor.getAttribute( 'rel' ) === 'noopener noreferrer' ) {
			anchor.removeAttribute( 'rel' );
		}
	}
}

/**
 * Whether an address uses a scheme other than the four a link may use.
 *
 * Only a courtesy to the person typing (the server decides, and says the same
 * thing): a colon before the first / ? or # makes what precedes it a scheme.
 *
 * @param url Address as typed.
 */
function badScheme( url: string ): boolean {
	const v = url.replace( /[\u0000-\u001f\u007f]+/g, '' ).trim();
	return /^[^/?#]*:/.test( v ) && ! /^(?:https?|mailto|tel):/i.test( v );
}

/**
 * Open the link editor for the text being edited.
 *
 * The text edit ends first (after the usual question if it has unsaved changes),
 * so the dialog and "Saved · Undo" never compete with an edit still in progress.
 */
async function startLinkEdit(): Promise< void > {
	if ( ! active || ! active.link || ! boot ) {
		return;
	}
	const ctx = {
		root: active.root,
		link: active.link,
		blockId: active.blockId,
		blockName: active.blockName,
		targetId: active.targetId,
	};

	if ( isDirty() ) {
		if ( ! ( await settle( boot.i18n.unsaved || '' ) ) ) {
			return;
		}
	}
	// Not dirty: settle() left the edit open; close it without changing anything.
	if ( active ) {
		endEdit( true );
	}

	const anchor = anchorOf( ctx.root, ctx.link );
	if ( anchor ) {
		openLinkDialog( { ...ctx, anchor } );
	}
}

/**
 * The address / new-tab dialog, and what Apply does.
 *
 * @param ctx Where the link is and how to save it.
 */
function openLinkDialog( ctx: {
	root: HTMLElement;
	link: LinkDef;
	anchor: HTMLAnchorElement;
	blockId: string;
	blockName: string;
	targetId: number;
} ): void {
	const dialog = linkDialog;
	if ( ! dialog || ! boot ) {
		return;
	}
	const form = dialog.querySelector< HTMLFormElement >( 'form' );
	const input = dialog.querySelector< HTMLInputElement >( '#flexa-fee-link-url' );
	const tab = dialog.querySelector< HTMLInputElement >( '#flexa-fee-link-tab' );
	const error = dialog.querySelector< HTMLElement >( '.flexa-fee-linkdialog__error' );
	const apply = dialog.querySelector< HTMLButtonElement >( '[data-link="apply"]' );
	if ( ! form || ! input || ! tab || ! error || ! apply ) {
		return;
	}

	const current = linkStateOf( ctx.anchor );
	input.value = current.url;
	tab.checked = current.newTab;
	error.hidden = true;
	error.textContent = '';
	apply.disabled = false;

	const fail = ( message: string ) => {
		error.textContent = message;
		error.hidden = false;
		apply.disabled = false;
		input.focus();
	};

	const close = () => {
		dialog.setAttribute( 'hidden', '' );
		document.body.classList.remove( 'flexa-fee-modal-open' );
		form.removeEventListener( 'submit', onSubmit );
		dialog.removeEventListener( 'click', onClick );
		document.removeEventListener( 'keydown', onKey, true );
	};

	const onSubmit = ( e: Event ) => {
		e.preventDefault();
		const url = input.value.trim();
		if ( badScheme( url ) ) {
			fail( boot?.i18n.linkInvalid || '' );
			return;
		}
		apply.disabled = true;
		error.hidden = true;

		const payload: Record< string, unknown > = {
			postId: boot?.postId,
			blockId: ctx.blockId,
			blockName: ctx.blockName,
			attr: ctx.link.attr,
			link: true,
			value: JSON.stringify( { url, newTab: tab.checked } ),
		};
		if ( ctx.targetId > 0 ) {
			payload.targetId = ctx.targetId;
		}
		const version = ctx.root.getAttribute( 'data-flexa-edit-version' ) || '';
		if ( version ) {
			payload.version = version;
		}

		postEdit( payload, ctx.targetId )
			.then( ( result ) => {
				close();
				// What the server stored (cleaned), not what was typed.
				let saved: LinkState = { url, newTab: tab.checked };
				try {
					if ( result.after ) {
						saved = JSON.parse( result.after ) as LinkState;
					}
				} catch ( _e ) {
					// Keep what was typed.
				}
				paintLink( ctx.anchor, saved );
				setMessage( boot?.i18n.saved || '', 'success' );

				if ( result.logId && typeof result.before === 'string' ) {
					let previous: LinkState = current;
					try {
						previous = JSON.parse( result.before ) as LinkState;
					} catch ( _e ) {
						// Fall back to what the page showed.
					}
					offerUndo( {
						payload: {
							postId: payload.postId,
							blockId: ctx.blockId,
							blockName: ctx.blockName,
							attr: ctx.link.attr,
							link: true,
							...( ctx.targetId > 0 && { targetId: ctx.targetId } ),
						},
						before: result.before,
						logId: result.logId,
						el: ctx.anchor,
						root: ctx.root,
						originalHTML: '',
						targetId: ctx.targetId,
						timer: 0,
						restore: () => paintLink( ctx.anchor, previous ),
					} );
				}
			} )
			.catch( ( e: unknown ) => {
				// Refused (a conflict, a lock, a bad address): stay open and say why.
				fail( e instanceof Error && e.message ? e.message : boot?.i18n.error || '' );
			} );
	};

	const onClick = ( e: Event ) => {
		const target = e.target as HTMLElement;
		// The backdrop and the Cancel button both mean "leave it as it was".
		if ( target === dialog || target.closest( '[data-link="cancel"]' ) ) {
			close();
		}
	};

	const onKey = ( e: KeyboardEvent ) => {
		if ( e.key === 'Escape' ) {
			e.preventDefault();
			e.stopPropagation();
			close();
			return;
		}
		if ( e.key === 'Tab' ) {
			const stops = Array.from(
				dialog.querySelectorAll< HTMLElement >( 'input, button' )
			).filter( ( el ) => ! ( el as HTMLButtonElement ).disabled );
			const first = stops[ 0 ];
			const last = stops[ stops.length - 1 ];
			if ( ! first || ! last ) {
				return;
			}
			if ( e.shiftKey && dialog.ownerDocument.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && dialog.ownerDocument.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		}
	};

	form.addEventListener( 'submit', onSubmit );
	dialog.addEventListener( 'click', onClick );
	document.addEventListener( 'keydown', onKey, true );
	dialog.removeAttribute( 'hidden' );
	document.body.classList.add( 'flexa-fee-modal-open' );
	input.focus();
	input.select();
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
	// The toolbar's own buttons, not the prompt's (which share the classes).
	saveBtn = toolbar.querySelector(
		'.flexa-fee-toolbar__inner > .flexa-fee-btn--save'
	);
	cancelBtn = toolbar.querySelector(
		'.flexa-fee-toolbar__inner > .flexa-fee-btn--cancel'
	);
	promptEl = document.querySelector(
		'.flexa-fee-toolbar__prompt:not(.flexa-fee-linkdialog)'
	);
	promptMsgEl =
		promptEl?.querySelector( '.flexa-fee-toolbar__prompt-msg' ) || null;

	saveBtn?.addEventListener( 'click', () => {
		void save();
	} );
	linkBtn = toolbar.querySelector(
		'.flexa-fee-toolbar__inner > .flexa-fee-btn--link'
	);
	linkBtn?.addEventListener( 'click', () => {
		void startLinkEdit();
	} );
	linkDialog = document.querySelector( '.flexa-fee-linkdialog' );
	undoBtn = toolbar.querySelector( '.flexa-fee-toolbar__inner > .flexa-fee-btn--undo' );
	undoBtn?.addEventListener( 'click', () => {
		void runUndo();
	} );
	cancelBtn?.addEventListener( 'click', () => {
		// Not while a save is on its way: it may still succeed.
		if ( ! inflight ) {
			endEdit( true );
		}
	} );
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
		: ( [ root.querySelector< HTMLElement >( field.selector ) ].filter(
				Boolean
		  ) as HTMLElement[] );

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
				// Text inside a link: keep the caret here, do not follow the link.
				e.preventDefault();
				return;
			}
			// Editable text often sits inside a link (button, linked heading) —
			// stop navigation and start editing instead.
			e.preventDefault();
			e.stopPropagation();
			// Moving on from another text with unsaved changes: ask first, and
			// only switch once those changes are saved or deliberately dropped.
			if ( active && isDirty() ) {
				void settle( boot?.i18n.unsaved || '' ).then( ( ok ) => {
					if ( ok ) {
						startEdit( el, root, field, itemEl );
					}
				} );
				return;
			}
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

	// Escape cancels (asking first if something would be lost); Ctrl/Cmd+Enter
	// saves. While the unsaved-changes question is open it owns the keyboard.
	document.addEventListener( 'keydown', ( e ) => {
		if ( ! active || pendingAsk ) {
			return;
		}
		if ( e.key === 'Escape' ) {
			e.preventDefault();
			if ( isDirty() ) {
				void settle( boot.i18n.discard || '' );
			} else {
				endEdit( true );
			}
		} else if ( ( e.metaKey || e.ctrlKey ) && e.key === 'Enter' ) {
			e.preventDefault();
			void save();
		}
	} );

	// Some browsers (Firefox) park the caret inside an uneditable marker, such as
	// the "*" of a required field, and then swallow what is typed. Step out of it
	// to just after the marker before the text goes in.
	document.addEventListener(
		'beforeinput',
		() => {
			if ( ! active ) {
				return;
			}
			const sel = window.getSelection();
			const node = sel?.anchorNode;
			if ( ! sel || ! sel.isCollapsed || ! node ) {
				return;
			}
			const host = ( node instanceof Element ? node : node.parentElement )?.closest(
				'.flexa-field__required'
			);
			if ( host && active.el.contains( host ) ) {
				const range = document.createRange();
				range.setStartAfter( host );
				range.collapse( true );
				sel.removeAllRanges();
				sel.addRange( range );
			}
		},
		true
	);

	// Typing is what makes an edit dirty; keep the beforeunload guard in step.
	document.addEventListener(
		'input',
		() => {
			if ( active ) {
				updateGuard();
			}
		},
		true
	);

	// A link on the page while an edit has unsaved changes: stop it, ask, and
	// follow it once the changes are saved or discarded. Runs in the capture
	// phase so it sees the click before the page's own handlers act on it.
	document.addEventListener(
		'click',
		( e ) => {
			if ( ! isDirty() || ! ( e.target instanceof Element ) ) {
				return;
			}
			const target = e.target;
			// The toolbar, and editable text (bindField asks for those itself).
			if (
				toolbar?.contains( target ) ||
				target.closest( `.${ EDITABLE_CLASS }` )
			) {
				return;
			}
			const link = target.closest< HTMLAnchorElement >( 'a[href]' );
			if ( ! link ) {
				return;
			}
			// Anything that does not leave this page needs no question: a new
			// tab or window, a download, an in-page anchor, a script link.
			const href = link.getAttribute( 'href' ) || '';
			if (
				e.button !== 0 ||
				e.metaKey ||
				e.ctrlKey ||
				e.shiftKey ||
				e.altKey ||
				( link.target && link.target !== '_self' ) ||
				link.hasAttribute( 'download' ) ||
				href.startsWith( '#' ) ||
				/^javascript:/i.test( href )
			) {
				return;
			}

			e.preventDefault();
			e.stopPropagation();
			const destination = link.href;
			void settle( boot.i18n.unsaved || '' ).then( ( ok ) => {
				if ( ok ) {
					window.location.assign( destination );
				}
			} );
		},
		true
	);

	// The same for a form on the page (search, contact form…).
	document.addEventListener(
		'submit',
		( e ) => {
			if ( bypassSubmit || ! isDirty() ) {
				return;
			}
			const form = e.target;
			if ( ! ( form instanceof HTMLFormElement ) ) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			const submitter = ( e as SubmitEvent ).submitter;
			void settle( boot.i18n.unsaved || '' ).then( ( ok ) => {
				if ( ! ok ) {
					return;
				}
				// Submitted again through requestSubmit() so the form's own
				// submit handlers still run; the flag keeps this listener out.
				bypassSubmit = true;
				if ( submitter && form.contains( submitter ) ) {
					form.requestSubmit( submitter );
				} else {
					form.requestSubmit();
				}
				bypassSubmit = false;
			} );
		},
		true
	);
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
