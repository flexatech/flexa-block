/**
 * FAQ block — FAQ-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) plus the shared typography controls come from @components; these
 * cover the parts unique to the FAQ: the accordion layout, open/close
 * behaviour, the open-state icon, dividers, and the per-question / answer /
 * item-box styling. Following the "prefer theme styles" rule nothing is styled
 * by default — only values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	Dimensions,
	DualColor,
	ColorGradientControl,
	TypographyControls,
	useDevice,
} from '@components';
import {
	rawDevice,
	patchDevice,
	SPACING_UNITS,
	LENGTH_UNITS,
	HTML_TAGS,
	AlignLeftIcon,
	AlignRightIcon,
} from '@utils';
import type { BoxValue, FaqAttributes, LengthValue, PanelProps, TypographyDevice } from '../../types';

type FaqPanelProps = PanelProps< FaqAttributes >;

/** Icon styles for the open/close indicator (few values → SelectControl). */
const ICON_STYLE_OPTIONS = [
	{ value: 'plus', label: __( 'Plus / Minus', 'flexa-block' ) },
	{ value: 'chevron', label: __( 'Chevron', 'flexa-block' ) },
	{ value: 'arrow', label: __( 'Arrow', 'flexa-block' ) },
];

/** Icon side (short icon set → Segmented). */
const ICON_POSITION_OPTIONS = [
	{ value: 'left', label: __( 'Left', 'flexa-block' ), icon: AlignLeftIcon },
	{ value: 'right', label: __( 'Right', 'flexa-block' ), icon: AlignRightIcon },
];

/**
 * Layout panel — row gap and max width (the FAQ is always an accordion).
 */
