/**
 * Product Field block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility / animation) come from @components; these cover what is unique to
 * one line of product data: which field it prints, its label, and then the
 * options that only apply to that field — the term options for a taxonomy
 * field, the SKU options for a SKU field. A panel that does not apply is not
 * rendered at all, so a SKU field never shows the term controls.
 *
 * Styling a whole Product Meta list at once is the parent's job; these panels
 * are the per-field override, which is why their colour labels say so.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl } from '@wordpress/components';

import { Segmented, SliderUnit, DualColor, Dimensions, TypographyControls, CONTENT_ALIGN_OPTIONS, useDevice } from '@components';
import { LENGTH_UNITS, SPACING_UNITS, rawDevice, patchDevice } from '@utils';
import type { BoxValue, ControlOption, LengthValue, PanelProps, ProductFieldAttributes, TypographyDevice } from '../../types';

type PFPanelProps = PanelProps< ProductFieldAttributes >;

/** What this line prints. */
const FIELD_OPTIONS: ControlOption[] = [
	{ value: 'sku', label: __( 'SKU', 'flexa-block' ) },
	{ value: 'categories', label: __( 'Categories', 'flexa-block' ) },
	{ value: 'tags', label: __( 'Tags', 'flexa-block' ) },
];

/** Where the label sits relative to the value. */
const LAYOUT_OPTIONS: ControlOption[] = [
	{ value: 'inline', label: __( 'Inline', 'flexa-block' ) },
	{ value: 'stacked', label: __( 'Stacked', 'flexa-block' ) },
];

/** How the terms inside the value read. */
const TERM_LAYOUT_OPTIONS: ControlOption[] = [
	{ value: 'inline', label: __( 'Inline', 'flexa-block' ) },
	{ value: 'badge', label: __( 'Badges', 'flexa-block' ) },
	{ value: 'list', label: __( 'List', 'flexa-block' ) },
];

/**
 * The label a field gets by default. Switching field carries the label along,
 * so nobody has to retype "Tags:" after changing SKU to Tags.
 */
const FIELD_LABELS: Record< string, string > = {
	sku: __( 'SKU:', 'flexa-block' ),
	categories: __( 'Categories:', 'flexa-block' ),
	tags: __( 'Tags:', 'flexa-block' ),
};

/** Whether a label is still one of the defaults — i.e. nobody typed over it. */
const isDefaultLabel = ( label: string ): boolean => Object.values( FIELD_LABELS ).includes( label );

/** True when this field lists taxonomy terms rather than the SKU. */
export const isTermField = ( attributes: ProductFieldAttributes ): boolean => 'sku' !== ( attributes.field || 'sku' );

/**
 * Settings panel — the field itself, its label, the label/value arrangement and
 * the gap between them.
 */
