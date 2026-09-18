/**
 * Product Image block — front-end view script.
 *
 * For each product-image block: clicking a thumbnail swaps the featured image to
 * the thumbnail's full-size source and moves the active state to it; the prev /
 * next arrows scroll the thumbnail carousel; and when autoplay is enabled the
 * featured image advances through the thumbnails on an interval that pauses while
 * the pointer is over the gallery. Blocks without a thumbnail strip need no
 * interaction — the loop simply skips them.
 *
 * On a variable product the gallery also follows the shopper's choice: picking
 * a set of options swaps the featured image to that variation's own, and
 * clearing the choice puts the original back. WooCommerce announces both
 * through jQuery custom events on its variations form, which is a sibling block
 * on the page rather than part of this one — `found_variation` / `reset_data`
 * cannot be heard with addEventListener, so jQuery is used when it is present
 * and the gallery simply stays put when it is not.
 *
 * With the lightbox on, clicking the featured image opens the full-screen
 * overlay from `@shared/lightbox` — the same one Image and Images Gallery use —
 * carrying every gallery image so the shopper can page through them.
 */

import { openLightbox, isDarkMode } from '@shared/lightbox';

const LIGHTBOX_PREFIX = 'flexa-product-image-lightbox';

export {}; // Treat as a module so top-level names don't collide with other view scripts.

/** The slice of a WooCommerce variation this block cares about. */
interface VariationImage {
	src?: string;
	srcset?: string;
	sizes?: string;
	alt?: string;
	title?: string;
	full_src?: string;
}

interface Variation {
	image?: VariationImage;
}

/** Minimal shape of the jQuery WooCommerce ships; absent on a static page. */
type JQueryLike = ( ( selector: Element ) => { on: ( event: string, handler: ( ...args: never[] ) => void ) => void } ) | undefined;

/**
 * Follow the shopper's variation choice on a variable product.
 *
 * WooCommerce fires `found_variation` on its variations form once every
 * attribute has been picked, and `reset_data` when the choice is cleared. Both
 * are jQuery custom events — `.trigger()` never reaches a native listener — so
 * this binds through jQuery when WooCommerce has put it on the page, and does
 * nothing at all when it has not.
 *
 * The form belongs to the Add to Cart block, a sibling on the page, so it is
 * looked up document-wide rather than inside this block.
 *
 * @param root    The block wrapper.
 * @param mainImg The featured image.
 * @param thumbs  The thumbnail strip, possibly empty.
 */
function bindVariations( root: HTMLElement, mainImg: HTMLImageElement, thumbs: HTMLElement[] ): void {
	const form = document.querySelector< HTMLElement >( 'form.variations_form' );
	const jq = ( window as unknown as { jQuery?: JQueryLike } ).jQuery;
	if ( ! form || ! jq ) {
		return;
	}

	// What to put back when the shopper clears their choice.
	const original = {
		src: mainImg.getAttribute( 'src' ) || '',
		srcset: mainImg.getAttribute( 'srcset' ) || '',
		sizes: mainImg.getAttribute( 'sizes' ) || '',
		alt: mainImg.getAttribute( 'alt' ) || '',
	};
	const originalActive = thumbs.find( ( el ) => el.classList.contains( 'is-active' ) ) || thumbs[ 0 ];

	/** Set or clear one attribute, so a variation without srcset drops the old one. */
	const setAttr = ( name: string, value: string ): void => {
		if ( value ) {
			mainImg.setAttribute( name, value );
		} else {
			mainImg.removeAttribute( name );
		}
	};

	jq( form ).on( 'found_variation', ( ...args: never[] ) => {
		const variation = args[ 1 ] as unknown as Variation | undefined;
		const image = variation?.image;
		if ( ! image?.src ) {
			return;
		}

		setAttr( 'src', image.src );
		setAttr( 'srcset', image.srcset || '' );
		setAttr( 'sizes', image.sizes || '' );
		setAttr( 'alt', image.alt || original.alt );

		// A variation image is often one of the gallery images; when it is, move
		// the active state onto it. When it is not, no thumbnail is active —
		// better than leaving a highlight on an image that is no longer shown.
		const match = thumbs.find( ( el ) => el.dataset.full === image.src || el.dataset.full === image.full_src );
		thumbs.forEach( ( el ) => el.classList.toggle( 'is-active', el === match ) );

		// Autoplay would cycle straight off the chosen variation.
		root.dataset.flexaVariation = '1';
	} );

	jq( form ).on( 'reset_data', () => {
		setAttr( 'src', original.src );
		setAttr( 'srcset', original.srcset );
		setAttr( 'sizes', original.sizes );
		setAttr( 'alt', original.alt );

		thumbs.forEach( ( el ) => el.classList.toggle( 'is-active', el === originalActive ) );
		delete root.dataset.flexaVariation;
	} );
}

