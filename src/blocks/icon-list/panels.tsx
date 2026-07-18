/**
 * Icon List block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) plus the shared typography controls come from @components; these
 * cover the parts unique to an icon list: the items (icon + text + link), the
 * list/grid layout (view, columns, icon position, gaps, alignment), the
 * block-level icon styling (size, view, shape, colours with hover, frame) and
 * the text colours. Following the "prefer theme styles" rule nothing is styled
 * by default — only the values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, TextControl, ToggleControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	DualColor,
	IconPicker,
	ItemListPanel,
	CONTENT_ALIGN_OPTIONS,
	useDevice,
} from '@components';
import { LENGTH_UNITS, SPACING_UNITS, HTML_TAGS } from '@utils';
import type {
	ControlOption,
	IconListAttributes,
	IconListItem,
	IconValue,
	LengthValue,
	LinkAttr,
	PanelProps,
} from '../../types';

type IconListPanelProps = PanelProps< IconListAttributes >;

/** List vs grid layout (two values → Segmented). */
const VIEW_OPTIONS: ControlOption[] = [
	{ value: 'list', label: __( 'List', 'flexa-block' ) },
	{ value: 'grid', label: __( 'Grid', 'flexa-block' ) },
];

/** Where the icon sits relative to the text (two values → Segmented). */
const ICON_POSITION_OPTIONS: ControlOption[] = [
	{ value: 'before', label: __( 'Before', 'flexa-block' ) },
	{ value: 'after', label: __( 'After', 'flexa-block' ) },
];

/** Icon frame style (few values → Segmented). */
const ICON_VIEW_OPTIONS: ControlOption[] = [
	{ value: 'default', label: __( 'Default', 'flexa-block' ) },
	{ value: 'stacked', label: __( 'Stacked', 'flexa-block' ) },
	{ value: 'framed', label: __( 'Framed', 'flexa-block' ) },
];

/** Icon frame shape (few values → Segmented). */
const ICON_SHAPE_OPTIONS: ControlOption[] = [
	{ value: 'square', label: __( 'Square', 'flexa-block' ) },
	{ value: 'rounded', label: __( 'Rounded', 'flexa-block' ) },
	{ value: 'circle', label: __( 'Circle', 'flexa-block' ) },
];

/** A fresh list item, used when adding one. */
const blankItem = (): IconListItem => ( {
	id: 'item-' + Math.random().toString( 36 ).slice( 2, 8 ),
	text: '',
	link: { url: '', target: '', rel: '' },
	icon: { source: 'none', name: '', markup: '', url: '', id: null },
} );

/** The collapsed-header icon preview for one item (inline SVG or uploaded image). */
const itemPreview = ( item: IconListItem ): JSX.Element | undefined => {
	const icon = item.icon || {};
	if ( icon.markup ) {
		return <span dangerouslySetInnerHTML={ { __html: icon.markup } } />;
	}
	if ( icon.source === 'upload' && icon.url ) {
		return <img src={ icon.url } alt="" />;
	}
	return undefined;
};

