/**
 * Before / After block — editor component.
 *
 * Assembles the block-specific panels (./panels) with the shared inspector
 * panels (@components). The canvas mirrors the front-end markup — after image in
 * flow, before image clipped to the handle position — with an inline preview
 * that reuses the same style.scss rules, so what you build is what ships. The
 * drag/hover interaction itself is a front-end concern (view.ts); the editor
 * shows the split at its start position.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { InspectorControls, MediaPlaceholder, useBlockProps } from '@wordpress/block-editor';

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
import { cn, effective, rawDevice, withUnit, spacingShorthand, visibilityClasses, applyBackgroundPreview, applyBorderPreview, boxShadowPreview } from '@utils';
import { MediaPanel, ComparePanel, SizePanel, HandlePanel } from './panels';
import type { BeforeAfterAttributes, DeviceKey, EditProps, ImageMedia } from '../../types';

type CssProps = Record< string, string >;

/** The double-arrow glyph inside the drag grip (rotated 90° in vertical mode by CSS). */
const HandleArrows = (): JSX.Element => (
	<svg className="flexa-before-after__arrows" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
		<path d="M9.5 7 5 12l4.5 5M14.5 7l4.5 5-4.5 5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
	</svg>
);

/** Outer wrapper preview: spacing + background. */
const buildWrapperStyle = ( attributes: BeforeAfterAttributes, device: DeviceKey ): CssProps => {
	const { spacing, background } = attributes;
	const sp = effective( spacing, device );
	const s: CssProps = {};
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;
	applyBackgroundPreview( s, background );
	return s;
};

/** Frame preview: width, alignment, aspect-ratio, min-height, border, shadow + CSS vars. */
const buildFrameStyle = ( attributes: BeforeAfterAttributes, device: DeviceKey ): CssProps => {
	const { maxWidth, align, size, aspectRatio, border, boxShadow, initialPosition, handleSize, dividerWidth } = attributes;
	const w = rawDevice( maxWidth, device );
	const b = effective( border, device );
	const minH = rawDevice( size, device ).minHeight || {};
	const s: CssProps = {};

	if ( w.value ) s.maxWidth = withUnit( w.value, w.unit || 'px' );

	const alignVal = align?.[ device ] || ( device === 'desktop' ? 'center' : '' );
	if ( alignVal === 'left' ) {
		s.marginRight = 'auto';
	} else if ( alignVal === 'right' ) {
		s.marginLeft = 'auto';
	} else if ( alignVal === 'center' ) {
		s.marginLeft = 'auto';
		s.marginRight = 'auto';
	}

	if ( aspectRatio?.enabled && aspectRatio.ratio ) s.aspectRatio = aspectRatio.ratio;
	if ( minH.value ) s.minHeight = withUnit( minH.value, minH.unit || 'px' );

	applyBorderPreview( s, b );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	s[ '--flexa-ba-pos' ] = `${ Math.min( 100, Math.max( 0, initialPosition ?? 50 ) ) }%`;
	if ( handleSize?.value ) s[ '--flexa-ba-handle-size' ] = withUnit( handleSize.value, 'px' );
	if ( dividerWidth?.value ) s[ '--flexa-ba-line-width' ] = withUnit( dividerWidth.value, 'px' );

	return s;
};

/** One corner label's preview colours. */
const labelStyle = ( attributes: BeforeAfterAttributes ): CssProps => {
	const s: CssProps = {};
	if ( attributes.labelColor?.light ) s.color = attributes.labelColor.light;
	if ( attributes.labelBackground?.light ) s.background = attributes.labelBackground.light;
	return s;
};

