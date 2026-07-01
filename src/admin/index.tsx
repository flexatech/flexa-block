/**
 * Flexa Block - admin settings app.
 *
 * A small dashboard to toggle dark mode, the CSS specificity boost, and
 * enable/disable blocks. Reads bootstrap data from `window.flexaBlockAdmin`
 * and persists via the plugin REST endpoint.
 */

import { createRoot, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import {
	Panel,
	PanelBody,
	PanelRow,
	ToggleControl,
	Button,
	Notice,
	Spinner,
} from '@wordpress/components';

interface AdminNotice {
	status: 'success' | 'error';
	text: string;
}

type Settings = Record< string, any >;

const boot: FlexaBlockAdminData = window.flexaBlockAdmin || {};

if ( boot.nonce ) {
	apiFetch.use( apiFetch.createNonceMiddleware( boot.nonce ) );
}

/**
 * The settings application.
 */
function App(): JSX.Element {
	const [ settings, setSettings ] = useState( boot.settings || {} ) as [ Settings, ( updater: Settings | ( ( s: Settings ) => Settings ) ) => void ];
	const [ saving, setSaving ] = useState( false );
	const [ notice, setNotice ] = useState( null ) as [ AdminNotice | null, ( n: AdminNotice | null ) => void ];

	const blocks = boot.blocks || [];
	const darkMode = settings.dark_mode || {};
	const performance = settings.performance || {};
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

	const save = () => {
		setSaving( true );
		setNotice( null );
		apiFetch( { url: boot.restUrl, method: 'POST', data: settings } )
			.then( ( res: { settings?: Settings } ) => {
				if ( res && res.settings ) {
					setSettings( res.settings );
				}
				setNotice( { status: 'success', text: __( 'Settings saved.', 'flexa-block' ) } );
			} )
			.catch( () => {
				setNotice( { status: 'error', text: __( 'Could not save settings.', 'flexa-block' ) } );
			} )
			.finally( () => setSaving( false ) );
	};

	return (
		<div style={ { maxWidth: 720 } }>
			<h1>{ __( 'Flexa Block', 'flexa-block' ) }</h1>

			{ notice && (
				<Notice status={ notice.status } isDismissible onRemove={ () => setNotice( null ) }>
					{ notice.text }
				</Notice>
			) }

			<Panel>
				<PanelBody title={ __( 'Dark Mode', 'flexa-block' ) } initialOpen>
					<PanelRow>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Output dark mode CSS', 'flexa-block' ) }
							help={ __( 'Master switch. Off = hide dark color pickers and skip dark CSS.', 'flexa-block' ) }
							checked={ darkMode.enabled !== false }
							onChange={ ( v: boolean ) => setDark( 'enabled', v ) }
						/>
					</PanelRow>
					{ darkMode.enabled !== false && (
						<>
							<PanelRow>
								<ToggleControl
									__nextHasNoMarginBottom
									label={ __( 'System preference (prefers-color-scheme)', 'flexa-block' ) }
									checked={ darkMode.colorScheme !== false }
									onChange={ ( v: boolean ) => setDark( 'colorScheme', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<ToggleControl
									__nextHasNoMarginBottom
									label={ __( 'Data attribute ([data-theme="dark"])', 'flexa-block' ) }
									checked={ darkMode.dataTheme === true }
									onChange={ ( v: boolean ) => setDark( 'dataTheme', v ) }
								/>
							</PanelRow>
						</>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Performance', 'flexa-block' ) } initialOpen={ false }>
					<PanelRow>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'CSS specificity boost', 'flexa-block' ) }
							help={ __( 'Prepend "body" to generated selectors so they reliably override theme styles.', 'flexa-block' ) }
							checked={ performance.specificityBoost === true }
							onChange={ ( v: boolean ) => setPerf( 'specificityBoost', v ) }
						/>
					</PanelRow>
				</PanelBody>

				<PanelBody title={ __( 'Blocks', 'flexa-block' ) } initialOpen={ false }>
					{ blocks.map( ( block ) => (
						<PanelRow key={ block.slug }>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ block.title || block.slug }
								help={ block.is_core ? __( 'Core block - always enabled.', 'flexa-block' ) : block.description }
								checked={ ! disabled.includes( block.slug ) }
								disabled={ !! block.is_core }
								onChange={ ( v: boolean ) => toggleBlock( block.slug, v ) }
							/>
						</PanelRow>
					) ) }
				</PanelBody>
			</Panel>

			<p>
				<Button variant="primary" onClick={ save } disabled={ saving }>
					{ saving ? <Spinner /> : __( 'Save settings', 'flexa-block' ) }
				</Button>
			</p>
		</div>
	);
}

const mount = document.getElementById( 'flexa-block-admin' );
if ( mount ) {
	createRoot( mount ).render( <App /> );
}
