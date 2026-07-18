/**
 * Blocks panel — searchable, group-filterable card grid for enabling/disabling
 * blocks.
 *
 * Blocks are filtered by a fine-grained group taxonomy (Layout, Content, Media,
 * Interactive, Marketing, Social, Forms — see block-groups) rather than the raw
 * two catalog categories. Each card carries an illustrative, group-tinted
 * thumbnail. Child blocks (those that only exist inside a parent, e.g. Slide)
 * are hidden — they can't be toggled independently.
 */

import { useMemo, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { GROUP_ORDER, groupMeta, groupOf } from './block-groups';
import { BlockThumb } from './block-thumb';

interface Props {
	blocks: FlexaBlockAdminBlock[];
	disabled: string[];
	onToggle: ( slug: string, enabled: boolean ) => void;
	onToggleMany: ( slugs: string[], enabled: boolean ) => void;
}

/**
 * The block card grid with search + group filter.
 * @param root0
 * @param root0.blocks   Full block catalog.
 * @param root0.disabled Disabled block slugs.
 * @param root0.onToggle Enable/disable callback.
 */
export function BlocksPanel( {
	blocks,
	disabled,
	onToggle,
	onToggleMany,
}: Props ): JSX.Element {
	const [ query, setQuery ] = useState( '' );
	const [ group, setGroup ] = useState( 'all' );

	// Only top-level blocks belong on the dashboard; child blocks are managed
	// through their parent.
	const topLevel = useMemo(
		() => blocks.filter( ( b ) => ! b.is_child ),
		[ blocks ]
	);

	// Count blocks per group, then list the groups that actually have blocks in
	// their canonical order.
	const groupCounts = useMemo( () => {
		const counts: Record< string, number > = {};
		topLevel.forEach( ( b ) => {
			const g = groupOf( b );
			counts[ g ] = ( counts[ g ] || 0 ) + 1;
		} );
		return counts;
	}, [ topLevel ] );

	const groups = useMemo(
		() => GROUP_ORDER.filter( ( g ) => groupCounts[ g ] > 0 ),
		[ groupCounts ]
	);

	const visible = useMemo( () => {
		const q = query.trim().toLowerCase();
		return topLevel.filter( ( b ) => {
			const inGroup = group === 'all' || groupOf( b ) === group;
			if ( ! inGroup ) {
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
	}, [ topLevel, query, group ] );

	const enabledCount = topLevel.filter(
		( b ) => ! disabled.includes( b.slug )
	).length;

	// Bulk actions operate on the currently visible (filtered + searched)
	// blocks, and only Core-free ones can be switched off.
	const visibleSlugs = visible.map( ( b ) => b.slug );
	const toggleable = visible.filter( ( b ) => ! b.is_core );
	const allEnabled =
		toggleable.length > 0 &&
		toggleable.every( ( b ) => ! disabled.includes( b.slug ) );
	const allDisabled =
		toggleable.length > 0 &&
		toggleable.every( ( b ) => disabled.includes( b.slug ) );

	return (
		<div className="flexa-admin-card flexa-blocks">
			<div className="flexa-blocks__head">
				<div>
					<h2 className="flexa-admin-card__title">
						{ __( 'Blocks', 'flexa-block' ) }
					</h2>
					<p className="flexa-blocks__count">
						{ sprintf(
							/* translators: 1: enabled block count, 2: total block count. */
							__( '%1$d of %2$d blocks enabled', 'flexa-block' ),
							enabledCount,
							topLevel.length
						) }
					</p>
				</div>
				<div className="flexa-blocks__search">
					<span
						className="dashicons dashicons-search"
						aria-hidden="true"
					/>
					<input
						type="search"
						value={ query }
						onChange={ ( e ) => setQuery( e.target.value ) }
						placeholder={ __( 'Search blocks…', 'flexa-block' ) }
						aria-label={ __( 'Search blocks', 'flexa-block' ) }
					/>
				</div>
			</div>

			<div className="flexa-blocks__toolbar">
				<div className="flexa-blocks__filters" role="tablist">
					<FilterPill
						label={ __( 'All', 'flexa-block' ) }
						count={ topLevel.length }
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

				<div className="flexa-blocks__actions">
					<button
						type="button"
						className="flexa-bulk-btn"
						disabled={ allEnabled }
						onClick={ () => onToggleMany( visibleSlugs, true ) }
					>
						<span
							className="dashicons dashicons-yes"
							aria-hidden="true"
						/>
						{ __( 'Enable all', 'flexa-block' ) }
					</button>
					<button
						type="button"
						className="flexa-bulk-btn"
						disabled={ allDisabled }
						onClick={ () => onToggleMany( visibleSlugs, false ) }
					>
						<span
							className="dashicons dashicons-hidden"
							aria-hidden="true"
						/>
						{ __( 'Disable all', 'flexa-block' ) }
					</button>
				</div>
			</div>

			{ visible.length === 0 ? (
				<p className="flexa-blocks__empty">
					{ __( 'No blocks match your search.', 'flexa-block' ) }
				</p>
			) : (
				<div className="flexa-blocks__grid">
					{ visible.map( ( block ) => (
						<BlockCard
							key={ block.slug }
							block={ block }
							enabled={ ! disabled.includes( block.slug ) }
							onToggle={ onToggle }
						/>
					) ) }
				</div>
			) }
		</div>
	);
}

/**
 * A single group filter pill.
 * @param root0
 * @param root0.label  Pill text.
 * @param root0.count  Block count badge.
 * @param root0.active Whether this pill is selected.
 * @param root0.onClick Selection handler.
 * @param root0.accent Optional accent colour (for the active state).
 */
export function FilterPill( {
	label,
	count,
	active,
	onClick,
	accent,
}: {
	label: string;
	count: number;
	active: boolean;
	onClick: () => void;
	accent?: string;
} ): JSX.Element {
	return (
		<button
			type="button"
			role="tab"
			aria-selected={ active }
			className={ `flexa-pill${ active ? ' is-active' : '' }` }
			style={ active && accent ? { background: accent, borderColor: accent } : undefined }
			onClick={ onClick }
		>
			{ label }
			<span className="flexa-pill__count">{ count }</span>
		</button>
	);
}

/**
 * A single block card: illustrative thumbnail, title and an accessible
 * enable/disable switch.
 * @param root0
 * @param root0.block    The block.
 * @param root0.enabled  Whether it is currently enabled.
 * @param root0.onToggle Enable/disable callback.
 */
function BlockCard( {
	block,
	enabled,
	onToggle,
}: {
	block: FlexaBlockAdminBlock;
	enabled: boolean;
	onToggle: ( slug: string, enabled: boolean ) => void;
} ): JSX.Element {
	const isCore = !! block.is_core;
	const title = block.title || block.slug;

	return (
		<div className={ `flexa-block-card${ enabled ? '' : ' is-off' }` }>
			<BlockThumb block={ block } />
			<div className="flexa-block-card__body">
				<div className="flexa-block-card__head">
					<h3 className="flexa-block-card__title">
						{ title }
						{ isCore && (
							<span className="flexa-block-card__badge">
								{ __( 'Core', 'flexa-block' ) }
							</span>
						) }
					</h3>
					<button
						type="button"
						role="switch"
						aria-checked={ enabled }
						aria-label={ sprintf(
							/* translators: %s: block title. */
							__( 'Enable %s block', 'flexa-block' ),
							title
						) }
						className={ `flexa-switch${ enabled ? ' is-on' : '' }` }
						disabled={ isCore }
						onClick={ () => onToggle( block.slug, ! enabled ) }
					>
						<span className="flexa-switch__knob" />
					</button>
				</div>
				<p className="flexa-block-card__desc">
					{ isCore
						? __( 'Core block — always enabled.', 'flexa-block' )
						: block.description || '' }
				</p>
			</div>
		</div>
	);
}
