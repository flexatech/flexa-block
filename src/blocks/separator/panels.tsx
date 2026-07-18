/**
 * Separator block — separator-specific inspector panels.
 *
 * The shared panels (spacing / visibility) plus the length/colour controls come
 * from @components; this file covers the parts unique to the separator: the line
 * geometry (alignment, width, thickness) in the Layout tab, its style and colour
 * in the Style tab, and the wrapper HTML tag in Advanced. Following the "prefer
 * theme styles" rule the generator emits nothing until the user picks a value —
 * the base line inherits the theme's text colour.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl } from '@wordpress/components';

import { Segmented, SliderUnit, DualColor, CONTENT_ALIGN_OPTIONS, useDevice } from '@components';
import {
	rawDevice,
	LENGTH_UNITS,
	WEIGHT_UNITS,
	LINE_STYLE_OPTIONS,
	HTML_TAGS,
} from '@utils';
import type { LengthValue, PanelProps, SeparatorAttributes } from '../../types';

type SeparatorPanelProps = PanelProps< SeparatorAttributes >;

/**
 * Layout panel — alignment, width and thickness of the line (per device).
 */
export const SeparatorPanel = ( { attributes, setAttributes }: SeparatorPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { alignment, width, weight } = attributes;

	const align = alignment?.[ device ] || '';
	const widthVal = rawDevice( width, device ) as LengthValue;
	const weightVal = rawDevice( weight, device ) as LengthValue;

	return (
		<PanelBody title={ __( 'Separator', 'flexa-block' ) } initialOpen={ true }>
			<div className="flexa-field">
				<Segmented
					label={ __( 'Alignment', 'flexa-block' ) }
					value={ align }
					responsive
					onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
					options={ CONTENT_ALIGN_OPTIONS }
				/>
			</div>
			<SliderUnit
				label={ __( 'Width', 'flexa-block' ) }
				value={ widthVal }
				units={ LENGTH_UNITS }
				defaultUnit="%"
				min={ 1 }
				max={ { px: 2000, '%': 100, em: 100, rem: 100, vw: 100, vh: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { width: { ...width, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Thickness', 'flexa-block' ) }
				value={ weightVal }
				units={ WEIGHT_UNITS }
				defaultUnit="px"
				min={ 1 }
				max={ { px: 20 } }
				onChange={ ( v: LengthValue ) => setAttributes( { weight: { ...weight, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Style panel — the line's style (solid / dashed / …) and its light/dark colour.
 */
export const SeparatorStylePanel = ( { attributes, setAttributes }: SeparatorPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Line', 'flexa-block' ) } initialOpen={ true }>
		<SelectControl
			__nextHasNoMarginBottom
			label={ __( 'Style', 'flexa-block' ) }
			value={ attributes.lineStyle || 'solid' }
			options={ LINE_STYLE_OPTIONS }
			onChange={ ( v: string ) => setAttributes( { lineStyle: v } ) }
		/>
		<DualColor
			label={ __( 'Color', 'flexa-block' ) }
			value={ attributes.color || {} }
			onChange={ ( v ) => setAttributes( { color: v } ) }
		/>
	</PanelBody>
);

/**
 * Advanced panel — the wrapper's HTML tag.
 */
export const SeparatorAdvancedPanel = ( { attributes, setAttributes }: SeparatorPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'HTML Tag', 'flexa-block' ) } initialOpen={ false }>
		<SelectControl
			__nextHasNoMarginBottom
			label={ __( 'HTML Tag', 'flexa-block' ) }
			value={ attributes.htmlTag || 'div' }
			options={ HTML_TAGS.map( ( t ) => ( { label: t, value: t } ) ) }
			onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
		/>
	</PanelBody>
);
