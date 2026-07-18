/**
 * Timeline block — timeline-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) plus the shared typography controls come from @components; these
 * cover the parts unique to the timeline: the chronological entries (via the
 * shared ItemListPanel), the alternate/left/right layout with its gaps, the
 * marker node, the connecting line and the date / title / description styling.
 * Following the "prefer theme styles" rule nothing is coloured by default — only
 * the values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, TextControl, TextareaControl, BaseControl, Button, Flex, FlexBlock, FlexItem, Dropdown, DatePicker } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { dateI18n } from '@wordpress/date';

import {
	Segmented,
	SliderUnit,
	Dimensions,
	DualColor,
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
	BoxValue,
	ControlOption,
	IconValue,
	ImageMedia,
	LengthValue,
	PanelProps,
	TimelineItem,
	TimelineAttributes,
	TypographyDevice,
} from '../../types';

type TPanelProps = PanelProps< TimelineAttributes >;

/** How the entries are arranged around the connector line (few values → Segmented). */
const LAYOUT_OPTIONS: ControlOption[] = [
	{ value: 'alternate', label: __( 'Alternate', 'flexa-block' ) },
	{ value: 'left', label: __( 'Left', 'flexa-block' ) },
	{ value: 'right', label: __( 'Right', 'flexa-block' ) },
];

/** Marker shape (few values → Segmented). */
const MARKER_SHAPE_OPTIONS: ControlOption[] = [
	{ value: 'circle', label: __( 'Circle', 'flexa-block' ) },
	{ value: 'rounded', label: __( 'Rounded', 'flexa-block' ) },
	{ value: 'square', label: __( 'Square', 'flexa-block' ) },
];

/** Where the date sits relative to the title (two values → Segmented). */
const DATE_POSITION_OPTIONS: ControlOption[] = [
	{ value: 'above', label: __( 'Above title', 'flexa-block' ) },
	{ value: 'inline', label: __( 'Inline', 'flexa-block' ) },
];

/** Where a per-entry image sits within its content box (two values → Segmented). */
const IMAGE_POSITION_OPTIONS: ControlOption[] = [
	{ value: 'top', label: __( 'Above text', 'flexa-block' ) },
	{ value: 'bottom', label: __( 'Below text', 'flexa-block' ) },
];

/** A fresh entry, used when adding one. */
const blankItem = (): TimelineItem => ( {
	title: '',
	description: '',
	date: '',
	icon: { source: 'none', name: '', markup: '', url: '', id: null },
	image: { id: null, url: '', alt: '' },
} );

/** The collapsed-header marker preview for one entry (icon, else the image, else empty). */
const itemPreview = ( item: TimelineItem ): JSX.Element | undefined => {
	const icon = item.icon || {};
	if ( icon.markup ) {
		return <span dangerouslySetInnerHTML={ { __html: icon.markup } } />;
	}
	if ( icon.source === 'upload' && icon.url ) {
		return <img src={ icon.url } alt="" />;
	}
	if ( item.image?.url ) {
		return <img src={ item.image.url } alt="" />;
	}
	return undefined;
};

/** Date field: free text (so "2019"/"Q1 2020" work) plus an optional calendar picker. */
const TimelineDateField = ( { item, update }: { item: TimelineItem; update: ( patch: Partial< TimelineItem > ) => void } ): JSX.Element => (
	<BaseControl __nextHasNoMarginBottom label={ __( 'Date', 'flexa-block' ) }>
		<Flex gap={ 2 } align="center">
			<FlexBlock>
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					hideLabelFromVision
					label={ __( 'Date', 'flexa-block' ) }
					value={ item.date ?? '' }
					placeholder={ __( 'e.g. 2019', 'flexa-block' ) }
					onChange={ ( v: string ) => update( { date: v } ) }
				/>
			</FlexBlock>
			<FlexItem>
				<Dropdown
					popoverProps={ { placement: 'left-start' } }
					renderToggle={ ( { isOpen, onToggle }: { isOpen: boolean; onToggle: () => void } ) => (
						<Button
							icon="calendar-alt"
							label={ __( 'Pick a date', 'flexa-block' ) }
							aria-expanded={ isOpen }
							variant="secondary"
							onClick={ onToggle }
							style={ { height: '40px', width: '40px', flexShrink: 0, marginBottom: '12px' } }
						/>
					) }
					renderContent={ () => (
						<DatePicker onChange={ ( d: string ) => update( { date: dateI18n( 'F j, Y', d ) } ) } />
					) }
				/>
			</FlexItem>
		</Flex>
	</BaseControl>
);

/** Body controls for one entry: marker icon, optional image, date, title and description. */
const TimelineItemBody = ( { item, update }: { item: TimelineItem; update: ( patch: Partial< TimelineItem > ) => void } ): JSX.Element => {
	const image: ImageMedia = item.image || {};

	return (
		<>
			<IconPicker label={ __( 'Marker icon', 'flexa-block' ) } value={ item.icon || {} } onChange={ ( v: IconValue ) => update( { icon: v } ) } />
			<BaseControl __nextHasNoMarginBottom label={ __( 'Image', 'flexa-block' ) }>
				<MediaUploadCheck>
					<MediaUpload
						allowedTypes={ [ 'image' ] }
						value={ image.id ?? undefined }
						onSelect={ ( media: { id: number; url: string; alt?: string } ) => update( { image: { id: media.id, url: media.url, alt: media.alt ?? '' } } ) }
						render={ ( { open }: { open: () => void } ) => (
							<Flex gap={ 2 } justify="flex-start">
								<Button variant="secondary" onClick={ open }>
									{ image.url ? __( 'Replace', 'flexa-block' ) : __( 'Select Image', 'flexa-block' ) }
								</Button>
								{ image.url && (
									<Button isDestructive variant="tertiary" onClick={ () => update( { image: { id: null, url: '', alt: '' } } ) }>
										{ __( 'Remove', 'flexa-block' ) }
									</Button>
								) }
							</Flex>
						) }
					/>
				</MediaUploadCheck>
			</BaseControl>
			<TimelineDateField item={ item } update={ update } />
			<TextControl __nextHasNoMarginBottom label={ __( 'Title', 'flexa-block' ) } value={ item.title ?? '' } onChange={ ( v: string ) => update( { title: v } ) } />
			<TextareaControl __nextHasNoMarginBottom label={ __( 'Description', 'flexa-block' ) } value={ item.description ?? '' } onChange={ ( v: string ) => update( { description: v } ) } />
		</>
	);
};

