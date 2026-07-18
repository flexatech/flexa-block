/**
 * Pricing Table front-end view script.
 *
 * Wires the optional monthly/yearly billing switch. Each pricing table that has
 * the toggle renders both a monthly and a yearly price group per plan (monthly
 * shown by default, yearly hidden via CSS). Clicking a billing option flips the
 * wrapper's `.is-yearly` class — which swaps every plan's visible price group —
 * and keeps the two option buttons' active / pressed state in sync. A single
 * pill indicator slides horizontally to the active option (its position/size fed
 * to CSS vars from the measured button), mirroring the tabs block's moving
 * active indicator. Tables without the toggle are ignored.
 */

export {}; // Module scope so names don't collide with other view scripts.

/**
 * Slide the pill indicator onto the active billing option by measuring its
 * offset + size and feeding them to the CSS vars. Marks the switch
 * `is-indicator-ready` so the CSS reveals the pill and drops the per-option
 * active fill. When the switch is hidden (the active option measures 0 wide) the
 * ready flag is removed so the pill stays hidden and the static fill takes over.
 *
 * @param billing The `.flexa-pricing-table__billing` switch.
 * @param options Its billing-option buttons.
 */
function positionIndicator( billing: HTMLElement, options: HTMLButtonElement[] ): void {
	const indicator = billing.querySelector< HTMLElement >( '.flexa-pricing-table__billing-indicator' );
	if ( ! indicator ) {
		return;
	}
	const active = options.find( ( opt ) => opt.classList.contains( 'is-active' ) );
	if ( ! active || 0 === active.offsetWidth ) {
		billing.classList.remove( 'is-indicator-ready' );
		return;
	}
	indicator.style.setProperty( '--flexa-billing-ind-x', active.offsetLeft + 'px' );
	indicator.style.setProperty( '--flexa-billing-ind-y', active.offsetTop + 'px' );
	indicator.style.setProperty( '--flexa-billing-ind-w', active.offsetWidth + 'px' );
	indicator.style.setProperty( '--flexa-billing-ind-h', active.offsetHeight + 'px' );
	billing.classList.add( 'is-indicator-ready' );
}

/** Every wired switch on the page, so the resize handler can reposition them. */
const switches: Array< { billing: HTMLElement; options: HTMLButtonElement[] } > = [];

/**
 * Wire one pricing table's billing switch.
 *
 * @param root The `.flexa-pricing-table` wrapper.
 */
function initTable( root: HTMLElement ): void {
	const billing = root.querySelector< HTMLElement >( '.flexa-pricing-table__billing' );
	if ( ! billing ) {
		return;
	}
	const options = Array.from( billing.querySelectorAll< HTMLButtonElement >( '.flexa-pricing-table__billing-option' ) );
	if ( options.length === 0 ) {
		return;
	}

	const select = ( mode: string ): void => {
		const yearly = mode === 'yearly';
		root.classList.toggle( 'is-yearly', yearly );
		billing.setAttribute( 'data-billing', yearly ? 'yearly' : 'monthly' );
		options.forEach( ( opt ) => {
			const active = ( opt.getAttribute( 'data-billing-value' ) || 'monthly' ) === mode;
			opt.classList.toggle( 'is-active', active );
			opt.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
		} );
		positionIndicator( billing, options );
	};

	options.forEach( ( opt ) => {
		opt.addEventListener( 'click', () => select( opt.getAttribute( 'data-billing-value' ) || 'monthly' ) );
	} );

	switches.push( { billing, options } );

	// Position the pill once layout has settled (after the first paint).
	window.requestAnimationFrame( () => positionIndicator( billing, options ) );
}

/** rAF guard so a burst of resize events repositions the pills just once. */
let resizeScheduled = false;

/** Reposition every switch's pill on resize (throttled to one rAF). */
function handleResize(): void {
	if ( resizeScheduled ) {
		return;
	}
	resizeScheduled = true;
	window.requestAnimationFrame( () => {
		resizeScheduled = false;
		switches.forEach( ( s ) => positionIndicator( s.billing, s.options ) );
	} );
}

/**
 * Wire every pricing table on the page.
 */
function init(): void {
	document.querySelectorAll< HTMLElement >( '.flexa-pricing-table--has-toggle' ).forEach( initTable );
	if ( switches.length > 0 ) {
		window.addEventListener( 'resize', handleResize );
	}
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
