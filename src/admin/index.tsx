/**
 * Flexa Block — admin settings app.
 *
 * A sidebar dashboard: a left rail switches between "General" (dark mode +
 * performance) and "Blocks" (a searchable, group-filtered card grid), with the
 * active panel rendered on the right. Changes auto-save (debounced) to the
 * plugin REST endpoint; bootstrap data comes from `window.flexaBlockAdmin`.
 */

import { createRoot, useMemo, useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Spinner } from '@wordpress/components';
import { BlocksPanel } from './blocks-panel';
import { GeneralSettings } from './settings-general';
import { EditingSettings } from './settings-editing';
import './admin.scss';

type Settings = Record< string, any >;
type SaveStatus = 'idle' | 'saving' | 'saved' | 'error';
type View = 'general' | 'blocks' | 'editing';

const SAVE_DEBOUNCE_MS = 700;

const boot: FlexaBlockAdminData = window.flexaBlockAdmin || {};

if ( boot.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( boot.nonce ) );
}

/**
 * The settings application.
 */
function App(): JSX.Element {
	const [ settings, setSettings ] = useState( boot.settings || {} ) as [
		Settings,
		( updater: Settings | ( ( s: Settings ) => Settings ) ) => void,
	];
	const [ status, setStatus ] = useState( 'idle' ) as [
		SaveStatus,
		( s: SaveStatus ) => void,
	];
	const [ view, setView ] = useState( 'general' ) as [
		View,
		( v: View ) => void,
	];

	const isFirstRender = useRef( true );
	const timer = useRef( 0 );
	const dismiss = useRef( 0 );

	const persist = ( data: Settings ) => {
		setStatus( 'saving' );
		window.clearTimeout( dismiss.current );
		apiFetch( { url: boot.restUrl, method: 'POST', data } )
			.then( () => {
				setStatus( 'saved' );
				// Auto-hide the success toast; errors stay until retried.
				dismiss.current = window.setTimeout(
					() => setStatus( 'idle' ),
					2500
				);
			} )
			.catch( () => setStatus( 'error' ) );
	};

	// Debounced auto-save: fire whenever settings change (but not on mount).
	useEffect( () => {
		if ( isFirstRender.current ) {
			isFirstRender.current = false;
			return;
		}
		setStatus( 'saving' );
		window.clearTimeout( timer.current );
		timer.current = window.setTimeout(
			() => persist( settings ),
			SAVE_DEBOUNCE_MS
		);
		return () => window.clearTimeout( timer.current );
	}, [ settings ] );

	const blocks = boot.blocks || [];
	const disabled: string[] = settings.disabled_blocks || [];

	const setDark = ( key: string, value: boolean ) =>
		setSettings( ( s: Settings ) => ( {
			...s,
			dark_mode: { ...s.dark_mode, [ key ]: value },
		} ) );

	const setPerf = ( key: string, value: boolean ) =>
		setSettings( ( s: Settings ) => ( {
			...s,
			performance: { ...s.performance, [ key ]: value },
		} ) );

	// Core blocks can never be disabled, so bulk actions skip them.
	const coreSlugs = useMemo(
		() =>
			new Set(
				blocks.filter( ( b ) => b.is_core ).map( ( b ) => b.slug )
			),
		[ blocks ]
	);

	const toggleBlock = ( slug: string, enabled: boolean ) =>
		setSettings( ( s: Settings ) => {
			const list = new Set< string >( s.disabled_blocks || [] );
			if ( enabled ) {
				list.delete( slug );
			} else {
				list.add( slug );
			}
			return { ...s, disabled_blocks: Array.from( list ) };
		} );

	const setInline = ( key: string, value: unknown ) =>
		setSettings( ( s: Settings ) => ( {
			...s,
			inline_editor: { ...s.inline_editor, [ key ]: value },
		} ) );

	const toggleRole = ( slug: string, allowed: boolean ) =>
		setSettings( ( s: Settings ) => {
			const list = new Set< string >( s.inline_editor?.roles || [] );
			if ( allowed ) {
				list.add( slug );
			} else {
				list.delete( slug );
			}
			return {
				...s,
				inline_editor: { ...s.inline_editor, roles: Array.from( list ) },
			};
		} );

	const toggleEditableBlock = ( slug: string, enabled: boolean ) =>
		setSettings( ( s: Settings ) => {
			const list = new Set< string >(
				s.inline_editor?.disabled_blocks || []
			);
			if ( enabled ) {
				list.delete( slug );
			} else {
				list.add( slug );
			}
			return {
				...s,
				inline_editor: {
					...s.inline_editor,
					disabled_blocks: Array.from( list ),
				},
			};
		} );

	// Enable/disable many blocks in one state update (one debounced save).
	const toggleMany = ( slugs: string[], enabled: boolean ) =>
		setSettings( ( s: Settings ) => {
			const list = new Set< string >( s.disabled_blocks || [] );
			slugs.forEach( ( slug ) => {
				if ( enabled ) {
					list.delete( slug );
				} else if ( ! coreSlugs.has( slug ) ) {
					list.add( slug );
				}
			} );
			return { ...s, disabled_blocks: Array.from( list ) };
		} );

	return (
		<div className="flexa-app">
			<aside className="flexa-sidebar">
				<div className="flexa-sidebar__brand">
					<span
						className="flexa-sidebar__logo dashicons dashicons-layout"
						aria-hidden="true"
					/>
					<div>
						<h1 className="flexa-sidebar__title">
							{ __( 'Flexa Block', 'flexa-block' ) }
						</h1>
						{ boot.version && (
							<span className="flexa-sidebar__version">
								v{ boot.version }
							</span>
						) }
					</div>
				</div>

				<nav className="flexa-sidebar__nav" aria-label={ __( 'Settings sections', 'flexa-block' ) }>
					<NavItem
						icon="admin-settings"
						label={ __( 'General', 'flexa-block' ) }
						desc={ __( 'Dark mode & performance', 'flexa-block' ) }
						active={ view === 'general' }
						onClick={ () => setView( 'general' ) }
					/>
					<NavItem
						icon="screenoptions"
						label={ __( 'Blocks', 'flexa-block' ) }
						desc={ __( 'Enable or disable blocks', 'flexa-block' ) }
						active={ view === 'blocks' }
						onClick={ () => setView( 'blocks' ) }
					/>
					<NavItem
						icon="edit-page"
						label={ __( 'Editing', 'flexa-block' ) }
						desc={ __( 'Front-end inline editing', 'flexa-block' ) }
						active={ view === 'editing' }
						onClick={ () => setView( 'editing' ) }
					/>
				</nav>
			</aside>

			<main className="flexa-content">
				{ view === 'general' && (
					<GeneralSettings
						settings={ settings }
						onDark={ setDark }
						onPerf={ setPerf }
					/>
				) }
				{ view === 'blocks' && (
					<BlocksPanel
						blocks={ blocks }
						disabled={ disabled }
						onToggle={ toggleBlock }
						onToggleMany={ toggleMany }
					/>
				) }
				{ view === 'editing' && (
					<EditingSettings
						settings={ settings }
						editableBlocks={ boot.editableBlocks || [] }
						blocks={ blocks }
						roles={ boot.roles || [] }
						onSetEnabled={ ( v: boolean ) =>
							setInline( 'enabled', v )
						}
						onToggleRole={ toggleRole }
						onToggleBlock={ toggleEditableBlock }
					/>
				) }
			</main>

			<div className="flexa-toast" aria-live="polite">
				<SaveStatus
					status={ status }
					onRetry={ () => persist( settings ) }
				/>
			</div>
		</div>
	);
}

