/**
 * Filter: Taxonomy — editor component (child of flexa/post-filter).
 *
 * The preview shows the REAL terms (read from core-data), not placeholders, so the
 * author can see at once whether the control offers what they expect.
 *
 * It also tells the truth about the target grid's own constraint: if the grid is
 * already limited to certain terms, the server will only ever let a visitor narrow
 * WITHIN them — so those are the only terms this control offers, and a notice says
 * why.
 *
 * @package Flexa\Block
 */

import { __, sprintf } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl, RangeControl, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import { Segmented, VisibilityPanel, useBlockId, useDevice } from '@components';
import { cn } from '@utils';
import { FIELD_WIDTH_OPTIONS } from '@shared/filter-widths';
import { FilterFieldStylePanels, filterFieldCss } from '@shared/filter-field-style';
import type { EditProps, FilterTaxonomyAttributes } from '../../types';

/** Term count past which the menu offers a box to narrow itself. Mirrors PHP. */
const MENU_SEARCH_FROM = 12;

/** Ordering options for the term list. */
const ORDER_BY_OPTIONS = [
	{ value: 'name', label: __( 'Name', 'flexa-block' ) },
	{ value: 'count', label: __( 'Post count', 'flexa-block' ) },
	{ value: 'term_order', label: __( 'Term order', 'flexa-block' ) },
];

interface TermRecord {
	id: number;
	name: string;
	slug: string;
	count: number;
}

/**
 * The grid this control filters: the one named by the parent's context, or — when
 * the parent is on "auto" — the first Post Grid in the editor. Same rule the server
 * applies, so what the author previews is what visitors get.
 */
const useTargetGrid = ( targetId: string ): { postType: string; taxonomy: string; terms: string } => {
	return useSelect(
		( select: ( store: string ) => any ) => {
			const editor = select( 'core/block-editor' );
			const fallback = { postType: 'post', taxonomy: '', terms: '' };
			if ( ! editor?.getClientIdsWithDescendants ) {
				return fallback;
			}
			const grids = editor
				.getClientIdsWithDescendants()
				.filter( ( clientId: string ) => 'flexa/post-grid' === editor.getBlockName( clientId ) )
				.map( ( clientId: string ) => editor.getBlockAttributes( clientId ) || {} );

			const grid = targetId
				? grids.filter( ( attrs: any ) => attrs.blockId === targetId )[ 0 ]
				: grids[ 0 ];

			return grid
				? { postType: grid.postType || 'post', taxonomy: grid.taxonomy || '', terms: String( grid.terms || '' ) }
				: fallback;
		},
		[ targetId ]
	);
};

/**
 * Filter: Taxonomy edit component.
 */
