/**
 * Video Popup block — editor component.
 *
 * Assembles the block-specific panels (./panels) with the shared inspector
 * panels (@components). The canvas mirrors the front-end trigger — a thumbnail
 * with a play button, a button, or a text link — using the same style.scss
 * rules, so what you build is what ships. The lightbox itself is a front-end
 * concern (view.ts); the editor never opens the popup.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
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
	ExamplePreviewSkeleton,
} from '@components';
import {
	cn,
	effective,
	rawDevice,
	withUnit,
	spacingShorthand,
	visibilityClasses,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	CONTENT_ALIGN_TO_FLEX,
} from '@utils';
import { VideoPanel, TriggerPanel, PlayIconPanel, SizePanel, PopupPanel } from './panels';
import type { DeviceKey, EditProps, VideoPopupAttributes } from '../../types';

type CssProps = Record< string, string >;

/** CSS aspect ratios keyed by the block's colon-form choice. */
const RATIO_MAP: Record< string, string > = { '16:9': '16/9', '4:3': '4/3', '1:1': '1/1' };

/** The default play glyph — a filled triangle (mirrors render.php's fallback). */
const PlayGlyph = (): JSX.Element => (
	<svg className="flexa-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
		<path d="M8 5v14l11-7z" fill="currentColor" />
	</svg>
);

