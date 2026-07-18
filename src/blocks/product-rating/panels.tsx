/**
 * Product Rating block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility / animation) come from @components; these cover the parts unique to
 * a product rating: the display type (stars / number / both), the review-count
 * and unrated toggles, the star size / gap / alignment / filled-and-empty
 * colours, and the review-count colour + typography. Following the "prefer theme
 * styles" rule nothing is coloured by default — only the values the user picks
 * produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, SelectControl } from '@wordpress/components';

import { Segmented, SliderUnit, DualColor, TypographyControls, CONTENT_ALIGN_OPTIONS, useDevice } from '@components';
import { rawDevice, patchDevice, LENGTH_UNITS, SPACING_UNITS } from '@utils';
import type { ControlOption, LengthValue, PanelProps, ProductRatingAttributes, TypographyDevice } from '../../types';

type PRPanelProps = PanelProps< ProductRatingAttributes >;

/** How the rating is shown — stars, both, or a bare number. */
const DISPLAY_TYPE_OPTIONS: ControlOption[] = [
	{ value: 'stars', label: __( 'Stars', 'flexa-block' ) },
	{ value: 'stars-number', label: __( 'Stars + number', 'flexa-block' ) },
	{ value: 'number', label: __( 'Number', 'flexa-block' ) },
];

/**
 * Settings panel — alignment, display type and the review-count / unrated
 * toggles.
 */
export const ProductRatingSettingsPanel = ( { attributes, setAttributes }: PRPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { alignment, displayType, showReviewCount, showEmptyRating } = attributes;

	return (
		<PanelBody title={ __( 'Settings', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || 'left' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Display type', 'flexa-block' ) }
				value={ displayType || 'stars' }
				options={ DISPLAY_TYPE_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { displayType: v as ProductRatingAttributes[ 'displayType' ] } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show review count', 'flexa-block' ) }
				checked={ showReviewCount !== false }
				onChange={ ( v: boolean ) => setAttributes( { showReviewCount: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show when unrated', 'flexa-block' ) }
				help={ __( 'Show the block even when the product has no rating yet.', 'flexa-block' ) }
				checked={ !! showEmptyRating }
				onChange={ ( v: boolean ) => setAttributes( { showEmptyRating: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Stars panel — filled / empty star colours and the star size + gap.
 */
export const ProductRatingStarsPanel = ( { attributes, setAttributes }: PRPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { starColor, starEmptyColor, starSize, starGap } = attributes;

	return (
		<PanelBody title={ __( 'Stars', 'flexa-block' ) } initialOpen={ true }>
			<DualColor label={ __( 'Star colour', 'flexa-block' ) } value={ starColor || {} } onChange={ ( v ) => setAttributes( { starColor: v } ) } />
			<DualColor label={ __( 'Empty star colour', 'flexa-block' ) } value={ starEmptyColor || {} } onChange={ ( v ) => setAttributes( { starEmptyColor: v } ) } />
			<SliderUnit
				label={ __( 'Star size', 'flexa-block' ) }
				value={ starSize?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 8, rem: 8, '%': 300 } }
				onChange={ ( v: LengthValue ) => setAttributes( { starSize: { ...starSize, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Star gap', 'flexa-block' ) }
				value={ starGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 60, em: 6, rem: 6, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { starGap: { ...starGap, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Number panel — the numeric score's colour and typography. Only meaningful for
 * the 'number' / 'stars-number' display types, so it renders only then.
 */
export const ProductRatingNumberPanel = ( { attributes, setAttributes }: PRPanelProps ): JSX.Element | null => {
	const [ device ] = useDevice();
	const { displayType, numberColor } = attributes;

	if ( 'number' !== displayType && 'stars-number' !== displayType ) {
		return null;
	}

	const typo = rawDevice( attributes.numberTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { numberTypography: patchDevice( attributes.numberTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Number', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Number colour', 'flexa-block' ) } value={ numberColor || {} } onChange={ ( v ) => setAttributes( { numberColor: v } ) } />
			<TypographyControls value={ typo } onChange={ setTypo } />
		</PanelBody>
	);
};

/**
 * Review-count panel — the count colour and typography. Only meaningful when the
 * review count is shown, so it renders only then.
 */
export const ProductRatingCountPanel = ( { attributes, setAttributes }: PRPanelProps ): JSX.Element | null => {
	const [ device ] = useDevice();
	const { showReviewCount, countColor } = attributes;

	if ( showReviewCount === false ) {
		return null;
	}

	const typo = rawDevice( attributes.countTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { countTypography: patchDevice( attributes.countTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Review Count', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Count colour', 'flexa-block' ) } value={ countColor || {} } onChange={ ( v ) => setAttributes( { countColor: v } ) } />
			<TypographyControls value={ typo } onChange={ setTypo } />
		</PanelBody>
	);
};
