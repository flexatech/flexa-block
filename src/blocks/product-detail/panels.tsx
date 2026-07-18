/**
 * Product Details block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) come from @components; these cover the parts unique to the product
 * details: which tabs to show, the tab-title typography / colours (normal +
 * active) / padding / gap, and the content area's typography / colour / padding.
 * Following the "prefer theme styles" rule nothing is coloured by default — only
 * values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl } from '@wordpress/components';

import {
	SliderUnit,
	Dimensions,
	DualColor,
	TypographyControls,
	FieldHead,
	useDevice,
} from '@components';
import { rawDevice, patchDevice, SPACING_UNITS, LENGTH_UNITS } from '@utils';
import type { BoxValue, LengthValue, PanelProps, ProductDetailAttributes, TypographyDevice } from '../../types';

type ProductDetailPanelProps = PanelProps< ProductDetailAttributes >;

/**
 * Settings panel — which of the three tabs (Description / Additional information
 * / Reviews) are shown.
 */
export const ProductDetailSettingsPanel = ( { attributes, setAttributes }: ProductDetailPanelProps ): JSX.Element => {
	const { showDescriptionTab, showAdditionalTab, showReviewsTab } = attributes;

	return (
		<PanelBody title={ __( 'Settings', 'flexa-block' ) } initialOpen={ true }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show Description tab', 'flexa-block' ) }
				checked={ showDescriptionTab !== false }
				onChange={ ( v: boolean ) => setAttributes( { showDescriptionTab: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show Additional information tab', 'flexa-block' ) }
				checked={ showAdditionalTab !== false }
				onChange={ ( v: boolean ) => setAttributes( { showAdditionalTab: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show Reviews tab', 'flexa-block' ) }
				checked={ showReviewsTab !== false }
				onChange={ ( v: boolean ) => setAttributes( { showReviewsTab: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Tab Title panel — the tab labels' typography, normal / active text and
 * background colours, the tab padding and the gap between tabs.
 */
export const ProductDetailTabTitlePanel = ( { attributes, setAttributes }: ProductDetailPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.tabTitleTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) =>
		setAttributes( { tabTitleTypography: patchDevice( attributes.tabTitleTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Tab Title', 'flexa-block' ) } initialOpen={ true }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text', 'flexa-block' ) } value={ attributes.tabTitleColor || {} } onChange={ ( v ) => setAttributes( { tabTitleColor: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ attributes.tabTitleBg || {} } onChange={ ( v ) => setAttributes( { tabTitleBg: v } ) } />
			<DualColor label={ __( 'Active text', 'flexa-block' ) } value={ attributes.tabActiveColor || {} } onChange={ ( v ) => setAttributes( { tabActiveColor: v } ) } />
			<DualColor label={ __( 'Active background', 'flexa-block' ) } value={ attributes.tabActiveBg || {} } onChange={ ( v ) => setAttributes( { tabActiveBg: v } ) } />
			<Dimensions
				label={ __( 'Padding', 'flexa-block' ) }
				responsive
				value={ rawDevice( attributes.tabTitlePadding, device ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { tabTitlePadding: { ...attributes.tabTitlePadding, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Gap', 'flexa-block' ) }
				value={ attributes.tabGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { tabGap: { ...attributes.tabGap, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Content panel — the content area's typography, text colour and padding.
 */
export const ProductDetailContentPanel = ( { attributes, setAttributes }: ProductDetailPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.contentTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) =>
		setAttributes( { contentTypography: patchDevice( attributes.contentTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Content', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text', 'flexa-block' ) } value={ attributes.contentColor || {} } onChange={ ( v ) => setAttributes( { contentColor: v } ) } />
			<Dimensions
				label={ __( 'Padding', 'flexa-block' ) }
				responsive
				value={ rawDevice( attributes.contentPadding, device ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { contentPadding: { ...attributes.contentPadding, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Reviews panel — per-element styling for the WooCommerce Reviews tab (the
 * reviews title, each review's author / date / stars, and the review text).
 * Applies only to the Reviews tab; the general Content panel still covers the
 * Description / Additional tabs.
 */
export const ProductDetailReviewsPanel = ( { attributes, setAttributes }: ProductDetailPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const titleTypo = rawDevice( attributes.reviewsTitleTypography, device );
	const setTitleTypo = ( patch: Partial< TypographyDevice > ) =>
		setAttributes( { reviewsTitleTypography: patchDevice( attributes.reviewsTitleTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Reviews', 'flexa-block' ) } initialOpen={ false }>
			<FieldHead label={ __( 'Title', 'flexa-block' ) } />
			<DualColor label={ __( 'Title colour', 'flexa-block' ) } value={ attributes.reviewsTitleColor || {} } onChange={ ( v ) => setAttributes( { reviewsTitleColor: v } ) } />
			<TypographyControls value={ titleTypo } onChange={ setTitleTypo } />

			<FieldHead label={ __( 'Meta', 'flexa-block' ) } />
			<DualColor label={ __( 'Author colour', 'flexa-block' ) } value={ attributes.reviewAuthorColor || {} } onChange={ ( v ) => setAttributes( { reviewAuthorColor: v } ) } />
			<DualColor label={ __( 'Date colour', 'flexa-block' ) } value={ attributes.reviewDateColor || {} } onChange={ ( v ) => setAttributes( { reviewDateColor: v } ) } />

			<FieldHead label={ __( 'Stars', 'flexa-block' ) } />
			<DualColor label={ __( 'Stars colour', 'flexa-block' ) } value={ attributes.reviewStarsColor || {} } onChange={ ( v ) => setAttributes( { reviewStarsColor: v } ) } />
			<SliderUnit
				label={ __( 'Stars size', 'flexa-block' ) }
				value={ attributes.reviewStarsSize || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 40, em: 4, rem: 4, '%': 300 } }
				onChange={ ( v: LengthValue ) => setAttributes( { reviewStarsSize: v } ) }
			/>

			<FieldHead label={ __( 'Text', 'flexa-block' ) } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.reviewTextColor || {} } onChange={ ( v ) => setAttributes( { reviewTextColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Additional information panel — style the WooCommerce product-attributes table
 * (row labels and values, cell borders and padding). Applies only to the
 * Additional information tab.
 */
export const ProductDetailAdditionalPanel = ( { attributes, setAttributes }: ProductDetailPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const labelTypo = rawDevice( attributes.additionalLabelTypography, device );
	const setLabelTypo = ( patch: Partial< TypographyDevice > ) =>
		setAttributes( { additionalLabelTypography: patchDevice( attributes.additionalLabelTypography, device, patch ) } );
	const valueTypo = rawDevice( attributes.additionalValueTypography, device );
	const setValueTypo = ( patch: Partial< TypographyDevice > ) =>
		setAttributes( { additionalValueTypography: patchDevice( attributes.additionalValueTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Additional information', 'flexa-block' ) } initialOpen={ false }>
			<FieldHead label={ __( 'Label', 'flexa-block' ) } />
			<DualColor label={ __( 'Label colour', 'flexa-block' ) } value={ attributes.additionalLabelColor || {} } onChange={ ( v ) => setAttributes( { additionalLabelColor: v } ) } />
			<TypographyControls value={ labelTypo } onChange={ setLabelTypo } />

			<FieldHead label={ __( 'Value', 'flexa-block' ) } />
			<DualColor label={ __( 'Value colour', 'flexa-block' ) } value={ attributes.additionalValueColor || {} } onChange={ ( v ) => setAttributes( { additionalValueColor: v } ) } />
			<TypographyControls value={ valueTypo } onChange={ setValueTypo } />

			<FieldHead label={ __( 'Cells', 'flexa-block' ) } />
			<DualColor label={ __( 'Border colour', 'flexa-block' ) } value={ attributes.additionalBorderColor || {} } onChange={ ( v ) => setAttributes( { additionalBorderColor: v } ) } />
			<Dimensions
				label={ __( 'Cell padding', 'flexa-block' ) }
				value={ rawDevice( attributes.additionalCellPadding, device ) }
				units={ SPACING_UNITS }
				responsive
				onChange={ ( v: BoxValue ) => setAttributes( { additionalCellPadding: { ...attributes.additionalCellPadding, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};
