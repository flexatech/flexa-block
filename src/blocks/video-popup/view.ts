/**
 * Video Popup front-end view script.
 *
 * Each block renders its trigger (thumbnail / button / text) plus a hidden
 * lightbox container, carrying the provider in `data-video-type`, the embed base
 * in `data-video-src` and `data-autoplay` (0|1). Clicking the trigger reveals the
 * lightbox and injects the iframe (YouTube / Vimeo) or a `<video>` (MP4) into the
 * player — building the autoplay URL only at open time so nothing plays inline.
 * Closing (X button, backdrop click or Escape) empties the player, which removes
 * the iframe/video and stops playback. Vanilla, dependency-free.
 */

export {}; // Module scope so top-level names don't collide with other view scripts.

const VP_ROOT_SELECTOR = '.flexa-video-popup';

/** The lightbox currently open on the page (only one at a time). */
let vpActive: HTMLElement | null = null;
let vpActivePlayer: HTMLElement | null = null;

/** Build the embed URL with an autoplay flag appended when requested. */
function vpWithAutoplay( type: string, src: string, autoplay: boolean ): string {
	if ( ! autoplay ) {
		return src;
	}
	const sep = src.indexOf( '?' ) === -1 ? '?' : '&';
	// Muted autoplay is the most reliable across providers on open.
	if ( type === 'vimeo' ) {
		return `${ src }${ sep }autoplay=1&muted=1`;
	}
	return `${ src }${ sep }autoplay=1`;
}

/** Create the iframe / video node for the player. */
function vpBuildPlayer( type: string, src: string, autoplay: boolean ): HTMLElement {
	if ( type === 'mp4' ) {
		const video = document.createElement( 'video' );
		video.className = 'flexa-video-popup__video';
		video.setAttribute( 'controls', '' );
		video.setAttribute( 'playsinline', '' );
		if ( autoplay ) {
			video.setAttribute( 'autoplay', '' );
		}
		video.src = src;
		return video;
	}

	const iframe = document.createElement( 'iframe' );
	iframe.className = 'flexa-video-popup__iframe';
	iframe.src = vpWithAutoplay( type, src, autoplay );
	iframe.setAttribute( 'allow', 'autoplay; fullscreen; picture-in-picture; encrypted-media' );
	iframe.setAttribute( 'allowfullscreen', '' );
	iframe.setAttribute( 'frameborder', '0' );
	iframe.setAttribute( 'title', 'Video' );
	return iframe;
}

/** Close whichever lightbox is open and stop its video by removing the node. */
function vpClose(): void {
	if ( ! vpActive ) {
		return;
	}
	if ( vpActivePlayer ) {
		vpActivePlayer.textContent = ''; // Removing the iframe/video halts playback.
	}
	vpActive.setAttribute( 'hidden', '' );
	vpActive.classList.remove( 'is-open' );
	vpActive = null;
	vpActivePlayer = null;
	document.removeEventListener( 'keydown', vpOnKeydown );
}

function vpOnKeydown( e: KeyboardEvent ): void {
	if ( e.key === 'Escape' ) {
		vpClose();
	}
}

/**
 * Swap the auto YouTube thumbnail to its fallback when maxresdefault is missing.
 * YouTube returns a 120×90 grey image (HTTP 200, so `error` never fires) for
 * videos without a maxres thumbnail — detect that by naturalWidth and drop to
 * mqdefault (both fallback and maxres are true 16:9, so no letterbox bars show).
 */
function vpThumbFallback( img: HTMLImageElement ): void {
	const fb = img.getAttribute( 'data-vp-fallback' );
	if ( ! fb ) {
		return;
	}
	const check = (): void => {
		if ( img.naturalWidth > 0 && img.naturalWidth <= 120 ) {
			img.removeAttribute( 'data-vp-fallback' ); // Guard against a swap loop.
			img.src = fb;
		}
	};
	if ( img.complete ) {
		check();
	} else {
		img.addEventListener( 'load', check, { once: true } );
	}
}

/** Wire one video-popup block. */
function vpInit( root: HTMLElement ): void {
	const trigger = root.querySelector< HTMLElement >( '.flexa-video-popup__trigger' );
	if ( ! trigger ) {
		return;
	}

	const thumb = root.querySelector< HTMLImageElement >( '.flexa-video-popup__image[data-vp-fallback]' );
	if ( thumb ) {
		vpThumbFallback( thumb );
	}

	const type = root.getAttribute( 'data-video-type' ) || 'youtube';
	const src = root.getAttribute( 'data-video-src' ) || '';
	const autoplay = root.getAttribute( 'data-autoplay' ) !== '0';

	// Inline mode: swap the thumbnail façade for the player in place (no lightbox).
	// The replacement keeps the __trigger + __media classes so the instance CSS
	// (max width, border, aspect ratio) still applies, and being a <div> — not a
	// <button> — the injected iframe/video is fully interactive.
	if ( root.getAttribute( 'data-display-mode' ) === 'inline' ) {
		trigger.addEventListener(
			'click',
			( e ) => {
				e.preventDefault();
				if ( ! src ) {
					return;
				}
				const frame = document.createElement( 'div' );
				frame.className = 'flexa-video-popup__trigger flexa-video-popup__media is-playing';
				frame.appendChild( vpBuildPlayer( type, src, autoplay ) );
				trigger.replaceWith( frame );
			},
			{ once: true }
		);
		return;
	}

	// Popup mode: reveal the lightbox and inject the player on demand.
	const lightbox = root.querySelector< HTMLElement >( '.flexa-video-popup__lightbox' );
	if ( ! lightbox ) {
		return;
	}

	const player = lightbox.querySelector< HTMLElement >( '.flexa-video-popup__player' );
	const backdrop = lightbox.querySelector< HTMLElement >( '.flexa-video-popup__backdrop' );
	const closeBtn = lightbox.querySelector< HTMLElement >( '.flexa-video-popup__close' );
	if ( ! player ) {
		return;
	}

	const open = (): void => {
		if ( ! src ) {
			return;
		}
		vpClose(); // Close any other open popup first.
		player.textContent = '';
		player.appendChild( vpBuildPlayer( type, src, autoplay ) );
		lightbox.removeAttribute( 'hidden' );
		lightbox.classList.add( 'is-open' );
		vpActive = lightbox;
		vpActivePlayer = player;
		document.addEventListener( 'keydown', vpOnKeydown );
	};

	trigger.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		open();
	} );

	if ( backdrop ) {
		backdrop.addEventListener( 'click', vpClose );
	}
	if ( closeBtn ) {
		closeBtn.addEventListener( 'click', vpClose );
	}
}

function vpBoot(): void {
	document.querySelectorAll< HTMLElement >( VP_ROOT_SELECTOR ).forEach( vpInit );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', vpBoot );
} else {
	vpBoot();
}
