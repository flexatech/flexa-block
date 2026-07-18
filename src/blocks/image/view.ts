/**
 * Image block — front-end view script.
 *
 * Two responsibilities:
 *   1. Lazy-load background images (shared `data-flexa-lazy-bg` convention).
 *   2. Handle the click action: open a self-contained lightbox, or navigate to
 *      the media file. Link clicks are plain <a> elements and need no script.
 */

export {}; // Treat as a module so top-level names don't collide with other view scripts.

/* -------------------------------------------------------------------------
 * Lazy background images (same pattern as Container / Heading).
 * ---------------------------------------------------------------------- */

const LOADED_CLASS = 'flexa-bg-loaded';
const LAZY_SELECTOR = '[data-flexa-lazy-bg]';

function revealBg( el: Element ): void {
	el.classList.add( LOADED_CLASS );
	el.removeAttribute( 'data-flexa-lazy-bg' );
}

function initLazyBackgrounds(): void {
	const targets = document.querySelectorAll( LAZY_SELECTOR );
	if ( ! targets.length ) {
		return;
	}
	if ( typeof window.IntersectionObserver === 'undefined' ) {
		targets.forEach( revealBg );
		return;
	}
	const observer = new window.IntersectionObserver(
		( entries, obs ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					revealBg( entry.target );
					obs.unobserve( entry.target );
				}
			} );
		},
		{ rootMargin: '200px 0px' }
	);
	targets.forEach( ( el ) => observer.observe( el ) );
}

/* -------------------------------------------------------------------------
 * Lightbox.
 * ---------------------------------------------------------------------- */

let activeLightbox: HTMLElement | null = null;

/**
 * Whether the page is currently in dark mode, covering both strategies the
 * plugin supports: an explicit `[data-theme="dark"]` on <html>/<body>, or the
 * OS `prefers-color-scheme: dark`. The lightbox lives on <body>, outside the
 * block, so its dark colour can't come from the per-instance CSS — resolve here.
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

function onKeydown( e: KeyboardEvent ): void {
	if ( e.key === 'Escape' ) {
		closeLightbox();
	}
}

function openLightbox( src: string, caption: string, bg: string ): void {
	closeLightbox();

	const overlay = document.createElement( 'div' );
	overlay.className = 'flexa-image-lightbox';
	if ( bg ) {
		overlay.style.setProperty( '--flexa-lightbox-bg', bg );
	}

	const close = document.createElement( 'button' );
	close.type = 'button';
	close.className = 'flexa-image-lightbox__close';
	close.setAttribute( 'aria-label', 'Close' );
	close.textContent = '×';

	const figure = document.createElement( 'figure' );
	figure.className = 'flexa-image-lightbox__figure';

	const img = document.createElement( 'img' );
	img.className = 'flexa-image-lightbox__img';
	img.src = src;
	img.alt = caption || '';
	figure.appendChild( img );

	if ( caption ) {
		const cap = document.createElement( 'figcaption' );
		cap.className = 'flexa-image-lightbox__caption';
		cap.textContent = caption;
		figure.appendChild( cap );
	}

	overlay.appendChild( close );
	overlay.appendChild( figure );

	// Click on the backdrop (not the figure) closes.
	overlay.addEventListener( 'click', ( e ) => {
		if ( e.target === overlay || e.target === close ) {
			closeLightbox();
		}
	} );

	document.body.appendChild( overlay );
	activeLightbox = overlay;
	document.addEventListener( 'keydown', onKeydown );
}

function initClicks(): void {
	const blocks = document.querySelectorAll< HTMLElement >( '.flexa-image[data-click]' );
	blocks.forEach( ( block ) => {
		const action = block.dataset.click;
		const frame = block.querySelector< HTMLElement >( '.flexa-image__frame' );
		if ( ! frame ) {
			return;
		}

		if ( action === 'lightbox' ) {
			frame.style.cursor = 'zoom-in';
			frame.addEventListener( 'click', () => {
				const img = frame.querySelector< HTMLImageElement >( '.flexa-image__img' );
				const src = block.dataset.full || img?.currentSrc || img?.src || '';
				if ( ! src ) {
					return;
				}
				const caption = block.dataset.lightboxCaption || '';
				const bg = isDarkMode() && block.dataset.lightboxBgDark ? block.dataset.lightboxBgDark : block.dataset.lightboxBg || '';
				openLightbox( src, caption, bg );
			} );
		} else if ( action === 'media' ) {
			frame.style.cursor = 'pointer';
			frame.addEventListener( 'click', () => {
				const href = block.dataset.full;
				if ( href ) {
					window.location.href = href;
				}
			} );
		}
	} );
}

function init(): void {
	initLazyBackgrounds();
	initClicks();
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
