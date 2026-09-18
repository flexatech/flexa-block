/**
 * Images Gallery block — front-end view script.
 *
 * Handles the click action for each gallery: open the shared lightbox with
 * previous / next navigation across the gallery's images, or jump to the media
 * file. Link-less galleries (clickAction "none") need no script.
 *
 * The overlay itself lives in `@shared/lightbox`, which this block and the Image
 * and Product Image blocks all drive; the class prefix passed below is the one
 * this block's stylesheet already targeted, so nothing here changed visually.
 */

export {}; // Treat as a module so top-level names don't collide with other view scripts.

import { openLightbox, isDarkMode } from '@shared/lightbox';

const PREFIX = 'flexa-images-gallery-lightbox';

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
					const slides = items
						.map( ( el ) => {
							const elImg = el.querySelector< HTMLImageElement >( '.flexa-images-gallery__image' );
							return { src: el.dataset.full || elImg?.currentSrc || elImg?.src || '', caption: el.dataset.caption || '' };
						} )
						.filter( ( s ) => s.src );
					const bg = isDarkMode() && gallery.dataset.lightboxBgDark ? gallery.dataset.lightboxBgDark : gallery.dataset.lightboxBg || '';
					openLightbox( {
						prefix: PREFIX,
						slides,
						index,
						background: bg,
						showCaption: gallery.dataset.lightboxNoCaption !== '1',
					} );
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
