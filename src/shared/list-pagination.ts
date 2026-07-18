/**
 * Shared client-side list pagination — used by the RSS and Facebook Feed front-end
 * view scripts (dependency-light so view scripts can import it without the React
 * bundle).
 *
 * The whole item pool is rendered server-side and cached, so paging is a pure
 * show/hide over the already-present cards — no extra requests. Two modes, chosen
 * by the wrapper's pagination attribute:
 *   - `numbered`  — split the items into pages of `data-per-page`, build numbered
 *     page buttons in `.flexa-pagination`, and show one page at a time.
 *   - `loadmore`  — show `data-loadmore-rows` rows first, then reveal one more row
 *     (the current column count) per click of `.flexa-pagination-loadmore__btn`.
 * With JavaScript off every item stays visible, so nothing ever breaks.
 */

import { pageWindow } from './pagination';

/** Per-block selectors identifying the wrapper, grid and items. */
export interface ListPaginationConfig {
	/** Attribute on the wrapper holding the mode (`numbered` | `loadmore`). */
	wrapAttr: string;
	/** Grid element selector (the item container). */
	grid: string;
	/** Item element selector. */
	item: string;
}

/** Wrappers already wired (avoids double-init when the script runs twice). */
const booted = new WeakSet< HTMLElement >();

/** Current rendered column count of a grid (1 for a stacked list layout). */
function columnCount( grid: HTMLElement ): number {
	const template = getComputedStyle( grid ).gridTemplateColumns;
	if ( ! template || template === 'none' ) {
		return 1;
	}
	return Math.max( 1, template.split( ' ' ).filter( Boolean ).length );
}

/** Numbered pages: split items into pages and build a WP-style windowed nav. */
function setupNumbered( wrap: HTMLElement, cfg: ListPaginationConfig ): void {
	const grid = wrap.querySelector< HTMLElement >( cfg.grid );
	const nav = wrap.querySelector< HTMLElement >( '.flexa-pagination' );
	if ( ! grid || ! nav ) {
		return;
	}
	const items = Array.from( grid.querySelectorAll< HTMLElement >( cfg.item ) );
	const perPage = Math.max( 1, parseInt( wrap.getAttribute( 'data-per-page' ) || '6', 10 ) || 6 );
	const pages = Math.ceil( items.length / perPage );
	if ( pages <= 1 ) {
		return;
	}
	const prevLabel = wrap.getAttribute( 'data-prev-label' ) || '‹';
	const nextLabel = wrap.getAttribute( 'data-next-label' ) || '›';

	let current = 1;

	/** Show only the current page's items. */
	const showItems = (): void => {
		const start = ( current - 1 ) * perPage;
		items.forEach( ( el, i ) => {
			el.style.display = i >= start && i < start + perPage ? '' : 'none';
		} );
	};

	/** A clickable page link (`.page-numbers`). */
	const link = ( label: string, page: number, extra: string ): HTMLButtonElement => {
		const btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'page-numbers' + ( extra ? ' ' + extra : '' );
		btn.textContent = label;
		if ( page === current && 'current' === extra ) {
			btn.setAttribute( 'aria-current', 'page' );
		}
		btn.addEventListener( 'click', () => go( page ) );
		return btn;
	};

	/** Rebuild the nav for the current page (dots window shifts as you page). */
	const render = (): void => {
		nav.textContent = '';
		if ( current > 1 ) {
			nav.appendChild( link( prevLabel, current - 1, 'prev' ) );
		}
		pageWindow( pages, current ).forEach( ( token ) => {
			if ( 'dots' === token ) {
				const dots = document.createElement( 'span' );
				dots.className = 'page-numbers dots';
				dots.textContent = '…';
				nav.appendChild( dots );
			} else {
				nav.appendChild( link( String( token ), token, token === current ? 'current' : '' ) );
			}
		} );
		if ( current < pages ) {
			nav.appendChild( link( nextLabel, current + 1, 'next' ) );
		}
	};

	const go = ( page: number ): void => {
		current = Math.min( Math.max( 1, page ), pages );
		showItems();
		render();
	};

	go( 1 );
}

/** Load-more: reveal an initial number of rows, then one more row per click. */
function setupLoadMore( wrap: HTMLElement, cfg: ListPaginationConfig ): void {
	const grid = wrap.querySelector< HTMLElement >( cfg.grid );
	const btnWrap = wrap.querySelector< HTMLElement >( '.flexa-pagination-loadmore' );
	const btn = wrap.querySelector< HTMLElement >( '.flexa-pagination-loadmore__btn' );
	if ( ! grid || ! btnWrap || ! btn ) {
		return;
	}
	const items = Array.from( grid.querySelectorAll< HTMLElement >( cfg.item ) );
	const rows = Math.max( 1, parseInt( wrap.getAttribute( 'data-loadmore-rows' ) || '2', 10 ) || 2 );
	// Optional explicit reveal count per click (`data-loadmore-step`). When absent
	// the increment is one grid row (the current column count) — the old behaviour.
	const stepAttr = parseInt( wrap.getAttribute( 'data-loadmore-step' ) || '', 10 );

	let visible = rows * columnCount( grid );
	const apply = (): void => {
		items.forEach( ( el, i ) => {
			el.style.display = i < visible ? '' : 'none';
		} );
		btnWrap.style.display = visible >= items.length ? 'none' : '';
	};

	if ( visible >= items.length ) {
		btnWrap.style.display = 'none';
		return;
	}
	apply();
	btn.addEventListener( 'click', () => {
		visible += stepAttr > 0 ? stepAttr : columnCount( grid );
		apply();
	} );
}

/**
 * Wire client-side pagination for every matching block on the page.
 *
 * @param cfg Per-block selectors (wrapper attribute, grid, item).
 */
export function initListPagination( cfg: ListPaginationConfig ): void {
	document.querySelectorAll< HTMLElement >( `[${ cfg.wrapAttr }]` ).forEach( ( wrap ) => {
		if ( booted.has( wrap ) ) {
			return;
		}
		booted.add( wrap );
		const type = wrap.getAttribute( cfg.wrapAttr );
		if ( 'numbered' === type ) {
			setupNumbered( wrap, cfg );
		} else if ( 'loadmore' === type ) {
			setupLoadMore( wrap, cfg );
		}
	} );
}
