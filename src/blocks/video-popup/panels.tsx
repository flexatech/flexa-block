/**
 * Video Popup block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) come from @components; these cover the parts unique to the video
 * popup: the video source + URL, the trigger (thumbnail / button / text) and its
 * play icon, the aspect ratio, autoplay and the lightbox backdrop. Following the
 * "prefer theme styles" rule nothing is styled by default — only values the user
 * picks produce CSS.
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
	IconPicker,
	useDevice,
	CONTENT_ALIGN_OPTIONS,
} from '@components';
import { rawDevice, patchDevice, LENGTH_UNITS, WEIGHT_UNITS } from '@utils';
import type { ImageMedia, LengthValue, PanelProps, VideoPopupAttributes } from '../../types';

type Props = PanelProps< VideoPopupAttributes >;

const EMPTY_MEDIA: ImageMedia = { id: null, url: '', alt: '', width: null, height: null };

/** The three video providers (short list → SelectControl). */
const SOURCE_OPTIONS = [
	{ value: 'youtube', label: __( 'YouTube', 'flexa-block' ) },
	{ value: 'vimeo', label: __( 'Vimeo', 'flexa-block' ) },
	{ value: 'mp4', label: __( 'MP4 File', 'flexa-block' ) },
];

/** Aspect ratios offered for the video frame (short icon-less set → Segmented). */
const ASPECT_OPTIONS = [
	{ value: '16:9', label: '16:9' },
	{ value: '4:3', label: '4:3' },
	{ value: '1:1', label: '1:1' },
];

/**
 * Video panel — where the video comes from, its URL, the frame ratio and whether
 * it autoplays when the popup opens.
 */
