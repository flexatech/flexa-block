/**
 * Lottie Animation block — editor component.
 *
 * Assembles the block-specific panels (source / playback / size) with the shared
 * inspector panels (@components). The canvas plays the real animation live via
 * lottie-web, using the same loader as the front end so `.json` and `.lottie`
 * sources render identically. A static placeholder shows while there is no
 * source, or if it can't be read. The preview mirrors the front-end size,
 * alignment and background so what you lay out is what ships.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import lottie from 'lottie-web';
import { InspectorControls, useBlockProps, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, RangeControl, TextControl, SelectControl, BaseControl, Button, Flex } from '@wordpress/components';

import {
	InspectorTabs,
	Segmented,
	SliderUnit,
	DualColor,
	BackgroundPanel,
	SpacingPanel,
	BorderPanel,
	ShadowPanel,
	PositionPanel,
	VisibilityPanel,
	AnimationPanel,
	CONTENT_ALIGN_OPTIONS,
	useDevice,
	ExamplePreviewSkeleton,
} from '@components';
import {
	cn,
	effective,
	rawDevice,
	patchDevice,
	withUnit,
	spacingShorthand,
	visibilityClasses,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	CONTENT_ALIGN_TO_FLEX,
	LENGTH_UNITS,
} from '@utils';
import { BLOCK_ICONS } from '@shared/block-icons';
import { loadLottieData } from './load';
import type { DeviceKey, EditProps, LengthValue, LottieAttributes, PanelProps } from '../../types';

type CssProps = Record< string, string >;
type Props = PanelProps< LottieAttributes >;

/** The play-trigger choices (six values → SelectControl). */
const PLAY_ON_OPTIONS = [
	{ value: 'autoplay', label: __( 'Autoplay', 'flexa-block' ) },
	{ value: 'none', label: __( 'None', 'flexa-block' ) },
	{ value: 'hover', label: __( 'On hover', 'flexa-block' ) },
	{ value: 'click', label: __( 'On click', 'flexa-block' ) },
	{ value: 'scroll', label: __( 'On scroll', 'flexa-block' ) },
	{ value: 'viewport', label: __( 'When visible', 'flexa-block' ) },
];

/**
 * Source panel — where the animation data comes from: an uploaded JSON/.lottie
 * file, or a remote URL.
 */
const SourcePanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const { source, fileUrl, url } = attributes;
	const isFile = ( source || 'file' ) === 'file';

	return (
		<PanelBody title={ __( 'Source', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Source', 'flexa-block' ) }
				value={ source || 'file' }
				onChange={ ( v ) => setAttributes( { source: v as LottieAttributes[ 'source' ] } ) }
				options={ [
					{ value: 'file', label: __( 'File', 'flexa-block' ) },
					{ value: 'url', label: __( 'URL', 'flexa-block' ) },
				] }
			/>
			{ isFile ? (
				<BaseControl
					__nextHasNoMarginBottom
					help={ __( 'Upload a Lottie JSON or .lottie file.', 'flexa-block' ) }
				>
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'application/json', 'application/zip' ] }
							value={ attributes.fileId ?? undefined }
							onSelect={ ( m: { id: number; url: string } ) => setAttributes( { fileUrl: m.url, fileId: m.id } ) }
							render={ ( { open }: { open: () => void } ) => (
								<Flex gap={ 2 } justify="flex-start">
									<Button variant="secondary" onClick={ open }>
										{ fileUrl ? __( 'Replace File', 'flexa-block' ) : __( 'Upload File', 'flexa-block' ) }
									</Button>
									{ fileUrl && (
										<Button isDestructive variant="tertiary" onClick={ () => setAttributes( { fileUrl: '', fileId: null } ) }>
											{ __( 'Remove', 'flexa-block' ) }
										</Button>
									) }
								</Flex>
							) }
						/>
					</MediaUploadCheck>
				</BaseControl>
			) : (
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Animation URL', 'flexa-block' ) }
					value={ url || '' }
					placeholder="https://example.com/animation.json"
					onChange={ ( v: string ) => setAttributes( { url: v } ) }
				/>
			) }
		</PanelBody>
	);
};

