/**
 * Grid block — editor component.
 *
 * Assembles a grid-specific layout panel (CSS Grid tracks, gaps, item and track
 * alignment) plus the shared inspector panels (@components) for width, spacing,
 * background, border, shadow, position and visibility. Inner blocks render as
 * real grid items so the column layout previews live on the canvas.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useEffect, useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { InspectorControls, useBlockProps, useInnerBlocksProps, InnerBlocks, ButtonBlockAppender } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, TextControl, ToggleControl } from '@wordpress/components';

import {
	InspectorTabs,
	Segmented,
	SliderUnit,
	FieldHead,
	GridItemPanel,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	PositionPanel,
	VisibilityPanel,
	AnimationPanel,
	CONTAINER_WIDTH_OPTIONS,
	useDevice,
	ExamplePreviewSkeleton,
} from '@components';
import {
	effective,
	rawDevice,
	patchDevice,
	withUnit,
	spacingShorthand,
	HTML_TAGS,
	WIDTH_UNITS,
	HEIGHT_UNITS,
	SPACING_UNITS,
	DIRECTION_OPTIONS,
	HStartIcon,
	HCenterIcon,
	HEndIcon,
	HBetweenIcon,
	HAroundIcon,
	VStartIcon,
	VCenterIcon,
	VEndIcon,
	VBetweenIcon,
	VAroundIcon,
	cn,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
} from '@utils';
import type { ControlOption, DeviceKey, EditProps, GridAttributes, GridLayoutDevice, LengthValue, PanelProps } from '../../types';
import GridStructurePicker from './components/GridStructurePicker';

type CssProps = Record< string, string >;

/* ---------------------------------------------------------------------------
 * Grid alignment option sets. Inline (inline/horizontal) axis reuses the H
 * glyphs; block (vertical) axis reuses the V glyphs.
 * ------------------------------------------------------------------------- */

const JUSTIFY_ITEMS_OPTIONS: ControlOption[] = [
	{ value: 'start', label: __( 'Start', 'flexa-block' ), icon: HStartIcon },
	{ value: 'center', label: __( 'Center', 'flexa-block' ), icon: HCenterIcon },
	{ value: 'end', label: __( 'End', 'flexa-block' ), icon: HEndIcon },
	{ value: 'stretch', label: __( 'Stretch', 'flexa-block' ), icon: HAroundIcon },
];

const ALIGN_ITEMS_OPTIONS: ControlOption[] = [
	{ value: 'start', label: __( 'Start', 'flexa-block' ), icon: VStartIcon },
	{ value: 'center', label: __( 'Center', 'flexa-block' ), icon: VCenterIcon },
	{ value: 'end', label: __( 'End', 'flexa-block' ), icon: VEndIcon },
	{ value: 'stretch', label: __( 'Stretch', 'flexa-block' ), icon: VAroundIcon },
];

const JUSTIFY_CONTENT_OPTIONS: ControlOption[] = [
	{ value: 'start', label: __( 'Start', 'flexa-block' ), icon: HStartIcon },
	{ value: 'center', label: __( 'Center', 'flexa-block' ), icon: HCenterIcon },
	{ value: 'end', label: __( 'End', 'flexa-block' ), icon: HEndIcon },
	{ value: 'space-between', label: __( 'Space between', 'flexa-block' ), icon: HBetweenIcon },
	{ value: 'space-around', label: __( 'Space around', 'flexa-block' ), icon: HAroundIcon },
];

const ALIGN_CONTENT_OPTIONS: ControlOption[] = [
	{ value: 'start', label: __( 'Start', 'flexa-block' ), icon: VStartIcon },
	{ value: 'center', label: __( 'Center', 'flexa-block' ), icon: VCenterIcon },
	{ value: 'end', label: __( 'End', 'flexa-block' ), icon: VEndIcon },
	{ value: 'space-between', label: __( 'Space between', 'flexa-block' ), icon: VBetweenIcon },
	{ value: 'space-around', label: __( 'Space around', 'flexa-block' ), icon: VAroundIcon },
];

