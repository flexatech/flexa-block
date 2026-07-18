/**
 * Product Details front-end view script.
 *
 * Each product-details block renders as `.flexa-product-detail` with a tab list
 * and one panel per tab (the first active by default so it works without JS).
 * Clicking a `.flexa-product-detail__tab` activates that tab and reveals its
 * matching `.flexa-product-detail__panel` (paired by `data-tab`), clearing the
 * siblings. A single underline indicator slides beneath the active tab (like the
 * Tabs block); until JS measures it, the per-tab `.is-active` underline is the
 * fallback. Initialisation is idempotent over every block on the page.
 *
 * The Reviews tab list is paginated by the shared `initListPagination` controller
 * (also used by RSS / Facebook Feed) — numbered pages or a load-more button over
 * the server-rendered `.commentlist` items.
 */

import { initListPagination } from '@shared/list-pagination';

/** Shared list-pagination config for the reviews list. */
const REVIEW_PAGINATION = {
	wrapAttr: 'data-flexa-pd-pagination',
	grid: '.commentlist',
	item: ':scope > li',
};

/** Slide the underline indicator beneath the active tab. */
function productDetailMoveIndicator( root: HTMLElement ): void {
	const nav = root.querySelector< HTMLElement >( '.flexa-product-detail__nav' );
	const active = root.querySelector< HTMLElement >( '.flexa-product-detail__tab.is-active' );
	if ( ! nav || ! active ) {
		return;
	}
	const navRect = nav.getBoundingClientRect();
	const tabRect = active.getBoundingClientRect();
	const x = tabRect.left - navRect.left + nav.scrollLeft;
	nav.style.setProperty( '--flexa-pd-ind-x', `${ x }px` );
	nav.style.setProperty( '--flexa-pd-ind-w', `${ tabRect.width }px` );
	nav.classList.add( 'is-indicator-ready' );
}

/** Activate the tab + panel with the given `data-tab` index, clearing siblings. */
function productDetailSetActive( root: HTMLElement, index: string ): void {
	root.querySelectorAll< HTMLElement >( '.flexa-product-detail__tab' ).forEach( ( tab ) => {
		const on = tab.getAttribute( 'data-tab' ) === index;
		tab.classList.toggle( 'is-active', on );
		tab.setAttribute( 'aria-selected', on ? 'true' : 'false' );
	} );
	root.querySelectorAll< HTMLElement >( '.flexa-product-detail__panel' ).forEach( ( panel ) => {
		panel.classList.toggle( 'is-active', panel.getAttribute( 'data-tab' ) === index );
	} );
	productDetailMoveIndicator( root );
}

/**
 * Turn the WooCommerce review-form rating <select> into clickable stars. Works
 * without WooCommerce's own front-end script — we hide the native select and
 * drive its value from a star row (hover previews, click sets). Idempotent, and
 * skips if WooCommerce already converted the select (a `p.stars` is present).
 */
function productDetailInitRatingStars( root: HTMLElement ): void {
	const selects = root.querySelectorAll< HTMLSelectElement >( 'select#rating, select[name="rating"]' );
	selects.forEach( ( select ) => {
		if ( select.dataset.flexaStars === '1' ) {
			return;
		}
		// WooCommerce already built its own star row, or the select is hidden.
		if ( select.parentElement?.querySelector( 'p.stars' ) ) {
			return;
		}
		select.dataset.flexaStars = '1';

		const widget = document.createElement( 'span' );
		widget.className = 'flexa-product-detail__stars';
		const stars: HTMLButtonElement[] = [];
		for ( let i = 1; i <= 5; i++ ) {
			const star = document.createElement( 'button' );
			star.type = 'button';
			star.className = 'flexa-product-detail__star';
			star.dataset.value = String( i );
			star.setAttribute( 'aria-label', String( i ) );
			star.textContent = '★';
			stars.push( star );
			widget.appendChild( star );
		}

		const paint = ( value: number ): void => {
			stars.forEach( ( s, idx ) => s.classList.toggle( 'is-on', idx < value ) );
		};

		widget.addEventListener( 'click', ( e ) => {
			const target = ( e.target as HTMLElement ).closest< HTMLElement >( '.flexa-product-detail__star' );
			if ( ! target ) {
				return;
			}
			const value = parseInt( target.dataset.value || '0', 10 );
			select.value = String( value );
			select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			paint( value );
		} );
		widget.addEventListener( 'mouseover', ( e ) => {
			const target = ( e.target as HTMLElement ).closest< HTMLElement >( '.flexa-product-detail__star' );
			if ( target ) {
				paint( parseInt( target.dataset.value || '0', 10 ) );
			}
		} );
		widget.addEventListener( 'mouseleave', () => paint( parseInt( select.value || '0', 10 ) ) );

		select.style.display = 'none';
		select.parentElement?.insertBefore( widget, select );
		paint( parseInt( select.value || '0', 10 ) );
	} );
}

/** Wire one product-details block: tab clicks switch the visible panel. */
function productDetailInitRoot( root: HTMLElement ): void {
	if ( root.dataset.flexaProductDetailReady === '1' ) {
		return;
	}
	root.dataset.flexaProductDetailReady = '1';

	root.querySelectorAll< HTMLElement >( '.flexa-product-detail__tab' ).forEach( ( tab ) => {
		tab.addEventListener( 'click', () => {
			const index = tab.getAttribute( 'data-tab' );
			if ( null !== index ) {
				productDetailSetActive( root, index );
			}
		} );
	} );

	// Place the indicator under the initially-active tab, and keep it aligned on
	// resize (tab widths can change with the viewport).
	productDetailMoveIndicator( root );
	window.addEventListener( 'resize', () => productDetailMoveIndicator( root ) );

	// Convert the review-form rating select into clickable stars.
	productDetailInitRatingStars( root );
}

/** Wire up every product-details block on the page. */
function productDetailInit(): void {
	document.querySelectorAll< HTMLElement >( '.flexa-product-detail' ).forEach( productDetailInitRoot );
	// Paginate the reviews list (numbered / load-more), shared controller.
	initListPagination( REVIEW_PAGINATION );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', productDetailInit );
} else {
	productDetailInit();
}

export {}; // Module scope so names don't collide with other view scripts.
