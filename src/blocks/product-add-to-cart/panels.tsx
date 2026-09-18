/**
 * Add to Cart block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility / animation) come from @components, and the whole button style set
 * comes from the shared <ButtonStylePanel>. These cover what is unique to an
 * add-to-cart form: the layout of quantity field vs button, the button's own
 * label and cart icon, the quantity field's box, and the attribute dropdowns a
 * variable product renders. Nothing is styled by default, so an untouched form
 * keeps whatever the theme and WooCommerce already give it.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, TextControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	DualColor,
	TypographyControls,
	CONTENT_ALIGN_OPTIONS,
	useDevice,
} from '@components';
import { LENGTH_UNITS, SPACING_UNITS, rawDevice, patchDevice } from '@utils';
import type {
	ControlOption,
	LengthValue,
	PanelProps,
	ProductAddToCartAttributes,
	TypographyDevice,
} from '../../types';

type CartPanelProps = PanelProps< ProductAddToCartAttributes >;

/** How the quantity field and the button sit together. */
const CART_LAYOUT_OPTIONS: ControlOption[] = [
	{ value: 'inline', label: __( 'Inline', 'flexa-block' ) },
	{ value: 'stacked', label: __( 'Stacked', 'flexa-block' ) },
	{ value: 'button-only', label: __( 'Button only', 'flexa-block' ) },
];

/** Which side of the label the cart icon sits on. */
const ICON_POSITION_OPTIONS: ControlOption[] = [
	{ value: 'before', label: __( 'Before', 'flexa-block' ) },
	{ value: 'after', label: __( 'After', 'flexa-block' ) },
];

/** True when the quantity field is actually rendered — `button-only` wins. */
export const quantityShown = ( attributes: ProductAddToCartAttributes ): boolean =>
	'button-only' !== ( attributes.cartLayout || 'inline' ) && false !== attributes.showQuantity;

/**
 * Settings panel — the form's layout, whether the quantity field shows, the
 * wrapper alignment and the gap between field and button.
 */
