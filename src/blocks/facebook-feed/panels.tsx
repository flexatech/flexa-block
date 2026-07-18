/**
 * Facebook Feed block — block-specific inspector panels.
 *
 * The shared foundational panels (spacing / background / border / shadow /
 * position / visibility / animation / pagination) come from @components; these
 * cover the parts unique to the Facebook Feed block: the source (Page access
 * token + id, post count, sort, cache), the grid / list / masonry / carousel
 * layout (width, per-device columns and gaps), the content toggles (avatar / page
 * name / timestamp / image / message / reactions / comments / shares / link) and
 * the card typography and colours. Following the "prefer theme styles" rule the
 * card colours are unset by default — only values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl, RangeControl } from '@wordpress/components';

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
import type { BoxValue, FacebookFeedAttributes, LengthValue, PanelProps, TypographyDevice } from '../../types';

type FbPanelProps = PanelProps< FacebookFeedAttributes >;

/** Feed layout modes. */
const LAYOUT_OPTIONS = [
	{ value: 'grid', label: __( 'Grid', 'flexa-block' ) },
	{ value: 'list', label: __( 'List', 'flexa-block' ) },
	{ value: 'masonry', label: __( 'Masonry', 'flexa-block' ) },
	{ value: 'carousel', label: __( 'Carousel', 'flexa-block' ) },
];

/** Sort order (two values → Segmented). */
const SORT_OPTIONS = [
	{ value: 'newest', label: __( 'Newest', 'flexa-block' ) },
	{ value: 'oldest', label: __( 'Oldest', 'flexa-block' ) },
];

/**
 * Source panel — the Page access token (stored server-side, never printed), the
 * Page id, how many posts to show, the sort order and the cache lifetime.
 */
