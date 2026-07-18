/**
 * Taxonomy block — block-specific inspector panels.
 *
 * The shared foundational panels (spacing / background / border / shadow /
 * position / visibility / animation) plus the shared typography panel come from
 * @components; these cover the parts unique to the taxonomy list: which taxonomy
 * to list and how (display style, columns, order, count / empty / hierarchy,
 * limit), the icon / text prefix & suffix and separator, the row layout
 * (alignment / gap / item padding / radius) and the per-term / count / affix
 * colours. Following the "prefer theme styles" rule nothing is coloured by
 * default — only values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl, RangeControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import {
	Segmented,
	SliderUnit,
	Dimensions,
	DualColor,
	IconPicker,
	TypographyControls,
	CONTENT_ALIGN_OPTIONS,
	useDevice,
} from '@components';
import { HTML_TAGS, LENGTH_UNITS, SPACING_UNITS } from '@utils';
import type { BoxValue, LengthValue, PanelProps, TaxonomyAttributes, TypographyDevice } from '../../types';

type TaxPanelProps = PanelProps< TaxonomyAttributes >;

/** Display styles (few short values → Segmented). */
const DISPLAY_OPTIONS = [
	{ value: 'list', label: __( 'List', 'flexa-block' ) },
	{ value: 'inline', label: __( 'Inline', 'flexa-block' ) },
	{ value: 'dropdown', label: __( 'Dropdown', 'flexa-block' ) },
	{ value: 'grid', label: __( 'Grid', 'flexa-block' ) },
];

/** Term ordering fields (three values → SelectControl). */
const ORDER_BY_OPTIONS = [
	{ value: 'name', label: __( 'Name', 'flexa-block' ) },
	{ value: 'count', label: __( 'Count', 'flexa-block' ) },
	{ value: 'slug', label: __( 'Slug', 'flexa-block' ) },
];

/** Sort direction (two values → Segmented). */
const ORDER_OPTIONS = [
	{ value: 'asc', label: __( 'Ascending', 'flexa-block' ) },
	{ value: 'desc', label: __( 'Descending', 'flexa-block' ) },
];

/** Affix type (three short values → Segmented). */
const AFFIX_OPTIONS = [
	{ value: 'none', label: __( 'None', 'flexa-block' ) },
	{ value: 'icon', label: __( 'Icon', 'flexa-block' ) },
	{ value: 'text', label: __( 'Text', 'flexa-block' ) },
];

/**
 * General panel — the source taxonomy, the display style + grid columns, ordering
 * and the count / empty / hierarchy / limit toggles. Registered taxonomies are
 * read live from the site so the dropdown matches what's available.
 */
