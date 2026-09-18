/**
 * Product Description front-end view script.
 *
 * Only the clamped variant needs behaviour: the wrapper carries
 * `flexa-product-description--clamped` and a toggle button whose two labels ride
 * along as `data-more` / `data-less`. Clicking it flips `is-expanded` on the
 * wrapper (style.scss unsets the clamp) and swaps the label + `aria-expanded`.
 */

export {}; // Module scope so names don't collide with other view scripts.

const BOUND = 'flexaDescriptionBound';

/**
 * Wire one clamped description's toggle button.
 *
 * @param root The `.flexa-product-description--clamped` wrapper.
 */
function initDescription( root: HTMLElement ): void {
	const toggle = root.querySelector< HTMLElement >( '.flexa-product-description__toggle' );
	if ( ! toggle ) {
		return;
	}

	// A block can be initialised twice (editor iframe, re-run script); bind once.
	if ( toggle.dataset[ BOUND ] === '1' ) {
		return;
	}
	toggle.dataset[ BOUND ] = '1';

	const more = toggle.getAttribute( 'data-more' ) || toggle.textContent || '';
	const less = toggle.getAttribute( 'data-less' ) || more;

	toggle.setAttribute( 'aria-expanded', 'false' );

	toggle.addEventListener( 'click', () => {
		const expanded = root.classList.toggle( 'is-expanded' );
		toggle.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
		toggle.textContent = expanded ? less : more;
	} );
}

function init(): void {
	document.querySelectorAll< HTMLElement >( '.flexa-product-description--clamped' ).forEach( initDescription );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
