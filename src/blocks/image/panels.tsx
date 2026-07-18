/**
 * Image block — image-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / visibility) and
 * typography controls come from @components; these cover the parts unique to the
 * image: the media + sizing, the effects (hover / overlay / mask), the caption
 * and the link / lightbox behaviour. Following the "prefer theme styles" rule
 * nothing is styled by default — only values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl, RangeControl, BaseControl, Button, Flex } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import {
	Segmented,
	SliderUnit,
	DualColor,
	GradientControl,
	TypographyControls,
	useDevice,
	BLEND_MODE_OPTIONS,
	CONTENT_ALIGN_OPTIONS,
	OBJECT_FIT_OPTIONS,
	ASPECT_RATIO_OPTIONS,
} from '@components';
import {
	rawDevice,
	patchDevice,
	LENGTH_UNITS,
} from '@utils';
import type { ImageAttributes, ImageCaptionAttr, ImageMedia, ImageOverlayAttr, LengthValue, PanelProps, TypographyDevice } from '../../types';

type ImagePanelProps = PanelProps< ImageAttributes >;

/* ---------------------------------------------------------------------------
 * Option sets (block-specific — kept local per the block conventions).
 * ------------------------------------------------------------------------- */

const IMAGE_SIZE_OPTIONS = [
	{ value: 'thumbnail', label: __( 'Thumbnail', 'flexa-block' ) },
	{ value: 'medium', label: __( 'Medium', 'flexa-block' ) },
	{ value: 'large', label: __( 'Large', 'flexa-block' ) },
	{ value: 'full', label: __( 'Full', 'flexa-block' ) },
];

const HOVER_EFFECT_OPTIONS = [
	{ value: 'none', label: __( 'None', 'flexa-block' ) },
	{ value: 'zoom-in', label: __( 'Zoom in', 'flexa-block' ) },
	{ value: 'zoom-out', label: __( 'Zoom out', 'flexa-block' ) },
	{ value: 'grayscale', label: __( 'Grayscale', 'flexa-block' ) },
	{ value: 'blur', label: __( 'Blur', 'flexa-block' ) },
	{ value: 'rotate', label: __( 'Rotate', 'flexa-block' ) },
	{ value: 'shine', label: __( 'Shine', 'flexa-block' ) },
	{ value: 'slide', label: __( 'Slide', 'flexa-block' ) },
];

const MASK_SHAPE_OPTIONS = [
	{ value: 'none', label: __( 'None', 'flexa-block' ) },
	{ value: 'circle', label: __( 'Circle', 'flexa-block' ) },
	{ value: 'ellipse', label: __( 'Ellipse', 'flexa-block' ) },
	{ value: 'rounded', label: __( 'Rounded', 'flexa-block' ) },
	{ value: 'triangle', label: __( 'Triangle', 'flexa-block' ) },
	{ value: 'diamond', label: __( 'Diamond', 'flexa-block' ) },
	{ value: 'hexagon', label: __( 'Hexagon', 'flexa-block' ) },
	{ value: 'pentagon', label: __( 'Pentagon', 'flexa-block' ) },
	{ value: 'octagon', label: __( 'Octagon', 'flexa-block' ) },
	{ value: 'star', label: __( 'Star', 'flexa-block' ) },
	{ value: 'heart', label: __( 'Heart', 'flexa-block' ) },
	{ value: 'blob', label: __( 'Blob', 'flexa-block' ) },
	{ value: 'custom', label: __( 'Custom SVG', 'flexa-block' ) },
];

const MASK_SIZE_OPTIONS = [
	{ value: 'contain', label: __( 'Contain', 'flexa-block' ) },
	{ value: 'cover', label: __( 'Cover', 'flexa-block' ) },
	{ value: 'auto', label: __( 'Auto', 'flexa-block' ) },
];

const MASK_REPEAT_OPTIONS = [
	{ value: 'no-repeat', label: __( 'No repeat', 'flexa-block' ) },
	{ value: 'repeat', label: __( 'Repeat', 'flexa-block' ) },
	{ value: 'repeat-x', label: __( 'Repeat X', 'flexa-block' ) },
	{ value: 'repeat-y', label: __( 'Repeat Y', 'flexa-block' ) },
];

/** Add or remove a token from a space-separated `rel` string. */
const toggleRel = ( rel: string | undefined, token: string, on: boolean ): string => {
	const parts = ( rel || '' ).split( /\s+/ ).filter( Boolean ).filter( ( t ) => t !== token );
	if ( on ) {
		parts.push( token );
	}
	return parts.join( ' ' );
};

const relHas = ( rel: string | undefined, token: string ): boolean => ( rel || '' ).split( /\s+/ ).includes( token );

/**
 * Image panel — media selection, size, alt, object-fit, width, alignment,
 * aspect-ratio and lazy loading.
 */
