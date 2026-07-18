/**
 * CTA front-end view script: lazy-load background images.
 *
 * A CTA banner whose background image opts into lazy loading renders with
 * `data-flexa-lazy-bg` and WITHOUT the actual `background-image` url (that rule is
 * gated behind `.flexa-bg-loaded` in the generated CSS). When the element nears
 * the viewport we add `flexa-bg-loaded`, which lets the image fetch.
 *
 * Falls back to loading immediately when IntersectionObserver is unavailable.
 */

export {}; // Treat as a module so top-level names don't collide with other view scripts.

const LOADED_CLASS = 'flexa-bg-loaded';
const SELECTOR = '[data-flexa-lazy-bg]';

/**
 * Mark one element as ready so its background image rule applies.
 *
 * @param el Target element.
 */
function reveal( el: Element ): void {
	el.classList.add( LOADED_CLASS );
	el.removeAttribute( 'data-flexa-lazy-bg' );
}

/**
 * Wire up lazy loading for all lazy CTA banners on the page.
 */
function init(): void {
	const targets = document.querySelectorAll( SELECTOR );
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

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
