/**
 * RSS block — block-specific inspector panels.
 *
 * The shared foundational panels (spacing / background / border / shadow /
 * position / visibility / animation) come from @components; these cover the parts
 * unique to the RSS block: the feed source (URL, item count, cache, sort, link
 * target), the list/grid layout (width, per-device columns and gaps, card
 * alignment and padding), the item toggles (thumbnail / title / meta / excerpt /
 * read-more) and the card typography and colours. Following the "prefer theme
 * styles" rule the card colours are unset by default — only values the user picks
 * produce CSS.
 *
 * @package Flexa\Block
 */

import { __, sprintf } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl, RangeControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	Dimensions,
	DualColor,
	TypographyControls,
	FieldHead,
	CONTENT_ALIGN_OPTIONS,
	CONTAINER_WIDTH_OPTIONS,
	TEXT_TAG_OPTIONS,
	ASPECT_RATIO_OPTIONS,
	useDevice,
} from '@components';
import { HTML_TAGS, LENGTH_UNITS, SPACING_UNITS, rawDevice, patchDevice } from '@utils';
import type { BoxValue, LengthValue, PanelProps, RssAttributes, TypographyDevice } from '../../types';

type RssPanelProps = PanelProps< RssAttributes >;

/** Layout mode (two values → Segmented). */
const LAYOUT_OPTIONS = [
	{ value: 'grid', label: __( 'Grid', 'flexa-block' ) },
	{ value: 'list', label: __( 'List', 'flexa-block' ) },
];

/**
 * Source panel — the feed URL, how many items to show, the cache lifetime, the
 * sort order and whether item links open in a new tab. `maxItems` (the feed's
 * real item count, resolved by the editor preview) caps the "items to show"
 * slider at what the feed actually offers.
 */
