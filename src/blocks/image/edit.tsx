/**
 * Image block — editor component.
 *
 * Assembles the image-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / visibility.
 * The canvas renders the picture with an inline preview that mirrors the PHP CSS
 * generator; nothing is styled by default so the image inherits sensible base
 * styles until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { InspectorControls, BlockControls, MediaUpload, MediaUploadCheck, MediaPlaceholder, useBlockProps } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';

import {
	InspectorTabs,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	VisibilityPanel,
	AnimationPanel,
	useDevice,
	ExamplePreviewSkeleton,
} from '@components';
import { cn, effective, rawDevice, withUnit, spacingShorthand, applyTypography, applyBackgroundPreview, applyBorderPreview, boxShadowPreview } from '@utils';
import { ImagePanel, ImageEffectsPanel, ImageCaptionPanel, ImageLinkPanel } from './panels';
import type { DeviceKey, EditProps, ImageAttributes, ImageMedia } from '../../types';

type CssProps = Record< string, string >;

/** Built-in mask shapes (geometry lives in style.scss, keyed by class name). */
const BUILT_IN_MASKS = [ 'circle', 'ellipse', 'rounded', 'triangle', 'diamond', 'hexagon', 'pentagon', 'octagon', 'star', 'heart', 'blob' ];

/** Outer figure preview: spacing, background, box-shadow. */
const buildWrapperStyle = ( attributes: ImageAttributes, device: DeviceKey ): CssProps => {
	const { spacing, background, boxShadow } = attributes;
	const sp = effective( spacing, device );
	const s: CssProps = {};

	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, background );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	return s;
};

/** Frame preview: width, alignment, aspect-ratio, border, custom mask. */
const buildFrameStyle = ( attributes: ImageAttributes, device: DeviceKey ): CssProps => {
	const { imageWidth, imageAlign, aspectRatio, border, mask } = attributes;
	const w = rawDevice( imageWidth, device );
	const b = effective( border, device );
	const s: CssProps = {};

	if ( w.value ) s.width = withUnit( w.value, w.unit || '%' );

	const align = imageAlign?.[ device ] || ( device === 'desktop' ? 'center' : '' );
	if ( align === 'left' ) {
		s.marginRight = 'auto';
	} else if ( align === 'right' ) {
		s.marginLeft = 'auto';
	} else if ( align === 'center' ) {
		s.marginLeft = 'auto';
		s.marginRight = 'auto';
	}

	if ( aspectRatio?.enabled && aspectRatio.ratio ) s.aspectRatio = aspectRatio.ratio;

	applyBorderPreview( s, b );

	// Custom uploaded mask — built-in shapes come from CSS classes.
	if ( mask?.shape === 'custom' && mask.customImage?.url ) {
		s[ '--flexa-img-mask' ] = `url(${ mask.customImage.url })`;
		s[ '--flexa-img-mask-size' ] = mask.size || 'contain';
		s[ '--flexa-img-mask-position' ] = mask.position || 'center center';
		s[ '--flexa-img-mask-repeat' ] = mask.repeat || 'no-repeat';
	}

	return s;
};

/** Overlay preview: colour / gradient, opacity, blend. */
const buildOverlayStyle = ( attributes: ImageAttributes ): CssProps => {
	const ov = attributes.overlay || {};
	const s: CssProps = {};
	if ( ov.type === 'color' && ov.color?.light ) s.backgroundColor = ov.color.light;
	else if ( ov.type === 'gradient' && ov.gradient?.light ) s.backgroundImage = ov.gradient.light;
	s.opacity = String( ov.opacity ?? 0.5 );
	if ( ov.blendMode ) s.mixBlendMode = ov.blendMode;
	return s;
};

/** Caption preview: colours, alignment, typography. */
const buildCaptionStyle = ( attributes: ImageAttributes, device: DeviceKey ): CssProps => {
	const { caption, captionColor, captionBackground, captionTypography } = attributes;
	const typo = effective( captionTypography, device );
	const s: CssProps = {};

	if ( captionColor?.light ) s.color = captionColor.light;
	if ( captionBackground?.light ) s.backgroundColor = captionBackground.light;
	if ( caption?.alignment ) s.textAlign = caption.alignment;

	applyTypography( s, typo );

	return s;
};