/**
 * Wire the featured image to the full-screen overlay.
 *
 * The slides are the gallery's own images at their full size (`data-large`,
 * printed by render.php), opened at whichever thumbnail is active. A variation
 * image that is not part of the gallery has no thumbnail, so it is prepended as
 * its own slide — otherwise clicking it would open a different picture.
 *
 * @param root    The block wrapper.
 * @param mainImg The featured image.
 * @param thumbs  The thumbnail strip, possibly empty.
 */
function bindLightbox( root: HTMLElement, mainImg: HTMLImageElement, thumbs: HTMLElement[] ): void {
	if ( root.dataset.flexaLightbox !== '1' ) {
		return;
	}

	const frame = mainImg.closest< HTMLElement >( '.flexa-product-image__main' ) || mainImg;
	frame.style.cursor = 'zoom-in';

	frame.addEventListener( 'click', () => {
		const shown = mainImg.currentSrc || mainImg.src;
		const slides = thumbs
			.map( ( thumb ) => ( { src: thumb.dataset.large || thumb.dataset.full || '', full: thumb.dataset.full || '' } ) )
			.filter( ( slide ) => slide.src );

		let index = thumbs.findIndex( ( thumb ) => thumb.classList.contains( 'is-active' ) );

		// No active thumbnail means the featured image is not one of them — a
		// variation image outside the gallery, or a gallery of one.
		if ( index < 0 ) {
			const large = frame.dataset.large || '';
			slides.unshift( { src: large || shown, full: shown } );
			index = 0;
		}

		if ( ! slides.length ) {
			return;
		}

		openLightbox( {
			prefix: LIGHTBOX_PREFIX,
			slides: slides.map( ( slide ) => ( { src: slide.src, caption: mainImg.alt || '' } ) ),
			index,
			background: isDarkMode() ? 'rgba(0, 0, 0, 0.94)' : '',
			// A product gallery repeats the product name on every slide, which
			// reads as noise under the picture.
			showCaption: false,
			labels: {
				close: root.dataset.flexaLabelClose,
				previous: root.dataset.flexaLabelPrev,
				next: root.dataset.flexaLabelNext,
			},
		} );
	} );
}

