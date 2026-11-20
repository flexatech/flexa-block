/**
 * Review view: changes made from the front end to published pages, waiting to
 * be applied.
 *
 * One card per page with a review copy: who changed it, every change as a
 * before / after pair, and the decision. Apply goes through the same route as
 * the editor's "Apply to original" button, so the same permission, lock and
 * conflict checks hold; Discard trashes the copy. Either way the edit log
 * records who decided and when.
 */

import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, CheckboxControl, Spinner } from '@wordpress/components';
import { CardHeader } from './settings-general';
import { Highlight } from '../shared/text-diff';
import { Avatar, roleStyle } from './log-panel';

interface Person {
	name: string;
	avatar: string;
	roleLabel: string;
	roleSlug: string;
}

/**
 * A person's name with their role, coloured as in the edit log.
 * @param root0   Props.
 * @param root0.p The person.
 */
function PersonTag( { p }: { p: Person } ): JSX.Element {
	return (
		<span className="flexa-log__name">
			<strong>{ p.name }</strong>
			{ p.roleLabel && (
				<span className="flexa-log__role" style={ roleStyle( p.roleSlug, p.roleLabel ) }>
					{ p.roleLabel }
				</span>
			) }
		</span>
	);
}

interface Change {
	id: string;
	time_display: string;
	user_name: string;
	person: Person;
	block: string;
	attr: string;
	kind: string;
	action: string;
	before: string;
	after: string;
}

interface Item {
	copyId: number;
	title: string;
	viewLink: string;
	copyEditLink: string;
	modified: string;
	people: Person[];
	entries: Change[];
	conflicted: boolean;
	canApply: boolean;
	reason: string;
	applyPath: string;
	discardPath: string;
	// The copy's content as listed; applying is refused if it changed since.
	seen: string;
}

// WordPress's jQuery, for the Heartbeat events.
type JQ = ( target: unknown ) => {
	on: ( event: string, handler: ( e: unknown, data: unknown ) => void ) => void;
	off: ( event: string ) => void;
};

interface Props {
	url: string;
	onCount: ( count: number ) => void;
}

/**
 * Plain text of a stored value.
 * @param html Stored value.
 */
function plain( html: string ): string {
	return ( new DOMParser().parseFromString( html, 'text/html' ).body.textContent || '' ).trim();
}

/**
 * One page waiting for review.
 * @param root0          Props.
 * @param root0.item     The copy.
 * @param root0.onDone   Called once it has been applied or discarded.
 */
