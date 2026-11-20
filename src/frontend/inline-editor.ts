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
	// The field's allowlist name (plain, minimal, minimal-link, inline, rich, post).
	allowed?: string;
	// The link that wraps this text, when it can be edited: the URL attribute,
	// its target attribute, and a selector (within the block root) for the <a>.
	link?: LinkDef;
}

interface LinkDef {
	attr: string;
	target: string;
	anchor: string;
	// Set when the link lives inside the list item (items[i][item]); the
	// item's own element is then the <a>.
	item?: string;
}

// A picture a block keeps: the attribute, the <img>, and (optionally) the
// element to click when overlays sit on top of it. An image in a list item
// has `repeat` + `key`.
interface ImageDef {
	attr: string;
	selector: string;
	hit?: string;
	repeat?: boolean;
	key?: string;
	// The picture is the block root's CSS background, not an <img>.
	background?: boolean;
	// Open the editor from a button at this corner instead of from a click on
	// the picture, inside `host` (a selector within the root) or the root.
	button?: string;
	host?: string;
}

// An icon a block keeps: the attribute and the element to click. With `wrap`
// the element only holds the icon (its contents are replaced); without it the
// element is the icon itself. An icon in a list item has `repeat` + `key`.
interface IconDef {
	attr: string;
	selector: string;
	wrap?: boolean;
	// The text attribute (or list key) this icon goes with: editing that text
	// offers "Add icon" when the icon is not there yet.
	with?: string;
	repeat?: boolean;
	key?: string;
}

interface Boot {
	postId: number;
	nonce: string;
	endpoint: string;
	fields: Record< string, FieldDef[] >;
	images?: Record< string, ImageDef[] >;
	icons?: Record< string, IconDef[] >;
	// Review: edits to this published page go to a copy (editLink once one exists).
	review?: { on: boolean; editLink: string; blocks?: string[] };
	iconsUrl?: string;
	// Whether the person may choose from the media library (and so whether the
	// library was loaded on this page).
	canPick?: boolean;
	i18n: {
		editing: string;
		save: string;
		cancel: string;
		saved: string;
		saving: string;
		// The Undo button after a save, and what is said once it has worked.
		undo?: string;
		undone?: string;
		// The image editor.
		imageEdit?: string;
		imageBg?: string;
		iconEdit?: string;
		textEdit?: string;
		reviewNote?: string;
		reviewSaved?: string;
		reviewPending?: string;
		reviewOpen?: string;
		reviewBadge?: string;
		iconLoadError?: string;
		iconNone?: string;
		iconUpload?: string;
		imageBefore?: string;
		imageAfter?: string;
		imageNoWp?: string;
		imageTitle?: string;
		imageUse?: string;
		// The link editor.
		linkEdit?: string;
		linkAdd?: string;
		iconAdd?: string;
		reloading?: string;
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
	allowed?: string;
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
let addIconBtn: HTMLButtonElement | null = null;
let linkDialog: HTMLElement | null = null;
let imageDialog: HTMLElement | null = null;
let iconDialog: HTMLElement | null = null;
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
	if ( addIconBtn ) {
		addIconBtn.hidden = true;
	}
	active.el.contentEditable = 'false';
	active.el.setAttribute( 'role', 'button' );
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
		allowed: field.join !== undefined ? 'plain' : field.allowed,
		originalValue: '',
	};
	active.originalValue = currentValue();

	// The "*" of a required field is not part of the text: stop the caret from
	// typing into it, or what is typed there is dropped by currentValue().
	lockMarkers( el, true );
	el.contentEditable = 'true';
	// While edited it is a text box, not a button to press.
	el.removeAttribute( 'role' );
	el.classList.add( ACTIVE_CLASS );
	root.classList.add( ACTIVE_CLASS );
	// Text stored in a template part or synced pattern is shared: say so before
	// the person types, not after they have saved a change to every page.
	const scope = root.getAttribute( 'data-flexa-edit-scope' ) || '';
	setMessage(
		( scope && boot?.i18n.scope?.[ scope ] ) ||
			( ! scope && boot?.review?.on ? boot.i18n.reviewNote || '' : '' )
	);
	toggleToolbar( true );
	updateLinkButton();
	el.focus();
	// Opened from the keyboard (or by script), there is no caret yet, and
	// Safari then ignores typing. A click has put one where it landed; in
	// every other case it goes at the end of the text.
	const sel = window.getSelection();
	if ( sel && ( ! sel.rangeCount || ! el.contains( sel.anchorNode ) ) ) {
		const range = document.createRange();
		range.selectNodeContents( el );
		range.collapse( false );
		sel.removeAllRanges();
		sel.addRange( range );
	}
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
	// For a link or image edit: the state the server stored, as JSON.
	after?: string;
	// False when the change cannot be undone (an image that was not from the library).
	undo?: boolean;
	// Set when the edit went to a review copy.
	review?: { copyId: number; editLink: string; created: boolean };
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
	if ( result.review?.editLink ) {
		showReviewLink( result.review.editLink );
		const id = payload.blockId;
		if ( typeof id === 'string' && boot.review ) {
			const blocks = boot.review.blocks || [];
			if ( ! blocks.includes( id ) ) {
				blocks.push( id );
			}
			boot.review.blocks = blocks;
			paintPending();
		}
	}
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
			result.logId && typeof result.before === 'string' && result.undo !== false
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

