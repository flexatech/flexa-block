/**
 * Log view: what was edited from the front end, site-wide.
 *
 * A quick look for an administrator: who changed what, where, and when, with
 * the text before and after. Entries are removed after the retention period
 * (30 days by default), which the header states. The block editor has its own
 * per-page panel; this one spans every page and pattern.
 */

import { useCallback, useEffect, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, Dropdown, MenuGroup, MenuItemsChoice, Spinner } from '@wordpress/components';
import { CardHeader } from './settings-general';
import { Highlight, SearchHits } from '../shared/text-diff';

interface Entry {
	id: string;
	time: string;
	/** Time in the site's own time zone and date format. */
	time_display?: string;
	/** Avatar image URL; empty when the account is gone or avatars are off. */
	avatar?: string;
	/** Role the person had when they made the edit, as a label. */
	role_label?: string;
	/** Role slug, used only to colour the label. */
	role_slug?: string;
	/** "undo" when this entry reversed an earlier one. */
	action?: string;
	user_name: string;
	block: string;
	attr: string;
	index: number | null;
	subindex: number | null;
	key: string | null;
	before: string;
	after: string;
	truncated: boolean;
	store_title: string;
	editUrl: string;
	viewUrl: string;
	revisionUrl: string;
}

interface Result {
	entries: Entry[];
	total: number;
	pages: number;
	users: string[];
	blocks: string[];
	retentionDays: number;
}

interface Props {
	url: string;
	days: number;
}

const PER_PAGE = 20;

/**
 * Plain text of an HTML value, for a one-line preview.
 * @param html Stored value.
 */
function plain( html: string ): string {
	const doc = new DOMParser().parseFromString( html, 'text/html' );
	return ( doc.body.textContent || '' ).trim();
}

/**
 * Name the field an entry touched, e.g. `items[2].question`.
 * @param e Log entry.
 */
function fieldName( e: Entry ): string {
	let out = e.attr;
	if ( e.index !== null ) {
		out += `[${ e.index }]`;
	}
	if ( e.key ) {
		out += `.${ e.key }`;
	}
	if ( e.subindex !== null ) {
		out += `[${ e.subindex }]`;
	}
	return out;
}

/**
 * A filter as a pill button that opens a menu, in place of a native <select>
 * whose list the browser draws and a stylesheet cannot reach.
 *
 * Reads "Edited by  Everyone", turns accent-coloured once a choice narrows the
 * list, and the open menu marks the current choice with a tick.
 * @param root0         Props.
 * @param root0.icon    Dashicon slug.
 * @param root0.label   What is being filtered.
 * @param root0.value   Current value ('' = everything).
 * @param root0.options Choices; the first is "everything".
 * @param root0.onChange Called with the chosen value.
 */
function FilterPill( {
	icon,
	label,
	value,
	options,
	onChange,
}: {
	icon: string;
	label: string;
	value: string;
	options: { label: string; value: string }[];
	onChange: ( value: string ) => void;
} ): JSX.Element {
	const current = options.find( ( o ) => o.value === value ) || options[ 0 ];
	return (
		<Dropdown
			className="flexa-log__filter"
			popoverProps={ { placement: 'bottom-start', offset: 6 } }
			renderToggle={ ( { isOpen, onToggle }: { isOpen: boolean; onToggle: () => void } ) => (
				<button
					type="button"
					className={ `flexa-log__pill${ value ? ' is-active' : '' }${ isOpen ? ' is-open' : '' }` }
					onClick={ onToggle }
					aria-expanded={ isOpen }
					aria-haspopup="listbox"
				>
					<span className={ `dashicons dashicons-${ icon }` } aria-hidden="true" />
					<span className="flexa-log__pill-label">{ label }</span>
					<strong className="flexa-log__pill-value">{ current.label }</strong>
					<span
						className="dashicons dashicons-arrow-down-alt2 flexa-log__pill-caret"
						aria-hidden="true"
					/>
				</button>
			) }
			renderContent={ ( { onClose }: { onClose: () => void } ) => (
				<div className="flexa-log__menu">
					<MenuGroup>
						<MenuItemsChoice
							choices={ options }
							value={ value }
							onSelect={ ( v: string ) => {
								onChange( v );
								onClose();
							} }
						/>
					</MenuGroup>
				</div>
			) }
		/>
	);
}