/**
 * Expand a track definition to a CSS grid-template value: a `custom` unit is a
 * raw value; a numeric `fr` count becomes `repeat(n, 1fr)`. Mirror of the PHP
 * Grid_CSS::grid_template().
 *
 * @param track Track { value, unit }.
 * @return grid-template value, or '' when empty.
 */
const gridTemplate = ( track?: LengthValue ): string => {
	const value = String( track?.value ?? '' ).trim();
	if ( '' === value ) {
		return '';
	}
	if ( track?.unit === 'custom' ) {
		return value;
	}
	const count = parseInt( value, 10 );
	return count > 0 ? `repeat(${ count }, 1fr)` : value;
};

/**
 * Number of tracks a definition produces: a `custom` template counts its
 * space-separated tokens; an `fr` count is the number itself; empty → fallback.
 *
 * @param track    Track { value, unit }.
 * @param fallback Count when the track is empty.
 * @return Track count.
 */
const getTrackCount = ( track?: LengthValue, fallback = 1 ): number => {
	const value = String( track?.value ?? '' ).trim();
	if ( '' === value ) {
		return fallback;
	}
	if ( track?.unit === 'custom' ) {
		return value.split( /\s+/ ).length;
	}
	const count = parseInt( value, 10 );
	return count > 0 ? count : fallback;
};

/** Cap on editor placeholder cells, so a huge tracks × rows never floods the DOM. */
const MAX_PLACEHOLDERS = 48;

/**
 * One column/row track control: a slider for the `fr` count, or a text field
 * for a custom template (e.g. "1fr 2fr 1fr"), toggled per track.
 */
const TrackControl = ( {
	label,
	value,
	onChange,
	customPlaceholder,
}: {
	label: string;
	value: LengthValue;
	onChange: ( value: LengthValue ) => void;
	customPlaceholder: string;
} ): JSX.Element => {
	const isCustom = value?.unit === 'custom';
	return (
		<div className="flexa-field">
			<FieldHead label={ label } />
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Custom template', 'flexa-block' ) }
				checked={ isCustom }
				onChange={ ( custom: boolean ) => onChange( { value: '', unit: custom ? 'custom' : 'fr' } ) }
			/>
			{ isCustom ? (
				<TextControl
					__nextHasNoMarginBottom
					value={ value?.value ?? '' }
					placeholder={ customPlaceholder }
					onChange={ ( v: string ) => onChange( { value: v, unit: 'custom' } ) }
				/>
			) : (
				<RangeControl
					__nextHasNoMarginBottom
					value={ value?.value ? parseInt( value.value, 10 ) : undefined }
					min={ 1 }
					max={ 12 }
					allowReset
					onChange={ ( v?: number ) => onChange( { value: v ? String( v ) : '', unit: 'fr' } ) }
				/>
			) }
		</div>
	);
};

/**
 * Grid-specific container settings: width mode, width, min-height, tag.
 */
