/**
 * Shared front-end lightbox.
 *
 * Three blocks open a full-screen image overlay — Image, Images Gallery and
 * Product Image — and until this module existed the first two carried a
 * near-identical copy of it, down to the same `isDarkMode()` helper. The only
 * real difference was the CSS class prefix, so that is what the caller passes:
 * each block keeps the exact class names its own stylesheet already targets,
 * and nothing about their markup or CSS had to change to adopt this.
 *
 * One overlay exists at a time. It lives on `<body>`, outside the block, which
 * is why the dark background colour is resolved here rather than coming from
 * the block's generated per-instance CSS.
 *
 * @package Flexa\Block
 */

/** One image in the overlay. */
export interface LightboxSlide {
	src: string;
	caption?: string;
}

/** What a block hands over when it opens the overlay. */
export interface LightboxOptions {
	/** Class prefix, e.g. `flexa-image-lightbox` — the block's own CSS targets it. */
	prefix: string;
	/** Images to show; more than one turns the prev / next arrows on. */
	slides: LightboxSlide[];
	/** Which one opens first (clamped). */
	index?: number;
	/** Backdrop colour override; the stylesheet's own default applies when empty. */
	background?: string;
	/** Whether captions are printed at all. */
	showCaption?: boolean;
	/** Accessible names, so each block can translate them through PHP. */
	labels?: { close?: string; previous?: string; next?: string };
}

/** How far a touch has to travel before it counts as a swipe, in px. */
const SWIPE_THRESHOLD = 40;

let overlay: HTMLElement | null = null;
let slides: LightboxSlide[] = [];
let current = 0;
let captionsOn = true;
let classPrefix = '';

/**
 * Whether the page is currently in dark mode, covering both strategies the
 * plugin supports: an explicit `[data-theme="dark"]` on `<html>` / `<body>`, or
 * the OS `prefers-color-scheme`.
 *
 * @return Whether dark mode is on.
 */
export function isDarkMode(): boolean {
	if (
		document.documentElement.getAttribute( 'data-theme' ) === 'dark' ||
		document.body.getAttribute( 'data-theme' ) === 'dark'
	) {
		return true;
	}
	return !! ( window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches );
}

/**
 * Close the overlay and drop every listener it installed.
 */
export function closeLightbox(): void {
	if ( ! overlay ) {
		return;
	}
	overlay.remove();
	overlay = null;
	document.removeEventListener( 'keydown', onKeydown );
}

/** Paint the current slide into the open overlay. */
function renderSlide(): void {
	if ( ! overlay ) {
		return;
	}

	const slide = slides[ current ];
	const img = overlay.querySelector< HTMLImageElement >( `.${ classPrefix }__img` );
	const cap = overlay.querySelector< HTMLElement >( `.${ classPrefix }__caption` );

	if ( img ) {
		img.src = slide.src;
		img.alt = slide.caption || '';
	}
	if ( cap ) {
		const text = captionsOn ? slide.caption || '' : '';
		cap.textContent = text;
		cap.style.display = text ? '' : 'none';
	}
}

/** Move by `delta` slides, wrapping around. */
function step( delta: number ): void {
	if ( slides.length < 2 ) {
		return;
	}
	current = ( current + delta + slides.length ) % slides.length;
	renderSlide();
}

function onKeydown( e: KeyboardEvent ): void {
	if ( e.key === 'Escape' ) {
		closeLightbox();
	} else if ( e.key === 'ArrowLeft' ) {
		step( -1 );
	} else if ( e.key === 'ArrowRight' ) {
		step( 1 );
	}
}

/**
 * Build one arrow button.
 *
 * @param className Full class attribute.
 * @param label     Accessible name.
 * @param path      SVG path data.
 * @return The button.
 */
function arrowButton( className: string, label: string, path: string ): HTMLButtonElement {
	const btn = document.createElement( 'button' );
	btn.type = 'button';
	btn.className = className;
	btn.setAttribute( 'aria-label', label );
	btn.innerHTML = `<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="${ path }"/></svg>`;
	return btn;
}

/**
 * Let a touch drag left / right move between slides, the way a shopper expects
 * on a phone. Ignored for a single slide.
 *
 * @param target The overlay.
 */
function bindSwipe( target: HTMLElement ): void {
	let startX = 0;
	let tracking = false;

	target.addEventListener(
		'touchstart',
		( e: TouchEvent ) => {
			if ( slides.length < 2 || e.touches.length !== 1 ) {
				return;
			}
			startX = e.touches[ 0 ].clientX;
			tracking = true;
		},
		{ passive: true }
	);

	target.addEventListener(
		'touchend',
		( e: TouchEvent ) => {
			if ( ! tracking ) {
				return;
			}
			tracking = false;
			const endX = e.changedTouches[ 0 ]?.clientX ?? startX;
			const travelled = endX - startX;
			if ( Math.abs( travelled ) >= SWIPE_THRESHOLD ) {
				step( travelled > 0 ? -1 : 1 );
			}
		},
		{ passive: true }
	);
}

/**
 * Open the overlay. Any overlay already open is replaced.
 *
 * @param options What to show and what to call it.
 */
export function openLightbox( options: LightboxOptions ): void {
	const usable = ( options.slides || [] ).filter( ( slide ) => slide && slide.src );
	if ( ! usable.length ) {
		return;
	}

	closeLightbox();

	slides = usable;
	classPrefix = options.prefix;
	captionsOn = false !== options.showCaption;
	current = Math.min( Math.max( options.index || 0, 0 ), slides.length - 1 );

	const labels = options.labels || {};

	const el = document.createElement( 'div' );
	el.className = classPrefix;
	if ( options.background ) {
		el.style.setProperty( '--flexa-lightbox-bg', options.background );
	}

	const close = document.createElement( 'button' );
	close.type = 'button';
	close.className = `${ classPrefix }__close`;
	close.setAttribute( 'aria-label', labels.close || 'Close' );
	close.textContent = '×';
	el.appendChild( close );

	if ( slides.length > 1 ) {
		const prev = arrowButton( `${ classPrefix }__nav ${ classPrefix }__nav--prev`, labels.previous || 'Previous', 'M15 18l-6-6 6-6' );
		const next = arrowButton( `${ classPrefix }__nav ${ classPrefix }__nav--next`, labels.next || 'Next', 'M9 6l6 6-6 6' );

		prev.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			step( -1 );
		} );
		next.addEventListener( 'click', ( e ) => {
			e.stopPropagation();
			step( 1 );
		} );

		el.appendChild( prev );
		el.appendChild( next );
	}

	const figure = document.createElement( 'figure' );
	figure.className = `${ classPrefix }__figure`;

	const img = document.createElement( 'img' );
	img.className = `${ classPrefix }__img`;
	figure.appendChild( img );

	const cap = document.createElement( 'figcaption' );
	cap.className = `${ classPrefix }__caption`;
	figure.appendChild( cap );

	el.appendChild( figure );

	// A click on the backdrop closes; one on the image or the arrows does not.
	el.addEventListener( 'click', ( e ) => {
		if ( e.target === el || e.target === close ) {
			closeLightbox();
		}
	} );

	bindSwipe( el );

	document.body.appendChild( el );
	overlay = el;
	document.addEventListener( 'keydown', onKeydown );
	renderSlide();
}
