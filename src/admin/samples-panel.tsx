/**
 * Sample Data view — browse and import the free plugin's bundled samples.
 *
 * Unlike the settings panels (which mutate a shared settings tree), this panel
 * owns its own data: it fetches the import catalog from the REST engine on
 * mount, and each card drives an import or cleanup call. The same engine powers
 * Pro's Examples and Starter Templates, so any source registered server-side
 * shows up here as its own section automatically.
 */

import { useEffect, useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Modal, Spinner, Tooltip } from '@wordpress/components';

interface ImportItem {
	id: string;
	title: string;
	description?: string;
	category?: string;
	blocks?: string[];
	version?: string;
	tags?: string[];
	featured?: boolean;
	preview?: string;
	imported?: boolean;
	imported_post_id?: number;
	edit_link?: string;
	view_link?: string;
}

interface ImportSource {
	key: string;
	label: string;
	items: ImportItem[];
}

type Feedback = { type: 'error' | 'success'; text: string } | null;

const cfg: FlexaBlockImportsData = window.flexaBlockImports || {};

/**
 * The Sample Data panel.
 */
export function SamplesPanel(): JSX.Element {
	const [ sources, setSources ] = useState< ImportSource[] >( [] );
	const [ loading, setLoading ] = useState( true );
	const [ loadError, setLoadError ] = useState( false );
	const [ busy, setBusy ] = useState( '' );
	const [ feedback, setFeedback ] = useState< Feedback >( null );
	// The item awaiting a remove confirmation. Held here rather than in the card
	// so the dialog is a single centred modal owned by the panel, and so the
	// in-flight label lives in the dialog instead of resizing the card's button.
	const [ confirming, setConfirming ] = useState< {
		source: ImportSource;
		item: ImportItem;
	} | null >( null );

	const load = () => {
		if ( ! cfg.listUrl ) {
			setLoading( false );
			setLoadError( true );
			return;
		}
		setLoading( true );
		setLoadError( false );
		apiFetch( { url: cfg.listUrl } )
			.then( ( res: any ) => {
				setSources( Array.isArray( res?.sources ) ? res.sources : [] );
				setLoading( false );
			} )
			.catch( () => {
				setLoading( false );
				setLoadError( true );
			} );
	};

	useEffect( load, [] );

	// Replace one item in place after an import/cleanup so the card updates
	// without a full refetch.
	const patchItem = (
		sourceKey: string,
		itemId: string,
		patch: Partial< ImportItem >
	) =>
		setSources( ( prev ) =>
			prev.map( ( s ) =>
				s.key !== sourceKey
					? s
					: {
							...s,
							items: s.items.map( ( it ) =>
								it.id === itemId ? { ...it, ...patch } : it
							),
					  }
			)
		);

	const key = ( sourceKey: string, itemId: string ) =>
		`${ sourceKey }:${ itemId }`;

	const doImport = ( source: ImportSource, item: ImportItem ) => {
		if ( ! cfg.importUrl ) {
			return;
		}
		setBusy( key( source.key, item.id ) );
		setFeedback( null );
		apiFetch( {
			url: cfg.importUrl,
			method: 'POST',
			data: { source: source.key, id: item.id },
		} )
			.then( ( res: any ) => {
				patchItem( source.key, item.id, {
					imported: true,
					imported_post_id: res?.post_id,
					edit_link: res?.edit_link || '',
					view_link: res?.view_link || '',
				} );
				setFeedback( {
					type: 'success',
					text: sprintf(
						/* translators: %s: sample title */
						__( '“%s” imported as a draft.', 'flexa-block' ),
						item.title
					),
				} );
			} )
			.catch( ( err: any ) =>
				setFeedback( {
					type: 'error',
					text:
						err?.message ||
						__( 'Import failed. Please try again.', 'flexa-block' ),
				} )
			)
			.finally( () => setBusy( '' ) );
	};

	const doRemove = ( source: ImportSource, item: ImportItem ) => {
		if ( ! cfg.cleanupUrl ) {
			return;
		}
		setBusy( key( source.key, item.id ) );
		setFeedback( null );
		apiFetch( {
			url: cfg.cleanupUrl,
			method: 'POST',
			data: { source: source.key, id: item.id },
		} )
			.then( () => {
				patchItem( source.key, item.id, {
					imported: false,
					imported_post_id: 0,
					edit_link: '',
					view_link: '',
				} );
				setFeedback( {
					type: 'success',
					text: sprintf(
						/* translators: %s: sample title */
						__( '“%s” moved to Trash.', 'flexa-block' ),
						item.title
					),
				} );
			} )
			.catch( ( err: any ) =>
				setFeedback( {
					type: 'error',
					text:
						err?.message ||
						__( 'Could not remove that item.', 'flexa-block' ),
				} )
			)
			.finally( () => {
				setBusy( '' );
				setConfirming( null );
			} );
	};

	if ( loading ) {
		return (
			<div className="flexa-samples__state">
				<Spinner />
				<span>{ __( 'Loading samples…', 'flexa-block' ) }</span>
			</div>
		);
	}

	if ( loadError ) {
		return (
			<div className="flexa-samples__state">
				<span className="dashicons dashicons-warning" aria-hidden="true" />
				<span>
					{ __(
						'Could not load the sample catalog.',
						'flexa-block'
					) }
				</span>
				<button
					type="button"
					className="components-button is-secondary"
					onClick={ load }
				>
					{ __( 'Retry', 'flexa-block' ) }
				</button>
			</div>
		);
	}

	const totalItems = sources.reduce( ( n, s ) => n + s.items.length, 0 );
	if ( totalItems === 0 ) {
		return (
			<div className="flexa-samples__state">
				<span>{ __( 'No samples are available.', 'flexa-block' ) }</span>
			</div>
		);
	}

	return (
		<div className="flexa-samples">
			<header className="flexa-samples__intro">
				<h2 className="flexa-admin-card__title">
					{ __( 'Sample Data', 'flexa-block' ) }
				</h2>
				<p className="flexa-setting-card__sub">
					{ __(
						'Import a ready-made example of a block as a draft page, then open it in the editor to explore and customize, or preview it on the front end.',
						'flexa-block'
					) }
				</p>
			</header>

			{ feedback && (
				<div
					className={ `flexa-samples__notice is-${ feedback.type }` }
					aria-live="polite"
				>
					{ feedback.text }
				</div>
			) }

			{ sources.map( ( source ) => (
				<section key={ source.key } className="flexa-samples__section">
					{ sources.length > 1 && (
						<h3 className="flexa-samples__section-title">
							{ source.label }
						</h3>
					) }
					<div className="flexa-samples__grid">
						{ source.items.map( ( item ) => (
							<SampleCard
								key={ item.id }
								item={ item }
								busy={ busy === key( source.key, item.id ) }
								onImport={ () => doImport( source, item ) }
								onRemove={ () =>
									setConfirming( { source, item } )
								}
							/>
						) ) }
					</div>
				</section>
			) ) }

			{ confirming && (
				<ConfirmRemoveModal
					title={ confirming.item.title }
					busy={
						busy ===
						key( confirming.source.key, confirming.item.id )
					}
					onCancel={ () => setConfirming( null ) }
					onConfirm={ () =>
						doRemove( confirming.source, confirming.item )
					}
				/>
			) }
		</div>
	);
}

