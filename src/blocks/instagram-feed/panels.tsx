/**
 * Instagram Feed block — block-specific inspector panels.
 *
 * The shared foundational panels (spacing / background / border / shadow /
 * position / visibility / animation) come from @components; these cover the parts
 * unique to the Instagram Feed block: the source (access token, image count,
 * cache, sort), the grid/overlay/card/carousel layout (width, per-device columns
 * and gap, square thumbnails), the content toggles (caption + limit, meta,
 * profile, link) and the caption / meta typography and the caption / meta /
 * overlay / card colours. Following the "prefer theme styles" rule the colours
 * are unset by default — only values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, RangeControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	Dimensions,
	DualColor,
	TypographyControls,
	FieldHead,
	FeedTokenControl,
	CONTAINER_WIDTH_OPTIONS,
	useDevice,
} from '@components';
import { HTML_TAGS, LENGTH_UNITS, SPACING_UNITS, rawDevice, patchDevice } from '@utils';
import type { BoxValue, InstagramFeedAttributes, LengthValue, PanelProps, TypographyDevice } from '../../types';

type IgPanelProps = PanelProps< InstagramFeedAttributes >;

/** Feed layout (four values → Segmented with short labels). */
const LAYOUT_OPTIONS = [
	{ value: 'grid', label: __( 'Grid', 'flexa-block' ) },
	{ value: 'overlay', label: __( 'Overlay', 'flexa-block' ) },
	{ value: 'card', label: __( 'Card', 'flexa-block' ) },
	{ value: 'carousel', label: __( 'Carousel', 'flexa-block' ) },
];

/** Sort order (two values → Segmented). */
const SORT_OPTIONS = [
	{ value: 'newest', label: __( 'Newest', 'flexa-block' ) },
	{ value: 'oldest', label: __( 'Oldest', 'flexa-block' ) },
];

/**
 * Source panel — the access token, how many images to show, the cache lifetime
 * and the sort order. The token stays server-side; the editor preview fetches
 * the feed through an editor-only REST proxy.
 */
