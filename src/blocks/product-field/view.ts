export {}; // Module scope so names don't collide with other view scripts.

/**
 * Product Field front-end view script.
 *
 * Wires every `.flexa-product-field__copy` button — the SKU field's optional
 * copy-to-clipboard control. Clicking it writes the SKU carried in `data-sku`
 * to the clipboard and confirms with the `data-copied` wording for a couple of
 * seconds (a small span next to the button, plus the
 * button's aria-label so screen readers hear it too). The async Clipboard API
 * is unavailable on plain HTTP and can be blocked by permissions policy, so a
 * hidden-textarea + `document.execCommand` path stands behind it and every
 * failure is swallowed — a copy button must never throw on the front end.
 */

const BOUND = 'data-flexa-sku-bound';
const FEEDBACK_MS = 2000;

/**
 * Copy text using the legacy selection trick — the fallback for contexts where
 * `navigator.clipboard` is missing or refuses (non-secure origins).
 *
 * @param text Text to copy.
 * @return Whether the copy succeeded.
 */
function legacyCopy( text: string ): boolean {
	try {
		const area = document.createElement( 'textarea' );
		area.value = text;
		area.setAttribute( 'readonly', '' );
		area.style.position = 'fixed';
		area.style.top = '-1000px';
		area.style.opacity = '0';
		document.body.appendChild( area );
		area.select();
		const ok = document.execCommand( 'copy' );
		document.body.removeChild( area );
		return ok;
	} catch ( e ) {
		return false;
	}
}

/**
 * Show the "copied" confirmation next to a button, then restore it.
 *
 * @param button The copy button.
 * @param label  Confirmation wording (`data-copied`).
 */
function confirmCopy( button: HTMLElement, label: string ): void {
	const original = button.getAttribute( 'aria-label' ) || '';
	button.setAttribute( 'aria-label', label );

	let note = button.nextElementSibling as HTMLElement | null;
	if ( ! note || ! note.classList.contains( 'flexa-product-field__copied' ) ) {
		note = document.createElement( 'span' );
		note.className = 'flexa-product-field__copied';
		button.parentNode?.insertBefore( note, button.nextSibling );
	}
	note.textContent = label;

	window.setTimeout( () => {
		if ( original ) {
			button.setAttribute( 'aria-label', original );
		}
		note?.remove();
	}, FEEDBACK_MS );
}

/**
 * Bind one copy button.
 *
 * @param button The `.flexa-product-field__copy` element.
 */
function initCopy( button: HTMLElement ): void {
	// Guard against a second pass (blocks can be re-initialised by other scripts).
	if ( button.hasAttribute( BOUND ) ) {
		return;
	}
	button.setAttribute( BOUND, '' );

	button.addEventListener( 'click', () => {
		const sku = button.getAttribute( 'data-sku' ) || '';
		const label = button.getAttribute( 'data-copied' ) || '';
		if ( ! sku ) {
			return;
		}

		const done = (): void => confirmCopy( button, label );

		const clipboard = navigator.clipboard;
		if ( clipboard && typeof clipboard.writeText === 'function' ) {
			try {
				clipboard.writeText( sku ).then( done, () => {
					if ( legacyCopy( sku ) ) {
						done();
					}
				} );
				return;
			} catch ( e ) {
				// Fall through to the legacy path below.
			}
		}

		if ( legacyCopy( sku ) ) {
			done();
		}
	} );
}

/**
 * Wire up every copy button on the page.
 */
function init(): void {
	document.querySelectorAll< HTMLElement >( '.flexa-product-field__copy' ).forEach( initCopy );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