export const FaqLayoutPanel = ( { attributes, setAttributes }: FaqPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { rowGap, maxWidth } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<SliderUnit
				label={ __( 'Row gap', 'flexa-block' ) }
				value={ rowGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100, vw: 50, vh: 50 } }
				onChange={ ( v: LengthValue ) => setAttributes( { rowGap: { ...rowGap, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Max width', 'flexa-block' ) }
				value={ maxWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 1600, '%': 100, vw: 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { maxWidth: { ...maxWidth, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Behaviour panel — open/close rules, FAQ schema and the wrapper tag.
 */
export const FaqBehaviorPanel = ( { attributes, setAttributes }: FaqPanelProps ): JSX.Element => {
	const { expandFirst, closeOthers, allowToggle, enableSchema, htmlTag } = attributes;

	return (
		<PanelBody title={ __( 'Behaviour', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Open first item', 'flexa-block' ) } checked={ !! expandFirst } onChange={ ( v: boolean ) => setAttributes( { expandFirst: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Close others when one opens', 'flexa-block' ) } checked={ closeOthers !== false } onChange={ ( v: boolean ) => setAttributes( { closeOthers: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Allow closing an open item', 'flexa-block' ) } checked={ allowToggle !== false } onChange={ ( v: boolean ) => setAttributes( { allowToggle: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Output FAQ schema (SEO)', 'flexa-block' ) } help={ __( 'Add FAQPage structured data for search engines.', 'flexa-block' ) } checked={ !! enableSchema } onChange={ ( v: boolean ) => setAttributes( { enableSchema: v } ) } />
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'div' }
				options={ HTML_TAGS.map( ( t ) => ( { label: t, value: t } ) ) }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Icon panel — the open/close indicator: style, side, size, gap and colours.
 */
export const FaqIconPanel = ( { attributes, setAttributes }: FaqPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { showIcon, iconStyle, iconPosition, iconSize, iconGap, iconColor, iconActiveColor } = attributes;

	return (
		<PanelBody title={ __( 'Icon', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Show icon', 'flexa-block' ) } checked={ showIcon !== false } onChange={ ( v: boolean ) => setAttributes( { showIcon: v } ) } />
			{ showIcon !== false && (
				<>
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Icon style', 'flexa-block' ) }
						value={ iconStyle || 'plus' }
						options={ ICON_STYLE_OPTIONS }
						onChange={ ( v: string ) => setAttributes( { iconStyle: v as FaqAttributes[ 'iconStyle' ] } ) }
					/>
					<Segmented
						label={ __( 'Icon side', 'flexa-block' ) }
						value={ iconPosition || 'right' }
						onChange={ ( v ) => setAttributes( { iconPosition: v as FaqAttributes[ 'iconPosition' ] } ) }
						options={ ICON_POSITION_OPTIONS }
					/>
					<SliderUnit
						label={ __( 'Icon size', 'flexa-block' ) }
						value={ iconSize?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						max={ { px: 64, em: 6, rem: 6, '%': 200 } }
						onChange={ ( v: LengthValue ) => setAttributes( { iconSize: { ...iconSize, [ device ]: v } } ) }
					/>
					<SliderUnit
						label={ __( 'Gap from question', 'flexa-block' ) }
						value={ iconGap?.[ device ] || {} }
						units={ SPACING_UNITS }
						defaultUnit="px"
						max={ { px: 80, em: 8, rem: 8, '%': 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { iconGap: { ...iconGap, [ device ]: v } } ) }
					/>
					<DualColor label={ __( 'Icon colour', 'flexa-block' ) } value={ iconColor || {} } onChange={ ( v ) => setAttributes( { iconColor: v } ) } />
					<DualColor label={ __( 'Icon colour (open)', 'flexa-block' ) } value={ iconActiveColor || {} } onChange={ ( v ) => setAttributes( { iconActiveColor: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Question style panel — typography, colours (idle + open), backgrounds, padding.
 */
export const FaqQuestionPanel = ( { attributes, setAttributes }: FaqPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.questionTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { questionTypography: patchDevice( attributes.questionTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Question', 'flexa-block' ) } initialOpen={ true }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<ColorGradientControl
				label={ __( 'Text colour', 'flexa-block' ) }
				type={ attributes.questionColorType || 'color' }
				color={ attributes.questionColor || {} }
				gradient={ attributes.questionColorGradient || {} }
				onTypeChange={ ( v ) => setAttributes( { questionColorType: v } ) }
				onColorChange={ ( v ) => setAttributes( { questionColor: v } ) }
				onGradientChange={ ( v ) => setAttributes( { questionColorGradient: v } ) }
			/>
			<ColorGradientControl
				label={ __( 'Text colour (open)', 'flexa-block' ) }
				type={ attributes.questionActiveColorType || 'color' }
				color={ attributes.questionActiveColor || {} }
				gradient={ attributes.questionActiveColorGradient || {} }
				onTypeChange={ ( v ) => setAttributes( { questionActiveColorType: v } ) }
				onColorChange={ ( v ) => setAttributes( { questionActiveColor: v } ) }
				onGradientChange={ ( v ) => setAttributes( { questionActiveColorGradient: v } ) }
			/>
			<ColorGradientControl
				label={ __( 'Background', 'flexa-block' ) }
				type={ attributes.questionBackgroundType || 'color' }
				color={ attributes.questionBackground || {} }
				gradient={ attributes.questionBackgroundGradient || {} }
				onTypeChange={ ( v ) => setAttributes( { questionBackgroundType: v } ) }
				onColorChange={ ( v ) => setAttributes( { questionBackground: v } ) }
				onGradientChange={ ( v ) => setAttributes( { questionBackgroundGradient: v } ) }
			/>
			<ColorGradientControl
				label={ __( 'Background (open)', 'flexa-block' ) }
				type={ attributes.questionActiveBackgroundType || 'color' }
				color={ attributes.questionActiveBackground || {} }
				gradient={ attributes.questionActiveBackgroundGradient || {} }
				onTypeChange={ ( v ) => setAttributes( { questionActiveBackgroundType: v } ) }
				onColorChange={ ( v ) => setAttributes( { questionActiveBackground: v } ) }
				onGradientChange={ ( v ) => setAttributes( { questionActiveBackgroundGradient: v } ) }
			/>
			<Dimensions
				label={ __( 'Padding', 'flexa-block' ) }
				responsive
				value={ rawDevice( attributes.questionPadding, device ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { questionPadding: { ...attributes.questionPadding, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Answer style panel — typography, colour, background, padding.
 */
export const FaqAnswerPanel = ( { attributes, setAttributes }: FaqPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.answerTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { answerTypography: patchDevice( attributes.answerTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Answer', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<ColorGradientControl
				label={ __( 'Text colour', 'flexa-block' ) }
				type={ attributes.answerColorType || 'color' }
				color={ attributes.answerColor || {} }
				gradient={ attributes.answerColorGradient || {} }
				onTypeChange={ ( v ) => setAttributes( { answerColorType: v } ) }
				onColorChange={ ( v ) => setAttributes( { answerColor: v } ) }
				onGradientChange={ ( v ) => setAttributes( { answerColorGradient: v } ) }
			/>
			<ColorGradientControl
				label={ __( 'Background', 'flexa-block' ) }
				type={ attributes.answerBackgroundType || 'color' }
				color={ attributes.answerBackground || {} }
				gradient={ attributes.answerBackgroundGradient || {} }
				onTypeChange={ ( v ) => setAttributes( { answerBackgroundType: v } ) }
				onColorChange={ ( v ) => setAttributes( { answerBackground: v } ) }
				onGradientChange={ ( v ) => setAttributes( { answerBackgroundGradient: v } ) }
			/>
			<Dimensions
				label={ __( 'Padding', 'flexa-block' ) }
				responsive
				value={ rawDevice( attributes.answerPadding, device ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { answerPadding: { ...attributes.answerPadding, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Item-box panel — background of each question/answer box. The per-item border,
 * radius and shadow come from the shared Border / Box Shadow panels.
 */
export const FaqItemBoxPanel = ( { attributes, setAttributes }: FaqPanelProps ): JSX.Element => {
	const { itemBackground } = attributes;

	return (
		<PanelBody title={ __( 'Item Box', 'flexa-block' ) } initialOpen={ false }>
			<ColorGradientControl
				label={ __( 'Background', 'flexa-block' ) }
				type={ attributes.itemBackgroundType || 'color' }
				color={ itemBackground || {} }
				gradient={ attributes.itemBackgroundGradient || {} }
				onTypeChange={ ( v ) => setAttributes( { itemBackgroundType: v } ) }
				onColorChange={ ( v ) => setAttributes( { itemBackground: v } ) }
				onGradientChange={ ( v ) => setAttributes( { itemBackgroundGradient: v } ) }
			/>
		</PanelBody>
	);
};
