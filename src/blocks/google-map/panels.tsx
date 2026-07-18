/**
 * Google Map block — block-specific inspector panels.
 *
 * `MapPanel` owns the map itself (location, zoom, per-device height and the
 * free-embed vs. API-key mode). `MapLayoutPanel` owns the outer box width
 * (boxed vs. full-width) and the wrapper tag. Everything else (spacing,
 * background, border, shadow, position, visibility) comes from the shared
 * panels in `@components`, so only the map-specific attributes live here.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, TextareaControl, TextControl, RangeControl, SelectControl } from '@wordpress/components';

import { Segmented, SliderUnit, useDevice, CONTAINER_WIDTH_OPTIONS } from '@components';
import { HTML_TAGS, WIDTH_UNITS, HEIGHT_UNITS } from '@utils';
import type { GoogleMapAttributes, LengthValue, PanelProps } from '../../types';

/** Free-embed (no key) vs. Google Maps Embed API (with key). */
const EMBED_MODE_OPTIONS = [
	{ value: 'free', label: __( 'Free', 'flexa-block' ) },
	{ value: 'api', label: __( 'API Key', 'flexa-block' ) },
];

/**
 * Map panel — location, zoom, height and embed mode.
 */
export const MapPanel = ( { attributes, setAttributes }: PanelProps< GoogleMapAttributes > ): JSX.Element => {
	const [ device ] = useDevice();
	const { location, zoom, height, apiKey } = attributes;
	const apiEnabled = !! apiKey?.enabled;

	const setHeight = ( val: LengthValue ) => {
		setAttributes( { height: { ...height, [ device ]: { value: val.value ?? '', unit: val.unit || 'px' } } } );
	};

	return (
		<PanelBody title={ __( 'Map', 'flexa-block' ) } initialOpen={ true }>
			<TextareaControl
				__nextHasNoMarginBottom
				label={ __( 'Location', 'flexa-block' ) }
				help={ __( 'Address, place name or "latitude,longitude".', 'flexa-block' ) }
				value={ location || '' }
				rows={ 2 }
				onChange={ ( v: string ) => setAttributes( { location: v } ) }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Zoom', 'flexa-block' ) }
				value={ zoom ?? 12 }
				min={ 1 }
				max={ 20 }
				step={ 1 }
				onChange={ ( v?: number ) => setAttributes( { zoom: v ?? 12 } ) }
			/>
			<SliderUnit
				label={ __( 'Height', 'flexa-block' ) }
				value={ height?.[ device ] || {} }
				units={ HEIGHT_UNITS }
				defaultUnit="px"
				max={ { px: 1000, vh: 100, '%': 100, rem: 100 } }
				onChange={ setHeight }
			/>
			<Segmented
				label={ __( 'Embed Mode', 'flexa-block' ) }
				value={ apiEnabled ? 'api' : 'free' }
				onChange={ ( v: string ) => setAttributes( { apiKey: { ...apiKey, enabled: v === 'api' } } ) }
				options={ EMBED_MODE_OPTIONS }
			/>
			{ apiEnabled && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Google Maps API Key', 'flexa-block' ) }
					help={ __( 'Enables the official Embed API (satellite views, exact pins).', 'flexa-block' ) }
					value={ apiKey?.key || '' }
					onChange={ ( v: string ) => setAttributes( { apiKey: { ...apiKey, key: v } } ) }
				/>
			) }
		</PanelBody>
	);
};

/**
 * Layout panel — content width (boxed vs full-width) and the wrapper tag.
 */
export const MapLayoutPanel = ( { attributes, setAttributes }: PanelProps< GoogleMapAttributes > ): JSX.Element => {
	const [ device ] = useDevice();
	const { containerType, htmlTag, widthBoxed, widthFullWidth } = attributes;
	const isBoxed = containerType !== 'full-width';

	const widthGroup: 'widthBoxed' | 'widthFullWidth' = isBoxed ? 'widthBoxed' : 'widthFullWidth';
	const widthVal = ( isBoxed ? widthBoxed : widthFullWidth )?.[ device ] || {};

	const setWidth = ( val: LengthValue ) => {
		setAttributes( {
			[ widthGroup ]: {
				...attributes[ widthGroup ],
				[ device ]: { value: val.value ?? '', unit: val.unit || ( isBoxed ? 'px' : '%' ) },
			},
		} as Partial< GoogleMapAttributes > );
	};

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Content Width', 'flexa-block' ) }
				value={ containerType || 'boxed' }
				onChange={ ( v: string ) => setAttributes( { containerType: v as GoogleMapAttributes[ 'containerType' ] } ) }
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