function ReviewCard( {
	item,
	onDone,
	onStale,
}: {
	item: Item;
	onDone: () => void;
	onStale: () => void;
} ): JSX.Element {
	const [ busy, setBusy ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ overwrite, setOverwrite ] = useState( false );
	const [ conflicted, setConflicted ] = useState( item.conflicted );

	const run = ( kind: 'apply' | 'discard' ) => {
		if (
			kind === 'discard' &&
			// eslint-disable-next-line no-alert
			! window.confirm(
				__( 'Discard these changes? The copy goes to the trash; the live page stays as it is.', 'flexa-block' )
			)
		) {
			return;
		}
		setBusy( kind );
		setError( '' );
		( apiFetch as ( o: Record< string, unknown > ) => Promise< unknown > )( {
			path: kind === 'apply' ? item.applyPath : item.discardPath,
			method: 'POST',
			data: kind === 'apply' ? { force: conflicted && overwrite, seen: item.seen } : {},
		} )
			.then( onDone )
			.catch( ( e: { code?: string; message?: string } ) => {
				if ( e?.code === 'flexa_working_copy_conflict' ) {
					setConflicted( true );
				}
				// The copy has changes this card does not show yet.
				if ( e?.code === 'flexa_working_copy_changed' ) {
					onStale();
				}
				setError( e?.message || __( 'That did not work. Reload and try again.', 'flexa-block' ) );
				setBusy( '' );
			} );
	};

	return (
		<li className="flexa-log__item flexa-review__item">
			<header className="flexa-log__head">
				<div className="flexa-log__by">
					<strong className="flexa-review__title">{ item.title }</strong>
					<span className="flexa-log__time">
						{ sprintf(
							/* translators: %s: date and time */
							__( 'Last change %s', 'flexa-block' ),
							item.modified
						) }
					</span>
					<div className="flexa-review__who">
						<span className="flexa-log__time">{ __( 'Changed by', 'flexa-block' ) }</span>
						{ item.people.map( ( p ) => (
							<span key={ p.name } className="flexa-review__person">
								<Avatar name={ p.name } src={ p.avatar } />
								<PersonTag p={ p } />
							</span>
						) ) }
					</div>
				</div>
				<span className="flexa-log__undo flexa-log__pending">
					<span className="dashicons dashicons-clock" aria-hidden="true" />
					{ sprintf(
						/* translators: %d: number of changes */
						_n( '%d change', '%d changes', item.entries.length, 'flexa-block' ),
						item.entries.length
					) }
				</span>
			</header>

			<ol className="flexa-review__changes">
				{ item.entries.map( ( e ) => (
					<li key={ e.id }>
						<div className="flexa-review__meta">
							<Avatar name={ e.person.name } src={ e.person.avatar } />
							<PersonTag p={ e.person } />
							<span className="flexa-log__time">{ e.time_display }</span>
							<span className="flexa-log__chip">
								<b>{ e.block }</b>
								<span>
									{ e.attr }
									{ e.action === 'undo' ? ` · ${ __( 'Undo', 'flexa-block' ) }` : '' }
								</span>
							</span>
						</div>
						<div className="flexa-log__diff">
							<div className="flexa-log__pane is-before">
								<span className="flexa-log__label">{ __( 'Before', 'flexa-block' ) }</span>
								<div className="flexa-log__text">
									<Highlight before={ plain( e.before ) } after={ plain( e.after ) } side="before" />
								</div>
							</div>
							<div className="flexa-log__pane is-after">
								<span className="flexa-log__label">{ __( 'After', 'flexa-block' ) }</span>
								<div className="flexa-log__text">
									<Highlight before={ plain( e.before ) } after={ plain( e.after ) } side="after" />
								</div>
							</div>
						</div>
					</li>
				) ) }
			</ol>

			{ conflicted && (
				<div className="flexa-review__warn">
					<p>
						{ __(
							'The live page was changed after this copy was made. Applying the copy replaces those changes.',
							'flexa-block'
						) }
					</p>
					<CheckboxControl
						__nextHasNoMarginBottom
						label={ __( 'Apply anyway and replace those changes', 'flexa-block' ) }
						checked={ overwrite }
						onChange={ setOverwrite }
					/>
				</div>
			) }

			{ error && <p className="flexa-log__notice is-error">{ error }</p> }

			<footer className="flexa-review__actions">
				{ item.canApply ? (
					<Button
						variant="primary"
						isBusy={ busy === 'apply' }
						disabled={ !! busy || ( conflicted && ! overwrite ) }
						onClick={ () => run( 'apply' ) }
					>
						{ __( 'Apply to the live page', 'flexa-block' ) }
					</Button>
				) : (
					<span className="flexa-review__reason">
						<span className="dashicons dashicons-lock" aria-hidden="true" />
						{ item.reason || __( 'You cannot apply this copy.', 'flexa-block' ) }
					</span>
				) }
				<Button variant="secondary" isDestructive isBusy={ busy === 'discard' } disabled={ !! busy } onClick={ () => run( 'discard' ) }>
					{ __( 'Discard', 'flexa-block' ) }
				</Button>
				<Button variant="tertiary" href={ item.copyEditLink }>
					{ __( 'Open in editor', 'flexa-block' ) }
				</Button>
				<Button variant="tertiary" href={ item.viewLink } target="_blank">
					{ __( 'View live page', 'flexa-block' ) }
				</Button>
			</footer>
		</li>
	);
}

