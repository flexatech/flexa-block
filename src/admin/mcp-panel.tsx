/**
 * MCP view: the switch that lets external AI agents reach this site.
 *
 * This panel saves on its own, through its own endpoint, and never joins the
 * app's debounced auto-save. Opening a path that an outside agent can call is
 * a deliberate act: it takes a click, a confirmation, and one request that the
 * user can see succeed or fail. Everything it needs to render, including on a
 * WordPress too old to run the module at all, arrives in the boot payload.
 */

import { useState } from '@wordpress/element';
import { __, _n, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Modal } from '@wordpress/components';
import { CardHeader, SettingRow } from './settings-general';

const ADAPTER_URL = 'https://wordpress.org/plugins/mcp-adapter/';

type Patch = Partial< Pick< FlexaBlockMcpData, 'enabled' | 'read' | 'write' > >;
type Feedback = { type: 'error' | 'success'; text: string } | null;

interface Props {
	mcp: FlexaBlockMcpData;
}

/** What a pending confirmation is about. */
type Pending = { patch: Patch; title: string; body: string; action: string };

/**
 * The MCP panel.
 * @param root0     Props.
 * @param root0.mcp Initial module state from the boot payload.
 */
export function McpPanel( { mcp }: Props ): JSX.Element {
	const [ state, setState ] = useState< FlexaBlockMcpData >( mcp );
	const [ busy, setBusy ] = useState( false );
	const [ feedback, setFeedback ] = useState< Feedback >( null );
	const [ pending, setPending ] = useState< Pending | null >( null );

	const save = ( patch: Patch, successText: string ) => {
		setBusy( true );
		setFeedback( null );
		apiFetch( { url: state.restUrl, method: 'POST', data: patch } )
			.then( ( res: any ) => {
				if ( res?.mcp ) {
					setState( res.mcp );
				}
				setFeedback( { type: 'success', text: successText } );
			} )
			.catch( ( err: any ) =>
				setFeedback( {
					type: 'error',
					text:
						err?.message ||
						__( 'Could not save that change.', 'flexa-block' ),
				} )
			)
			.finally( () => {
				setBusy( false );
				setPending( null );
			} );
	};

	const refresh = () => {
		setBusy( true );
		apiFetch( { url: state.restUrl } )
			.then( ( res: any ) => {
				if ( res?.mcp ) {
					setState( res.mcp );
				}
			} )
			.catch( () => undefined )
			.finally( () => setBusy( false ) );
	};

	const setEnabled = ( on: boolean ) => {
		if ( ! on ) {
			save(
				{ enabled: false },
				__(
					'MCP is off. Nothing on this site is reachable by an agent.',
					'flexa-block'
				)
			);
			return;
		}
		setPending( {
			patch: { enabled: true },
			title: __( 'Turn on MCP?', 'flexa-block' ),
			body: __(
				'An AI client you connect will be able to read this site through the tools you allow, using an account you create for it. You can turn this off again at any time.',
				'flexa-block'
			),
			action: __( 'Turn on', 'flexa-block' ),
		} );
	};

	const setWrite = ( on: boolean ) => {
		if ( ! on ) {
			save(
				{ write: false },
				__( 'Agents can no longer create content.', 'flexa-block' )
			);
			return;
		}
		setPending( {
			patch: { write: true },
			title: __( 'Allow agents to create content?', 'flexa-block' ),
			body: __(
				'A connected agent will be able to create pages on this site. Everything it creates arrives as a draft for you to review, and it can never publish.',
				'flexa-block'
			),
			action: __( 'Allow', 'flexa-block' ),
		} );
	};

	return (
		<div className="flexa-mcp">
			<section className="flexa-admin-card flexa-setting-card">
				<CardHeader
					icon="rest-api"
					tint="#7c3aed"
					title={ __( 'AI agents (MCP)', 'flexa-block' ) }
					subtitle={ __(
						'Let an AI client read this site, and optionally draft pages from your presets, over the Model Context Protocol.',
						'flexa-block'
					) }
				/>

				<div className="flexa-setting-card__body">
					{ feedback && (
						<div
							className={ `flexa-mcp__notice is-${ feedback.type }` }
							aria-live="polite"
						>
							{ feedback.text }
						</div>
					) }

					{ ! state.supported ? (
						<Unsupported minWp={ state.minWp } />
					) : (
						<div className="flexa-rows">
							<SettingRow
								label={ __( 'Enable MCP', 'flexa-block' ) }
								help={ __(
									'Off by default. While it is off this plugin registers nothing and answers nothing.',
									'flexa-block'
								) }
								checked={ state.enabled }
								onChange={ setEnabled }
							/>
						</div>
					) }
				</div>
			</section>

			{ state.supported && state.enabled && (
				<>
					{ state.adapter.active ? (
						<Connected
							state={ state }
							busy={ busy }
							onRead={ ( v: boolean ) =>
								save(
									{ read: v },
									v
										? __(
												'Agents can read this site.',
												'flexa-block'
										  )
										: __(
												'Agents can no longer read this site.',
												'flexa-block'
										  )
								)
							}
							onWrite={ setWrite }
						/>
					) : (
						<MissingAdapter />
					) }
				</>
			) }

			{ state.supported && (
				<Activity
					rows={ state.activity ?? [] }
					days={ state.activityDays ?? 0 }
					busy={ busy }
					onRefresh={ refresh }
				/>
			) }

			{ pending && (
				<ConfirmModal
					pending={ pending }
					busy={ busy }
					onCancel={ () => setPending( null ) }
					onConfirm={ () =>
						save( pending.patch, __( 'Saved.', 'flexa-block' ) )
					}
				/>
			) }
		</div>
	);
}

