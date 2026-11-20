/**
 * "Front-end edits" panel for the block editor.
 *
 * Lists what was changed from the front end of the site: who, when, which block
 * and field, and the text before and after. Newest first, filterable by person
 * and by block. When the edit started a revision session, the entry links to the
 * revision that holds the content from before.
 *
 * Loaded for any post the current user can edit (see `Inline_Edit_Log::enqueue()`).
 * A synced pattern or template part opened in the editor shows its own history, so
 * a shared piece of content says who changed it from any page.
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { Button, SelectControl, Spinner } from '@wordpress/components';
import { useCallback, useEffect, useMemo, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import { Highlight } from '../shared/text-diff';

interface LogEntry {
	id: string;
	time: string;
	/** Time in the site's own time zone and date format. */
	time_display?: string;
	/** Role the person had when they made the edit, as a label. */
	role_label?: string;
	/** "undo" when this entry reversed an earlier one. */
	action?: string;
	user_id: number;
	user_name: string;
	block: string;
	block_name: string;
	attr: string;
	index: number | null;
	subindex: number | null;
	key: string | null;
	store_type: string;
	before: string;
	after: string;
	truncated: boolean;
	revisionUrl: string;
}

interface Boot {
	postId: number;
	path: string;
}

declare global {
	interface Window {
		flexaBlockInlineEditLog?: Boot;
	}
}

const PAGE = 20;

/**
 * Plain text of an HTML value, for a one-line preview.
 *
 * @param html Stored value.
 */
function plain( html: string ): string {
	const doc = new DOMParser().parseFromString( html, 'text/html' );
	return ( doc.body.textContent || '' ).trim();
}

/**
 * Name the field an entry touched, e.g. `items[2].title`.
 *
 * @param e Log entry.
 */
function fieldName( e: LogEntry ): string {
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
 * The panel itself.
 */
function InlineEditLogPanel(): JSX.Element | null {
	const boot = window.flexaBlockInlineEditLog;

	const [ entries, setEntries ] = useState< LogEntry[] >( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );
	const [ person, setPerson ] = useState( '' );
	const [ block, setBlock ] = useState( '' );
	const [ shown, setShown ] = useState( PAGE );

	const load = useCallback( () => {
		if ( ! boot ) {
			return;
		}
		setLoading( true );
		setError( '' );
		( apiFetch as ( o: { path: string } ) => Promise< { entries: LogEntry[] } > )( {
			path: `${ boot.path }?postId=${ boot.postId }`,
		} )
			.then( ( res ) => setEntries( res.entries || [] ) )
			.catch( ( e: { message?: string } ) =>
				setError( e?.message || __( 'Could not load.', 'flexa-block' ) )
			)
			.finally( () => setLoading( false ) );
	}, [ boot ] );

	useEffect( load, [ load ] );

	const people = useMemo(
		() => Array.from( new Set( entries.map( ( e ) => e.user_name ) ) ),
		[ entries ]
	);
	const blocks = useMemo(
		() => Array.from( new Set( entries.map( ( e ) => e.block ) ) ),
		[ entries ]
	);
	const visible = entries.filter(
		( e ) =>
			( ! person || e.user_name === person ) &&
			( ! block || e.block === block )
	);

	if ( ! boot ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="flexa-block-inline-edit-log"
			title={ __( 'Front-end edits', 'flexa-block' ) }
			initialOpen={ false }
		>
			{ loading && <Spinner /> }
			{ error && <p role="alert">{ error }</p> }
			{ ! loading && ! error && entries.length === 0 && (
				<p>
					{ __(
						'Nothing has been edited from the front end yet.',
						'flexa-block'
					) }
				</p>
			) }

			{ entries.length > 0 && (
				<>
					<SelectControl
						label={ __( 'Edited by', 'flexa-block' ) }
						value={ person }
						options={ [
							{ label: __( 'Everyone', 'flexa-block' ), value: '' },
							...people.map( ( p ) => ( { label: p, value: p } ) ),
						] }
						onChange={ ( v: string ) => {
							setPerson( v );
							setShown( PAGE );
						} }
					/>
					<SelectControl
						label={ __( 'Block', 'flexa-block' ) }
						value={ block }
						options={ [
							{ label: __( 'All blocks', 'flexa-block' ), value: '' },
							...blocks.map( ( b ) => ( { label: b, value: b } ) ),
						] }
						onChange={ ( v: string ) => {
							setBlock( v );
							setShown( PAGE );
						} }
					/>
				</>
			) }

			<ol style={ { margin: 0, padding: 0, listStyle: 'none' } }>
				{ visible.slice( 0, shown ).map( ( e ) => (
					<li
						key={ e.id }
						style={ {
							borderTop: '2px solid #c3c4c7',
							padding: '12px 0',
							fontSize: '12px',
						} }
					>
						<div>
							<strong>{ e.user_name }</strong>
							{ e.role_label ? ` (${ e.role_label })` : '' } · { e.time_display || e.time }
							{ e.action === 'undo' ? ` · ${ __( 'Undo', 'flexa-block' ) }` : '' }
						</div>
						<div style={ { color: '#757575' } }>
							{ sprintf(
								/* translators: 1: block type, 2: field name */
								__( '%1$s · %2$s', 'flexa-block' ),
								e.block,
								fieldName( e )
							) }
						</div>
						<div
							style={ {
								display: 'grid',
								gridTemplateColumns: '1fr 1fr',
								gap: '8px',
								margin: '4px 0',
							} }
						>
							{ [
								[ __( 'Before', 'flexa-block' ), e.before ],
								[ __( 'After', 'flexa-block' ), e.after ],
							].map( ( [ label, value ], col ) => (
								<div key={ label } style={ { wordBreak: 'break-word' } }>
									<strong>{ label }</strong>
									<div title={ value }>
										{ plain( value ) ? (
											<Highlight
												before={ plain( e.before ) }
												after={ plain( e.after ) }
												side={ col === 1 ? 'after' : 'before' }
											/>
										) : (
											<em>{ __( '(empty)', 'flexa-block' ) }</em>
										) }
									</div>
								</div>
							) ) }
						</div>
						{ e.truncated && (
							<div style={ { color: '#757575' } }>
								{ __(
									'Very long text; the log keeps only the start.',
									'flexa-block'
								) }
							</div>
						) }
						{ e.revisionUrl && (
							<a
								href={ e.revisionUrl }
								target="_blank"
								rel="noreferrer"
							>
								{ __(
									'See the version from before',
									'flexa-block'
								) }
							</a>
						) }
					</li>
				) ) }
			</ol>

			{ visible.length > shown && (
				<Button variant="secondary" onClick={ () => setShown( shown + PAGE ) }>
					{ __( 'Show more', 'flexa-block' ) }
				</Button>
			) }
			{ ' ' }
			<Button variant="tertiary" onClick={ load } disabled={ loading }>
				{ __( 'Refresh', 'flexa-block' ) }
			</Button>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'flexa-block-inline-edit-log', {
	render: InlineEditLogPanel,
	icon: 'backup',
} );