/** Extract a YouTube video id from a watch / short / embed / youtu.be URL. */
const youtubeId = ( url: string ): string => {
	const m = url.match( /(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/|v\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/ );
	return m ? m[ 1 ] : '';
};

/** Outer wrapper preview: spacing + background + alignment + icon-size var. */
const buildWrapperStyle = ( attributes: VideoPopupAttributes, device: DeviceKey ): CssProps => {
	const { spacing, background, align, playIconSize } = attributes;
	const sp = effective( spacing, device );
	const s: CssProps = {};
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;
	const alignVal = align?.[ device ] || ( device === 'desktop' ? 'center' : '' );
	if ( alignVal && CONTENT_ALIGN_TO_FLEX[ alignVal ] ) s.alignItems = CONTENT_ALIGN_TO_FLEX[ alignVal ];
	if ( playIconSize?.value ) s[ '--flexa-vp-icon-size' ] = withUnit( playIconSize.value, 'px' );
	applyBackgroundPreview( s, background );
	return s;
};

/** Trigger frame preview: width, border, shadow (targets the `__trigger` element). */
const buildFrameStyle = ( attributes: VideoPopupAttributes, device: DeviceKey ): CssProps => {
	const { maxWidth, border, boxShadow } = attributes;
	const w = rawDevice( maxWidth, device );
	const b = effective( border, device );
	const s: CssProps = {};

	if ( w.value ) s.maxWidth = withUnit( w.value, w.unit || 'px' );
	applyBorderPreview( s, b );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	return s;
};

/** Media (thumbnail) preview: aspect ratio only. */
const buildMediaStyle = ( attributes: VideoPopupAttributes ): CssProps => {
	const s: CssProps = {};
	if ( attributes.aspectRatio && RATIO_MAP[ attributes.aspectRatio ] ) s.aspectRatio = RATIO_MAP[ attributes.aspectRatio ];
	return s;
};

/** The play button's preview colours. */
const playStyle = ( attributes: VideoPopupAttributes ): CssProps => {
	const s: CssProps = {};
	if ( attributes.playIconColor?.light ) s.color = attributes.playIconColor.light;
	if ( attributes.playIconBackground?.light ) s.background = attributes.playIconBackground.light;
	return s;
};

/**
 * Video Popup edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< VideoPopupAttributes > ): JSX.Element {
	const { videoSource, videoUrl, displayMode, trigger, thumbnailImage, buttonText, linkText, playIcon, scrimOpacity, className, responsiveVisibility } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	// Fall back from maxresdefault → mqdefault when a video has no maxres thumb
	// (YouTube serves a 120×90 grey image; detected in the <img> onLoad below).
	const [ ytFallback, setYtFallback ] = useState( false );

	useEffect( () => {
		const expected = clientId.replace( /[^a-z0-9]/gi, '' ).slice( 0, 8 );
		if ( ! blockId || blockId !== expected ) {
			setAttributes( { blockId: expected } );
		}
	}, [ blockId, clientId, setAttributes ] );

	// A new video → re-try the high-res thumbnail before falling back again.
	useEffect( () => setYtFallback( false ), [ videoSource, videoUrl ] );

	const isInline = ( displayMode || 'popup' ) === 'inline';
	// Inline always previews as the thumbnail façade (it becomes the player in place).
	const mode = isInline ? 'thumbnail' : trigger || 'thumbnail';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-video-popup',
			`flexa-video-popup--${ mode }`,
			isInline && 'flexa-video-popup--inline',
			blockId && `flexa-video-popup-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	const inspector = (
		<InspectorControls>
			<div className="flexa-inspector flexa-video-popup-inspector">
				<InspectorTabs
					layout={
						<>
							<VideoPanel attributes={ attributes } setAttributes={ setAttributes } />
							<TriggerPanel attributes={ attributes } setAttributes={ setAttributes } />
							<SizePanel attributes={ attributes } setAttributes={ setAttributes } />
							<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
						</>
					}
					style={
						<>
							<PlayIconPanel attributes={ attributes } setAttributes={ setAttributes } />
							{ /* Popup backdrop only applies to the popup/lightbox — hidden for inline. */ }
							{ ! isInline && <PopupPanel attributes={ attributes } setAttributes={ setAttributes } /> }
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

	// Inserter hover-preview → faint skeleton mock-up instead of the real trigger.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="image" />
			</div>
		);
	}

	// Resolve the preview thumbnail: the chosen image, or the YouTube thumbnail
	// (maxresdefault — true 16:9, no baked-in bars — with an mqdefault fallback).
	const ytId = ( videoSource || 'youtube' ) === 'youtube' ? youtubeId( videoUrl || '' ) : '';
	const ytThumb = ytId ? `https://img.youtube.com/vi/${ ytId }/${ ytFallback ? 'mqdefault' : 'maxresdefault' }.jpg` : '';
	const thumbUrl = thumbnailImage?.url || ytThumb;

	const frameStyle = buildFrameStyle( attributes, device );
	const mediaStyle = buildMediaStyle( attributes );
	const iconStyle = playStyle( attributes );
	const scrimStyle: CssProps = { opacity: String( ( scrimOpacity ?? 30 ) / 100 ) };
	const icon = playIcon?.markup ? (
		<span className="flexa-icon-slot" dangerouslySetInnerHTML={ { __html: playIcon.markup } } />
	) : playIcon?.source === 'upload' && playIcon.url ? (
		<img className="flexa-icon" src={ playIcon.url } alt="" />
	) : (
		<PlayGlyph />
	);

	const playButton = (
		<span className="flexa-video-popup__play" style={ iconStyle }>
			{ icon }
		</span>
	);

	return (
		<>
			{ inspector }
			<div { ...blockProps }>
				{ mode === 'thumbnail' && (
					<div className="flexa-video-popup__trigger flexa-video-popup__thumb" style={ frameStyle }>
						<div className="flexa-video-popup__media" style={ mediaStyle }>
							{ thumbUrl ? (
								<img
									className="flexa-video-popup__image"
									src={ thumbUrl }
									alt={ thumbnailImage?.alt || '' }
									onLoad={ ( e ) => {
										// maxres missing → YouTube returns a 120×90 grey image; drop to mqdefault.
										if ( ! thumbnailImage?.url && ! ytFallback && e.currentTarget.naturalWidth > 0 && e.currentTarget.naturalWidth <= 120 ) {
											setYtFallback( true );
										}
									} }
								/>
							) : (
								<span className="flexa-video-popup__placeholder">{ __( 'Video', 'flexa-block' ) }</span>
							) }
							<span className="flexa-video-popup__scrim" style={ scrimStyle } />
							{ playButton }
						</div>
					</div>
				) }

				{ mode === 'button' && (
					<span className="flexa-video-popup__trigger flexa-video-popup__button wp-element-button" style={ frameStyle }>
						{ playButton }
						<span className="flexa-video-popup__button-text">{ buttonText ?? 'Play Video' }</span>
					</span>
				) }

				{ mode === 'text' && (
					<span className="flexa-video-popup__trigger flexa-video-popup__text" style={ frameStyle }>
						{ playButton }
						<span className="flexa-video-popup__text-label">{ linkText ?? 'Watch the video' }</span>
					</span>
				) }
			</div>
		</>
	);
}
