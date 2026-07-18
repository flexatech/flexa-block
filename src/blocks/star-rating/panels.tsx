/**
 * Star Rating block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility / animation) plus the shared typography controls come from
 * @components; these cover the parts unique to a star rating: the rating value
 * and range, the star size / gap / alignment / marked-and-unmarked colours, the
 * inline-vs-stacked layout with an optional title, and the aggregateRating
 * schema toggle. Following the "prefer theme styles" rule nothing is coloured by
 * default — only the values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, RangeControl, ToggleControl, TextControl } from '@wordpress/components';

import { Segmented, SliderUnit, DualColor, TypographyControls, CONTENT_ALIGN_OPTIONS, useDevice } from '@components';
import { rawDevice, patchDevice, LENGTH_UNITS, SPACING_UNITS } from '@utils';
import type { ControlOption, LengthValue, PanelProps, StarRatingAttributes, TypographyDevice } from '../../types';

type SPanelProps = PanelProps< StarRatingAttributes >;

/** Inline (title beside stars) vs stacked (title above/below) — few values → Segmented. */
const LAYOUT_OPTIONS: ControlOption[] = [
	{ value: 'inline', label: __( 'Inline', 'flexa-block' ) },
	{ value: 'stacked', label: __( 'Stacked', 'flexa-block' ) },
];

/** Whether the title sits before or after the stars. */
const TITLE_POSITION_OPTIONS: ControlOption[] = [
	{ value: 'before', label: __( 'Before', 'flexa-block' ) },
	{ value: 'after', label: __( 'After', 'flexa-block' ) },
];

/**
 * Rating panel — the value + range, star size / gap, alignment and the
 * marked / unmarked star colours.
 */
export const StarRatingRatingPanel = ( { attributes, setAttributes }: SPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { rating, maxRating, starSize, gap, alignment, color, unmarkedColor } = attributes;
	const max = typeof maxRating === 'number' && maxRating > 0 ? maxRating : 5;

	return (
		<PanelBody title={ __( 'Rating', 'flexa-block' ) } initialOpen={ true }>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Rating', 'flexa-block' ) }
				value={ typeof rating === 'number' ? rating : 4 }
				min={ 0 }
				max={ max }
				step={ 0.5 }
				onChange={ ( v: number | undefined ) => setAttributes( { rating: v ?? 0 } ) }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Out of', 'flexa-block' ) }
				value={ max }
				min={ 1 }
				max={ 10 }
				step={ 1 }
				onChange={ ( v: number | undefined ) => setAttributes( { maxRating: v ?? 5 } ) }
			/>
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
				value={ gap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 60, em: 6, rem: 6, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { gap: { ...gap, [ device ]: v } } ) }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || 'left' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<DualColor label={ __( 'Star colour', 'flexa-block' ) } value={ color || {} } onChange={ ( v ) => setAttributes( { color: v } ) } />
			<DualColor label={ __( 'Empty star colour', 'flexa-block' ) } value={ unmarkedColor || {} } onChange={ ( v ) => setAttributes( { unmarkedColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Layout & title panel — inline/stacked layout plus an optional title with its
 * position, gap, typography and colour.
 */
export const StarRatingLayoutPanel = ( { attributes, setAttributes }: SPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { ratingLayout, showTitle, title, titlePosition, titleGap, titleColor } = attributes;
	const typo = rawDevice( attributes.titleTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { titleTypography: patchDevice( attributes.titleTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Layout & title', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Layout', 'flexa-block' ) }
				value={ ratingLayout || 'inline' }
				onChange={ ( v ) => setAttributes( { ratingLayout: v as StarRatingAttributes[ 'ratingLayout' ] } ) }
				options={ LAYOUT_OPTIONS }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show title', 'flexa-block' ) }
				checked={ !! showTitle }
				onChange={ ( v: boolean ) => setAttributes( { showTitle: v } ) }
			/>
			{ showTitle && (
				<>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Title', 'flexa-block' ) }
						value={ title || '' }
						onChange={ ( v: string ) => setAttributes( { title: v } ) }
					/>
					<Segmented
						label={ __( 'Title position', 'flexa-block' ) }
						value={ titlePosition || 'after' }
						onChange={ ( v ) => setAttributes( { titlePosition: v as StarRatingAttributes[ 'titlePosition' ] } ) }
						options={ TITLE_POSITION_OPTIONS }
					/>
					<SliderUnit
						label={ __( 'Title gap', 'flexa-block' ) }
						value={ titleGap?.[ device ] || {} }
						units={ SPACING_UNITS }
						defaultUnit="px"
						max={ { px: 80, em: 8, rem: 8, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { titleGap: { ...titleGap, [ device ]: v } } ) }
					/>
					<TypographyControls value={ typo } onChange={ setTypo } />
					<DualColor label={ __( 'Title colour', 'flexa-block' ) } value={ titleColor || {} } onChange={ ( v ) => setAttributes( { titleColor: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * SEO panel — the aggregateRating JSON-LD schema toggle (off by default; only
 * one rating schema of each kind should appear per page).
 */
export const StarRatingSeoPanel = ( { attributes, setAttributes }: SPanelProps ): JSX.Element => {
	const { enableSchema } = attributes;

	return (
		<PanelBody title={ __( 'SEO', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Enable rating schema', 'flexa-block' ) }
				help={ __( 'Outputs aggregateRating JSON-LD for rich results. Keep only one rating schema per page to avoid duplicate structured data.', 'flexa-block' ) }
				checked={ !! enableSchema }
				onChange={ ( v: boolean ) => setAttributes( { enableSchema: v } ) }
			/>
		</PanelBody>
	);
};
