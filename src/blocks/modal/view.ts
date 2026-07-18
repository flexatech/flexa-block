/**
 * Modal front-end view script.
 *
 * Each block renders its trigger (button / text / image / icon) plus a hidden
 * modal root (overlay + box with a close button). Clicking the trigger opens the
 * modal: it un-hides the root, adds an `is-open` class, locks body scroll and
 * moves focus into the box. It closes on the × button, on an overlay click (when
 * `data-close-overlay="1"`) and on Escape (when `data-close-esc="1"`), restoring
 * focus to the trigger. When `data-show-once="1"` the fact that the modal has
 * been opened is remembered per visitor in localStorage (keyed by the block id),
 * so it can be shown only once. Vanilla, dependency-free.
 */

export {}; // Module scope so top-level names don't collide with other view scripts.

const MODAL_ROOT_SELECTOR = '.flexa-modal';
const OPEN_CLASS = 'is-open';
const BODY_LOCK_CLASS = 'flexa-modal-scroll-lock';
const STORAGE_PREFIX = 'flexaModalSeen:';

/** The modal currently open on the page (only one at a time). */
let mdActiveRoot: HTMLElement | null = null;
let mdActiveDialog: HTMLElement | null = null;
let mdLastFocus: HTMLElement | null = null;
let mdCloseOnEsc = true;

/** localStorage guarded against privacy-mode / disabled storage. */
function mdStorageGet( key: string ): string | null {
	try {
		return window.localStorage.getItem( key );
	} catch ( e ) {
		return null;
	}
}

function mdStorageSet( key: string, value: string ): void {
	try {
		window.localStorage.setItem( key, value );
	} catch ( e ) {
		// Ignore — showOnce simply won't persist when storage is unavailable.
	}
}

/** Close whichever modal is open and restore scroll + focus. */
function mdClose(): void {
	if ( ! mdActiveRoot ) {
		return;
	}
	mdActiveRoot.setAttribute( 'hidden', '' );
	mdActiveRoot.setAttribute( 'aria-hidden', 'true' );
	mdActiveRoot.classList.remove( OPEN_CLASS );
	document.body.classList.remove( BODY_LOCK_CLASS );
	document.removeEventListener( 'keydown', mdOnKeydown );

	const restore = mdLastFocus;
	mdActiveRoot = null;
	mdActiveDialog = null;
	mdLastFocus = null;
	if ( restore && typeof restore.focus === 'function' ) {
		restore.focus();
	}
}

function mdOnKeydown( e: KeyboardEvent ): void {
	if ( mdCloseOnEsc && e.key === 'Escape' ) {
		mdClose();
	}
}

/** Wire one modal block. */
function mdInit( root: HTMLElement ): void {
	const trigger = root.querySelector< HTMLElement >( '.flexa-modal__trigger' );
	const dialog = root.querySelector< HTMLElement >( '.flexa-modal__root' );
	if ( ! trigger || ! dialog ) {
		return;
	}

	const box = dialog.querySelector< HTMLElement >( '.flexa-modal__box' );
	const overlay = dialog.querySelector< HTMLElement >( '.flexa-modal__overlay' );
	const closeBtn = dialog.querySelector< HTMLElement >( '.flexa-modal__close' );

	const id = root.getAttribute( 'data-flexa-modal-id' ) || '';
	const closeOnOverlay = root.getAttribute( 'data-close-overlay' ) !== '0';
	const closeOnEsc = root.getAttribute( 'data-close-esc' ) !== '0';
	const showOnce = root.getAttribute( 'data-show-once' ) === '1';
	const storageKey = STORAGE_PREFIX + id;

	const open = (): void => {
		mdClose(); // Close any other open modal first.
		mdActiveRoot = dialog;
		mdActiveDialog = box;
		mdLastFocus = trigger;
		mdCloseOnEsc = closeOnEsc;

		dialog.removeAttribute( 'hidden' );
		dialog.setAttribute( 'aria-hidden', 'false' );
		dialog.classList.add( OPEN_CLASS );
		document.body.classList.add( BODY_LOCK_CLASS );
		document.addEventListener( 'keydown', mdOnKeydown );

		if ( showOnce && id ) {
			mdStorageSet( storageKey, '1' );
		}
		if ( mdActiveDialog && typeof mdActiveDialog.focus === 'function' ) {
			mdActiveDialog.setAttribute( 'tabindex', '-1' );
			mdActiveDialog.focus();
		}
	};

	trigger.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		// showOnce hides the trigger too once seen — but a manual click still opens it.
		open();
	} );

	if ( closeBtn ) {
		closeBtn.addEventListener( 'click', mdClose );
	}
	if ( overlay && closeOnOverlay ) {
		overlay.addEventListener( 'click', mdClose );
	}

	// Reflect "already seen" so CSS / auto-open logic can react (trigger stays usable).
	if ( showOnce && id && mdStorageGet( storageKey ) === '1' ) {
		root.classList.add( 'flexa-modal--seen' );
	}
}

function mdBoot(): void {
	document.querySelectorAll< HTMLElement >( MODAL_ROOT_SELECTOR ).forEach( mdInit );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', mdBoot );
} else {
	mdBoot();
}
