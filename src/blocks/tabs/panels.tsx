/**
 * Tabs block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) plus the shared typography controls come from @components; these
 * cover the parts unique to the tabs: the repeatable tab list (edited through the
 * shared ItemListPanel), the tab style / alignment, the optional per-tab icon,
 * the tab and content typography, colours and padding. Following the "prefer
 * theme styles" rule nothing is coloured by default — only values the user picks
 * produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl, TextareaControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	Dimensions,
	DualColor,
	ColorGradientControl,
	TypographyControls,
	IconPicker,
	ItemListPanel,
	TEXT_ALIGN_OPTIONS,
	useDevice,
} from '@components';
import {
	rawDevice,
	patchDevice,
	HTML_TAGS,
	SPACING_UNITS,
	LENGTH_UNITS,
} from '@utils';
import type { BoxValue, LengthValue, PanelProps, TabItem, TabsAttributes, TypographyDevice } from '../../types';

type TabsPanelProps = PanelProps< TabsAttributes >;

/**
 * Tab style choices (three labelled values → a Segmented control). Unique to
 * this block — the underline / pill / boxed treatments are not the shared
 * boxed/full-width container-width set, so they stay local here.
 */
const TAB_STYLE_OPTIONS = [
	{ label: __( 'Underline', 'flexa-block' ), value: 'underline' },
	{ label: __( 'Pill', 'flexa-block' ), value: 'pill' },
	{ label: __( 'Boxed', 'flexa-block' ), value: 'boxed' },
];

/** A fresh tab, used when adding one. */
const blankTab = (): TabItem => ( { label: '', content: '', icon: {} } );

/** Body controls for one tab: its label, an optional icon and its content. */
const TabBody = ( { item, showIcon, update }: { item: TabItem; showIcon: boolean; update: ( patch: Partial< TabItem > ) => void } ): JSX.Element => (
	<>
		<TextControl __nextHasNoMarginBottom label={ __( 'Label', 'flexa-block' ) } value={ item.label ?? '' } onChange={ ( v: string ) => update( { label: v } ) } />
		{ showIcon && (
			<IconPicker
				label={ __( 'Icon', 'flexa-block' ) }
				value={ item.icon || {} }
				onChange={ ( v ) => update( { icon: v } ) }
			/>
		) }
		<TextareaControl
			__nextHasNoMarginBottom
			label={ __( 'Content', 'flexa-block' ) }
			value={ item.content ?? '' }
			onChange={ ( v: string ) => update( { content: v } ) }
			rows={ 5 }
		/>
	</>
);

/**
 * Tabs panel — the repeatable tabs, via the shared ItemListPanel (accordion
 * cards + reorder + add/remove). Each card edits a tab's label, icon and content.
 */
export const TabsListPanel = ( { attributes, setAttributes }: TabsPanelProps ): JSX.Element => {
	const tabs: TabItem[] = Array.isArray( attributes.tabs ) ? attributes.tabs : [];
	const showIcon = attributes.showIcon !== false;

	return (
		<ItemListPanel< TabItem >
			title={ __( 'Tabs', 'flexa-block' ) }
			addLabel={ __( 'Add tab', 'flexa-block' ) }
			items={ tabs }
			onChange={ ( next ) => setAttributes( { tabs: next } ) }
			newItem={ blankTab }
			minItems={ 1 }
			renderHeader={ ( item, index ) => ( {
				preview: item.icon?.markup ? <span dangerouslySetInnerHTML={ { __html: item.icon.markup } } /> : undefined,
				title: ( item.label || '' ).trim() || __( 'Tab', 'flexa-block' ) + ' ' + ( index + 1 ),
			} ) }
			renderBody={ ( item, _index, update ) => <TabBody item={ item } showIcon={ showIcon } update={ update } /> }
		/>
	);
};

/**
 * Layout panel — the tab style, alignment, the gap between tabs, the trail max
 * width and the wrapper tag.
 */
