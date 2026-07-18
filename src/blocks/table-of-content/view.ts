/**
 * Table of Contents front-end view script.
 *
 * Each TOC renders with `data-flexa-toc` and an empty `.flexa-toc__list`; this
 * script scans the surrounding content for the chosen heading levels
 * (`data-levels`), gives each one a stable id, and builds a nested list of jump
 * links inside the block. It also wires optional smooth scrolling
 * (`data-smooth` + `data-offset` for a sticky header), the collapse toggle
 * (`data-collapsible`), and reveals a lazy background image when the block opts
 * in (shared `data-flexa-lazy-bg` marker).
 */

export {}; // Module scope so names don't collide with other view scripts.

const LOADED_CLASS = 'flexa-bg-loaded';

/** One collected heading element with its outline level and target id. */
interface TocHeading {
	el: HTMLElement;
	level: number;
	id: string;
	text: string;
}

/**
 * Turn heading text into a URL-safe slug, made unique against ids already used.
 *
 * @param text Heading text.
 * @param used Set of ids already taken on the page.
 */
function slugify( text: string, used: Set< string > ): string {
	let base = text
		.toLowerCase()
		.replace( /[^a-z0-9À-ɏḀ-ỿ]+/g, '-' )
		.replace( /^-+|-+$/g, '' );
	if ( ! base ) {
		base = 'section';
	}
	let id = base;
	let n = 2;
	while ( used.has( id ) ) {
		id = `${ base }-${ n }`;
		n += 1;
	}
	used.add( id );
	return id;
}

/**
 * Find the content region a TOC should scan. Falls back to the document body so
 * the block still works outside a standard post-content wrapper.
 *
 * @param toc The `[data-flexa-toc]` wrapper.
 */
function contentRoot( toc: HTMLElement ): HTMLElement {
	const root = toc.closest< HTMLElement >( '.wp-block-post-content, .entry-content, article, main' );
	return root || document.body;
}

/**
 * Collect the headings that match the chosen levels, in document order, skipping
 * any heading that lives inside the TOC itself. Each gets an id (reusing an
 * existing one where present) so the links can target it.
 *
 * @param toc    The TOC wrapper.
 * @param levels Tag names to include, e.g. [ 'h2', 'h3' ].
 * @param used   Ids already taken on the page.
 */
function collect( toc: HTMLElement, levels: string[], used: Set< string > ): TocHeading[] {
	const root = contentRoot( toc );
	const nodes = Array.from( root.querySelectorAll< HTMLElement >( levels.join( ',' ) ) );
	const out: TocHeading[] = [];
	nodes.forEach( ( el ) => {
		if ( toc.contains( el ) ) {
			return;
		}
		const text = ( el.textContent || '' ).trim();
		if ( ! text ) {
			return;
		}
		let id = el.id;
		if ( ! id ) {
			id = slugify( text, used );
			el.id = id;
		} else {
			used.add( id );
		}
		out.push( { el, level: Number( el.tagName.charAt( 1 ) ), id, text } );
	} );
	return out;
}

/**
 * Build the nested <ul>/<li> markup for the collected headings.
 *
 * @param headings Collected headings in document order.
 */
function buildList( headings: TocHeading[] ): HTMLUListElement {
	const root = document.createElement( 'ul' );
	root.className = 'flexa-toc__list';
	// Stack of open lists paired with the level they belong to.
	const stack: Array< { list: HTMLUListElement; level: number } > = [ { list: root, level: 0 } ];
	let lastItem: HTMLLIElement | null = null;
	let lastLevel = 0;

	headings.forEach( ( h ) => {
		if ( lastItem && h.level > lastLevel ) {
			// Deeper — nest a new list under the previous item.
			const sub = document.createElement( 'ul' );
			sub.className = 'flexa-toc__list';
			lastItem.appendChild( sub );
			stack.push( { list: sub, level: h.level } );
		} else {
			// Same or shallower — pop back to the matching level.
			while ( stack.length > 1 && stack[ stack.length - 1 ].level > h.level ) {
				stack.pop();
			}
		}

		const item = document.createElement( 'li' );
		item.className = 'flexa-toc__item';
		const link = document.createElement( 'a' );
		link.className = 'flexa-toc__link';
		link.href = `#${ h.id }`;
		link.textContent = h.text;
		item.appendChild( link );
		stack[ stack.length - 1 ].list.appendChild( item );

		lastItem = item;
		lastLevel = h.level;
	} );

	return root;
}