export const ProductAddToCartSettingsPanel = ( { attributes, setAttributes }: CartPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { alignment, cartLayout, showQuantity, gap } = attributes;
	const isButtonOnly = 'button-only' === ( cartLayout || 'inline' );

	return (
		<PanelBody title={ __( 'Settings', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Layout', 'flexa-block' ) }
				value={ cartLayout || 'inline' }
				onChange={ ( v ) => setAttributes( { cartLayout: v as ProductAddToCartAttributes[ 'cartLayout' ] } ) }
				options={ CART_LAYOUT_OPTIONS }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show quantity field', 'flexa-block' ) }
				help={
					isButtonOnly
						? __( 'The button-only layout always hides the quantity field.', 'flexa-block' )
						: __( 'Grouped and external products have no quantity field of their own.', 'flexa-block' )
				}
				disabled={ isButtonOnly }
				checked={ ! isButtonOnly && false !== showQuantity }
				onChange={ ( v: boolean ) => setAttributes( { showQuantity: v } ) }
			/>
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
				max={ { px: 80, em: 6, rem: 6 } }
				onChange={ ( v: LengthValue ) => setAttributes( { gap: { ...gap, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Button content panel — the label override and the cart icon. The button's
 * colours, radius, padding and typography live in the shared <ButtonStylePanel>.
 */
export const ProductAddToCartButtonPanel = ( { attributes, setAttributes }: CartPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { buttonText, showIcon, iconPosition, iconSize, iconGap } = attributes;

	return (
		<PanelBody title={ __( 'Button content', 'flexa-block' ) } initialOpen={ false }>
			<TextControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Button label', 'flexa-block' ) }
				help={ __( 'Leave empty to keep the label WooCommerce chose for this product type.', 'flexa-block' ) }
				value={ buttonText ?? '' }
				onChange={ ( v: string ) => setAttributes( { buttonText: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show cart icon', 'flexa-block' ) }
				checked={ !! showIcon }
				onChange={ ( v: boolean ) => setAttributes( { showIcon: v } ) }
			/>
			{ !! showIcon && (
				<>
					<Segmented
						label={ __( 'Icon position', 'flexa-block' ) }
						value={ iconPosition || 'before' }
						onChange={ ( v ) => setAttributes( { iconPosition: v as ProductAddToCartAttributes[ 'iconPosition' ] } ) }
						options={ ICON_POSITION_OPTIONS }
					/>
					<SliderUnit
						label={ __( 'Icon size', 'flexa-block' ) }
						value={ iconSize?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						max={ { px: 64, em: 4, rem: 4 } }
						onChange={ ( v: LengthValue ) => setAttributes( { iconSize: { ...iconSize, [ device ]: v } } ) }
					/>
					<SliderUnit
						label={ __( 'Icon gap', 'flexa-block' ) }
						value={ iconGap?.[ device ] || {} }
						units={ SPACING_UNITS }
						defaultUnit="px"
						max={ { px: 40, em: 4, rem: 4 } }
						onChange={ ( v: LengthValue ) => setAttributes( { iconGap: { ...iconGap, [ device ]: v } } ) }
					/>
				</>
			) }
		</PanelBody>
	);
};

/**
 * Quantity field panel — sizing, typography, colours, border and radius.
 * Renders only while the field is shown: with it hidden the generator emits
 * nothing but `display:none`, so the controls would promise CSS that never lands.
 */
export const ProductAddToCartQuantityPanel = ( { attributes, setAttributes }: CartPanelProps ): JSX.Element | null => {
	const [ device ] = useDevice();
	const {
		quantityWidth,
		quantityHeight,
		quantityTypography,
		quantityColor,
		quantityBackground,
		quantityBorderWidth,
		quantityBorderColor,
		quantityRadius,
	} = attributes;

	if ( ! quantityShown( attributes ) ) {
		return null;
	}

	return (
		<PanelBody title={ __( 'Quantity field', 'flexa-block' ) } initialOpen={ false }>
			<SliderUnit
				label={ __( 'Width', 'flexa-block' ) }
				value={ quantityWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 240, em: 16, rem: 16, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { quantityWidth: { ...quantityWidth, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Height', 'flexa-block' ) }
				value={ quantityHeight?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 8, rem: 8 } }
				onChange={ ( v: LengthValue ) => setAttributes( { quantityHeight: { ...quantityHeight, [ device ]: v } } ) }
			/>
			<TypographyControls
				value={ rawDevice( quantityTypography, device ) }
				onChange={ ( patch: Partial< TypographyDevice > ) =>
					setAttributes( { quantityTypography: patchDevice( quantityTypography, device, patch ) } )
				}
			/>
			<DualColor
				label={ __( 'Text color', 'flexa-block' ) }
				value={ quantityColor || {} }
				onChange={ ( v ) => setAttributes( { quantityColor: v } ) }
			/>
			<DualColor
				label={ __( 'Background', 'flexa-block' ) }
				value={ quantityBackground || {} }
				onChange={ ( v ) => setAttributes( { quantityBackground: v } ) }
			/>
			<SliderUnit
				label={ __( 'Border width', 'flexa-block' ) }
				value={ quantityBorderWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 12, em: 1, rem: 1 } }
				onChange={ ( v: LengthValue ) => setAttributes( { quantityBorderWidth: { ...quantityBorderWidth, [ device ]: v } } ) }
			/>
			<DualColor
				label={ __( 'Border color', 'flexa-block' ) }
				value={ quantityBorderColor || {} }
				onChange={ ( v ) => setAttributes( { quantityBorderColor: v } ) }
			/>
			<SliderUnit
				label={ __( 'Corner radius', 'flexa-block' ) }
				value={ quantityRadius?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 8, rem: 8 } }
				onChange={ ( v: LengthValue ) => setAttributes( { quantityRadius: { ...quantityRadius, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Variations panel — the label and dropdown of a variable product's attribute
 * form. WooCommerce owns that markup; these only tint what it renders.
 */
export const ProductAddToCartVariationsPanel = ( { attributes, setAttributes }: CartPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const {
		variationLabelColor,
		variationLabelTypography,
		variationSelectColor,
		variationSelectBackground,
		variationSelectBorderColor,
	} = attributes;

	return (
		<PanelBody title={ __( 'Variations', 'flexa-block' ) } initialOpen={ false }>
			<DualColor
				label={ __( 'Label color', 'flexa-block' ) }
				value={ variationLabelColor || {} }
				onChange={ ( v ) => setAttributes( { variationLabelColor: v } ) }
			/>
			<TypographyControls
				value={ rawDevice( variationLabelTypography, device ) }
				onChange={ ( patch: Partial< TypographyDevice > ) =>
					setAttributes( { variationLabelTypography: patchDevice( variationLabelTypography, device, patch ) } )
				}
			/>
			<DualColor
				label={ __( 'Dropdown text color', 'flexa-block' ) }
				value={ variationSelectColor || {} }
				onChange={ ( v ) => setAttributes( { variationSelectColor: v } ) }
			/>
			<DualColor
				label={ __( 'Dropdown background', 'flexa-block' ) }
				value={ variationSelectBackground || {} }
				onChange={ ( v ) => setAttributes( { variationSelectBackground: v } ) }
			/>
			<DualColor
				label={ __( 'Dropdown border color', 'flexa-block' ) }
				value={ variationSelectBorderColor || {} }
				onChange={ ( v ) => setAttributes( { variationSelectBorderColor: v } ) }
			/>
		</PanelBody>
	);
};