/**
 * Before / After edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< BeforeAfterAttributes > ): JSX.Element {
	const {
		beforeImage,
		afterImage,
		beforeAlt,
		afterAlt,
		objectFit,
		orientation,
		showLabels,
		beforeLabel,
		afterLabel,
		handleColor,
		className,
		responsiveVisibility,
	} = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useEffect( () => {
		const expected = clientId.replace( /[^a-z0-9]/gi, '' ).slice( 0, 8 );
		if ( ! blockId || blockId !== expected ) {
			setAttributes( { blockId: expected } );
		}
	}, [ blockId, clientId, setAttributes ] );

	const orient = orientation || 'horizontal';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-before-after',
			`flexa-before-after--${ orient }`,
			blockId && `flexa-before-after-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	const inspector = (
		<InspectorControls>
			<div className="flexa-inspector flexa-before-after-inspector">
				<InspectorTabs
					layout={
						<>
							<MediaPanel attributes={ attributes } setAttributes={ setAttributes } />
							<ComparePanel attributes={ attributes } setAttributes={ setAttributes } />
							<SizePanel attributes={ attributes } setAttributes={ setAttributes } />
							<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
						</>
					}
					style={
						<>
							<HandlePanel attributes={ attributes } setAttributes={ setAttributes } />
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

	// Inserter hover-preview → faint skeleton mock-up instead of the real slider.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="before-after" />
			</div>
		);
	}

	const beforeUrl = beforeImage?.url || '';
	const afterUrl = afterImage?.url || '';

	// Until both images are chosen, show a setup screen with a picker for each.
	if ( ! beforeUrl || ! afterUrl ) {
		const onBefore = ( m: { id: number; url: string; alt?: string; width?: number; height?: number } ) =>
			setAttributes( { beforeImage: { id: m.id, url: m.url, alt: m.alt || '', width: m.width ?? null, height: m.height ?? null } as ImageMedia } );
		const onAfter = ( m: { id: number; url: string; alt?: string; width?: number; height?: number } ) =>
			setAttributes( { afterImage: { id: m.id, url: m.url, alt: m.alt || '', width: m.width ?? null, height: m.height ?? null } as ImageMedia } );

		return (
			<>
				{ inspector }
				<div { ...blockProps }>
					<div className="flexa-before-after__setup">
						<MediaPlaceholder
							accept="image/*"
							allowedTypes={ [ 'image' ] }
							onSelect={ onBefore }
							labels={ { title: __( 'Before image', 'flexa-block' ), instructions: beforeUrl ? __( 'Selected.', 'flexa-block' ) : __( 'Upload or pick the “before” image.', 'flexa-block' ) } }
						/>
						<MediaPlaceholder
							accept="image/*"
							allowedTypes={ [ 'image' ] }
							onSelect={ onAfter }
							labels={ { title: __( 'After image', 'flexa-block' ), instructions: afterUrl ? __( 'Selected.', 'flexa-block' ) : __( 'Upload or pick the “after” image.', 'flexa-block' ) } }
						/>
					</div>
				</div>
			</>
		);
	}

	const frameStyle = buildFrameStyle( attributes, device );
	const imgStyle: CssProps = objectFit ? { objectFit } : {};
	const handleTint: CssProps = handleColor?.light ? { color: handleColor.light } : {};
	const lblStyle = labelStyle( attributes );
	const withLabels = showLabels !== false;

	// Mirror view.js: each label fades as its own image shrinks toward zero.
	const clamp01 = ( n: number ): number => Math.max( 0, Math.min( 1, n ) );
	const pos = Math.min( 100, Math.max( 0, attributes.initialPosition ?? 50 ) );
	const beforeLblStyle: CssProps = { ...lblStyle, opacity: String( clamp01( ( pos - 3 ) / 10 ) ) };
	const afterLblStyle: CssProps = { ...lblStyle, opacity: String( clamp01( ( 97 - pos ) / 10 ) ) };

	return (
		<>
			{ inspector }
			<div { ...blockProps }>
				<div className="flexa-before-after__frame" style={ frameStyle }>
					<div className="flexa-before-after__img-wrap flexa-before-after__img-wrap--after">
						<img className="flexa-before-after__img" src={ afterUrl } alt={ afterAlt || afterImage?.alt || '' } style={ imgStyle } />
					</div>
					<div className="flexa-before-after__img-wrap flexa-before-after__img-wrap--before">
						<img className="flexa-before-after__img" src={ beforeUrl } alt={ beforeAlt || beforeImage?.alt || '' } style={ imgStyle } />
					</div>
					{ /* Labels live on the frame (not the clipped layers); each fades as its image shrinks. */ }
					{ withLabels && (
						<span className="flexa-before-after__label flexa-before-after__label--before" style={ beforeLblStyle }>
							{ beforeLabel ?? 'Before' }
						</span>
					) }
					{ withLabels && (
						<span className="flexa-before-after__label flexa-before-after__label--after" style={ afterLblStyle }>
							{ afterLabel ?? 'After' }
						</span>
					) }
					<div className="flexa-before-after__handle" style={ handleTint }>
						<span className="flexa-before-after__line" />
						<span className="flexa-before-after__grip">
							<HandleArrows />
						</span>
					</div>
				</div>
			</div>
		</>
	);
}
