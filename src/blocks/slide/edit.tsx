/**
 * Slide block — editor component (child of flexa/slides).
 *
 * A single slide's content box: its own background, padding, content max-width
 * and content alignment (vertical + horizontal). Inner blocks render inside a
 * centred content wrapper so the slide previews live on the canvas.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps, useInnerBlocksProps, InnerBlocks } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';

import {
	InspectorTabs,
	Segmented,
	SliderUnit,
	FieldHead,
	SpacingPanel,
	BackgroundPanel,
	useBlockId,
	useDevice,
} from '@components';
import {
	cn,
	effective,
	rawDevice,
	patchDevice,
	withUnit,
	spacingShorthand,
	applyBackgroundPreview,
	WIDTH_UNITS,
	SPACING_UNITS,
	VStartIcon,
	VCenterIcon,
	VEndIcon,
	VBetweenIcon,
	VAroundIcon,
	HStartIcon,
	HCenterIcon,
	HEndIcon,
} from '@utils';
import type { ControlOption, DeviceKey, EditProps, LengthValue, PanelProps, SlideAttributes, SlideLayoutDevice } from '../../types';

type CssProps = Record< string, string >;

/** Vertical placement of the content (justify-content on a column flex box). */
const JUSTIFY_OPTIONS: ControlOption[] = [
	{ value: 'flex-start', label: __( 'Top', 'flexa-block' ), icon: VStartIcon },
	{ value: 'center', label: __( 'Middle', 'flexa-block' ), icon: VCenterIcon },
	{ value: 'flex-end', label: __( 'Bottom', 'flexa-block' ), icon: VEndIcon },
	{ value: 'space-between', label: __( 'Space between', 'flexa-block' ), icon: VBetweenIcon },
	{ value: 'space-around', label: __( 'Space around', 'flexa-block' ), icon: VAroundIcon },
];

/** Horizontal placement of the content (align-items on a column flex box). */
const ALIGN_OPTIONS: ControlOption[] = [
	{ value: 'flex-start', label: __( 'Left', 'flexa-block' ), icon: HStartIcon },
	{ value: 'center', label: __( 'Center', 'flexa-block' ), icon: HCenterIcon },
	{ value: 'flex-end', label: __( 'Right', 'flexa-block' ), icon: HEndIcon },
];

/**
 * Slide layout panel — content alignment, gap between blocks, content max-width.
 */
const SlideLayoutPanel = ( { attributes, setAttributes }: PanelProps< SlideAttributes > ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes.layout, device );
	const set = ( patch: Partial< SlideLayoutDevice > ) => setAttributes( { layout: patchDevice( attributes.layout, device, patch ) } );
	const gap = value.gap || {};
	const maxW = attributes.contentMaxWidth?.[ device ] || {};

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented label={ __( 'Vertical align', 'flexa-block' ) } responsive value={ value.justifyContent || 'center' } onChange={ ( v ) => set( { justifyContent: v } ) } options={ JUSTIFY_OPTIONS } />
			<Segmented label={ __( 'Horizontal align', 'flexa-block' ) } responsive value={ value.alignItems || 'center' } onChange={ ( v ) => set( { alignItems: v } ) } options={ ALIGN_OPTIONS } />
			<div className="flexa-field">
				<FieldHead label={ __( 'Gap', 'flexa-block' ) } />
				<SliderUnit
					label={ __( 'Row', 'flexa-block' ) }
					showDevice={ false }
					value={ { value: gap.row, unit: gap.unit } }
					units={ SPACING_UNITS }
					max={ { px: 200, '%': 100, em: 20, rem: 20, vh: 100, vw: 100 } }
					onChange={ ( v ) => set( { gap: { ...gap, row: v.value ?? '', unit: v.unit || gap.unit || 'px' } } ) }
				/>
				<SliderUnit
					label={ __( 'Column', 'flexa-block' ) }
					showDevice={ false }
					value={ { value: gap.column, unit: gap.unit } }
					units={ SPACING_UNITS }
					max={ { px: 200, '%': 100, em: 20, rem: 20, vh: 100, vw: 100 } }
					onChange={ ( v ) => set( { gap: { ...gap, column: v.value ?? '', unit: v.unit || gap.unit || 'px' } } ) }
				/>
			</div>
			<SliderUnit
				label={ __( 'Content max width', 'flexa-block' ) }
				value={ maxW }
				units={ WIDTH_UNITS }
				defaultUnit="px"
				max={ { px: 2000, '%': 100, vw: 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { contentMaxWidth: { ...attributes.contentMaxWidth, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/** Build the slide (flex box) preview style for the active device. */
const buildSlideStyle = ( attributes: SlideAttributes, device: DeviceKey ): CssProps => {
	const { layout, spacing, background } = attributes;
	const l = effective( layout, device );
	const sp = effective( spacing, device );
	const s: CssProps = { display: 'flex', flexDirection: 'column' };

	if ( l.justifyContent ) s.justifyContent = l.justifyContent;
	if ( l.alignItems ) s.alignItems = l.alignItems;
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;
	applyBackgroundPreview( s, background );
	return s;
};

/** Build the content wrapper preview style for the active device. */
const buildContentStyle = ( attributes: SlideAttributes, device: DeviceKey ): CssProps => {
	const { layout, contentMaxWidth } = attributes;
	const l = effective( layout, device );
	const w = effective( contentMaxWidth, device );
	const s: CssProps = { display: 'flex', flexDirection: 'column' };

	if ( l.gap ) {
		const gu = l.gap.unit || 'px';
		if ( l.gap.row ) s.rowGap = withUnit( l.gap.row, gu );
		if ( l.gap.column ) s.columnGap = withUnit( l.gap.column, gu );
	}
	if ( w.value ) s.maxWidth = withUnit( w.value, w.unit || 'px' );
	return s;
};

/**
 * Slide edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< SlideAttributes > ): JSX.Element {
	const [ device ] = useDevice();

	useBlockId( clientId, attributes.blockId, setAttributes );

	const blockProps = useBlockProps( {
		className: cn( 'swiper-slide', 'flexa-slide', attributes.blockId && `flexa-slide-${ attributes.blockId }`, attributes.className ),
		style: buildSlideStyle( attributes, device ),
	} );

	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'flexa-slide__content', style: buildContentStyle( attributes, device ) },
		{ renderAppender: InnerBlocks.ButtonBlockAppender }
	);

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-slide-inspector">
					<InspectorTabs
						layout={
							<>
								<SlideLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={ <BackgroundPanel attributes={ attributes } setAttributes={ setAttributes } /> }
						advanced={ <></> }
					/>
				</div>
			</InspectorControls>

			<div { ...blockProps }>
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
