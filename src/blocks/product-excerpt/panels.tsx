/**
 * Product Excerpt block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility / animation) come from @components; these cover the parts unique to
 * a product excerpt: which description field to read, how far to cut it back,
 * and the styling of the body copy and the links inside it. Following the
 * "prefer theme styles" rule nothing is styled by default — only the values the
 * user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, RangeControl, SelectControl, ToggleControl } from '@wordpress/components';

import { Segmented, DualColor, TypographyControls, CONTENT_ALIGN_OPTIONS, useDevice } from '@components';
import { rawDevice, patchDevice } from '@utils';
import type { ControlOption, PanelProps, ProductExcerptAttributes, TypographyDevice } from '../../types';

type PePanelProps = PanelProps< ProductExcerptAttributes >;

/** Where the excerpt comes from when the short description is empty. */
const SOURCE_OPTIONS: ControlOption[] = [
	{ value: 'auto', label: __( 'Short, then long description', 'flexa-block' ) },
	{ value: 'short', label: __( 'Short description only', 'flexa-block' ) },
];

/**
 * Settings panel — the source, the word cap, alignment and the line clamp.
 */
export const ProductExcerptSettingsPanel = ( { attributes, setAttributes }: PePanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { alignment, source, wordLimit, enableClamp, clampLines } = attributes;

	return (
		<PanelBody title={ __( 'Settings', 'flexa-block' ) } initialOpen={ true }>
			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Source', 'flexa-block' ) }
				value={ source || 'auto' }
				options={ SOURCE_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { source: v as ProductExcerptAttributes[ 'source' ] } ) }
			/>

			<RangeControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Word limit', 'flexa-block' ) }
				help={ __( 'Zero keeps the whole text. Above zero the excerpt is cut to that many words, without formatting.', 'flexa-block' ) }
				value={ typeof wordLimit === 'number' ? wordLimit : 0 }
				min={ 0 }
				max={ 200 }
				onChange={ ( v?: number ) => setAttributes( { wordLimit: v ?? 0 } ) }
			/>

			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || '' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Clamp to a number of lines', 'flexa-block' ) }
				help={ __( 'Cut the excerpt off after a set number of lines.', 'flexa-block' ) }
				checked={ !! enableClamp }
				onChange={ ( v: boolean ) => setAttributes( { enableClamp: v } ) }
			/>
			{ enableClamp && (
				<RangeControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Lines', 'flexa-block' ) }
					value={ typeof clampLines === 'number' ? clampLines : 3 }
					min={ 1 }
					max={ 20 }
					onChange={ ( v?: number ) => setAttributes( { clampLines: v ?? 3 } ) }
				/>
			) }
		</PanelBody>
	);
};

/**
 * Content panel — the excerpt's typography and its text / link colours.
 */
export const ProductExcerptContentPanel = ( { attributes, setAttributes }: PePanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.typography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { typography: patchDevice( attributes.typography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Content', 'flexa-block' ) } initialOpen={ true }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text color', 'flexa-block' ) } value={ attributes.textColor || {} } onChange={ ( v ) => setAttributes( { textColor: v } ) } />
			<DualColor label={ __( 'Link color', 'flexa-block' ) } value={ attributes.linkColor || {} } onChange={ ( v ) => setAttributes( { linkColor: v } ) } />
			<DualColor
				label={ __( 'Link color (hover)', 'flexa-block' ) }
				value={ attributes.linkColorHover || {} }
				onChange={ ( v ) => setAttributes( { linkColorHover: v } ) }
			/>
		</PanelBody>
	);
};
