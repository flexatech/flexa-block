/**
 * Images Gallery block — gallery-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) and typography controls come from @components; these cover the
 * parts unique to the gallery: the media + layout, the image effects (hover /
 * overlay / shadow), the caption and the click / lightbox behaviour. Following
 * the "prefer theme styles" rule nothing is styled by default — only values the
 * user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { PanelBody, SelectControl, ToggleControl, RangeControl, Button } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import {
	Segmented,
	SliderUnit,
	DualColor,
	GradientControl,
	FieldHead,
	TypographyControls,
	useDevice,
	CONTENT_ALIGN_OPTIONS,
	ASPECT_RATIO_OPTIONS,
} from '@components';
import { rawDevice, patchDevice, LENGTH_UNITS, SPACING_UNITS } from '@utils';
import type { GalleryImage, GalleryCaptionAttr, GalleryOverlayAttr, ImagesGalleryAttributes, LengthValue, PanelProps, TypographyDevice } from '../../types';

type GPanelProps = PanelProps< ImagesGalleryAttributes >;

/* ---------------------------------------------------------------------------
 * Option sets (block-specific — kept local per the block conventions).
 * ------------------------------------------------------------------------- */

const LAYOUT_OPTIONS = [
	{ value: 'grid', label: __( 'Grid', 'flexa-block' ) },
	{ value: 'masonry', label: __( 'Masonry', 'flexa-block' ) },
	{ value: 'tiled', label: __( 'Tiled', 'flexa-block' ) },
];

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

/** Box-shadow numeric offset fields (mirrors the shared ShadowPanel). */
const SHADOW_FIELDS: Array< { k: 'horizontal' | 'vertical' | 'blur' | 'spread'; l: string } > = [
	{ k: 'horizontal', l: __( 'X offset', 'flexa-block' ) },
	{ k: 'vertical', l: __( 'Y offset', 'flexa-block' ) },
	{ k: 'blur', l: __( 'Blur', 'flexa-block' ) },
	{ k: 'spread', l: __( 'Spread', 'flexa-block' ) },
];

/** A WordPress media item as handed to `MediaUpload.onSelect`. */
export interface MediaItem {
	id: number;
	url?: string;
	sizes?: Record< string, { url?: string } >;
	alt?: string;
	caption?: unknown;
	width?: number;
	height?: number;
}

/** Read a caption whether the media model exposes it as a string or { raw }. */
const readCaption = ( caption: unknown ): string => {
	if ( typeof caption === 'string' ) {
		return caption;
	}
	if ( caption && typeof caption === 'object' ) {
		const c = caption as { raw?: string; rendered?: string };
		return c.raw || c.rendered || '';
	}
	return '';
};

/** Build a render-ready image object from a WordPress media item. */
export const toGalleryImage = ( media: MediaItem ): GalleryImage => ( {
	id: media.id,
	url: media.sizes?.large?.url || media.url || '',
	alt: media.alt || '',
	caption: readCaption( media.caption ),
	width: media.width ?? null,
	height: media.height ?? null,
} );

/** Normalise a media selection (single or array) to a gallery image array. */
export const toGalleryImages = ( media: MediaItem | MediaItem[] ): GalleryImage[] =>
	( Array.isArray( media ) ? media : [ media ] ).map( toGalleryImage );

/**
 * Resolve each attachment's caption live from the media library, so whatever the
 * user types in the attachment's "Caption" field is always reflected — captions
 * are never edited inside the block. Returns a map keyed by attachment id.
 */
export const useAttachmentCaptions = ( ids: number[] ): Record< number, string > =>
	useSelect(
		( select: ( store: string ) => { getEntityRecord: ( kind: string, name: string, key: number ) => { caption?: { raw?: string; rendered?: string } } | undefined } ) => {
			const { getEntityRecord } = select( 'core' );
			const map: Record< number, string > = {};
			ids.forEach( ( id ) => {
				if ( ! id ) {
					return;
				}
				const record = getEntityRecord( 'postType', 'attachment', id );
				if ( record ) {
					map[ id ] = record.caption?.raw || record.caption?.rendered || '';
				}
			} );
			return map;
		},
		[ ids.join( ',' ) ]
	);

/** The caption to show for an image: live library caption, else the cached one. */
export const captionFor = ( image: GalleryImage, captions: Record< number, string > ): string => {
	if ( image.id && captions[ image.id ] !== undefined ) {
		return captions[ image.id ];
	}
	return image.caption || '';
};

