/**
 * Review panel for a working copy an AI agent made of a published page.
 *
 * Loaded only when the post open in the editor is such a copy (see
 * `Working_Copy::enqueue()`). It says plainly what the post is, links to the
 * live page, and offers the one action that puts the copy live: applying it to
 * the original. Applying is deliberately a person's button, never an agent's.
 *
 * When the original has changed since the copy was made, applying would undo
 * that change, so the panel says so and asks for a second, explicit
 * confirmation instead of applying.
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { Button, CheckboxControl, Notice } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import { Highlight } from '../shared/text-diff';

/** One front-end edit made in this copy (from the edit log). */
interface ChangeEntry {
	id: string;
	time: string;
	time_display?: string;
	user_name: string;
	role_label?: string;
	block: string;
	attr: string;
	kind?: string;
	action?: string;
	before: string;
	after: string;
}

/**
 * Plain text of a stored value.
 *
 * @param html Stored value.
 */
function plain( html: string ): string {
	return (
		new DOMParser().parseFromString( html, 'text/html' ).body.textContent || ''
	).trim();
}

/**
 * The changes made in this copy from the front end, oldest first, so a
 * reviewer reads them in the order they were made and does not have to look
 * for them on the page.
 *
 * @param props         Component props.
 * @param props.logPath REST path of this copy's edit log.
 */
function ChangeList( { logPath }: { logPath: string } ): JSX.Element | null {
	const [ entries, setEntries ] = useState< ChangeEntry[] | null >( null );

	useEffect( () => {
		( apiFetch as ( o: { path: string } ) => Promise< { entries: ChangeEntry[] } > )( {
			path: logPath,
		} )
			.then( ( res ) => setEntries( ( res.entries || [] ).slice().reverse() ) )
			.catch( () => setEntries( [] ) );
	}, [ logPath ] );

	if ( ! entries || ! entries.length ) {
		return null;
	}

	return (
		<div style={ { margin: '12px 0' } }>
			<p style={ { fontWeight: 600, margin: '0 0 6px' } }>
				{ sprintf(
					/* translators: %d: number of changes */
					__( 'Changes to review (%d)', 'flexa-block' ),
					entries.length
				) }
			</p>
			<ol style={ { margin: 0, paddingLeft: 18, maxHeight: 360, overflowY: 'auto' } }>
				{ entries.map( ( e ) => (
					<li key={ e.id } style={ { margin: '0 0 10px', fontSize: 12 } }>
						<div style={ { color: '#757575' } }>
							{ e.block } · { e.attr }
							{ e.action === 'undo' ? ` · ${ __( 'Undo', 'flexa-block' ) }` : '' }
							{ ' · ' }
							{ e.user_name }
							{ e.role_label ? ` (${ e.role_label })` : '' }
							{ ' · ' }
							{ e.time_display || e.time }
						</div>
						<div style={ { background: '#fff5f5', padding: '2px 6px', borderRadius: 4, margin: '2px 0' } }>
							<Highlight before={ plain( e.before ) } after={ plain( e.after ) } side="before" />
						</div>
						<div style={ { background: '#f1faf3', padding: '2px 6px', borderRadius: 4 } }>
							<Highlight before={ plain( e.before ) } after={ plain( e.after ) } side="after" />
						</div>
					</li>
				) ) }
			</ol>
		</div>
	);
}

interface WorkingCopyData {
	copyId: number;
	original: {
		id: number;
		title: string;
		status: string;
		editLink: string;
		viewLink: string;
	};
	conflicted: boolean;
	canApply: boolean;
	path: string;
	// Front-end review copies: why this person may not apply it, and the log.
	blockedReason?: string;
	isReview?: boolean;
	logPath?: string;
}

interface ApplyResult {
	original_id: number;
	edit_link: string;
	view_link: string;
}

interface ApplyError {
	code?: string;
	message?: string;
}

declare global {
	interface Window {
		flexaBlockWorkingCopy?: WorkingCopyData;
	}
}

const NOTICE_ID = 'flexa-block-working-copy';

/**
 * The panel itself.
 */
