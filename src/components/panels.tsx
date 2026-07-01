/**
 * Reusable inspector panels (shared across blocks).
 *
 * Each panel takes { attributes, setAttributes } and reads/writes a shared
 * attribute by name. Responsive panels follow the active editor device.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	ToggleControl,
	BaseControl,
	Flex,
	Button,
	TextControl,
} from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import {
	rawDevice,
	patchDevice,
	DIRECTION_OPTIONS,
	JUSTIFY_OPTIONS_ROW,
	JUSTIFY_OPTIONS_COLUMN,
	ALIGN_OPTIONS_ROW,
	ALIGN_OPTIONS_COLUMN,
	WRAP_OPTIONS,
	SPACING_UNITS,
	OVERFLOW_OPTIONS,
	BORDER_STYLE_OPTIONS,
} from '@utils';
import { Segmented, SliderUnit, Dimensions, DualColor, GradientControl, FieldHead, useDevice } from './controls';
import type {
	AdvancedLayoutDevice,
	BackgroundAttr,
	BorderDevice,
	BoxShadowAttr,
	BoxValue,
	ColorPair,
	LayoutDevice,
	PanelProps,
	RadiusValue,
	ResponsiveVisibilityAttr,
	SpacingDevice,
} from '../types';

/**
 * Layout panel — flex display, direction, alignment, gap.
 */
export const LayoutPanel = ( { attributes, setAttributes, initialOpen = true }: PanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes.layout, device );
	const set = ( patch: Partial< LayoutDevice > ) => setAttributes( { layout: patchDevice( attributes.layout, device, patch ) } );
	const display = value.display || 'flex';
	const gap = value.gap || {};
	const isRow = ( value.direction || 'column' ) === 'row';

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ initialOpen }>
			<Segmented
				label={ __( 'Display', 'flexa-block' ) }
				value={ display }
				responsive
				onChange={ ( v ) => set( { display: v } ) }
				options={ [
					{ value: 'flex', label: __( 'Flex', 'flexa-block' ) },
					{ value: 'block', label: __( 'Block', 'flexa-block' ) },
				] }
			/>

			{ display === 'flex' && (
				<>
					<Segmented label={ __( 'Direction', 'flexa-block' ) } responsive value={ value.direction || 'column' } onChange={ ( v ) => set( { direction: v } ) } options={ DIRECTION_OPTIONS } />
					<Segmented label={ __( 'Justify', 'flexa-block' ) } responsive value={ value.justifyContent || 'flex-start' } onChange={ ( v ) => set( { justifyContent: v } ) } options={ isRow ? JUSTIFY_OPTIONS_ROW : JUSTIFY_OPTIONS_COLUMN } />
					<Segmented label={ __( 'Align', 'flexa-block' ) } responsive value={ value.alignItems || 'stretch' } onChange={ ( v ) => set( { alignItems: v } ) } options={ isRow ? ALIGN_OPTIONS_ROW : ALIGN_OPTIONS_COLUMN } />
					<Segmented label={ __( 'Wrap', 'flexa-block' ) } responsive value={ value.wrap || 'nowrap' } onChange={ ( v ) => set( { wrap: v } ) } options={ WRAP_OPTIONS } />
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
				</>
			) }
		</PanelBody>
	);
};

/**
 * Spacing panel — padding + margin.
 */
export const SpacingPanel = ( { attributes, setAttributes, initialOpen = false }: PanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes.spacing, device );
	const set = ( patch: Partial< SpacingDevice > ) => setAttributes( { spacing: patchDevice( attributes.spacing, device, patch ) } );

	return (
		<PanelBody title={ __( 'Spacing', 'flexa-block' ) } initialOpen={ initialOpen }>
			<Dimensions label={ __( 'Margin', 'flexa-block' ) } responsive value={ value.margin || {} } units={ SPACING_UNITS } onChange={ ( v ) => set( { margin: v } ) } />
			<Dimensions label={ __( 'Padding', 'flexa-block' ) } responsive value={ value.padding || {} } units={ SPACING_UNITS } onChange={ ( v ) => set( { padding: v } ) } />
		</PanelBody>
	);
};

/**
 * Background panel — color / gradient / image with light & dark colors.
 */
