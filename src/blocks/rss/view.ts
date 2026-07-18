/**
 * RSS block front-end view script — client-side pagination.
 *
 * The whole feed pool is rendered server-side (render.php) and cached, so paging
 * is a pure show/hide over the already-present cards, handled by the shared
 * `initListPagination` controller (also used by the Facebook Feed block). With
 * JavaScript off every item stays visible, so nothing ever breaks.
 */

import { initListPagination } from '@shared/list-pagination';

const CONFIG = {
	wrapAttr: 'data-flexa-rss-pagination',
	grid: '.flexa-rss__grid',
	item: '.flexa-rss__item',
};

function init(): void {
	initListPagination( CONFIG );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
