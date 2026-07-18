/**
 * Post Grid front-end view script — AJAX paging + filtering.
 *
 * Everything this script does is progressive enhancement over markup that already
 * works: pagination links are real links carrying `pg_<blockId>`, and the filter
 * bar is a real GET form carrying `s_<blockId>` / `tx_<blockId>_<taxonomy>`. With
 * JavaScript off, both are plain page loads the server already understands.
 *
 * With JavaScript on, the URL stays the single source of truth — a click or a
 * filter change produces the URL the browser WOULD have navigated to, and this
 * script renders that URL's state by asking the REST route for just this grid's
 * markup instead of reloading the whole page. Every failure path falls back to
 * navigating to that same URL, so a broken fetch degrades to the no-JS behaviour
 * rather than to a dead grid.
 *
 * Instances are matched by their per-instance key, so several grids on one page
 * never touch each other's content.
 */

import {
	FILTER_EVENT,
	emitUpdated,
	pageKey,
	searchKey,
	type FilterDetail,
} from '@shared/post-grid-bus';

const INSTANCE_ATTR = 'data-flexa-post-grid';
const GRID_SELECTOR = '.flexa-post-grid__grid';
const PAGER_SELECTOR = '.flexa-pagination';
const LOADMORE_SELECTOR = '.flexa-pagination-loadmore';
const STATUS_SELECTOR = '.flexa-post-grid__status';
const INNER_SELECTOR = '.flexa-post-grid__inner';
const EMPTY_SELECTOR = '.flexa-post-grid__empty';
const LOADING_CLASS = 'is-loading';

interface GridResponse {
	grid: string;
	pagination: string;
	status: string;
	total: number;
	page: number;
	totalPages: number;
	url: string;
}

interface Instance {
	wrapper: HTMLElement;
	gid: string;
	postId: string;
	rest: string;
	loadMore: boolean;
	/** In-flight request, aborted when a newer one starts — otherwise fast typing
	 *  can let a stale response land last and render the wrong result. */
	pending: AbortController | null;
}

/**
 * Parse a front-end URL into the REST query for one grid: the visitor's search,
 * their per-taxonomy term slugs and the page. This is the only place the URL
 * contract is decoded on the client.
 *
 * @param url URL carrying the grid's filter state.
 * @param gid The grid's blockId.
 * @return Query string for the REST route.
 */
function restQuery( url: URL, gid: string, postId: string ): string {
	const query = new window.URLSearchParams();
	query.set( 'post_id', postId );
	query.set( 'block_id', gid );

	const page = parseInt( url.searchParams.get( pageKey( gid ) ) || '1', 10 );
	query.set( 'page', String( page > 0 ? page : 1 ) );

	const search = url.searchParams.get( searchKey( gid ) );
	if ( search ) {
		query.set( 's', search );
	}

	// `tx_<gid>_<taxonomy>` — one key per taxonomy, comma-separated slugs, or
	// repeated when the control is a multi-select.
	const prefix = `tx_${ gid }_`;
	const terms: Record< string, string[] > = {};
	url.searchParams.forEach( ( value, key ) => {
		if ( ! key.startsWith( prefix ) || ! value ) {
			return;
		}
		const taxonomy = key.slice( prefix.length );
		terms[ taxonomy ] = ( terms[ taxonomy ] || [] ).concat( value.split( ',' ).filter( Boolean ) );
	} );
	Object.keys( terms ).forEach( ( taxonomy ) => {
		query.set( `tax[${ taxonomy }]`, terms[ taxonomy ].join( ',' ) );
	} );

	return query.toString();
}

/**
 * Swap in a freshly rendered grid (or append to it, for load-more).
 *
 * @param instance The grid instance.
 * @param data     The REST payload.
 * @param append   Append the new cards instead of replacing them.
 */
