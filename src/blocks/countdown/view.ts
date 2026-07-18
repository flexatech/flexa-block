/**
 * Countdown front-end view script.
 *
 * Each countdown renders with `data-flexa-countdown` and a target time in
 * `data-end`. This script ticks once a second, updates the digits, and applies
 * the chosen completion action when the target passes. It also lazy-loads a
 * background image when the block opts in (shared `data-flexa-lazy-bg` marker).
 *
 * The unit maths mirrors shared.ts so the front end and the editor preview
 * agree; it's re-derived here in vanilla DOM terms (which units to update comes
 * from the digits actually present in the markup).
 */

import { UNIT_KEYS, remainingParts, pad, type UnitKey } from './shared';

export {}; // Module scope so names don't collide with other view scripts.

const LOADED_CLASS = 'flexa-bg-loaded';

/**
 * Drive one countdown element.
 *
 * @param root The `[data-flexa-countdown]` wrapper.
 */
function initCountdown( root: HTMLElement ): void {
	const target = Date.parse( root.getAttribute( 'data-end' ) || '' );
	if ( Number.isNaN( target ) ) {
		return;
	}

	const action = root.getAttribute( 'data-expired-action' ) || 'hide';
	const message = root.getAttribute( 'data-expired-message' ) || '';
	const timer = root.querySelector< HTMLElement >( '.flexa-countdown__timer' );

	// Which units are present in the markup (drives roll-up + updates).
	const digits = new Map< UnitKey, HTMLElement >();
	UNIT_KEYS.forEach( ( unit ) => {
		const el = root.querySelector< HTMLElement >( `.flexa-countdown__digit[data-unit="${ unit }"]` );
		if ( el ) {
			digits.set( unit, el );
		}
	} );
	if ( ! digits.size ) {
		return;
	}

	const shows: Record< UnitKey, boolean > = {
		days: digits.has( 'days' ),
		hours: digits.has( 'hours' ),
		minutes: digits.has( 'minutes' ),
		seconds: digits.has( 'seconds' ),
	};

	let expired = false;

	const expire = (): void => {
		expired = true;
		if ( 'hide' === action ) {
			root.style.display = 'none';
			return;
		}
		if ( 'message' === action ) {
			if ( timer ) {
				timer.hidden = true;
			}
			const note = document.createElement( 'div' );
			note.className = 'flexa-countdown__message';
			note.textContent = message;
			root.appendChild( note );
			return;
		}
		// 'zero' — leave the (already zeroed) digits in place.
	};

	const tick = (): void => {
		if ( expired ) {
			return;
		}
		const remaining = target - Date.now();
		const parts = remainingParts( remaining, shows );
		digits.forEach( ( el, unit ) => {
			el.textContent = pad( parts[ unit ] );
		} );
		if ( remaining <= 0 ) {
			expire();
		}
	};

	tick();
	if ( ! expired ) {
		const id = window.setInterval( () => {
			tick();
			if ( expired ) {
				window.clearInterval( id );
			}
		}, 1000 );
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
 * Wire up every countdown (and any lazy background) on the page.
 */
function init(): void {
	document.querySelectorAll< HTMLElement >( '[data-flexa-countdown]' ).forEach( initCountdown );

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
