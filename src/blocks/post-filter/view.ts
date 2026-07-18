/**
 * Post Filter front-end view script.
 *
 * The form already works without this file: it is a real GET form whose fields are
 * named exactly what the grid's render.php reads. All this script does is intercept
 * the submit, build the URL the browser WOULD have navigated to, and hand it to the
 * grid to render in place (see @shared/post-grid-bus — the grid owns the fetching).
 *
 * Because the URL is the whole state, there is nothing to keep in sync: the form
 * builds a URL, the grid renders it, the address bar shows it. A link a visitor
 * copies out of the address bar reproduces exactly what they were looking at.
 */

import { emitFilter, pageKey, searchKey } from '@shared/post-grid-bus';

const FORM_SELECTOR = '[data-flexa-post-filter]';
const RESET_SELECTOR = '.flexa-filter-reset__control';
const RESET_FIELD_SELECTOR = '.flexa-filter-field--reset';
const EMPTY_CLASS = 'flexa-filter-field--empty';
const FILTERED_CLASS = 'flexa-post-filter--filtered';
const MENU_SELECTOR = '.flexa-filter-field__menu';
const MENU_SEARCH_SELECTOR = '.flexa-filter-field__menu-search';
const SEARCH_DEBOUNCE_MS = 300;

/**
 * Write the ticked terms onto a multi-choice dropdown's summary, the way a closed
 * <select> shows what is picked. Falls back to the "any term" label — which the
 * server put in `data-empty`, so the wording stays the author's.
 *
 * @param menu The `<details>` element.
 */
function syncMenuLabel( menu: HTMLElement ): void {
	const value = menu.querySelector< HTMLElement >( '.flexa-filter-field__menu-value' );
	if ( ! value ) {
		return;
	}
	const names = Array.from( menu.querySelectorAll< HTMLInputElement >( '.flexa-filter-field__option-input:checked' ) )
		.map( ( input ) => {
			const text = input.parentElement?.querySelector< HTMLElement >( '.flexa-filter-field__option-text' );
			// The post count rides inside the same span — it belongs on the option, not
			// in a summary that has one line to say what is picked.
			return ( text?.firstChild?.textContent || '' ).trim();
		} )
		.filter( Boolean );

	value.textContent = names.length ? names.join( ', ' ) : value.getAttribute( 'data-empty' ) || '';
}

/**
 * Collect the form's filter state into the URL the grid should render.
 *
 * Only this grid's own args are rewritten — anything else already on the URL (a
 * language switcher, a second grid's page) is left exactly as it was. The page arg
 * is dropped: a changed filter always starts at page 1.
 *
 * @param form The filter form.
 * @param gid  The target grid's blockId.
 * @return The URL to render.
 */
function buildUrl( form: HTMLFormElement, gid: string ): URL {
	const url = new window.URL( window.location.href );
	const searchName = searchKey( gid );
	const taxPrefix = `tx_${ gid }_`;

	// Clear this grid's args, then rewrite them from the live controls.
	Array.from( url.searchParams.keys() ).forEach( ( key ) => {
		if ( key === searchName || key === pageKey( gid ) || key.indexOf( taxPrefix ) === 0 ) {
			url.searchParams.delete( key );
		}
	} );

	const values: Record< string, string[] > = {};
	Array.from( form.elements ).forEach( ( element ) => {
		const control = element as HTMLInputElement | HTMLSelectElement;
		const rawName = control.name || '';
		if ( ! rawName ) {
			return;
		}
		// Multi-select controls post as `name[]`.
		const name = rawName.replace( /\[\]$/, '' );
		if ( name !== searchName && name.indexOf( taxPrefix ) !== 0 ) {
			return;
		}
		if ( ( control.type === 'checkbox' || control.type === 'radio' ) && ! ( control as HTMLInputElement ).checked ) {
			return;
		}

		const selected = control instanceof window.HTMLSelectElement && control.multiple
			? Array.from( control.selectedOptions ).map( ( option ) => option.value )
			: [ control.value ];

		selected.filter( Boolean ).forEach( ( value ) => {
			values[ name ] = ( values[ name ] || [] ).concat( value.trim() );
		} );
	} );

	Object.keys( values ).forEach( ( name ) => {
		if ( values[ name ].length ) {
			url.searchParams.set( name, values[ name ].join( ',' ) );
		}
	} );

	return url;
}

/**
 * A long term list gets a box that narrows itself.
 *
 * It filters what is already in the panel — no request, no server involved — and it
 * carries no `name`, so it is invisible to the form and to buildUrl. The server ships
 * it `hidden`; unhiding it here is the promise that something is now driving it.
 *
 * A term the visitor has already ticked never hides: a filter you cannot see is one
 * you cannot take off.
 *
 * @param menu The `<details>` element.
 */
function wireMenuSearch( menu: HTMLElement ): void {
	const box = menu.querySelector< HTMLInputElement >( MENU_SEARCH_SELECTOR );
	if ( ! box ) {
		return;
	}

	box.hidden = false;

	const options = Array.from( menu.querySelectorAll< HTMLElement >( '.flexa-filter-field__option' ) );

	box.addEventListener( 'input', ( event: Event ) => {
		// Typing here must not reach the form's own handlers: it is not a filter, and a
		// debounced re-render of the grid on every keystroke is exactly what it isn't.
		event.stopPropagation();

		const needle = box.value.trim().toLowerCase();

		options.forEach( ( option ) => {
			const input = option.querySelector< HTMLInputElement >( '.flexa-filter-field__option-input' );
			const name = ( option.textContent || '' ).toLowerCase();
			option.hidden = '' !== needle && ! name.includes( needle ) && ! input?.checked;
		} );
	} );
}

