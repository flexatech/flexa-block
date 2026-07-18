/**
 * Steps block — steps-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) plus the shared typography controls come from @components; these
 * cover the parts unique to the timeline: the ordered step items (via the shared
 * ItemListPanel), the marker (type / shape / size / colours + per-status
 * accents), the connecting line, the layout gaps and the title / description
 * styling. Following the "prefer theme styles" rule nothing is coloured by
 * default — only the values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl, TextareaControl, BaseControl, Button, Flex } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import {
	Segmented,
	SliderUnit,
	DualColor,
	FieldHead,
	TypographyControls,
	IconPicker,
	ItemListPanel,
	CONTENT_ALIGN_OPTIONS,
	useDevice,
} from '@components';
import {
	rawDevice,
	patchDevice,
	LENGTH_UNITS,
	SPACING_UNITS,
	WEIGHT_UNITS,
	LINE_STYLE_OPTIONS,
} from '@utils';
import type {
	ControlOption,
	IconValue,
	ImageMedia,
	LengthValue,
	PanelProps,
	StepItem,
	StepsAttributes,
	TypographyDevice,
} from '../../types';

type SPanelProps = PanelProps< StepsAttributes >;

/** Global marker type (few values → Segmented). */
const MARKER_TYPE_OPTIONS: ControlOption[] = [
	{ value: 'number', label: __( 'Number', 'flexa-block' ) },
	{ value: 'icon', label: __( 'Icon', 'flexa-block' ) },
	{ value: 'image', label: __( 'Image', 'flexa-block' ) },
];

/** Marker shape (few values → Segmented). */
const MARKER_SHAPE_OPTIONS: ControlOption[] = [
	{ value: 'circle', label: __( 'Circle', 'flexa-block' ) },
	{ value: 'rounded', label: __( 'Rounded', 'flexa-block' ) },
	{ value: 'square', label: __( 'Square', 'flexa-block' ) },
];

/** Per-step marker override (adds an "inherit" choice → SelectControl). */
const ITEM_MARKER_OPTIONS = [
	{ value: '', label: __( 'Follow block default', 'flexa-block' ) },
	{ value: 'number', label: __( 'Number', 'flexa-block' ) },
	{ value: 'icon', label: __( 'Icon', 'flexa-block' ) },
	{ value: 'image', label: __( 'Image', 'flexa-block' ) },
];

/** Step status (few values → SelectControl for clarity). */
const STATUS_OPTIONS = [
	{ value: '', label: __( 'None', 'flexa-block' ) },
	{ value: 'done', label: __( 'Done', 'flexa-block' ) },
	{ value: 'active', label: __( 'Active', 'flexa-block' ) },
	{ value: 'upcoming', label: __( 'Upcoming', 'flexa-block' ) },
];

/** A fresh step, used when adding one. */
const blankItem = (): StepItem => ( {
	title: '',
	description: '',
	status: 'upcoming',
	markerType: '',
	icon: { source: 'none', name: '', markup: '', url: '', id: null },
	image: { id: null, url: '' },
} );

/** The collapsed-header marker preview for one step (icon / image, else empty). */
const stepPreview = ( item: StepItem, globalType: string ): JSX.Element | undefined => {
	const type = item.markerType || globalType;
	if ( type === 'icon' ) {
		const icon = item.icon || {};
		if ( icon.markup ) {
			return <span dangerouslySetInnerHTML={ { __html: icon.markup } } />;
		}
		if ( icon.source === 'upload' && icon.url ) {
			return <img src={ icon.url } alt="" />;
		}
	}
	if ( type === 'image' && item.image?.url ) {
		return <img src={ item.image.url } alt="" />;
	}
	return undefined;
};

