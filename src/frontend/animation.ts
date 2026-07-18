/**
 * Shared scroll-entrance animation observer.
 *
 * Every block that opted into an animation renders with `data-flexa-animate` and
 * an initial hidden state (via the static `.flexa-anim--*` CSS). This one script
 * watches those elements and, the moment each scrolls into view, flips it to
 * `data-flexa-animated="true"` so the CSS transition plays — once — then stops
 * observing it. Reduced-motion is handled by the CSS itself (elements are shown
 * immediately), so this script only drives the reveal.
 */

export {}; // Module scope so names don't collide with other frontend scripts.

const ANIMATE_ATTR = 'data-flexa-animate';
const ANIMATED_ATTR = 'data-flexa-animated';

/**
 * Reveal one element: run its transition, then leave it in the shown state.
 *
 * @param el Target `[data-flexa-animate]` element.
 */
function reveal( el: Element ): void {
	el.setAttribute( ANIMATED_ATTR, 'true' );
}

/**
 * Wire up every animated element on the page.
 */
function init(): void {
	const targets = document.querySelectorAll( `[${ ANIMATE_ATTR }]` );
	if ( ! targets.length ) {
		return;
	}

	// No IntersectionObserver (or reduced motion already handled by CSS): show all.
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
		// Fire a touch before fully in view; 15% visible is enough.
		{ rootMargin: '0px 0px -10% 0px', threshold: 0.15 }
	);

	targets.forEach( ( el ) => observer.observe( el ) );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
