/**
 * Post Grid ↔ Post Filter contract (front end).
 *
 * The two blocks ship as separate view bundles — webpack builds one entry per
 * `view.ts` with no shared chunk — so anything stateful here (a registry of grid
 * instances, say) would be DUPLICATED per bundle and the filter would never find
 * the grid. Hence a DOM event bus: the filter bar dispatches on `document`, the
 * grid listens. No shared state, so nothing to keep in sync.
 *
 * Everything the two sides must agree on lives here: the event names and the
 * query-arg keys. The keys are also built server side (Post_Query::page_key() and
 * friends) — keep the two in step.
 */

/** Filter bar → grid: "render yourself at this URL". */
export const FILTER_EVENT = 'flexa-post-grid:filter';

/** Grid → filter bar: "I rendered; here's the result". */
export const UPDATED_EVENT = 'flexa-post-grid:updated';

export interface FilterDetail {
	/** Target grid's blockId. Empty = the first grid on the page. */
	target: string;
	/** The full front-end URL the grid should render — the single source of filter state. */
	url: string;
}

export interface UpdatedDetail {
	target: string;
	total: number;
	page: number;
	totalPages: number;
}

/** The paging query arg for one grid. */
export function pageKey( gid: string ): string {
	return gid ? `pg_${ gid }` : 'pg';
}

/** The search query arg for one grid. */
export function searchKey( gid: string ): string {
	return `s_${ gid }`;
}

/** The term query arg for one grid + taxonomy. */
export function taxKey( gid: string, taxonomy: string ): string {
	return `tx_${ gid }_${ taxonomy }`;
}

/** Ask the grid identified by `target` to render the state encoded in `url`. */
export function emitFilter( detail: FilterDetail ): void {
	document.dispatchEvent( new window.CustomEvent< FilterDetail >( FILTER_EVENT, { detail } ) );
}

/** Announce a finished render (result count, page) to whoever is listening. */
export function emitUpdated( detail: UpdatedDetail ): void {
	document.dispatchEvent( new window.CustomEvent< UpdatedDetail >( UPDATED_EVENT, { detail } ) );
}