/**
 * Centred confirmation for a remove, in place of window.confirm.
 *
 * The in-flight label sits on this dialog's own button, not on the card's, so
 * the card's action row never reflows mid-request.
 * @param root0
 * @param root0.title     Sample title, named in the prompt.
 * @param root0.busy      Whether the cleanup request is in flight.
 * @param root0.onCancel  Dismiss handler.
 * @param root0.onConfirm Confirm handler.
 */
function ConfirmRemoveModal( {
	title,
	busy,
	onCancel,
	onConfirm,
}: {
	title: string;
	busy: boolean;
	onCancel: () => void;
	onConfirm: () => void;
} ): JSX.Element {
	return (
		<Modal
			title={ __( 'Move to Trash?', 'flexa-block' ) }
			onRequestClose={ busy ? () => undefined : onCancel }
			size="small"
			className="flexa-samples__confirm"
		>
			<p className="flexa-samples__confirm-text">
				{ sprintf(
					/* translators: %s: sample title */
					__(
						'The imported “%s” content will be moved to Trash. You can import it again afterwards.',
						'flexa-block'
					),
					title
				) }
			</p>
			<div className="flexa-samples__confirm-actions">
				<button
					type="button"
					className="components-button is-tertiary"
					onClick={ onCancel }
					disabled={ busy }
				>
					{ __( 'Cancel', 'flexa-block' ) }
				</button>
				<button
					type="button"
					className="components-button is-primary is-destructive"
					onClick={ onConfirm }
					disabled={ busy }
				>
					{ busy
						? __( 'Removing…', 'flexa-block' )
						: __( 'Move to Trash', 'flexa-block' ) }
				</button>
			</div>
		</Modal>
	);
}

