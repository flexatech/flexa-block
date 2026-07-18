/**
 * Product Image block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) come from @components; this file covers the parts unique to the
 * product-image block: the thumbnail gallery layout and the featured-image
 * appearance. There is no user-entered content — the images are read from the
 * current product on the front end. Following the "prefer theme styles" rule
 * nothing is styled by default; only values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, RangeControl } from '@wordpress/components';

import { Segmented, SliderUnit, useDevice, CONTENT_ALIGN_OPTIONS } from '@components';
import { patchDevice, LENGTH_UNITS } from '@utils';
import type { LengthValue, PanelProps, ProductImageAttributes } from '../../types';

type PImgPanelProps = PanelProps< ProductImageAttributes >;

/** Where the thumbnail strip sits relative to the featured image. */
const POSITION_OPTIONS = [
	{ value: 'bottom', label: __( 'Bottom', 'flexa-block' ) },
	{ value: 'left', label: __( 'Left', 'flexa-block' ) },
	{ value: 'right', label: __( 'Right', 'flexa-block' ) },
];

/** object-fit choices for the featured image. */
const SCALE_OPTIONS = [
	{ value: 'none', label: __( 'None', 'flexa-block' ) },
	{ value: 'cover', label: __( 'Cover', 'flexa-block' ) },
	{ value: 'contain', label: __( 'Contain', 'flexa-block' ) },
	{ value: 'fill', label: __( 'Fill', 'flexa-block' ) },
	{ value: 'scale-down', label: __( 'Scale down', 'flexa-block' ) },
];

/**
 * Gallery panel — thumbnail strip position, whether to show it, its per-device
 * columns and gap, and the overall content alignment.
 */
export const ProductImageGalleryPanel = ( { attributes, setAttributes }: PImgPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { galleryPosition, showThumbnails, thumbnailsPerView, thumbnailGap, autoplay, autoplaySpeed, alignment } = attributes;

	const align = alignment?.[ device ] || '';

	return (
		<PanelBody title={ __( 'Gallery', 'flexa-block' ) } initialOpen={ true }>
			<div className="flexa-field">
				<Segmented
					label={ __( 'Thumbnails', 'flexa-block' ) }
					value={ galleryPosition || 'bottom' }
					onChange={ ( v ) => setAttributes( { galleryPosition: v as ProductImageAttributes[ 'galleryPosition' ] } ) }
					options={ POSITION_OPTIONS }
				/>
			</div>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show thumbnails', 'flexa-block' ) }
				checked={ showThumbnails !== false }
				onChange={ ( v: boolean ) => setAttributes( { showThumbnails: v } ) }
			/>

			{ showThumbnails !== false && (
				<>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Thumbnails per view', 'flexa-block' ) }
						help={ __( 'How many thumbnails show at once; the rest scroll in a carousel.', 'flexa-block' ) }
						value={ thumbnailsPerView ?? 4 }
						min={ 1 }
						max={ 8 }
						step={ 1 }
						onChange={ ( v?: number ) => setAttributes( { thumbnailsPerView: v ?? 4 } ) }
					/>
					<SliderUnit
						label={ __( 'Thumbnail gap', 'flexa-block' ) }
						value={ thumbnailGap?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						max={ { px: 60, em: 6, rem: 6, '%': 100, vw: 50, vh: 50 } }
						onChange={ ( v: LengthValue ) => setAttributes( { thumbnailGap: patchDevice( thumbnailGap, device, v ) } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Autoplay', 'flexa-block' ) }
						help={ __( 'Automatically cycle the featured image through the gallery.', 'flexa-block' ) }
						checked={ !! autoplay }
						onChange={ ( v: boolean ) => setAttributes( { autoplay: v } ) }
					/>
					{ autoplay && (
						<RangeControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Autoplay speed (seconds)', 'flexa-block' ) }
							value={ autoplaySpeed ?? 4 }
							min={ 1 }
							max={ 15 }
							step={ 1 }
							onChange={ ( v?: number ) => setAttributes( { autoplaySpeed: v ?? 4 } ) }
						/>
					) }
				</>
			) }

			<div className="flexa-field">
				<Segmented
					label={ __( 'Alignment', 'flexa-block' ) }
					value={ align }
					responsive
					onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
					options={ CONTENT_ALIGN_OPTIONS }
				/>
			</div>
		</PanelBody>
	);
};

/**
 * Featured Image panel — adaptive vs fixed height (with image scale), zoom on
 * hover and the corner radius.
 */
export const ProductImageFeaturedPanel = ( { attributes, setAttributes }: PImgPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { imageHeight, imageScale, adaptiveHeight, zoomOnHover, imageRadius } = attributes;

	return (
		<PanelBody title={ __( 'Featured Image', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Adaptive height', 'flexa-block' ) }
				help={ __( 'Let the image keep its natural aspect ratio.', 'flexa-block' ) }
				checked={ adaptiveHeight !== false }
				onChange={ ( v: boolean ) => setAttributes( { adaptiveHeight: v } ) }
			/>

			{ adaptiveHeight === false && (
				<>
					<SliderUnit
						label={ __( 'Height', 'flexa-block' ) }
						value={ imageHeight?.[ device ] || {} }
						units={ LENGTH_UNITS }
						defaultUnit="px"
						max={ { px: 900, em: 60, rem: 60, '%': 100, vw: 100, vh: 100 } }
						onChange={ ( v: LengthValue ) => setAttributes( { imageHeight: patchDevice( imageHeight, device, v ) } ) }
					/>
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Image scale', 'flexa-block' ) }
						value={ imageScale || 'cover' }
						options={ SCALE_OPTIONS }
						onChange={ ( v: string ) => setAttributes( { imageScale: v as ProductImageAttributes[ 'imageScale' ] } ) }
					/>
				</>
			) }

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Zoom on hover', 'flexa-block' ) }
				checked={ !! zoomOnHover }
				onChange={ ( v: boolean ) => setAttributes( { zoomOnHover: v } ) }
			/>

			<SliderUnit
				label={ __( 'Corner radius', 'flexa-block' ) }
				value={ imageRadius?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 10, rem: 10, '%': 50, vw: 50, vh: 50 } }
				onChange={ ( v: LengthValue ) => setAttributes( { imageRadius: patchDevice( imageRadius, device, v ) } ) }
			/>
		</PanelBody>
	);
};
