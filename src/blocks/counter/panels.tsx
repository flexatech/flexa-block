/**
 * Counter block — counter-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) plus the shared typography controls come from @components; these
 * cover the parts unique to a counter: the stat items (icon + number + label),
 * the column grid, the count-up animation, the icon / number / label styling,
 * the number↔label divider and the per-item box. Following the "prefer theme
 * styles" rule nothing is styled by default — only the values the user picks
 * produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, RangeControl, TextControl, Flex, FlexItem } from '@wordpress/components';

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
	CounterAttributes,
	CounterItem,
	CounterNumber,
	IconValue,
	LengthValue,
	PanelProps,
	TypographyDevice,
} from '../../types';

type CPanelProps = PanelProps< CounterAttributes >;

/** Where the icon sits relative to the number (few values → Segmented). */
const ICON_POSITION_OPTIONS: ControlOption[] = [
	{ value: 'top', label: __( 'Top', 'flexa-block' ) },
	{ value: 'inline', label: __( 'Inline', 'flexa-block' ) },
];

/**
 * A fresh counter item, used when adding one.
 */
const blankItem = (): CounterItem => ( {
	number: { value: '0', prefix: '', suffix: '' },
	label: '',
	icon: { source: 'none', name: '', markup: '', url: '', id: null },
} );

/** The collapsed-header icon preview for one stat (inline SVG or uploaded image). */
const counterPreview = ( item: CounterItem ): JSX.Element | undefined => {
	const icon = item.icon || {};
	if ( icon.markup ) {
		return <span dangerouslySetInnerHTML={ { __html: icon.markup } } />;
	}
	if ( icon.source === 'upload' && icon.url ) {
		return <img src={ icon.url } alt="" />;
	}
	return undefined;
};

/** Body controls for one stat: its icon, its number (prefix/value/suffix) and label. */
const CounterItemBody = ( { item, update }: { item: CounterItem; update: ( patch: Partial< CounterItem > ) => void } ): JSX.Element => {
	const num: CounterNumber = item.number || {};
	const setNumber = ( patch: Partial< CounterNumber > ) => update( { number: { ...num, ...patch } } );

	return (
		<>
			<IconPicker label={ __( 'Icon', 'flexa-block' ) } value={ item.icon || {} } onChange={ ( v: IconValue ) => update( { icon: v } ) } />
			<Flex gap={ 2 } align="flex-end">
				<FlexItem isBlock>
					<TextControl __nextHasNoMarginBottom label={ __( 'Prefix', 'flexa-block' ) } value={ num.prefix ?? '' } onChange={ ( v: string ) => setNumber( { prefix: v } ) } />
				</FlexItem>
				<FlexItem isBlock>
					<TextControl __nextHasNoMarginBottom label={ __( 'Number', 'flexa-block' ) } value={ num.value ?? '' } onChange={ ( v: string ) => setNumber( { value: v } ) } />
				</FlexItem>
				<FlexItem isBlock>
					<TextControl __nextHasNoMarginBottom label={ __( 'Suffix', 'flexa-block' ) } value={ num.suffix ?? '' } onChange={ ( v: string ) => setNumber( { suffix: v } ) } />
				</FlexItem>
			</Flex>
			<TextControl __nextHasNoMarginBottom label={ __( 'Label', 'flexa-block' ) } value={ item.label ?? '' } onChange={ ( v: string ) => update( { label: v } ) } />
		</>
	);
};

/**
 * Items panel — the stat list, via the shared ItemListPanel (accordion cards +
 * reorder + add/remove). Each item edits its icon, its number (prefix / value /
 * suffix) and its label.
 */
export const CounterItemsPanel = ( { attributes, setAttributes }: CPanelProps ): JSX.Element => {
	const items: CounterItem[] = Array.isArray( attributes.items ) ? attributes.items : [];

	return (
		<ItemListPanel< CounterItem >
			title={ __( 'Stats', 'flexa-block' ) }
			addLabel={ __( 'Add stat', 'flexa-block' ) }
			items={ items }
			onChange={ ( next ) => setAttributes( { items: next } ) }
			newItem={ blankItem }
			minItems={ 1 }
			renderHeader={ ( item, index ) => ( {
				preview: counterPreview( item ),
				title: ( item.label || '' ).trim() || __( 'Stat', 'flexa-block' ) + ' ' + ( index + 1 ),
			} ) }
			renderBody={ ( item, _index, update ) => <CounterItemBody item={ item } update={ update } /> }
		/>
	);
};

/**
 * Layout panel — columns, the gap between stats, content alignment and the gap
 * between the icon / number / label.
 */
export const CounterLayoutPanel = ( { attributes, setAttributes }: CPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { columns, gap, alignment, contentGap } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
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
				label={ __( 'Gap between stats', 'flexa-block' ) }
				value={ gap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { gap: { ...gap, [ device ]: v } } ) }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || ( device === 'desktop' ? 'center' : '' ) || 'center' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Gap between icon, number & label', 'flexa-block' ) }
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
 * Icon panel — show toggle, position, size and colour.
 */
