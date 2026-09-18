/**
 * Image block — front-end view script.
 *
 * Two responsibilities:
 *   1. Lazy-load background images (shared `data-flexa-lazy-bg` convention).
 *   2. Handle the click action: open the shared lightbox, or navigate to the
 *      media file. Link clicks are plain <a> elements and need no script.
 *
 * The overlay lives in `@shared/lightbox`, which this block and the Images
 * Gallery and Product Image blocks all drive; the class prefix passed below is
 * the one this block's stylesheet already targeted, so nothing here changed
 * visually.
 */

export {}; // Treat as a module so top-level names don't collide with other view scripts.

import { openLightbox, isDarkMode } from '@shared/lightbox';

const PREFIX = 'flexa-image-lightbox';

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
				const bg = isDarkMode() && block.dataset.lightboxBgDark ? block.dataset.lightboxBgDark : block.dataset.lightboxBg || '';
				openLightbox( {
					prefix: PREFIX,
					slides: [ { src, caption: block.dataset.lightboxCaption || '' } ],
					background: bg,
				} );
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
