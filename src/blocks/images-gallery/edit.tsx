/**
 * Images Gallery block — editor component.
 *
 * Assembles the gallery-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility. The canvas renders the thumbnails with an inline preview that
 * mirrors the PHP CSS generator; nothing is styled by default so the gallery
 * inherits sensible base styles until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, BlockControls, MediaUpload, MediaUploadCheck, MediaPlaceholder, useBlockProps } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';

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
import { cn, effective, rawDevice, withUnit, spacingShorthand, applyTypography, applyBackgroundPreview, applyBorderPreview, boxShadowPreview, editorCss } from '@utils';
import { GalleryImagesPanel, GalleryPanel, GalleryEffectsPanel, GalleryCaptionPanel, GalleryInteractionPanel, toGalleryImages, useAttachmentCaptions, captionFor } from './panels';
import type { MediaItem } from './panels';
import type { DeviceKey, EditProps, ImagesGalleryAttributes } from '../../types';

type CssProps = Record< string, string >;

/** Outer wrapper preview: spacing, background, border, box-shadow, overflow. */
const buildWrapperStyle = ( attributes: ImagesGalleryAttributes, device: DeviceKey ): CssProps => {
	const { spacing, background, border, boxShadow, advancedLayout } = attributes;
	const sp = effective( spacing, device );
	const b = effective( border, device );
	const adv = effective( advancedLayout, device );
	const s: CssProps = {};

	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, background );
	applyBorderPreview( s, b );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	if ( adv.overflow ) s.overflow = adv.overflow;

	return s;
};

/** Items container preview: the grid / masonry / tiled layout + gap. */
const buildItemsStyle = ( attributes: ImagesGalleryAttributes, device: DeviceKey ): CssProps => {
	const { galleryLayout, columns, gap } = attributes;
	const s: CssProps = {};
	const gapVal = rawDevice( gap, device );
	const gapCss = gapVal.value ? withUnit( gapVal.value, gapVal.unit || 'px' ) : '';
	const cols = parseInt( String( rawDevice( columns, device ).value ?? '' ), 10 );

	if ( galleryLayout === 'masonry' ) {
		if ( cols > 0 ) s.columnCount = String( cols );
		if ( gapCss ) s.columnGap = gapCss;
	} else if ( galleryLayout === 'tiled' ) {
		s.display = 'flex';
		s.flexWrap = 'wrap';
		if ( gapCss ) s.gap = gapCss;
	} else {
		s.display = 'grid';
		if ( cols > 0 ) s.gridTemplateColumns = `repeat(${ cols }, minmax(0, 1fr))`;
		if ( gapCss ) s.gap = gapCss;
	}
	return s;
};

/** Per-item preview: masonry break spacing + tiled row height. */
const buildItemStyle = ( attributes: ImagesGalleryAttributes, device: DeviceKey ): CssProps => {
	const { galleryLayout, gap, tiledHeight } = attributes;
	const s: CssProps = {};
	if ( galleryLayout === 'masonry' ) {
		const gapVal = rawDevice( gap, device );
		if ( gapVal.value ) s.marginBottom = withUnit( gapVal.value, gapVal.unit || 'px' );
		s.breakInside = 'avoid';
	} else if ( galleryLayout === 'tiled' ) {
		const h = rawDevice( tiledHeight, device );
		if ( h.value ) s.height = withUnit( h.value, h.unit || 'px' );
		s.flexGrow = '1';
	}
	return s;
};

/** Per-media (figure) preview: image radius + shadow + aspect ratio. */
const buildMediaStyle = ( attributes: ImagesGalleryAttributes, device: DeviceKey ): CssProps => {
	const { galleryLayout, imageRadius, imageShadow, aspectRatio } = attributes;
	const s: CssProps = {};
	const radius = rawDevice( imageRadius, device );
	if ( radius.value ) s.borderRadius = withUnit( radius.value, radius.unit || 'px' );
	const shadow = boxShadowPreview( imageShadow );
	if ( shadow ) s.boxShadow = shadow;
	if ( galleryLayout === 'grid' && aspectRatio?.enabled && aspectRatio.ratio ) s.aspectRatio = aspectRatio.ratio;
	return s;
};

/** Overlay preview: colour / gradient at the resting opacity. */
const buildOverlayStyle = ( attributes: ImagesGalleryAttributes ): CssProps => {
	const ov = attributes.overlay || {};
	const s: CssProps = {};
	if ( ov.type === 'color' && ov.color?.light ) s.backgroundColor = ov.color.light;
	else if ( ov.type === 'gradient' && ov.gradient?.light ) s.backgroundImage = ov.gradient.light;
	s.opacity = String( ov.opacity ?? 0.3 );
	return s;
};

/** Caption preview: colours, alignment, typography. */
const buildCaptionStyle = ( attributes: ImagesGalleryAttributes, device: DeviceKey ): CssProps => {
	const { caption, captionColor, captionBackground, captionTypography } = attributes;
	const s: CssProps = {};
	if ( captionColor?.light ) s.color = captionColor.light;
	if ( captionBackground?.light ) s.backgroundColor = captionBackground.light;
	if ( caption?.alignment ) s.textAlign = caption.alignment;
	applyTypography( s, effective( captionTypography, device ) );
	return s;
};