/**
 * Image edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ImageAttributes > ): JSX.Element {
	const { image, altText, objectFit, aspectRatio, hoverEffect, overlay, mask, caption, className, responsiveVisibility } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useEffect( () => {
		const expected = clientId.replace( /[^a-z0-9]/gi, '' ).slice( 0, 8 );
		if ( ! blockId || blockId !== expected ) {
			setAttributes( { blockId: expected } );
		}
	}, [ blockId, clientId, setAttributes ] );

	// Attachment caption for preview when the caption source is the media library.
	const attachmentCaption = useSelect(
		( select: ( store: string ) => any ) => {
			if ( ! image?.id || caption?.source !== 'attachment' ) {
				return '';
			}
			const media = select( 'core' ).getEntityRecord( 'postType', 'attachment', image.id );
			return media ? media.caption?.raw || media.caption?.rendered || '' : '';
		},
		[ image?.id, caption?.source ]
	);

	const onSelect = ( media: { id: number; url: string; alt?: string; width?: number; height?: number } ) => {
		const next: ImageMedia = { id: media.id, url: media.url, alt: media.alt || '', width: media.width ?? null, height: media.height ?? null };
		setAttributes( { image: next } );
	};

	const hasMask = !! mask?.shape && mask.shape !== 'none';
	const isBuiltInMask = hasMask && BUILT_IN_MASKS.includes( mask!.shape as string );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-image',
			blockId && `flexa-image-${ blockId }`,
			className,
			hoverEffect && hoverEffect !== 'none' && `flexa-image--hover-${ hoverEffect }`,
			hasMask && 'flexa-image--masked',
			isBuiltInMask && `flexa-image--mask-${ mask!.shape }`,
			aspectRatio?.enabled && 'flexa-image--has-ratio',
			responsiveVisibility?.hideOnDesktop && 'flexa-hide-desktop',
			responsiveVisibility?.hideOnTablet && 'flexa-hide-tablet',
			responsiveVisibility?.hideOnMobile && 'flexa-hide-mobile'
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real image.
	if ( ( attributes as any ).isExamplePreview ) {
		return (
			<figure { ...blockProps }>
				<ExamplePreviewSkeleton kind="image" />
			</figure>
		);
	}

	if ( ! image?.url ) {
		return (
			<figure { ...blockProps }>
				<MediaPlaceholder
					accept="image/*"
					allowedTypes={ [ 'image' ] }
					onSelect={ onSelect }
					labels={ { title: __( 'Image', 'flexa-block' ), instructions: __( 'Upload or select an image from the media library.', 'flexa-block' ) } }
				/>
			</figure>
		);
	}

	const frameStyle = buildFrameStyle( attributes, device );
	const imgStyle: CssProps = {};
	if ( objectFit ) imgStyle.objectFit = objectFit;
	if ( aspectRatio?.enabled && aspectRatio.ratio ) imgStyle.height = '100%';

	const hasOverlay = !! overlay?.type && overlay.type !== 'none';
	const overlayStyle = buildOverlayStyle( attributes );

	const captionText = caption?.source === 'attachment' ? attachmentCaption : caption?.text || '';
	const showCaption = !! caption?.show && !! captionText;
	const captionStyle = buildCaptionStyle( attributes, device );
	const isOverlayCaption = caption?.display === 'overlay';

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<MediaUploadCheck>
						<MediaUpload
							onSelect={ onSelect }
							allowedTypes={ [ 'image' ] }
							value={ image.id ?? undefined }
							render={ ( { open }: { open: () => void } ) => <ToolbarButton onClick={ open }>{ __( 'Replace', 'flexa-block' ) }</ToolbarButton> }
						/>
					</MediaUploadCheck>
				</ToolbarGroup>
			</BlockControls>

			<InspectorControls>
				<div className="flexa-inspector flexa-image-inspector">
					<InspectorTabs
						layout={
							<>
								<ImagePanel attributes={ attributes } setAttributes={ setAttributes } />
								<ImageCaptionPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ImageLinkPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ImageEffectsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<BackgroundPanel attributes={ attributes } setAttributes={ setAttributes } />
								<BorderPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ShadowPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						advanced={
							<>
								<VisibilityPanel attributes={ attributes } setAttributes={ setAttributes } />
								<AnimationPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
					/>
				</div>
			</InspectorControls>

			<figure { ...blockProps }>
				<div className="flexa-image__frame" style={ frameStyle }>
					<img className="flexa-image__img" src={ image.url } alt={ altText || image.alt || '' } style={ imgStyle } />
					{ hasOverlay && <span className="flexa-image__overlay" style={ overlayStyle } aria-hidden="true" /> }
					{ showCaption && isOverlayCaption && (
						<figcaption className={ cn( 'flexa-image__caption', `flexa-image__caption--${ caption!.position || 'bottom' }` ) } style={ captionStyle }>
							{ captionText }
						</figcaption>
					) }
				</div>
				{ showCaption && ! isOverlayCaption && (
					<figcaption className="flexa-image__caption flexa-image__caption--below" style={ captionStyle }>
						{ captionText }
					</figcaption>
				) }
			</figure>
		</>
	);
}