/**
 * A single sidebar navigation item.
 * @param root0
 * @param root0.icon   Dashicon slug (without the `dashicons-` prefix).
 * @param root0.label  Item label.
 * @param root0.desc   Small helper line.
 * @param root0.active Whether this item is the current view.
 * @param root0.onClick Selection handler.
 */
function NavItem( {
	icon,
	label,
	desc,
	active,
	onClick,
}: {
	icon: string;
	label: string;
	desc: string;
	active: boolean;
	onClick: () => void;
} ): JSX.Element {
	return (
		<button
			type="button"
			className={ `flexa-nav-item${ active ? ' is-active' : '' }` }
			aria-current={ active ? 'page' : undefined }
			onClick={ onClick }
		>
			<span
				className={ `flexa-nav-item__icon dashicons dashicons-${ icon }` }
				aria-hidden="true"
			/>
			<span className="flexa-nav-item__text">
				<span className="flexa-nav-item__label">{ label }</span>
				<span className="flexa-nav-item__desc">{ desc }</span>
			</span>
		</button>
	);
}

/**
 * Auto-save status indicator (fixed toast).
 * @param root0
 * @param root0.status  Current save status.
 * @param root0.onRetry Retry handler for the error state.
 */
function SaveStatus( {
	status,
	onRetry,
}: {
	status: SaveStatus;
	onRetry: () => void;
} ): JSX.Element | null {
	if ( status === 'idle' ) {
		return null;
	}
	if ( status === 'saving' ) {
		return (
			<span className="flexa-save flexa-save--saving">
				<Spinner />
				{ __( 'Saving…', 'flexa-block' ) }
			</span>
		);
	}
	if ( status === 'saved' ) {
		return (
			<span className="flexa-save flexa-save--saved">
				<span
					className="dashicons dashicons-yes-alt"
					aria-hidden="true"
				/>
				{ __( 'All changes saved', 'flexa-block' ) }
			</span>
		);
	}
	return (
		<span className="flexa-save flexa-save--error">
			<span className="dashicons dashicons-warning" aria-hidden="true" />
			{ __( 'Could not save.', 'flexa-block' ) }
			<button
				type="button"
				className="flexa-save__retry"
				onClick={ onRetry }
			>
				{ __( 'Retry', 'flexa-block' ) }
			</button>
		</span>
	);
}

const mount = document.getElementById( 'flexa-block-admin' );
if ( mount ) {
	createRoot( mount ).render( <App /> );
}
