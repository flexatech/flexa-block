export {}; // Treat as a module so top-level names don't collide with other view scripts.

/**
 * Lottie Animation front-end view script.
 *
 * Boots a lottie-web instance for every `.flexa-lottie__player` on the page from
 * the config carried in its data-* attributes (written by render.php). The
 * animation data is resolved from the source URL by the shared loader (loadLottieData),
 * which handles both a plain `.json` file and a `.lottie` (dotLottie ZIP) bundle.
 * All playback behaviour (loop, speed, reverse direction and the play trigger:
 * autoplay / hover / click / scroll / viewport) lives here.
 */

import lottie from 'lottie-web';
import { loadLottieData } from './load';

interface PlayerConfig {
	src: string;
	loop: boolean;
	speed: number;
	reverse: boolean;
	playOn: string;
}

/** Read the animation config off a player box, guarding against a missing src. */
function readConfig( el: HTMLElement ): PlayerConfig | null {
	const src = el.getAttribute( 'data-src' ) || '';
	if ( ! src ) {
		return null;
	}
	const speed = parseFloat( el.getAttribute( 'data-speed' ) || '1' );
	return {
		src,
		loop: el.getAttribute( 'data-loop' ) !== '0',
		speed: Number.isFinite( speed ) && speed > 0 ? speed : 1,
		reverse: el.getAttribute( 'data-reverse' ) === '1',
		playOn: el.getAttribute( 'data-play-on' ) || 'autoplay',
	};
}

/** Load one animation and wire up its play trigger. */
async function setup( el: HTMLElement ): Promise< void > {
	if ( el.dataset.flexaInit ) {
		return;
	}
	const cfg = readConfig( el );
	if ( ! cfg ) {
		return;
	}
	el.dataset.flexaInit = '1';

	let animationData: Record< string, unknown >;
	try {
		animationData = await loadLottieData( cfg.src );
	} catch {
		// Unreadable / missing source — leave the box empty rather than throw.
		return;
	}

	const anim = lottie.loadAnimation( {
		container: el,
		renderer: 'svg',
		loop: cfg.loop,
		autoplay: cfg.playOn === 'autoplay',
		animationData,
	} );

	// Speed / direction can only be applied once the animation data is ready.
	anim.addEventListener( 'DOMLoaded', () => {
		anim.setSpeed( cfg.speed );
		anim.setDirection( cfg.reverse ? -1 : 1 );
	} );

	switch ( cfg.playOn ) {
		case 'hover':
			el.addEventListener( 'mouseenter', () => anim.play() );
			el.addEventListener( 'mouseleave', () => anim.pause() );
			break;

		case 'click': {
			let playing = false;
			el.addEventListener( 'click', () => {
				playing = ! playing;
				if ( playing ) {
					anim.play();
				} else {
					anim.pause();
				}
			} );
			break;
		}

		case 'scroll':
		case 'viewport': {
			const observer = new IntersectionObserver(
				( entries ) => {
					entries.forEach( ( entry ) => {
						if ( entry.isIntersecting ) {
							anim.play();
							// `viewport` plays once, `scroll` replays each time it re-enters.
							if ( cfg.playOn === 'viewport' ) {
								observer.disconnect();
							}
						} else if ( cfg.playOn === 'scroll' ) {
							anim.pause();
						}
					} );
				},
				{ threshold: 0.25 }
			);
			observer.observe( el );
			break;
		}

		default:
			// `autoplay` is handled by the autoplay flag above; `none` stays paused.
			break;
	}
}

/** Boot every Lottie player on the page. */
function init(): void {
	document.querySelectorAll< HTMLElement >( '.flexa-lottie__player' ).forEach( setup );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
