export {}; // Module scope so names don't collide with other view scripts.

/**
 * Add to Cart front-end view script.
 *
 * Two jobs, both about the parts WooCommerce leaves bare:
 *
 * 1. QUANTITY STEPPER. Core prints a plain `input[type=number]`, whose native
 *    spinners are tiny and look different in every browser. This wraps the
 *    input with a minus / plus pair and keeps them in step with the field's own
 *    min / max / step. The markup is added here rather than in render.php
 *    because the field belongs to core's template, not to ours.
 *
 * 2. "SELECT AN OPTION FIRST". On a variable product core disables the button
 *    until every attribute is chosen, and answers a click with `window.alert`.
 *    A capture-phase listener runs before core's delegated handler, stops the
 *    event there and shows an inline notice instead, so the message stays in
 *    the page. Nothing here re-enables the button: whether the product can be
 *    added is still entirely WooCommerce's decision.
 *
 * Every string comes off the wrapper as a data attribute, translated by PHP in
 * render.php — a view script has no access to the block's text domain.
 */

const BOUND = 'data-flexa-cart-bound';

/** How long the "choose an option" notice stays up. */
const NOTICE_MS = 6000;

/** Minus / plus glyphs, drawn to match the block's other icons. */
const GLYPH = {
	minus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M5 12h14"/></svg>',
	plus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M12 5v14M5 12h14"/></svg>',
};

/**
 * Read a numeric input attribute, falling back when it is absent or not a
 * number — core leaves `max` off entirely for a product with no stock limit.
 *
 * @param input    The quantity field.
 * @param name     Attribute name.
 * @param fallback Value to use when the attribute cannot be read.
 * @return The number.
 */
function numAttr( input: HTMLInputElement, name: string, fallback: number ): number {
	const raw = input.getAttribute( name );
	if ( null === raw || '' === raw ) {
		return fallback;
	}
	const value = parseFloat( raw );
	return Number.isNaN( value ) ? fallback : value;
}

/**
 * Build one stepper button.
 *
 * @param kind  Which way it steps.
 * @param label Accessible name.
 * @return The button.
 */
function stepButton( kind: 'minus' | 'plus', label: string ): HTMLButtonElement {
	const button = document.createElement( 'button' );
	button.type = 'button';
	button.className = `flexa-qty__btn flexa-qty__btn--${ kind }`;
	button.setAttribute( 'aria-label', label );
	button.innerHTML = GLYPH[ kind ];
	return button;
}

/**
 * Wrap one quantity field with a minus / plus pair.
 *
 * @param wrapper Core's `.quantity` element.
 */
