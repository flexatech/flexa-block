/**
 * Google Map block — editor component.
 *
 * Assembles the map-specific panels (./panels) with the shared inspector panels
 * (@components) for spacing / background / border / shadow / position /
 * visibility. The canvas renders the live embed with an inline preview that
 * mirrors the PHP CSS generator; nothing is styled by default so the map keeps
 * sensible base styling until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { Placeholder } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

import {
	InspectorTabs,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	PositionPanel,
	VisibilityPanel,
	AnimationPanel,
	useDevice,
} from '@components';
import { cn, effective, rawDevice, withUnit, spacingShorthand, applyBackgroundPreview, applyBorderPreview, boxShadowPreview, visibilityClasses } from '@utils';
import { MapPanel, MapLayoutPanel } from './panels';
import type { DeviceKey, EditProps, GoogleMapAttributes } from '../../types';

type CssProps = Record< string, string >;

/** Build the Google Maps embed URL for the current location / zoom / mode. */
const buildEmbedUrl = ( location: string, zoom: number, apiEnabled: boolean, apiKey: string ): string => {
	const q = encodeURIComponent( location );
	const z = Math.max( 1, Math.min( 20, Math.round( zoom || 12 ) ) );
	if ( apiEnabled && apiKey ) {
		return `https://www.google.com/maps/embed/v1/place?key=${ encodeURIComponent( apiKey ) }&q=${ q }&zoom=${ z }`;
	}
	return `https://maps.google.com/maps?q=${ q }&z=${ z }&output=embed`;
};

/** Outer wrapper preview: spacing, width, background, overflow. */
const buildWrapperStyle = ( attributes: GoogleMapAttributes, device: DeviceKey ): CssProps => {
	const { spacing, containerType, widthBoxed, widthFullWidth, background, advancedLayout } = attributes;
	const sp = effective( spacing, device );
	const adv = effective( advancedLayout, device );
	const isBoxed = containerType !== 'full-width';
	const w = rawDevice( isBoxed ? widthBoxed : widthFullWidth, device );
	const s: CssProps = {};

	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	if ( w.value ) {
		const wu = w.unit || ( isBoxed ? 'px' : '%' );
		s[ isBoxed ? 'maxWidth' : 'width' ] = withUnit( w.value, wu );
	}

	applyBackgroundPreview( s, background );
	if ( adv.overflow ) s.overflow = adv.overflow;

	return s;
};

/** Frame preview: height, border (+radius), box shadow. */
const buildFrameStyle = ( attributes: GoogleMapAttributes, device: DeviceKey ): CssProps => {
	const { height, border, boxShadow } = attributes;
	const h = rawDevice( height, device );
	const b = effective( border, device );
	const s: CssProps = {};

	if ( h.value ) s.height = withUnit( h.value, h.unit || 'px' );

	applyBorderPreview( s, b );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	return s;
};

/**
 * Google Map edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< GoogleMapAttributes > ): JSX.Element {
	const { location, zoom, apiKey, containerType, className, responsiveVisibility } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useEffect( () => {
		const expected = clientId.replace( /[^a-z0-9]/gi, '' ).slice( 0, 8 );
		if ( ! blockId || blockId !== expected ) {
			setAttributes( { blockId: expected } );
		}
	}, [ blockId, clientId, setAttributes ] );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-google-map',
			`flexa-google-map--${ containerType || 'boxed' }`,
			blockId && `flexa-google-map-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	const inspector = (
		<InspectorControls>
			<div className="flexa-inspector flexa-google-map-inspector">
				<InspectorTabs
					layout={
						<>
							<MapPanel attributes={ attributes } setAttributes={ setAttributes } />
							<MapLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
							<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
						</>
					}
					style={
						<>
							<BackgroundPanel attributes={ attributes } setAttributes={ setAttributes } />
							<BorderPanel attributes={ attributes } setAttributes={ setAttributes } />
							<ShadowPanel attributes={ attributes } setAttributes={ setAttributes } />
						</>
					}
					advanced={
						<>
							<PositionPanel attributes={ attributes } setAttributes={ setAttributes } />
							<VisibilityPanel attributes={ attributes } setAttributes={ setAttributes } />
							<AnimationPanel attributes={ attributes } setAttributes={ setAttributes } />
						</>
					}
				/>
			</div>
		</InspectorControls>
	);

	const frameStyle = buildFrameStyle( attributes, device );

	// No location yet → prompt instead of an empty grey box.
	if ( ! location ) {
		return (
			<>
				{ inspector }
				<div { ...blockProps }>
					<Placeholder
						icon="location-alt"
						label={ __( 'Google Map', 'flexa-block' ) }
						instructions={ __( 'Enter an address or place name in the Map panel to show the map.', 'flexa-block' ) }
					/>
				</div>
			</>
		);
	}

	const embedUrl = buildEmbedUrl( location, zoom ?? 12, !! apiKey?.enabled, apiKey?.key || '' );

	return (
		<>
			{ inspector }
			<div { ...blockProps }>
				<div className="flexa-google-map__frame" style={ frameStyle }>
					<iframe
						className="flexa-google-map__iframe"
						title={ __( 'Google Map', 'flexa-block' ) }
						src={ embedUrl }
						loading="lazy"
						referrerPolicy="no-referrer-when-downgrade"
						allowFullScreen
					/>
					{ /* Editor-only shield so clicks select the block instead of panning the map. */ }
					<span className="flexa-google-map__shield" aria-hidden="true" />
				</div>
			</div>
		</>
	);
}