/**
 * Playback panel — loop, speed, direction and the play trigger.
 */
const PlaybackPanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const { loop, speed, reverse, playOn } = attributes;

	return (
		<PanelBody title={ __( 'Playback', 'flexa-block' ) } initialOpen={ false }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Play', 'flexa-block' ) }
				value={ playOn || 'autoplay' }
				options={ PLAY_ON_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { playOn: v as LottieAttributes[ 'playOn' ] } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Loop', 'flexa-block' ) }
				checked={ loop !== false }
				onChange={ ( v: boolean ) => setAttributes( { loop: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Play in reverse', 'flexa-block' ) }
				checked={ reverse === true }
				onChange={ ( v: boolean ) => setAttributes( { reverse: v } ) }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				label={ __( 'Speed', 'flexa-block' ) }
				value={ speed ?? 1 }
				min={ 0.25 }
				max={ 3 }
				step={ 0.25 }
				onChange={ ( v?: number ) => setAttributes( { speed: v ?? 1 } ) }
			/>
		</PanelBody>
	);
};

/**
 * Size panel — the animation box's per-device width, height and alignment.
 */
const SizePanel = ( { attributes, setAttributes }: Props ): JSX.Element => {
	const [ device ] = useDevice();
	const { width, height, alignment } = attributes;
	const w = rawDevice( width, device );
	const h = rawDevice( height, device );
	const alignVal = alignment?.[ device ] || ( device === 'desktop' ? 'center' : '' );

	return (
		<PanelBody title={ __( 'Size', 'flexa-block' ) } initialOpen={ false }>
			<SliderUnit
				label={ __( 'Width', 'flexa-block' ) }
				value={ w }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 2000, '%': 100, vw: 100, em: 100, rem: 100, vh: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { width: patchDevice( width, device, v ) } ) }
			/>
			<SliderUnit
				label={ __( 'Height', 'flexa-block' ) }
				value={ h }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 2000, '%': 100, vw: 100, em: 100, rem: 100, vh: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { height: patchDevice( height, device, v ) } ) }
			/>
			<div className="flexa-field">
				<Segmented
					label={ __( 'Alignment', 'flexa-block' ) }
					value={ alignVal }
					responsive
					onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
					options={ CONTENT_ALIGN_OPTIONS }
				/>
			</div>
		</PanelBody>
	);
};

/** Wrapper preview: spacing + background + border + shadow + alignment + advanced. */
const buildWrapperStyle = ( attributes: LottieAttributes, device: DeviceKey ): CssProps => {
	const { spacing, background, border, boxShadow, advancedLayout, alignment } = attributes;
	const sp = effective( spacing, device );
	const b = effective( border, device );
	const adv = effective( advancedLayout, device );
	const s: CssProps = {};

	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	const alignVal = alignment?.[ device ] || ( device === 'desktop' ? 'center' : '' );
	if ( alignVal && CONTENT_ALIGN_TO_FLEX[ alignVal ] ) s.justifyContent = CONTENT_ALIGN_TO_FLEX[ alignVal ];

	applyBackgroundPreview( s, background );
	applyBorderPreview( s, b );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	if ( adv.overflow ) s.overflow = adv.overflow;
	if ( adv.position ) s.position = adv.position;

	return s;
};

/** Player box preview: width + height. */
const buildPlayerStyle = ( attributes: LottieAttributes, device: DeviceKey ): CssProps => {
	const w = effective( attributes.width, device );
	const h = effective( attributes.height, device );
	const s: CssProps = {};
	if ( w.value ) s.width = withUnit( w.value, w.unit || 'px' );
	if ( h.value ) s.height = withUnit( h.value, h.unit || 'px' );
	return s;
};