function apply( instance: Instance, data: GridResponse, append: boolean ): void {
	const { wrapper } = instance;
	const inner = wrapper.querySelector( INNER_SELECTOR ) || wrapper;

	const parsed = new window.DOMParser().parseFromString( data.grid, 'text/html' );
	const fresh = parsed.querySelector( GRID_SELECTOR );
	const current = wrapper.querySelector( GRID_SELECTOR );

	if ( append && current && fresh ) {
		Array.from( fresh.children ).forEach( ( card ) => current.appendChild( card ) );
	} else if ( current ) {
		// `fresh` is null when the filter matched nothing — the server sent the
		// empty state instead of a grid.
		current.replaceWith( fresh || parsed.body.firstElementChild! );
	} else {
		// Coming back from the empty state: there is no grid to replace.
		const empty = wrapper.querySelector( EMPTY_SELECTOR );
		if ( empty ) {
			empty.replaceWith( fresh || parsed.body.firstElementChild! );
		} else {
			inner.insertAdjacentHTML( 'beforeend', data.grid );
		}
	}

	// Pagination may appear, disappear (last page) or change — reconcile all cases.
	const oldPager = wrapper.querySelector( `${ PAGER_SELECTOR }, ${ LOADMORE_SELECTOR }` );
	if ( oldPager ) {
		oldPager.remove();
	}
	if ( data.pagination ) {
		inner.insertAdjacentHTML( 'beforeend', data.pagination );
	}

	const status = wrapper.querySelector( STATUS_SELECTOR );
	if ( status && data.status ) {
		const template = status.getAttribute( 'data-template' ) || '%s';
		status.textContent = template.replace( '%s', String( data.total ) );
	}

	emitUpdated( {
		target: instance.gid,
		total: data.total,
		page: data.page,
		totalPages: data.totalPages,
	} );
}

/**
 * Put the visitor back where they were after a page change.
 *
 * Paging is the one update where the control the visitor used lives INSIDE the thing
 * that gets replaced: the link they clicked is gone by the time the new page lands, so
 * focus falls to <body> and a keyboard visitor is back at the top of the document.
 * Focus therefore moves to the fresh pagination — the same place, one page on.
 *
 * The scroll is for the mouse: paging from the bottom of page 1 otherwise drops you
 * into the middle of page 2. Only when the grid's top has actually scrolled out of
 * view — a grid already on screen must not jump under the pointer.
 *
 * Filtering does none of this on purpose: the control there is the filter bar, which
 * is not replaced, and yanking the page around while someone is typing is hostile.
 *
 * @param instance The grid instance.
 */
function afterPaging( instance: Instance ): void {
	const { wrapper } = instance;

	if ( wrapper.getBoundingClientRect().top < 0 ) {
		wrapper.scrollIntoView( { block: 'start', behavior: 'smooth' } );
	}

	const pager = wrapper.querySelector< HTMLElement >( PAGER_SELECTOR );
	if ( pager ) {
		pager.setAttribute( 'tabindex', '-1' );
		pager.focus( { preventScroll: true } );
	}
}

/**
 * Render one grid at the state encoded in `url`.
 *
 * @param instance The grid instance.
 * @param url      The front-end URL to render.
 * @param append   Append (load-more) instead of replacing.
 * @param push     Put the URL in the address bar.
 * @param paging   The update came from a pagination link (see afterPaging).
 */