export const BackgroundPanel = ( { attributes, setAttributes, initialOpen = false }: PanelProps ): JSX.Element => {
	const bg: BackgroundAttr = attributes.background || {};
	const set = ( patch: Partial< BackgroundAttr > ) => setAttributes( { background: { ...bg, ...patch } } );

	return (
		<PanelBody title={ __( 'Background', 'flexa-block' ) } initialOpen={ initialOpen }>
			<Segmented
				label={ __( 'Type', 'flexa-block' ) }
				value={ bg.type || 'none' }
				onChange={ ( v ) => set( { type: v as BackgroundAttr[ 'type' ] } ) }
				options={ [
					{ value: 'none', label: __( 'None', 'flexa-block' ) },
					{ value: 'classic', label: __( 'Color', 'flexa-block' ) },
					{ value: 'gradient', label: __( 'Gradient', 'flexa-block' ) },
					{ value: 'image', label: __( 'Image', 'flexa-block' ) },
				] }
			/>

			{ bg.type === 'classic' && (
				<DualColor label={ __( 'Color', 'flexa-block' ) } value={ bg.color || {} } onChange={ ( v ) => set( { color: v } ) } />
			) }

			{ bg.type === 'gradient' && (
				<GradientControl label={ __( 'Gradient', 'flexa-block' ) } value={ bg.gradient || {} } onChange={ ( v ) => set( { gradient: v } ) } />
			) }

			{ bg.type === 'image' && (
				<BaseControl __nextHasNoMarginBottom label={ __( 'Image', 'flexa-block' ) }>
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ bg.image?.id }
							onSelect={ ( media: { id: number; url: string } ) => set( { image: { ...bg.image, id: media.id, url: media.url } } ) }
							render={ ( { open }: { open: () => void } ) => (
								<>
									{ bg.image?.url && (
										<button type="button" className="flexa-bg-preview" onClick={ open } aria-label={ __( 'Replace image', 'flexa-block' ) }>
											<img src={ bg.image.url } alt="" />
										</button>
									) }
									<Flex gap={ 2 } justify="flex-start">
										<Button variant="secondary" onClick={ open }>
											{ bg.image?.url ? __( 'Replace', 'flexa-block' ) : __( 'Select Image', 'flexa-block' ) }
										</Button>
										{ bg.image?.url && (
											<Button isDestructive variant="tertiary" onClick={ () => set( { image: { ...bg.image, id: null, url: '' } } ) }>
												{ __( 'Remove', 'flexa-block' ) }
											</Button>
										) }
									</Flex>
								</>
							) }
						/>
					</MediaUploadCheck>
				</BaseControl>
			) }

			{ bg.type === 'image' && bg.image?.url && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Lazy load image', 'flexa-block' ) }
					help={ __( 'Only fetch the background image when it nears the viewport.', 'flexa-block' ) }
					checked={ !! bg.lazyLoad }
					onChange={ ( v: boolean ) => set( { lazyLoad: v } ) }
				/>
			) }
		</PanelBody>
	);
};

// Radius uses corner keys; map to/from the 4-side Dimensions control.
const mapRadiusToBox = ( r: RadiusValue = {} ): BoxValue => ( { top: r.topLeft ?? '', right: r.topRight ?? '', bottom: r.bottomRight ?? '', left: r.bottomLeft ?? '', unit: r.unit || 'px' } );
const mapBoxToRadius = ( b: BoxValue = {} ): RadiusValue => ( { topLeft: b.top ?? '', topRight: b.right ?? '', bottomRight: b.bottom ?? '', bottomLeft: b.left ?? '', unit: b.unit || 'px' } );

/**
 * Border panel — style, width, color, radius (responsive width/radius).
 */
export const BorderPanel = ( { attributes, setAttributes, initialOpen = false }: PanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes.border, device );
	const set = ( patch: Partial< BorderDevice > ) => setAttributes( { border: patchDevice( attributes.border, device, patch ) } );

	return (
		<PanelBody title={ __( 'Border', 'flexa-block' ) } initialOpen={ initialOpen }>
			<SelectControl __nextHasNoMarginBottom label={ __( 'Style', 'flexa-block' ) } value={ value.style || '' } options={ BORDER_STYLE_OPTIONS } onChange={ ( v: string ) => set( { style: v } ) } />
			<Dimensions label={ __( 'Width', 'flexa-block' ) } responsive value={ value.width || {} } units={ SPACING_UNITS } onChange={ ( v ) => set( { width: v } ) } />
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ value.color || {} } onChange={ ( v ) => set( { color: v } ) } />
			<Dimensions label={ __( 'Radius', 'flexa-block' ) } responsive value={ mapRadiusToBox( value.radius || {} ) } units={ SPACING_UNITS } onChange={ ( v ) => set( { radius: mapBoxToRadius( v ) } ) } />
		</PanelBody>
	);
};

