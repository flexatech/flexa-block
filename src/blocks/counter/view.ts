/**
 * Counter front-end view script.
 *
 * Each counter that opted into the count-up animation renders with
 * `data-flexa-counter`; its number spans carry `data-target`. When the counter
 * scrolls into view this script animates each number from zero to its target
 * (respecting the stored duration and the user's reduced-motion preference),
 * then leaves the final value in place. It also lazy-loads a background image
 * when the block opts in (shared `data-flexa-lazy-bg` marker).
 */

export {}; // Module scope so names don't collide with other view scripts.

const LOADED_CLASS = 'flexa-bg-loaded';

/**
 * Count how many decimal places a numeric string uses, so the animation keeps
 * the same precision as the target (e.g. "4.5" animates in tenths).
 *
 * @param raw The target value string.
 * @return Number of decimal places.
 */
function decimalsOf( raw: string ): number {
	const dot = raw.indexOf( '.' );
	return dot === -1 ? 0 : raw.length - dot - 1;
}

/**
 * Animate one number span from zero to its `data-target`.
 *
 * @param el       The `.flexa-counter__value` element.
 * @param duration Animation length in milliseconds.
 */
function animateValue( el: HTMLElement, duration: number ): void {
	const raw = el.getAttribute( 'data-target' ) || '';
	const target = parseFloat( raw );
	if ( Number.isNaN( target ) ) {
		return;
	}
	const decimals = decimalsOf( raw );

	const reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	if ( reduced || duration <= 0 ) {
		el.textContent = target.toFixed( decimals );
		return;
	}

	const start = performance.now();
	const step = ( now: number ): void => {
		const progress = Math.min( 1, ( now - start ) / duration );
		// Ease-out so the count decelerates as it approaches the target.
		const eased = 1 - Math.pow( 1 - progress, 3 );
		el.textContent = ( target * eased ).toFixed( decimals );
		if ( progress < 1 ) {
			window.requestAnimationFrame( step );
		} else {
			el.textContent = target.toFixed( decimals );
		}
	};
	// Start from zero so the run is visible even when already near the target.
	el.textContent = ( 0 ).toFixed( decimals );
	window.requestAnimationFrame( step );
}

/**
 * Run the count-up for every number in one counter.
 *
 * @param root The `[data-flexa-counter]` wrapper.
 */
function runCounter( root: HTMLElement ): void {
	const duration = parseInt( root.getAttribute( 'data-duration' ) || '2000', 10 ) || 2000;
	root.querySelectorAll< HTMLElement >( '.flexa-counter__value[data-target]' ).forEach( ( el ) => animateValue( el, duration ) );
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
 * Wire up every counter (and any lazy background) on the page.
 */
function init(): void {
	const counters = document.querySelectorAll< HTMLElement >( '[data-flexa-counter]' );
	const lazy = document.querySelectorAll( '[data-flexa-lazy-bg]' );

	if ( typeof window.IntersectionObserver === 'undefined' ) {
		counters.forEach( runCounter );
		lazy.forEach( revealLazyBg );
		return;
	}

	if ( counters.length ) {
		const counterObserver = new window.IntersectionObserver(
			( entries, obs ) => {
				entries.forEach( ( entry ) => {
					if ( entry.isIntersecting ) {
						runCounter( entry.target as HTMLElement );
						obs.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '0px 0px -10% 0px' }
		);
		counters.forEach( ( el ) => counterObserver.observe( el ) );
	}

	if ( lazy.length ) {
		const lazyObserver = new window.IntersectionObserver(
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
		lazy.forEach( ( el ) => lazyObserver.observe( el ) );
	}
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