function render( instance: Instance, url: URL, append: boolean, push: boolean, paging = false ): void {
	if ( ! instance.gid || ! instance.postId || ! instance.rest ) {
		window.location.href = url.toString();
		return;
	}

	if ( instance.pending ) {
		instance.pending.abort();
	}
	const controller = new window.AbortController();
	instance.pending = controller;

	const grid = instance.wrapper.querySelector( GRID_SELECTOR );
	if ( grid ) {
		grid.classList.add( LOADING_CLASS );
		grid.setAttribute( 'aria-busy', 'true' );
	}

	const endpoint = instance.rest
		+ ( instance.rest.indexOf( '?' ) === -1 ? '?' : '&' )
		+ restQuery( url, instance.gid, instance.postId );

	window
		.fetch( endpoint, { credentials: 'same-origin', signal: controller.signal } )
		.then( ( res ) => {
			if ( ! res.ok ) {
				throw new Error( 'HTTP ' + res.status );
			}
			return res.json();
		} )
		.then( ( data: GridResponse ) => {
			apply( instance, data, append );
			instance.pending = null;

			// Load-more appends: the address bar must keep pointing at page 1, or a
			// reload would show only the last chunk.
			if ( push && ! append ) {
				try {
					window.history.pushState( { flexaPostGrid: instance.gid }, '', data.url || url.toString() );
				} catch ( e ) {
					// pushState throws on file:// and cross-origin URLs — the content is
					// already swapped, so there is nothing to recover from.
				}
			}

			const fresh = instance.wrapper.querySelector( GRID_SELECTOR );
			if ( fresh ) {
				fresh.classList.remove( LOADING_CLASS );
				fresh.removeAttribute( 'aria-busy' );
			}

			if ( paging ) {
				afterPaging( instance );
			}
		} )
		.catch( ( error: Error ) => {
			// A newer request superseded this one — its response is the one that counts.
			if ( error && error.name === 'AbortError' ) {
				return;
			}
			instance.pending = null;
			if ( grid ) {
				grid.classList.remove( LOADING_CLASS );
				grid.removeAttribute( 'aria-busy' );
			}
			// Fall back to the plain navigation this URL always supported.
			window.location.href = url.toString();
		} );
}

/**
 * Wire one grid: numbered pagination, load-more, and filter events.
 *
 * @param wrapper The `[data-flexa-post-grid]` element.
 */
function initInstance( wrapper: HTMLElement ): Instance | null {
	const gid = wrapper.getAttribute( 'data-flexa-grid-id' ) || '';
	if ( ! gid ) {
		return null;
	}

	const instance: Instance = {
		wrapper,
		gid,
		postId: wrapper.getAttribute( 'data-flexa-post-id' ) || '',
		rest: wrapper.getAttribute( 'data-flexa-rest' ) || '',
		loadMore: wrapper.hasAttribute( 'data-flexa-loadmore' ),
		pending: null,
	};

	wrapper.addEventListener( 'click', ( event: MouseEvent ) => {
		// Respect modifier clicks / non-primary buttons (open-in-new-tab, etc.).
		if ( event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ) {
			return;
		}
		const target = event.target as HTMLElement | null;
		if ( ! target ) {
			return;
		}

		const link = target.closest< HTMLAnchorElement >( `${ PAGER_SELECTOR } a.page-numbers` );
		if ( link && link.href ) {
			event.preventDefault();
			render( instance, new window.URL( link.href ), false, true, true );
			return;
		}

		const button = target.closest< HTMLButtonElement >( '.flexa-pagination-loadmore__btn' );
		if ( button && ! button.disabled ) {
			const box = button.closest< HTMLElement >( LOADMORE_SELECTOR );
			const next = box ? box.getAttribute( 'data-next' ) : '';
			if ( ! next ) {
				return;
			}
			event.preventDefault();
			button.disabled = true;
			render( instance, new window.URL( next, window.location.href ), true, false );
		}
	} );

	return instance;
}

/**
 * Wire up every grid on the page, and route filter events to the right one.
 */
function init(): void {
	const instances: Instance[] = [];
	document.querySelectorAll< HTMLElement >( `[${ INSTANCE_ATTR }]` ).forEach( ( wrapper ) => {
		const instance = initInstance( wrapper );
		if ( instance ) {
			instances.push( instance );
		}
	} );

	if ( ! instances.length ) {
		return;
	}

	document.addEventListener( FILTER_EVENT, ( event: Event ) => {
		const detail = ( event as CustomEvent< FilterDetail > ).detail;
		if ( ! detail || ! detail.url ) {
			return;
		}
		// An empty target means "the first grid on the page" — the same rule the
		// server uses when a filter block has no explicit target.
		const instance = detail.target
			? instances.filter( ( item ) => item.gid === detail.target )[ 0 ]
			: instances[ 0 ];
		if ( instance ) {
			render( instance, new window.URL( detail.url, window.location.href ), false, true );
		}
	} );

	// Back / forward: content was swapped in place, so re-sync from the server.
	window.addEventListener( 'popstate', ( event: PopStateEvent ) => {
		if ( event.state && ( event.state as { flexaPostGrid?: string } ).flexaPostGrid ) {
			window.location.reload();
		}
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