/** Body controls for one step: marker override, title, description and status. */
const StepItemBody = ( { item, update, globalType }: { item: StepItem; update: ( patch: Partial< StepItem > ) => void; globalType: string } ): JSX.Element => {
	const type = item.markerType || globalType;
	const image: ImageMedia = item.image || {};

	return (
		<>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Marker', 'flexa-block' ) }
				value={ item.markerType || '' }
				options={ ITEM_MARKER_OPTIONS }
				onChange={ ( v: string ) => update( { markerType: v as StepItem[ 'markerType' ] } ) }
			/>
			{ type === 'icon' && (
				<IconPicker label={ __( 'Icon', 'flexa-block' ) } value={ item.icon || {} } onChange={ ( v: IconValue ) => update( { icon: v } ) } />
			) }
			{ type === 'image' && (
				<BaseControl __nextHasNoMarginBottom label={ __( 'Image', 'flexa-block' ) }>
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ image.id ?? undefined }
							onSelect={ ( media: { id: number; url: string } ) => update( { image: { id: media.id, url: media.url } } ) }
							render={ ( { open }: { open: () => void } ) => (
								<Flex gap={ 2 } justify="flex-start">
									<Button variant="secondary" onClick={ open }>
										{ image.url ? __( 'Replace', 'flexa-block' ) : __( 'Select Image', 'flexa-block' ) }
									</Button>
									{ image.url && (
										<Button isDestructive variant="tertiary" onClick={ () => update( { image: { id: null, url: '' } } ) }>
											{ __( 'Remove', 'flexa-block' ) }
										</Button>
									) }
								</Flex>
							) }
						/>
					</MediaUploadCheck>
				</BaseControl>
			) }
			<TextControl __nextHasNoMarginBottom label={ __( 'Title', 'flexa-block' ) } value={ item.title ?? '' } onChange={ ( v: string ) => update( { title: v } ) } />
			<TextareaControl __nextHasNoMarginBottom label={ __( 'Description', 'flexa-block' ) } value={ item.description ?? '' } onChange={ ( v: string ) => update( { description: v } ) } />
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Status', 'flexa-block' ) }
				value={ item.status || '' }
				options={ STATUS_OPTIONS }
				onChange={ ( v: string ) => update( { status: v as StepItem[ 'status' ] } ) }
			/>
		</>
	);
};

/**
 * Steps panel — the ordered list, via the shared ItemListPanel (accordion cards
 * + reorder + add/remove). Each step edits its marker override, title,
 * description and status.
 */
export const StepsItemsPanel = ( { attributes, setAttributes }: SPanelProps ): JSX.Element => {
	const items: StepItem[] = Array.isArray( attributes.items ) ? attributes.items : [];
	const globalType = attributes.markerType || 'number';

	return (
		<ItemListPanel< StepItem >
			title={ __( 'Steps', 'flexa-block' ) }
			addLabel={ __( 'Add step', 'flexa-block' ) }
			items={ items }
			onChange={ ( next ) => setAttributes( { items: next } ) }
			newItem={ blankItem }
			minItems={ 1 }
			renderHeader={ ( item, index ) => ( {
				preview: stepPreview( item, globalType ),
				title: ( item.title || '' ).trim() || __( 'Step', 'flexa-block' ) + ' ' + ( index + 1 ),
			} ) }
			renderBody={ ( item, _index, update ) => <StepItemBody item={ item } update={ update } globalType={ globalType } /> }
		/>
	);
};

/**
 * Marker panel — type, shape, size and colours, plus optional per-status
 * background accents (done / active / upcoming).
 */