		setMessage(
			result.review ? boot.i18n.reviewSaved || boot.i18n.saved : boot.i18n.saved,
			'success'
		);
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
function anchorOf(
	root: HTMLElement,
	link: LinkDef,
	itemEl?: HTMLElement | null
): HTMLAnchorElement | null {
	if ( link.item ) {
		// A row's link: the row itself, or an <a> inside it. Never another row's.
		if ( ! itemEl ) {
			return null;
		}
		return itemEl.matches( link.anchor )
			? ( itemEl as HTMLAnchorElement )
			: itemEl.querySelector< HTMLAnchorElement >( link.anchor );
	}
	return root.querySelector< HTMLAnchorElement >( link.anchor );
}

/**
 * Show "Edit link" only while editing text that has a link to edit.
 */
function updateLinkButton(): void {
	if ( ! linkBtn ) {
		return;
	}
	// "Edit link" where the text sits in a link; "Add link" where it could but
	// does not (a heading without one, a list row without one).
	const has =
		!! active && !! active.link && !! anchorOf( active.root, active.link, active.itemEl );
	linkBtn.hidden = ! ( active && active.link );
	linkBtn.textContent =
		( has ? boot?.i18n.linkEdit : boot?.i18n.linkAdd || boot?.i18n.linkEdit ) || '';
	updateAddIcon();
}

/**
 * The icon declaration that goes with the text being edited, if that text has
 * no icon yet (so "Add icon" makes sense).
 */
function missingIcon(): { def: IconDef; name: string } | null {
	if ( ! active || ! boot?.icons ) {
		return null;
	}
	const name = active.root.getAttribute( 'data-flexa-edit-name' ) || '';
	const field = active.key ?? active.attr;
	const def = ( boot.icons[ name ] || [] ).find(
		( d ) => d.with === field && ( d.repeat ? d.attr === active?.attr : true )
	);
	if ( ! def ) {
		return null;
	}
	const scope = def.repeat ? active.itemEl : active.root;
	if ( ! scope ) {
		return null;
	}
	const present = Array.from( scope.querySelectorAll< HTMLElement >( def.selector ) ).some(
		( el ) => el.closest( '[data-flexa-edit-id]' ) === active?.root && ( ! def.wrap || el.childElementCount > 0 )
	);
	return present ? null : { def, name };
}

/**
 * Show "Add icon" while editing text that could have an icon but has none.
 */
function updateAddIcon(): void {
	if ( ! addIconBtn ) {
		return;
	}
	addIconBtn.hidden = ! missingIcon();
	addIconBtn.textContent = boot?.i18n.iconAdd || addIconBtn.textContent;
}

/**
 * Open the icon editor to add an icon to the text being edited.
 *
 * There is no icon element on the page to update in place (where it goes,
 * before or after the text, is the block's to decide), so the page reloads
 * once it is saved.
 */
async function startAddIcon(): Promise< void > {
	const found = missingIcon();
	if ( ! active || ! found || ! boot ) {
		return;
	}
	const root = active.root;
	const index = active.index;
	if ( isDirty() && ! ( await settle( boot.i18n.unsaved || '' ) ) ) {
		return;
	}
	if ( active ) {
		endEdit( true );
	}
	openIconDialog( root, found.name, found.def, document.createElement( 'span' ), index, true );
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
		index: active.index,
	};
	const itemEl = active.itemEl;

	if ( isDirty() ) {
		if ( ! ( await settle( boot.i18n.unsaved || '' ) ) ) {
			return;
		}
	}
	// Not dirty: settle() left the edit open; close it without changing anything.
	if ( active ) {
		endEdit( true );
	}

	// No <a> yet: the dialog adds the link and the page reloads to show it.
	openLinkDialog( { ...ctx, anchor: anchorOf( ctx.root, ctx.link, itemEl ) } );
}

/**
 * The address / new-tab dialog, and what Apply does.
 *
 * @param ctx Where the link is and how to save it.
 */
