/**
 * Notice block front-end view script.
 *
 * Two behaviours:
 *  1. Dismiss — clicking `.flexa-notice__dismiss` hides its notice. When the
 *     wrapper carries `data-remember="1"` the dismissal is persisted to
 *     localStorage under `flexa-notice-<id>`, and such notices are hidden on load
 *     if already dismissed.
 *  2. Lazy background — a notice whose background image opts into lazy loading
 *     renders with `data-flexa-lazy-bg` and without the `background-image` url
 *     (gated behind `.flexa-bg-loaded` in the generated CSS). It is revealed when
 *     it nears the viewport.
 */

export {}; // Treat as a module so top-level names don't collide with other view scripts.

const STORAGE_PREFIX = 'flexa-notice-';
const LOADED_CLASS = 'flexa-bg-loaded';
const LAZY_SELECTOR = '[data-flexa-lazy-bg]';

/** Whether localStorage is usable (private modes / disabled storage throw). */
function storageAvailable(): boolean {
	try {
		const k = '__flexa_notice_test__';
		window.localStorage.setItem( k, '1' );
		window.localStorage.removeItem( k );
		return true;
	} catch ( e ) {
		return false;
	}
}

/** Hide a notice wrapper. */
function hide( el: HTMLElement ): void {
	el.style.display = 'none';
}

/** Wire dismiss buttons + restore remembered dismissals. */
function initDismiss(): void {
	const hasStorage = storageAvailable();
	const notices = document.querySelectorAll< HTMLElement >( '.flexa-notice[data-flexa-notice]' );

	notices.forEach( ( notice ) => {
		const id = notice.getAttribute( 'data-flexa-notice' ) || '';
		const remember = notice.getAttribute( 'data-remember' ) === '1';

		// Already dismissed on a previous visit → hide immediately.
		if ( remember && hasStorage && id && window.localStorage.getItem( STORAGE_PREFIX + id ) === '1' ) {
			hide( notice );
			return;
		}

		const button = notice.querySelector< HTMLElement >( '.flexa-notice__dismiss' );
		if ( ! button ) {
			return;
		}
		button.addEventListener( 'click', () => {
			hide( notice );
			if ( remember && hasStorage && id ) {
				try {
					window.localStorage.setItem( STORAGE_PREFIX + id, '1' );
				} catch ( e ) {
					// Ignore storage write failures — the notice still hides.
				}
			}
		} );
	} );
}

/** Mark one element as ready so its background image rule applies. */
function reveal( el: Element ): void {
	el.classList.add( LOADED_CLASS );
	el.removeAttribute( 'data-flexa-lazy-bg' );
}

/** Wire up lazy loading for all lazy notice backgrounds on the page. */
function initLazyBg(): void {
	const targets = document.querySelectorAll( LAZY_SELECTOR );
	if ( ! targets.length ) {
		return;
	}
	if ( typeof window.IntersectionObserver === 'undefined' ) {
		targets.forEach( reveal );
		return;
	}
	const observer = new window.IntersectionObserver(
		( entries, obs ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					reveal( entry.target );
					obs.unobserve( entry.target );
				}
			} );
		},
		{ rootMargin: '200px 0px' }
	);
	targets.forEach( ( el ) => observer.observe( el ) );
}

function init(): void {
	initDismiss();
	initLazyBg();
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