export const RssSourcePanel = ( { attributes, setAttributes, maxItems }: RssPanelProps & { maxItems?: number } ): JSX.Element => {
	const { feedUrl, itemsToShow, cacheTime, sortNewestFirst, openInNewTab, htmlTag } = attributes;
	const sliderMax = maxItems && maxItems > 0 ? Math.min( 100, maxItems ) : 30;

	return (
		<PanelBody title={ __( 'Feed', 'flexa-block' ) } initialOpen={ true }>
			<TextControl
				__nextHasNoMarginBottom
				type="url"
				label={ __( 'Feed URL', 'flexa-block' ) }
				help={ __( 'A link to an RSS or Atom feed (e.g. https://example.com/feed).', 'flexa-block' ) }
				value={ feedUrl ?? '' }
				placeholder="https://"
				onChange={ ( v: string ) => setAttributes( { feedUrl: v } ) }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Items to show', 'flexa-block' ) }
				help={ maxItems && maxItems > 0
					? /* translators: %d: number of entries the feed offers. */ sprintf( __( 'This feed offers %d entries.', 'flexa-block' ), maxItems )
					: undefined }
				value={ typeof itemsToShow === 'number' ? itemsToShow : 5 }
				min={ 1 }
				max={ sliderMax }
				onChange={ ( v?: number ) => setAttributes( { itemsToShow: v || 1 } ) }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Cache time (minutes)', 'flexa-block' ) }
				help={ __( 'How long the fetched feed is cached before refreshing.', 'flexa-block' ) }
				value={ typeof cacheTime === 'number' ? cacheTime : 60 }
				min={ 5 }
				max={ 1440 }
				step={ 5 }
				onChange={ ( v?: number ) => setAttributes( { cacheTime: v ?? 60 } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Newest first', 'flexa-block' ) }
				checked={ sortNewestFirst !== false }
				onChange={ ( v: boolean ) => setAttributes( { sortNewestFirst: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Open links in a new tab', 'flexa-block' ) }
				checked={ openInNewTab !== false }
				onChange={ ( v: boolean ) => setAttributes( { openInNewTab: v } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'section' }
				options={ HTML_TAGS.map( ( t ) => ( { label: t, value: t } ) ) }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Layout panel — list vs grid, content width, per-device columns and gaps, card
 * alignment and padding, and equal-height cards.
 */
export const RssLayoutPanel = ( { attributes, setAttributes }: RssPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { feedLayout, containerType, widthBoxed, widthFullWidth, columns, rowGap, columnGap, equalHeight, contentAlign, contentPadding, contentGap } = attributes;
	const isBoxed = ( containerType || 'boxed' ) === 'boxed';
	const isGrid = ( feedLayout || 'grid' ) === 'grid';
	const widthGroup: 'widthBoxed' | 'widthFullWidth' = isBoxed ? 'widthBoxed' : 'widthFullWidth';
	const widthVal = ( isBoxed ? widthBoxed : widthFullWidth )?.[ device ] || {};

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Layout', 'flexa-block' ) }
				value={ feedLayout || 'grid' }
				onChange={ ( v ) => setAttributes( { feedLayout: v as RssAttributes[ 'feedLayout' ] } ) }
				options={ LAYOUT_OPTIONS }
			/>
			<Segmented
				label={ __( 'Content Width', 'flexa-block' ) }
				value={ containerType || 'boxed' }
				onChange={ ( v ) => setAttributes( { containerType: v as RssAttributes[ 'containerType' ] } ) }
				options={ CONTAINER_WIDTH_OPTIONS }
			/>
			<SliderUnit
				label={ isBoxed ? __( 'Max Width', 'flexa-block' ) : __( 'Width', 'flexa-block' ) }
				value={ widthVal }
				units={ LENGTH_UNITS }
				defaultUnit={ isBoxed ? 'px' : '%' }
				max={ { px: 3000, '%': 100, vw: 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => {
					const next: Partial< RssAttributes > = {};
					next[ widthGroup ] = { ...attributes[ widthGroup ], [ device ]: { value: v.value ?? '', unit: v.unit || ( isBoxed ? 'px' : '%' ) } };
					setAttributes( next );
				} }
			/>
			{ isGrid && (
				<SliderUnit
					label={ __( 'Columns', 'flexa-block' ) }
					value={ columns?.[ device ] || {} }
					units={ [ { value: '', label: '' } ] }
					defaultUnit=""
					min={ 1 }
					max={ { '': 12 } }
					onChange={ ( v: LengthValue ) => setAttributes( { columns: { ...columns, [ device ]: { value: v.value ?? '', unit: '' } } } ) }
				/>
			) }
			<SliderUnit
				label={ isGrid ? __( 'Row gap', 'flexa-block' ) : __( 'Item gap', 'flexa-block' ) }
				value={ rowGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { rowGap: { ...rowGap, [ device ]: v } } ) }
			/>
			{ isGrid && (
				<SliderUnit
					label={ __( 'Column gap', 'flexa-block' ) }
					value={ columnGap?.[ device ] || {} }
					units={ SPACING_UNITS }
					defaultUnit="px"
					max={ { px: 120, em: 12, rem: 12, '%': 100 } }
					onChange={ ( v: LengthValue ) => setAttributes( { columnGap: { ...columnGap, [ device ]: v } } ) }
				/>
			) }
			<Segmented
				label={ __( 'Card alignment', 'flexa-block' ) }
				responsive
				value={ contentAlign?.[ device ] || 'left' }
				onChange={ ( v ) => setAttributes( { contentAlign: { ...contentAlign, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<Dimensions
				label={ __( 'Content padding', 'flexa-block' ) }
				value={ ( contentPadding?.[ device ] || {} ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { contentPadding: { ...contentPadding, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Content gap', 'flexa-block' ) }
				value={ contentGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { contentGap: { ...contentGap, [ device ]: v } } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Equal height cards', 'flexa-block' ) }
				checked={ equalHeight !== false }
				onChange={ ( v: boolean ) => setAttributes( { equalHeight: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Elements panel — which parts of each item to show (thumbnail + ratio, title +
 * tag, meta and its sub-fields, excerpt + length, read-more link + label).
 */
export const RssElementsPanel = ( { attributes, setAttributes }: RssPanelProps ): JSX.Element => {
	const {
		showImage, imageRatio,
		showTitle, titleTag,
		showMeta, showDate, showAuthor, showSource,
		showExcerpt, excerptLength,
		showReadMore, readMoreText,
	} = attributes;

	return (
		<PanelBody title={ __( 'Elements', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Thumbnail', 'flexa-block' ) }
				checked={ showImage !== false }
				onChange={ ( v: boolean ) => setAttributes( { showImage: v } ) }
			/>
			{ showImage !== false && (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Image ratio', 'flexa-block' ) }
					value={ imageRatio || '16/9' }
					options={ [ { value: '', label: __( 'Original', 'flexa-block' ) }, ...ASPECT_RATIO_OPTIONS ] }
					onChange={ ( v: string ) => setAttributes( { imageRatio: v } ) }
				/>
			) }

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Title', 'flexa-block' ) }
				checked={ showTitle !== false }
				onChange={ ( v: boolean ) => setAttributes( { showTitle: v } ) }
			/>
			{ showTitle !== false && (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Title tag', 'flexa-block' ) }
					value={ titleTag || 'h3' }
					options={ TEXT_TAG_OPTIONS }
					onChange={ ( v: string ) => setAttributes( { titleTag: v } ) }
				/>
			) }

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Meta', 'flexa-block' ) }
				checked={ showMeta !== false }
				onChange={ ( v: boolean ) => setAttributes( { showMeta: v } ) }
			/>
			{ showMeta !== false && (
				<>
					<ToggleControl __nextHasNoMarginBottom label={ __( 'Date', 'flexa-block' ) } checked={ showDate !== false } onChange={ ( v: boolean ) => setAttributes( { showDate: v } ) } />
					<ToggleControl __nextHasNoMarginBottom label={ __( 'Author', 'flexa-block' ) } checked={ showAuthor !== false } onChange={ ( v: boolean ) => setAttributes( { showAuthor: v } ) } />
					<ToggleControl __nextHasNoMarginBottom label={ __( 'Source', 'flexa-block' ) } checked={ !! showSource } onChange={ ( v: boolean ) => setAttributes( { showSource: v } ) } />
				</>
			) }

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Excerpt', 'flexa-block' ) }
				checked={ showExcerpt !== false }
				onChange={ ( v: boolean ) => setAttributes( { showExcerpt: v } ) }
			/>
			{ showExcerpt !== false && (
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Excerpt length (words)', 'flexa-block' ) }
					value={ typeof excerptLength === 'number' ? excerptLength : 20 }
					min={ 0 }
					max={ 100 }
					onChange={ ( v?: number ) => setAttributes( { excerptLength: v ?? 0 } ) }
				/>
			) }

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Read more link', 'flexa-block' ) }
				checked={ showReadMore !== false }
				onChange={ ( v: boolean ) => setAttributes( { showReadMore: v } ) }
			/>
			{ showReadMore !== false && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Read more text', 'flexa-block' ) }
					value={ readMoreText ?? '' }
					onChange={ ( v: string ) => setAttributes( { readMoreText: v } ) }
				/>
			) }
		</PanelBody>
	);
};

/** One typography group (title / meta / excerpt), on the active device. */
const TypoGroup = ( {
	label,
	attr,
	attributes,
	setAttributes,
}: {
	label: string;
	attr: 'titleTypography' | 'metaTypography' | 'excerptTypography';
	attributes: RssAttributes;
	setAttributes: RssPanelProps[ 'setAttributes' ];
} ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes[ attr ], device );
	const set = ( patch: Partial< TypographyDevice > ) => {
		const next: Partial< RssAttributes > = {};
		next[ attr ] = patchDevice( attributes[ attr ], device, patch );
		setAttributes( next );
	};

	return (
		<div className="flexa-field">
			<FieldHead label={ label } />
			<TypographyControls value={ value } onChange={ set } />
		</div>
	);
};

/**
 * Typography panel — per-device typography for the card title, meta and excerpt.
 */
export const RssTypographyPanel = ( { attributes, setAttributes }: RssPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Typography', 'flexa-block' ) } initialOpen={ false }>
		<TypoGroup label={ __( 'Title', 'flexa-block' ) } attr="titleTypography" attributes={ attributes } setAttributes={ setAttributes } />
		<TypoGroup label={ __( 'Meta', 'flexa-block' ) } attr="metaTypography" attributes={ attributes } setAttributes={ setAttributes } />
		<TypoGroup label={ __( 'Excerpt', 'flexa-block' ) } attr="excerptTypography" attributes={ attributes } setAttributes={ setAttributes } />
	</PanelBody>
);

/**
 * Colours panel — card title / meta / excerpt text colours and the card
 * background. All light/dark pairs, unset by default so the card inherits the
 * theme until the user picks a value. (The read-more button has its own panel.)
 */
export const RssColorsPanel = ( { attributes, setAttributes }: RssPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Colours', 'flexa-block' ) } initialOpen={ true }>
		<DualColor label={ __( 'Title', 'flexa-block' ) } value={ attributes.titleColor || {} } onChange={ ( v ) => setAttributes( { titleColor: v } ) } />
		<DualColor label={ __( 'Meta', 'flexa-block' ) } value={ attributes.metaColor || {} } onChange={ ( v ) => setAttributes( { metaColor: v } ) } />
		<DualColor label={ __( 'Excerpt', 'flexa-block' ) } value={ attributes.excerptColor || {} } onChange={ ( v ) => setAttributes( { excerptColor: v } ) } />
		<DualColor label={ __( 'Card background', 'flexa-block' ) } value={ attributes.cardBackground || {} } onChange={ ( v ) => setAttributes( { cardBackground: v } ) } />
	</PanelBody>
);

/**
 * Button panel — the read-more link styling: width, alignment, typography, text +
 * background colours (base and hover), corner radius and padding. Empty values
 * inherit the theme's button look (the link keeps `wp-element-button`); only what
 * the user sets produces CSS. Rendered only when the read-more link is shown.
 */
export const RssButtonPanel = ( { attributes, setAttributes }: RssPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.buttonTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { buttonTypography: patchDevice( attributes.buttonTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Button', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Width', 'flexa-block' ) }
				value={ attributes.buttonWidth || 'auto' }
				onChange={ ( v ) => setAttributes( { buttonWidth: v as RssAttributes[ 'buttonWidth' ] } ) }
				options={ [ { value: 'auto', label: __( 'Boxed', 'flexa-block' ) }, { value: 'full', label: __( 'Full width', 'flexa-block' ) } ] }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				value={ attributes.buttonAlign || 'left' }
				onChange={ ( v ) => setAttributes( { buttonAlign: v as RssAttributes[ 'buttonAlign' ] } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.buttonTextColor || {} } onChange={ ( v ) => setAttributes( { buttonTextColor: v } ) } />
			<DualColor label={ __( 'Text colour (hover)', 'flexa-block' ) } value={ attributes.buttonTextColorHover || {} } onChange={ ( v ) => setAttributes( { buttonTextColorHover: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ attributes.buttonBackground || {} } onChange={ ( v ) => setAttributes( { buttonBackground: v } ) } />
			<DualColor label={ __( 'Background (hover)', 'flexa-block' ) } value={ attributes.buttonBackgroundHover || {} } onChange={ ( v ) => setAttributes( { buttonBackgroundHover: v } ) } />
			<SliderUnit
				label={ __( 'Corner radius', 'flexa-block' ) }
				value={ attributes.buttonRadius || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 10, rem: 10, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { buttonRadius: v } ) }
			/>
			<Dimensions
				label={ __( 'Padding', 'flexa-block' ) }
				value={ ( attributes.buttonPadding || {} ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { buttonPadding: v } ) }
			/>
		</PanelBody>
	);
};
