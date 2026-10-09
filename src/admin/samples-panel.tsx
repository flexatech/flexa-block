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
import { Modal, Spinner } from '@wordpress/components';

/**
 * One value a preset lets you fill in before importing, as the server declares
 * it. Mirrors the normalized shape from Preset_Slots; nothing is validated here
 * beyond `maxLength`, because the server decides what a slot accepts and a
 * second opinion in the browser would only drift from it.
 */
interface ImportSlot {
	type: 'text' | 'multiline' | 'url' | 'email' | 'phone' | 'media';
	label: string;
	default: string;
	max: number;
	help: string;
}

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
	slots?: Record< string, ImportSlot >;
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
	// The item whose slots are being filled in, held here for the same reason.
	const [ filling, setFilling ] = useState< {
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

	/**
	 * Import one item, optionally with slot values.
	 *
	 * Resolves to an error message, or to '' on success. It does not place the
	 * message itself: a plain import shows it in the panel's notice, while a
	 * slot import has to show it inside the open dialog, next to the fields the
	 * user still has to fix. The panel's notice sits behind the modal, where
	 * nobody would read it.
	 * @param source The owning source.
	 * @param item   The item to import.
	 * @param slots  Values for the item's declared slots, if it has any.
	 */
	const doImport = (
		source: ImportSource,
		item: ImportItem,
		slots: Record< string, string > = {}
	): Promise< string > => {
		if ( ! cfg.importUrl ) {
			return Promise.resolve(
				__( 'The import endpoint is unavailable.', 'flexa-block' )
			);
		}
		setBusy( key( source.key, item.id ) );
		setFeedback( null );
		return apiFetch( {
			url: cfg.importUrl,
			method: 'POST',
			data: { source: source.key, id: item.id, slots },
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
				return '';
			} )
			.catch(
				( err: any ) =>
					err?.message ||
					__( 'Import failed. Please try again.', 'flexa-block' )
			)
			.finally( () => setBusy( '' ) );
	};

	// Pressing Import on an item that declares slots opens this first. Same
	// button, one step in front of it: the values have to be collected before
	// the request, because the importer applies them while building the post
	// and there is no second pass that could add them afterwards.
	const startImport = ( source: ImportSource, item: ImportItem ) => {
		if ( item.slots && Object.keys( item.slots ).length > 0 ) {
			setFilling( { source, item } );
			return;
		}
		doImport( source, item ).then( ( message ) => {
			if ( message ) {
				setFeedback( { type: 'error', text: message } );
			}
		} );
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
								onImport={ () => startImport( source, item ) }
								onRemove={ () =>
									setConfirming( { source, item } )
								}
							/>
						) ) }
					</div>
				</section>
			) ) }

			{ filling && (
				<SlotFormModal
					item={ filling.item }
					busy={ busy === key( filling.source.key, filling.item.id ) }
					onCancel={ () => setFilling( null ) }
					onSubmit={ ( values ) =>
						doImport( filling.source, filling.item, values ).then(
							( message ) => {
								if ( ! message ) {
									setFilling( null );
								}
								return message;
							}
						)
					}
				/>
			) }

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
 * Collect a preset's slot values, then import.
 *
 * Every field starts empty with the preset's own wording as its placeholder.
 * Prefilling the inputs instead would read the same and be worse in two ways:
 * the user could not tell which fields they had actually set, and the import
 * would record all of them as customised when none were touched.
 *
 * Validation is the server's. The one thing enforced here is `maxLength`, and
 * only because a hard stop at the limit is kinder than being told about it
 * after writing a paragraph. Everything else is reported back from the import
 * request and shown below, with the dialog left open on the values that need
 * fixing.
 * @param root0
 * @param root0.item     The sample, with its declared slots.
 * @param root0.busy     Whether the import request is in flight.
 * @param root0.onCancel Dismiss handler.
 * @param root0.onSubmit Import handler; resolves to an error message or ''.
 */
