/**
 * Images Gallery block — front-end view script.
 *
 * Handles the click action for each gallery: open a self-contained lightbox with
 * previous / next navigation across the gallery's images, or jump to the media
 * file. Link-less galleries (clickAction "none") need no script.
 */

export {}; // Treat as a module so top-level names don't collide with other view scripts.

interface Slide {
	src: string;
	caption: string;
}

let activeLightbox: HTMLElement | null = null;
let slides: Slide[] = [];
let current = 0;
let showCaption = true;

/**
 * Whether the page is currently in dark mode, covering both strategies the
 * plugin supports: an explicit `[data-theme="dark"]`, or the OS preference. The
 * lightbox lives on <body>, outside the block, so its dark colour can't come
 * from the per-instance CSS — resolve it here.
 */
function isDarkMode(): boolean {
	if ( document.documentElement.getAttribute( 'data-theme' ) === 'dark' || document.body.getAttribute( 'data-theme' ) === 'dark' ) {
		return true;
	}
	return !! ( window.matchMedia && window.matchMedia( '(prefers-color-scheme: dark)' ).matches );
}

function closeLightbox(): void {
	if ( activeLightbox ) {
		activeLightbox.remove();
		activeLightbox = null;
		document.removeEventListener( 'keydown', onKeydown );
	}
}

function renderSlide(): void {
	if ( ! activeLightbox ) {
		return;
	}
	const slide = slides[ current ];
	const img = activeLightbox.querySelector< HTMLImageElement >( '.flexa-images-gallery-lightbox__img' );
	const cap = activeLightbox.querySelector< HTMLElement >( '.flexa-images-gallery-lightbox__caption' );
	if ( img ) {
		img.src = slide.src;
		img.alt = slide.caption || '';
	}
	if ( cap ) {
		cap.textContent = showCaption ? slide.caption : '';
		cap.style.display = showCaption && slide.caption ? '' : 'none';
	}
}

function step( delta: number ): void {
	if ( ! slides.length ) {
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

function arrowButton( className: string, label: string, path: string ): HTMLButtonElement {
	const btn = document.createElement( 'button' );
	btn.type = 'button';
	btn.className = className;
	btn.setAttribute( 'aria-label', label );
	btn.innerHTML = `<svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="${ path }"/></svg>`;
	return btn;
}

function openLightbox( bg: string ): void {
	closeLightbox();

	const overlay = document.createElement( 'div' );
	overlay.className = 'flexa-images-gallery-lightbox';
	if ( bg ) {
		overlay.style.setProperty( '--flexa-lightbox-bg', bg );
	}

	const close = document.createElement( 'button' );
	close.type = 'button';
	close.className = 'flexa-images-gallery-lightbox__close';
	close.setAttribute( 'aria-label', 'Close' );
	close.textContent = '×';

	const prev = arrowButton( 'flexa-images-gallery-lightbox__nav flexa-images-gallery-lightbox__nav--prev', 'Previous', 'M15 18l-6-6 6-6' );
	const next = arrowButton( 'flexa-images-gallery-lightbox__nav flexa-images-gallery-lightbox__nav--next', 'Next', 'M9 6l6 6-6 6' );

	const figure = document.createElement( 'figure' );
	figure.className = 'flexa-images-gallery-lightbox__figure';

	const img = document.createElement( 'img' );
	img.className = 'flexa-images-gallery-lightbox__img';
	figure.appendChild( img );

	const cap = document.createElement( 'figcaption' );
	cap.className = 'flexa-images-gallery-lightbox__caption';
	figure.appendChild( cap );

	overlay.appendChild( close );
	if ( slides.length > 1 ) {
		overlay.appendChild( prev );
		overlay.appendChild( next );
	}
	overlay.appendChild( figure );

	prev.addEventListener( 'click', ( e ) => {
		e.stopPropagation();
		step( -1 );
	} );
	next.addEventListener( 'click', ( e ) => {
		e.stopPropagation();
		step( 1 );
	} );

	// Click on the backdrop (not the figure / controls) closes.
	overlay.addEventListener( 'click', ( e ) => {
		if ( e.target === overlay || e.target === close ) {
			closeLightbox();
		}
	} );

	document.body.appendChild( overlay );
	activeLightbox = overlay;
	document.addEventListener( 'keydown', onKeydown );
	renderSlide();
}

function initClicks(): void {
	const galleries = document.querySelectorAll< HTMLElement >( '.flexa-images-gallery[data-click]' );
	galleries.forEach( ( gallery ) => {
		const action = gallery.dataset.click;
		const items = Array.from( gallery.querySelectorAll< HTMLElement >( '.flexa-images-gallery__item' ) );

		items.forEach( ( item, index ) => {
			const media = item.querySelector< HTMLElement >( '.flexa-images-gallery__media' );
			if ( ! media ) {
				return;
			}

			if ( action === 'lightbox' ) {
				media.style.cursor = 'zoom-in';
				media.addEventListener( 'click', () => {
					slides = items
						.map( ( el ) => {
							const elImg = el.querySelector< HTMLImageElement >( '.flexa-images-gallery__image' );
							return { src: el.dataset.full || elImg?.currentSrc || elImg?.src || '', caption: el.dataset.caption || '' };
						} )
						.filter( ( s ) => s.src );
					current = index;
					showCaption = gallery.dataset.lightboxNoCaption !== '1';
					const bg = isDarkMode() && gallery.dataset.lightboxBgDark ? gallery.dataset.lightboxBgDark : gallery.dataset.lightboxBg || '';
					openLightbox( bg );
				} );
			} else if ( action === 'media' ) {
				media.style.cursor = 'pointer';
				media.addEventListener( 'click', () => {
					const href = item.dataset.full;
					if ( href ) {
						// Open the file in a new tab so the gallery page stays put and
						// the visitor can simply close the tab to return.
						window.open( href, '_blank', 'noopener' );
					}
				} );
			}
		} );
	} );
}

function init(): void {
	initClicks();
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
