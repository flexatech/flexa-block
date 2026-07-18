/**
 * Progress Bar front-end view script.
 *
 * Each bar that opted into the on-scroll animation renders with
 * `data-flexa-process-bar`. The final value is already painted server-side (the
 * line fill's width, the ring's stroke-dashoffset), so with JavaScript off the
 * bar simply shows its final state. When the block scrolls into view this script
 * animates the fill from empty to that final state and counts the readout number
 * up from zero — respecting the stored duration and the user's reduced-motion
 * preference. It also lazy-loads a background image when the block opts in
 * (shared `data-flexa-lazy-bg` marker).
 */

export {}; // Module scope so names don't collide with other view scripts.

const LOADED_CLASS = 'flexa-bg-loaded';

/**
 * Count how many decimal places a numeric string uses, so the count-up keeps the
 * same precision as the target.
 *
 * @param raw The target value string.
 * @return Number of decimal places.
 */
function decimalsOf( raw: string ): number {
	const dot = raw.indexOf( '.' );
	return dot === -1 ? 0 : raw.length - dot - 1;
}

/**
 * Animate one counter number from zero to its `data-target`.
 *
 * @param el       The `.flexa-process-bar__counter-value` element.
 * @param duration Animation length in milliseconds.
 */
function animateNumber( el: HTMLElement, duration: number ): void {
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
		const eased = 1 - Math.pow( 1 - progress, 3 );
		el.textContent = ( target * eased ).toFixed( decimals );
		if ( progress < 1 ) {
			window.requestAnimationFrame( step );
		} else {
			el.textContent = target.toFixed( decimals );
		}
	};
	el.textContent = ( 0 ).toFixed( decimals );
	window.requestAnimationFrame( step );
}

/**
 * Animate the fill of one bar from empty to its final (server-rendered) value.
 *
 * @param root     The `[data-flexa-process-bar]` wrapper.
 * @param duration Animation length in milliseconds.
 */
function animateFill( root: HTMLElement, duration: number ): void {
	const reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const type = root.getAttribute( 'data-bar-type' ) || 'line';

	if ( type === 'line' ) {
		const fill = root.querySelector< HTMLElement >( '.flexa-process-bar__fill' );
		if ( ! fill ) {
			return;
		}
		const final = fill.style.width || '0%';
		if ( reduced || duration <= 0 ) {
			fill.style.width = final;
			return;
		}
		fill.style.transition = 'none';
		fill.style.width = '0%';
		// Force a reflow so the browser registers the start value before the transition.
		void fill.offsetWidth;
		fill.style.transition = `width ${ duration }ms ease-out`;
		fill.style.width = final;
		return;
	}

	// Circle / semi-circle: animate the ring's stroke-dashoffset from empty (the
	// full dash length) to the final offset.
	const fill = root.querySelector< SVGElement >( '.flexa-process-bar__ring-fill' );
	if ( ! fill ) {
		return;
	}
	const dash = fill.getAttribute( 'stroke-dasharray' ) || '0';
	const final = fill.getAttribute( 'stroke-dashoffset' ) || '0';
	if ( reduced || duration <= 0 ) {
		( fill as unknown as HTMLElement ).style.strokeDashoffset = final;
		return;
	}
	const style = ( fill as unknown as HTMLElement ).style;
	style.transition = 'none';
	style.strokeDashoffset = dash;
	void ( fill as SVGGraphicsElement ).getBoundingClientRect();
	style.transition = `stroke-dashoffset ${ duration }ms ease-out`;
	style.strokeDashoffset = final;
}

/**
 * Run the full animation (fill + counter) for one bar.
 *
 * @param root The `[data-flexa-process-bar]` wrapper.
 */
function runBar( root: HTMLElement ): void {
	const duration = parseInt( root.getAttribute( 'data-duration' ) || '1500', 10 ) || 1500;
	animateFill( root, duration );
	root.querySelectorAll< HTMLElement >( '.flexa-process-bar__counter-value[data-target]' ).forEach( ( el ) => animateNumber( el, duration ) );
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
 * Wire up every progress bar (and any lazy background) on the page.
 */
function init(): void {
	const bars = document.querySelectorAll< HTMLElement >( '[data-flexa-process-bar]' );
	const lazy = document.querySelectorAll( '[data-flexa-lazy-bg]' );

	if ( typeof window.IntersectionObserver === 'undefined' ) {
		bars.forEach( runBar );
		lazy.forEach( revealLazyBg );
		return;
	}

	if ( bars.length ) {
		const barObserver = new window.IntersectionObserver(
			( entries, obs ) => {
				entries.forEach( ( entry ) => {
					if ( entry.isIntersecting ) {
						runBar( entry.target as HTMLElement );
						obs.unobserve( entry.target );
					}
				} );
			},
			{ rootMargin: '0px 0px -10% 0px' }
		);
		bars.forEach( ( el ) => barObserver.observe( el ) );
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
