/**
 * FAQ front-end view script.
 *
 * Each FAQ renders with `data-flexa-faq`; every question is a <button> that
 * toggles the `data-open` state on its item (CSS animates the answer open). The
 * two behaviour flags come from data attributes: `data-close-others` collapses
 * siblings when one opens, and `data-allow-toggle` lets an open item be closed
 * again. It also lazy-loads a background image when the block opts in (shared
 * `data-flexa-lazy-bg` marker).
 */

export {}; // Module scope so names don't collide with other view scripts.

const LOADED_CLASS = 'flexa-bg-loaded';

/**
 * Wire one FAQ element's questions to their answers.
 *
 * @param root The `[data-flexa-faq]` wrapper.
 */
function initFaq( root: HTMLElement ): void {
	const closeOthers = root.hasAttribute( 'data-close-others' );
	const allowToggle = root.hasAttribute( 'data-allow-toggle' );
	const items = Array.from( root.querySelectorAll< HTMLElement >( '.flexa-faq__item' ) );
	if ( ! items.length ) {
		return;
	}

	const setOpen = ( item: HTMLElement, open: boolean ): void => {
		item.toggleAttribute( 'data-open', open );
		const button = item.querySelector< HTMLElement >( '.flexa-faq__question' );
		if ( button ) {
			button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}
	};

	items.forEach( ( item ) => {
		const button = item.querySelector< HTMLElement >( '.flexa-faq__question' );
		if ( ! button ) {
			return;
		}
		button.addEventListener( 'click', () => {
			const isOpen = item.hasAttribute( 'data-open' );
			if ( isOpen ) {
				// Keep at least one panel open unless toggling is allowed.
				if ( allowToggle ) {
					setOpen( item, false );
				}
				return;
			}
			if ( closeOthers ) {
				items.forEach( ( other ) => {
					if ( other !== item ) {
						setOpen( other, false );
					}
				} );
			}
			setOpen( item, true );
		} );
	} );
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
 * Wire up every FAQ (and any lazy background) on the page.
 */
function init(): void {
	document.querySelectorAll< HTMLElement >( '[data-flexa-faq]' ).forEach( initFaq );

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
