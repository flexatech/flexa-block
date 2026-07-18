/**
 * Heading block — editor component.
 *
 * Assembles the heading-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / visibility.
 * The canvas renders the title (and optional subheading / separator) with an
 * inline preview that mirrors the PHP CSS generator; nothing is styled by
 * default so the heading inherits the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';

import {
	InspectorTabs,
	TypographyPanel,
	TextColorsPanel,
	EffectsPanel,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	VisibilityPanel,
	AnimationPanel,
	useDevice,
	ExamplePreviewSkeleton,
} from '@components';
import { cn, effective, withUnit, spacingShorthand, applyTypography, applyTextStroke, applyTextShadow, applyBackgroundPreview, applyBorderPreview, boxShadowPreview, editorCss, CONTENT_ALIGN_TO_FLEX } from '@utils';
import {
	HeadingContentPanel,
	SubheadingPanel,
	SeparatorPanel,
} from './panels';
import type { DeviceKey, EditProps, HeadingAttributes } from '../../types';

type CssProps = Record< string, string >;

/** Map an alignment keyword to a flex align-self value (for the separator).
 *  Reuses the shared content-align map, adding heading's `justify` case. */
const ALIGN_SELF: Record< string, string > = { ...CONTENT_ALIGN_TO_FLEX, justify: 'flex-start' };

/** Wrapper preview: alignment, gap, spacing, background, border, shadow. */
const buildWrapperStyle = ( attributes: HeadingAttributes, device: DeviceKey ): CssProps => {
	const { alignment, gap, spacing, background, border, boxShadow } = attributes;
	const sp = effective( spacing, device );
	const b = effective( border, device );
	const s: CssProps = {};

	const align = alignment?.[ device ] || ( device === 'desktop' ? 'left' : '' );
	if ( align ) {
		s.textAlign = align;
	}
	if ( gap?.value ) {
		s.gap = withUnit( gap.value, gap.unit || 'px' );
	}

	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, background );
	applyBorderPreview( s, b );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	return s;
};

/** Title preview: typography, colour / gradient text, stroke, shadow, blend. */
const buildTitleStyle = ( attributes: HeadingAttributes, device: DeviceKey ): CssProps => {
	const { typography, textType, textColor, textGradient, textStroke, textShadow, blendMode } = attributes;
	const s: CssProps = {};
	applyTypography( s, effective( typography, device ) );

	if ( textType === 'gradient' && textGradient?.light ) {
		s.backgroundImage = textGradient.light;
		s.WebkitBackgroundClip = 'text';
		s.backgroundClip = 'text';
		s.WebkitTextFillColor = 'transparent';
		s.color = 'transparent';
	} else if ( textType !== 'gradient' && textColor?.light ) {
		s.color = textColor.light;
	}

	applyTextStroke( s, textStroke );
	applyTextShadow( s, textShadow );

	if ( blendMode ) s.mixBlendMode = blendMode;

	return s;
};

/** Subheading preview: typography + colour. */
const buildSubheadingStyle = ( attributes: HeadingAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.subheadingTypography, device ) );
	if ( attributes.subheadingColor?.light ) s.color = attributes.subheadingColor.light;
	return s;
};

/** Separator preview: width, border, alignment, spacing. */
const buildSeparatorStyle = ( attributes: HeadingAttributes, device: DeviceKey ): CssProps => {
	const { separatorWidth, separatorWeight, separatorStyle, separatorColor, separatorSpacing, separatorPosition, alignment } = attributes;
	const s: CssProps = { borderTopStyle: separatorStyle || 'solid' };

	s.width = separatorWidth?.value ? withUnit( separatorWidth.value, separatorWidth.unit || 'px' ) : '80px';
	s.borderTopWidth = separatorWeight?.value ? withUnit( separatorWeight.value, separatorWeight.unit || 'px' ) : '2px';
	if ( separatorColor?.light ) s.borderTopColor = separatorColor.light;

	const align = alignment?.[ device ] || ( device === 'desktop' ? 'left' : '' );
	if ( align ) s.alignSelf = ALIGN_SELF[ align ] || 'flex-start';

	if ( separatorSpacing?.value ) {
		const gap = withUnit( separatorSpacing.value, separatorSpacing.unit || 'px' );
		if ( separatorPosition === 'top' ) s.marginBottom = gap;
		else s.marginTop = gap;
	}

	return s;
};