/**
 * Wire smooth-scroll jumping (honouring a sticky-header offset) onto the links.
 *
 * @param list   The rendered list.
 * @param offset Pixels to leave above the target heading.
 */
function wireSmoothScroll( list: HTMLElement, offset: number ): void {
	list.addEventListener( 'click', ( event ) => {
		const target = event.target as HTMLElement;
		const link = target.closest< HTMLAnchorElement >( 'a.flexa-toc__link' );
		if ( ! link ) {
			return;
		}
		const id = decodeURIComponent( ( link.getAttribute( 'href' ) || '' ).slice( 1 ) );
		const heading = id ? document.getElementById( id ) : null;
		if ( ! heading ) {
			return;
		}
		event.preventDefault();
		const top = heading.getBoundingClientRect().top + window.pageYOffset - offset;
		window.scrollTo( { top, behavior: 'smooth' } );
		if ( window.history && window.history.pushState ) {
			window.history.pushState( null, '', `#${ id }` );
		}
	} );
}

/**
 * Wire the collapse toggle: the header shows/hides the list, tracking state on
 * the wrapper (`data-collapsed`) and the toggle's `aria-expanded`.
 *
 * @param toc The TOC wrapper.
 */
function wireCollapse( toc: HTMLElement ): void {
	const toggle = toc.querySelector< HTMLElement >( '.flexa-toc__toggle' );
	if ( ! toggle ) {
		return;
	}
	toggle.addEventListener( 'click', () => {
		const collapsed = toc.toggleAttribute( 'data-collapsed' );
		toggle.setAttribute( 'aria-expanded', collapsed ? 'false' : 'true' );
	} );
}

/**
 * Build one TOC: read its config, populate the list (or reveal the empty note),
 * then wire behaviour.
 *
 * @param toc  The TOC wrapper.
 * @param used Ids already taken on the page (shared so links stay unique).
 */
function initToc( toc: HTMLElement, used: Set< string > ): void {
	const listHost = toc.querySelector< HTMLElement >( '.flexa-toc__list' );
	if ( ! listHost ) {
		return;
	}

	const levels = ( toc.getAttribute( 'data-levels' ) || '' )
		.split( ',' )
		.map( ( l ) => l.trim() )
		.filter( Boolean );

	const empty = toc.querySelector< HTMLElement >( '.flexa-toc__empty' );
	const headings = levels.length ? collect( toc, levels, used ) : [];

	if ( ! headings.length ) {
		// No matching headings — hide the (empty) list, surface the note.
		toc.classList.add( 'flexa-toc--empty' );
		if ( empty ) {
			empty.hidden = false;
		}
		return;
	}

	if ( empty ) {
		empty.hidden = true;
	}
	const list = buildList( headings );
	listHost.replaceWith( list );

	if ( toc.hasAttribute( 'data-smooth' ) ) {
		const offset = Number( toc.getAttribute( 'data-offset' ) ) || 0;
		wireSmoothScroll( list, offset );
	}
	if ( toc.hasAttribute( 'data-collapsible' ) ) {
		wireCollapse( toc );
	}
}

/**
 * Reveal a lazy background element so its image rule applies.
 *
 * @param el Target element.
 */
function revealLazyBg( el: Element ): void {
	el.classList.add( LOADED_CLASS );
	el.removeAttribute( 'data-flexa-lazy-bg' );
}

/**
 * Wire up every TOC (and any lazy background) on the page.
 */
function init(): void {
	const used = new Set< string >();
	document.querySelectorAll< HTMLElement >( '[data-flexa-toc]' ).forEach( ( toc ) => initToc( toc, used ) );

	const lazy = document.querySelectorAll( '[data-flexa-lazy-bg]' );
	if ( ! lazy.length ) {
		return;
	}
	if ( typeof window.IntersectionObserver === 'undefined' ) {
		lazy.forEach( revealLazyBg );
		return;
	}
	const observer = new window.IntersectionObserver(
		( entries, obs ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					revealLazyBg( entry.target );
					obs.unobserve( entry.target );
				}
			} );
		},
		{ rootMargin: '200px 0px' }
	);
	lazy.forEach( ( el ) => observer.observe( el ) );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
