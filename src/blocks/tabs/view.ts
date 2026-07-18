/**
 * Tabs front-end view script.
 *
 * Each tabs block renders with `data-flexa-tabs`. On desktop the horizontal bar
 * of role="tab" buttons switches the visible role="tabpanel" (aria-selected +
 * roving tabindex + arrow-key navigation). On mobile the bar collapses to an
 * accordion (CSS), where each panel's header toggles its own panel open/closed —
 * one open at a time. A single indicator element slides horizontally to the
 * active tab (its position/size fed to CSS vars from the measured tab). It also
 * lazy-loads a background image when the block opts in (shared
 * `data-flexa-lazy-bg` marker).
 */

export {}; // Module scope so names don't collide with other view scripts.

const TABS_LOADED_CLASS = 'flexa-bg-loaded';

/** The nav (bar) tab buttons of one tabs block, in order. */
function tabsNavButtons( root: HTMLElement ): HTMLElement[] {
	return Array.from( root.querySelectorAll< HTMLElement >( '.flexa-tabs__nav .flexa-tabs__tab' ) );
}

/** The panel wrappers of one tabs block, in the same order as the nav buttons. */
function tabsPanelWraps( root: HTMLElement ): HTMLElement[] {
	return Array.from( root.querySelectorAll< HTMLElement >( '.flexa-tabs__panel-wrap' ) );
}

/**
 * Slide the moving indicator to the active nav tab by measuring its position and
 * width and feeding them to the CSS vars. Marks the nav `is-indicator-ready` so
 * the CSS reveals the indicator and drops the per-tab active fill. When the nav
 * is hidden (mobile accordion → the active tab measures 0 wide), the ready flag
 * is removed so the indicator stays hidden and the accordion look takes over.
 *
 * @param root The `[data-flexa-tabs]` wrapper.
 */
function tabsPositionIndicator( root: HTMLElement ): void {
	const nav = root.querySelector< HTMLElement >( '.flexa-tabs__nav' );
	if ( ! nav ) {
		return;
	}
	const indicator = nav.querySelector< HTMLElement >( '.flexa-tabs__indicator' );
	if ( ! indicator ) {
		return;
	}
	const active = nav.querySelector< HTMLElement >( '.flexa-tabs__tab.is-active' );
	if ( ! active || 0 === active.offsetWidth ) {
		nav.classList.remove( 'is-indicator-ready' );
		return;
	}
	indicator.style.setProperty( '--flexa-tabs-ind-x', active.offsetLeft + 'px' );
	indicator.style.setProperty( '--flexa-tabs-ind-w', active.offsetWidth + 'px' );
	nav.classList.add( 'is-indicator-ready' );
}

/**
 * Make tab `index` the active one: select its nav button, reveal its panel and
 * mark its accordion header expanded — clearing the others.
 *
 * @param root  The `[data-flexa-tabs]` wrapper.
 * @param index Tab index to activate.
 */
function tabsSetActive( root: HTMLElement, index: number ): void {
	tabsNavButtons( root ).forEach( ( btn, i ) => {
		const on = i === index;
		btn.classList.toggle( 'is-active', on );
		btn.setAttribute( 'aria-selected', on ? 'true' : 'false' );
		btn.setAttribute( 'tabindex', on ? '0' : '-1' );
	} );
	tabsPanelWraps( root ).forEach( ( wrap, i ) => {
		const on = i === index;
		wrap.classList.toggle( 'is-active', on );
		wrap.classList.toggle( 'is-open', on );
		const header = wrap.querySelector< HTMLElement >( '.flexa-tabs__accordion-header' );
		if ( header ) {
			header.classList.toggle( 'is-active', on );
			header.setAttribute( 'aria-expanded', on ? 'true' : 'false' );
		}
	} );
	tabsPositionIndicator( root );
}

/**
 * Toggle one panel's accordion state (mobile). Opening it activates that tab and
 * closes the others; clicking an already-open header collapses it, leaving the
 * desktop tab state intact.
 *
 * @param root  The wrapper.
 * @param index Panel index whose header was clicked.
 */
