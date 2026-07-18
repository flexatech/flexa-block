/**
 * Progress Bar block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility / animation) come from @components; these cover the parts unique to
 * a progress bar: the value (percentage or absolute count), the title + counter
 * readout, the bar geometry (line thickness or circle size / ring width), the
 * fill style and the fill / track colours. Following the "prefer theme styles"
 * rule nothing is styled by default beyond a sensible fill so the bar reads as
 * complete the moment it is inserted.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, RangeControl, TextControl, SelectControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	DualColor,
	FieldHead,
	TypographyControls,
	TEXT_TAG_OPTIONS,
	CONTENT_ALIGN_OPTIONS,
	useDevice,
} from '@components';
import {
	rawDevice,
	patchDevice,
	LENGTH_UNITS,
	WIDTH_UNITS,
	WEIGHT_UNITS,
} from '@utils';
import type {
	ControlOption,
	LengthValue,
	PanelProps,
	ProcessBarAttributes,
	TypographyDevice,
} from '../../types';

type PPanelProps = PanelProps< ProcessBarAttributes >;

/** Layout of the bar (few values → Segmented). */
const BAR_TYPE_OPTIONS: ControlOption[] = [
	{ value: 'line', label: __( 'Line', 'flexa-block' ) },
	{ value: 'circle', label: __( 'Circle', 'flexa-block' ) },
	{ value: 'semicircle', label: __( 'Semi-circle', 'flexa-block' ) },
];

/** Percentage vs absolute count (two values → Segmented). */
const VALUE_TYPE_OPTIONS: ControlOption[] = [
	{ value: 'percent', label: __( 'Percent', 'flexa-block' ) },
	{ value: 'absolute', label: __( 'Absolute', 'flexa-block' ) },
];

/** Fill appearance (three values → SelectControl). */
const FILL_STYLE_OPTIONS: ControlOption[] = [
	{ value: 'solid', label: __( 'Solid', 'flexa-block' ) },
	{ value: 'striped', label: __( 'Striped', 'flexa-block' ) },
	{ value: 'striped-animated', label: __( 'Striped (animated)', 'flexa-block' ) },
];

/** Rounded / square / flat ring ends (few values → Segmented). */
const LINE_CAP_OPTIONS: ControlOption[] = [
	{ value: 'round', label: __( 'Round', 'flexa-block' ) },
	{ value: 'square', label: __( 'Square', 'flexa-block' ) },
	{ value: 'butt', label: __( 'Flat', 'flexa-block' ) },
];

/** Where the title / counter row sits relative to the line bar. */
const TITLE_POSITION_OPTIONS: ControlOption[] = [
	{ value: 'above', label: __( 'Above', 'flexa-block' ) },
	{ value: 'below', label: __( 'Below', 'flexa-block' ) },
];

/**
 * Value panel — the layout, the progress value (percentage or absolute count)
 * and the counter readout.
 */
