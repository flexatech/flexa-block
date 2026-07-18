/**
 * Slider front-end view script.
 *
 * Boots a Swiper instance for every `.flexa-slides` on the page from the config
 * carried in its `data-flexa-slider` attribute (written by render.php). All
 * carousel behaviour (autoplay, loop, effect, per-device slides-per-view, arrow
 * and dot navigation) lives here; the editor renders the slides stacked.
 */

export {}; // Treat as a module so top-level names don't collide with other view scripts.

import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay, EffectFade, EffectCube, EffectCoverflow, EffectFlip, EffectCreative } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';
import 'swiper/css/effect-fade';
import 'swiper/css/effect-cube';
import 'swiper/css/effect-coverflow';
import 'swiper/css/effect-flip';
import 'swiper/css/effect-creative';

interface SliderConfig {
	autoplay: boolean;
	autoplayDelay: number;
	pauseOnHover: boolean;
	pauseOnInteraction: boolean;
	loop: boolean;
	speed: number;
	effect: string;
	spaceBetween: number;
	slidesPerView: { desktop: number; tablet: number; mobile: number };
	navigation: boolean;
	pagination: boolean;
	paginationType: 'bullets' | 'fraction' | 'progressbar';
}

/** Parse the JSON config off a slider root, tolerating malformed data. */
function readConfig( root: Element ): SliderConfig | null {
	const raw = root.getAttribute( 'data-flexa-slider' );
	if ( ! raw ) {
		return null;
	}
	try {
		return JSON.parse( raw ) as SliderConfig;
	} catch ( e ) {
		return null;
	}
}

/** Build the Swiper options object from one slider's config. */
function buildOptions( cfg: SliderConfig, root: Element ): Record< string, any > {
	const modules: any[] = [];
	if ( cfg.navigation ) {
		modules.push( Navigation );
	}
	if ( cfg.pagination ) {
		modules.push( Pagination );
	}
	if ( cfg.autoplay ) {
		modules.push( Autoplay );
	}

	const effectModule: Record< string, any > = {
		fade: EffectFade,
		cube: EffectCube,
		coverflow: EffectCoverflow,
		flip: EffectFlip,
		creative: EffectCreative,
	};
	if ( effectModule[ cfg.effect ] ) {
		modules.push( effectModule[ cfg.effect ] );
	}

	const options: Record< string, any > = {
		modules,
		effect: cfg.effect || 'slide',
		speed: cfg.speed,
		loop: cfg.loop,
		spaceBetween: cfg.spaceBetween,
		slidesPerView: cfg.slidesPerView.mobile,
		breakpoints: {
			768: { slidesPerView: cfg.slidesPerView.tablet },
			1024: { slidesPerView: cfg.slidesPerView.desktop },
		},
	};

	if ( cfg.autoplay ) {
		options.autoplay = {
			delay: cfg.autoplayDelay,
			pauseOnMouseEnter: cfg.pauseOnHover,
			disableOnInteraction: cfg.pauseOnInteraction,
		};
	}
	if ( cfg.navigation ) {
		options.navigation = {
			nextEl: root.querySelector( '.swiper-button-next' ),
			prevEl: root.querySelector( '.swiper-button-prev' ),
		};
	}
	if ( cfg.pagination ) {
		options.pagination = {
			el: root.querySelector( '.swiper-pagination' ),
			type: cfg.paginationType,
			clickable: true,
		};
	}

	return options;
}

/** Boot Swiper for every slider on the page. */
function init(): void {
	const roots = document.querySelectorAll( '.flexa-slides' );
	roots.forEach( ( root ) => {
		const el = root.querySelector( '.swiper' );
		if ( ! el || ( el as HTMLElement ).dataset.flexaInit ) {
			return;
		}
		const cfg = readConfig( root );
		if ( ! cfg ) {
			return;
		}
		( el as HTMLElement ).dataset.flexaInit = '1';
		// eslint-disable-next-line no-new
		new Swiper( el as HTMLElement, buildOptions( cfg, root ) );
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