function tabsToggleAccordion( root: HTMLElement, index: number ): void {
	const wrap = tabsPanelWraps( root )[ index ];
	if ( ! wrap ) {
		return;
	}
	if ( wrap.classList.contains( 'is-open' ) ) {
		wrap.classList.remove( 'is-open' );
		const header = wrap.querySelector< HTMLElement >( '.flexa-tabs__accordion-header' );
		if ( header ) {
			header.setAttribute( 'aria-expanded', 'false' );
		}
		return;
	}
	tabsSetActive( root, index );
}

/**
 * Wire one tabs block: nav-bar clicks + keyboard, and accordion-header clicks.
 *
 * @param root The `[data-flexa-tabs]` wrapper.
 */
function initTabs( root: HTMLElement ): void {
	const buttons = tabsNavButtons( root );
	if ( ! buttons.length ) {
		return;
	}

	buttons.forEach( ( btn, index ) => {
		btn.addEventListener( 'click', () => tabsSetActive( root, index ) );
		btn.addEventListener( 'keydown', ( event: KeyboardEvent ) => {
			const last = buttons.length - 1;
			let next = -1;
			if ( 'ArrowRight' === event.key || 'ArrowDown' === event.key ) {
				next = index >= last ? 0 : index + 1;
			} else if ( 'ArrowLeft' === event.key || 'ArrowUp' === event.key ) {
				next = index <= 0 ? last : index - 1;
			} else if ( 'Home' === event.key ) {
				next = 0;
			} else if ( 'End' === event.key ) {
				next = last;
			}
			if ( -1 === next ) {
				return;
			}
			event.preventDefault();
			tabsSetActive( root, next );
			buttons[ next ].focus();
		} );
	} );

	tabsPanelWraps( root ).forEach( ( wrap, index ) => {
		const header = wrap.querySelector< HTMLElement >( '.flexa-tabs__accordion-header' );
		if ( header ) {
			header.addEventListener( 'click', () => tabsToggleAccordion( root, index ) );
		}
	} );

	// Position the indicator once layout has settled (after the first paint).
	window.requestAnimationFrame( () => tabsPositionIndicator( root ) );
}

/** rAF guard so a burst of resize events repositions the indicators just once. */
let tabsResizeScheduled = false;

/** Reposition every block's indicator on resize (throttled to one rAF). */
function tabsHandleResize(): void {
	if ( tabsResizeScheduled ) {
		return;
	}
	tabsResizeScheduled = true;
	window.requestAnimationFrame( () => {
		tabsResizeScheduled = false;
		document.querySelectorAll< HTMLElement >( '[data-flexa-tabs]' ).forEach( tabsPositionIndicator );
	} );
}

/**
 * Reveal a lazy background element so its image rule applies.
 *
 * @param el Target element.
 */
function tabsRevealLazyBg( el: Element ): void {
	el.classList.add( TABS_LOADED_CLASS );
	el.removeAttribute( 'data-flexa-lazy-bg' );
}

/**
 * Wire up every tabs block (and any lazy background) on the page.
 */
function tabsInit(): void {
	document.querySelectorAll< HTMLElement >( '[data-flexa-tabs]' ).forEach( initTabs );
	window.addEventListener( 'resize', tabsHandleResize );

	const lazy = document.querySelectorAll( '[data-flexa-lazy-bg]' );
	if ( ! lazy.length ) {
		return;
	}
	if ( typeof window.IntersectionObserver === 'undefined' ) {
		lazy.forEach( tabsRevealLazyBg );
		return;
	}
	const observer = new window.IntersectionObserver(
		( entries, obs ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					tabsRevealLazyBg( entry.target );
					obs.unobserve( entry.target );
				}
			} );
		},
		{ rootMargin: '200px 0px' }
	);
	lazy.forEach( ( el ) => observer.observe( el ) );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', tabsInit );
} else {
	tabsInit();
}