export const TabsLayoutPanel = ( { attributes, setAttributes }: TabsPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { tabStyle, tabAlign, tabGap, maxWidth, htmlTag } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Tab style', 'flexa-block' ) }
				value={ tabStyle || 'underline' }
				onChange={ ( v ) => setAttributes( { tabStyle: v as TabsAttributes[ 'tabStyle' ] } ) }
				options={ TAB_STYLE_OPTIONS }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				value={ tabAlign || 'left' }
				onChange={ ( v ) => setAttributes( { tabAlign: v as TabsAttributes[ 'tabAlign' ] } ) }
				options={ TEXT_ALIGN_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Gap between tabs', 'flexa-block' ) }
				value={ tabGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { tabGap: { ...tabGap, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Max width', 'flexa-block' ) }
				value={ maxWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 1600, '%': 100, vw: 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { maxWidth: { ...maxWidth, [ device ]: v } } ) }
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
 * Icon panel — whether tabs show their icon, its size and the gap from the label.
 */
export const TabsIconPanel = ( { attributes, setAttributes }: TabsPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { showIcon, iconSize, iconGap } = attributes;

	return (
		<PanelBody title={ __( 'Icon', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Show icons', 'flexa-block' ) } checked={ showIcon !== false } onChange={ ( v: boolean ) => setAttributes( { showIcon: v } ) } />
			{ showIcon !== false && (
				<>
					<SliderUnit
						label={ __( 'Icon size', 'flexa-block' ) }
						value={ iconSize?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						max={ { px: 64, em: 6, rem: 6, '%': 200 } }
						onChange={ ( v: LengthValue ) => setAttributes( { iconSize: { ...iconSize, [ device ]: v } } ) }
					/>
					<SliderUnit
						label={ __( 'Gap from label', 'flexa-block' ) }
						value={ iconGap?.[ device ] || {} }
						units={ SPACING_UNITS }
						defaultUnit="px"
						max={ { px: 40, em: 4, rem: 4, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { iconGap: { ...iconGap, [ device ]: v } } ) }
					/>
				</>
			) }
		</PanelBody>
	);
};

/**
 * Tabs style panel — the tab labels' typography, colours (normal / hover /
 * active), the active indicator (colour|gradient) and the tab padding.
 */
export const TabsBarPanel = ( { attributes, setAttributes }: TabsPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.tabTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { tabTypography: patchDevice( attributes.tabTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Tabs', 'flexa-block' ) } initialOpen={ true }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.tabColor || {} } onChange={ ( v ) => setAttributes( { tabColor: v } ) } />
			<DualColor label={ __( 'Text colour (hover)', 'flexa-block' ) } value={ attributes.tabHoverColor || {} } onChange={ ( v ) => setAttributes( { tabHoverColor: v } ) } />
			<DualColor label={ __( 'Text colour (active)', 'flexa-block' ) } value={ attributes.tabActiveColor || {} } onChange={ ( v ) => setAttributes( { tabActiveColor: v } ) } />
			<ColorGradientControl
				label={ __( 'Active indicator', 'flexa-block' ) }
				type={ attributes.tabActiveIndicatorType || 'color' }
				color={ attributes.tabActiveIndicator || {} }
				gradient={ attributes.tabActiveIndicatorGradient || {} }
				onTypeChange={ ( v ) => setAttributes( { tabActiveIndicatorType: v } ) }
				onColorChange={ ( v ) => setAttributes( { tabActiveIndicator: v } ) }
				onGradientChange={ ( v ) => setAttributes( { tabActiveIndicatorGradient: v } ) }
			/>
			<Dimensions
				label={ __( 'Tab padding', 'flexa-block' ) }
				responsive
				value={ rawDevice( attributes.tabPadding, device ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { tabPadding: { ...attributes.tabPadding, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Content panel — the panel content's typography, text colour, background
 * (colour|gradient) and padding.
 */
export const TabsContentPanel = ( { attributes, setAttributes }: TabsPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.contentTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { contentTypography: patchDevice( attributes.contentTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Content', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.contentColor || {} } onChange={ ( v ) => setAttributes( { contentColor: v } ) } />
			<ColorGradientControl
				label={ __( 'Background', 'flexa-block' ) }
				type={ attributes.contentBackgroundType || 'color' }
				color={ attributes.contentBackground || {} }
				gradient={ attributes.contentBackgroundGradient || {} }
				onTypeChange={ ( v ) => setAttributes( { contentBackgroundType: v } ) }
				onColorChange={ ( v ) => setAttributes( { contentBackground: v } ) }
				onGradientChange={ ( v ) => setAttributes( { contentBackgroundGradient: v } ) }
			/>
			<Dimensions
				label={ __( 'Content padding', 'flexa-block' ) }
				responsive
				value={ rawDevice( attributes.contentPadding, device ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { contentPadding: { ...attributes.contentPadding, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};