function initProductImage( root: HTMLElement ): void {
	const mainImg = root.querySelector< HTMLImageElement >( '.flexa-product-image__main img' );
	const viewport = root.querySelector< HTMLElement >( '.flexa-product-image__thumbs' );
	const thumbs = Array.from( root.querySelectorAll< HTMLElement >( '.flexa-product-image__thumb' ) );

	// A single-image gallery has no thumbnails to click, but it still has to
	// follow the variation the shopper picks — so only a missing image bails.
	if ( ! mainImg ) {
		return;
	}

	// Left/right positions stack thumbnails vertically → scroll the Y axis.
	const vertical = root.classList.contains( 'flexa-product-image--pos-left' ) || root.classList.contains( 'flexa-product-image--pos-right' );

	// Bring a thumbnail into view by scrolling ONLY the carousel viewport — never
	// via scrollIntoView(), which also scrolls the window (the page would jump).
	const scrollThumbIntoView = ( thumb: HTMLElement ): void => {
		if ( ! viewport ) {
			return;
		}
		const vr = viewport.getBoundingClientRect();
		const tr = thumb.getBoundingClientRect();
		if ( vertical ) {
			if ( tr.top < vr.top ) {
				viewport.scrollTop -= vr.top - tr.top + 8;
			} else if ( tr.bottom > vr.bottom ) {
				viewport.scrollTop += tr.bottom - vr.bottom + 8;
			}
		} else if ( tr.left < vr.left ) {
			viewport.scrollLeft -= vr.left - tr.left + 8;
		} else if ( tr.right > vr.right ) {
			viewport.scrollLeft += tr.right - vr.right + 8;
		}
	};

	const activate = ( thumb: HTMLElement ): void => {
		const full = thumb.dataset.full;
		if ( full ) {
			mainImg.src = full;
			mainImg.removeAttribute( 'srcset' );
		}
		thumbs.forEach( ( el ) => el.classList.toggle( 'is-active', el === thumb ) );
		scrollThumbIntoView( thumb );
	};

	thumbs.forEach( ( thumb ) => {
		thumb.addEventListener( 'click', () => activate( thumb ) );
	} );

	bindVariations( root, mainImg, thumbs );
	bindLightbox( root, mainImg, thumbs );

	// Carousel arrows — scroll the viewport by roughly one page.
	const prev = root.querySelector< HTMLButtonElement >( '.flexa-product-image__nav--prev' );
	const next = root.querySelector< HTMLButtonElement >( '.flexa-product-image__nav--next' );

	if ( viewport && ( prev || next ) ) {
		const scrollPos = (): number => ( vertical ? viewport.scrollTop : viewport.scrollLeft );
		const maxScroll = (): number => ( vertical ? viewport.scrollHeight - viewport.clientHeight : viewport.scrollWidth - viewport.clientWidth ) - 1;
		const page = (): number => Math.max( 120, ( vertical ? viewport.clientHeight : viewport.clientWidth ) * 0.9 );

		const updateArrows = (): void => {
			if ( prev ) {
				prev.disabled = scrollPos() <= 0;
			}
			if ( next ) {
				next.disabled = scrollPos() >= maxScroll();
			}
		};

		prev?.addEventListener( 'click', () => viewport.scrollBy( vertical ? { top: -page(), behavior: 'smooth' } : { left: -page(), behavior: 'smooth' } ) );
		next?.addEventListener( 'click', () => viewport.scrollBy( vertical ? { top: page(), behavior: 'smooth' } : { left: page(), behavior: 'smooth' } ) );
		viewport.addEventListener( 'scroll', updateArrows, { passive: true } );
		window.addEventListener( 'resize', updateArrows );
		updateArrows();
	}

	// Autoplay — advance to the next thumbnail on an interval, wrapping around.
	const wantsAutoplay = root.dataset.flexaAutoplay === '1';
	const reducedMotion =
		typeof window.matchMedia === 'function' &&
		window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	if ( wantsAutoplay && ! reducedMotion && thumbs.length > 1 ) {
		const speed = Math.max( 1000, parseInt( root.dataset.flexaAutoplaySpeed || '4000', 10 ) || 4000 );
		let timer = 0;

		const step = (): void => {
			// A picked variation owns the featured image until it is cleared.
			if ( root.dataset.flexaVariation === '1' ) {
				return;
			}
			const current = thumbs.findIndex( ( el ) => el.classList.contains( 'is-active' ) );
			const nextThumb = thumbs[ ( current + 1 ) % thumbs.length ];
			if ( nextThumb ) {
				activate( nextThumb );
			}
		};
		const stop = (): void => {
			if ( timer ) {
				window.clearInterval( timer );
				timer = 0;
			}
		};
		const start = (): void => {
			stop();
			timer = window.setInterval( step, speed );
		};

		root.addEventListener( 'mouseenter', stop );
		root.addEventListener( 'mouseleave', start );
		start();
	}
}

function init(): void {
	document
		.querySelectorAll< HTMLElement >( '.flexa-product-image' )
		.forEach( ( root ) => initProductImage( root ) );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
