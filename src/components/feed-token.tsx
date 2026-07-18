/**
 * Feed token connection control — shared by the Facebook / Instagram feed blocks.
 *
 * The access token is a secret kept in the admin-only server-side `Feed_Tokens`
 * store. The browser only ever learns whether the service is configured (a
 * boolean, localised as `window.flexaBlockFeedTokens`) and, for administrators, a
 * write-only password field to set / replace it via the `/feed-token` REST route.
 * The token itself is never sent to the browser. Saving fires a `flexa:feed-token`
 * DOM event so the block's live preview can refresh without a reload.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { TextControl, Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

interface FeedTokenBoot {
	canManage?: boolean;
	configured?: Record< string, boolean >;
}

interface FeedTokenControlProps {
	/** Service key stored server-side. */
	service: 'facebook' | 'instagram';
	/** Field label for the token input. */
	label: string;
}

/** Read the localised bootstrap (configured status + capability). */
const readBoot = (): FeedTokenBoot =>
	( window as unknown as { flexaBlockFeedTokens?: FeedTokenBoot } ).flexaBlockFeedTokens || {};

/**
 * The connection status + (admin-only) token field for one feed service.
 */
export const FeedTokenControl = ( { service, label }: FeedTokenControlProps ): JSX.Element => {
	const boot = readBoot();
	const canManage = !! boot.canManage;
	const [ configured, setConfigured ] = useState< boolean >( !! boot.configured?.[ service ] );
	const [ token, setToken ] = useState( '' );
	const [ busy, setBusy ] = useState( false );
	const [ message, setMessage ] = useState( '' );

	const emit = ( value: boolean ): void => {
		document.dispatchEvent( new CustomEvent( 'flexa:feed-token', { detail: { service, configured: value } } ) );
	};

	const save = ( value: string ): void => {
		setBusy( true );
		setMessage( '' );
		apiFetch( { path: '/flexa-block/v1/feed-token', method: 'POST', data: { service, token: value } } )
			.then( ( res: unknown ) => {
				const ok = !! ( res as { configured?: boolean } )?.configured;
				setConfigured( ok );
				setToken( '' );
				setMessage( value ? __( 'Token saved.', 'flexa-block' ) : __( 'Disconnected.', 'flexa-block' ) );
				emit( ok );
			} )
			.catch( () => setMessage( __( 'Could not save — administrator permission required.', 'flexa-block' ) ) )
			.finally( () => setBusy( false ) );
	};

	return (
		<div className="flexa-feed-token">
			<p className={ 'flexa-feed-token__status' + ( configured ? ' is-connected' : '' ) }>
				{ configured ? __( '● Connected', 'flexa-block' ) : __( '○ Not connected', 'flexa-block' ) }
			</p>
			{ canManage ? (
				<>
					<TextControl
						__nextHasNoMarginBottom
						type="password"
						label={ label }
						value={ token }
						placeholder={ configured ? __( 'Saved — leave blank to keep', 'flexa-block' ) : '' }
						help={ __( 'Stored securely on the server — never saved in this page, its export, or the front end.', 'flexa-block' ) }
						onChange={ setToken }
					/>
					<div className="flexa-feed-token__actions">
						<Button variant="secondary" isBusy={ busy } disabled={ busy || ! token.trim() } onClick={ () => save( token.trim() ) }>
							{ __( 'Save token', 'flexa-block' ) }
						</Button>
						{ configured && (
							<Button variant="tertiary" isDestructive disabled={ busy } onClick={ () => save( '' ) }>
								{ __( 'Disconnect', 'flexa-block' ) }
							</Button>
						) }
					</div>
					{ message && <p className="flexa-feed-token__msg">{ message }</p> }
				</>
			) : (
				<p className="flexa-feed-token__note">
					{ configured
						? __( 'Connected by an administrator.', 'flexa-block' )
						: __( 'Ask an administrator to connect the account before this feed can show live posts.', 'flexa-block' ) }
				</p>
			) }
		</div>
	);
};
