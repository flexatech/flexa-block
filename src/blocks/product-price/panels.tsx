/**
 * Product Price block — block-specific inspector panels.
 *
 * The foundation panels (spacing / background / border / shadow / position /
 * visibility / animation) come from @components; this file covers the parts
 * unique to the product-price block: the settings (alignment, sale position)
 * and the regular / sale amount styling. Every value shown is pulled from
 * WooCommerce — the user only styles it, never types the price. Nothing is
 * styled by default, so the price inherits the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, RangeControl } from '@wordpress/components';

import {
	Segmented,
	DualColor,
	TypographyControls,
	CONTENT_ALIGN_OPTIONS,
	useDevice,
} from '@components';
import { rawDevice, patchDevice } from '@utils';
import type { ControlOption, PanelProps, ProductPriceAttributes, TypographyDevice } from '../../types';

type PPPanelProps = PanelProps< ProductPriceAttributes >;

/** Where the sale amount sits relative to the regular amount (few values → Segmented). */
const SALE_POSITION_OPTIONS: ControlOption[] = [
	{ value: 'after', label: __( 'After regular', 'flexa-block' ) },
	{ value: 'before', label: __( 'Before regular', 'flexa-block' ) },
];

/**
 * Settings panel — alignment and sale price position.
 */
export const ProductPriceSettingsPanel = ( { attributes, setAttributes }: PPPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { alignment, salePricePosition, strikethrough, strikeColor, strikeThickness } = attributes;

	const align = alignment?.[ device ] || '';

	return (
		<PanelBody title={ __( 'Settings', 'flexa-block' ) } initialOpen={ true }>
			<div className="flexa-field">
				<Segmented
					label={ __( 'Alignment', 'flexa-block' ) }
					value={ align }
					responsive
					onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
					options={ CONTENT_ALIGN_OPTIONS }
				/>
			</div>

			<div className="flexa-field">
				<Segmented
					label={ __( 'Sale Price Position', 'flexa-block' ) }
					value={ salePricePosition || 'after' }
					onChange={ ( v ) => setAttributes( { salePricePosition: v as ProductPriceAttributes[ 'salePricePosition' ] } ) }
					options={ SALE_POSITION_OPTIONS }
				/>
			</div>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Strike through original price', 'flexa-block' ) }
				help={ __( 'Draw a line through the regular price when the product is on sale.', 'flexa-block' ) }
				checked={ strikethrough !== false }
				onChange={ ( v: boolean ) => setAttributes( { strikethrough: v } ) }
			/>

			{ strikethrough !== false && (
				<>
					<DualColor
						label={ __( 'Line colour', 'flexa-block' ) }
						value={ strikeColor || {} }
						onChange={ ( v ) => setAttributes( { strikeColor: v } ) }
					/>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Line thickness (px)', 'flexa-block' ) }
						value={ strikeThickness ?? 0 }
						min={ 0 }
						max={ 10 }
						step={ 1 }
						onChange={ ( v?: number ) => setAttributes( { strikeThickness: v ?? 0 } ) }
					/>
				</>
			) }
		</PanelBody>
	);
};

/**
 * Regular Price panel — the regular amount's colour and typography.
 */
export const ProductPriceRegularPanel = ( { attributes, setAttributes }: PPPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.regularTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) =>
		setAttributes( { regularTypography: patchDevice( attributes.regularTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Regular Price', 'flexa-block' ) } initialOpen={ true }>
			<DualColor label={ __( 'Colour', 'flexa-block' ) } value={ attributes.regularColor || {} } onChange={ ( v ) => setAttributes( { regularColor: v } ) } />
			<TypographyControls value={ typo } onChange={ setTypo } />
		</PanelBody>
	);
};

/**
 * Sale Price panel — the sale amount's colour and typography.
 */
export const ProductPriceSalePanel = ( { attributes, setAttributes }: PPPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.saleTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) =>
		setAttributes( { saleTypography: patchDevice( attributes.saleTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Sale Price', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Colour', 'flexa-block' ) } value={ attributes.saleColor || {} } onChange={ ( v ) => setAttributes( { saleColor: v } ) } />
			<TypographyControls value={ typo } onChange={ setTypo } />
		</PanelBody>
	);
};