function WorkingCopyPanel(): JSX.Element | null {
	const data = window.flexaBlockWorkingCopy;

	const [ busy, setBusy ] = useState( false );
	const [ conflicted, setConflicted ] = useState(
		Boolean( data?.conflicted )
	);
	const [ overwrite, setOverwrite ] = useState( false );
	const [ error, setError ] = useState( '' );

	const isDirty = useSelect(
		( select: any ) => select( 'core/editor' ).isEditedPostDirty(),
		[]
	);
	const { savePost } = useDispatch( 'core/editor' ) as {
		savePost: () => Promise< void >;
	};
	const { createNotice } = useDispatch( 'core/notices' ) as {
		createNotice: (
			status: string,
			content: string,
			options: Record< string, unknown >
		) => void;
	};

	// One persistent line at the top of the editor, so nobody mistakes this
	// draft for the page itself or publishes it as a second copy of it.
	useEffect( () => {
		if ( ! data ) {
			return;
		}
		createNotice(
			'info',
			sprintf(
				/* translators: %s: title of the original page */
				__(
					'This is a draft copy with changes for the published page “%s”. Visitors still see the published page. Review the changes, then use “Apply to original” in the sidebar; do not publish this copy on its own.',
					'flexa-block'
				),
				data.original.title
			),
			{ id: NOTICE_ID, isDismissible: false }
		);
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	if ( ! data ) {
		return null;
	}

	const apply = async () => {
		setBusy( true );
		setError( '' );

		try {
			// The person may have adjusted the copy before applying; what gets
			// applied is what is saved, so save first.
			if ( isDirty ) {
				await savePost();
			}

			const result = ( await apiFetch( {
				path: data.path,
				method: 'POST',
				data: { force: conflicted && overwrite },
			} ) ) as ApplyResult;

			window.location.href = result.edit_link || data.original.editLink;
		} catch ( e ) {
			const err = e as ApplyError;
			if ( err.code === 'flexa_working_copy_conflict' ) {
				setConflicted( true );
			}
			setError(
				err.message ||
					__( 'The copy could not be applied.', 'flexa-block' )
			);
			setBusy( false );
		}
	};

	return (
		<PluginDocumentSettingPanel
			name="flexa-block-working-copy"
			title={ __( 'Changes for a published page', 'flexa-block' ) }
			initialOpen
		>
			<p>
				{ sprintf(
					/* translators: %s: title of the original page */
					__(
						'This draft holds changes for “%s”, which is published. Nothing on the live page changes until you apply this copy.',
						'flexa-block'
					),
					data.original.title
				) }
			</p>
			<p>
				<a
					href={ data.original.viewLink }
					target="_blank"
					rel="noreferrer"
				>
					{ __( 'View the live page', 'flexa-block' ) }
				</a>
				{ ' · ' }
				<a href={ data.original.editLink }>
					{ __( 'Open the original', 'flexa-block' ) }
				</a>
			</p>

			{ data.logPath && <ChangeList logPath={ data.logPath } /> }

			{ conflicted && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'The published page has been changed since this copy was made. Applying the copy replaces those changes.',
						'flexa-block'
					) }
				</Notice>
			) }
			{ conflicted && (
				<CheckboxControl
					label={ __(
						'Apply anyway and replace those changes',
						'flexa-block'
					) }
					checked={ overwrite }
					onChange={ setOverwrite }
				/>
			) }

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			{ data.canApply ? (
				<Button
					variant="primary"
					isBusy={ busy }
					disabled={ busy || ( conflicted && ! overwrite ) }
					onClick={ apply }
				>
					{ __( 'Apply to original', 'flexa-block' ) }
				</Button>
			) : (
				<p>
					{ data.blockedReason ||
						__(
							'Someone who can publish the original page has to apply this copy.',
							'flexa-block'
						) }
				</p>
			) }

			<p className="components-base-control__help">
				{ __(
					'Applying copies this draft’s title and content onto the published page, keeps the old version as a revision, and deletes this draft. To discard the changes instead, move this draft to the trash.',
					'flexa-block'
				) }
			</p>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'flexa-block-working-copy', {
	render: WorkingCopyPanel,
	icon: 'backup',
} );
