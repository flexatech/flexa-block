/**
 * Breadcrumb block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / position / visibility) plus
 * the shared typography panel come from @components; these cover the parts unique
 * to the breadcrumb: where the trail comes from (auto vs a hand-listed set of
 * crumbs via the shared ItemListPanel), the home icon + text, the separator, the
 * show-current / current-is-link toggles, the row layout (alignment / gap / max
 * width) and the link / current / separator colours. Following the "prefer theme
 * styles" rule nothing is coloured by default — only values the user picks
 * produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	DualColor,
	IconPicker,
	ItemListPanel,
	CONTENT_ALIGN_OPTIONS,
	useDevice,
} from '@components';
import { HTML_TAGS, SPACING_UNITS, LENGTH_UNITS } from '@utils';
import type { BreadcrumbAttributes, BreadcrumbItem, LengthValue, PanelProps } from '../../types';

type BcPanelProps = PanelProps< BreadcrumbAttributes >;

/** Where the trail comes from (two icon-less choices → Segmented). */
const SOURCE_OPTIONS = [
	{ value: 'auto', label: __( 'Automatic', 'flexa-block' ) },
	{ value: 'manual', label: __( 'Manual', 'flexa-block' ) },
];

/** Separator glyphs (few values → SelectControl); `custom` reveals a text field. */
const SEPARATOR_OPTIONS = [
	{ value: 'slash', label: __( 'Slash  /', 'flexa-block' ) },
	{ value: 'chevron', label: __( 'Chevron  ›', 'flexa-block' ) },
	{ value: 'raquo', label: __( 'Double  »', 'flexa-block' ) },
	{ value: 'dash', label: __( 'Dash  -', 'flexa-block' ) },
	{ value: 'custom', label: __( 'Custom…', 'flexa-block' ) },
];

/** A fresh manual crumb, used when adding one. */
const blankItem = (): BreadcrumbItem => ( { label: '', url: '' } );

/**
 * Breadcrumb panel — the source, home crumb, separator and current-item rules.
 */