/**
 * Heading edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< HeadingAttributes > ): JSX.Element {
	const {
		content,
		tag,
		className,
		responsiveVisibility,
		showSubheading,
		subheadingContent,
		subheadingTag,
		subheadingPosition,
		showSeparator,
		separatorPosition,
	} = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useEffect( () => {
		const expected = clientId.replace( /[^a-z0-9]/gi, '' ).slice( 0, 8 );
		if ( ! blockId || blockId !== expected ) {
			setAttributes( { blockId: expected } );
		}
	}, [ blockId, clientId, setAttributes ] );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-heading',
			blockId && `flexa-heading-${ blockId }`,
			className,
			responsiveVisibility?.hideOnDesktop && 'flexa-hide-desktop',
			responsiveVisibility?.hideOnTablet && 'flexa-hide-tablet',
			responsiveVisibility?.hideOnMobile && 'flexa-hide-mobile'
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	const titleStyle = buildTitleStyle( attributes, device );
	const subStyle = buildSubheadingStyle( attributes, device );
	const sepStyle = buildSeparatorStyle( attributes, device );

	// Hover colour can't be expressed inline; mirror the generator's `:hover`
	// rule in a scoped <style> so the editor previews it (light value).
	const hoverCss = blockId
		? editorCss( [
				{ selector: `.flexa-heading-${ blockId } .flexa-heading__title:hover`, prop: 'color', value: attributes.textColorHover?.light },
		  ] )
		: '';

	const Separator = <div className="flexa-heading__separator" style={ sepStyle } aria-hidden="true" />;
	const Subheading = (
		<RichText
			tagName={ subheadingTag || 'span' }
			className="flexa-heading__subheading"
			style={ subStyle }
			value={ subheadingContent || '' }
			allowedFormats={ [ 'core/bold', 'core/italic', 'core/text-color' ] }
			placeholder={ __( 'Subheading…', 'flexa-block' ) }
			onChange={ ( v: string ) => setAttributes( { subheadingContent: v } ) }
		/>
	);

	// Inserter hover-preview → faint skeleton mock-up instead of the real heading.
	if ( ( attributes as any ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="heading" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-heading-inspector">
					<InspectorTabs
						layout={
							<>
								<HeadingContentPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TypographyPanel attributes={ attributes } setAttributes={ setAttributes } />
								{ showSubheading && <SubheadingPanel attributes={ attributes } setAttributes={ setAttributes } /> }
								{ showSeparator && <SeparatorPanel attributes={ attributes } setAttributes={ setAttributes } /> }
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<TextColorsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<EffectsPanel attributes={ attributes } setAttributes={ setAttributes } />
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

			<div { ...blockProps }>
				{ hoverCss && <style>{ hoverCss }</style> }
				{ showSeparator && separatorPosition === 'top' && Separator }
				{ showSubheading && subheadingPosition === 'top' && Subheading }
				<RichText
					tagName={ tag || 'h2' }
					className="flexa-heading__title"
					style={ titleStyle }
					value={ content || '' }
					allowedFormats={ [ 'core/bold', 'core/italic', 'core/text-color' ] }
					placeholder={ __( 'Heading…', 'flexa-block' ) }
					onChange={ ( v: string ) => setAttributes( { content: v } ) }
				/>
				{ showSubheading && subheadingPosition !== 'top' && Subheading }
				{ showSeparator && separatorPosition !== 'top' && Separator }
			</div>
		</>
	);
}