/** Label colours per role: [text, background]. Anything else gets one from the person's colour. */
const ROLE_COLORS: Record< string, [ string, string ] > = {
	administrator: [ '#b32d2e', '#fcebec' ],
	editor: [ '#1d4ed8', '#e6eefe' ],
	author: [ '#15803d', '#e3f6e9' ],
	contributor: [ '#b45309', '#fdf0dc' ],
	subscriber: [ '#50575e', '#eef0f3' ],
	shop_manager: [ '#7c3aed', '#f0e9fe' ],
	customer: [ '#0e7490', '#e0f2f7' ],
	translator: [ '#4338ca', '#e8e8fb' ],
};

/**
 * Colours for a role label.
 * @param slug  Role slug.
 * @param label Role label (used to pick a colour for roles not listed).
 */
function roleColors( slug: string, label: string ): [ string, string ] {
	if ( ROLE_COLORS[ slug ] ) {
		return ROLE_COLORS[ slug ];
	}
	const base = avatarColor( slug || label );
	return [ base, `${ base }1f` ];
}

/**
 * Inline custom properties for a role label (read by .flexa-log__role).
 * @param slug  Role slug.
 * @param label Role label.
 */
function roleStyle( slug: string, label: string ): any {
	const [ fg, bg ] = roleColors( slug, label );
	return { '--role-fg': fg, '--role-bg': bg };
}

/** Avatar colours, picked by name so one person keeps one colour. */
const AVATAR_COLORS = [ '#0891b2', '#7c3aed', '#db2777', '#ea580c', '#16a34a', '#2563eb' ];

/**
 * A stable colour for a person's avatar.
 * @param name Display name.
 */
function avatarColor( name: string ): string {
	let hash = 0;
	for ( let i = 0; i < name.length; i++ ) {
		hash = ( hash * 31 + name.charCodeAt( i ) ) >>> 0;
	}
	return AVATAR_COLORS[ hash % AVATAR_COLORS.length ];
}

/**
 * A person's avatar: their picture, or a coloured initial when there is none
 * (or it fails to load).
 * @param root0      Props.
 * @param root0.name Display name.
 * @param root0.src  Avatar URL, may be empty.
 */
function Avatar( { name, src }: { name: string; src?: string } ) {
	const [ broken, setBroken ] = useState( false );
	if ( src && ! broken ) {
		return (
			<img
				className="flexa-log__avatar"
				src={ src }
				alt=""
				width={ 36 }
				height={ 36 }
				loading="lazy"
				onError={ () => setBroken( true ) }
			/>
		);
	}
	return (
		<span
			className="flexa-log__avatar"
			style={ { background: avatarColor( name ) } }
			aria-hidden="true"
		>
			{ ( name || '?' ).charAt( 0 ).toUpperCase() }
		</span>
	);
}

/**
 * One side of an edit: a labelled pane with the text, changed words marked.
 * @param root0        Props.
 * @param root0.side   "before" or "after".
 * @param root0.entry  The log entry.
 */
function Pane( {
	side,
	entry,
	query,
}: {
	side: 'before' | 'after';
	entry: Entry;
	query: string;
} ) {
	const own = plain( side === 'before' ? entry.before : entry.after );
	return (
		<div className={ `flexa-log__pane is-${ side }` }>
			<span className="flexa-log__label">
				<span
					className={ `dashicons dashicons-${ side === 'before' ? 'minus' : 'plus-alt2' }` }
					aria-hidden="true"
				/>
				{ side === 'before'
					? __( 'Before', 'flexa-block' )
					: __( 'After', 'flexa-block' ) }
			</span>
			<div className="flexa-log__text">
				{ own ? (
					<Highlight
						before={ plain( entry.before ) }
						after={ plain( entry.after ) }
						side={ side }
						query={ query }
					/>
				) : (
					<em>{ __( '(empty)', 'flexa-block' ) }</em>
				) }
			</div>
		</div>
	);
}

