/**
 * Pagination — shared page-window helper (dependency-light so front-end view
 * scripts can import it without pulling the React/@utils bundle).
 *
 * Produces the WP-style windowed page list (first/last, a range around the
 * current page, and `dots` gaps) used by the RSS front-end view script and the
 * shared editor pagination nav so numbered paging looks identical everywhere.
 */

export type PageToken = number | 'dots';

/**
 * The windowed list of page tokens for a numbered pager.
 *
 * @param total   Total number of pages.
 * @param current The active page (1-based).
 * @param mid     Pages to show either side of the current page.
 * @param end     Pages to always show at each end.
 * @return Ordered tokens, e.g. `[1, 'dots', 4, 5, 6, 'dots', 13]`.
 */
export function pageWindow( total: number, current: number, mid = 1, end = 1 ): PageToken[] {
	const out: PageToken[] = [];
	for ( let i = 1; i <= total; i++ ) {
		if ( i <= end || i > total - end || ( i >= current - mid && i <= current + mid ) ) {
			out.push( i );
		} else if ( out[ out.length - 1 ] !== 'dots' ) {
			out.push( 'dots' );
		}
	}
	return out;
}