const GridContainerPanel = ( { attributes, setAttributes }: PanelProps< GridAttributes > ): JSX.Element => {
	const [ device ] = useDevice();
	const { containerType, htmlTag, widthBoxed, widthFullWidth, size } = attributes;
	const isBoxed = containerType === 'boxed';

	const widthGroup: 'widthBoxed' | 'widthFullWidth' = isBoxed ? 'widthBoxed' : 'widthFullWidth';
	const widthVal = ( isBoxed ? widthBoxed : widthFullWidth )?.[ device ] || {};
	const minH = size?.[ device ]?.minHeight || {};

	const setWidth = ( val: LengthValue ) => {
		const next: Partial< GridAttributes > = {};
		next[ widthGroup ] = { ...attributes[ widthGroup ], [ device ]: { value: val.value ?? '', unit: val.unit || ( isBoxed ? 'px' : '%' ) } };
		setAttributes( next );
	};
	const setMinHeight = ( val: LengthValue ) => {
		setAttributes( { size: { ...size, [ device ]: { minHeight: { value: val.value ?? '', unit: val.unit || 'px' } } } } );
	};

	return (
		<PanelBody title={ __( 'Container', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Content Width', 'flexa-block' ) }
				value={ containerType || 'boxed' }
				onChange={ ( v ) => setAttributes( { containerType: v as GridAttributes[ 'containerType' ] } ) }
				options={ CONTAINER_WIDTH_OPTIONS }
			/>
			<SliderUnit
				label={ isBoxed ? __( 'Max Width', 'flexa-block' ) : __( 'Width', 'flexa-block' ) }
				value={ widthVal }
				units={ WIDTH_UNITS }
				defaultUnit={ isBoxed ? 'px' : '%' }
				max={ { px: 3000, '%': 100, vw: 100, rem: 100 } }
				onChange={ setWidth }
			/>
			<SliderUnit
				label={ __( 'Min Height', 'flexa-block' ) }
				value={ minH }
				units={ HEIGHT_UNITS }
				defaultUnit="px"
				max={ { px: 1200, vh: 100, '%': 100, rem: 100 } }
				onChange={ setMinHeight }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'div' }
				options={ HTML_TAGS.map( ( t ) => ( { label: t, value: t } ) ) }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Grid layout panel — columns, rows, gaps, auto-flow, item & track alignment.
 */
const GridLayoutPanel = ( { attributes, setAttributes }: PanelProps< GridAttributes > ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes.layout, device );
	const set = ( patch: Partial< GridLayoutDevice > ) => setAttributes( { layout: patchDevice( attributes.layout, device, patch ) } );
	const gap = value.gap || {};

	return (
		<PanelBody title={ __( 'Grid', 'flexa-block' ) } initialOpen={ true }>
			<TrackControl
				label={ __( 'Columns', 'flexa-block' ) }
				value={ value.columns || { value: '', unit: 'fr' } }
				onChange={ ( v ) => set( { columns: v } ) }
				customPlaceholder="1fr 2fr 1fr"
			/>
			<TrackControl
				label={ __( 'Rows', 'flexa-block' ) }
				value={ value.rows || { value: '', unit: 'fr' } }
				onChange={ ( v ) => set( { rows: v } ) }
				customPlaceholder={ __( 'auto', 'flexa-block' ) }
			/>

			<div className="flexa-field">
				<FieldHead label={ __( 'Gap', 'flexa-block' ) } />
				<SliderUnit
					label={ __( 'Column', 'flexa-block' ) }
					showDevice={ false }
					value={ { value: gap.column, unit: gap.unit } }
					units={ SPACING_UNITS }
					max={ { px: 200, '%': 100, em: 20, rem: 20, vh: 100, vw: 100 } }
					onChange={ ( v ) => set( { gap: { ...gap, column: v.value ?? '', unit: v.unit || gap.unit || 'px' } } ) }
				/>
				<SliderUnit
					label={ __( 'Row', 'flexa-block' ) }
					showDevice={ false }
					value={ { value: gap.row, unit: gap.unit } }
					units={ SPACING_UNITS }
					max={ { px: 200, '%': 100, em: 20, rem: 20, vh: 100, vw: 100 } }
					onChange={ ( v ) => set( { gap: { ...gap, row: v.value ?? '', unit: v.unit || gap.unit || 'px' } } ) }
				/>
			</div>

			<Segmented label={ __( 'Auto Flow', 'flexa-block' ) } responsive value={ value.autoFlow || 'row' } onChange={ ( v ) => set( { autoFlow: v } ) } options={ DIRECTION_OPTIONS } />
			<Segmented label={ __( 'Justify Items', 'flexa-block' ) } responsive value={ value.justifyItems || 'stretch' } onChange={ ( v ) => set( { justifyItems: v } ) } options={ JUSTIFY_ITEMS_OPTIONS } />
			<Segmented label={ __( 'Align Items', 'flexa-block' ) } responsive value={ value.alignItems || 'stretch' } onChange={ ( v ) => set( { alignItems: v } ) } options={ ALIGN_ITEMS_OPTIONS } />
			<Segmented label={ __( 'Justify Content', 'flexa-block' ) } responsive value={ value.justifyContent || '' } onChange={ ( v ) => set( { justifyContent: v } ) } options={ JUSTIFY_CONTENT_OPTIONS } />
			<Segmented label={ __( 'Align Content', 'flexa-block' ) } responsive value={ value.alignContent || '' } onChange={ ( v ) => set( { alignContent: v } ) } options={ ALIGN_CONTENT_OPTIONS } />
		</PanelBody>
	);
};

/**
 * Build the inline preview style for the grid element on the active device.
 */
const buildStyledStyle = ( attributes: GridAttributes, device: DeviceKey, isBoxed: boolean ): CssProps => {
	const { layout, spacing, border, advancedLayout, size, widthBoxed, widthFullWidth, background, boxShadow } = attributes;
	const l = effective( layout, device );
	const sp = effective( spacing, device );
	const b = effective( border, device );
	const adv = effective( advancedLayout, device );
	const sz = effective( size, device );
	const w = effective( isBoxed ? widthBoxed : widthFullWidth, device );
	const s: CssProps = {};

	s.display = 'grid';
	const cols = gridTemplate( l.columns );
	if ( cols ) s.gridTemplateColumns = cols;
	const rows = gridTemplate( l.rows );
	if ( rows ) s.gridTemplateRows = rows;
	if ( l.gap ) {
		const gu = l.gap.unit || 'px';
		if ( l.gap.column ) s.columnGap = withUnit( l.gap.column, gu );
		if ( l.gap.row ) s.rowGap = withUnit( l.gap.row, gu );
	}
	if ( l.autoFlow ) s.gridAutoFlow = l.autoFlow;
	if ( l.justifyItems ) s.justifyItems = l.justifyItems;
	if ( l.alignItems ) s.alignItems = l.alignItems;
	if ( l.justifyContent ) s.justifyContent = l.justifyContent;
	if ( l.alignContent ) s.alignContent = l.alignContent;

	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	if ( w.value ) {
		const wu = w.unit || ( isBoxed ? 'px' : '%' );
		s[ isBoxed ? 'maxWidth' : 'width' ] = withUnit( w.value, wu );
	}
	if ( sz.minHeight?.value ) s.minHeight = withUnit( sz.minHeight.value, sz.minHeight.unit || 'px' );
	applyBorderPreview( s, b );
	applyBackgroundPreview( s, background );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	if ( adv.overflow ) s.overflow = adv.overflow;
	return s;
};

/**
 * Grid edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< GridAttributes > ): JSX.Element {
	const { htmlTag, containerType, spacing, responsiveVisibility, className, variationSelected } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();
	const isBoxed = containerType === 'boxed';

	// Nesting depth + how many children the block has (for picker + placeholders)
	// + whether this grid sits directly inside another Grid (to show span).
	const { depth, innerCount, isGridChild } = useSelect(
		( select: ( store: string ) => any ) => {
			const { getBlockParents, getBlockName, getBlock, getBlockRootClientId } = select( 'core/block-editor' );
			const parents: string[] = getBlockParents( clientId );
			const d = parents.filter( ( p ) => getBlockName( p ) === 'flexa/grid' ).length;
			const blk = getBlock( clientId );
			const root = getBlockRootClientId( clientId );
			return { depth: d, innerCount: blk?.innerBlocks?.length || 0, isGridChild: !! root && getBlockName( root ) === 'flexa/grid' };
		},
		[ clientId ]
	);
	const hasInner = innerCount > 0;

	useEffect( () => {
		const expected = clientId.replace( /[^a-z0-9]/gi, '' ).slice( 0, 8 );
		if ( ! blockId || blockId !== expected ) {
			setAttributes( { blockId: expected } );
		}
	}, [ blockId, clientId, setAttributes ] );

	const showPicker = ! variationSelected && depth === 0 && ! hasInner;

	// Fill the grid to its columns × rows matrix with placeholder cells so every
	// empty cell is visible and fillable; the first placeholder carries the "+"
	// appender. When the grid is already full, fall back to the default appender.
	const currentLayout = effective( attributes.layout, device );
	const totalCells = getTrackCount( currentLayout.columns, 1 ) * getTrackCount( currentLayout.rows, 1 );
	const placeholderCount = Math.min( MAX_PLACEHOLDERS, Math.max( 0, totalCells - innerCount ) );
	const appender = placeholderCount > 0 ? false : InnerBlocks.ButtonBlockAppender;

	const placeholderCells = useMemo(
		() =>
			placeholderCount > 0
				? Array.from( { length: placeholderCount } ).map( ( _, index ) =>
						index === 0 ? (
							<div key={ `ph-${ index }` } className="flexa-grid__placeholder flexa-grid__placeholder--active">
								<ButtonBlockAppender rootClientId={ clientId } />
							</div>
						) : (
							<div key={ `ph-${ index }` } className="flexa-grid__placeholder" aria-hidden="true" />
						)
				  )
				: null,
		[ placeholderCount, clientId ]
	);

	const styledStyle = buildStyledStyle( attributes, device, isBoxed );
	const margin = spacingShorthand( effective( spacing, device ).margin );
	const marginStyle: CssProps = margin ? { margin } : {};

	// Grid item span (only when this grid sits directly inside another Grid).
	// Applied to the outer element — the actual grid item.
	const spanStyle: CssProps = {};
	if ( isGridChild ) {
		const span = effective( attributes.gridSpan, device );
		if ( span.column ) spanStyle.gridColumn = `span ${ span.column }`;
		if ( span.row ) spanStyle.gridRow = `span ${ span.row }`;
	}

	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-grid',
			`flexa-grid--${ containerType }`,
			className,
			responsiveVisibility?.hideOnDesktop && 'flexa-hide-desktop',
			responsiveVisibility?.hideOnTablet && 'flexa-hide-tablet',
			responsiveVisibility?.hideOnMobile && 'flexa-hide-mobile'
		),
		style: isBoxed ? { ...marginStyle, ...spanStyle } : { ...styledStyle, ...marginStyle, ...spanStyle },
	} );

	// Apply inner-blocks props to the grid element so children become grid items.
	// Destructure `children` (the InnerBlocks) so placeholder cells can be
	// appended as further direct grid items after the real blocks.
	const { children: innerChildren, ...innerBlocksProps } = useInnerBlocksProps(
		isBoxed
			? { className: 'flexa-grid__inner', style: { ...styledStyle, marginLeft: 'auto', marginRight: 'auto' } }
			: blockProps,
		{ renderAppender: appender }
	);

	// Inserter hover-preview → faint skeleton mock-up instead of the real grid.
	if ( ( attributes as any ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="grid" />
			</div>
		);
	}

	if ( showPicker ) {
		return (
			<div { ...blockProps }>
				<GridStructurePicker clientId={ clientId } setAttributes={ setAttributes } />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-grid-inspector">
					<InspectorTabs
						layout={
							<>
								<GridContainerPanel attributes={ attributes } setAttributes={ setAttributes } />
								{ isGridChild && <GridItemPanel attributes={ attributes } setAttributes={ setAttributes } /> }
								<GridLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<BackgroundPanel attributes={ attributes } setAttributes={ setAttributes } />
								<BorderPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ShadowPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						advanced={
							<>
								<PositionPanel attributes={ attributes } setAttributes={ setAttributes } />
								<VisibilityPanel attributes={ attributes } setAttributes={ setAttributes } />
								<AnimationPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
					/>
				</div>
			</InspectorControls>

			{ isBoxed ? (
				<Tag { ...blockProps }>
					<div { ...innerBlocksProps }>
						{ innerChildren }
						{ placeholderCells }
					</div>
				</Tag>
			) : (
				<Tag { ...innerBlocksProps }>
					{ innerChildren }
					{ placeholderCells }
				</Tag>
			) }
		</>
	);
}
