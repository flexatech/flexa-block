/**
 * Shared call-to-action button panel.
 *
 * Any block that renders a button styles it the same way — text and background
 * colour with a hover pair, typography, corner radius, padding, alignment and
 * an optional full-width stretch. The attribute names are fixed (`button*`) so
 * the PHP mirror `CSS_Helpers::add_button()` and the editor mirror
 * `buttonPreviewStyle()` read the same keys without per-block wiring.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl } from '@wordpress/components';

import { Segmented, SliderUnit, DualColor, Dimensions, useDevice } from './controls';
import { TypographyControls, CONTENT_ALIGN_OPTIONS } from './panels';
import { rawDevice, patchDevice, LENGTH_UNITS, SPACING_UNITS } from '@utils';
import type { BoxValue, ButtonStyleAttributes, ControlOption, LengthValue, TypographyDevice } from '../types';

interface ButtonPanelProps< T extends ButtonStyleAttributes > {
	attributes: T;
	setAttributes: ( attrs: Partial< T > ) => void;
	/** Panel heading — blocks with more than one button can name theirs. */
	title?: string;
	initialOpen?: boolean;
}

/** Whether the button hugs its label or stretches across its parent. */
export const BUTTON_WIDTH_OPTIONS: ControlOption[] = [
	{ value: 'auto', label: __( 'Auto', 'flexa-block' ) },
	{ value: 'full', label: __( 'Full width', 'flexa-block' ) },
];

/**
 * Button panel — typography, the two colour pairs, radius, padding, alignment
 * and width. Nothing is styled by default so an untouched button inherits the
 * theme's own button look.
 */
export const ButtonStylePanel = < T extends ButtonStyleAttributes >( {
	attributes,
	setAttributes,
	title,
	initialOpen = false,
}: ButtonPanelProps< T > ): JSX.Element => {
	const [ device ] = useDevice();

	const typo = rawDevice( attributes.buttonTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) =>
		setAttributes( { buttonTypography: patchDevice( attributes.buttonTypography, device, patch ) } as Partial< T > );

	return (
		<PanelBody title={ title || __( 'Button', 'flexa-block' ) } initialOpen={ initialOpen }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor
				label={ __( 'Text color', 'flexa-block' ) }
				value={ attributes.buttonTextColor || {} }
				onChange={ ( v ) => setAttributes( { buttonTextColor: v } as Partial< T > ) }
			/>
			<DualColor
				label={ __( 'Text color (hover)', 'flexa-block' ) }
				value={ attributes.buttonTextColorHover || {} }
				onChange={ ( v ) => setAttributes( { buttonTextColorHover: v } as Partial< T > ) }
			/>
			<DualColor
				label={ __( 'Background', 'flexa-block' ) }
				value={ attributes.buttonBackground || {} }
				onChange={ ( v ) => setAttributes( { buttonBackground: v } as Partial< T > ) }
			/>
			<DualColor
				label={ __( 'Background (hover)', 'flexa-block' ) }
				value={ attributes.buttonBackgroundHover || {} }
				onChange={ ( v ) => setAttributes( { buttonBackgroundHover: v } as Partial< T > ) }
			/>
			<SliderUnit
				label={ __( 'Corner radius', 'flexa-block' ) }
				value={ attributes.buttonRadius || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 8, rem: 8, '%': 50 } }
				showDevice={ false }
				onChange={ ( v: LengthValue ) => setAttributes( { buttonRadius: v } as Partial< T > ) }
			/>
			<Dimensions
				label={ __( 'Button padding', 'flexa-block' ) }
				value={ attributes.buttonPadding || {} }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { buttonPadding: v } as Partial< T > ) }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				value={ attributes.buttonAlign || '' }
				onChange={ ( v ) => setAttributes( { buttonAlign: v } as Partial< T > ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Width', 'flexa-block' ) }
				value={ attributes.buttonWidth || 'auto' }
				options={ BUTTON_WIDTH_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { buttonWidth: v } as Partial< T > ) }
			/>
		</PanelBody>
	);
};