export const StepsMarkerPanel = ( { attributes, setAttributes }: SPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { markerType, markerShape, markerSize, markerColor, markerTextColor, doneColor, activeColor, upcomingColor } = attributes;

	return (
		<PanelBody title={ __( 'Marker', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Type', 'flexa-block' ) }
				value={ markerType || 'number' }
				onChange={ ( v ) => setAttributes( { markerType: v as StepsAttributes[ 'markerType' ] } ) }
				options={ MARKER_TYPE_OPTIONS }
			/>
			<Segmented
				label={ __( 'Shape', 'flexa-block' ) }
				value={ markerShape || 'circle' }
				onChange={ ( v ) => setAttributes( { markerShape: v as StepsAttributes[ 'markerShape' ] } ) }
				options={ MARKER_SHAPE_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Size', 'flexa-block' ) }
				value={ markerSize?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				min={ 16 }
				max={ { px: 120, em: 10, rem: 10, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { markerSize: { ...markerSize, [ device ]: v } } ) }
			/>
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ markerColor || {} } onChange={ ( v ) => setAttributes( { markerColor: v } ) } />
			<DualColor label={ __( 'Number / icon colour', 'flexa-block' ) } value={ markerTextColor || {} } onChange={ ( v ) => setAttributes( { markerTextColor: v } ) } />
			<FieldHead label={ __( 'Status accents', 'flexa-block' ) } />
			<DualColor label={ __( 'Done', 'flexa-block' ) } value={ doneColor || {} } onChange={ ( v ) => setAttributes( { doneColor: v } ) } />
			<DualColor label={ __( 'Active', 'flexa-block' ) } value={ activeColor || {} } onChange={ ( v ) => setAttributes( { activeColor: v } ) } />
			<DualColor label={ __( 'Upcoming', 'flexa-block' ) } value={ upcomingColor || {} } onChange={ ( v ) => setAttributes( { upcomingColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Connector panel — the line between markers: show toggle, style, thickness and
 * colour.
 */
export const StepsConnectorPanel = ( { attributes, setAttributes }: SPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { connectorShow, connectorStyle, connectorColor, connectorWidth } = attributes;
	const shown = connectorShow !== false;

	return (
		<PanelBody title={ __( 'Connector', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show connector', 'flexa-block' ) }
				checked={ shown }
				onChange={ ( v: boolean ) => setAttributes( { connectorShow: v } ) }
			/>
			{ shown && (
				<>
					<Segmented
						label={ __( 'Line style', 'flexa-block' ) }
						value={ connectorStyle || 'solid' }
						onChange={ ( v ) => setAttributes( { connectorStyle: v } ) }
						options={ LINE_STYLE_OPTIONS }
					/>
					<SliderUnit
						label={ __( 'Thickness', 'flexa-block' ) }
						value={ connectorWidth?.[ device ] || {} }
						units={ WEIGHT_UNITS }
						defaultUnit="px"
						min={ 1 }
						max={ { px: 16 } }
						onChange={ ( v: LengthValue ) => setAttributes( { connectorWidth: { ...connectorWidth, [ device ]: v } } ) }
					/>
					<DualColor label={ __( 'Colour', 'flexa-block' ) } value={ connectorColor || {} } onChange={ ( v ) => setAttributes( { connectorColor: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Layout panel — the marker↔content gap, the gap between steps, the content
 * alignment and the timeline max width.
 */
export const StepsLayoutPanel = ( { attributes, setAttributes }: SPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { markerGap, itemGap, contentAlign, maxWidth } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ false }>
			<SliderUnit
				label={ __( 'Gap between marker & text', 'flexa-block' ) }
				value={ markerGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { markerGap: { ...markerGap, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Gap between steps', 'flexa-block' ) }
				value={ itemGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 160, em: 16, rem: 16, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { itemGap: { ...itemGap, [ device ]: v } } ) }
			/>
			<Segmented
				label={ __( 'Text alignment', 'flexa-block' ) }
				responsive
				value={ contentAlign?.[ device ] || ( device === 'desktop' ? 'left' : '' ) || 'left' }
				onChange={ ( v ) => setAttributes( { contentAlign: { ...contentAlign, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Max width', 'flexa-block' ) }
				value={ maxWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 1200, '%': 100, vw: 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { maxWidth: { ...maxWidth, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Title panel — the step title's typography and colour.
 */
export const StepsTitlePanel = ( { attributes, setAttributes }: SPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.titleTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { titleTypography: patchDevice( attributes.titleTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Title', 'flexa-block' ) } initialOpen={ true }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Title colour', 'flexa-block' ) } value={ attributes.titleColor || {} } onChange={ ( v ) => setAttributes( { titleColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Description panel — the step description's typography and colour.
 */
export const StepsDescriptionPanel = ( { attributes, setAttributes }: SPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.descriptionTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { descriptionTypography: patchDevice( attributes.descriptionTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Description', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Description colour', 'flexa-block' ) } value={ attributes.descriptionColor || {} } onChange={ ( v ) => setAttributes( { descriptionColor: v } ) } />
		</PanelBody>
	);
};