export const VideoPanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const { videoSource, videoUrl, aspectRatio, autoplay } = attributes;
	const source = videoSource || 'youtube';

	const placeholder =
		source === 'vimeo'
			? 'https://vimeo.com/76979871'
			: source === 'mp4'
				? 'https://example.com/video.mp4'
				: 'https://www.youtube.com/watch?v=…';

	return (
		<PanelBody title={ __( 'Video', 'flexa-block' ) } initialOpen={ true }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Source', 'flexa-block' ) }
				value={ source }
				options={ SOURCE_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { videoSource: v as VideoPopupAttributes[ 'videoSource' ] } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				label={ source === 'mp4' ? __( 'MP4 URL', 'flexa-block' ) : __( 'Video URL', 'flexa-block' ) }
				value={ videoUrl || '' }
				placeholder={ placeholder }
				onChange={ ( v: string ) => setAttributes( { videoUrl: v } ) }
			/>
			{ source === 'mp4' && (
				<BaseControl
					__nextHasNoMarginBottom
					help={ __( 'Or upload / pick an MP4 from your Media Library.', 'flexa-block' ) }
				>
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'video' ] }
							onSelect={ ( m: { url: string } ) => setAttributes( { videoUrl: m.url } ) }
							render={ ( { open }: { open: () => void } ) => (
								<Flex gap={ 2 } justify="flex-start">
									<Button variant="secondary" onClick={ open }>
										{ videoUrl ? __( 'Replace Video', 'flexa-block' ) : __( 'Upload Video', 'flexa-block' ) }
									</Button>
									{ videoUrl && (
										<Button isDestructive variant="tertiary" onClick={ () => setAttributes( { videoUrl: '' } ) }>
											{ __( 'Remove', 'flexa-block' ) }
										</Button>
									) }
								</Flex>
							) }
						/>
					</MediaUploadCheck>
				</BaseControl>
			) }
			<Segmented
				label={ __( 'Aspect Ratio', 'flexa-block' ) }
				value={ aspectRatio || '16:9' }
				onChange={ ( v ) => setAttributes( { aspectRatio: v as VideoPopupAttributes[ 'aspectRatio' ] } ) }
				options={ ASPECT_OPTIONS }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Autoplay when opened', 'flexa-block' ) }
				help={ __( 'Start the video automatically once the popup opens (or, for inline, right after it is clicked).', 'flexa-block' ) }
				checked={ autoplay !== false }
				onChange={ ( v: boolean ) => setAttributes( { autoplay: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Trigger panel — what the visitor clicks to open the popup: a thumbnail image,
 * a button or a text link, plus each mode's own content (thumbnail + scrim,
 * button label, link text).
 */
export const TriggerPanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const { displayMode, trigger, thumbnailImage, buttonText, linkText, scrimOpacity } = attributes;
	const display = displayMode || 'popup';
	const isInline = display === 'inline';
	const mode = trigger || 'thumbnail';
	// Inline mode always uses the thumbnail façade (it turns into the player in place);
	// the button / text triggers only make sense for the popup.
	const showThumb = isInline || mode === 'thumbnail';

	return (
		<PanelBody title={ __( 'Trigger', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Display', 'flexa-block' ) }
				value={ display }
				onChange={ ( v ) => setAttributes( { displayMode: v as VideoPopupAttributes[ 'displayMode' ] } ) }
				options={ [
					{ value: 'popup', label: __( 'Popup', 'flexa-block' ) },
					{ value: 'inline', label: __( 'Inline', 'flexa-block' ) },
				] }
			/>

			{ ! isInline && (
				<Segmented
					label={ __( 'Type', 'flexa-block' ) }
					value={ mode }
					onChange={ ( v ) => setAttributes( { trigger: v as VideoPopupAttributes[ 'trigger' ] } ) }
					options={ [
						{ value: 'thumbnail', label: __( 'Thumbnail', 'flexa-block' ) },
						{ value: 'button', label: __( 'Button', 'flexa-block' ) },
						{ value: 'text', label: __( 'Text', 'flexa-block' ) },
					] }
				/>
			) }

			{ showThumb && (
				<>
					<BaseControl
						__nextHasNoMarginBottom
						label={ __( 'Thumbnail', 'flexa-block' ) }
						help={ __( 'Leave empty to use the YouTube thumbnail automatically (YouTube videos only).', 'flexa-block' ) }
					>
						<MediaUploadCheck>
							<MediaUpload
								allowedTypes={ [ 'image' ] }
								value={ thumbnailImage?.id ?? undefined }
								onSelect={ ( m: { id: number; url: string; alt?: string; width?: number; height?: number } ) =>
									setAttributes( { thumbnailImage: { id: m.id, url: m.url, alt: m.alt || '', width: m.width ?? null, height: m.height ?? null } } )
								}
								render={ ( { open }: { open: () => void } ) => (
									<>
										{ thumbnailImage?.url && (
											<button type="button" className="flexa-bg-preview" onClick={ open } aria-label={ __( 'Replace image', 'flexa-block' ) }>
												<img src={ thumbnailImage.url } alt="" />
											</button>
										) }
										<Flex gap={ 2 } justify="flex-start">
											<Button variant="secondary" onClick={ open }>
												{ thumbnailImage?.url ? __( 'Replace', 'flexa-block' ) : __( 'Select Image', 'flexa-block' ) }
											</Button>
											{ thumbnailImage?.url && (
												<Button isDestructive variant="tertiary" onClick={ () => setAttributes( { thumbnailImage: { ...EMPTY_MEDIA } } ) }>
													{ __( 'Remove', 'flexa-block' ) }
												</Button>
											) }
										</Flex>
									</>
								) }
							/>
						</MediaUploadCheck>
					</BaseControl>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Overlay Darkness', 'flexa-block' ) }
						value={ scrimOpacity ?? 30 }
						min={ 0 }
						max={ 100 }
						onChange={ ( v?: number ) => setAttributes( { scrimOpacity: v ?? 30 } ) }
					/>
				</>
			) }

			{ ! isInline && mode === 'button' && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Button Text', 'flexa-block' ) }
					value={ buttonText ?? 'Play Video' }
					onChange={ ( v: string ) => setAttributes( { buttonText: v } ) }
				/>
			) }

			{ ! isInline && mode === 'text' && (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Link Text', 'flexa-block' ) }
					value={ linkText ?? 'Watch the video' }
					onChange={ ( v: string ) => setAttributes( { linkText: v } ) }
				/>
			) }
		</PanelBody>
	);
};

/**
 * Play icon panel — the glyph shown on the trigger, its size and its colours.
 */
export const PlayIconPanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const { playIcon, playIconSize, playIconColor, playIconBackground } = attributes;

	return (
		<PanelBody title={ __( 'Play Icon', 'flexa-block' ) } initialOpen={ false }>
			<IconPicker label={ __( 'Icon', 'flexa-block' ) } value={ playIcon || {} } onChange={ ( v ) => setAttributes( { playIcon: v } ) } />
			<SliderUnit
				label={ __( 'Icon Size', 'flexa-block' ) }
				value={ playIconSize || {} }
				units={ WEIGHT_UNITS }
				defaultUnit="px"
				min={ 16 }
				max={ { px: 160 } }
				onChange={ ( v: LengthValue ) => setAttributes( { playIconSize: v } ) }
			/>
			<DualColor label={ __( 'Icon Color', 'flexa-block' ) } value={ playIconColor || {} } onChange={ ( v ) => setAttributes( { playIconColor: v } ) } />
			<DualColor label={ __( 'Icon Background', 'flexa-block' ) } value={ playIconBackground || {} } onChange={ ( v ) => setAttributes( { playIconBackground: v } ) } />
		</PanelBody>
	);
};

/**
 * Size panel — the trigger's max width and its alignment on the page.
 */
export const SizePanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const [ device ] = useDevice();
	const { maxWidth, align } = attributes;
	const width = rawDevice( maxWidth, device );
	const alignVal = align?.[ device ] || ( device === 'desktop' ? 'center' : '' );

	return (
		<PanelBody title={ __( 'Size', 'flexa-block' ) } initialOpen={ false }>
			<SliderUnit
				label={ __( 'Max Width', 'flexa-block' ) }
				value={ width }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 2000, '%': 100, vw: 100, em: 100, rem: 100, vh: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { maxWidth: patchDevice( maxWidth, device, v ) } ) }
			/>
			<div className="flexa-field">
				<Segmented
					label={ __( 'Alignment', 'flexa-block' ) }
					value={ alignVal }
					responsive
					onChange={ ( v ) => setAttributes( { align: { ...align, [ device ]: v } } ) }
					options={ CONTENT_ALIGN_OPTIONS }
				/>
			</div>
		</PanelBody>
	);
};

/**
 * Popup panel — the lightbox backdrop behind the playing video.
 */
export const PopupPanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const { overlayColor, overlayOpacity } = attributes;

	return (
		<PanelBody title={ __( 'Popup Backdrop', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Backdrop Color', 'flexa-block' ) } value={ overlayColor || {} } onChange={ ( v ) => setAttributes( { overlayColor: v } ) } />
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Backdrop Opacity', 'flexa-block' ) }
				value={ overlayOpacity ?? 90 }
				min={ 0 }
				max={ 100 }
				onChange={ ( v?: number ) => setAttributes( { overlayOpacity: v ?? 90 } ) }
			/>
		</PanelBody>
	);
};