/**
 * State one: the site's WordPress has no Abilities API.
 *
 * The switch is shown rather than hidden, so the feature is discoverable and
 * the reason it cannot be used is in the same place as the switch itself.
 * @param root0       Props.
 * @param root0.minWp Lowest supported WordPress version.
 */
function Unsupported( { minWp }: { minWp: string } ): JSX.Element {
	return (
		<div className="flexa-rows">
			<div className="flexa-setting-row is-disabled">
				<span className="flexa-setting-row__text">
					<span className="flexa-setting-row__label">
						{ __( 'Enable MCP', 'flexa-block' ) }
					</span>
					<span className="flexa-setting-row__help">
						{ sprintf(
							/* translators: %s: minimum WordPress version */
							__(
								'Needs WordPress %s or newer, which is where the Abilities API this is built on lives.',
								'flexa-block'
							),
							minWp
						) }
					</span>
				</span>
				<button
					type="button"
					role="switch"
					aria-checked={ false }
					aria-label={ __( 'Enable MCP', 'flexa-block' ) }
					className="flexa-switch"
					disabled
				>
					<span className="flexa-switch__knob" />
				</button>
			</div>
		</div>
	);
}

/**
 * State three: the module is on, the MCP Adapter plugin is not installed.
 */
function MissingAdapter(): JSX.Element {
	return (
		<section className="flexa-admin-card flexa-setting-card">
			<CardHeader
				icon="admin-plugins"
				tint="#d97706"
				title={ __( 'One plugin still missing', 'flexa-block' ) }
				subtitle={ __(
					'MCP Adapter is what speaks the protocol. Flexa Block does not.',
					'flexa-block'
				) }
			/>
			<div className="flexa-setting-card__body">
				<p className="flexa-mcp__text">
					{ __(
						'The MCP server, its transport and its authentication belong to the MCP Adapter plugin, maintained on WordPress.org. Install and activate it, and the tools this module registers become reachable by an MCP client.',
						'flexa-block'
					) }
				</p>
				<p className="flexa-mcp__text">
					{ __(
						'Until then nothing is lost: the same tools are already registered with WordPress core, so they can be listed and run through the core abilities REST routes with an account that has permission.',
						'flexa-block'
					) }
				</p>
				<p>
					<a
						className="components-button is-secondary"
						href={ ADAPTER_URL }
						target="_blank"
						rel="noreferrer"
					>
						{ __( 'View MCP Adapter', 'flexa-block' ) }
						<span
							className="flexa-mcp__ext dashicons dashicons-external"
							aria-hidden="true"
						/>
					</a>
				</p>
			</div>
		</section>
	);
}

