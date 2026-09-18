/**
 * Product Stock block — block-specific inspector panels.
 *
 * The shared panels (typography / spacing / background / border / shadow /
 * position / visibility / animation) come from @components; these cover the
 * parts unique to a stock status: the display type and the icon / quantity /
 * low-stock settings, the three status strings, the four per-status colour
 * pairs, the icon size + gap, and the badge padding + radius. Following the
 * "prefer theme styles" rule nothing is coloured by default — only the values
 * the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, TextControl, RangeControl } from '@wordpress/components';

import { Segmented, SliderUnit, DualColor, Dimensions, CONTENT_ALIGN_OPTIONS, useDevice } from '@components';
import { LENGTH_UNITS, SPACING_UNITS } from '@utils';
import type { BoxValue, ControlOption, LengthValue, PanelProps, ProductStockAttributes } from '../../types';

type PSPanelProps = PanelProps< ProductStockAttributes >;

/** How the status is shown — a run of text or a padded badge. */
const DISPLAY_TYPE_OPTIONS: ControlOption[] = [
	{ value: 'text', label: __( 'Text', 'flexa-block' ) },
	{ value: 'badge', label: __( 'Badge', 'flexa-block' ) },
];

/**
 * Settings panel — alignment, display type, and the icon / quantity /
 * low-stock-threshold settings.
 */
export const ProductStockSettingsPanel = ( { attributes, setAttributes }: PSPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { alignment, displayType, showIcon, showQuantity, lowStockThreshold } = attributes;

	return (
		<PanelBody title={ __( 'Settings', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || 'left' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<Segmented
				label={ __( 'Display type', 'flexa-block' ) }
				value={ displayType || 'text' }
				onChange={ ( v ) => setAttributes( { displayType: v as ProductStockAttributes[ 'displayType' ] } ) }
				options={ DISPLAY_TYPE_OPTIONS }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show status icon', 'flexa-block' ) }
				checked={ !! showIcon }
				onChange={ ( v: boolean ) => setAttributes( { showIcon: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show remaining quantity', 'flexa-block' ) }
				help={ __( 'Only shown when WooCommerce manages stock for the product.', 'flexa-block' ) }
				checked={ !! showQuantity }
				onChange={ ( v: boolean ) => setAttributes( { showQuantity: v } ) }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Low stock threshold', 'flexa-block' ) }
				help={ __( 'At or below this quantity the status uses the low-stock colors. 0 turns the low-stock state off.', 'flexa-block' ) }
				value={ typeof lowStockThreshold === 'number' ? lowStockThreshold : 0 }
				min={ 0 }
				max={ 50 }
				onChange={ ( v?: number ) => setAttributes( { lowStockThreshold: v ?? 0 } ) }
			/>
		</PanelBody>
	);
};

/**
 * Status text panel — the wording for each availability state. Low stock reuses
 * the in-stock wording on purpose: it is still in stock, only more urgent.
 */
export const ProductStockTextPanel = ( { attributes, setAttributes }: PSPanelProps ): JSX.Element => {
	const { inStockText, outOfStockText, backorderText } = attributes;

	return (
		<PanelBody title={ __( 'Status text', 'flexa-block' ) } initialOpen={ false }>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'In stock', 'flexa-block' ) }
				value={ inStockText ?? '' }
				onChange={ ( v: string ) => setAttributes( { inStockText: v } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Out of stock', 'flexa-block' ) }
				value={ outOfStockText ?? '' }
				onChange={ ( v: string ) => setAttributes( { outOfStockText: v } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'On backorder', 'flexa-block' ) }
				value={ backorderText ?? '' }
				onChange={ ( v: string ) => setAttributes( { backorderText: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Layout panel — the icon size and the gap between icon, text and quantity.
 * These size the status row itself, so they stay available in both display
 * types (unlike the badge padding / radius).
 */
export const ProductStockLayoutPanel = ( { attributes, setAttributes }: PSPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { showIcon, iconSize, gap } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ false }>
			{ !! showIcon && (
				<SliderUnit
					label={ __( 'Icon size', 'flexa-block' ) }
					value={ iconSize?.[ device ] || {} }
					units={ LENGTH_UNITS }
					defaultUnit="px"
					max={ { px: 96, em: 6, rem: 6 } }
					onChange={ ( v: LengthValue ) => setAttributes( { iconSize: { ...iconSize, [ device ]: v } } ) }
				/>
			) }
			<SliderUnit
				label={ __( 'Gap', 'flexa-block' ) }
				value={ gap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 60, em: 6, rem: 6 } }
				onChange={ ( v: LengthValue ) => setAttributes( { gap: { ...gap, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Status colours panel — a text + background pair per availability state. Only
 * the state WooCommerce resolves on the front end is ever painted, so all four
 * pairs can be set without clashing.
 */
export const ProductStockColorsPanel = ( { attributes, setAttributes }: PSPanelProps ): JSX.Element => {
	const {
		inStockColor,
		inStockBackground,
		outOfStockColor,
		outOfStockBackground,
		backorderColor,
		backorderBackground,
		lowStockColor,
		lowStockBackground,
	} = attributes;

	return (
		<PanelBody title={ __( 'Status colors', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'In stock — text', 'flexa-block' ) } value={ inStockColor || {} } onChange={ ( v ) => setAttributes( { inStockColor: v } ) } />
			<DualColor label={ __( 'In stock — background', 'flexa-block' ) } value={ inStockBackground || {} } onChange={ ( v ) => setAttributes( { inStockBackground: v } ) } />
			<DualColor label={ __( 'Out of stock — text', 'flexa-block' ) } value={ outOfStockColor || {} } onChange={ ( v ) => setAttributes( { outOfStockColor: v } ) } />
			<DualColor label={ __( 'Out of stock — background', 'flexa-block' ) } value={ outOfStockBackground || {} } onChange={ ( v ) => setAttributes( { outOfStockBackground: v } ) } />
			<DualColor label={ __( 'On backorder — text', 'flexa-block' ) } value={ backorderColor || {} } onChange={ ( v ) => setAttributes( { backorderColor: v } ) } />
			<DualColor label={ __( 'On backorder — background', 'flexa-block' ) } value={ backorderBackground || {} } onChange={ ( v ) => setAttributes( { backorderBackground: v } ) } />
			<DualColor label={ __( 'Low stock — text', 'flexa-block' ) } value={ lowStockColor || {} } onChange={ ( v ) => setAttributes( { lowStockColor: v } ) } />
			<DualColor label={ __( 'Low stock — background', 'flexa-block' ) } value={ lowStockBackground || {} } onChange={ ( v ) => setAttributes( { lowStockBackground: v } ) } />
		</PanelBody>
	);
};

/**
 * Badge panel — padding and corner radius. Renders only for the badge display
 * type: in text mode the generator emits neither, so offering the controls would
 * promise CSS that never lands.
 */
export const ProductStockBadgePanel = ( { attributes, setAttributes }: PSPanelProps ): JSX.Element | null => {
	const [ device ] = useDevice();
	const { displayType, badgePadding, badgeRadius } = attributes;

	if ( 'badge' !== displayType ) {
		return null;
	}

	return (
		<PanelBody title={ __( 'Badge', 'flexa-block' ) } initialOpen={ false }>
			<Dimensions
				label={ __( 'Badge padding', 'flexa-block' ) }
				responsive
				value={ badgePadding?.[ device ] || {} }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { badgePadding: { ...badgePadding, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Badge radius', 'flexa-block' ) }
				value={ badgeRadius?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 8, rem: 8 } }
				onChange={ ( v: LengthValue ) => setAttributes( { badgeRadius: { ...badgeRadius, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};
