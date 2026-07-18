/**
 * Product Image block — editor component.
 *
 * Assembles the product-image panels (gallery layout + featured image) with the
 * shared inspector panels (@components) for spacing / background / border /
 * shadow / position / visibility. There is no product in the editor, so the
 * canvas renders a placeholder featured image with a thumbnail strip, mirroring
 * the PHP CSS generator inline; nothing is styled by default so the images
 * inherit sensible base styles until the user picks a value. The thumbnail
 * position (bottom / left / right) and zoom-on-hover are handled by modifier
 * classes + style.scss.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
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
	useBlockId,
	useDevice,
	ExamplePreviewSkeleton,
} from '@components';
import {
	cn,
	visibilityClasses,
	effective,
	rawDevice,
	withUnit,
	spacingShorthand,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
} from '@utils';
import type { CssProps } from '@utils';
import { ProductImageGalleryPanel, ProductImageFeaturedPanel } from './panels';
import type { DeviceKey, EditProps, ProductImageAttributes } from '../../types';

/** A neutral grey placeholder, since there is no product in the editor. */
const PLACEHOLDER =
	'data:image/svg+xml;charset=utf-8,' +
	encodeURIComponent(
		'<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800"><rect width="100%" height="100%" fill="#e5e7eb"/></svg>'
	);

const THUMB_COUNT = 6;

/** Wrapper preview: alignment, spacing, background, border, shadow, overflow, and
 * the carousel CSS vars (thumbnails-per-view + gap) that style.scss reads. */
const buildWrapperStyle = ( attributes: ProductImageAttributes, device: DeviceKey ): CssProps => {
	const { alignment, spacing, background, border, boxShadow, advancedLayout, thumbnailsPerView, thumbnailGap } = attributes;
	const sp = effective( spacing, device );
	const adv = effective( advancedLayout, device );
	const s: CssProps = {};

	const align = alignment?.[ device ] || '';
	if ( align ) s.textAlign = align;

	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, background );
	applyBorderPreview( s, effective( border, device ) );

	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	if ( adv.overflow ) s.overflow = adv.overflow;

	// Carousel sizing vars (mirror the PHP generator).
	const vars = s as Record< string, string | number >;
	vars[ '--flexa-thumb-pv' ] = thumbnailsPerView ?? 4;
	const gap = rawDevice( thumbnailGap, device );
	if ( gap.value ) vars[ '--flexa-thumb-gap' ] = withUnit( gap.value, gap.unit || 'px' );

	return s;
};

/** Featured image container preview: a fixed height (when not adaptive) so the box
 * keeps its size when a different image is swapped in. */
const buildMainStyle = ( attributes: ProductImageAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	if ( attributes.adaptiveHeight === false ) {
		const h = rawDevice( attributes.imageHeight, device );
		if ( h.value ) s.height = withUnit( h.value, h.unit || 'px' );
	}
	return s;
};

/** Featured image preview: fixed height + scale (when not adaptive) + radius. */
const buildMainImgStyle = ( attributes: ProductImageAttributes, device: DeviceKey ): CssProps => {
	const { imageHeight, imageScale, adaptiveHeight, imageRadius } = attributes;
	const s: CssProps = {};

	if ( adaptiveHeight === false ) {
		const h = rawDevice( imageHeight, device );
		if ( h.value ) s.height = withUnit( h.value, h.unit || 'px' );
		if ( imageScale ) s.objectFit = imageScale;
	}
	const radius = rawDevice( imageRadius, device );
	if ( radius.value ) s.borderRadius = withUnit( radius.value, radius.unit || 'px' );

	return s;
};

/** Thumbnail image preview: shares the featured image's corner radius. */
const buildThumbImgStyle = ( attributes: ProductImageAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const radius = rawDevice( attributes.imageRadius, device );
	if ( radius.value ) s.borderRadius = withUnit( radius.value, radius.unit || 'px' );
	return s;
};

/**
 * Product Image edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ProductImageAttributes > ): JSX.Element {
	const { galleryPosition = 'bottom', showThumbnails, zoomOnHover, className, responsiveVisibility } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, attributes.blockId, setAttributes );

	const blockId = attributes.blockId;
	const showThumbs = showThumbnails !== false;

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-product-image',
			`flexa-product-image--pos-${ galleryPosition }`,
			zoomOnHover && 'flexa-product-image--zoom',
			blockId && `flexa-product-image-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real image.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="image" />
			</div>
		);
	}

	const mainStyle = buildMainStyle( attributes, device );
	const mainImgStyle = buildMainImgStyle( attributes, device );
	const thumbImgStyle = buildThumbImgStyle( attributes, device );
	const perView = attributes.thumbnailsPerView ?? 4;
	const showNav = THUMB_COUNT > perView;

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-product-image-inspector">
					<InspectorTabs
						layout={
							<>
								<ProductImageGalleryPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductImageFeaturedPanel attributes={ attributes } setAttributes={ setAttributes } />
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

			<div { ...blockProps }>
				<div className="flexa-product-image__main" style={ mainStyle }>
					<img src={ PLACEHOLDER } alt="" style={ mainImgStyle } />
				</div>
				{ showThumbs && (
					<div className="flexa-product-image__thumbs-wrap">
						{ showNav && (
							<button type="button" className="flexa-product-image__nav flexa-product-image__nav--prev" aria-label={ __( 'Previous', 'flexa-block' ) }>
								‹
							</button>
						) }
						<ul className="flexa-product-image__thumbs">
							{ Array.from( { length: THUMB_COUNT } ).map( ( _, i ) => (
								<li key={ i } className={ cn( 'flexa-product-image__thumb', i === 0 && 'is-active' ) }>
									<img src={ PLACEHOLDER } alt="" style={ thumbImgStyle } />
								</li>
							) ) }
						</ul>
						{ showNav && (
							<button type="button" className="flexa-product-image__nav flexa-product-image__nav--next" aria-label={ __( 'Next', 'flexa-block' ) }>
								›
							</button>
						) }
					</div>
				) }
			</div>
		</>
	);
}