/**
 * Images Gallery edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ImagesGalleryAttributes > ): JSX.Element {
	const { images = [], galleryLayout = 'grid', hoverEffect, overlay, caption, className, responsiveVisibility } = attributes;
	const [ device ] = useDevice();
	const captionById = useAttachmentCaptions( images.map( ( img ) => img.id ).filter( Boolean ) as number[] );

	useBlockId( clientId, attributes.blockId, setAttributes );

	const onSelect = ( media: MediaItem | MediaItem[] ) => setAttributes( { images: toGalleryImages( media ) } );

	const hasImages = images.length > 0;
	const hasOverlay = !! overlay?.type && overlay.type !== 'none';
	const captionShow = !! caption?.show;
	const captionDisplay = caption?.display || 'overlay';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-images-gallery',
			attributes.blockId && `flexa-images-gallery-${ attributes.blockId }`,
			`flexa-images-gallery--${ galleryLayout }`,
			className,
			hoverEffect && hoverEffect !== 'none' && `flexa-images-gallery--hover-${ hoverEffect }`,
			captionShow && caption?.display === 'overlay' && `flexa-images-gallery--cap-${ caption?.position || 'bottom' }`,
			captionShow && caption?.display === 'overlay' && caption?.visibility === 'hover' && 'flexa-images-gallery--cap-hover',
			responsiveVisibility?.hideOnDesktop && 'flexa-hide-desktop',
			responsiveVisibility?.hideOnTablet && 'flexa-hide-tablet',
			responsiveVisibility?.hideOnMobile && 'flexa-hide-mobile'
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real gallery.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="image" />
			</div>
		);
	}

	if ( ! hasImages ) {
		return (
			<div { ...blockProps }>
				<MediaPlaceholder
					multiple
					accept="image/*"
					allowedTypes={ [ 'image' ] }
					onSelect={ onSelect }
					labels={ { title: __( 'Images Gallery', 'flexa-block' ), instructions: __( 'Upload or select images from the media library.', 'flexa-block' ) } }
				/>
			</div>
		);
	}

	const itemsStyle = buildItemsStyle( attributes, device );
	const itemStyle = buildItemStyle( attributes, device );
	const mediaStyle = buildMediaStyle( attributes, device );
	const overlayStyle = buildOverlayStyle( attributes );
	const captionStyle = buildCaptionStyle( attributes, device );

	// The overlay's resting opacity is an inline style, so the generator's
	// `:hover` opacity can't preview inline — mirror it in a scoped <style>.
	const hoverCss = attributes.blockId && hasOverlay
		? editorCss( [ {
			selector: `.flexa-images-gallery-${ attributes.blockId } .flexa-images-gallery__item:hover .flexa-images-gallery__overlay`,
			prop: 'opacity',
			value: String( overlay?.hoverOpacity ?? 0.5 ),
		} ] )
		: '';

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<MediaUploadCheck>
						<MediaUpload
							multiple
							gallery
							addToGallery
							allowedTypes={ [ 'image' ] }
							value={ images.map( ( img ) => img.id ).filter( Boolean ) as number[] }
							onSelect={ onSelect }
							render={ ( { open }: { open: () => void } ) => <ToolbarButton onClick={ open }>{ __( 'Edit Gallery', 'flexa-block' ) }</ToolbarButton> }
						/>
					</MediaUploadCheck>
				</ToolbarGroup>
			</BlockControls>

			<InspectorControls>
				<div className="flexa-inspector flexa-images-gallery-inspector">
					<InspectorTabs
						layout={
							<>
								<GalleryImagesPanel attributes={ attributes } setAttributes={ setAttributes } />
								<GalleryPanel attributes={ attributes } setAttributes={ setAttributes } />
								<GalleryCaptionPanel attributes={ attributes } setAttributes={ setAttributes } />
								<GalleryInteractionPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<GalleryEffectsPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				{ hoverCss && <style>{ hoverCss }</style> }
				<div className="flexa-images-gallery__items" style={ itemsStyle }>
					{ images.map( ( image, index ) => {
						const capText = captionFor( image, captionById );
						return (
							<div key={ image.id || index } className="flexa-images-gallery__item" style={ itemStyle }>
								<figure className="flexa-images-gallery__media" style={ mediaStyle }>
									<img className="flexa-images-gallery__image" src={ image.url } alt={ image.alt || '' } />
									{ hasOverlay && <span className="flexa-images-gallery__overlay" style={ overlayStyle } aria-hidden="true" /> }
									{ captionShow && captionDisplay === 'overlay' && capText && (
										<figcaption className="flexa-images-gallery__caption" style={ captionStyle }>
											{ capText }
										</figcaption>
									) }
								</figure>
								{ captionShow && captionDisplay === 'below' && capText && (
									<figcaption className="flexa-images-gallery__caption flexa-images-gallery__caption--below" style={ captionStyle }>
										{ capText }
									</figcaption>
								) }
							</div>
						);
					} ) }
				</div>
			</div>
		</>
	);
}