function openLinkDialog( ctx: {
	root: HTMLElement;
	link: LinkDef;
	anchor: HTMLAnchorElement | null;
	blockId: string;
	blockName: string;
	targetId: number;
	index?: number;
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

	const current: LinkState = ctx.anchor
		? linkStateOf( ctx.anchor )
		: { url: '', newTab: false };
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
		// A row's link names its row.
		if ( ctx.link.item ) {
			payload.index = ctx.index;
			payload.key = ctx.link.item;
		}
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
				if ( ! ctx.anchor ) {
					// The block draws the new link itself; show it by reloading.
					setMessage( boot?.i18n.reloading || boot?.i18n.saved || '', 'success' );
					toggleToolbar( true );
					window.location.reload();
					return;
				}
				const anchor = ctx.anchor;
				paintLink( anchor, saved );
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
							...( ctx.link.item && { index: ctx.index, key: ctx.link.item } ),
							...( ctx.targetId > 0 && { targetId: ctx.targetId } ),
						},
						before: result.before,
						logId: result.logId,
						el: anchor,
						root: ctx.root,
						originalHTML: '',
						targetId: ctx.targetId,
						timer: 0,
						restore: () => paintLink( anchor, previous ),
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

interface ImageCtx {
	// Null for a CSS background.
	img: HTMLImageElement | null;
	root: HTMLElement;
	def: ImageDef;
	index?: number;
	blockId: string;
	blockName: string;
	targetId: number;
}

interface MediaSelection {
	id: number;
	url: string;
	alt?: string;
	sizes?: Record< string, { url: string } >;
}

// A WordPress media frame, as far as this file uses one.
interface MediaFrame {
	on: ( event: string, cb: () => void ) => void;
	open: () => void;
	state: () => {
		get: ( name: string ) => { first: () => { toJSON: () => MediaSelection } };
	};
}

/**
 * The context an image editor needs, for one image of one block.
 *
 * @param root  Block root.
 * @param name  Block name.
 * @param def   Image declaration.
 * @param img   The <img>, or null for a CSS background.
 * @param index List item index, for an image in a list.
 */
function imageCtx(
	root: HTMLElement,
	name: string,
	def: ImageDef,
	img: HTMLImageElement | null,
	index?: number
): ImageCtx {
	return {
		img,
		root,
		def,
		index,
		blockId: root.getAttribute( 'data-flexa-edit-id' ) || '',
		blockName: name,
		targetId:
			parseInt( root.getAttribute( 'data-flexa-edit-target' ) || '0', 10 ) ||
			0,
	};
}

/**
 * Label for the button that opens an image editor.
 *
 * @param def Image declaration.
 */
function imageButtonLabel( def: ImageDef ): string {
	const i18n = boot?.i18n;
	if ( def.background ) {
		return i18n?.imageBg || '';
	}
	if ( def.attr === 'beforeImage' ) {
		return i18n?.imageBefore || '';
	}
	if ( def.attr === 'afterImage' ) {
		return i18n?.imageAfter || '';
	}
	return i18n?.imageEdit || '';
}

/**
 * Make a block's image editable.
 *
 * Most images open the editor when clicked, instead of doing what they normally
 * do (follow a link, open a lightbox or a video). That listener sits on the
 * block root in the capture phase, so it runs before anything the block itself
 * listens for, whichever element under the picture receives the click.
 *
 * Some images cannot be clicked that way: a CSS background has no element of
 * its own, and pressing a before/after picture drags the slider. Those get a
 * small button instead (`button` in the declaration); see onImageButton().
 *
 * @param root Block root.
 * @param name Block name.
 * @param def  Image declaration from the registry.
 */
function bindImage( root: HTMLElement, name: string, def: ImageDef ): void {
	if ( def.button ) {
		// Only a new picture can change a background, and that needs the library.
		if ( def.background && ! boot?.canPick ) {
			return;
		}
		const host =
			( def.host && root.querySelector< HTMLElement >( def.host ) ) || root;
		const defs = boot?.images?.[ name ] || [];
		const btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = `flexa-fee-imgbtn flexa-fee-imgbtn--${ def.button }`;
		btn.setAttribute( 'data-fee-image', String( defs.indexOf( def ) ) );
		btn.textContent = imageButtonLabel( def );
		if ( getComputedStyle( host ).position === 'static' ) {
			host.classList.add( 'flexa-fee-imgbtn-host' );
		}
		host.appendChild( btn );
		return;
	}

	const imgs = def.repeat
		? Array.from( root.querySelectorAll< HTMLImageElement >( def.selector ) )
		: ( [ root.querySelector< HTMLImageElement >( def.selector ) ].filter(
				Boolean
		  ) as HTMLImageElement[] );

	imgs.forEach( ( img ) => {
		// An image of a block nested inside this one belongs to that block.
		if ( img.closest( '[data-flexa-edit-id]' ) !== root ) {
			return;
		}
		let index: number | undefined;
		if ( def.repeat ) {
			const item = img.closest< HTMLElement >( '[data-flexa-item]' );
			if ( ! item ) {
				return;
			}
			index = parseInt( item.getAttribute( 'data-flexa-item' ) || '', 10 );
			if ( Number.isNaN( index ) ) {
				return;
			}
		}

		const hit =
			( def.hit && img.closest< HTMLElement >( def.hit ) ) || img;
		if ( hit.classList.contains( 'flexa-fee-image-hit' ) ) {
			return;
		}
		hit.classList.add( 'flexa-fee-image-hit' );
		if ( ! hit.getAttribute( 'title' ) ) {
			hit.setAttribute( 'title', boot?.i18n.imageEdit || '' );
		}
		makeOperable( hit, boot?.i18n.imageEdit || '' );

		root.addEventListener(
			'click',
			( e ) => {
				if ( ! ( e.target instanceof Node ) || ! hit.contains( e.target ) ) {
					return;
				}
				e.preventDefault();
				e.stopPropagation();
				void startImageEdit( imageCtx( root, name, def, img, index ) );
			},
			true
		);
	} );
}

/**
 * A click on one of the image buttons placed by bindImage().
 *
 * Delegated from the document, because a slider may copy its slides (and the
 * buttons in them) after this script has run, and copies carry no listeners.
 *
 * @param e The click.
 */
function onImageButton( e: Event ): void {
	const btn =
		e.target instanceof Element
			? e.target.closest< HTMLElement >( '.flexa-fee-imgbtn' )
			: null;
	if ( ! btn || ! boot ) {
		return;
	}
	e.preventDefault();
	e.stopPropagation();
	const root = btn.closest< HTMLElement >( '[data-flexa-edit-id]' );
	if ( ! root ) {
		return;
	}
	const name = root.getAttribute( 'data-flexa-edit-name' ) || '';
	const def = boot.images?.[ name ]?.[
		parseInt( btn.getAttribute( 'data-fee-image' ) || '-1', 10 )
	];
	if ( ! def ) {
		return;
	}
	const img = def.background
		? null
		: root.querySelector< HTMLImageElement >( def.selector );
	void startImageEdit( imageCtx( root, name, def, img ) );
}

/**
 * Open the image editor, after settling any text that has unsaved changes.
 *
 * @param ctx Which image, and where it is stored.
 */
async function startImageEdit( ctx: ImageCtx ): Promise< void > {
	if ( ! boot || undoing ) {
		return;
	}
	if ( active ) {
		if ( isDirty() && ! ( await settle( boot.i18n.unsaved || '' ) ) ) {
			return;
		}
		if ( active ) {
			endEdit( true );
		}
	}
	openImageDialog( ctx );
}

/**
 * Put an <img> back to the attributes it had.
 *
 * @param img   The element.
 * @param attrs Attribute values; null removes the attribute.
 */
function setImgAttrs(
	img: HTMLImageElement,
	attrs: Record< string, string | null >
): void {
	Object.keys( attrs ).forEach( ( k ) => {
		const v = attrs[ k ];
		if ( v === null ) {
			img.removeAttribute( k );
		} else {
			img.setAttribute( k, v );
		}
	} );
}

/**
 * The picture and alt-text dialog, and what Apply does.
 *
 * @param ctx Which image, and where it is stored.
 */
function openImageDialog( ctx: ImageCtx ): void {
	const dialog = imageDialog;
	if ( ! dialog || ! boot ) {
		return;
	}
	const form = dialog.querySelector< HTMLFormElement >( 'form' );
	const preview = dialog.querySelector< HTMLImageElement >(
		'.flexa-fee-imagedialog__preview img'
	);
	const altInput = dialog.querySelector< HTMLInputElement >( '#flexa-fee-image-alt' );
	const pick = dialog.querySelector< HTMLButtonElement >( '[data-image="pick"]' );
	const error = dialog.querySelector< HTMLElement >( '.flexa-fee-linkdialog__error' );
	const apply = dialog.querySelector< HTMLButtonElement >( '[data-image="apply"]' );
	if ( ! form || ! preview || ! altInput || ! pick || ! error || ! apply ) {
		return;
	}

	const { img } = ctx;
	const isBg = ! img;
	const startAlt = img ? img.getAttribute( 'alt' ) || '' : '';
	let chosenId: number | null = null;
	let picking = false;

	// Every element showing this block (a slider may hold copies of a slide).
	const copies = Array.from(
		document.querySelectorAll< HTMLElement >( '[data-flexa-edit-id]' )
	).filter( ( el ) => el.getAttribute( 'data-flexa-edit-id' ) === ctx.blockId );

	if ( img ) {
		preview.src = img.currentSrc || img.src;
	} else {
		const m = /url\((['"]?)(.*?)\1\)/.exec(
			getComputedStyle( ctx.root ).backgroundImage
		);
		preview.src = m ? m[ 2 ] : '';
	}
	altInput.value = startAlt;
	// A background has no alt text: it is decoration by definition.
	dialog
		.querySelectorAll< HTMLElement >(
			'label[for="flexa-fee-image-alt"], #flexa-fee-image-alt, .flexa-fee-linkdialog__hint'
		)
		.forEach( ( el ) => {
			el.hidden = isBg;
		} );
	const title = dialog.querySelector< HTMLElement >( '.flexa-fee-toolbar__prompt-title' );
	if ( title ) {
		title.textContent = imageButtonLabel( ctx.def ) || boot.i18n.imageEdit || '';
	}
	error.hidden = true;
	error.textContent = '';
	apply.disabled = false;
	// Without the right to use the library only the alt text can change.
	pick.hidden = ! boot.canPick;

	const fail = ( message: string ) => {
		error.textContent = message;
		error.hidden = false;
		apply.disabled = false;
	};

	const close = () => {
		dialog.setAttribute( 'hidden', '' );
		document.body.classList.remove( 'flexa-fee-modal-open' );
		form.removeEventListener( 'submit', onSubmit );
		dialog.removeEventListener( 'click', onClick );
		document.removeEventListener( 'keydown', onKey, true );
	};

	const choose = () => {
		const media = (
			window as unknown as {
				wp?: { media?: ( o: Record< string, unknown > ) => MediaFrame };
			}
		 ).wp?.media;
		if ( ! media ) {
			fail( boot?.i18n.imageNoWp || '' );
			return;
		}
		const frame = media( {
			title: boot?.i18n.imageTitle || '',
			button: { text: boot?.i18n.imageUse || '' },
			library: { type: 'image' },
			multiple: false,
		} );
		picking = true;
		frame.on( 'select', () => {
			const att = frame.state().get( 'selection' ).first().toJSON();
			chosenId = att.id;
			preview.src = att.sizes?.medium?.url || att.url;
			// The picture's own alt text, as the block editor would fill in.
			altInput.value = att.alt || '';
			error.hidden = true;
		} );
		// Escape inside the media modal must not also close this dialog.
		frame.on( 'close', () => {
			window.setTimeout( () => {
				picking = false;
			}, 0 );
		} );
		frame.open();
	};

	const onSubmit = ( e: Event ) => {
		e.preventDefault();
		const alt = altInput.value.trim();
		if ( chosenId === null && ( isBg || alt === startAlt.trim() ) ) {
			close();
			return;
		}
		apply.disabled = true;
		error.hidden = true;

		const payload: Record< string, unknown > = {
			postId: boot?.postId,
			blockId: ctx.blockId,
			blockName: ctx.blockName,
			attr: ctx.def.attr,
			image: true,
			value: JSON.stringify( isBg ? { id: chosenId } : { id: chosenId, alt } ),
		};
		if ( ctx.def.repeat ) {
			payload.index = ctx.index;
			if ( ctx.def.key ) {
				payload.key = ctx.def.key;
			}
		}
		if ( ctx.targetId > 0 ) {
			payload.targetId = ctx.targetId;
		}
		const version = ctx.root.getAttribute( 'data-flexa-edit-version' ) || '';
		if ( version ) {
			payload.version = version;
		}

		// What the page looks like now, to put back on Undo.
		const previous: Record< string, string | null > = {};
		if ( img ) {
			[ 'src', 'srcset', 'sizes', 'alt', 'width', 'height' ].forEach( ( k ) => {
				previous[ k ] = img.getAttribute( k );
			} );
		}
		const previousBg = copies.map( ( el ) => el.style.backgroundImage );
		const restore = () => {
			if ( img ) {
				setImgAttrs( img, previous );
			} else {
				copies.forEach( ( el, i ) => {
					el.style.backgroundImage = previousBg[ i ];
				} );
			}
		};

		postEdit( payload, ctx.targetId )
			.then( ( result ) => {
				close();
				// What the server stored, read from the library, not what was typed.
				try {
					const saved = JSON.parse( result.after || '{}' ) as {
						src?: string;
						alt?: string;
						width?: number | null;
						height?: number | null;
					};
					if ( ! img ) {
						// The stylesheet is rebuilt on the server; until the page is
						// reloaded, show the new picture from an inline style.
						if ( saved.src ) {
							const css = `url("${ saved.src.replace( /"/g, '%22' ) }")`;
							copies.forEach( ( el ) => {
								el.style.backgroundImage = css;
							} );
						}
					} else {
						const next: Record< string, string | null > = {
							alt: saved.alt ?? alt,
						};
						if ( saved.src ) {
							next.src = saved.src;
							// The old picture's other sizes no longer apply.
							next.srcset = null;
							next.sizes = null;
							next.width = saved.width ? String( saved.width ) : null;
							next.height = saved.height ? String( saved.height ) : null;
						}
						setImgAttrs( img, next );
					}
				} catch ( _e ) {
					// Leave the page as it was; the save itself succeeded.
				}
				setMessage( boot?.i18n.saved || '', 'success' );

				if (
					result.logId &&
					typeof result.before === 'string' &&
					result.undo !== false
				) {
					offerUndo( {
						payload: {
							postId: payload.postId,
							blockId: ctx.blockId,
							blockName: ctx.blockName,
							attr: ctx.def.attr,
							image: true,
							...( ctx.def.repeat && { index: ctx.index } ),
							...( ctx.def.repeat && ctx.def.key && { key: ctx.def.key } ),
							...( ctx.targetId > 0 && { targetId: ctx.targetId } ),
						},
						before: result.before,
						logId: result.logId,
						el: img || ctx.root,
						root: ctx.root,
						originalHTML: '',
						targetId: ctx.targetId,
						timer: 0,
						restore,
					} );
				}
			} )
			.catch( ( e: unknown ) => {
				// Refused (a conflict, a lock, a permission): stay open and say why.
				fail( e instanceof Error && e.message ? e.message : boot?.i18n.error || '' );
			} );
	};

	const onClick = ( e: Event ) => {
		const target = e.target as HTMLElement;
		if ( target.closest( '[data-image="pick"]' ) ) {
			choose();
			return;
		}
		// The backdrop and the Cancel button both mean "leave it as it was".
		if ( target === dialog || target.closest( '[data-image="cancel"]' ) ) {
			close();
		}
	};

	const onKey = ( e: KeyboardEvent ) => {
		if ( picking ) {
			return;
		}
		if ( e.key === 'Escape' ) {
			e.preventDefault();
			e.stopPropagation();
			close();
			return;
		}
		if ( e.key === 'Tab' ) {
			const stops = Array.from(
				dialog.querySelectorAll< HTMLElement >( 'input, button' )
			).filter(
				( el ) =>
					! ( el as HTMLButtonElement ).disabled &&
					! ( el as HTMLButtonElement ).hidden
			);
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
	altInput.focus();
	altInput.select();
}

interface IconChoice {
	source: string;
	name: string;
	id: number | null;
}

interface CatalogEntry {
	name: string;
	markup: string;
}

// The picker's icons, fetched once on the first click and kept for the page.
let iconCatalog: Promise< Record< string, CatalogEntry[] > > | null = null;

/**
 * The icon catalog from the server (the only icons a save accepts).
 */
function loadIconCatalog(): Promise< Record< string, CatalogEntry[] > > {
	if ( ! iconCatalog && boot?.iconsUrl ) {
		iconCatalog = fetch( boot.iconsUrl, {
			headers: { 'X-WP-Nonce': boot.nonce },
			credentials: 'same-origin',
		} ).then( ( resp ) => {
			if ( ! resp.ok ) {
				throw new Error( `HTTP ${ resp.status }` );
			}
			return resp.json() as Promise< Record< string, CatalogEntry[] > >;
		} );
		// A failed load may be tried again on the next click.
		iconCatalog.catch( () => {
			iconCatalog = null;
		} );
	}
	return iconCatalog || Promise.reject( new Error( '' ) );
}

/**
 * Make a block's icons editable.
 *
 * One listener on the block root, in the capture phase, so the icon inside a
 * link or a button opens the editor instead of following or pressing it. The
 * target is matched by selector on every click rather than bound per element,
 * because saving an icon replaces its element.
 *
 * @param root Block root.
 * @param name Block name.
 * @param def  Icon declaration from the registry.
 */
function bindIcon( root: HTMLElement, name: string, def: IconDef ): void {
	root.querySelectorAll< HTMLElement >( def.selector ).forEach( ( el ) => {
		if ( el.closest( '[data-flexa-edit-id]' ) === root ) {
			el.classList.add( 'flexa-fee-icon-hit' );
			if ( ! el.getAttribute( 'title' ) ) {
				el.setAttribute( 'title', boot?.i18n.iconEdit || '' );
			}
			makeOperable( el, boot?.i18n.iconEdit || '' );
		}
	} );

	root.addEventListener(
		'click',
		( e ) => {
			const target = e.target instanceof Element ? e.target : null;
			const el = target?.closest< HTMLElement >( def.selector );
			if ( ! el || ! root.contains( el ) || el.closest( '[data-flexa-edit-id]' ) !== root ) {
				return;
			}
			let index: number | undefined;
			if ( def.repeat ) {
				const item = el.closest< HTMLElement >( '[data-flexa-item]' );
				index = item
					? parseInt( item.getAttribute( 'data-flexa-item' ) || '', 10 )
					: NaN;
				if ( Number.isNaN( index ) ) {
					return;
				}
			}
			e.preventDefault();
			e.stopPropagation();
			void startIconEdit( root, name, def, el, index );
		},
		true
	);
}

/**
 * Open the icon editor, after settling any text that has unsaved changes.
 *
 * @param root  Block root.
 * @param name  Block name.
 * @param def   Icon declaration.
 * @param el    The clicked icon (or its holder).
 * @param index List item index, for an icon in a list.
 */
async function startIconEdit(
	root: HTMLElement,
	name: string,
	def: IconDef,
	el: HTMLElement,
	index?: number
): Promise< void > {
	if ( ! boot || undoing ) {
		return;
	}
	if ( active ) {
		if ( isDirty() && ! ( await settle( boot.i18n.unsaved || '' ) ) ) {
			return;
		}
		if ( active ) {
			endEdit( true );
		}
	}
	openIconDialog( root, name, def, el, index );
}

/**
 * Build the element for an icon as the server described it.
 *
 * @param after The stored state: catalog markup, or an upload's address.
 */
function iconNode( after: { source?: string; markup?: string; url?: string } ): Node | null {
	if ( after.source === 'upload' && after.url ) {
		const img = document.createElement( 'img' );
		img.className = 'flexa-icon';
		img.src = after.url;
		img.alt = '';
		img.width = 24;
		img.height = 24;
		return img;
	}
	if ( after.markup ) {
		const tpl = document.createElement( 'template' );
		tpl.innerHTML = after.markup.trim();
		return tpl.content.firstElementChild;
	}
	return null;
}

/**
 * The icon dialog, and what Apply does.
 *
 * @param root  Block root.
 * @param name  Block name.
 * @param def   Icon declaration.
 * @param el    The clicked icon (or its holder).
 * @param index List item index, for an icon in a list.
 */
function openIconDialog(
	root: HTMLElement,
	name: string,
	def: IconDef,
	el: HTMLElement,
	index?: number,
	reload = false
): void {
	const dialog = iconDialog;
	if ( ! dialog || ! boot ) {
		return;
	}
	const form = dialog.querySelector< HTMLFormElement >( 'form' );
	const grid = dialog.querySelector< HTMLElement >( '.flexa-fee-icondialog__grid' );
	const search = dialog.querySelector< HTMLInputElement >( '.flexa-fee-icondialog__search' );
	const current = dialog.querySelector< HTMLElement >( '.flexa-fee-icondialog__current' );
	const error = dialog.querySelector< HTMLElement >( '.flexa-fee-linkdialog__error' );
	const apply = dialog.querySelector< HTMLButtonElement >( '[data-icon="apply"]' );
	const upload = dialog.querySelector< HTMLButtonElement >( '[data-icon="upload"]' );
	if ( ! form || ! grid || ! search || ! current || ! error || ! apply || ! upload ) {
		return;
	}

	let tab = 'builtin';
	let choice: IconChoice | null = null;
	let picking = false;
	let catalog: Record< string, CatalogEntry[] > = {};

	const blockId = root.getAttribute( 'data-flexa-edit-id' ) || '';
	const targetId =
		parseInt( root.getAttribute( 'data-flexa-edit-target' ) || '0', 10 ) || 0;

	search.value = '';
	error.hidden = true;
	error.textContent = '';
	apply.disabled = false;
	upload.hidden = ! boot.canPick;
	// What the page shows now, as the "current" preview.
	current.innerHTML = '';
	const shown = def.wrap ? el.firstElementChild : el;
	if ( shown ) {
		current.appendChild( shown.cloneNode( true ) );
	}

	const fail = ( message: string ) => {
		error.textContent = message;
		error.hidden = false;
		apply.disabled = false;
	};

	const preview = ( node: Node | null ) => {
		current.innerHTML = '';
		if ( node ) {
			current.appendChild( node );
		}
	};

	const render = () => {
		const q = search.value.trim().toLowerCase();
		const list = ( catalog[ tab ] || [] ).filter(
			( icon ) =>
				! q ||
				icon.name.toLowerCase().replace( /^wp-/, '' ).includes( q.replace( /\s+/g, '' ) )
		);
		grid.innerHTML = '';
		if ( ! list.length ) {
			const empty = document.createElement( 'p' );
			empty.className = 'flexa-fee-icondialog__empty';
			empty.textContent = boot?.i18n.iconNone || '';
			grid.appendChild( empty );
			return;
		}
		list.forEach( ( icon ) => {
			const btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'flexa-fee-icondialog__item';
			btn.title = icon.name.replace( /^wp-/, '' );
			btn.setAttribute( 'role', 'option' );
			btn.setAttribute( 'data-icon-name', icon.name );
			btn.innerHTML = icon.markup;
			if ( choice && choice.source === tab && choice.name === icon.name ) {
				btn.classList.add( 'is-selected' );
				btn.setAttribute( 'aria-selected', 'true' );
			}
			grid.appendChild( btn );
		} );
	};

	const setTab = ( next: string ) => {
		tab = next;
		dialog.querySelectorAll< HTMLElement >( '[data-icon-tab]' ).forEach( ( t ) => {
			const on = t.getAttribute( 'data-icon-tab' ) === tab;
			t.classList.toggle( 'is-active', on );
			t.setAttribute( 'aria-selected', on ? 'true' : 'false' );
		} );
		render();
	};

	grid.innerHTML = '<span class="flexa-fee-icondialog__loading"></span>';
	loadIconCatalog()
		.then( ( data ) => {
			catalog = data;
			setTab( 'builtin' );
		} )
		.catch( () => {
			grid.innerHTML = '';
			fail( boot?.i18n.iconLoadError || '' );
		} );

	const close = () => {
		dialog.setAttribute( 'hidden', '' );
		document.body.classList.remove( 'flexa-fee-modal-open' );
		form.removeEventListener( 'submit', onSubmit );
		dialog.removeEventListener( 'click', onClick );
		search.removeEventListener( 'input', render );
		document.removeEventListener( 'keydown', onKey, true );
	};

	const chooseFile = () => {
		const media = (
			window as unknown as {
				wp?: { media?: ( o: Record< string, unknown > ) => MediaFrame };
			}
		 ).wp?.media;
		if ( ! media ) {
			fail( boot?.i18n.imageNoWp || '' );
			return;
		}
		const frame = media( {
			title: boot?.i18n.iconUpload || '',
			button: { text: boot?.i18n.imageUse || '' },
			library: { type: 'image' },
			multiple: false,
		} );
		picking = true;
		frame.on( 'select', () => {
			const att = frame.state().get( 'selection' ).first().toJSON();
			choice = { source: 'upload', name: '', id: att.id };
			preview( iconNode( { source: 'upload', url: att.url } ) );
			render();
			error.hidden = true;
		} );
		frame.on( 'close', () => {
			window.setTimeout( () => {
				picking = false;
			}, 0 );
		} );
		frame.open();
	};

	const onSubmit = ( e: Event ) => {
		e.preventDefault();
		if ( ! choice ) {
			close();
			return;
		}
		apply.disabled = true;
		error.hidden = true;

		const payload: Record< string, unknown > = {
			postId: boot?.postId,
			blockId,
			blockName: name,
			attr: def.attr,
			icon: true,
			value: JSON.stringify( choice ),
		};
		if ( def.repeat ) {
			payload.index = index;
			payload.key = def.key;
		}
		if ( targetId > 0 ) {
			payload.targetId = targetId;
		}
		const version = root.getAttribute( 'data-flexa-edit-version' ) || '';
		if ( version ) {
			payload.version = version;
		}

		// How to put the page back on Undo: the holder's contents, or the element
		// itself (which a save replaces, so the current one is tracked).
		const previousInner = el.innerHTML;
		const previousEl = el;
		let shownEl: Element = el;
		const restore = () => {
			if ( def.wrap ) {
				el.innerHTML = previousInner;
			} else if ( shownEl !== previousEl ) {
				shownEl.replaceWith( previousEl );
				shownEl = previousEl;
			}
			previousEl.hidden = false;
		};

		postEdit( payload, targetId )
			.then( ( result ) => {
				close();
				if ( reload ) {
					// A new icon: the block decides where it goes; reload to show it.
					setMessage( boot?.i18n.reloading || boot?.i18n.saved || '', 'success' );
					toggleToolbar( true );
					window.location.reload();
					return;
				}
				try {
					const after = JSON.parse( result.after || '{}' ) as {
						source?: string;
						markup?: string;
						url?: string;
					};
					const node = iconNode( after );
					if ( def.wrap ) {
						el.innerHTML = '';
						if ( node ) {
							el.appendChild( node );
						}
					} else if ( node instanceof Element ) {
						node.classList.add( 'flexa-fee-icon-hit' );
						makeOperable( node as HTMLElement, boot?.i18n.iconEdit || '' );
						el.replaceWith( node );
						shownEl = node;
					} else {
						// No icon any more: keep the element (Undo needs it) but hide it.
						el.hidden = true;
					}
				} catch ( _e ) {
					// The save succeeded; the page catches up on reload.
				}
				setMessage( boot?.i18n.saved || '', 'success' );

				if ( result.logId && typeof result.before === 'string' && result.undo !== false ) {
					offerUndo( {
						payload: {
							postId: payload.postId,
							blockId,
							blockName: name,
							attr: def.attr,
							icon: true,
							...( def.repeat && { index, key: def.key } ),
							...( targetId > 0 && { targetId } ),
						},
						before: result.before,
						logId: result.logId,
						el,
						root,
						originalHTML: '',
						targetId,
						timer: 0,
						restore,
					} );
				}
			} )
			.catch( ( err: unknown ) => {
				fail( err instanceof Error && err.message ? err.message : boot?.i18n.error || '' );
			} );
	};

	const onClick = ( e: Event ) => {
		const target = e.target as HTMLElement;
		const item = target.closest< HTMLElement >( '.flexa-fee-icondialog__item' );
		if ( item ) {
			choice = { source: tab, name: item.getAttribute( 'data-icon-name' ) || '', id: null };
			preview( item.firstElementChild ? item.firstElementChild.cloneNode( true ) : null );
			render();
			return;
		}
		const tabBtn = target.closest< HTMLElement >( '[data-icon-tab]' );
		if ( tabBtn ) {
			setTab( tabBtn.getAttribute( 'data-icon-tab' ) || 'builtin' );
			return;
		}
		if ( target.closest( '[data-icon="upload"]' ) ) {
			chooseFile();
			return;
		}
		if ( target.closest( '[data-icon="none"]' ) ) {
			choice = { source: 'none', name: '', id: null };
			preview( null );
			render();
			return;
		}
		if ( target === dialog || target.closest( '[data-icon="cancel"]' ) ) {
			close();
		}
	};

	const onKey = ( e: KeyboardEvent ) => {
		if ( picking ) {
			return;
		}
		if ( e.key === 'Escape' ) {
			e.preventDefault();
			e.stopPropagation();
			close();
			return;
		}
		if ( e.key === 'Tab' ) {
			trapTab( e, dialog );
		}
	};

	form.addEventListener( 'submit', onSubmit );
	dialog.addEventListener( 'click', onClick );
	search.addEventListener( 'input', render );
	document.addEventListener( 'keydown', onKey, true );
	dialog.removeAttribute( 'hidden' );
	document.body.classList.add( 'flexa-fee-modal-open' );
	search.focus();
}

/**
 * Show the link to the review copy in the toolbar.
 *
 * @param href The copy's edit link.
 */
function showReviewLink( href: string ): void {
	if ( boot?.review ) {
		boot.review.editLink = href;
	}
	const link = toolbar?.querySelector< HTMLAnchorElement >( '.flexa-fee-toolbar__review' );
	if ( link && href ) {
		link.href = href;
		link.hidden = false;
	}
}

/**
 * Mark the blocks changed in the review copy, and say how many there are.
 *
 * The page shows the copy to the person editing it, so without this nothing
 * would tell them which parts are not live yet. A small bar in the corner
 * stays up while there are changes waiting, with the way to the copy.
 */
function paintPending(): void {
	const review = boot?.review;
	const blocks = review?.blocks || [];
	if ( ! review?.on || ! blocks.length ) {
		return;
	}
	const badge = boot?.i18n.reviewBadge || '';
	document
		.querySelectorAll< HTMLElement >( '[data-flexa-edit-id]' )
		.forEach( ( root ) => {
			if ( blocks.includes( root.getAttribute( 'data-flexa-edit-id' ) || '' ) ) {
				root.classList.add( 'flexa-fee-pending' );
				root.setAttribute( 'data-flexa-pending-label', badge );
				// The label is placed against the block; give it a frame only where
				// the block has none, so a block's own positioning is never changed.
				if ( getComputedStyle( root ).position === 'static' ) {
					root.classList.add( 'flexa-fee-pending-host' );
				}
			}
		} );

	let bar = document.querySelector< HTMLElement >( '.flexa-fee-pendingbar' );
	if ( ! bar ) {
		bar = document.createElement( 'div' );
		bar.className = 'flexa-fee-pendingbar';
		bar.setAttribute( 'role', 'status' );
		document.body.appendChild( bar );
	}
	bar.textContent = ( boot?.i18n.reviewPending || '%d' ).replace(
		'%d',
		String( blocks.length )
	);
	if ( review.editLink ) {
		const link = document.createElement( 'a' );
		link.href = review.editLink;
		link.target = '_blank';
		link.rel = 'noopener';
		link.textContent = boot?.i18n.reviewOpen || '';
		bar.appendChild( document.createTextNode( ' · ' ) );
		bar.appendChild( link );
	}
}

// What pasting may keep, per allowlist: the tags the server keeps for that
// field, without the attributes (pasted styles and classes are always junk).
const PASTE_TAGS: Record< string, string[] > = {
	plain: [],
	minimal: [ 'strong', 'em', 'b', 'i', 'br' ],
	'minimal-link': [ 'strong', 'em', 'b', 'i', 'br', 'a' ],
	inline: [ 'strong', 'em', 'b', 'i', 'br', 'sub', 'sup' ],
	rich: [ 'strong', 'em', 'b', 'i', 'br', 'sub', 'sup', 'a' ],
	post: [ 'strong', 'em', 'b', 'i', 'br', 'sub', 'sup', 'a', 'p', 'ul', 'ol', 'li', 'blockquote' ],
};

const BLOCK_TAGS = /^(P|DIV|LI|UL|OL|H[1-6]|BLOCKQUOTE|TR|TABLE|SECTION|ARTICLE|HEADER|FOOTER|PRE)$/;

/**
 * Turn pasted HTML into what this field keeps.
 *
 * Word and Google Docs paste fonts, colours, sizes, classes and wrappers; the
 * server would strip them on save, so what the person saw would not be what
 * was saved. Here the same cut is made at paste time: allowed tags stay (with
 * no attributes, except a link's checked address), bold and italic written as
 * styles become <strong> / <em>, block breaks become line breaks where the
 * field has them and spaces where it does not, and everything else keeps
 * only its text.
 *
 * @param html    Pasted HTML.
 * @param allowed Field allowlist name.
 */
function cleanPaste( html: string, allowed: string ): DocumentFragment {
	const keep = PASTE_TAGS[ allowed ] ?? PASTE_TAGS.inline;
	const canBreak = keep.includes( 'br' );
	const doc = new DOMParser().parseFromString( html, 'text/html' );
	const out = document.createDocumentFragment();

	const breakOrSpace = ( target: Node ) => {
		const last = target.lastChild;
		if ( ! last ) {
			return;
		}
		if ( canBreak ) {
			if ( last.nodeName !== 'BR' ) {
				target.appendChild( document.createElement( 'br' ) );
			}
		} else if ( ! /\s$/.test( last.textContent || '' ) ) {
			target.appendChild( document.createTextNode( ' ' ) );
		}
	};

	const walk = ( node: Node, target: Node ) => {
		node.childNodes.forEach( ( child ) => {
			if ( child.nodeType === Node.TEXT_NODE ) {
				const text = ( child.textContent || '' ).replace( /\s+/g, ' ' );
				if ( text ) {
					target.appendChild( document.createTextNode( text ) );
				}
				return;
			}
			if ( child.nodeType !== Node.ELEMENT_NODE ) {
				return; // Comments (Word's conditional markup) and the rest.
			}
			const el = child as HTMLElement;
			const tag = el.tagName.toLowerCase();
			if ( /^(script|style|meta|link|title|img|svg|video|audio|iframe|object|picture)$/.test( tag ) ) {
				return;
			}
			if ( tag === 'br' ) {
				if ( canBreak ) {
					target.appendChild( document.createElement( 'br' ) );
				} else {
					target.appendChild( document.createTextNode( ' ' ) );
				}
				return;
			}

			// Bold / italic written as styles (Word, Docs) count as the real thing;
			// Docs' outer <b style="font-weight:normal"> does not.
			const weight = el.style.fontWeight;
			const isBold =
				( tag === 'b' || tag === 'strong' || /^(bold|[6-9]00)$/.test( weight ) ) &&
				! /^(normal|[1-4]00)$/.test( weight );
			const isItalic = tag === 'i' || tag === 'em' || el.style.fontStyle === 'italic';

			let wrapTag = '';
			if ( tag === 'a' && keep.includes( 'a' ) ) {
				const href = el.getAttribute( 'href' ) || '';
				wrapTag = href && ! badScheme( href ) && ! /^\s*(data|vbscript):/i.test( href ) ? 'a' : '';
			} else if ( keep.includes( tag ) && ! [ 'b', 'strong', 'i', 'em' ].includes( tag ) ) {
				wrapTag = tag;
			}

			let inner: Node = target;
			if ( wrapTag ) {
				const copy = document.createElement( wrapTag );
				if ( wrapTag === 'a' ) {
					copy.setAttribute( 'href', el.getAttribute( 'href' ) || '' );
				}
				target.appendChild( copy );
				inner = copy;
			}
			if ( isBold && keep.includes( 'strong' ) ) {
				const strong = document.createElement( 'strong' );
				inner.appendChild( strong );
				inner = strong;
			}
			if ( isItalic && keep.includes( 'em' ) ) {
				const em = document.createElement( 'em' );
				inner.appendChild( em );
				inner = em;
			}

			walk( el, inner );

			// A block that is not kept still ends a line.
			if ( BLOCK_TAGS.test( el.tagName ) && ! wrapTag ) {
				breakOrSpace( target );
			}
		} );
	};

	walk( doc.body, out );

	// No leading/trailing breaks or spaces from the pasted document's wrappers.
	while ( out.lastChild && ( out.lastChild.nodeName === 'BR' || ! ( out.lastChild.textContent || '' ).trim() ) ) {
		out.removeChild( out.lastChild );
	}
	while ( out.firstChild && ( out.firstChild.nodeName === 'BR' || ! ( out.firstChild.textContent || '' ).trim() ) ) {
		out.removeChild( out.firstChild );
	}
	return out;
}

/**
 * Plain pasted text as this field keeps it: line breaks where it has them.
 *
 * @param text    Pasted text.
 * @param allowed Field allowlist name.
 */
function plainPaste( text: string, allowed: string ): DocumentFragment {
	const out = document.createDocumentFragment();
	const canBreak = ( PASTE_TAGS[ allowed ] ?? PASTE_TAGS.inline ).includes( 'br' );
	const lines = text.replace( /\r\n?/g, '\n' ).replace( /[ \t]+/g, ' ' ).trim().split( '\n' );
	lines.forEach( ( line, i ) => {
		if ( i > 0 ) {
			out.appendChild( canBreak ? document.createElement( 'br' ) : document.createTextNode( ' ' ) );
		}
		out.appendChild( document.createTextNode( line.trim() ) );
	} );
	return out;
}

/**
 * Paste into the text being edited, cut down to what it keeps.
 *
 * @param e The paste.
 */
function onPaste( e: ClipboardEvent ): void {
	if ( ! active || ! e.clipboardData || ! ( e.target instanceof Node ) || ! active.el.contains( e.target ) ) {
		return;
	}
	const allowed = active.allowed || 'inline';
	const html = e.clipboardData.getData( 'text/html' );
	const text = e.clipboardData.getData( 'text/plain' );
	if ( ! html && ! text ) {
		return; // A file or an image: nothing a text field can take.
	}
	e.preventDefault();

	const frag = html && allowed !== 'plain' ? cleanPaste( html, allowed ) : plainPaste( text || '', allowed );
	const sel = window.getSelection();
	if ( ! sel || ! sel.rangeCount ) {
		return;
	}
	let range = sel.getRangeAt( 0 );
	if ( ! active.el.contains( range.commonAncestorContainer ) ) {
		return;
	}
	// Never inside the "*" of a required field (see the beforeinput handler).
	const marker = ( range.startContainer instanceof Element
		? range.startContainer
		: range.startContainer.parentElement
	)?.closest( '.flexa-field__required' );
	if ( marker && active.el.contains( marker ) ) {
		range = document.createRange();
		range.setStartAfter( marker );
		range.collapse( true );
	}

	range.deleteContents();
	const last = frag.lastChild;
	range.insertNode( frag );
	if ( last ) {
		range.setStartAfter( last );
		range.collapse( true );
		sel.removeAllRanges();
		sel.addRange( range );
	}
	updateGuard();
}

/**
 * Make something that opens an editor reachable and usable from the keyboard.
 *
 * Tab reaches it, a screen reader names it ("Edit text", "Edit image"), and
 * Enter or Space presses it, which runs the same click handler a mouse does.
 * Elements already focusable (a link, a button) keep their own behaviour.
 *
 * @param el    The text, image or icon.
 * @param label What pressing it does.
 */
function makeOperable( el: HTMLElement, label: string ): void {
	if ( el.dataset.feeOperable ) {
		return;
	}
	el.dataset.feeOperable = '1';
	const native = el.matches( 'a[href], button, input, select, textarea' );
	if ( ! native ) {
		if ( ! el.hasAttribute( 'tabindex' ) ) {
			el.tabIndex = 0;
		}
		el.setAttribute( 'role', 'button' );
	}
	if ( label && ! el.hasAttribute( 'aria-label' ) ) {
		// The visible text stays readable to a screen reader after the action.
		const text = ( el.textContent || '' ).trim().slice( 0, 60 );
		el.setAttribute( 'aria-label', text ? `${ label }: ${ text }` : label );
	}
	el.addEventListener( 'keydown', ( e: KeyboardEvent ) => {
		// While the text is being edited, Enter and Space are typing.
		if ( el.isContentEditable || ( e.key !== 'Enter' && e.key !== ' ' ) ) {
			return;
		}
		if ( e.target !== el ) {
			return;
		}
		e.preventDefault();
		el.click();
	} );
}

/**
 * Keep Tab and Shift+Tab inside a dialog.
 *
 * @param e      The key press.
 * @param dialog The dialog.
 */
function trapTab( e: KeyboardEvent, dialog: HTMLElement ): void {
	const stops = Array.from(
		dialog.querySelectorAll< HTMLElement >( 'input, button, [tabindex="0"]' )
	).filter(
		( el ) =>
			! ( el as HTMLButtonElement ).disabled &&
			! el.hidden &&
			el.offsetParent !== null
	);
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
		'.flexa-fee-toolbar__inner > .flexa-fee-btn--link:not(.flexa-fee-btn--addicon)'
	);
	linkBtn?.addEventListener( 'click', () => {
		void startLinkEdit();
	} );
	addIconBtn = toolbar.querySelector(
		'.flexa-fee-toolbar__inner > .flexa-fee-btn--addicon'
	);
	addIconBtn?.addEventListener( 'click', () => {
		void startAddIcon();
	} );
	linkDialog = document.querySelector(
		'.flexa-fee-linkdialog:not(.flexa-fee-imagedialog):not(.flexa-fee-icondialog)'
	);
	imageDialog = document.querySelector( '.flexa-fee-imagedialog' );
	iconDialog = document.querySelector( '.flexa-fee-icondialog' );
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
		makeOperable( el, boot?.i18n.textEdit || '' );
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
	if ( boot.review?.editLink ) {
		showReviewLink( boot.review.editLink );
	}
	paintPending();

	const roots = document.querySelectorAll< HTMLElement >(
		'[data-flexa-edit-id]'
	);
	roots.forEach( ( root ) => {
		const name = root.getAttribute( 'data-flexa-edit-name' ) || '';
		const fields = boot.fields[ name ];
		if ( fields ) {
			fields.forEach( ( field ) => bindField( root, field ) );
		}
		const images = boot.images?.[ name ];
		if ( images ) {
			images.forEach( ( def ) => bindImage( root, name, def ) );
		}
		const icons = boot.icons?.[ name ];
		if ( icons ) {
			icons.forEach( ( def ) => bindIcon( root, name, def ) );
		}
	} );

	// Pasting keeps only what the field keeps (see onPaste()).
	document.addEventListener( 'paste', onPaste, true );

	// Image buttons: opened from a click, and kept from starting what a press on
	// their host does (dragging a before/after slider, swiping a slider).
	document.addEventListener( 'click', onImageButton, true );
	[ 'pointerdown', 'mousedown', 'touchstart' ].forEach( ( type ) =>
		document.addEventListener(
			type,
			( e ) => {
				if (
					e.target instanceof Element &&
					e.target.closest( '.flexa-fee-imgbtn' )
				) {
					e.stopPropagation();
				}
			},
			true
		)
	);

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
			// The toolbar, editable text, and images and icons that open an editor:
			// those ask about unsaved changes themselves, then open their editor.
			// Treating a click on an image inside a link as "leaving the page"
			// would follow the link after "Discard" instead of editing the image.
			if (
				toolbar?.contains( target ) ||
				target.closest(
					`.${ EDITABLE_CLASS }, .flexa-fee-image-hit, .flexa-fee-icon-hit, .flexa-fee-imgbtn`
				)
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