/**
 * Timeline panel — the chronological entries, via the shared ItemListPanel
 * (accordion cards + reorder + add/remove). Each entry edits its marker icon,
 * date, title and description.
 */
export const TimelineItemsPanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
	const items: TimelineItem[] = Array.isArray( attributes.items ) ? attributes.items : [];

	return (
		<ItemListPanel< TimelineItem >
			title={ __( 'Timeline', 'flexa-block' ) }
			addLabel={ __( 'Add event', 'flexa-block' ) }
			items={ items }
			onChange={ ( next ) => setAttributes( { items: next } ) }
			newItem={ blankItem }
			minItems={ 1 }
			renderHeader={ ( item, index ) => ( {
				preview: itemPreview( item ),
				title: ( item.title || '' ).trim() || __( 'Event', 'flexa-block' ) + ' ' + ( index + 1 ),
			} ) }
			renderBody={ ( item, _index, update ) => <TimelineItemBody item={ item } update={ update } /> }
		/>
	);
};

/**
 * Layout panel — the alternate/left/right arrangement, the marker↔content gap,
 * the gap between events, the content alignment and the timeline max width.
 */
export const TimelineLayoutPanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { timelineLayout, markerGap, itemGap, contentAlign, maxWidth, cardPadding } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Arrangement', 'flexa-block' ) }
				value={ timelineLayout || 'alternate' }
				onChange={ ( v ) => setAttributes( { timelineLayout: v as TimelineAttributes[ 'timelineLayout' ] } ) }
				options={ LAYOUT_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Gap between marker & text', 'flexa-block' ) }
				value={ markerGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { markerGap: { ...markerGap, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Gap between events', 'flexa-block' ) }
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
			<Dimensions
				label={ __( 'Card padding', 'flexa-block' ) }
				value={ ( cardPadding?.[ device ] || {} ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { cardPadding: { ...cardPadding, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Image panel — the optional per-entry image: where it sits (above/below the
 * text), how it aligns within the card (left/centre/right) and its display width.
 */
export const TimelineImagePanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { imagePosition, imageAlign, imageWidth } = attributes;

	return (
		<PanelBody title={ __( 'Image', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Position', 'flexa-block' ) }
				value={ imagePosition || 'top' }
				onChange={ ( v ) => setAttributes( { imagePosition: v as TimelineAttributes[ 'imagePosition' ] } ) }
				options={ IMAGE_POSITION_OPTIONS }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				value={ imageAlign || 'left' }
				onChange={ ( v ) => setAttributes( { imageAlign: v as TimelineAttributes[ 'imageAlign' ] } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Width', 'flexa-block' ) }
				value={ imageWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="%"
				max={ { px: 800, '%': 100, vw: 100, rem: 60 } }
				onChange={ ( v: LengthValue ) => setAttributes( { imageWidth: { ...imageWidth, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Marker panel — the node's shape, size and colours (background + icon tint).
 */
export const TimelineMarkerPanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { markerShape, markerSize, markerColor, markerIconColor } = attributes;

	return (
		<PanelBody title={ __( 'Marker', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Shape', 'flexa-block' ) }
				value={ markerShape || 'circle' }
				onChange={ ( v ) => setAttributes( { markerShape: v as TimelineAttributes[ 'markerShape' ] } ) }
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
			<DualColor label={ __( 'Icon colour', 'flexa-block' ) } value={ markerIconColor || {} } onChange={ ( v ) => setAttributes( { markerIconColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Connector panel — the line between markers: show toggle, style, thickness and
 * colour.
 */
export const TimelineConnectorPanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
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
						onChange={ ( v ) => setAttributes( { connectorStyle: v as TimelineAttributes[ 'connectorStyle' ] } ) }
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
 * Date panel — where the date sits plus its typography and colour.
 */
export const TimelineDatePanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.dateTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { dateTypography: patchDevice( attributes.dateTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Date', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Position', 'flexa-block' ) }
				value={ attributes.datePosition || 'above' }
				onChange={ ( v ) => setAttributes( { datePosition: v as TimelineAttributes[ 'datePosition' ] } ) }
				options={ DATE_POSITION_OPTIONS }
			/>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Date colour', 'flexa-block' ) } value={ attributes.dateColor || {} } onChange={ ( v ) => setAttributes( { dateColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Title panel — the event title's typography and colour.
 */
export const TimelineTitlePanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.titleTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { titleTypography: patchDevice( attributes.titleTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Title', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Title colour', 'flexa-block' ) } value={ attributes.titleColor || {} } onChange={ ( v ) => setAttributes( { titleColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Description panel — the event description's typography and colour.
 */
export const TimelineDescriptionPanel = ( { attributes, setAttributes }: TPanelProps ): JSX.Element => {
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
