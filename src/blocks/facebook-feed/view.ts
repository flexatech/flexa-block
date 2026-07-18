/**
 * Facebook Feed block front-end view script — client-side pagination.
 *
 * The whole post pool is rendered server-side (render.php) and cached, so paging
 * is a pure show/hide over the already-present cards, handled by the shared
 * `initListPagination` controller (also used by the RSS block). With JavaScript
 * off every post stays visible, so nothing ever breaks.
 */

import { initListPagination } from '@shared/list-pagination';

function init(): void {
	initListPagination( {
		wrapAttr: 'data-flexa-fb-pagination',
		grid: '.flexa-facebook-feed__grid',
		item: '.flexa-facebook-feed__item',
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}

export {};