/**
 * The Log panel.
 * @param root0      Props.
 * @param root0.url  REST URL of the site-wide log.
 * @param root0.days Retention in days (0 = until the per-page limit).
 */
export function LogPanel( { url, days }: Props ): JSX.Element {
	const [ data, setData ] = useState< Result | null >( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ user, setUser ] = useState( '' );
	const [ block, setBlock ] = useState( '' );
	// What is typed, and what has been searched for: the second follows the
	// first after a short pause, so each keystroke is not a request.
	const [ typed, setTyped ] = useState( '' );
	const [ term, setTerm ] = useState( '' );

	useEffect( () => {
		const timer = window.setTimeout( () => {
			setTerm( typed.trim() );
			setPage( 1 );
		}, 400 );
		return () => window.clearTimeout( timer );
	}, [ typed ] );

	const load = useCallback( () => {
		setLoading( true );
		setError( '' );
		const query = new URLSearchParams( {
			page: String( page ),
			per_page: String( PER_PAGE ),
			user,
			block,
			search: term,
		} );
		( apiFetch as ( o: { url: string } ) => Promise< Result > )( {
			url: `${ url }${ url.includes( '?' ) ? '&' : '?' }${ query }`,
		} )
			.then( setData )
			.catch( ( e: { message?: string } ) =>
				setError( e?.message || __( 'Could not load the log.', 'flexa-block' ) )
			)
			.finally( () => setLoading( false ) );
	}, [ url, page, user, block, term ] );

	useEffect( load, [ load ] );

	const subtitle =
		days > 0
			? sprintf(
					/* translators: %d: number of days */
					__(
						'Changes made from the front end. Entries are removed after %d days.',
						'flexa-block'
					),
					days
			  )
			: __( 'Changes made from the front end.', 'flexa-block' );

	return (
		<div className="flexa-admin-card flexa-setting-card flexa-log">
			<CardHeader
				icon="backup"
				tint="#0891b2"
				title={ __( 'Edit log', 'flexa-block' ) }
				subtitle={ subtitle }
			/>

			<div className="flexa-log__toolbar">
				<div className="flexa-log__filters">
					<label className="flexa-log__search">
						<span className="dashicons dashicons-search" aria-hidden="true" />
						<input
							type="search"
							value={ typed }
							placeholder={ __( 'Search text, page or person…', 'flexa-block' ) }
							aria-label={ __( 'Search the edit log', 'flexa-block' ) }
							onChange={ ( ev: { target: { value: string } } ) => setTyped( ev.target.value ) }
						/>
						{ typed && (
							<button
								type="button"
								className="flexa-log__search-clear"
								aria-label={ __( 'Clear search', 'flexa-block' ) }
								onClick={ () => setTyped( '' ) }
							>
								<span className="dashicons dashicons-no-alt" aria-hidden="true" />
							</button>
						) }
					</label>
					<FilterPill
						icon="admin-users"
						label={ __( 'Edited by', 'flexa-block' ) }
						value={ user }
						options={ [
							{ label: __( 'Everyone', 'flexa-block' ), value: '' },
							...( data?.users || [] ).map( ( u ) => ( {
								label: u,
								value: u,
							} ) ),
						] }
						onChange={ ( v ) => {
							setUser( v );
							setPage( 1 );
						} }
					/>
					<FilterPill
						icon="screenoptions"
						label={ __( 'Block', 'flexa-block' ) }
						value={ block }
						options={ [
							{ label: __( 'All blocks', 'flexa-block' ), value: '' },
							...( data?.blocks || [] ).map( ( b ) => ( {
								label: b,
								value: b,
							} ) ),
						] }
						onChange={ ( v ) => {
							setBlock( v );
							setPage( 1 );
						} }
					/>
				</div>
				<div className="flexa-log__summary">
					{ data && (
						<span className="flexa-log__count">
							{ sprintf(
								/* translators: %d: number of edits */
								_n( '%d edit', '%d edits', data.total, 'flexa-block' ),
								data.total
							) }
						</span>
					) }
					<Button
						variant="secondary"
						icon="update"
						onClick={ load }
						disabled={ loading }
					>
						{ __( 'Refresh', 'flexa-block' ) }
					</Button>
				</div>
			</div>

			{ error && (
				<p className="flexa-log__notice is-error" role="alert">
					{ error }
				</p>
			) }
			{ loading && ! data && <Spinner /> }
			{ data && data.total === 0 && ! loading && (
				<div className="flexa-log__empty">
					<span className="dashicons dashicons-backup" aria-hidden="true" />
					<p>
						{ term
							? sprintf(
									/* translators: %s: the search text */
									__( 'No edits match “%s”.', 'flexa-block' ),
									term
							  )
							: __( 'No front-end edits to show.', 'flexa-block' ) }
					</p>
				</div>
			) }

			{ data && data.entries.length > 0 && (
				<ol className={ `flexa-log__list${ loading ? ' is-loading' : '' }` }>
					{ data.entries.map( ( e ) => (
						<li key={ e.id } className="flexa-log__item">
							<header className="flexa-log__head">
								<Avatar name={ e.user_name } src={ e.avatar } />
								<div className="flexa-log__by">
									<span className="flexa-log__name">
										<strong>
										<SearchHits text={ e.user_name } query={ term } />
									</strong>
										{ e.role_label && (
											<span
												className="flexa-log__role"
												style={ roleStyle( e.role_slug || '', e.role_label || '' ) }
											>
												{ e.role_label }
											</span>
										) }
									</span>
									<span className="flexa-log__time">
										{ e.time_display || e.time }
									</span>
								</div>
								{ e.action === 'undo' && (
									<span className="flexa-log__undo">
										<span className="dashicons dashicons-undo" aria-hidden="true" />
										{ __( 'Undo', 'flexa-block' ) }
									</span>
								) }
								<span className="flexa-log__chip">
									<b>{ e.block }</b>
									<span>{ fieldName( e ) }</span>
								</span>
							</header>

							<div className="flexa-log__where">
								<span className="dashicons dashicons-admin-page" aria-hidden="true" />
								{ e.editUrl ? (
									<a href={ e.editUrl }>
										<SearchHits
											text={ e.store_title || __( '(no title)', 'flexa-block' ) }
											query={ term }
										/>
									</a>
								) : (
									<span>{ e.store_title || __( '(no title)', 'flexa-block' ) }</span>
								) }
								{ e.viewUrl && (
									<a
										className="flexa-log__view"
										href={ e.viewUrl }
										target="_blank"
										rel="noreferrer"
									>
										{ __( 'View page', 'flexa-block' ) }
										<span className="dashicons dashicons-external" aria-hidden="true" />
									</a>
								) }
							</div>

							<div className="flexa-log__diff">
								<Pane side="before" entry={ e } query={ term } />
								<Pane side="after" entry={ e } query={ term } />
							</div>

							{ ( e.revisionUrl || e.truncated ) && (
								<footer className="flexa-log__foot">
									{ e.revisionUrl && (
										<a href={ e.revisionUrl } target="_blank" rel="noreferrer">
											<span className="dashicons dashicons-backup" aria-hidden="true" />
											{ __( 'Compare with the version before', 'flexa-block' ) }
										</a>
									) }
									{ e.truncated && (
										<span>
											{ __( 'Very long text; only the start is kept.', 'flexa-block' ) }
										</span>
									) }
								</footer>
							) }
						</li>
					) ) }
				</ol>
			) }

			{ data && data.pages > 1 && (
				<div className="flexa-log__pager">
					<Button
						variant="secondary"
						disabled={ loading || page <= 1 }
						onClick={ () => setPage( page - 1 ) }
					>
						{ __( 'Previous', 'flexa-block' ) }
					</Button>
					<span>
						{ sprintf(
							/* translators: 1: current page, 2: number of pages */
							__( 'Page %1$d of %2$d', 'flexa-block' ),
							page,
							data.pages
						) }
					</span>
					<Button
						variant="secondary"
						disabled={ loading || page >= data.pages }
						onClick={ () => setPage( page + 1 ) }
					>
						{ __( 'Next', 'flexa-block' ) }
					</Button>
				</div>
			) }
		</div>
	);
}
