/**
 * Word-level "what was added" highlighting for the edit log.
 *
 * Splits both texts into words and spaces, keeps the longest run of words they
 * share, and marks what is left in the new text as added. Short texts get a
 * proper longest-common-subsequence; very long ones fall back to trimming the
 * shared start and end, which still marks the one changed stretch.
 */

export interface Segment {
	text: string;
	added: boolean;
}

const MAX_CELLS = 4_000_000;

/**
 * Split text into words and the spaces between them.
 *
 * @param text Plain text.
 */
function tokens( text: string ): string[] {
	return text.match( /\s+|\S+/g ) || [];
}

/**
 * Which tokens of `after` are not part of what it shares with `before`.
 *
 * @param before Text before the edit.
 * @param after  Text after the edit.
 * @return One segment per run of added or unchanged text, in order.
 */
export function addedSegments( before: string, after: string ): Segment[] {
	const a = tokens( before );
	const b = tokens( after );

	// Nothing to compare against: marking all of it would say nothing.
	if ( ! a.length ) {
		return b.length ? [ { text: after, added: false } ] : [];
	}

	let start = 0;
	while ( start < a.length && start < b.length && a[ start ] === b[ start ] ) {
		start++;
	}
	let endA = a.length;
	let endB = b.length;
	while ( endA > start && endB > start && a[ endA - 1 ] === b[ endB - 1 ] ) {
		endA--;
		endB--;
	}

	const midA = a.slice( start, endA );
	const midB = b.slice( start, endB );
	const kept = new Array< boolean >( midB.length ).fill( false );

	if ( midA.length && midB.length && midA.length * midB.length <= MAX_CELLS ) {
		// Longest common subsequence of the differing middle.
		const w = midB.length + 1;
		const table = new Uint32Array( ( midA.length + 1 ) * w );
		for ( let i = midA.length - 1; i >= 0; i-- ) {
			for ( let j = midB.length - 1; j >= 0; j-- ) {
				table[ i * w + j ] =
					midA[ i ] === midB[ j ]
						? table[ ( i + 1 ) * w + j + 1 ] + 1
						: Math.max( table[ ( i + 1 ) * w + j ], table[ i * w + j + 1 ] );
			}
		}
		let i = 0;
		let j = 0;
		while ( i < midA.length && j < midB.length ) {
			if ( midA[ i ] === midB[ j ] ) {
				kept[ j ] = true;
				i++;
				j++;
			} else if ( table[ ( i + 1 ) * w + j ] >= table[ i * w + j + 1 ] ) {
				i++;
			} else {
				j++;
			}
		}
	}

	const flags: boolean[] = [
		...new Array< boolean >( start ).fill( false ),
		...kept.map( ( k ) => ! k ),
		...new Array< boolean >( b.length - endB ).fill( false ),
	];

	// A space between two added words belongs to the added run; a space on its
	// own at the edge of one does not need emphasis.
	const out: Segment[] = [];
	b.forEach( ( token, idx ) => {
		let added = flags[ idx ];
		if ( added && /^\s+$/.test( token ) ) {
			added = Boolean( flags[ idx - 1 ] && flags[ idx + 1 ] );
		}
		const last = out[ out.length - 1 ];
		if ( last && last.added === added ) {
			last.text += token;
		} else {
			out.push( { text: token, added } );
		}
	} );
	return out;
}

const MARK_STYLE = {
	background: '#a7e3b4',
	color: '#06391a',
	fontWeight: 800,
	padding: '1px 3px',
	margin: '0 1px',
	borderRadius: '3px',
	boxDecorationBreak: 'clone',
	WebkitBoxDecorationBreak: 'clone',
} as const;

const DEL_STYLE = {
	background: '#f5b9bd',
	color: '#6b0f16',
	fontWeight: 700,
	padding: '1px 3px',
	margin: '0 1px',
	borderRadius: '3px',
	textDecoration: 'line-through',
	textDecorationThickness: '1.5px',
} as const;

const HIT_STYLE = {
	background: '#ffe14d',
	color: '#1e1e1e',
	borderRadius: '2px',
	boxShadow: '0 0 0 1px #e6c200',
} as const;

/**
 * Text with every occurrence of a search term marked yellow.
 *
 * @param props       Component props.
 * @param props.text  Text to draw.
 * @param props.query Search term; empty draws the text unchanged.
 */
export function SearchHits( {
	text,
	query,
}: {
	text: string;
	query: string;
} ): JSX.Element {
	const needle = query.trim();
	if ( ! needle ) {
		return <>{ text }</>;
	}
	const parts = text.split(
		new RegExp( `(${ needle.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' ) })`, 'gi' )
	);
	return (
		<>
			{ parts.map( ( part, i ) =>
				part.toLowerCase() === needle.toLowerCase() ? (
					<span key={ i } style={ HIT_STYLE }>
						{ part }
					</span>
				) : (
					part
				)
			) }
		</>
	);
}

/**
 * The text of one side of an edit with the changed words made obvious.
 *
 * After: words that are new, bold on green. Before: words that were taken out,
 * struck through on red. The rest is left plain, so the eye goes to the change.
 *
 * @param props        Component props.
 * @param props.before Text before the edit.
 * @param props.after  Text after the edit.
 * @param props.side   Which side to draw.
 * @param props.query  Text being searched for; where it appears it is marked yellow.
 */
export function Highlight( {
	before,
	after,
	side,
	query = '',
}: {
	before: string;
	after: string;
	side: 'before' | 'after';
	query?: string;
} ): JSX.Element {
	const segments =
		side === 'after'
			? addedSegments( before, after )
			: addedSegments( after, before );

	return (
		<>
			{ segments.map( ( seg, i ) => {
				const text = <SearchHits text={ seg.text } query={ query } />;
				if ( ! seg.added ) {
					return <span key={ i }>{ text }</span>;
				}
				return side === 'after' ? (
					<mark key={ i } style={ MARK_STYLE }>
						{ text }
					</mark>
				) : (
					<del key={ i } style={ DEL_STYLE }>
						{ text }
					</del>
				);
			} ) }
		</>
	);
}