/**
 * A single sample card.
 * @param root0
 * @param root0.item     The sample metadata.
 * @param root0.busy     Whether an action is in flight for this card.
 * @param root0.onImport Import handler.
 * @param root0.onRemove Cleanup handler.
 */
function SampleCard( {
	item,
	busy,
	onImport,
	onRemove,
}: {
	item: ImportItem;
	busy: boolean;
	onImport: () => void;
	onRemove: () => void;
} ): JSX.Element {
	const blocks = useMemo(
		() =>
			( item.blocks || [] ).map( ( name ) =>
				name.replace( /^flexa\//, '' )
			),
		[ item.blocks ]
	);

	return (
		<article
			className={ `flexa-admin-card flexa-sample-card${
				item.imported ? ' is-imported' : ''
			}` }
		>
			{ item.preview && (
				<div className="flexa-sample-card__preview">
					<img src={ item.preview } alt="" loading="lazy" />
				</div>
			) }
			<div className="flexa-sample-card__body">
				<div className="flexa-sample-card__head">
					<h4 className="flexa-sample-card__title">{ item.title }</h4>
					{ item.imported && (
						<span className="flexa-sample-card__badge">
							{ __( 'Imported', 'flexa-block' ) }
						</span>
					) }
				</div>
				{ item.description && (
					<p className="flexa-sample-card__desc">
						{ item.description }
					</p>
				) }
				{ blocks.length > 0 && (
					<ul className="flexa-sample-card__blocks">
						{ blocks.map( ( name ) => (
							<li key={ name }>{ name }</li>
						) ) }
					</ul>
				) }
			</div>

			<footer className="flexa-sample-card__actions">
				{ item.imported ? (
					<>
						{ item.edit_link && (
							<a
								className="components-button is-primary"
								href={ item.edit_link }
							>
								{ __( 'Open in editor', 'flexa-block' ) }
							</a>
						) }
						{ item.view_link && (
							<a
								className="components-button is-secondary"
								href={ item.view_link }
								target="_blank"
								rel="noreferrer"
							>
								{ __( 'Preview', 'flexa-block' ) }
								<span
									className="flexa-sample-card__ext dashicons dashicons-external"
									aria-hidden="true"
								/>
							</a>
						) }
						<Tooltip text={ __( 'Remove', 'flexa-block' ) }>
							<button
								type="button"
								className="components-button is-tertiary is-destructive flexa-sample-card__remove"
								onClick={ onRemove }
								disabled={ busy }
								aria-label={ __( 'Remove', 'flexa-block' ) }
							>
								<span
									className="dashicons dashicons-trash"
									aria-hidden="true"
								/>
							</button>
						</Tooltip>
					</>
				) : (
					<button
						type="button"
						className="components-button is-primary"
						onClick={ onImport }
						disabled={ busy }
					>
						{ busy
							? __( 'Importing…', 'flexa-block' )
							: __( 'Import', 'flexa-block' ) }
					</button>
				) }
			</footer>
		</article>
	);
}