/**
 * The Review panel.
 * @param root0         Props.
 * @param root0.url     REST URL of the queue.
 * @param root0.onCount Reports how many copies wait (for the tab's badge).
 */
export function ReviewPanel( { url, onCount }: Props ): JSX.Element {
	const [ items, setItems ] = useState< Item[] | null >( null );
	const [ error, setError ] = useState( '' );
	// The queue's signature as listed, and whether the server has a newer one.
	const stamp = useRef( '' );
	const [ fresh, setFresh ] = useState( false );

	const load = useCallback( () => {
		setError( '' );
		setFresh( false );
		( apiFetch as ( o: { url: string } ) => Promise< { items: Item[]; stamp: string } > )( { url } )
			.then( ( res ) => {
				setItems( res.items || [] );
				stamp.current = res.stamp || '';
				onCount( ( res.items || [] ).length );
			} )
			.catch( ( e: { message?: string } ) => setError( e?.message || __( 'Could not load.', 'flexa-block' ) ) );
	}, [ url, onCount ] );

	useEffect( load, [ load ] );

	// Listen to the Heartbeat (set up for every admin screen on the server) and
	// ask it to beat about every 15 seconds while this tab is open. A change is
	// announced, not applied: the list only reloads when the person says so,
	// so nothing moves under a card they are reading.
	useEffect( () => {
		const w = window as unknown as {
			jQuery?: JQ;
			wp?: { heartbeat?: { interval: ( s: number | string ) => void } };
		};
		const $ = w.jQuery;
		if ( ! $ ) {
			return;
		}
		w.wp?.heartbeat?.interval( 15 );
		$( document ).on( 'flexa-review-tick.panel', ( _e, data ) => {
			const r = data as { count: number; stamp: string };
			onCount( r.count );
			if ( stamp.current && r.stamp !== stamp.current ) {
				setFresh( true );
			}
		} );
		return () => {
			$( document ).off( 'flexa-review-tick.panel' );
			w.wp?.heartbeat?.interval( 60 );
		};
	}, [ onCount ] );

	return (
		<div className="flexa-admin-card flexa-setting-card flexa-log">
			<CardHeader
				icon="yes-alt"
				tint="#d97706"
				title={ __( 'Review', 'flexa-block' ) }
				subtitle={ __(
					'Changes made from the front end to published pages. Visitors see none of them until they are applied.',
					'flexa-block'
				) }
			/>

			<div className="flexa-review__bar">
				{ fresh ? (
					<span className="flexa-review__fresh" role="status">
						<span className="dashicons dashicons-update" aria-hidden="true" />
						{ __( 'There are new changes waiting for review.', 'flexa-block' ) }
					</span>
				) : (
					<span className="flexa-log__time">
						{ __( 'Checked for new changes about every 15 seconds.', 'flexa-block' ) }
					</span>
				) }
				<Button variant={ fresh ? 'primary' : 'secondary' } icon="update" onClick={ load }>
					{ fresh ? __( 'Show them', 'flexa-block' ) : __( 'Refresh', 'flexa-block' ) }
				</Button>
			</div>

			{ error && <p className="flexa-log__notice is-error">{ error }</p> }
			{ ! items && ! error && <Spinner /> }
			{ items && items.length === 0 && (
				<div className="flexa-log__empty">
					<span className="dashicons dashicons-yes-alt" aria-hidden="true" />
					<p>{ __( 'Nothing is waiting for review.', 'flexa-block' ) }</p>
				</div>
			) }

			{ items && items.length > 0 && (
				<ol className="flexa-log__list">
					{ items.map( ( item ) => (
						<ReviewCard
							key={ `${ item.copyId }-${ item.seen }` }
							item={ item }
							onDone={ load }
							onStale={ () => setFresh( true ) }
						/>
					) ) }
				</ol>
			) }
		</div>
	);
}