/**
 * State four: the module is on and the adapter is active.
 *
 * The scope note is the important part of this card. Someone turning these on
 * should be able to read, in one place, what a connected agent can and cannot
 * reach.
 * @param root0         Props.
 * @param root0.state   Current module state.
 * @param root0.busy    Whether a save is in flight.
 * @param root0.onRead  Read toggle handler.
 * @param root0.onWrite Write toggle handler.
 */
function Connected( {
	state,
	busy,
	onRead,
	onWrite,
}: {
	state: FlexaBlockMcpData;
	busy: boolean;
	onRead: ( value: boolean ) => void;
	onWrite: ( value: boolean ) => void;
} ): JSX.Element {
	return (
		<>
			<section className="flexa-admin-card flexa-setting-card">
				<CardHeader
					icon="shield"
					tint="#0d9488"
					title={ __( 'What agents may do', 'flexa-block' ) }
					subtitle={
						state.adapter.version
							? sprintf(
									/* translators: %s: MCP Adapter version */
									__(
										'Served by MCP Adapter %s.',
										'flexa-block'
									),
									state.adapter.version
							  )
							: __( 'Served by MCP Adapter.', 'flexa-block' )
					}
				/>
				<div className="flexa-setting-card__body">
					<div className="flexa-rows">
						<SettingRow
							label={ __( 'Read', 'flexa-block' ) }
							help={ __(
								'Site design settings, the list of presets, and the block structure of a page an agent asks about.',
								'flexa-block'
							) }
							checked={ state.read }
							onChange={ ( v ) => ! busy && onRead( v ) }
						/>
						<SettingRow
							label={ __( 'Create drafts', 'flexa-block' ) }
							help={ __(
								'Create a page from one of your presets. Drafts only, never published, never an existing page overwritten.',
								'flexa-block'
							) }
							checked={ state.write }
							onChange={ ( v ) => ! busy && onWrite( v ) }
						/>
					</div>

					<p className="flexa-mcp__text">
						{ __(
							'An agent acts as the WordPress user whose credentials it was given, and can do nothing that user cannot do. Create a dedicated account with the lowest role that fits, give it an application password, and revoke that password to cut access off.',
							'flexa-block'
						) }
					</p>

					<p className="flexa-mcp__text">
						{ __(
							'These two switches cover the tools Flexa Block registers. MCP Adapter also serves tools that WordPress and other active plugins register, which these switches do not govern, so the role on that account is what decides how far a client can reach.',
							'flexa-block'
						) }
					</p>

					<p className="flexa-mcp__text flexa-mcp__text--muted">
						{ __(
							'Every call is recorded below, with the user, the tool, the page and the outcome.',
							'flexa-block'
						) }
					</p>
				</div>
			</section>

			{ state.endpoint && (
				<section className="flexa-admin-card flexa-setting-card">
					<CardHeader
						icon="admin-links"
						tint="#6366f1"
						title={ __( 'Endpoint', 'flexa-block' ) }
						subtitle={ __(
							'The address an MCP client connects to.',
							'flexa-block'
						) }
					/>
					<div className="flexa-setting-card__body">
						<code className="flexa-mcp__endpoint">
							{ state.endpoint }
						</code>
					</div>
				</section>
			) }
		</>
	);
}

/**
 * A word for one recorded outcome.
 *
 * Written for whoever reads the panel rather than for whoever wrote the hook:
 * `refused` and `denied` both mean the call did not happen, and the difference
 * between a request this module rejected and a capability the account lacks is
 * worth saying in the row itself.
 * @param outcome Stored outcome.
 */
function outcomeLabel( outcome: string ): string {
	switch ( outcome ) {
		case 'ok':
			return __( 'Done', 'flexa-block' );
		case 'refused':
			return __( 'Refused', 'flexa-block' );
		case 'denied':
			return __( 'Not allowed', 'flexa-block' );
		default:
			return __( 'Unfinished', 'flexa-block' );
	}
}

/**
 * The log of calls this module handled.
 *
 * Shown whether the module is on or off, because the question "what did it do
 * while it was on" arrives after the switch has been turned off. Read-only on
 * purpose: there is no clear button, since a log someone can erase from the
 * screen it is displayed on answers nothing later.
 * @param root0           Props.
 * @param root0.rows      Recorded calls, newest first.
 * @param root0.days      Days a row is kept, 0 for no age limit.
 * @param root0.busy      Whether a request is in flight.
 * @param root0.onRefresh Re-read handler.
 */
