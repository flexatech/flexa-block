/**
 * Social Icons block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / visibility) come
 * from @components; these cover the parts unique to this block: the icon list
 * (platform, link, per-icon colour mode), the row layout (alignment, gap,
 * size, tag) and the custom-tint colours + hover motion. Following the
 * "prefer theme styles" rule nothing is styled by default — only values the
 * user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, TextControl, ToggleControl } from '@wordpress/components';

import { Segmented, SliderUnit, DualColor, IconPicker, ItemListPanel, CONTENT_ALIGN_OPTIONS, useDevice } from '@components';
import { SPACING_UNITS, LENGTH_UNITS, HTML_TAGS } from '@utils';
import { SOCIAL_PLATFORMS, getPlatform, CUSTOM_KEY } from './social-data';
import type { ControlOption, IconValue, LengthValue, PanelProps, SocialIconAttributes, SocialIconItem } from '../../types';

type SocialPanelProps = PanelProps< SocialIconAttributes >;

/** Per-icon colour modes. */
const COLOR_MODE_OPTIONS: ControlOption[] = [
	{ value: 'official', label: __( 'Official', 'flexa-block' ) },
	{ value: 'custom', label: __( 'Custom', 'flexa-block' ) },
];

/** Hover motions (few values, no icons → SelectControl). */
const HOVER_EFFECT_OPTIONS = [
	{ value: '', label: __( 'None', 'flexa-block' ) },
	{ value: 'grow', label: __( 'Grow', 'flexa-block' ) },
	{ value: 'shrink', label: __( 'Shrink', 'flexa-block' ) },
	{ value: 'lift', label: __( 'Lift', 'flexa-block' ) },
	{ value: 'rotate', label: __( 'Rotate', 'flexa-block' ) },
];

/** Icon choices for an item: the brand catalog plus a "Custom" (library) option. */
const ICON_OPTIONS = [
	...SOCIAL_PLATFORMS.map( ( p ) => ( { value: p.key, label: p.label } ) ),
	{ value: CUSTOM_KEY, label: __( 'Custom', 'flexa-block' ) },
];

/** A fresh item for the "Add icon" button. */
const newItem = (): SocialIconItem => ( {
	platform: 'facebook',
	link: { url: '', target: '', rel: '' },
	colorMode: 'official',
	color: { light: '', dark: '' },
} );

/** The collapsed-header icon preview for one item (brand mark or custom glyph). */
const socialPreview = ( item: SocialIconItem ): JSX.Element | undefined => {
	if ( item.platform === CUSTOM_KEY ) {
		return item.icon?.markup ? <span dangerouslySetInnerHTML={ { __html: item.icon.markup } } /> : undefined;
	}
	return getPlatform( item.platform )?.brand;
};

/**
 * Body controls for one social item: the platform picker (brand catalog + a
 * "Custom" library option), the link, and the colour mode / tint. Switching to
 * Custom forces the tinted (library) mode; switching back to a brand resets to
 * its full-colour artwork.
 */
const SocialItemBody = ( { item, update }: { item: SocialIconItem; update: ( patch: Partial< SocialIconItem > ) => void } ): JSX.Element => {
	const link = item.link || {};
	const isCustomIcon = item.platform === CUSTOM_KEY;
	const isCustomColor = item.colorMode === 'custom';

	const onPlatformChange = ( value: string ): void => {
		if ( value === CUSTOM_KEY ) {
			update( { platform: CUSTOM_KEY, colorMode: 'custom' } );
		} else if ( isCustomIcon ) {
			update( { platform: value, colorMode: 'official' } );
		} else {
			update( { platform: value } );
		}
	};

	return (
		<>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Icon', 'flexa-block' ) }
				value={ item.platform || 'facebook' }
				options={ ICON_OPTIONS }
				onChange={ onPlatformChange }
			/>

			{ isCustomIcon && (
				<IconPicker label={ __( 'Custom icon', 'flexa-block' ) } value={ item.icon || {} } onChange={ ( v: IconValue ) => update( { icon: v } ) } />
			) }

			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Link', 'flexa-block' ) }
				type="url"
				placeholder="https://"
				value={ link.url || '' }
				onChange={ ( v: string ) => update( { link: { ...link, url: v } } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Open in new tab', 'flexa-block' ) }
				checked={ link.target === '_blank' }
				onChange={ ( v: boolean ) => update( { link: { ...link, target: v ? '_blank' : '', rel: v ? 'noopener noreferrer' : '' } } ) }
			/>

			{ /* Brand marks choose Official vs Custom; a library icon is always tinted. */ }
			{ ! isCustomIcon && (
				<Segmented
					label={ __( 'Color', 'flexa-block' ) }
					value={ item.colorMode || 'official' }
					onChange={ ( v ) => update( { colorMode: v as SocialIconItem[ 'colorMode' ] } ) }
					options={ COLOR_MODE_OPTIONS }
				/>
			) }
			{ ( isCustomIcon || isCustomColor ) && (
				<DualColor label={ __( 'Icon color', 'flexa-block' ) } value={ item.color || {} } onChange={ ( v ) => update( { color: v } ) } />
			) }
		</>
	);
};

/**
 * Icons panel — the list of social icons (add, reorder, edit, remove) via the
 * shared ItemListPanel: cards collapse to a header row; opening one closes the
 * rest (accordion) so long lists stay compact.
 */
export const SocialItemsPanel = ( { attributes, setAttributes }: SocialPanelProps ): JSX.Element => {
	const items: SocialIconItem[] = Array.isArray( attributes.items ) ? attributes.items : [];

	return (
		<ItemListPanel< SocialIconItem >
			title={ __( 'Icons', 'flexa-block' ) }
			addLabel={ __( 'Add icon', 'flexa-block' ) }
			items={ items }
			onChange={ ( next ) => setAttributes( { items: next } ) }
			newItem={ newItem }
			renderHeader={ ( item ) => ( {
				preview: socialPreview( item ),
				title: item.platform === CUSTOM_KEY
					? item.icon?.name || __( 'Custom icon', 'flexa-block' )
					: getPlatform( item.platform )?.label || __( 'Icon', 'flexa-block' ),
			} ) }
			renderBody={ ( item, _index, update ) => <SocialItemBody item={ item } update={ update } /> }
		/>
	);
};

/**
 * Layout panel — alignment, gap, icon size and the wrapper tag.
 */
export const SocialLayoutPanel = ( { attributes, setAttributes }: SocialPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { alignment, gap, iconSize, htmlTag, hoverEffect } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ false }>
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