/** Body controls for one item: its icon, its text and its link. */
const IconListItemBody = ( { item, update }: { item: IconListItem; update: ( patch: Partial< IconListItem > ) => void } ): JSX.Element => {
	const link: LinkAttr = item.link || {};

	return (
		<>
			<IconPicker label={ __( 'Icon', 'flexa-block' ) } value={ item.icon || {} } onChange={ ( v: IconValue ) => update( { icon: v } ) } />
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Text', 'flexa-block' ) }
				value={ item.text ?? '' }
				onChange={ ( v: string ) => update( { text: v } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Link', 'flexa-block' ) }
				type="url"
				placeholder="https://"
				value={ link.url ?? '' }
				onChange={ ( v: string ) => update( { link: { ...link, url: v } } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Open in new tab', 'flexa-block' ) }
				checked={ link.target === '_blank' }
				onChange={ ( v: boolean ) => update( { link: { ...link, target: v ? '_blank' : '', rel: v ? 'noopener noreferrer' : '' } } ) }
			/>
		</>
	);
};

/**
 * Items panel — the icon list (add, reorder, edit, remove) via the shared
 * ItemListPanel: cards collapse to a header row; opening one closes the rest.
 */
export const IconListItemsPanel = ( { attributes, setAttributes }: IconListPanelProps ): JSX.Element => {
	const items: IconListItem[] = Array.isArray( attributes.items ) ? attributes.items : [];

	return (
		<ItemListPanel< IconListItem >
			title={ __( 'Items', 'flexa-block' ) }
			addLabel={ __( 'Add item', 'flexa-block' ) }
			items={ items }
			onChange={ ( next ) => setAttributes( { items: next } ) }
			newItem={ blankItem }
			minItems={ 1 }
			renderHeader={ ( item, index ) => ( {
				preview: itemPreview( item ),
				title: ( item.text || '' ).trim() || __( 'Item', 'flexa-block' ) + ' ' + ( index + 1 ),
			} ) }
			renderBody={ ( item, _index, update ) => <IconListItemBody item={ item } update={ update } /> }
		/>
	);
};

/**
 * Layout panel — list vs grid, per-device columns (grid), icon position, the
 * gap between items, the gap between icon and text, alignment and the tag.
 */
export const IconListLayoutPanel = ( { attributes, setAttributes }: IconListPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { view, columns, iconPosition, gap, iconGap, alignment, htmlTag } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Layout', 'flexa-block' ) }
				value={ view || 'list' }
				onChange={ ( v ) => setAttributes( { view: v as IconListAttributes[ 'view' ] } ) }
				options={ VIEW_OPTIONS }
			/>
			{ view === 'grid' && (
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
			<Segmented
				label={ __( 'Icon position', 'flexa-block' ) }
				value={ iconPosition || 'before' }
				onChange={ ( v ) => setAttributes( { iconPosition: v as IconListAttributes[ 'iconPosition' ] } ) }
				options={ ICON_POSITION_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Gap between items', 'flexa-block' ) }
				value={ gap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { gap: { ...gap, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Gap between icon & text', 'flexa-block' ) }
				value={ iconGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { iconGap: { ...iconGap, [ device ]: v } } ) }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || '' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
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
 * Icon panel — the icon size, its framed/stacked view and shape, the icon /
 * background / border colours (with hover) and the frame padding.
 */
export const IconListIconPanel = ( { attributes, setAttributes }: IconListPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const {
		iconSize,
		iconView,
		iconShape,
		iconColor,
		iconColorHover,
		iconBackground,
		iconBackgroundHover,
		iconBorderColor,
		iconPadding,
	} = attributes;

	return (
		<PanelBody title={ __( 'Icon', 'flexa-block' ) } initialOpen={ false }>
			<SliderUnit
				label={ __( 'Icon size', 'flexa-block' ) }
				value={ iconSize?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 128, em: 8, rem: 8, '%': 200 } }
				onChange={ ( v: LengthValue ) => setAttributes( { iconSize: { ...iconSize, [ device ]: v } } ) }
			/>
			<Segmented
				label={ __( 'View', 'flexa-block' ) }
				value={ iconView || 'default' }
				onChange={ ( v ) => setAttributes( { iconView: v as IconListAttributes[ 'iconView' ] } ) }
				options={ ICON_VIEW_OPTIONS }
			/>
			{ iconView && iconView !== 'default' && (
				<Segmented
					label={ __( 'Shape', 'flexa-block' ) }
					value={ iconShape || 'square' }
					onChange={ ( v ) => setAttributes( { iconShape: v as IconListAttributes[ 'iconShape' ] } ) }
					options={ ICON_SHAPE_OPTIONS }
				/>
			) }
			<DualColor label={ __( 'Icon colour', 'flexa-block' ) } value={ iconColor || {} } onChange={ ( v ) => setAttributes( { iconColor: v } ) } />
			<DualColor label={ __( 'Icon colour (hover)', 'flexa-block' ) } value={ iconColorHover || {} } onChange={ ( v ) => setAttributes( { iconColorHover: v } ) } />
			<DualColor label={ __( 'Icon background', 'flexa-block' ) } value={ iconBackground || {} } onChange={ ( v ) => setAttributes( { iconBackground: v } ) } />
			<DualColor label={ __( 'Icon background (hover)', 'flexa-block' ) } value={ iconBackgroundHover || {} } onChange={ ( v ) => setAttributes( { iconBackgroundHover: v } ) } />
			<DualColor label={ __( 'Icon border colour', 'flexa-block' ) } value={ iconBorderColor || {} } onChange={ ( v ) => setAttributes( { iconBorderColor: v } ) } />
			<SliderUnit
				label={ __( 'Icon padding', 'flexa-block' ) }
				value={ iconPadding?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 60, em: 6, rem: 6, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { iconPadding: { ...iconPadding, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Text colours panel — the text colour and its hover colour (typography lives
 * in the shared TypographyPanel).
 */
export const IconListTextPanel = ( { attributes, setAttributes }: IconListPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Text', 'flexa-block' ) } initialOpen={ false }>
		<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.textColor || {} } onChange={ ( v ) => setAttributes( { textColor: v } ) } />
		<DualColor label={ __( 'Text colour (hover)', 'flexa-block' ) } value={ attributes.textColorHover || {} } onChange={ ( v ) => setAttributes( { textColorHover: v } ) } />
	</PanelBody>
);