export const ImagePanel = ( { attributes, setAttributes }: ImagePanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { image, imageSize, altText, objectFit, imageWidth, imageAlign, aspectRatio, lazyLoad } = attributes;
	const width = rawDevice( imageWidth, device );
	const align = imageAlign?.[ device ] || ( device === 'desktop' ? 'center' : '' );
	const ratio = aspectRatio || {};

	const onSelect = ( media: { id: number; url: string; alt?: string; width?: number; height?: number } ) => {
		const next: ImageMedia = { id: media.id, url: media.url, alt: media.alt || '', width: media.width ?? null, height: media.height ?? null };
		setAttributes( { image: next } );
	};

	return (
		<PanelBody title={ __( 'Image', 'flexa-block' ) } initialOpen={ true }>
			<BaseControl __nextHasNoMarginBottom label={ __( 'Media', 'flexa-block' ) }>
				<MediaUploadCheck>
					<MediaUpload
						allowedTypes={ [ 'image' ] }
						value={ image?.id ?? undefined }
						onSelect={ onSelect }
						render={ ( { open }: { open: () => void } ) => (
							<>
								{ image?.url && (
									<button type="button" className="flexa-bg-preview" onClick={ open } aria-label={ __( 'Replace image', 'flexa-block' ) }>
										<img src={ image.url } alt="" />
									</button>
								) }
								<Flex gap={ 2 } justify="flex-start">
									<Button variant="secondary" onClick={ open }>
										{ image?.url ? __( 'Replace', 'flexa-block' ) : __( 'Select Image', 'flexa-block' ) }
									</Button>
									{ image?.url && (
										<Button isDestructive variant="tertiary" onClick={ () => setAttributes( { image: { id: null, url: '', alt: '', width: null, height: null } } ) }>
											{ __( 'Remove', 'flexa-block' ) }
										</Button>
									) }
								</Flex>
							</>
						) }
					/>
				</MediaUploadCheck>
			</BaseControl>

			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Image Size', 'flexa-block' ) }
				value={ imageSize || 'large' }
				options={ IMAGE_SIZE_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { imageSize: v } ) }
			/>

			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Alt Text', 'flexa-block' ) }
				help={ __( 'Leave empty to use the media library alt text.', 'flexa-block' ) }
				value={ altText || '' }
				onChange={ ( v: string ) => setAttributes( { altText: v } ) }
			/>

			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Object Fit', 'flexa-block' ) }
				value={ objectFit || 'cover' }
				options={ OBJECT_FIT_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { objectFit: v } ) }
			/>

			<SliderUnit
				label={ __( 'Width', 'flexa-block' ) }
				value={ width }
				units={ LENGTH_UNITS }
				defaultUnit="%"
				max={ { px: 2000, '%': 100, vw: 100, em: 100, rem: 100, vh: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { imageWidth: patchDevice( imageWidth, device, v ) } ) }
			/>

			<div className="flexa-field">
				<Segmented
					label={ __( 'Alignment', 'flexa-block' ) }
					value={ align }
					responsive
					onChange={ ( v ) => setAttributes( { imageAlign: { ...imageAlign, [ device ]: v } } ) }
					options={ CONTENT_ALIGN_OPTIONS }
				/>
			</div>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Lock aspect ratio', 'flexa-block' ) }
				checked={ !! ratio.enabled }
				onChange={ ( v: boolean ) => setAttributes( { aspectRatio: { ...ratio, enabled: v } } ) }
			/>
			{ ratio.enabled && (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Aspect Ratio', 'flexa-block' ) }
					value={ ratio.ratio || '16/9' }
					options={ ASPECT_RATIO_OPTIONS }
					onChange={ ( v: string ) => setAttributes( { aspectRatio: { ...ratio, ratio: v } } ) }
				/>
			) }

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Lazy load', 'flexa-block' ) }
				help={ __( 'Defer loading until the image nears the viewport.', 'flexa-block' ) }
				checked={ !! lazyLoad }
				onChange={ ( v: boolean ) => setAttributes( { lazyLoad: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Effects panel — hover motion, colour / gradient overlay and shape mask.
 */
export const ImageEffectsPanel = ( { attributes, setAttributes }: ImagePanelProps ): JSX.Element => {
	const { hoverEffect, overlay, mask } = attributes;
	const ov = overlay || {};
	const mk = mask || {};
	const setOverlay = ( patch: Partial< ImageAttributes[ 'overlay' ] > ) => setAttributes( { overlay: { ...ov, ...patch } } );
	const setMask = ( patch: Partial< ImageAttributes[ 'mask' ] > ) => setAttributes( { mask: { ...mk, ...patch } } );

	return (
		<PanelBody title={ __( 'Effects', 'flexa-block' ) } initialOpen={ false }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Hover Effect', 'flexa-block' ) }
				value={ hoverEffect || 'none' }
				options={ HOVER_EFFECT_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { hoverEffect: v } ) }
			/>

			<Segmented
				label={ __( 'Overlay', 'flexa-block' ) }
				value={ ov.type || 'none' }
				onChange={ ( v ) => setOverlay( { type: v as ImageOverlayAttr[ 'type' ] } ) }
				options={ [
					{ value: 'none', label: __( 'None', 'flexa-block' ) },
					{ value: 'color', label: __( 'Color', 'flexa-block' ) },
					{ value: 'gradient', label: __( 'Gradient', 'flexa-block' ) },
				] }
			/>
			{ ov.type === 'color' && (
				<DualColor label={ __( 'Overlay Color', 'flexa-block' ) } value={ ov.color || {} } onChange={ ( v ) => setOverlay( { color: v } ) } />
			) }
			{ ov.type === 'gradient' && (
				<GradientControl label={ __( 'Overlay Gradient', 'flexa-block' ) } value={ ov.gradient || {} } onChange={ ( v ) => setOverlay( { gradient: v } ) } />
			) }
			{ ov.type && ov.type !== 'none' && (
				<>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Opacity', 'flexa-block' ) }
						value={ Math.round( ( ov.opacity ?? 0.5 ) * 100 ) }
						min={ 0 }
						max={ 100 }
						onChange={ ( v: number ) => setOverlay( { opacity: ( v ?? 50 ) / 100 } ) }
					/>
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Blend Mode', 'flexa-block' ) }
						value={ ov.blendMode || '' }
						options={ BLEND_MODE_OPTIONS }
						onChange={ ( v: string ) => setOverlay( { blendMode: v } ) }
					/>
				</>
			) }

			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Mask Shape', 'flexa-block' ) }
				value={ mk.shape || 'none' }
				options={ MASK_SHAPE_OPTIONS }
				onChange={ ( v: string ) => setMask( { shape: v } ) }
			/>
			{ mk.shape === 'custom' && (
				<BaseControl __nextHasNoMarginBottom label={ __( 'Mask Image', 'flexa-block' ) }>
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ mk.customImage?.id ?? undefined }
							onSelect={ ( media: { id: number; url: string } ) => setMask( { customImage: { id: media.id, url: media.url } } ) }
							render={ ( { open }: { open: () => void } ) => (
								<Flex gap={ 2 } justify="flex-start">
									<Button variant="secondary" onClick={ open }>
										{ mk.customImage?.url ? __( 'Replace', 'flexa-block' ) : __( 'Select Mask', 'flexa-block' ) }
									</Button>
									{ mk.customImage?.url && (
										<Button isDestructive variant="tertiary" onClick={ () => setMask( { customImage: { id: null, url: '' } } ) }>
											{ __( 'Remove', 'flexa-block' ) }
										</Button>
									) }
								</Flex>
							) }
						/>
					</MediaUploadCheck>
				</BaseControl>
			) }
			{ mk.shape === 'custom' && (
				<>
					<SelectControl __nextHasNoMarginBottom label={ __( 'Mask Size', 'flexa-block' ) } value={ mk.size || 'contain' } options={ MASK_SIZE_OPTIONS } onChange={ ( v: string ) => setMask( { size: v } ) } />
					<SelectControl __nextHasNoMarginBottom label={ __( 'Mask Repeat', 'flexa-block' ) } value={ mk.repeat || 'no-repeat' } options={ MASK_REPEAT_OPTIONS } onChange={ ( v: string ) => setMask( { repeat: v } ) } />
					<TextControl __nextHasNoMarginBottom label={ __( 'Mask Position', 'flexa-block' ) } value={ mk.position || 'center center' } onChange={ ( v: string ) => setMask( { position: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Caption panel — text source, placement, alignment, colours and typography.
 */
export const ImageCaptionPanel = ( { attributes, setAttributes }: ImagePanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { caption, captionColor, captionBackground } = attributes;
	const cap = caption || {};
	const setCaption = ( patch: Partial< ImageAttributes[ 'caption' ] > ) => setAttributes( { caption: { ...cap, ...patch } } );
	const typo = rawDevice( attributes.captionTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { captionTypography: patchDevice( attributes.captionTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Caption', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show caption', 'flexa-block' ) }
				checked={ !! cap.show }
				onChange={ ( v: boolean ) => setCaption( { show: v } ) }
			/>
			{ cap.show && (
				<>
					<Segmented
						label={ __( 'Source', 'flexa-block' ) }
						value={ cap.source || 'custom' }
						onChange={ ( v ) => setCaption( { source: v as ImageCaptionAttr[ 'source' ] } ) }
						options={ [
							{ value: 'custom', label: __( 'Custom', 'flexa-block' ) },
							{ value: 'attachment', label: __( 'From media', 'flexa-block' ) },
						] }
					/>
					{ cap.source !== 'attachment' && (
						<TextControl __nextHasNoMarginBottom label={ __( 'Caption Text', 'flexa-block' ) } value={ cap.text || '' } onChange={ ( v: string ) => setCaption( { text: v } ) } />
					) }
					<Segmented
						label={ __( 'Display', 'flexa-block' ) }
						value={ cap.display || 'below' }
						onChange={ ( v ) => setCaption( { display: v as ImageCaptionAttr[ 'display' ] } ) }
						options={ [
							{ value: 'below', label: __( 'Below', 'flexa-block' ) },
							{ value: 'overlay', label: __( 'Overlay', 'flexa-block' ) },
						] }
					/>
					{ cap.display === 'overlay' && (
						<Segmented
							label={ __( 'Position', 'flexa-block' ) }
							value={ cap.position || 'bottom' }
							onChange={ ( v ) => setCaption( { position: v as ImageCaptionAttr[ 'position' ] } ) }
							options={ [
								{ value: 'top', label: __( 'Top', 'flexa-block' ) },
								{ value: 'center', label: __( 'Center', 'flexa-block' ) },
								{ value: 'bottom', label: __( 'Bottom', 'flexa-block' ) },
							] }
						/>
					) }
					<div className="flexa-field">
						<Segmented
							label={ __( 'Text Align', 'flexa-block' ) }
							value={ cap.alignment || 'center' }
							onChange={ ( v ) => setCaption( { alignment: v as ImageCaptionAttr[ 'alignment' ] } ) }
							options={ CONTENT_ALIGN_OPTIONS }
						/>
					</div>
					<DualColor label={ __( 'Text Color', 'flexa-block' ) } value={ captionColor || {} } onChange={ ( v ) => setAttributes( { captionColor: v } ) } />
					<DualColor label={ __( 'Background', 'flexa-block' ) } value={ captionBackground || {} } onChange={ ( v ) => setAttributes( { captionBackground: v } ) } />
					<TypographyControls value={ typo } onChange={ setTypo } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Link panel — click action plus link fields or lightbox appearance.
 */
export const ImageLinkPanel = ( { attributes, setAttributes }: ImagePanelProps ): JSX.Element => {
	const { clickAction, link, lightbox } = attributes;
	const lk = link || {};
	const lb = lightbox || {};
	const setLink = ( patch: Partial< ImageAttributes[ 'link' ] > ) => setAttributes( { link: { ...lk, ...patch } } );
	const setLightbox = ( patch: Partial< ImageAttributes[ 'lightbox' ] > ) => setAttributes( { lightbox: { ...lb, ...patch } } );

	return (
		<PanelBody title={ __( 'Link & Lightbox', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'On Click', 'flexa-block' ) }
				value={ clickAction || 'none' }
				onChange={ ( v ) => setAttributes( { clickAction: v as ImageAttributes[ 'clickAction' ] } ) }
				options={ [
					{ value: 'none', label: __( 'None', 'flexa-block' ) },
					{ value: 'link', label: __( 'Link', 'flexa-block' ) },
					{ value: 'lightbox', label: __( 'Lightbox', 'flexa-block' ) },
					{ value: 'media', label: __( 'Media', 'flexa-block' ) },
				] }
			/>

			{ clickAction === 'link' && (
				<>
					<TextControl __nextHasNoMarginBottom label={ __( 'Link URL', 'flexa-block' ) } type="url" value={ lk.url || '' } placeholder="https://" onChange={ ( v: string ) => setLink( { url: v } ) } />
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Open in new tab', 'flexa-block' ) }
						checked={ lk.target === '_blank' }
						onChange={ ( v: boolean ) => setLink( { target: v ? '_blank' : '', rel: toggleRel( toggleRel( lk.rel, 'noopener', v ), 'noreferrer', v ) } ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Add nofollow', 'flexa-block' ) }
						checked={ relHas( lk.rel, 'nofollow' ) }
						onChange={ ( v: boolean ) => setLink( { rel: toggleRel( lk.rel, 'nofollow', v ) } ) }
					/>
				</>
			) }

			{ clickAction === 'lightbox' && (
				<>
					<DualColor label={ __( 'Overlay Background', 'flexa-block' ) } value={ lb.background || {} } onChange={ ( v ) => setLightbox( { background: v } ) } />
					<ToggleControl __nextHasNoMarginBottom label={ __( 'Show caption in lightbox', 'flexa-block' ) } checked={ lb.showCaption !== false } onChange={ ( v: boolean ) => setLightbox( { showCaption: v } ) } />
				</>
			) }
		</PanelBody>
	);
};
