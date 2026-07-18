/**
 * Social Share block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / visibility) come
 * from @components; these cover the parts unique to this block: the network
 * buttons, what is shared (current page vs a fixed URL/title/image), the row
 * layout, and the icon-only button appearance (colour mode, tint, shape,
 * button background). Following the "prefer theme styles" rule nothing is
 * styled by default — only values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, TextControl, ToggleControl } from '@wordpress/components';

import { Segmented, SliderUnit, DualColor, ItemListPanel, CONTENT_ALIGN_OPTIONS, useDevice } from '@components';
import { DIRECTION_OPTIONS, SPACING_UNITS, LENGTH_UNITS, HTML_TAGS, getPlatform } from '@utils';
import { SHARE_NETWORKS } from './share-data';
import type { ControlOption, LengthValue, PanelProps, SocialShareAttributes, SocialShareItem } from '../../types';

type SharePanelProps = PanelProps< SocialShareAttributes >;

/** Networks the picker offers (shareable ones only). */
const NETWORK_OPTIONS = SHARE_NETWORKS.map( ( p ) => ( { value: p.key, label: p.label } ) );

/** What the buttons share. */
const SHARE_SOURCE_OPTIONS: ControlOption[] = [
	{ value: 'current', label: __( 'Current page', 'flexa-block' ) },
	{ value: 'custom', label: __( 'Custom', 'flexa-block' ) },
];

/** Icon colour: full brand artwork vs a single tint. */
const COLOR_MODE_OPTIONS: ControlOption[] = [
	{ value: 'official', label: __( 'Official', 'flexa-block' ) },
	{ value: 'custom', label: __( 'Custom', 'flexa-block' ) },
];

/** Button shape around the icon. */
const SHAPE_OPTIONS: ControlOption[] = [
	{ value: 'bare', label: __( 'Bare', 'flexa-block' ) },
	{ value: 'rounded', label: __( 'Rounded', 'flexa-block' ) },
	{ value: 'circle', label: __( 'Circle', 'flexa-block' ) },
	{ value: 'square', label: __( 'Square', 'flexa-block' ) },
];

/** Hover motions (few values, no icons → SelectControl). */
const HOVER_EFFECT_OPTIONS = [
	{ value: '', label: __( 'None', 'flexa-block' ) },
	{ value: 'grow', label: __( 'Grow', 'flexa-block' ) },
	{ value: 'shrink', label: __( 'Shrink', 'flexa-block' ) },
	{ value: 'lift', label: __( 'Lift', 'flexa-block' ) },
	{ value: 'rotate', label: __( 'Rotate', 'flexa-block' ) },
];

/** A fresh button for the "Add network" action. */
const newItem = (): SocialShareItem => ( { network: 'facebook' } );

/**
 * Networks panel — the list of share buttons (add, reorder, remove) via the
 * shared ItemListPanel. Each card body picks one network from the catalog.
 */
export const ShareItemsPanel = ( { attributes, setAttributes }: SharePanelProps ): JSX.Element => {
	const items: SocialShareItem[] = Array.isArray( attributes.items ) ? attributes.items : [];

	return (
		<ItemListPanel< SocialShareItem >
			title={ __( 'Networks', 'flexa-block' ) }
			addLabel={ __( 'Add network', 'flexa-block' ) }
			items={ items }
			onChange={ ( next ) => setAttributes( { items: next } ) }
			newItem={ newItem }
			renderHeader={ ( item ) => ( {
				preview: getPlatform( item.network )?.brand,
				title: getPlatform( item.network )?.label || __( 'Network', 'flexa-block' ),
			} ) }
			renderBody={ ( item, _index, update ) => (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Network', 'flexa-block' ) }
					value={ item.network || 'facebook' }
					options={ NETWORK_OPTIONS }
					onChange={ ( v: string ) => update( { network: v } ) }
				/>
			) }
		/>
	);
};

/**
 * Share source panel — whether buttons share the current page or a fixed
 * URL/title/image, and whether they open in a new tab.
 */
export const ShareSourcePanel = ( { attributes, setAttributes }: SharePanelProps ): JSX.Element => {
	const { shareSource, shareUrl, shareTitle, shareImage, newTab } = attributes;
	const isCustom = shareSource === 'custom';

	return (
		<PanelBody title={ __( 'Share', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Share', 'flexa-block' ) }
				value={ shareSource || 'current' }
				onChange={ ( v ) => setAttributes( { shareSource: v as SocialShareAttributes[ 'shareSource' ] } ) }
				options={ SHARE_SOURCE_OPTIONS }
			/>
			{ isCustom && (
				<>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'URL', 'flexa-block' ) }
						type="url"
						placeholder="https://"
						value={ shareUrl || '' }
						onChange={ ( v: string ) => setAttributes( { shareUrl: v } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Title', 'flexa-block' ) }
						value={ shareTitle || '' }
						onChange={ ( v: string ) => setAttributes( { shareTitle: v } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Image URL (Pinterest)', 'flexa-block' ) }
						type="url"
						placeholder="https://"
						value={ shareImage || '' }
						onChange={ ( v: string ) => setAttributes( { shareImage: v } ) }
					/>
				</>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Open in new tab', 'flexa-block' ) }
				checked={ newTab !== false }
				onChange={ ( v: boolean ) => setAttributes( { newTab: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Layout panel — direction, alignment, icon size, gap, hover motion and tag.
 */
export const ShareLayoutPanel = ( { attributes, setAttributes }: SharePanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { direction, alignment, gap, iconSize, htmlTag, hoverEffect } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Direction', 'flexa-block' ) }
				value={ direction || 'row' }
				onChange={ ( v ) => setAttributes( { direction: v as SocialShareAttributes[ 'direction' ] } ) }
				options={ DIRECTION_OPTIONS }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || '' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Icon size', 'flexa-block' ) }
				value={ iconSize?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 128, em: 8, rem: 8, '%': 200 } }
				onChange={ ( v: LengthValue ) => setAttributes( { iconSize: { ...iconSize, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Gap', 'flexa-block' ) }
				value={ gap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { gap: { ...gap, [ device ]: v } } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Hover effect', 'flexa-block' ) }
				value={ hoverEffect || '' }
				options={ HOVER_EFFECT_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { hoverEffect: v } ) }
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
 * Buttons panel — icon colour mode + tint, and the button shape + background.
 */
export const ShareButtonsPanel = ( { attributes, setAttributes }: SharePanelProps ): JSX.Element => {
	const { colorMode, tint, shape, buttonBackground } = attributes;
	const isTinted = colorMode === 'custom';

	return (
		<PanelBody title={ __( 'Buttons', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Icon color', 'flexa-block' ) }
				value={ colorMode || 'official' }
				onChange={ ( v ) => setAttributes( { colorMode: v as SocialShareAttributes[ 'colorMode' ] } ) }
				options={ COLOR_MODE_OPTIONS }
			/>
			{ isTinted && (
				<DualColor label={ __( 'Tint', 'flexa-block' ) } value={ tint || {} } onChange={ ( v ) => setAttributes( { tint: v } ) } />
			) }
			<Segmented
				label={ __( 'Shape', 'flexa-block' ) }
				value={ shape || 'bare' }
				onChange={ ( v ) => setAttributes( { shape: v as SocialShareAttributes[ 'shape' ] } ) }
				options={ SHAPE_OPTIONS }
			/>
			<DualColor
				label={ __( 'Button background', 'flexa-block' ) }
				value={ buttonBackground || {} }
				onChange={ ( v ) => setAttributes( { buttonBackground: v } ) }
			/>
		</PanelBody>
	);
};