/**
 * Images panel — add / replace images via the media library, then manage each
 * one as a collapsible card: thumbnail preview, reorder, remove and a read-only
 * caption pulled live from the attachment's own "Caption" field in the library.
 */
export const GalleryImagesPanel = ( { attributes, setAttributes }: GPanelProps ): JSX.Element => {
	const images = attributes.images || [];
	const ids = images.map( ( img ) => img.id ).filter( Boolean ) as number[];
	const captions = useAttachmentCaptions( ids );
	const [ openIndex, setOpenIndex ] = useState< number >( -1 );

	const onSelect = ( media: MediaItem | MediaItem[] ) => setAttributes( { images: toGalleryImages( media ) } );
	const move = ( index: number, dir: -1 | 1 ) => {
		const target = index + dir;
		if ( target < 0 || target >= images.length ) {
			return;
		}
		const next = [ ...images ];
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];
		setAttributes( { images: next } );
		setOpenIndex( target );
	};
	const remove = ( index: number ) => {
		setAttributes( { images: images.filter( ( _, i ) => i !== index ) } );
		setOpenIndex( -1 );
	};

	return (
		<PanelBody title={ __( 'Images', 'flexa-block' ) } initialOpen={ true }>
			<MediaUploadCheck>
				<MediaUpload
					multiple
					gallery
					addToGallery
					allowedTypes={ [ 'image' ] }
					value={ ids }
					onSelect={ onSelect }
					render={ ( { open }: { open: () => void } ) => (
						<Button className="flexa-item-card__add" variant="secondary" onClick={ open }>
							{ images.length ? __( 'Add / Edit Images', 'flexa-block' ) : __( 'Add Images', 'flexa-block' ) }
						</Button>
					) }
				/>
			</MediaUploadCheck>

			{ images.map( ( image, index ) => {
				const isOpen = openIndex === index;
				const caption = captionFor( image, captions );
				return (
					<div key={ image.id || index } className={ 'flexa-item-card' + ( isOpen ? ' is-open' : '' ) }>
						<div className="flexa-item-card__head">
							<button type="button" className="flexa-item-card__toggle" aria-expanded={ isOpen } onClick={ () => setOpenIndex( isOpen ? -1 : index ) }>
								{ image.url && (
									<span className="flexa-item-card__preview">
										<img src={ image.url } alt="" />
									</span>
								) }
								<span className="flexa-item-card__title">
									{ image.alt || caption || `${ __( 'Image', 'flexa-block' ) } ${ index + 1 }` }
								</span>
							</button>
							<span className="flexa-item-card__tools">
								<Button size="small" icon="arrow-up-alt2" label={ __( 'Move up', 'flexa-block' ) } disabled={ index === 0 } onClick={ () => move( index, -1 ) } />
								<Button size="small" icon="arrow-down-alt2" label={ __( 'Move down', 'flexa-block' ) } disabled={ index === images.length - 1 } onClick={ () => move( index, 1 ) } />
								<Button size="small" icon="no-alt" label={ __( 'Remove', 'flexa-block' ) } isDestructive onClick={ () => remove( index ) } />
							</span>
						</div>
						{ isOpen && (
							<div className="flexa-item-card__body">
								<p className="flexa-images-gallery__cap-note">
									{ caption
										? caption
										: __( 'No caption. Set it in the Media Library — the attachment’s “Caption” field.', 'flexa-block' ) }
								</p>
							</div>
						) }
					</div>
				);
			} ) }
		</PanelBody>
	);
};

/**
 * Gallery layout panel — layout mode, per-device columns / gap / tiled height,
 * image size, aspect ratio, per-image corner radius and lazy loading.
 */
