/**
 * Container block - editor component.
 *
 * Assembles the shared inspector panels (@components) plus a container-specific
 * panel. Responsive values follow the editor's active device preview.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { InspectorControls, useBlockProps, useInnerBlocksProps, InnerBlocks } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

import {
	InspectorTabs,
	Segmented,
	SliderUnit,
	LayoutPanel,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	PositionPanel,
	VisibilityPanel,
	useDevice,
} from '@components';
import {
	effective,
	withUnit,
	spacingShorthand,
	radiusShorthand,
	HTML_TAGS,
	WIDTH_UNITS,
	HEIGHT_UNITS,
} from '@utils';
import type { ContainerAttributes, DeviceKey, EditProps, LengthValue, PanelProps } from '../../types';
import StructurePicker from './components/StructurePicker';

type CssProps = Record< string, string >;

const cn = ( ...parts: Array< string | false | null | undefined > ): string => parts.filter( Boolean ).join( ' ' );

/**
 * Container-specific settings: width mode, width, min-height, tag.
 */
const ContainerPanel = ( { attributes, setAttributes }: PanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { containerType, htmlTag, widthBoxed, widthFullWidth, size } = attributes;
	const isBoxed = containerType === 'boxed';

	const widthGroup: 'widthBoxed' | 'widthFullWidth' = isBoxed ? 'widthBoxed' : 'widthFullWidth';
	const widthVal = ( isBoxed ? widthBoxed : widthFullWidth )?.[ device ] || {};
	const minH = size?.[ device ]?.minHeight || {};

	const setWidth = ( val: LengthValue ) => {
		const next: Partial< ContainerAttributes > = {};
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
				onChange={ ( v ) => setAttributes( { containerType: v as ContainerAttributes[ 'containerType' ] } ) }
				options={ [
					{ value: 'boxed', label: __( 'Boxed', 'flexa-block' ) },
					{ value: 'full-width', label: __( 'Full Width', 'flexa-block' ) },
				] }
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
 * Build the inline preview style for the styled element on the active device.
 */
const buildStyledStyle = ( attributes: ContainerAttributes, device: DeviceKey, isBoxed: boolean ): CssProps => {
	const { layout, spacing, border, advancedLayout, size, widthBoxed, widthFullWidth, background, boxShadow } = attributes;
	const l = effective( layout, device );
	const sp = effective( spacing, device );
	const b = effective( border, device );
	const adv = effective( advancedLayout, device );
	const sz = effective( size, device );
	const w = effective( isBoxed ? widthBoxed : widthFullWidth, device );
	const s: CssProps = {};

	if ( l.display ) {
		s.display = l.display;
	}
	if ( l.display === 'flex' ) {
		if ( l.direction ) s.flexDirection = l.direction;
		if ( l.justifyContent ) s.justifyContent = l.justifyContent;
		if ( l.alignItems ) s.alignItems = l.alignItems;
		if ( l.wrap ) s.flexWrap = l.wrap;
		if ( l.gap ) {
			const gu = l.gap.unit || 'px';
			if ( l.gap.column ) s.columnGap = withUnit( l.gap.column, gu );
			if ( l.gap.row ) s.rowGap = withUnit( l.gap.row, gu );
		}
	}
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	if ( w.value ) {
		const wu = w.unit || ( isBoxed ? 'px' : '%' );
		s[ isBoxed ? 'maxWidth' : 'width' ] = withUnit( w.value, wu );
	}
	if ( sz.minHeight?.value ) s.minHeight = withUnit( sz.minHeight.value, sz.minHeight.unit || 'px' );
	if ( b.style ) s.borderStyle = b.style;
	const bw = spacingShorthand( b.width );
	if ( bw ) s.borderWidth = bw;
	if ( b.color?.light ) s.borderColor = b.color.light;
	const br = radiusShorthand( b.radius );
	if ( br ) s.borderRadius = br;
	if ( background?.type === 'classic' && background.color?.light ) s.backgroundColor = background.color.light;
	else if ( background?.type === 'gradient' && background.gradient?.light ) s.backgroundImage = background.gradient.light;
	else if ( background?.type === 'image' && background.image?.url ) {
		s.backgroundImage = `url(${ background.image.url })`;
		s.backgroundSize = background.image.size || 'cover';
		s.backgroundPosition = background.image.position || 'center center';
		s.backgroundRepeat = background.image.repeat || 'no-repeat';
	}
	if ( boxShadow?.enabled ) {
		const c = boxShadow.color?.light || 'rgba(0,0,0,0.1)';
		s.boxShadow = `${ boxShadow.inset ? 'inset ' : '' }${ withUnit( boxShadow.horizontal ) } ${ withUnit( boxShadow.vertical ) } ${ withUnit( boxShadow.blur ) } ${ withUnit( boxShadow.spread ) } ${ c }`;
	}
	if ( adv.overflow ) s.overflow = adv.overflow;
	return s;
};

/**
 * Container edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps ): JSX.Element {
	const { htmlTag, containerType, spacing, responsiveVisibility, className, variationSelected } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();
	const isBoxed = containerType === 'boxed';

	// Nesting depth + whether the block already has children (to decide on the picker).
	const { depth, hasInner } = useSelect(
		( select: ( store: string ) => any ) => {
			const { getBlockParents, getBlockName, getBlock } = select( 'core/block-editor' );
			const parents: string[] = getBlockParents( clientId );
			const d = parents.filter( ( p ) => getBlockName( p ) === 'flexa/container' ).length;
			const blk = getBlock( clientId );
			return { depth: d, hasInner: !! ( blk?.innerBlocks?.length ) };
		},
		[ clientId ]
	);

	useEffect( () => {
		const expected = clientId.replace( /[^a-z0-9]/gi, '' ).slice( 0, 8 );
		if ( ! blockId || blockId !== expected ) {
			setAttributes( { blockId: expected } );
		}
	}, [ blockId, clientId, setAttributes ] );

	const showPicker = ! variationSelected && depth === 0 && ! hasInner;

	// Empty containers (e.g. fresh columns) always show a "+" appender so each is
	// fillable at a glance; containers with content use the default appender.
	const appender = hasInner ? undefined : InnerBlocks.ButtonBlockAppender;

	const styledStyle = buildStyledStyle( attributes, device, isBoxed );
	const margin = spacingShorthand( effective( spacing, device ).margin );
	const marginStyle: CssProps = margin ? { margin } : {};

	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-container',
			`flexa-container--${ containerType }`,
			className,
			responsiveVisibility?.hideOnDesktop && 'flexa-hide-desktop',
			responsiveVisibility?.hideOnTablet && 'flexa-hide-tablet',
			responsiveVisibility?.hideOnMobile && 'flexa-hide-mobile'
		),
		style: isBoxed ? marginStyle : { ...styledStyle, ...marginStyle },
	} );

	// Apply inner-blocks props directly to the flex container so child columns
	// become real flex items. A bare <InnerBlocks/> wraps children in an extra
	// div, which breaks the row layout in the editor (columns stack vertically).
	const innerBlocksProps = useInnerBlocksProps(
		isBoxed
			? { className: 'flexa-container__inner', style: { ...styledStyle, marginLeft: 'auto', marginRight: 'auto' } }
			: blockProps,
		{ renderAppender: appender }
	);

	if ( showPicker ) {
		return (
			<div { ...blockProps }>
				<StructurePicker clientId={ clientId } setAttributes={ setAttributes } />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-container-inspector">
					<InspectorTabs
						layout={
							<>
								<ContainerPanel attributes={ attributes } setAttributes={ setAttributes } />
								<LayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
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
							</>
						}
					/>
				</div>
			</InspectorControls>

			{ isBoxed ? (
				<Tag { ...blockProps }>
					<div { ...innerBlocksProps } />
				</Tag>
			) : (
				<Tag { ...innerBlocksProps } />
			) }
		</>
	);
}