export const BreadcrumbSourcePanel = ( { attributes, setAttributes }: BcPanelProps ): JSX.Element => {
	const { source, homeIcon, homeIconValue, homeText, separator, showCurrent, currentIsLink, htmlTag } = attributes;
	const sep = separator || {};

	return (
		<PanelBody title={ __( 'Breadcrumb', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Source', 'flexa-block' ) }
				value={ source || 'auto' }
				onChange={ ( v ) => setAttributes( { source: v as BreadcrumbAttributes[ 'source' ] } ) }
				options={ SOURCE_OPTIONS }
			/>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Home label', 'flexa-block' ) }
				value={ homeText ?? '' }
				onChange={ ( v: string ) => setAttributes( { homeText: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show home icon', 'flexa-block' ) }
				checked={ !! homeIcon }
				onChange={ ( v: boolean ) => setAttributes( { homeIcon: v } ) }
			/>
			{ homeIcon && (
				<IconPicker
					label={ __( 'Home icon', 'flexa-block' ) }
					value={ homeIconValue || {} }
					onChange={ ( v ) => setAttributes( { homeIconValue: v } ) }
				/>
			) }
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Separator', 'flexa-block' ) }
				value={ sep.type || 'slash' }
				options={ SEPARATOR_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { separator: { ...sep, type: v as NonNullable< BreadcrumbAttributes[ 'separator' ] >[ 'type' ] } } ) }
			/>
			{ 'custom' === ( sep.type || 'slash' ) && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Custom separator', 'flexa-block' ) }
					value={ sep.custom ?? '' }
					onChange={ ( v: string ) => setAttributes( { separator: { ...sep, custom: v } } ) }
				/>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show current page', 'flexa-block' ) }
				checked={ showCurrent !== false }
				onChange={ ( v: boolean ) => setAttributes( { showCurrent: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Link the current page', 'flexa-block' ) }
				checked={ !! currentIsLink }
				onChange={ ( v: boolean ) => setAttributes( { currentIsLink: v } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'nav' }
				options={ HTML_TAGS.map( ( t ) => ( { label: t, value: t } ) ) }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>
		</PanelBody>
	);
};

/** Body controls for one manual crumb: its label and link URL. */
const ItemBody = ( { item, update }: { item: BreadcrumbItem; update: ( patch: Partial< BreadcrumbItem > ) => void } ): JSX.Element => (
	<>
		<TextControl __nextHasNoMarginBottom label={ __( 'Label', 'flexa-block' ) } value={ item.label ?? '' } onChange={ ( v: string ) => update( { label: v } ) } />
		<TextControl __nextHasNoMarginBottom label={ __( 'Link', 'flexa-block' ) } help={ __( 'Leave empty for the current (last) crumb.', 'flexa-block' ) } value={ item.url ?? '' } onChange={ ( v: string ) => update( { url: v } ) } />
	</>
);

/**
 * Items panel — the hand-listed crumbs (manual source only), via the shared
 * ItemListPanel (accordion cards + reorder + add/remove).
 */
export const BreadcrumbItemsPanel = ( { attributes, setAttributes }: BcPanelProps ): JSX.Element => {
	const items: BreadcrumbItem[] = Array.isArray( attributes.items ) ? attributes.items : [];

	return (
		<ItemListPanel< BreadcrumbItem >
			title={ __( 'Crumbs', 'flexa-block' ) }
			addLabel={ __( 'Add crumb', 'flexa-block' ) }
			items={ items }
			onChange={ ( next ) => setAttributes( { items: next } ) }
			newItem={ blankItem }
			minItems={ 1 }
			renderHeader={ ( item, index ) => ( {
				title: ( item.label || '' ).trim() || __( 'Crumb', 'flexa-block' ) + ' ' + ( index + 1 ),
			} ) }
			renderBody={ ( item, _index, update ) => <ItemBody item={ item } update={ update } /> }
		/>
	);
};

/**
 * Layout panel — row alignment, the gap between crumbs and the trail max width.
 */
export const BreadcrumbLayoutPanel = ( { attributes, setAttributes }: BcPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { alignment, itemGap, maxWidth } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || 'left' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Gap between crumbs', 'flexa-block' ) }
				value={ itemGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { itemGap: { ...itemGap, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Max width', 'flexa-block' ) }
				value={ maxWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 1600, '%': 100, vw: 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { maxWidth: { ...maxWidth, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Colours panel — link, current-page and separator colours, each with a hover
 * colour. These are plain light/dark colour pairs (no gradient).
 */
export const BreadcrumbColorsPanel = ( { attributes, setAttributes }: BcPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Colours', 'flexa-block' ) } initialOpen={ true }>
		<DualColor label={ __( 'Link', 'flexa-block' ) } value={ attributes.linkColor || {} } onChange={ ( v ) => setAttributes( { linkColor: v } ) } />
		<DualColor label={ __( 'Link (hover)', 'flexa-block' ) } value={ attributes.linkColorHover || {} } onChange={ ( v ) => setAttributes( { linkColorHover: v } ) } />
		<DualColor label={ __( 'Current page', 'flexa-block' ) } value={ attributes.currentColor || {} } onChange={ ( v ) => setAttributes( { currentColor: v } ) } />
		<DualColor label={ __( 'Current page (hover)', 'flexa-block' ) } value={ attributes.currentColorHover || {} } onChange={ ( v ) => setAttributes( { currentColorHover: v } ) } />
		<DualColor label={ __( 'Separator', 'flexa-block' ) } value={ attributes.separatorColor || {} } onChange={ ( v ) => setAttributes( { separatorColor: v } ) } />
		<DualColor label={ __( 'Separator (hover)', 'flexa-block' ) } value={ attributes.separatorColorHover || {} } onChange={ ( v ) => setAttributes( { separatorColorHover: v } ) } />
	</PanelBody>
);

/**
 * SEO panel — the optional BreadcrumbList structured data.
 */
export const BreadcrumbSchemaPanel = ( { attributes, setAttributes }: BcPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'SEO', 'flexa-block' ) } initialOpen={ false }>
		<ToggleControl
			__nextHasNoMarginBottom
			label={ __( 'Output breadcrumb schema', 'flexa-block' ) }
			help={ __( 'Add BreadcrumbList structured data for search engines. Use only one breadcrumb schema per page.', 'flexa-block' ) }
			checked={ !! attributes.enableSchema }
			onChange={ ( v: boolean ) => setAttributes( { enableSchema: v } ) }
		/>
	</PanelBody>
);
