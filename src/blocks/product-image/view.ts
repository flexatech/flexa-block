/**
 * Product Image block — front-end view script.
 *
 * For each product-image block: clicking a thumbnail swaps the featured image to
 * the thumbnail's full-size source and moves the active state to it; the prev /
 * next arrows scroll the thumbnail carousel; and when autoplay is enabled the
 * featured image advances through the thumbnails on an interval that pauses while
 * the pointer is over the gallery. Blocks without a thumbnail strip need no
 * interaction — the loop simply skips them.
 */

export {}; // Treat as a module so top-level names don't collide with other view scripts.

function initProductImage( root: HTMLElement ): void {
	const mainImg = root.querySelector< HTMLImageElement >( '.flexa-product-image__main img' );
	const viewport = root.querySelector< HTMLElement >( '.flexa-product-image__thumbs' );
	const thumbs = Array.from( root.querySelectorAll< HTMLElement >( '.flexa-product-image__thumb' ) );
	if ( ! mainImg || thumbs.length === 0 ) {
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