/** Trim a URL down to its file name for the placeholder label. */
const fileName = ( src: string ): string => {
	if ( ! src ) {
		return '';
	}
	const clean = src.split( /[?#]/ )[ 0 ];
	return clean.substring( clean.lastIndexOf( '/' ) + 1 ) || clean;
};

/**
 * Lottie Animation edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< LottieAttributes > ): JSX.Element {
	const { source, fileUrl, url, loop, speed, reverse, className, responsiveVisibility } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useEffect( () => {
		const expected = clientId.replace( /[^a-z0-9]/gi, '' ).slice( 0, 8 );
		if ( ! blockId || blockId !== expected ) {
			setAttributes( { blockId: expected } );
		}
	}, [ blockId, clientId, setAttributes ] );

	const src = ( source || 'file' ) === 'file' ? fileUrl || '' : url || '';

	// Live preview: lazy-load lottie-web + the shared source loader and play the
	// animation into the box, mirroring the front end. Re-runs when the source or
	// playback options change; `failed` falls the canvas back to the placeholder.
	const playerRef = useRef< HTMLDivElement >( null );
	const [ failed, setFailed ] = useState( false );
	useEffect( () => {
		const el = playerRef.current;
		if ( ! el || ! src ) {
			return;
		}
		let cancelled = false;
		let anim: { destroy: () => void } | null = null;
		setFailed( false );
		loadLottieData( src )
			.then( ( animationData ) => {
				if ( cancelled || ! playerRef.current ) {
					return;
				}
				const created = lottie.loadAnimation( {
					container: playerRef.current,
					renderer: 'svg',
					loop: loop !== false,
					autoplay: true,
					animationData,
				} );
				anim = created;
				created.addEventListener( 'DOMLoaded', () => {
					created.setSpeed( speed && speed > 0 ? speed : 1 );
					created.setDirection( reverse ? -1 : 1 );
				} );
			} )
			.catch( () => {
				if ( ! cancelled ) {
					setFailed( true );
				}
			} );
		return () => {
			cancelled = true;
			if ( anim ) {
				anim.destroy();
			}
		};
	}, [ src, loop, speed, reverse ] );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-lottie',
			blockId && `flexa-lottie-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Mirror the generator's wrapper :hover rule so the editor previews the hover
	// background (inline React styles can't express :hover). Light value only.
	const hoverCss = blockId
		? editorCss( [
			{ selector: `.flexa-lottie-${ blockId }:hover`, prop: 'background-color', value: attributes.backgroundColorHover?.light },
		] )
		: '';

	const inspector = (
		<InspectorControls>
			<div className="flexa-inspector flexa-lottie-inspector">
				<InspectorTabs
					layout={
						<>
							<SourcePanel attributes={ attributes } setAttributes={ setAttributes } />
							<PlaybackPanel attributes={ attributes } setAttributes={ setAttributes } />
							<SizePanel attributes={ attributes } setAttributes={ setAttributes } />
							<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
						</>
					}
					style={
						<>
							<BackgroundPanel attributes={ attributes } setAttributes={ setAttributes } />
							<PanelBody title={ __( 'Background Hover', 'flexa-block' ) } initialOpen={ false }>
								<DualColor
									label={ __( 'Hover Color', 'flexa-block' ) }
									value={ attributes.backgroundColorHover || {} }
									onChange={ ( v ) => setAttributes( { backgroundColorHover: v } ) }
								/>
							</PanelBody>
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

	// Inserter hover-preview → faint skeleton mock-up instead of the placeholder.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="image" />
			</div>
		);
	}

	return (
		<>
			{ inspector }
			<div { ...blockProps }>
				{ hoverCss && <style>{ hoverCss }</style> }
				{ src && ! failed ? (
					/* Live preview — plays the real animation via lottie-web (view.ts mirror). */
					<div ref={ playerRef } className="flexa-lottie__player" style={ buildPlayerStyle( attributes, device ) } />
				) : (
					/* No source yet, or it couldn't be read — show the placeholder. */
					<div className="flexa-lottie__player flexa-lottie__placeholder" style={ buildPlayerStyle( attributes, device ) }>
						<span className="flexa-lottie__placeholder-icon">{ BLOCK_ICONS[ 'lottie' ].src }</span>
						<span className="flexa-lottie__placeholder-label">
							{ failed ? __( 'Could not read this Lottie source', 'flexa-block' ) : __( 'Lottie animation', 'flexa-block' ) }
						</span>
						{ src && <span className="flexa-lottie__placeholder-src">{ fileName( src ) }</span> }
					</div>
				) }
			</div>
		</>
	);
}