/**
 * Everything that depends on the answer to "is anything filtering this grid?".
 *
 * The question is answered from the URL the form WOULD apply — the same one buildUrl
 * hands the grid — so it has one definition here, in the grid, and in the PHP that
 * renders the first paint (Filter_Fields::has_active_filters).
 *
 * Two things hang off it:
 *   - Reset, which has nothing to clear until something is applied.
 *   - The term counts, which are counted against the whole archive and stop being
 *     true the moment the visitor narrows it.
 *
 * @param form The filter form.
 * @param gid  The target grid's blockId.
 */
function syncFilterState( form: HTMLFormElement, gid: string ): void {
	const url = buildUrl( form, gid );
	const search = searchKey( gid );
	const taxPrefix = `tx_${ gid }_`;
	const filtering = Array.from( url.searchParams.keys() ).some(
		( key ) => key === search || key.indexOf( taxPrefix ) === 0
	);

	form.classList.toggle( FILTERED_CLASS, filtering );

	const reset = form.querySelector< HTMLElement >( RESET_FIELD_SELECTOR );
	if ( reset ) {
		reset.classList.toggle( EMPTY_CLASS, ! filtering );
	}
}

/**
 * Wire one filter bar.
 *
 * @param form The `[data-flexa-post-filter]` form.
 */
function initForm( form: HTMLFormElement ): void {
	const gid = form.getAttribute( 'data-flexa-post-filter' ) || '';
	if ( ! gid ) {
		return;
	}

	// Only now is it safe for CSS to hide the submit button — up to this point the
	// button was the only way to apply a filter.
	form.classList.add( 'is-js' );

	const apply = (): void => emitFilter( { target: gid, url: buildUrl( form, gid ).toString() } );

	// Reset appears, and the counts go away, the moment a visitor types or ticks —
	// the same instant the filter is applied. `input` covers the search box, `change`
	// everything else, `reset` the way back to nothing.
	form.addEventListener( 'input', () => syncFilterState( form, gid ) );
	form.addEventListener( 'change', () => syncFilterState( form, gid ) );
	form.addEventListener( 'reset', () => window.setTimeout( () => syncFilterState( form, gid ), 0 ) );
	syncFilterState( form, gid );

	// --- Multi-choice dropdowns. The <details> already opens, closes and submits on
	// its own; these are the two things a native select does that it doesn't. ---
	const menus = Array.from( form.querySelectorAll< HTMLDetailsElement >( MENU_SELECTOR ) );

	if ( menus.length ) {
		menus.forEach( wireMenuSearch );
		form.addEventListener( 'change', () => menus.forEach( syncMenuLabel ) );
		// `reset` fires BEFORE the fields are cleared, so read them on the next tick.
		form.addEventListener( 'reset', () => window.setTimeout( () => menus.forEach( syncMenuLabel ), 0 ) );

		// A dropdown a visitor has clicked away from is closed. Without this the panel
		// stays parked over the posts it was meant to filter.
		document.addEventListener( 'click', ( event: MouseEvent ) => {
			const target = event.target as Node | null;
			menus.forEach( ( menu ) => {
				if ( menu.open && target && ! menu.contains( target ) ) {
					menu.open = false;
				}
			} );
		} );

		document.addEventListener( 'keydown', ( event: KeyboardEvent ) => {
			if ( 'Escape' !== event.key ) {
				return;
			}
			menus.forEach( ( menu ) => {
				if ( menu.open ) {
					menu.open = false;
					menu.querySelector< HTMLElement >( '.flexa-filter-field__menu-toggle' )?.focus();
				}
			} );
		} );
	}

	form.addEventListener( 'submit', ( event: Event ) => {
		event.preventDefault();
		apply();
	} );

	const isTyped = ( target: EventTarget | null ): boolean => {
		const type = ( target as HTMLInputElement | null )?.type;
		return type === 'search' || type === 'text';
	};

	// Dropdowns and checkboxes apply at once; typing waits for a pause, so a search
	// isn't fired once per keystroke. (Applying never moves focus, so the visitor can
	// keep typing straight through the update.)
	form.addEventListener( 'change', ( event: Event ) => {
		if ( ! isTyped( event.target ) ) {
			apply();
		}
	} );

	let timer = 0;
	form.addEventListener( 'input', ( event: Event ) => {
		if ( ! isTyped( event.target ) ) {
			return;
		}
		window.clearTimeout( timer );
		timer = window.setTimeout( apply, SEARCH_DEBOUNCE_MS );
	} );

	// Reset is a real link to the unfiltered URL — intercept it so it swaps in place
	// like everything else, but leave its href intact so it still works when JS fails.
	form.addEventListener( 'click', ( event: MouseEvent ) => {
		if ( event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
			return;
		}
		const target = event.target as HTMLElement | null;
		const reset = target ? target.closest< HTMLAnchorElement >( RESET_SELECTOR ) : null;
		if ( ! reset || ! reset.href ) {
			return;
		}
		event.preventDefault();
		form.reset();
		emitFilter( { target: gid, url: reset.href } );
	} );
}

/**
 * Wire up every filter bar on the page.
 */
function init(): void {
	document.querySelectorAll< HTMLFormElement >( FORM_SELECTOR ).forEach( initForm );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