function initStepper( wrapper: HTMLElement ): void {
	const input = wrapper.querySelector< HTMLInputElement >( 'input.qty' );
	if ( ! input || wrapper.classList.contains( 'flexa-qty' ) ) {
		return;
	}

	// A read-only quantity (a grouped product's "1") has nothing to step.
	if ( input.readOnly || 'hidden' === input.type ) {
		return;
	}

	const block = wrapper.closest< HTMLElement >( '.flexa-product-add-to-cart' );
	const minus = stepButton( 'minus', block?.dataset.flexaLabelMinus || 'Decrease quantity' );
	const plus = stepButton( 'plus', block?.dataset.flexaLabelPlus || 'Increase quantity' );

	wrapper.classList.add( 'flexa-qty' );
	wrapper.insertBefore( minus, input );
	wrapper.appendChild( plus );

	/** Clamp the field and tell the page, the way a real edit would. */
	const step = ( direction: -1 | 1 ): void => {
		const size = numAttr( input, 'step', 1 ) || 1;
		const min = numAttr( input, 'min', 0 );
		const max = numAttr( input, 'max', Infinity );
		const current = parseFloat( input.value );
		const base = Number.isNaN( current ) ? min : current;

		let next = base + direction * size;
		if ( next < min ) next = min;
		if ( next > max ) next = max;

		// Steps can be fractional (weight-priced goods), so trim float noise.
		input.value = String( parseFloat( next.toFixed( 4 ) ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		syncDisabled();
	};

	/** Grey out whichever end the field has reached. */
	const syncDisabled = (): void => {
		const min = numAttr( input, 'min', 0 );
		const max = numAttr( input, 'max', Infinity );
		const value = parseFloat( input.value );
		const current = Number.isNaN( value ) ? min : value;
		minus.disabled = current <= min;
		plus.disabled = current >= max;
	};

	minus.addEventListener( 'click', () => step( -1 ) );
	plus.addEventListener( 'click', () => step( 1 ) );
	input.addEventListener( 'input', syncDisabled );
	syncDisabled();
}

/**
 * Show the inline "choose an option" notice for one form, replacing the alert.
 *
 * @param button  The add-to-cart button.
 * @param message What to say.
 */
function showNotice( button: HTMLElement, message: string ): void {
	const form = button.closest( 'form.cart' ) || button.parentElement;
	if ( ! form ) {
		return;
	}

	let notice = form.querySelector< HTMLElement >( '.flexa-product-add-to-cart__notice' );
	if ( ! notice ) {
		notice = document.createElement( 'p' );
		notice.className = 'flexa-product-add-to-cart__notice';
		// Announced by screen readers without stealing focus.
		notice.setAttribute( 'role', 'status' );
		form.appendChild( notice );
	}

	notice.textContent = message;

	window.clearTimeout( Number( notice.dataset.flexaTimer || 0 ) );
	notice.dataset.flexaTimer = String(
		window.setTimeout( () => {
			notice?.remove();
		}, NOTICE_MS )
	);
}

/**
 * Intercept a click on a button WooCommerce has disabled, before core's own
 * delegated handler can reach it and call `window.alert`.
 *
 * @param wrapper The block wrapper.
 */
function initVariationGuard( wrapper: HTMLElement ): void {
	wrapper.addEventListener(
		'click',
		( event: Event ) => {
			const target = event.target as HTMLElement | null;
			const button = target?.closest< HTMLElement >( 'button.single_add_to_cart_button' );
			if ( ! button || ! button.classList.contains( 'disabled' ) ) {
				return;
			}

			// Core's handler lives on `document`, so stopping propagation during
			// the capture phase is what keeps the alert from firing.
			event.preventDefault();
			event.stopPropagation();

			const unavailable = button.classList.contains( 'wc-variation-is-unavailable' );

			showNotice(
				button,
				unavailable
					? wrapper.dataset.flexaMsgUnavailable || 'Sorry, this product is unavailable. Please choose a different combination.'
					: wrapper.dataset.flexaMsgSelect || 'Please select some product options before adding this product to your cart.'
			);
		},
		true
	);
}

/**
 * Wire one Add to Cart block.
 *
 * @param wrapper The block wrapper.
 */
function initBlock( wrapper: HTMLElement ): void {
	// Guard against a second pass (blocks can be re-initialised by other scripts).
	if ( wrapper.hasAttribute( BOUND ) ) {
		return;
	}
	wrapper.setAttribute( BOUND, '' );

	initVariationGuard( wrapper );
	wrapper.querySelectorAll< HTMLElement >( '.quantity' ).forEach( initStepper );

	// A variable product swaps its quantity field out when the visitor picks a
	// variation, so the stepper has to be re-applied to whatever replaced it.
	const observer = new MutationObserver( () => {
		wrapper.querySelectorAll< HTMLElement >( '.quantity' ).forEach( initStepper );
	} );
	observer.observe( wrapper, { childList: true, subtree: true } );
}

/**
 * Wire up every Add to Cart block on the page.
 */
function init(): void {
	document.querySelectorAll< HTMLElement >( '.flexa-product-add-to-cart' ).forEach( initBlock );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
