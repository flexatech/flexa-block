/**
 * Banner block — banner-specific inspector panels.
 *
 * The shared promo panels (content / heading / description / buttons) and the
 * foundational panels (spacing / background / border / shadow / position /
 * visibility) come from @components; these cover the parts unique to a banner:
 * the section width + min-height + vertical placement, and the colour/gradient
 * overlay laid over the background image.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, RangeControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	DualColor,
	GradientControl,
	CONTAINER_WIDTH_OPTIONS,
	BLEND_MODE_OPTIONS,
	useDevice,
} from '@components';
import { HTML_TAGS, WIDTH_UNITS, HEIGHT_UNITS } from '@utils';
import type { BannerAttributes, ImageOverlayAttr, LengthValue, PanelProps } from '../../types';

type BannerPanelProps = PanelProps< BannerAttributes >;

/** Vertical placement of the content within the banner's min-height. */
const VERTICAL_ALIGN_OPTIONS = [
	{ value: 'flex-start', label: __( 'Top', 'flexa-block' ) },
	{ value: 'center', label: __( 'Middle', 'flexa-block' ) },
	{ value: 'flex-end', label: __( 'Bottom', 'flexa-block' ) },
];

/**
 * Where the banner's content comes from. `fields` is the default so every banner
 * saved before this option existed keeps behaving exactly as it did.
 */
const CONTENT_SOURCE_OPTIONS = [
	{ value: 'fields', label: __( 'Fields', 'flexa-block' ) },
	{ value: 'custom', label: __( 'Any blocks', 'flexa-block' ) },
];

/**
 * Layout panel — section width, min-height, vertical content placement and tag.
 */
export const BannerLayoutPanel = ( { attributes, setAttributes }: BannerPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { contentSource, containerType, widthBoxed, widthFullWidth, contentBoxWidth, size, verticalAlign, htmlTag } = attributes;
	const isBoxed = containerType !== 'full-width';
	const isCustom = 'custom' === ( contentSource || 'fields' );

	const widthGroup: 'widthBoxed' | 'widthFullWidth' = isBoxed ? 'widthBoxed' : 'widthFullWidth';
	const widthVal = ( isBoxed ? widthBoxed : widthFullWidth )?.[ device ] || {};
	const boxVal = contentBoxWidth?.[ device ] || {};
	const minH = size?.[ device ]?.minHeight || {};

	const setWidth = ( val: LengthValue ) =>
		setAttributes( { [ widthGroup ]: { ...attributes[ widthGroup ], [ device ]: { value: val.value ?? '', unit: val.unit || ( isBoxed ? 'px' : '%' ) } } } );
	const setContentBoxWidth = ( val: LengthValue ) =>
		setAttributes( { contentBoxWidth: { ...contentBoxWidth, [ device ]: { value: val.value ?? '', unit: val.unit || 'px' } } } );
	const setMinHeight = ( val: LengthValue ) =>
		setAttributes( { size: { ...size, [ device ]: { minHeight: { value: val.value ?? '', unit: val.unit || 'px' } } } } );

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Content', 'flexa-block' ) }
				value={ contentSource || 'fields' }
				onChange={ ( v ) => setAttributes( { contentSource: v as BannerAttributes[ 'contentSource' ] } ) }
				options={ CONTENT_SOURCE_OPTIONS }
			/>
			{ /* Segmented takes no `help`, so the explanation uses WordPress's own
			     help class rather than inventing one — it inherits the same small,
			     muted styling every other control's help text has. Saying the fields
			     are KEPT matters: switching mode looks like losing them otherwise. */ }
			<p className="components-base-control__help">
				{ isCustom
					? __(
							'Build the banner from any blocks. The heading, description and buttons are kept — switch back to Fields to use them again.',
							'flexa-block'
					  )
					: __(
							'Use the built-in heading, description and buttons. Choose “Any blocks” to build the banner from blocks instead.',
							'flexa-block'
					  ) }
			</p>
			<Segmented
				label={ __( 'Section Width', 'flexa-block' ) }
				value={ containerType || 'full-width' }
				onChange={ ( v ) => setAttributes( { containerType: v as BannerAttributes[ 'containerType' ] } ) }
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
				label={ __( 'Content Box Width', 'flexa-block' ) }
				value={ boxVal }
				units={ WIDTH_UNITS }
				defaultUnit="px"
				max={ { px: 3000, '%': 100, vw: 100, rem: 100 } }
				onChange={ setContentBoxWidth }
			/>
			<SliderUnit
				label={ __( 'Min Height', 'flexa-block' ) }
				value={ minH }
				units={ HEIGHT_UNITS }
				defaultUnit="px"
				max={ { px: 1200, vh: 100, '%': 100, rem: 100 } }
				onChange={ setMinHeight }
			/>
			<Segmented
				label={ __( 'Vertical Align', 'flexa-block' ) }
				value={ verticalAlign || 'center' }
				onChange={ ( v ) => setAttributes( { verticalAlign: v } ) }
				options={ VERTICAL_ALIGN_OPTIONS }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'section' }
				options={ HTML_TAGS.map( ( t ) => ( { label: t, value: t } ) ) }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Overlay panel — a colour or gradient laid over the background image, with an
 * opacity and a blend mode.
 */
export const BannerOverlayPanel = ( { attributes, setAttributes }: BannerPanelProps ): JSX.Element => {
	const overlay: ImageOverlayAttr = attributes.overlay || {};
	const set = ( patch: Partial< ImageOverlayAttr > ) => setAttributes( { overlay: { ...overlay, ...patch } } );

	return (
		<PanelBody title={ __( 'Overlay', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Type', 'flexa-block' ) }
				value={ overlay.type || 'none' }
				onChange={ ( v ) => set( { type: v as ImageOverlayAttr[ 'type' ] } ) }
				options={ [
					{ value: 'none', label: __( 'None', 'flexa-block' ) },
					{ value: 'color', label: __( 'Color', 'flexa-block' ) },
					{ value: 'gradient', label: __( 'Gradient', 'flexa-block' ) },
				] }
			/>
			{ overlay.type === 'color' && (
				<DualColor label={ __( 'Color', 'flexa-block' ) } value={ overlay.color || {} } onChange={ ( v ) => set( { color: v } ) } />
			) }
			{ overlay.type === 'gradient' && (
				<GradientControl label={ __( 'Gradient', 'flexa-block' ) } value={ overlay.gradient || {} } onChange={ ( v ) => set( { gradient: v } ) } />
			) }
			{ overlay.type && overlay.type !== 'none' && (
				<>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Opacity', 'flexa-block' ) }
						value={ typeof overlay.opacity === 'number' ? overlay.opacity : 50 }
						min={ 0 }
						max={ 100 }
						onChange={ ( v?: number ) => set( { opacity: v ?? 50 } ) }
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Blend Mode', 'flexa-block' ) }
						value={ overlay.blendMode || '' }
						options={ BLEND_MODE_OPTIONS }
						onChange={ ( v: string ) => set( { blendMode: v } ) }
					/>
				</>
			) }
		</PanelBody>
	);
};