export default function Edit( { attributes, setAttributes, clientId, context }: EditProps< FilterTaxonomyAttributes > ): JSX.Element {
	const { blockId, taxonomy, label, showLabel, control, multiple, allTermsLabel, showCount, hideEmpty, orderBy, limit, width } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const targetId = String( context?.[ 'flexa/filterTarget' ] || '' );
	const grid = useTargetGrid( targetId );

	// The grid's own term constraint, if it constrains THIS taxonomy.
	const constrainedIds = grid.taxonomy === taxonomy
		? grid.terms.split( ',' ).map( ( id ) => id.trim() ).filter( Boolean )
		: [];

	// Taxonomies registered for the grid's post type — filtering `post` by a product
	// category would silently match nothing.
	const taxonomyOptions = useSelect(
		( select: ( store: string ) => any ) => {
			const taxes = select( 'core' ).getTaxonomies( { per_page: -1, type: grid.postType } ) || [];
			return taxes.map( ( t: any ) => ( { value: t.slug, label: t.name || t.slug } ) );
		},
		[ grid.postType ]
	);

	// The real terms, so the preview is the control the visitor will see.
	const terms = useSelect(
		( select: ( store: string ) => any ) => {
			if ( ! taxonomy ) {
				return [] as TermRecord[];
			}
			const records = select( 'core' ).getEntityRecords( 'taxonomy', taxonomy, {
				per_page: Math.min( 100, limit || 50 ),
				hide_empty: false !== hideEmpty,
				orderby: 'count' === orderBy ? 'count' : 'name',
				context: 'view',
			} );
			return ( Array.isArray( records ) ? records : [] ).map( ( t: any ) => ( {
				id: t.id,
				name: t.name || String( t.id ),
				slug: t.slug || '',
				count: Number( t.count ) || 0,
			} ) );
		},
		[ taxonomy, limit, hideEmpty, orderBy ]
	);

	const visibleTerms = constrainedIds.length
		? terms.filter( ( term: TermRecord ) => constrainedIds.includes( String( term.id ) ) )
		: terms;

	// The "Custom taxonomy" variation lands with no taxonomy on purpose — the author
	// picks one. Until they do, offer the choice rather than defaulting them into
	// filtering by category behind their back.
	const resolved = taxonomyOptions.length ? taxonomyOptions : [ { value: 'category', label: __( 'Category', 'flexa-block' ) } ];
	const options = taxonomy
		? resolved
		: [ { value: '', label: __( '— Select a taxonomy —', 'flexa-block' ) }, ...resolved ];
	const isPills = 'pills' === control;
	const isCheckbox = 'checkbox' === control;
	const isGroup = isPills || isCheckbox;
	// A multi-choice dropdown is a disclosure with checkboxes, not a <select multiple>
	// (which the browser draws as an open list box). Mirrors render.php.
	const isMenu = ! isGroup && !! multiple;

	const fieldCss = filterFieldCss( attributes, blockId || '', device );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-filter-field',
			'flexa-filter-field--taxonomy',
			`flexa-filter-field--${ control || 'dropdown' }`,
			`flexa-filter-field--w${ width || 'auto' }`,
			blockId && `flexa-filter-field-${ blockId }`
		),
	} );

	/** One term's chip / checkbox label, with the post count when asked for. */
	const termText = ( term: TermRecord ): JSX.Element => (
		<span className="flexa-filter-field__option-text">
			{ term.name }
			{ showCount && <span className="flexa-filter-field__count">{ term.count }</span> }
		</span>
	);

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-filter-taxonomy-inspector">
					<PanelBody title={ __( 'Taxonomy', 'flexa-block' ) } initialOpen={ true }>
						{ ! taxonomy && (
							<Notice status="warning" isDismissible={ false }>
								{ __( 'Pick a taxonomy — until you do, this filter renders nothing on the front end.', 'flexa-block' ) }
							</Notice>
						) }
						{ !! constrainedIds.length && (
							<Notice status="info" isDismissible={ false }>
								{ sprintf(
									/* translators: %s: comma-separated term names the target grid is limited to. */
									__( 'The target grid only shows: %s. Visitors can filter within those terms, not outside them.', 'flexa-block' ),
									visibleTerms.map( ( term: TermRecord ) => term.name ).join( ', ' ) || '…'
								) }
							</Notice>
						) }
						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Taxonomy', 'flexa-block' ) }
							value={ taxonomy || '' }
							options={ options }
							onChange={ ( v: string ) => setAttributes( { taxonomy: v } ) }
						/>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Label', 'flexa-block' ) }
							value={ label ?? '' }
							onChange={ ( v: string ) => setAttributes( { label: v } ) }
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Show label', 'flexa-block' ) }
							checked={ false !== showLabel }
							onChange={ ( v: boolean ) => setAttributes( { showLabel: v } ) }
						/>
						<Segmented
							label={ __( 'Control', 'flexa-block' ) }
							value={ control || 'dropdown' }
							onChange={ ( v ) => setAttributes( { control: v as FilterTaxonomyAttributes[ 'control' ] } ) }
							options={ [
								{ value: 'dropdown', label: __( 'Dropdown', 'flexa-block' ) },
								{ value: 'pills', label: __( 'Pills', 'flexa-block' ) },
								{ value: 'checkbox', label: __( 'Checkboxes', 'flexa-block' ) },
							] }
						/>
						{ ! isCheckbox && (
							<>
								<ToggleControl
									__nextHasNoMarginBottom
									label={ __( 'Allow multiple', 'flexa-block' ) }
									help={ isPills ? __( 'Off: one pill at a time. On: visitors can combine several.', 'flexa-block' ) : undefined }
									checked={ !! multiple }
									onChange={ ( v: boolean ) => setAttributes( { multiple: v } ) }
								/>
								{ ! multiple && (
									<TextControl
										__nextHasNoMarginBottom
										label={ __( '"Any term" label', 'flexa-block' ) }
										value={ allTermsLabel ?? '' }
										onChange={ ( v: string ) => setAttributes( { allTermsLabel: v } ) }
									/>
								) }
							</>
						) }
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Show post count', 'flexa-block' ) }
							checked={ !! showCount }
							onChange={ ( v: boolean ) => setAttributes( { showCount: v } ) }
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Hide empty terms', 'flexa-block' ) }
							checked={ false !== hideEmpty }
							onChange={ ( v: boolean ) => setAttributes( { hideEmpty: v } ) }
						/>
						<SelectControl
							__nextHasNoMarginBottom
							label={ __( 'Order terms by', 'flexa-block' ) }
							value={ orderBy || 'name' }
							options={ ORDER_BY_OPTIONS }
							onChange={ ( v: string ) => setAttributes( { orderBy: v as FilterTaxonomyAttributes[ 'orderBy' ] } ) }
						/>
						<RangeControl
							__nextHasNoMarginBottom
							label={ __( 'Max terms', 'flexa-block' ) }
							value={ typeof limit === 'number' ? limit : 50 }
							min={ 1 }
							max={ 100 }
							onChange={ ( v?: number ) => setAttributes( { limit: v || 1 } ) }
						/>
						<Segmented
							label={ __( 'Width', 'flexa-block' ) }
							value={ width || 'auto' }
							onChange={ ( v ) => setAttributes( { width: v as FilterTaxonomyAttributes[ 'width' ] } ) }
							options={ FIELD_WIDTH_OPTIONS }
						/>
					</PanelBody>
					<FilterFieldStylePanels attributes={ attributes } setAttributes={ setAttributes } />
					<VisibilityPanel attributes={ attributes } setAttributes={ setAttributes } />
				</div>
			</InspectorControls>

			<div { ...blockProps }>
				{ fieldCss && <style>{ fieldCss }</style> }
				{ isGroup ? (
					<fieldset className="flexa-filter-field__fieldset">
						{ label && (
							<legend className={ cn( 'flexa-filter-field__label', ! showLabel && 'screen-reader-text' ) }>{ label }</legend>
						) }
						<div className={ cn( 'flexa-filter-field__options', `flexa-filter-field__options--${ isPills ? 'pills' : 'checkbox' }` ) }>
							{ /* Pills default to single-choice, so the "any term" chip is the resting state. */ }
							{ isPills && ! multiple && (
								<label className="flexa-filter-field__option">
									<input type="radio" className="flexa-filter-field__option-input" defaultChecked disabled />
									<span className="flexa-filter-field__option-text">{ allTermsLabel || __( 'All', 'flexa-block' ) }</span>
								</label>
							) }
							{ visibleTerms.map( ( term: TermRecord ) => (
								<label key={ term.id } className="flexa-filter-field__option">
									<input
										type={ isPills && ! multiple ? 'radio' : 'checkbox' }
										className="flexa-filter-field__option-input"
										disabled
									/>
									{ termText( term ) }
								</label>
							) ) }
						</div>
					</fieldset>
				) : isMenu ? (
					<fieldset className="flexa-filter-field__fieldset">
						{ label && (
							<legend className={ cn( 'flexa-filter-field__label', ! showLabel && 'screen-reader-text' ) }>{ label }</legend>
						) }
						{ /* Closed on the canvas — the author is styling a dropdown, not browsing terms. */ }
						<details className="flexa-filter-field__menu">
							<summary className="flexa-filter-field__control flexa-filter-field__menu-toggle">
								<span className="flexa-filter-field__menu-value">{ allTermsLabel || __( 'All', 'flexa-block' ) }</span>
							</summary>
							<div className="flexa-filter-field__menu-panel">
								{ /* Past a dozen terms the panel gets a box to narrow itself. Mirrors
								     Filter_Fields::MENU_SEARCH_FROM. */ }
								{ visibleTerms.length > MENU_SEARCH_FROM && (
									<input
										type="search"
										className="flexa-filter-field__menu-search"
										placeholder={ __( 'Filter terms…', 'flexa-block' ) }
										disabled
									/>
								) }
								<div className="flexa-filter-field__menu-list">
									{ visibleTerms.map( ( term: TermRecord ) => (
										<label key={ term.id } className="flexa-filter-field__option">
											<input type="checkbox" className="flexa-filter-field__option-input" disabled />
											{ termText( term ) }
										</label>
									) ) }
								</div>
							</div>
						</details>
					</fieldset>
				) : (
					<>
						{ label && (
							<label className={ cn( 'flexa-filter-field__label', ! showLabel && 'screen-reader-text' ) }>{ label }</label>
						) }
						<select className="flexa-filter-field__control" disabled>
							<option>{ allTermsLabel || __( 'All', 'flexa-block' ) }</option>
							{ visibleTerms.map( ( term: TermRecord ) => (
								<option key={ term.id }>{ showCount ? `${ term.name } (${ term.count })` : term.name }</option>
							) ) }
						</select>
					</>
				) }
			</div>
		</>
	);
}
