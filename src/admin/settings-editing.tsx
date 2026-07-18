/**
 * Front-End Editing view — configure the inline (front-end) text editor.
 *
 * A master on/off switch, the roles allowed to edit inline (administrators are
 * always allowed and not listed), and a per-block enable list. All state lives
 * in the parent; this component reads the current `inline_editor` settings and
 * calls back on change.
 */

import { useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { CardHeader } from './settings-general';
import { FilterPill } from './blocks-panel';
import { BlockThumb } from './block-thumb';
import { GROUP_ORDER, groupMeta, groupOf } from './block-groups';

type Settings = Record< string, any >;

interface Props {
	settings: Settings;
	editableBlocks: string[];
	blocks: FlexaBlockAdminBlock[];
	roles: FlexaBlockAdminRole[];
	onSetEnabled: ( value: boolean ) => void;
	onToggleRole: ( slug: string, allowed: boolean ) => void;
	onToggleBlock: ( slug: string, enabled: boolean ) => void;
}

/**
 * The Front-End Editing settings panel.
 * @param root0
 * @param root0.settings       Current settings tree.
 * @param root0.editableBlocks Slugs of blocks that support inline editing.
 * @param root0.blocks         Full block catalog (for titles).
 * @param root0.roles          Site roles (excluding administrator).
 * @param root0.onSetEnabled   Toggle the master switch.
 * @param root0.onToggleRole   Allow/disallow a role.
 * @param root0.onToggleBlock  Enable/disable a block for inline editing.
 */
export function EditingSettings( {
	settings,
	editableBlocks,
	blocks,
	roles,
	onSetEnabled,
	onToggleRole,
	onToggleBlock,
}: Props ): JSX.Element {
	const ie = settings.inline_editor || {};
	const enabled = ie.enabled !== false;
	const disabledBlocks: string[] = ie.disabled_blocks || [];
	const allowedRoles: string[] = ie.roles || [];

	const [ group, setGroup ] = useState( 'all' );
	const [ query, setQuery ] = useState( '' );

	// Editable slugs mapped to their catalog entries, grouped for the filter.
	const editableCatalog = useMemo(
		() =>
			editableBlocks
				.map( ( slug ) => blocks.find( ( b ) => b.slug === slug ) )
				.filter( Boolean ) as FlexaBlockAdminBlock[],
		[ editableBlocks, blocks ]
	);

	const groupCounts = useMemo( () => {
		const counts: Record< string, number > = {};
		editableCatalog.forEach( ( b ) => {
			const g = groupOf( b );
			counts[ g ] = ( counts[ g ] || 0 ) + 1;
		} );
		return counts;
	}, [ editableCatalog ] );

	const groups = useMemo(
		() => GROUP_ORDER.filter( ( g ) => groupCounts[ g ] > 0 ),
		[ groupCounts ]
	);

	const visibleBlocks = useMemo( () => {
		const q = query.trim().toLowerCase();
		return editableCatalog.filter( ( b ) => {
			if ( group !== 'all' && groupOf( b ) !== group ) {
				return false;
			}
			if ( ! q ) {
				return true;
			}
			const hay = `${ b.title || '' } ${ b.slug } ${
				b.description || ''
			}`.toLowerCase();
			return hay.includes( q );
		} );
	}, [ editableCatalog, group, query ] );

	return (
		<div className="flexa-editing">
			<section className="flexa-admin-card flexa-setting-card">
				<CardHeader
					icon="edit-page"
					tint="#2563eb"
					title={ __( 'Front-End Editing', 'flexa-block' ) }
					subtitle={ __(
						'Let permitted users edit block text directly on the live site.',
						'flexa-block'
					) }
				/>
				<div className="flexa-setting-card__body">
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Enable front-end editing', 'flexa-block' ) }
						help={ __(
							'Master switch. Off = no inline editing UI is loaded on the site.',
							'flexa-block'
						) }
						checked={ enabled }
						onChange={ onSetEnabled }
					/>
				</div>
			</section>

			{ enabled && (
				<>
					<section className="flexa-admin-card flexa-setting-card">
						<CardHeader
							icon="groups"
							tint="#0d9488"
							title={ __( 'Allowed roles', 'flexa-block' ) }
							subtitle={ __(
								'Administrators always have access. Choose which other roles may edit inline (they still need permission to edit the post).',
								'flexa-block'
							) }
						/>
						<div className="flexa-setting-card__body">
							{ roles.length === 0 ? (
								<p className="flexa-editing__empty">
									{ __(
										'No other roles found.',
										'flexa-block'
									) }
								</p>
							) : (
								<div className="flexa-roles">
									<RoleRow
										role={ {
											slug: 'administrator',
											name: __(
												'Administrator',
												'flexa-block'
											),
										} }
										allowed
										locked
										onToggle={ () => undefined }
									/>
									{ roles.map( ( role ) => (
										<RoleRow
											key={ role.slug }
											role={ role }
											allowed={ allowedRoles.includes(
												role.slug
											) }
											onToggle={ onToggleRole }
										/>
									) ) }
								</div>
							) }
						</div>
					</section>

					<section className="flexa-admin-card flexa-setting-card">
						<CardHeader
							icon="screenoptions"
							tint="#7c3aed"
							title={ __( 'Editable blocks', 'flexa-block' ) }
							subtitle={ __(
								'Which block types can be edited from the front end.',
								'flexa-block'
							) }
						/>
						<div className="flexa-setting-card__body">
							<p className="flexa-editing__note">
								{ __(
									'Only blocks with editable text are listed. Media, layout and auto-generated blocks (Image, Gallery, Container, Post Grid…) aren’t shown — they have no text to edit.',
									'flexa-block'
								) }
							</p>
							<div>
								<div className="flexa-blocks__toolbar">
									<div
										className="flexa-blocks__filters"
										role="tablist"
									>
										<FilterPill
											label={ __( 'All', 'flexa-block' ) }
											count={ editableCatalog.length }
											active={ group === 'all' }
											onClick={ () => setGroup( 'all' ) }
										/>
										{ groups.map( ( g ) => (
											<FilterPill
												key={ g }
												label={ groupMeta( g ).label }
												count={ groupCounts[ g ] }
												accent={ groupMeta( g ).accent }
												active={ group === g }
												onClick={ () => setGroup( g ) }
											/>
										) ) }
									</div>
									<div className="flexa-blocks__search">
										<span
											className="dashicons dashicons-search"
											aria-hidden="true"
										/>
										<input
											type="search"
											value={ query }
											onChange={ ( e ) =>
												setQuery( e.target.value )
											}
											placeholder={ __(
												'Search blocks…',
												'flexa-block'
											) }
											aria-label={ __(
												'Search editable blocks',
												'flexa-block'
											) }
										/>
									</div>
								</div>

								{ visibleBlocks.length === 0 ? (
									<p className="flexa-blocks__empty">
										{ __(
											'No blocks match your search.',
											'flexa-block'
										) }
									</p>
								) : (
									<div className="flexa-blocks__grid">
										{ visibleBlocks.map( ( block ) => (
											<EditableBlockCard
												key={ block.slug }
												block={ block }
												enabled={
													! disabledBlocks.includes(
														block.slug
													)
												}
												onToggle={ onToggleBlock }
											/>
										) ) }
									</div>
								) }
							</div>
						</div>
					</section>
				</>
			) }
		</div>
	);
}

/**
 * A single role row: icon, name, and an allow switch (or a locked badge).
 * @param root0
 * @param root0.role     The role (slug + display name).
 * @param root0.allowed  Whether the role may edit inline.
 * @param root0.locked   When true, show a non-toggleable "Always" badge.
 * @param root0.onToggle Allow/disallow callback.
 */
function RoleRow( {
	role,
	allowed,
	locked,
	onToggle,
}: {
	role: FlexaBlockAdminRole;
	allowed: boolean;
	locked?: boolean;
	onToggle: ( slug: string, allowed: boolean ) => void;
} ): JSX.Element {
	return (
		<div
			className={ `flexa-role-row${ allowed ? ' is-on' : '' }${
				locked ? ' is-locked' : ''
			}` }
		>
			<span
				className="flexa-role-row__icon dashicons dashicons-admin-users"
				aria-hidden="true"
			/>
			<span className="flexa-role-row__name">{ role.name }</span>
			{ locked ? (
				<span className="flexa-role-row__badge">
					{ __( 'Always', 'flexa-block' ) }
				</span>
			) : (
				<button
					type="button"
					role="switch"
					aria-checked={ allowed }
					aria-label={ sprintf(
						/* translators: %s: role name. */
						__( 'Allow %s to edit on the front end', 'flexa-block' ),
						role.name
					) }
					className={ `flexa-switch${ allowed ? ' is-on' : '' }` }
					onClick={ () => onToggle( role.slug, ! allowed ) }
				>
					<span className="flexa-switch__knob" />
				</button>
			) }
		</div>
	);
}

/**
 * A block card with an enable/disable-for-editing switch.
 * @param root0
 * @param root0.block    The block from the catalog.
 * @param root0.enabled  Whether inline editing is on for it.
 * @param root0.onToggle Enable/disable callback.
 */
function EditableBlockCard( {
	block,
	enabled,
	onToggle,
}: {
	block: FlexaBlockAdminBlock;
	enabled: boolean;
	onToggle: ( slug: string, enabled: boolean ) => void;
} ): JSX.Element {
	const title = block.title || block.slug;

	return (
		<div className={ `flexa-block-card${ enabled ? '' : ' is-off' }` }>
			<BlockThumb block={ block } />
			<div className="flexa-block-card__body">
				<div className="flexa-block-card__head">
					<h3 className="flexa-block-card__title">{ title }</h3>
					<button
						type="button"
						role="switch"
						aria-checked={ enabled }
						aria-label={ sprintf(
							/* translators: %s: block title. */
							__( 'Allow editing %s', 'flexa-block' ),
							title
						) }
						className={ `flexa-switch${ enabled ? ' is-on' : '' }` }
						onClick={ () => onToggle( block.slug, ! enabled ) }
					>
						<span className="flexa-switch__knob" />
					</button>
				</div>
				<p className="flexa-block-card__desc">
					{ block.description || '' }
				</p>
			</div>
		</div>
	);
}