/** Box shadow numeric offset fields. */
const SHADOW_FIELDS: Array< { k: 'horizontal' | 'vertical' | 'blur' | 'spread'; l: string } > = [
	{ k: 'horizontal', l: __( 'X offset', 'flexa-block' ) },
	{ k: 'vertical', l: __( 'Y offset', 'flexa-block' ) },
	{ k: 'blur', l: __( 'Blur', 'flexa-block' ) },
	{ k: 'spread', l: __( 'Spread', 'flexa-block' ) },
];

/**
 * Box shadow panel.
 */
export const ShadowPanel = ( { attributes, setAttributes, initialOpen = false }: PanelProps ): JSX.Element => {
	const shadow: BoxShadowAttr = attributes.boxShadow || {};
	const set = ( patch: Partial< BoxShadowAttr > ) => setAttributes( { boxShadow: { ...shadow, ...patch } } );

	return (
		<PanelBody title={ __( 'Box Shadow', 'flexa-block' ) } initialOpen={ initialOpen }>
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Enable', 'flexa-block' ) } checked={ !! shadow.enabled } onChange={ ( v: boolean ) => set( { enabled: v } ) } />
			{ shadow.enabled && (
				<>
					{ SHADOW_FIELDS.map( ( f ) => (
						<RangeControl key={ f.k } __nextHasNoMarginBottom label={ f.l } value={ parseInt( String( shadow[ f.k ] ?? '' ), 10 ) || 0 } min={ -100 } max={ 100 } onChange={ ( v: number ) => set( { [ f.k ]: String( v ) } ) } />
					) ) }
					<DualColor label={ __( 'Color', 'flexa-block' ) } value={ shadow.color || {} } onChange={ ( v ) => set( { color: v } ) } />
					<ToggleControl __nextHasNoMarginBottom label={ __( 'Inset', 'flexa-block' ) } checked={ !! shadow.inset } onChange={ ( v: boolean ) => set( { inset: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Position panel — overflow + z-index (responsive).
 */
export const PositionPanel = ( { attributes, setAttributes, initialOpen = true }: PanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes.advancedLayout, device );
	const set = ( patch: Partial< AdvancedLayoutDevice > ) => setAttributes( { advancedLayout: patchDevice( attributes.advancedLayout, device, patch ) } );

	return (
		<PanelBody title={ __( 'Position & Overflow', 'flexa-block' ) } initialOpen={ initialOpen }>
			<SelectControl __nextHasNoMarginBottom label={ __( 'Overflow', 'flexa-block' ) } value={ value.overflow || '' } options={ OVERFLOW_OPTIONS } onChange={ ( v: string ) => set( { overflow: v } ) } />
			<TextControl __nextHasNoMarginBottom type="number" label={ __( 'Z-Index', 'flexa-block' ) } value={ value.zIndex ?? '' } onChange={ ( v: string ) => set( { zIndex: v } ) } />
		</PanelBody>
	);
};

/**
 * Visibility panel — hide per device.
 */
export const VisibilityPanel = ( { attributes, setAttributes, initialOpen = false }: PanelProps ): JSX.Element => {
	const vis = attributes.responsiveVisibility || {};
	const setVis = ( patch: Partial< ResponsiveVisibilityAttr > ) => setAttributes( { responsiveVisibility: { ...vis, ...patch } } );

	return (
		<PanelBody title={ __( 'Visibility', 'flexa-block' ) } initialOpen={ initialOpen }>
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Hide on Desktop', 'flexa-block' ) } checked={ !! vis.hideOnDesktop } onChange={ ( v: boolean ) => setVis( { hideOnDesktop: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Hide on Tablet', 'flexa-block' ) } checked={ !! vis.hideOnTablet } onChange={ ( v: boolean ) => setVis( { hideOnTablet: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Hide on Mobile', 'flexa-block' ) } checked={ !! vis.hideOnMobile } onChange={ ( v: boolean ) => setVis( { hideOnMobile: v } ) } />
		</PanelBody>
	);
};