export const IgSourcePanel = ( { attributes, setAttributes }: IgPanelProps ): JSX.Element => {
	const { numberOfImages, cacheTime, sortNewestFirst, htmlTag } = attributes;

	return (
		<PanelBody title={ __( 'Source', 'flexa-block' ) } initialOpen={ true }>
			<FeedTokenControl service="instagram" label={ __( 'Access token', 'flexa-block' ) } />
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Number of images', 'flexa-block' ) }
				value={ typeof numberOfImages === 'number' ? numberOfImages : 8 }
				min={ 1 }
				max={ 50 }
				onChange={ ( v?: number ) => setAttributes( { numberOfImages: v || 1 } ) }
			/>
			<Segmented
				label={ __( 'Sort order', 'flexa-block' ) }
				value={ sortNewestFirst === false ? 'oldest' : 'newest' }
				onChange={ ( v ) => setAttributes( { sortNewestFirst: v !== 'oldest' } ) }
				options={ SORT_OPTIONS }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Cache time (minutes)', 'flexa-block' ) }
				help={ __( 'How long the fetched media is cached before refreshing.', 'flexa-block' ) }
				value={ typeof cacheTime === 'number' ? cacheTime : 30 }
				min={ 5 }
				max={ 1440 }
				step={ 5 }
				onChange={ ( v?: number ) => setAttributes( { cacheTime: v ?? 30 } ) }
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
 * Layout panel — content width, the grid / overlay / card / carousel layout,
 * per-device columns and gap, and square vs original thumbnails.
 */
export const IgLayoutPanel = ( { attributes, setAttributes }: IgPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { feedLayout, containerType, widthBoxed, widthFullWidth, columns, itemGap, squareThumbnail, contentPadding, contentGap } = attributes;
	const isBoxed = ( containerType || 'boxed' ) === 'boxed';
	const widthGroup: 'widthBoxed' | 'widthFullWidth' = isBoxed ? 'widthBoxed' : 'widthFullWidth';
	const widthVal = ( isBoxed ? widthBoxed : widthFullWidth )?.[ device ] || {};

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Content Width', 'flexa-block' ) }
				value={ containerType || 'boxed' }
				onChange={ ( v ) => setAttributes( { containerType: v as InstagramFeedAttributes[ 'containerType' ] } ) }
				options={ CONTAINER_WIDTH_OPTIONS }
			/>
			<SliderUnit
				label={ isBoxed ? __( 'Max Width', 'flexa-block' ) : __( 'Width', 'flexa-block' ) }
				value={ widthVal }
				units={ LENGTH_UNITS }
				defaultUnit={ isBoxed ? 'px' : '%' }
				max={ { px: 3000, '%': 100, vw: 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => {
					const next: Partial< InstagramFeedAttributes > = {};
					next[ widthGroup ] = { ...attributes[ widthGroup ], [ device ]: { value: v.value ?? '', unit: v.unit || ( isBoxed ? 'px' : '%' ) } };
					setAttributes( next );
				} }
			/>
			<Segmented
				label={ __( 'Layout', 'flexa-block' ) }
				value={ feedLayout || 'grid' }
				onChange={ ( v ) => setAttributes( { feedLayout: v as InstagramFeedAttributes[ 'feedLayout' ] } ) }
				options={ LAYOUT_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Columns', 'flexa-block' ) }
				value={ columns?.[ device ] || {} }
				units={ [ { value: '', label: '' } ] }
				defaultUnit=""
				min={ 1 }
				max={ { '': 12 } }
				onChange={ ( v: LengthValue ) => setAttributes( { columns: { ...columns, [ device ]: { value: v.value ?? '', unit: '' } } } ) }
			/>
			<SliderUnit
				label={ __( 'Item gap', 'flexa-block' ) }
				value={ itemGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { itemGap: { ...itemGap, [ device ]: v } } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Square thumbnails', 'flexa-block' ) }
				checked={ squareThumbnail !== false }
				onChange={ ( v: boolean ) => setAttributes( { squareThumbnail: v } ) }
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
		</PanelBody>
	);
};

/**
 * Content panel — which parts to show: caption (+ word limit), the post date
 * meta, the profile username, and whether images link to the post (+ new tab).
 */
export const IgContentPanel = ( { attributes, setAttributes }: IgPanelProps ): JSX.Element => {
	const { showCaption, captionLimit, showMeta, showProfile, enableLink, openInNewTab } = attributes;

	return (
		<PanelBody title={ __( 'Content', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Caption', 'flexa-block' ) }
				checked={ showCaption !== false }
				onChange={ ( v: boolean ) => setAttributes( { showCaption: v } ) }
			/>
			{ showCaption !== false && (
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Caption length (words)', 'flexa-block' ) }
					value={ typeof captionLimit === 'number' ? captionLimit : 15 }
					min={ 0 }
					max={ 100 }
					onChange={ ( v?: number ) => setAttributes( { captionLimit: v ?? 0 } ) }
				/>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Date', 'flexa-block' ) }
				checked={ !! showMeta }
				onChange={ ( v: boolean ) => setAttributes( { showMeta: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Profile name', 'flexa-block' ) }
				checked={ !! showProfile }
				onChange={ ( v: boolean ) => setAttributes( { showProfile: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Link to post', 'flexa-block' ) }
				checked={ enableLink !== false }
				onChange={ ( v: boolean ) => setAttributes( { enableLink: v } ) }
			/>
			{ enableLink !== false && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Open links in a new tab', 'flexa-block' ) }
					checked={ openInNewTab !== false }
					onChange={ ( v: boolean ) => setAttributes( { openInNewTab: v } ) }
				/>
			) }
		</PanelBody>
	);
};

/** One typography group (caption / meta), on the active device. */
const TypoGroup = ( {
	label,
	attr,
	attributes,
	setAttributes,
}: {
	label: string;
	attr: 'captionTypography' | 'metaTypography';
	attributes: InstagramFeedAttributes;
	setAttributes: IgPanelProps[ 'setAttributes' ];
} ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes[ attr ], device );
	const set = ( patch: Partial< TypographyDevice > ) => {
		const next: Partial< InstagramFeedAttributes > = {};
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
 * Caption panel — the caption / meta typography and the caption, meta, overlay
 * and card colours. All light/dark pairs, unset by default so the feed inherits
 * the theme until the user picks a value. The overlay colour tints the hover
 * overlay in the overlay layout; the card background fills the card in the card
 * layout.
 */
export const IgCaptionPanel = ( { attributes, setAttributes }: IgPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Caption', 'flexa-block' ) } initialOpen={ true }>
		<TypoGroup label={ __( 'Caption typography', 'flexa-block' ) } attr="captionTypography" attributes={ attributes } setAttributes={ setAttributes } />
		<TypoGroup label={ __( 'Meta typography', 'flexa-block' ) } attr="metaTypography" attributes={ attributes } setAttributes={ setAttributes } />
		<DualColor label={ __( 'Caption', 'flexa-block' ) } value={ attributes.captionColor || {} } onChange={ ( v ) => setAttributes( { captionColor: v } ) } />
		<DualColor label={ __( 'Meta', 'flexa-block' ) } value={ attributes.metaColor || {} } onChange={ ( v ) => setAttributes( { metaColor: v } ) } />
		{ ( attributes.feedLayout || 'grid' ) === 'overlay' && (
			<DualColor label={ __( 'Overlay', 'flexa-block' ) } value={ attributes.overlayColor || {} } onChange={ ( v ) => setAttributes( { overlayColor: v } ) } />
		) }
		<DualColor label={ __( 'Card background', 'flexa-block' ) } value={ attributes.cardBackground || {} } onChange={ ( v ) => setAttributes( { cardBackground: v } ) } />
	</PanelBody>
);