function Activity( {
	rows,
	days,
	busy,
	onRefresh,
}: {
	rows: FlexaBlockMcpActivityRow[];
	days: number;
	busy: boolean;
	onRefresh: () => void;
} ): JSX.Element {
	return (
		<section className="flexa-admin-card flexa-setting-card">
			<CardHeader
				icon="list-view"
				tint="#475569"
				title={ __( 'Recent activity', 'flexa-block' ) }
				subtitle={
					days > 0
						? sprintf(
								/* translators: %d: number of days a row is kept */
								_n(
									'Calls to this plugin’s tools, kept for %d day.',
									'Calls to this plugin’s tools, kept for %d days.',
									days,
									'flexa-block'
								),
								days
						  )
						: __( 'Calls to this plugin’s tools.', 'flexa-block' )
				}
			/>
			<div className="flexa-setting-card__body">
				{ rows.length === 0 ? (
					<p className="flexa-mcp__text flexa-mcp__text--muted">
						{ __(
							'Nothing recorded yet. A row appears here the first time a client calls one of these tools.',
							'flexa-block'
						) }
					</p>
				) : (
					<table className="flexa-mcp__log">
						<thead>
							<tr>
								<th scope="col">
									{ __( 'When', 'flexa-block' ) }
								</th>
								<th scope="col">
									{ __( 'Who', 'flexa-block' ) }
								</th>
								<th scope="col">
									{ __( 'Tool', 'flexa-block' ) }
								</th>
								<th scope="col">
									{ __( 'Page', 'flexa-block' ) }
								</th>
								<th scope="col">
									{ __( 'Outcome', 'flexa-block' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ rows.map( ( row, index ) => (
								<tr key={ `${ row.request }-${ index }` }>
									<td>{ row.when }</td>
									<td>{ row.user }</td>
									<td>
										<code>{ row.ability }</code>
									</td>
									<td>
										{ row.post ? `#${ row.post }` : '' }
									</td>
									<td>
										<span
											className={ `flexa-mcp__outcome is-${ row.outcome }` }
										>
											{ outcomeLabel( row.outcome ) }
										</span>
										{ row.code && (
											<span className="flexa-mcp__code">
												{ row.code }
											</span>
										) }
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }

				<p className="flexa-mcp__log-actions">
					<button
						type="button"
						className="components-button is-secondary"
						onClick={ onRefresh }
						disabled={ busy }
					>
						{ busy
							? __( 'Refreshing…', 'flexa-block' )
							: __( 'Refresh', 'flexa-block' ) }
					</button>
				</p>

				<p className="flexa-mcp__text flexa-mcp__text--muted">
					{ __(
						'The log keeps the user, the tool, the page and the outcome. It keeps no page content, no prompts and no credentials, and it is not a way to undo what a call did.',
						'flexa-block'
					) }
				</p>
			</div>
		</section>
	);
}

/**
 * Confirmation for an action that widens what an agent can do.
 * @param root0           Props.
 * @param root0.pending   The action awaiting confirmation.
 * @param root0.busy      Whether the request is in flight.
 * @param root0.onCancel  Dismiss handler.
 * @param root0.onConfirm Confirm handler.
 */
function ConfirmModal( {
	pending,
	busy,
	onCancel,
	onConfirm,
}: {
	pending: Pending;
	busy: boolean;
	onCancel: () => void;
	onConfirm: () => void;
} ): JSX.Element {
	return (
		<Modal
			title={ pending.title }
			onRequestClose={ busy ? () => undefined : onCancel }
			size="small"
			className="flexa-mcp__confirm"
		>
			<p className="flexa-mcp__confirm-text">{ pending.body }</p>
			<div className="flexa-mcp__confirm-actions">
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
					className="components-button is-primary"
					onClick={ onConfirm }
					disabled={ busy }
				>
					{ busy ? __( 'Saving…', 'flexa-block' ) : pending.action }
				</button>
			</div>
		</Modal>
	);
}
