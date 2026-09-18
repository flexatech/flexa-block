/**
 * Product Meta block — block-specific inspector panels.
 *
 * Product Meta is a container of flexa/product-field rows, so these panels only
 * cover what a list can own: the label column, the gaps, the divider, and the
 * label / value styling every row starts from. Everything about what a row
 * prints lives in that row's own inspector — select the row to reach it.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility / animation) come from @components. Nothing is styled by default,
 * so only the values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl } from '@wordpress/components';

import { Segmented, SliderUnit, DualColor, TypographyControls, CONTENT_ALIGN_OPTIONS, useDevice } from '@components';
import { LENGTH_UNITS, SPACING_UNITS, rawDevice, patchDevice } from '@utils';
import type { LengthValue, PanelProps, ProductMetaAttributes, TypographyDevice } from '../../types';

type PMPanelProps = PanelProps< ProductMetaAttributes >;

/**
 * Layout panel — alignment plus the three lengths that turn a stack of rows
 * into a table: the gap between rows, the gap between a label and its value,
 * and the label column that makes the values line up.
 */
export const ProductMetaLayoutPanel = ( { attributes, setAttributes }: PMPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { alignment, rowGap, labelGap, labelWidth } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || '' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Row gap', 'flexa-block' ) }
				value={ rowGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 60, em: 6, rem: 6 } }
				onChange={ ( v: LengthValue ) => setAttributes( { rowGap: { ...rowGap, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Label gap', 'flexa-block' ) }
				value={ labelGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 60, em: 6, rem: 6 } }
				onChange={ ( v: LengthValue ) => setAttributes( { labelGap: { ...labelGap, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Label column width', 'flexa-block' ) }
				value={ labelWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 400, em: 24, rem: 24, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { labelWidth: { ...labelWidth, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Labels panel — the typography and colour every row's label starts from. A row
 * can override both from its own inspector.
 */
export const ProductMetaLabelStylePanel = ( { attributes, setAttributes }: PMPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.labelTypography, device );

	return (
		<PanelBody title={ __( 'Labels', 'flexa-block' ) } initialOpen={ false }>
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
 * Values panel — the typography and colour every row's value starts from, terms
 * included so a linked category looks like the plain SKU above it.
 */
export const ProductMetaValueStylePanel = ( { attributes, setAttributes }: PMPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.valueTypography, device );

	return (
		<PanelBody title={ __( 'Values', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls
				value={ typo }
				onChange={ ( patch: Partial< TypographyDevice > ) =>
					setAttributes( { valueTypography: patchDevice( attributes.valueTypography, device, patch ) } )
				}
			/>
			<DualColor
				label={ __( 'Value color', 'flexa-block' ) }
				value={ attributes.valueColor || {} }
				onChange={ ( v ) => setAttributes( { valueColor: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Divider panel — the hairline between rows. The width and colour only render
 * once the divider is on, because the generator emits neither until then.
 */
export const ProductMetaDividerPanel = ( { attributes, setAttributes }: PMPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { showDivider, dividerWidth, dividerColor } = attributes;

	return (
		<PanelBody title={ __( 'Divider', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show divider between rows', 'flexa-block' ) }
				checked={ !! showDivider }
				onChange={ ( v: boolean ) => setAttributes( { showDivider: v } ) }
			/>
			{ !! showDivider && (
				<>
					<SliderUnit
						label={ __( 'Divider width', 'flexa-block' ) }
						value={ dividerWidth?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						max={ { px: 20, em: 2, rem: 2 } }
						onChange={ ( v: LengthValue ) => setAttributes( { dividerWidth: { ...dividerWidth, [ device ]: v } } ) }
					/>
					<DualColor label={ __( 'Divider color', 'flexa-block' ) } value={ dividerColor || {} } onChange={ ( v ) => setAttributes( { dividerColor: v } ) } />
				</>
			) }
		</PanelBody>
	);
};