export const GalleryPanel = ( { attributes, setAttributes }: GPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { galleryLayout, columns, gap, tiledHeight, imageSize, aspectRatio, imageRadius, lazyLoad } = attributes;
	const ratio = aspectRatio || {};
	const isTiled = galleryLayout === 'tiled';
	const isGrid = galleryLayout === 'grid';
	const isMasonry = galleryLayout === 'masonry';

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Layout', 'flexa-block' ) }
				value={ galleryLayout || 'grid' }
				onChange={ ( v ) => setAttributes( { galleryLayout: v as ImagesGalleryAttributes[ 'galleryLayout' ] } ) }
				options={ LAYOUT_OPTIONS }
			/>

			{ ( isGrid || isMasonry ) && (
				<SliderUnit
					label={ __( 'Columns', 'flexa-block' ) }
					value={ columns?.[ device ] || {} }
					units={ [ { value: '', label: '' } ] }
					defaultUnit=""
					min={ 1 }
					max={ { '': 12 } }
					onChange={ ( v: LengthValue ) => setAttributes( { columns: { ...columns, [ device ]: { value: v.value ?? '', unit: '' } } } ) }
				/>
			) }

			{ isTiled && (
				<SliderUnit
					label={ __( 'Row Height', 'flexa-block' ) }
					value={ tiledHeight?.[ device ] || {} }
					units={ LENGTH_UNITS }
					defaultUnit="px"
					max={ { px: 600, vh: 100, rem: 40, '%': 100, vw: 100, em: 40 } }
					onChange={ ( v: LengthValue ) => setAttributes( { tiledHeight: patchDevice( tiledHeight, device, v ) } ) }
				/>
			) }

			<SliderUnit
				label={ __( 'Gap', 'flexa-block' ) }
				value={ gap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 10, rem: 10, '%': 100, vw: 100, vh: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { gap: patchDevice( gap, device, v ) } ) }
			/>

			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Image Size', 'flexa-block' ) }
				value={ imageSize || 'large' }
				options={ IMAGE_SIZE_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { imageSize: v } ) }
			/>

			{ isGrid && (
				<>
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
							value={ ratio.ratio || '1/1' }
							options={ ASPECT_RATIO_OPTIONS }
							onChange={ ( v: string ) => setAttributes( { aspectRatio: { ...ratio, ratio: v } } ) }
						/>
					) }
				</>
			) }

			<SliderUnit
				label={ __( 'Image Radius', 'flexa-block' ) }
				value={ imageRadius?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 10, rem: 10, '%': 50, vw: 50, vh: 50 } }
				onChange={ ( v: LengthValue ) => setAttributes( { imageRadius: patchDevice( imageRadius, device, v ) } ) }
			/>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Lazy load', 'flexa-block' ) }
				help={ __( 'Defer loading images until they near the viewport.', 'flexa-block' ) }
				checked={ !! lazyLoad }
				onChange={ ( v: boolean ) => setAttributes( { lazyLoad: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Image effects panel — hover motion, colour / gradient overlay and per-image
 * box shadow.
 */
export const GalleryEffectsPanel = ( { attributes, setAttributes }: GPanelProps ): JSX.Element => {
	const { hoverEffect, overlay, imageShadow } = attributes;
	const ov: GalleryOverlayAttr = overlay || {};
	const shadow = imageShadow || {};
	const setOverlay = ( patch: Partial< GalleryOverlayAttr > ) => setAttributes( { overlay: { ...ov, ...patch } } );
	const setShadow = ( patch: Partial< ImagesGalleryAttributes[ 'imageShadow' ] > ) => setAttributes( { imageShadow: { ...shadow, ...patch } } );

	return (
		<PanelBody title={ __( 'Image Effects', 'flexa-block' ) } initialOpen={ false }>
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
				onChange={ ( v ) => setOverlay( { type: v as GalleryOverlayAttr[ 'type' ] } ) }
				options={ [
					{ value: 'none', label: __( 'None', 'flexa-block' ) },
					{ value: 'color', label: __( 'Color', 'flexa-block' ) },
					{ value: 'gradient', label: __( 'Gradient', 'flexa-block' ) },
				] }
			/>
			{ ov.type === 'color' && <DualColor label={ __( 'Overlay Color', 'flexa-block' ) } value={ ov.color || {} } onChange={ ( v ) => setOverlay( { color: v } ) } /> }
			{ ov.type === 'gradient' && <GradientControl label={ __( 'Overlay Gradient', 'flexa-block' ) } value={ ov.gradient || {} } onChange={ ( v ) => setOverlay( { gradient: v } ) } /> }
			{ ov.type && ov.type !== 'none' && (
				<>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Opacity', 'flexa-block' ) }
						value={ Math.round( ( ov.opacity ?? 0.3 ) * 100 ) }
						min={ 0 }
						max={ 100 }
						onChange={ ( v: number ) => setOverlay( { opacity: ( v ?? 30 ) / 100 } ) }
					/>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Opacity on hover', 'flexa-block' ) }
						value={ Math.round( ( ov.hoverOpacity ?? 0.5 ) * 100 ) }
						min={ 0 }
						max={ 100 }
						onChange={ ( v: number ) => setOverlay( { hoverOpacity: ( v ?? 50 ) / 100 } ) }
					/>
				</>
			) }

			<FieldHead label={ __( 'Image Shadow', 'flexa-block' ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Enable', 'flexa-block' ) } checked={ !! shadow.enabled } onChange={ ( v: boolean ) => setShadow( { enabled: v } ) } />
			{ shadow.enabled && (
				<>
					{ SHADOW_FIELDS.map( ( f ) => (
						<RangeControl key={ f.k } __nextHasNoMarginBottom label={ f.l } value={ parseInt( String( shadow[ f.k ] ?? '' ), 10 ) || 0 } min={ -100 } max={ 100 } onChange={ ( v: number ) => setShadow( { [ f.k ]: String( v ) } ) } />
					) ) }
					<DualColor label={ __( 'Color', 'flexa-block' ) } value={ shadow.color || {} } onChange={ ( v ) => setShadow( { color: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Caption panel — whether/where to show each image's caption, its alignment,
 * colours and typography.
 */
export const GalleryCaptionPanel = ( { attributes, setAttributes }: GPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { caption, captionColor, captionBackground } = attributes;
	const cap: GalleryCaptionAttr = caption || {};
	const setCaption = ( patch: Partial< GalleryCaptionAttr > ) => setAttributes( { caption: { ...cap, ...patch } } );
	const typo = rawDevice( attributes.captionTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { captionTypography: patchDevice( attributes.captionTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Caption', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Show captions', 'flexa-block' ) } checked={ !! cap.show } onChange={ ( v: boolean ) => setCaption( { show: v } ) } />
			{ cap.show && (
				<>
					<Segmented
						label={ __( 'Display', 'flexa-block' ) }
						value={ cap.display || 'overlay' }
						onChange={ ( v ) => setCaption( { display: v as GalleryCaptionAttr[ 'display' ] } ) }
						options={ [
							{ value: 'overlay', label: __( 'Overlay', 'flexa-block' ) },
							{ value: 'below', label: __( 'Below', 'flexa-block' ) },
						] }
					/>
					{ cap.display === 'overlay' && (
						<>
							<Segmented
								label={ __( 'Position', 'flexa-block' ) }
								value={ cap.position || 'bottom' }
								onChange={ ( v ) => setCaption( { position: v as GalleryCaptionAttr[ 'position' ] } ) }
								options={ [
									{ value: 'top', label: __( 'Top', 'flexa-block' ) },
									{ value: 'center', label: __( 'Center', 'flexa-block' ) },
									{ value: 'bottom', label: __( 'Bottom', 'flexa-block' ) },
								] }
							/>
							<Segmented
								label={ __( 'Visibility', 'flexa-block' ) }
								value={ cap.visibility || 'always' }
								onChange={ ( v ) => setCaption( { visibility: v as GalleryCaptionAttr[ 'visibility' ] } ) }
								options={ [
									{ value: 'always', label: __( 'Always', 'flexa-block' ) },
									{ value: 'hover', label: __( 'On hover', 'flexa-block' ) },
								] }
							/>
						</>
					) }
					<div className="flexa-field">
						<Segmented
							label={ __( 'Text Align', 'flexa-block' ) }
							value={ cap.alignment || 'center' }
							onChange={ ( v ) => setCaption( { alignment: v as GalleryCaptionAttr[ 'alignment' ] } ) }
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
 * Interaction panel — what happens on click plus the lightbox appearance.
 */
export const GalleryInteractionPanel = ( { attributes, setAttributes }: GPanelProps ): JSX.Element => {
	const { clickAction, lightbox } = attributes;
	const lb = lightbox || {};
	const setLightbox = ( patch: Partial< ImagesGalleryAttributes[ 'lightbox' ] > ) => setAttributes( { lightbox: { ...lb, ...patch } } );

	return (
		<PanelBody title={ __( 'Interaction', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'On Click', 'flexa-block' ) }
				value={ clickAction || 'lightbox' }
				onChange={ ( v ) => setAttributes( { clickAction: v as ImagesGalleryAttributes[ 'clickAction' ] } ) }
				options={ [
					{ value: 'none', label: __( 'None', 'flexa-block' ) },
					{ value: 'lightbox', label: __( 'Lightbox', 'flexa-block' ) },
					{ value: 'media', label: __( 'Media', 'flexa-block' ) },
				] }
			/>
			{ clickAction === 'lightbox' && (
				<>
					<DualColor label={ __( 'Overlay Background', 'flexa-block' ) } value={ lb.background || {} } onChange={ ( v ) => setLightbox( { background: v } ) } />
					<ToggleControl __nextHasNoMarginBottom label={ __( 'Show caption in lightbox', 'flexa-block' ) } checked={ lb.showCaption !== false } onChange={ ( v: boolean ) => setLightbox( { showCaption: v } ) } />
				</>
			) }
		</PanelBody>
	);
};