export const TaxonomyGeneralPanel = ( { attributes, setAttributes }: TaxPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { taxonomy, displayStyle, columns, orderBy, order, showCount, showEmpty, hierarchical, limit, htmlTag } = attributes;

	// Public taxonomies, read from the core store.
	const taxonomyOptions = useSelect( ( select: ( store: string ) => any ) => {
		const taxes = select( 'core' ).getTaxonomies( { per_page: -1 } ) || [];
		const skip = [ 'nav_menu', 'link_category', 'post_format', 'wp_pattern_category' ];
		const list = taxes
			.filter( ( t: any ) => t.visibility?.show_ui !== false && ! skip.includes( t.slug ) )
			.map( ( t: any ) => ( { value: t.slug, label: t.name || t.slug } ) );
		return list.length
			? list
			: [
					{ value: 'category', label: __( 'Categories', 'flexa-block' ) },
					{ value: 'post_tag', label: __( 'Tags', 'flexa-block' ) },
			  ];
	}, [] );

	const isGrid = ( displayStyle || 'list' ) === 'grid';

	return (
		<PanelBody title={ __( 'General', 'flexa-block' ) } initialOpen={ true }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Taxonomy', 'flexa-block' ) }
				value={ taxonomy || 'category' }
				options={ taxonomyOptions }
				onChange={ ( v: string ) => setAttributes( { taxonomy: v } ) }
			/>
			<Segmented
				label={ __( 'Display', 'flexa-block' ) }
				value={ displayStyle || 'list' }
				onChange={ ( v ) => setAttributes( { displayStyle: v as TaxonomyAttributes[ 'displayStyle' ] } ) }
				options={ DISPLAY_OPTIONS }
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
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Order by', 'flexa-block' ) }
				value={ orderBy || 'name' }
				options={ ORDER_BY_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { orderBy: v as TaxonomyAttributes[ 'orderBy' ] } ) }
			/>
			<Segmented
				label={ __( 'Order', 'flexa-block' ) }
				value={ order || 'asc' }
				onChange={ ( v ) => setAttributes( { order: v as TaxonomyAttributes[ 'order' ] } ) }
				options={ ORDER_OPTIONS }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show post count', 'flexa-block' ) }
				checked={ showCount !== false }
				onChange={ ( v: boolean ) => setAttributes( { showCount: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show empty terms', 'flexa-block' ) }
				checked={ !! showEmpty }
				onChange={ ( v: boolean ) => setAttributes( { showEmpty: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show hierarchy', 'flexa-block' ) }
				help={ __( 'Nest child terms under their parents (list display).', 'flexa-block' ) }
				checked={ !! hierarchical }
				onChange={ ( v: boolean ) => setAttributes( { hierarchical: v } ) }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Limit', 'flexa-block' ) }
				help={ __( '0 shows every term.', 'flexa-block' ) }
				value={ typeof limit === 'number' ? limit : 0 }
				min={ 0 }
				max={ 100 }
				onChange={ ( v?: number ) => setAttributes( { limit: v ?? 0 } ) }
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
 * Affixes panel — an icon or text prefix and suffix around each term, plus an
 * optional separator between inline terms.
 */
export const TaxonomyAffixesPanel = ( { attributes, setAttributes }: TaxPanelProps ): JSX.Element => {
	const { prefixType, prefixIcon, prefixText, suffixType, suffixIcon, suffixText, showSeparator, separator } = attributes;

	return (
		<PanelBody title={ __( 'Affixes', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Prefix', 'flexa-block' ) }
				value={ prefixType || 'none' }
				onChange={ ( v ) => setAttributes( { prefixType: v as TaxonomyAttributes[ 'prefixType' ] } ) }
				options={ AFFIX_OPTIONS }
			/>
			{ 'icon' === prefixType && (
				<IconPicker
					label={ __( 'Prefix icon', 'flexa-block' ) }
					value={ prefixIcon || {} }
					onChange={ ( v ) => setAttributes( { prefixIcon: v } ) }
				/>
			) }
			{ 'text' === prefixType && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Prefix text', 'flexa-block' ) }
					value={ prefixText ?? '' }
					onChange={ ( v: string ) => setAttributes( { prefixText: v } ) }
				/>
			) }
			<Segmented
				label={ __( 'Suffix', 'flexa-block' ) }
				value={ suffixType || 'none' }
				onChange={ ( v ) => setAttributes( { suffixType: v as TaxonomyAttributes[ 'suffixType' ] } ) }
				options={ AFFIX_OPTIONS }
			/>
			{ 'icon' === suffixType && (
				<IconPicker
					label={ __( 'Suffix icon', 'flexa-block' ) }
					value={ suffixIcon || {} }
					onChange={ ( v ) => setAttributes( { suffixIcon: v } ) }
				/>
			) }
			{ 'text' === suffixType && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Suffix text', 'flexa-block' ) }
					value={ suffixText ?? '' }
					onChange={ ( v: string ) => setAttributes( { suffixText: v } ) }
				/>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show separator', 'flexa-block' ) }
				help={ __( 'Print a character between inline terms.', 'flexa-block' ) }
				checked={ !! showSeparator }
				onChange={ ( v: boolean ) => setAttributes( { showSeparator: v } ) }
			/>
			{ !! showSeparator && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Separator', 'flexa-block' ) }
					value={ separator ?? '' }
					onChange={ ( v: string ) => setAttributes( { separator: v } ) }
				/>
			) }
		</PanelBody>
	);
};

/**
 * Layout panel — row alignment, the gap between chips, the padding inside each
 * chip and the chip corner radius.
 */
export const TaxonomyLayoutPanel = ( { attributes, setAttributes }: TaxPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { alignment, gap, itemPadding, itemBorderRadius } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || 'left' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Gap', 'flexa-block' ) }
				value={ gap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { gap: { ...gap, [ device ]: v } } ) }
			/>
			<Dimensions
				label={ __( 'Item padding', 'flexa-block' ) }
				value={ ( itemPadding?.[ device ] || {} ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { itemPadding: { ...itemPadding, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Item radius', 'flexa-block' ) }
				value={ itemBorderRadius || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 10, rem: 10, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { itemBorderRadius: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Typography panel — per-device typography for the term text.
 */
export const TaxonomyTypographyPanel = ( { attributes, setAttributes }: TaxPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const value = ( attributes.typography?.[ device ] || {} ) as TypographyDevice;
	const setValue = ( patch: Partial< TypographyDevice > ) =>
		setAttributes( { typography: { ...attributes.typography, [ device ]: { ...value, ...patch } } } );

	return (
		<PanelBody title={ __( 'Typography', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ value } onChange={ setValue } />
		</PanelBody>
	);
};

/**
 * Colours panel — the term text / background / hover colours, the count and
 * affix colours, and the affix icon sizes. All light/dark pairs, unset by default
 * so the chips inherit the theme until the user picks a value.
 */
export const TaxonomyColorsPanel = ( { attributes, setAttributes }: TaxPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { prefixIconSize, suffixIconSize } = attributes;

	return (
		<PanelBody title={ __( 'Colours', 'flexa-block' ) } initialOpen={ true }>
			<DualColor label={ __( 'Term', 'flexa-block' ) } value={ attributes.itemColor || {} } onChange={ ( v ) => setAttributes( { itemColor: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ attributes.itemBackground || {} } onChange={ ( v ) => setAttributes( { itemBackground: v } ) } />
			<DualColor label={ __( 'Term (hover)', 'flexa-block' ) } value={ attributes.itemHoverColor || {} } onChange={ ( v ) => setAttributes( { itemHoverColor: v } ) } />
			<DualColor label={ __( 'Background (hover)', 'flexa-block' ) } value={ attributes.itemHoverBackground || {} } onChange={ ( v ) => setAttributes( { itemHoverBackground: v } ) } />
			<DualColor label={ __( 'Count', 'flexa-block' ) } value={ attributes.countColor || {} } onChange={ ( v ) => setAttributes( { countColor: v } ) } />
			<DualColor label={ __( 'Prefix', 'flexa-block' ) } value={ attributes.prefixColor || {} } onChange={ ( v ) => setAttributes( { prefixColor: v } ) } />
			<DualColor label={ __( 'Suffix', 'flexa-block' ) } value={ attributes.suffixColor || {} } onChange={ ( v ) => setAttributes( { suffixColor: v } ) } />
			<DualColor label={ __( 'Icon (hover)', 'flexa-block' ) } value={ attributes.iconHoverColor || {} } onChange={ ( v ) => setAttributes( { iconHoverColor: v } ) } />
			<DualColor label={ __( 'Separator', 'flexa-block' ) } value={ attributes.separatorColor || {} } onChange={ ( v ) => setAttributes( { separatorColor: v } ) } />
			<SliderUnit
				label={ __( 'Prefix icon size', 'flexa-block' ) }
				value={ prefixIconSize?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { prefixIconSize: { ...prefixIconSize, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Suffix icon size', 'flexa-block' ) }
				value={ suffixIconSize?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { suffixIconSize: { ...suffixIconSize, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};