export const ProcessBarValuePanel = ( { attributes, setAttributes }: PPanelProps ): JSX.Element => {
	const { barType, valueType, value, max, showCounter, dividerChar } = attributes;
	const isAbsolute = valueType === 'absolute';

	return (
		<PanelBody title={ __( 'Progress', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Layout', 'flexa-block' ) }
				value={ barType || 'line' }
				onChange={ ( v ) => setAttributes( { barType: v as ProcessBarAttributes[ 'barType' ] } ) }
				options={ BAR_TYPE_OPTIONS }
			/>
			<Segmented
				label={ __( 'Value type', 'flexa-block' ) }
				value={ valueType || 'percent' }
				onChange={ ( v ) => setAttributes( { valueType: v as ProcessBarAttributes[ 'valueType' ] } ) }
				options={ VALUE_TYPE_OPTIONS }
			/>
			{ isAbsolute ? (
				<>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Value', 'flexa-block' ) }
						type="number"
						value={ String( typeof value === 'number' ? value : 75 ) }
						onChange={ ( v: string ) => setAttributes( { value: parseFloat( v ) || 0 } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Maximum', 'flexa-block' ) }
						type="number"
						value={ String( typeof max === 'number' ? max : 100 ) }
						onChange={ ( v: string ) => setAttributes( { max: parseFloat( v ) || 0 } ) }
					/>
				</>
			) : (
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Value (%)', 'flexa-block' ) }
					value={ typeof value === 'number' ? value : 75 }
					min={ 0 }
					max={ 100 }
					onChange={ ( v: number | undefined ) => setAttributes( { value: v ?? 0 } ) }
				/>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show counter', 'flexa-block' ) }
				checked={ showCounter !== false }
				onChange={ ( v: boolean ) => setAttributes( { showCounter: v } ) }
			/>
			{ showCounter !== false && isAbsolute && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Divider character', 'flexa-block' ) }
					help={ __( 'Shown between the value and the maximum, e.g. 75 / 100.', 'flexa-block' ) }
					value={ dividerChar ?? '/' }
					onChange={ ( v: string ) => setAttributes( { dividerChar: v } ) }
				/>
			) }
		</PanelBody>
	);
};

/**
 * Title panel — the optional title text, its tag and (for the line layout) where
 * it sits relative to the bar.
 */
export const ProcessBarTitlePanel = ( { attributes, setAttributes }: PPanelProps ): JSX.Element => {
	const { showTitle, title, titleTag, titlePosition, barType } = attributes;

	return (
		<PanelBody title={ __( 'Title', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show title', 'flexa-block' ) }
				checked={ showTitle !== false }
				onChange={ ( v: boolean ) => setAttributes( { showTitle: v } ) }
			/>
			{ showTitle !== false && (
				<>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Title', 'flexa-block' ) }
						value={ title ?? '' }
						onChange={ ( v: string ) => setAttributes( { title: v } ) }
					/>
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Title tag', 'flexa-block' ) }
						value={ titleTag || 'div' }
						options={ TEXT_TAG_OPTIONS }
						onChange={ ( v: string ) => setAttributes( { titleTag: v } ) }
					/>
					{ ( barType || 'line' ) === 'line' && (
						<Segmented
							label={ __( 'Title position', 'flexa-block' ) }
							value={ titlePosition || 'above' }
							onChange={ ( v ) => setAttributes( { titlePosition: v as ProcessBarAttributes[ 'titlePosition' ] } ) }
							options={ TITLE_POSITION_OPTIONS }
						/>
					) }
				</>
			) }
		</PanelBody>
	);
};

/**
 * Bar panel — alignment, max width, the geometry (line thickness or circle
 * diameter / ring width), the fill style and the on-scroll fill animation.
 */
export const ProcessBarBarPanel = ( { attributes, setAttributes }: PPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { barType, barHeight, circleSize, strokeWidth, barRadius, fillStyle, lineCap, alignment, maxWidth, animateFill, fillDuration } = attributes;
	const isLine = ( barType || 'line' ) === 'line';

	return (
		<PanelBody title={ __( 'Bar', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || ( device === 'desktop' ? 'left' : '' ) || 'left' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Max width', 'flexa-block' ) }
				value={ maxWidth?.[ device ] || {} }
				units={ WIDTH_UNITS }
				defaultUnit="px"
				max={ { px: 1200, '%': 100, em: 60, rem: 60, vw: 100, vh: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { maxWidth: { ...maxWidth, [ device ]: v } } ) }
			/>
			{ isLine ? (
				<>
					<SliderUnit
						label={ __( 'Bar thickness', 'flexa-block' ) }
						value={ barHeight?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						min={ 1 }
						max={ { px: 80, em: 8, rem: 8, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { barHeight: { ...barHeight, [ device ]: v } } ) }
					/>
					<SliderUnit
						label={ __( 'Corner radius', 'flexa-block' ) }
						showDevice={ false }
						value={ barRadius || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						max={ { px: 999, em: 10, rem: 10, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { barRadius: v } ) }
					/>
				</>
			) : (
				<>
					<SliderUnit
						label={ __( 'Circle size', 'flexa-block' ) }
						value={ circleSize?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						min={ 40 }
						max={ { px: 480, em: 30, rem: 30, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { circleSize: { ...circleSize, [ device ]: v } } ) }
					/>
					<SliderUnit
						label={ __( 'Ring thickness', 'flexa-block' ) }
						value={ strokeWidth?.[ device ] || {} }
						units={ WEIGHT_UNITS }
						defaultUnit="px"
						min={ 1 }
						max={ { px: 20 } }
						onChange={ ( v: LengthValue ) => setAttributes( { strokeWidth: { ...strokeWidth, [ device ]: v } } ) }
					/>
					<Segmented
						label={ __( 'Ring ends', 'flexa-block' ) }
						value={ lineCap || 'round' }
						onChange={ ( v ) => setAttributes( { lineCap: v as ProcessBarAttributes[ 'lineCap' ] } ) }
						options={ LINE_CAP_OPTIONS }
					/>
				</>
			) }
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Fill style', 'flexa-block' ) }
				value={ fillStyle || 'solid' }
				options={ FILL_STYLE_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { fillStyle: v as ProcessBarAttributes[ 'fillStyle' ] } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Animate on scroll', 'flexa-block' ) }
				checked={ animateFill !== false }
				onChange={ ( v: boolean ) => setAttributes( { animateFill: v } ) }
			/>
			{ animateFill !== false && (
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Fill duration (ms)', 'flexa-block' ) }
					value={ typeof fillDuration === 'number' ? fillDuration : 1500 }
					min={ 200 }
					max={ 6000 }
					step={ 100 }
					onChange={ ( v: number | undefined ) => setAttributes( { fillDuration: v ?? 1500 } ) }
				/>
			) }
		</PanelBody>
	);
};

/**
 * Colours panel — the fill and track colours (each a light / dark pair).
 */
export const ProcessBarColorsPanel = ( { attributes, setAttributes }: PPanelProps ): JSX.Element => {
	const { fillColor, trackColor } = attributes;

	return (
		<PanelBody title={ __( 'Colours', 'flexa-block' ) } initialOpen={ true }>
			<DualColor label={ __( 'Fill', 'flexa-block' ) } value={ fillColor || {} } onChange={ ( v ) => setAttributes( { fillColor: v } ) } />
			<DualColor label={ __( 'Track', 'flexa-block' ) } value={ trackColor || {} } onChange={ ( v ) => setAttributes( { trackColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Text panel — the title and counter typography and colours.
 */
export const ProcessBarTextPanel = ( { attributes, setAttributes }: PPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const titleTypo = rawDevice( attributes.titleTypography, device );
	const counterTypo = rawDevice( attributes.counterTypography, device );
	const setTitleTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { titleTypography: patchDevice( attributes.titleTypography, device, patch ) } );
	const setCounterTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { counterTypography: patchDevice( attributes.counterTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Text', 'flexa-block' ) } initialOpen={ false }>
			<FieldHead label={ __( 'Title typography', 'flexa-block' ) } />
			<TypographyControls value={ titleTypo } onChange={ setTitleTypo } />
			<DualColor label={ __( 'Title colour', 'flexa-block' ) } value={ attributes.titleColor || {} } onChange={ ( v ) => setAttributes( { titleColor: v } ) } />
			<FieldHead label={ __( 'Counter typography', 'flexa-block' ) } />
			<TypographyControls value={ counterTypo } onChange={ setCounterTypo } />
			<DualColor label={ __( 'Counter colour', 'flexa-block' ) } value={ attributes.counterColor || {} } onChange={ ( v ) => setAttributes( { counterColor: v } ) } />
		</PanelBody>
	);
};