function SlotFormModal( {
	item,
	busy,
	onCancel,
	onSubmit,
}: {
	item: ImportItem;
	busy: boolean;
	onCancel: () => void;
	onSubmit: ( values: Record< string, string > ) => Promise< string >;
} ): JSX.Element {
	const slots = item.slots || {};
	const [ values, setValues ] = useState< Record< string, string > >( {} );
	const [ error, setError ] = useState( '' );

	const set = ( slotKey: string, value: string ) =>
		setValues( ( prev ) => ( { ...prev, [ slotKey ]: value } ) );

	const submit = ( event: any ) => {
		event.preventDefault();
		if ( busy ) {
			return;
		}
		setError( '' );
		onSubmit( values ).then( setError );
	};

	return (
		<Modal
			title={ sprintf(
				/* translators: %s: sample title */
				__( 'Import “%s”', 'flexa-block' ),
				item.title
			) }
			onRequestClose={ busy ? () => undefined : onCancel }
			size="medium"
			className="flexa-samples__slots"
		>
			<form onSubmit={ submit }>
				<p className="flexa-samples__slots-intro">
					{ __(
						'Fill in what you want to change. Anything left blank keeps the wording this sample ships with, and you can edit all of it in the editor afterwards.',
						'flexa-block'
					) }
				</p>

				{ Object.entries( slots ).map( ( [ slotKey, slot ] ) => {
					const fieldId = `flexa-slot-${ item.id }-${ slotKey }`;
					const describedBy = slot.help
						? `${ fieldId }-help`
						: undefined;

					return (
						<div
							key={ slotKey }
							className="flexa-samples__slot-field"
						>
							<label htmlFor={ fieldId }>{ slot.label }</label>
							{ slot.type === 'multiline' ? (
								<textarea
									id={ fieldId }
									rows={ 3 }
									maxLength={ slot.max }
									placeholder={ slot.default }
									aria-describedby={ describedBy }
									value={ values[ slotKey ] || '' }
									onChange={ ( e ) =>
										set( slotKey, e.target.value )
									}
									disabled={ busy }
								/>
							) : (
								<input
									id={ fieldId }
									type={ inputType( slot.type ) }
									maxLength={
										slot.type === 'media'
											? undefined
											: slot.max
									}
									placeholder={ slot.default }
									aria-describedby={ describedBy }
									value={ values[ slotKey ] || '' }
									onChange={ ( e ) =>
										set( slotKey, e.target.value )
									}
									disabled={ busy }
								/>
							) }
							{ slot.help && (
								<p
									id={ describedBy }
									className="flexa-samples__slot-help"
								>
									{ slot.help }
								</p>
							) }
						</div>
					);
				} ) }

				{ error && (
					<div
						className="flexa-samples__notice is-error"
						aria-live="polite"
					>
						{ error }
					</div>
				) }

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
						type="submit"
						className="components-button is-primary"
						disabled={ busy }
					>
						{ busy
							? __( 'Importing…', 'flexa-block' )
							: __( 'Import as draft', 'flexa-block' ) }
					</button>
				</div>
			</form>
		</Modal>
	);
}

/**
 * The input type a slot wants, so the browser offers the right keyboard and
 * its own first-pass check. `text` is the fallback rather than a mapping of
 * last resort: a slot type this bundle does not know about is still a string
 * the server will validate.
 * @param type Declared slot type.
 */
function inputType( type: ImportSlot[ 'type' ] ): string {
	switch ( type ) {
		case 'email':
			return 'email';
		case 'phone':
			return 'tel';
		case 'url':
			return 'url';
		case 'media':
			return 'number';
		default:
			return 'text';
	}
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
						<button
							type="button"
							className="components-button is-tertiary is-destructive"
							onClick={ onRemove }
							disabled={ busy }
						>
							{ __( 'Remove', 'flexa-block' ) }
						</button>
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