export const FbSourcePanel = ( { attributes, setAttributes }: FbPanelProps ): JSX.Element => {
	const { pageId, numberOfPosts, sortNewestFirst, cacheTime, htmlTag } = attributes;

	return (
		<PanelBody title={ __( 'Source', 'flexa-block' ) } initialOpen={ true }>
			<FeedTokenControl service="facebook" label={ __( 'Page access token', 'flexa-block' ) } />
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Page ID', 'flexa-block' ) }
				help={ __( 'Leave empty for the token’s own page.', 'flexa-block' ) }
				value={ pageId ?? '' }
				onChange={ ( v: string ) => setAttributes( { pageId: v } ) }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Number of posts', 'flexa-block' ) }
				value={ typeof numberOfPosts === 'number' ? numberOfPosts : 6 }
				min={ 1 }
				max={ 50 }
				onChange={ ( v?: number ) => setAttributes( { numberOfPosts: v || 1 } ) }
			/>
			<Segmented
				label={ __( 'Sort order', 'flexa-block' ) }
				value={ sortNewestFirst === false ? 'oldest' : 'newest' }
				onChange={ ( v ) => setAttributes( { sortNewestFirst: v === 'newest' } ) }
				options={ SORT_OPTIONS }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Cache time (minutes)', 'flexa-block' ) }
				help={ __( 'How long the fetched posts are cached before refreshing.', 'flexa-block' ) }
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
 * Layout panel — feed layout, content width, per-device columns (grid / masonry)
 * and the gap between posts.
 */
export const FbLayoutPanel = ( { attributes, setAttributes }: FbPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { feedLayout, containerType, widthBoxed, widthFullWidth, columns, itemGap, contentPadding, contentGap } = attributes;
	const isBoxed = ( containerType || 'boxed' ) === 'boxed';
	const layout = feedLayout || 'grid';
	const hasColumns = layout === 'grid' || layout === 'masonry';
	const widthGroup: 'widthBoxed' | 'widthFullWidth' = isBoxed ? 'widthBoxed' : 'widthFullWidth';
	const widthVal = ( isBoxed ? widthBoxed : widthFullWidth )?.[ device ] || {};

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Content Width', 'flexa-block' ) }
				value={ containerType || 'boxed' }
				onChange={ ( v ) => setAttributes( { containerType: v as FacebookFeedAttributes[ 'containerType' ] } ) }
				options={ CONTAINER_WIDTH_OPTIONS }
			/>
			<SliderUnit
				label={ isBoxed ? __( 'Max Width', 'flexa-block' ) : __( 'Width', 'flexa-block' ) }
				value={ widthVal }
				units={ LENGTH_UNITS }
				defaultUnit={ isBoxed ? 'px' : '%' }
				max={ { px: 3000, '%': 100, vw: 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => {
					const next: Partial< FacebookFeedAttributes > = {};
					next[ widthGroup ] = { ...attributes[ widthGroup ], [ device ]: { value: v.value ?? '', unit: v.unit || ( isBoxed ? 'px' : '%' ) } };
					setAttributes( next );
				} }
			/>
			<Segmented
				label={ __( 'Layout', 'flexa-block' ) }
				value={ layout }
				onChange={ ( v ) => setAttributes( { feedLayout: v as FacebookFeedAttributes[ 'feedLayout' ] } ) }
				options={ LAYOUT_OPTIONS }
			/>
			{ hasColumns && (
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
				label={ __( 'Gap between posts', 'flexa-block' ) }
				value={ itemGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { itemGap: { ...itemGap, [ device ]: v } } ) }
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
 * Content panel — which parts of each post to show (avatar, page name, timestamp,
 * image, message + length limit, reactions / comments / shares counts) and the
 * link behaviour.
 */
export const FbContentPanel = ( { attributes, setAttributes }: FbPanelProps ): JSX.Element => {
	const {
		showAvatar, showPageName, showTimestamp, showImage,
		showMessage, messageLimit,
		showReactions, showComments, showShares,
		enableLink, openInNewTab,
	} = attributes;

	return (
		<PanelBody title={ __( 'Content', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Profile avatar', 'flexa-block' ) } checked={ showAvatar !== false } onChange={ ( v: boolean ) => setAttributes( { showAvatar: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Page name', 'flexa-block' ) } checked={ showPageName !== false } onChange={ ( v: boolean ) => setAttributes( { showPageName: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Timestamp', 'flexa-block' ) } checked={ showTimestamp !== false } onChange={ ( v: boolean ) => setAttributes( { showTimestamp: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Image', 'flexa-block' ) } checked={ showImage !== false } onChange={ ( v: boolean ) => setAttributes( { showImage: v } ) } />

			<ToggleControl __nextHasNoMarginBottom label={ __( 'Message', 'flexa-block' ) } checked={ showMessage !== false } onChange={ ( v: boolean ) => setAttributes( { showMessage: v } ) } />
			{ showMessage !== false && (
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Message length (words)', 'flexa-block' ) }
					value={ typeof messageLimit === 'number' ? messageLimit : 20 }
					min={ 0 }
					max={ 100 }
					onChange={ ( v?: number ) => setAttributes( { messageLimit: v ?? 0 } ) }
				/>
			) }

			<ToggleControl __nextHasNoMarginBottom label={ __( 'Reactions count', 'flexa-block' ) } checked={ showReactions !== false } onChange={ ( v: boolean ) => setAttributes( { showReactions: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Comments count', 'flexa-block' ) } checked={ !! showComments } onChange={ ( v: boolean ) => setAttributes( { showComments: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Shares count', 'flexa-block' ) } checked={ showShares !== false } onChange={ ( v: boolean ) => setAttributes( { showShares: v } ) } />

			<ToggleControl __nextHasNoMarginBottom label={ __( 'Link posts to Facebook', 'flexa-block' ) } checked={ enableLink !== false } onChange={ ( v: boolean ) => setAttributes( { enableLink: v } ) } />
			{ enableLink !== false && (
				<ToggleControl __nextHasNoMarginBottom label={ __( 'Open links in a new tab', 'flexa-block' ) } checked={ openInNewTab !== false } onChange={ ( v: boolean ) => setAttributes( { openInNewTab: v } ) } />
			) }
		</PanelBody>
	);
};

/** One typography group (header / message / meta), on the active device. */
const TypoGroup = ( {
	label,
	attr,
	attributes,
	setAttributes,
}: {
	label: string;
	attr: 'headerTypography' | 'messageTypography' | 'metaTypography';
	attributes: FacebookFeedAttributes;
	setAttributes: FbPanelProps[ 'setAttributes' ];
} ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes[ attr ], device );
	const set = ( patch: Partial< TypographyDevice > ) => {
		const next: Partial< FacebookFeedAttributes > = {};
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
 * Typography panel — per-device typography for the card header (page name),
 * message and meta (timestamp + counts).
 */
export const FbTypographyPanel = ( { attributes, setAttributes }: FbPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Typography', 'flexa-block' ) } initialOpen={ false }>
		<TypoGroup label={ __( 'Header', 'flexa-block' ) } attr="headerTypography" attributes={ attributes } setAttributes={ setAttributes } />
		<TypoGroup label={ __( 'Message', 'flexa-block' ) } attr="messageTypography" attributes={ attributes } setAttributes={ setAttributes } />
		<TypoGroup label={ __( 'Meta', 'flexa-block' ) } attr="metaTypography" attributes={ attributes } setAttributes={ setAttributes } />
	</PanelBody>
);

/**
 * Colours panel — card header / message / meta text colours and the card
 * background. All light/dark pairs, unset by default so the card inherits the
 * theme until the user picks a value.
 */
export const FbColorsPanel = ( { attributes, setAttributes }: FbPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Colours', 'flexa-block' ) } initialOpen={ true }>
		<DualColor label={ __( 'Header', 'flexa-block' ) } value={ attributes.headerColor || {} } onChange={ ( v ) => setAttributes( { headerColor: v } ) } />
		<DualColor label={ __( 'Message', 'flexa-block' ) } value={ attributes.messageColor || {} } onChange={ ( v ) => setAttributes( { messageColor: v } ) } />
		<DualColor label={ __( 'Meta', 'flexa-block' ) } value={ attributes.metaColor || {} } onChange={ ( v ) => setAttributes( { metaColor: v } ) } />
		<DualColor label={ __( 'Card background', 'flexa-block' ) } value={ attributes.cardBackground || {} } onChange={ ( v ) => setAttributes( { cardBackground: v } ) } />
	</PanelBody>
);
