/**
 * CTA block — CTA-specific inspector panels.
 *
 * The shared promo panels (content / heading / description / buttons) and the
 * foundational panels come from @components; this covers the parts unique to a
 * CTA: the centred-vs-split layout, the section width and min-height, and the tag.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl } from '@wordpress/components';

import { Segmented, SliderUnit, CONTAINER_WIDTH_OPTIONS, useDevice } from '@components';
import { HTML_TAGS, WIDTH_UNITS, HEIGHT_UNITS } from '@utils';
import type { CtaAttributes, LengthValue, PanelProps } from '../../types';

type CtaPanelProps = PanelProps< CtaAttributes >;

/** Centred (stacked) vs split (text one side, buttons the other). */
const ARRANGEMENT_OPTIONS = [
	{ value: 'centered', label: __( 'Centered', 'flexa-block' ) },
	{ value: 'split', label: __( 'Split', 'flexa-block' ) },
];

/**
 * Layout panel — arrangement, section width, min-height and wrapper tag.
 */
export const CtaLayoutPanel = ( { attributes, setAttributes }: CtaPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { arrangement, containerType, widthBoxed, widthFullWidth, size, htmlTag } = attributes;
	const isBoxed = containerType !== 'full-width';

	const widthGroup: 'widthBoxed' | 'widthFullWidth' = isBoxed ? 'widthBoxed' : 'widthFullWidth';
	const widthVal = ( isBoxed ? widthBoxed : widthFullWidth )?.[ device ] || {};
	const minH = size?.[ device ]?.minHeight || {};

	const setWidth = ( val: LengthValue ) =>
		setAttributes( { [ widthGroup ]: { ...attributes[ widthGroup ], [ device ]: { value: val.value ?? '', unit: val.unit || ( isBoxed ? 'px' : '%' ) } } } );
	const setMinHeight = ( val: LengthValue ) =>
		setAttributes( { size: { ...size, [ device ]: { minHeight: { value: val.value ?? '', unit: val.unit || 'px' } } } } );

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Arrangement', 'flexa-block' ) }
				value={ arrangement || 'centered' }
				onChange={ ( v ) => setAttributes( { arrangement: v as CtaAttributes[ 'arrangement' ] } ) }
				options={ ARRANGEMENT_OPTIONS }
			/>
			<Segmented
				label={ __( 'Section Width', 'flexa-block' ) }
				value={ containerType || 'boxed' }
				onChange={ ( v ) => setAttributes( { containerType: v as CtaAttributes[ 'containerType' ] } ) }
				options={ CONTAINER_WIDTH_OPTIONS }
			/>
			<SliderUnit
				label={ isBoxed ? __( 'Max Width', 'flexa-block' ) : __( 'Width', 'flexa-block' ) }
				value={ widthVal }
				units={ WIDTH_UNITS }
				defaultUnit={ isBoxed ? 'px' : '%' }
				max={ { px: 3000, '%': 100, vw: 100, rem: 100 } }
				onChange={ setWidth }
			/>
			<SliderUnit
				label={ __( 'Min Height', 'flexa-block' ) }
				value={ minH }
				units={ HEIGHT_UNITS }
				defaultUnit="px"
				max={ { px: 1000, vh: 100, '%': 100, rem: 100 } }
				onChange={ setMinHeight }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'section' }
				options={ HTML_TAGS.map( ( t ) => ( { label: t, value: t } ) ) }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>
		</PanelBody>
	);
};