export const CounterIconPanel = ( { attributes, setAttributes }: CPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { showIcon, iconPosition, iconSize, iconColor } = attributes;

	return (
		<PanelBody title={ __( 'Icon', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show icon', 'flexa-block' ) }
				checked={ showIcon !== false }
				onChange={ ( v: boolean ) => setAttributes( { showIcon: v } ) }
			/>
			{ showIcon !== false && (
				<>
					<Segmented
						label={ __( 'Position', 'flexa-block' ) }
						value={ iconPosition || 'top' }
						onChange={ ( v ) => setAttributes( { iconPosition: v as CounterAttributes[ 'iconPosition' ] } ) }
						options={ ICON_POSITION_OPTIONS }
					/>
					<SliderUnit
						label={ __( 'Icon size', 'flexa-block' ) }
						value={ iconSize?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						max={ { px: 160, em: 12, rem: 12, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { iconSize: { ...iconSize, [ device ]: v } } ) }
					/>
					<DualColor label={ __( 'Icon colour', 'flexa-block' ) } value={ iconColor || {} } onChange={ ( v ) => setAttributes( { iconColor: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Number panel — the count-up animation plus the number typography and colour.
 */
export const CounterNumberPanel = ( { attributes, setAttributes }: CPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.numberTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { numberTypography: patchDevice( attributes.numberTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Number', 'flexa-block' ) } initialOpen={ true }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Count up on scroll', 'flexa-block' ) }
				checked={ attributes.countUp !== false }
				onChange={ ( v: boolean ) => setAttributes( { countUp: v } ) }
			/>
			{ attributes.countUp !== false && (
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Animation duration (ms)', 'flexa-block' ) }
					value={ typeof attributes.countDuration === 'number' ? attributes.countDuration : 2000 }
					min={ 200 }
					max={ 6000 }
					step={ 100 }
					onChange={ ( v: number | undefined ) => setAttributes( { countDuration: v ?? 2000 } ) }
				/>
			) }
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Number colour', 'flexa-block' ) } value={ attributes.numberColor || {} } onChange={ ( v ) => setAttributes( { numberColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Label panel — the label typography and colour.
 */
export const CounterLabelPanel = ( { attributes, setAttributes }: CPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.labelTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { labelTypography: patchDevice( attributes.labelTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Label', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Label colour', 'flexa-block' ) } value={ attributes.labelColor || {} } onChange={ ( v ) => setAttributes( { labelColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Divider panel — an optional line between the number and the label.
 */
export const CounterSeparatorPanel = ( { attributes, setAttributes }: CPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { showSeparator, separatorStyle, separatorColor, separatorWidth, separatorWeight } = attributes;

	return (
		<PanelBody title={ __( 'Divider', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show divider', 'flexa-block' ) }
				checked={ !! showSeparator }
				onChange={ ( v: boolean ) => setAttributes( { showSeparator: v } ) }
			/>
			{ showSeparator && (
				<>
					<Segmented
						label={ __( 'Divider style', 'flexa-block' ) }
						value={ separatorStyle || 'solid' }
						onChange={ ( v ) => setAttributes( { separatorStyle: v } ) }
						options={ LINE_STYLE_OPTIONS }
					/>
					<SliderUnit
						label={ __( 'Divider width', 'flexa-block' ) }
						value={ separatorWidth?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						max={ { px: 400, em: 20, rem: 20, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { separatorWidth: { ...separatorWidth, [ device ]: v } } ) }
					/>
					<SliderUnit
						label={ __( 'Divider thickness', 'flexa-block' ) }
						value={ separatorWeight?.[ device ] || {} }
						units={ WEIGHT_UNITS }
						defaultUnit="px"
						max={ { px: 20 } }
						onChange={ ( v: LengthValue ) => setAttributes( { separatorWeight: { ...separatorWeight, [ device ]: v } } ) }
					/>
					<DualColor label={ __( 'Divider colour', 'flexa-block' ) } value={ separatorColor || {} } onChange={ ( v ) => setAttributes( { separatorColor: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Item box panel — each stat's background, padding and corner radius.
 */
export const CounterItemBoxPanel = ( { attributes, setAttributes }: CPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { itemBackground, itemPadding, itemBorderRadius } = attributes;

	return (
		<PanelBody title={ __( 'Item Box', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ itemBackground || {} } onChange={ ( v ) => setAttributes( { itemBackground: v } ) } />
			<Dimensions
				label={ __( 'Padding', 'flexa-block' ) }
				responsive
				value={ rawDevice( itemPadding, device ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { itemPadding: { ...itemPadding, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Corner radius', 'flexa-block' ) }
				value={ itemBorderRadius?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 10, rem: 10, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { itemBorderRadius: { ...itemBorderRadius, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};