export const ProductFieldSettingsPanel = ( { attributes, setAttributes }: PFPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { field, label, fieldLayout, alignment, labelGap } = attributes;

	return (
		<PanelBody title={ __( 'Settings', 'flexa-block' ) } initialOpen={ true }>
			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Field', 'flexa-block' ) }
				value={ field || 'sku' }
				options={ FIELD_OPTIONS }
				onChange={ ( v: string ) => {
					const next = ( v || 'sku' ) as NonNullable< ProductFieldAttributes[ 'field' ] >;
					// Carry the label along unless the author wrote their own.
					setAttributes( isDefaultLabel( label ?? '' ) ? { field: next, label: FIELD_LABELS[ next ] } : { field: next } );
				} }
			/>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Label', 'flexa-block' ) }
				help={ __( 'Leave empty to print the value with no label.', 'flexa-block' ) }
				value={ label ?? '' }
				onChange={ ( v: string ) => setAttributes( { label: v } ) }
			/>
			<Segmented
				label={ __( 'Label position', 'flexa-block' ) }
				value={ fieldLayout || 'inline' }
				onChange={ ( v ) => setAttributes( { fieldLayout: v as ProductFieldAttributes[ 'fieldLayout' ] } ) }
				options={ LAYOUT_OPTIONS }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || '' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Label gap', 'flexa-block' ) }
				value={ labelGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 60, em: 6, rem: 6 } }
				onChange={ ( v: LengthValue ) => setAttributes( { labelGap: { ...labelGap, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Terms panel — how a taxonomy field reads: the term layout, the separator, the
 * archive links, a cap on how many terms show and the gap between them. Not
 * rendered for a SKU field.
 */
export const ProductFieldTermsPanel = ( { attributes, setAttributes }: PFPanelProps ): JSX.Element | null => {
	const [ device ] = useDevice();
	const { termLayout, separator, linkTerms, linkTarget, maxTerms, termGap } = attributes;

	if ( ! isTermField( attributes ) ) {
		return null;
	}

	return (
		<PanelBody title={ __( 'Terms', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Term layout', 'flexa-block' ) }
				value={ termLayout || 'inline' }
				onChange={ ( v ) => setAttributes( { termLayout: v as ProductFieldAttributes[ 'termLayout' ] } ) }
				options={ TERM_LAYOUT_OPTIONS }
			/>
			{ 'inline' === ( termLayout || 'inline' ) && (
				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Separator', 'flexa-block' ) }
					help={ __( 'Printed between terms — a comma and a space by default.', 'flexa-block' ) }
					value={ separator ?? ', ' }
					onChange={ ( v: string ) => setAttributes( { separator: v } ) }
				/>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Link to archive', 'flexa-block' ) }
				checked={ linkTerms !== false }
				onChange={ ( v: boolean ) => setAttributes( { linkTerms: v } ) }
			/>
			{ linkTerms !== false && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Open in new tab', 'flexa-block' ) }
					checked={ !! linkTarget }
					onChange={ ( v: boolean ) => setAttributes( { linkTarget: v } ) }
				/>
			) }
			<RangeControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Maximum terms', 'flexa-block' ) }
				help={ __( 'Zero shows every term.', 'flexa-block' ) }
				value={ maxTerms ?? 0 }
				min={ 0 }
				max={ 20 }
				onChange={ ( v?: number ) => setAttributes( { maxTerms: v ?? 0 } ) }
			/>
			<SliderUnit
				label={ __( 'Gap between terms', 'flexa-block' ) }
				value={ termGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 60, em: 6, rem: 6, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { termGap: { ...termGap, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * SKU panel — the prefix / suffix wrapped around the code, the wording used when
 * a product has no SKU, and the copy button. Not rendered for a taxonomy field.
 */
export const ProductFieldSkuPanel = ( { attributes, setAttributes }: PFPanelProps ): JSX.Element | null => {
	const { skuPrefix, skuSuffix, skuFallback, showCopy, copiedText } = attributes;

	if ( isTermField( attributes ) ) {
		return null;
	}

	return (
		<PanelBody title={ __( 'SKU', 'flexa-block' ) } initialOpen={ false }>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Prefix', 'flexa-block' ) }
				help={ __( 'Printed just before the SKU.', 'flexa-block' ) }
				value={ skuPrefix || '' }
				onChange={ ( v: string ) => setAttributes( { skuPrefix: v } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Suffix', 'flexa-block' ) }
				help={ __( 'Printed just after the SKU.', 'flexa-block' ) }
				value={ skuSuffix || '' }
				onChange={ ( v: string ) => setAttributes( { skuSuffix: v } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Fallback text', 'flexa-block' ) }
				help={ __( 'Shown when the product has no SKU. Leave empty to hide the block.', 'flexa-block' ) }
				value={ skuFallback || '' }
				onChange={ ( v: string ) => setAttributes( { skuFallback: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show copy button', 'flexa-block' ) }
				checked={ !! showCopy }
				onChange={ ( v: boolean ) => setAttributes( { showCopy: v } ) }
			/>
			{ !! showCopy && (
				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Copied message', 'flexa-block' ) }
					value={ copiedText ?? '' }
					placeholder={ __( 'Copied', 'flexa-block' ) }
					onChange={ ( v: string ) => setAttributes( { copiedText: v } ) }
				/>
			) }
		</PanelBody>
	);
};

/**
 * Label panel — typography and colour of the text in front of the value.
 * Inside Product Meta these override whatever the parent set for every field.
 */
export const ProductFieldLabelPanel = ( { attributes, setAttributes }: PFPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.labelTypography, device );

	return (
		<PanelBody title={ __( 'Label', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls
				value={ typo }
				onChange={ ( patch: Partial< TypographyDevice > ) =>
					setAttributes( { labelTypography: patchDevice( attributes.labelTypography, device, patch ) } )
				}
			/>
			<DualColor
				label={ __( 'Label color', 'flexa-block' ) }
				value={ attributes.labelColor || {} }
				onChange={ ( v ) => setAttributes( { labelColor: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Value panel — typography and colour of the SKU, or of the terms when this is
 * a taxonomy field. The term chips can override the colour below.
 */
export const ProductFieldValuePanel = ( { attributes, setAttributes }: PFPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const isTerms = isTermField( attributes );
	const typo = rawDevice( attributes.valueTypography, device );

	return (
		<PanelBody title={ isTerms ? __( 'Term text', 'flexa-block' ) : __( 'Value', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls
				value={ typo }
				onChange={ ( patch: Partial< TypographyDevice > ) =>
					setAttributes( { valueTypography: patchDevice( attributes.valueTypography, device, patch ) } )
				}
			/>
			<DualColor
				label={ isTerms ? __( 'Text color', 'flexa-block' ) : __( 'Value color', 'flexa-block' ) }
				value={ attributes.valueColor || {} }
				onChange={ ( v ) => setAttributes( { valueColor: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Term box panel — the chip behind each term: its colours (normal + hover),
 * padding, radius and outline. Left alone, terms simply take the value styling.
 * Not rendered for a SKU field.
 */
export const ProductFieldTermBoxPanel = ( { attributes, setAttributes }: PFPanelProps ): JSX.Element | null => {
	const [ device ] = useDevice();
	const { itemColor, itemColorHover, itemBackground, itemBackgroundHover, itemPadding, itemRadius, itemBorderWidth, itemBorderColor } = attributes;

	if ( ! isTermField( attributes ) ) {
		return null;
	}

	return (
		<PanelBody title={ __( 'Term box', 'flexa-block' ) } initialOpen={ false }>
			{ /* DualColor takes no help text, so the override is spelled out in the label. */ }
			<DualColor
				label={ __( 'Text color (overrides Term text)', 'flexa-block' ) }
				value={ itemColor || {} }
				onChange={ ( v ) => setAttributes( { itemColor: v } ) }
			/>
			<DualColor label={ __( 'Text color (hover)', 'flexa-block' ) } value={ itemColorHover || {} } onChange={ ( v ) => setAttributes( { itemColorHover: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ itemBackground || {} } onChange={ ( v ) => setAttributes( { itemBackground: v } ) } />
			<DualColor label={ __( 'Background (hover)', 'flexa-block' ) } value={ itemBackgroundHover || {} } onChange={ ( v ) => setAttributes( { itemBackgroundHover: v } ) } />
			<Dimensions
				label={ __( 'Term padding', 'flexa-block' ) }
				responsive
				value={ itemPadding?.[ device ] || {} }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { itemPadding: { ...itemPadding, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Corner radius', 'flexa-block' ) }
				value={ itemRadius?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 8, rem: 8, '%': 50 } }
				onChange={ ( v: LengthValue ) => setAttributes( { itemRadius: { ...itemRadius, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Border width', 'flexa-block' ) }
				value={ itemBorderWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 20, em: 2, rem: 2, '%': 10 } }
				onChange={ ( v: LengthValue ) => setAttributes( { itemBorderWidth: { ...itemBorderWidth, [ device ]: v } } ) }
			/>
			<DualColor label={ __( 'Border color', 'flexa-block' ) } value={ itemBorderColor || {} } onChange={ ( v ) => setAttributes( { itemBorderColor: v } ) } />
		</PanelBody>
	);
};
