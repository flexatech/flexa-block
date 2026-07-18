/**
 * Info Box block — editor component.
 *
 * Assembles the info-box-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / visibility.
 * The canvas renders the card (media + prefix / title / separator / description /
 * button) with inline styles that mirror the PHP CSS generator; nothing is styled
 * by default so the card inherits the theme until the user picks a value. The
 * prefix / title / description / button label are edited inline with RichText.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';

import {
	InspectorTabs,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
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
	applyTypography,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	CONTENT_ALIGN_TO_FLEX,
	type CssProps,
} from '@utils';
import {
	InfoBoxLayoutPanel,
	InfoBoxMediaPanel,
	InfoBoxPrefixPanel,
	InfoBoxTitlePanel,
	InfoBoxDescriptionPanel,
	InfoBoxSeparatorPanel,
	InfoBoxButtonPanel,
} from './panels';
import type { ButtonIconAttr, ColorPair, DeviceKey, EditProps, InfoBoxAttributes, TypographyDevice } from '../../types';

/** The active device's content alignment, cascading tablet/mobile back to desktop.
 *  Empty means "inherit the base layout from style.scss" (centered icon-top). */
const alignFor = ( attributes: InfoBoxAttributes, device: DeviceKey ): string => {
	const a = attributes.alignment || {};
	return a[ device ] || a.desktop || '';
};

/** Card preview: layout direction, alignment, media gap, spacing, background, border, shadow. */
const buildWrapperStyle = ( attributes: InfoBoxAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const isTop = ( attributes.iconPosition || 'top' ) === 'top';
	s.flexDirection = isTop ? 'column' : 'row';

	const align = alignFor( attributes, device );
	if ( align ) {
		s.textAlign = align;
		// The wrapper's cross-axis placement only follows the alignment in the column
		// (icon-top) layout; the row layout keeps the media pinned to the top.
		if ( isTop ) {
			s.alignItems = CONTENT_ALIGN_TO_FLEX[ align ] || 'center';
		}
	}

	const gap = effective( attributes.mediaGap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );

	const sp = effective( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, attributes.background );
	applyBorderPreview( s, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	return s;
};

/** Content column preview: alignment of the inner elements + gap between them. */
const buildContentStyle = ( attributes: InfoBoxAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const align = alignFor( attributes, device );
	if ( align ) s.alignItems = CONTENT_ALIGN_TO_FLEX[ align ] || 'center';
	const gap = effective( attributes.contentGap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** Media box preview: background, padding, radius. */
const buildMediaStyle = ( attributes: InfoBoxAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	if ( attributes.mediaBackground?.light ) s.background = attributes.mediaBackground.light;
	const padding = spacingShorthand( rawDevice( attributes.mediaPadding, device ) );
	if ( padding ) s.padding = padding;
	const radius = effective( attributes.mediaRadius, device );
	if ( radius.value ) s.borderRadius = withUnit( radius.value, radius.unit || 'px' );
	return s;
};

/** Icon preview: size (font-size drives the 1em glyph) + colour. */
const buildIconStyle = ( attributes: InfoBoxAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.iconSize, device );
	if ( size.value ) s.fontSize = withUnit( size.value, size.unit || 'px' );
	if ( attributes.iconColor?.light ) s.color = attributes.iconColor.light;
	return s;
};

/** Image preview: width + radius. */
const buildImageStyle = ( attributes: InfoBoxAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const width = effective( attributes.imageWidth, device );
	if ( width.value ) s.width = withUnit( width.value, width.unit || 'px' );
	const radius = effective( attributes.mediaRadius, device );
	if ( radius.value ) s.borderRadius = withUnit( radius.value, radius.unit || 'px' );
	return s;
};

/** Text element preview (prefix / title / description): typography + colour. */
const buildTextStyle = ( typo: Partial< TypographyDevice >, color?: ColorPair ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, typo );
	if ( color?.light ) s.color = color.light;
	return s;
};

/** Separator preview: width, weight, style, colour. */
const buildSeparatorStyle = ( attributes: InfoBoxAttributes ): CssProps => {
	const { separatorWidth, separatorWeight, separatorStyle, separatorColor } = attributes;
	const s: CssProps = { borderTopStyle: separatorStyle || 'solid' };
	s.width = separatorWidth?.value ? withUnit( separatorWidth.value, separatorWidth.unit || 'px' ) : '60px';
	s.borderTopWidth = separatorWeight?.value ? withUnit( separatorWeight.value, separatorWeight.unit || 'px' ) : '2px';
	if ( separatorColor?.light ) s.borderTopColor = separatorColor.light;
	return s;
};

/** Button preview: text + background colour (radius/padding inherit the theme). */
const buildButtonStyle = ( attributes: InfoBoxAttributes ): CssProps => {
	const s: CssProps = {};
	if ( attributes.buttonTextColor?.light ) s.color = attributes.buttonTextColor.light;
	if ( attributes.buttonBgColor?.light ) s.backgroundColor = attributes.buttonBgColor.light;
	return s;
};

/** Render a chosen icon in the editor preview from its stored markup / url. */
const renderIconPreview = ( icon?: ButtonIconAttr ): JSX.Element | null => {
	if ( ! icon ) {
		return null;
	}
	if ( icon.source === 'upload' && icon.url ) {
		return <img className="flexa-icon" src={ icon.url } alt="" />;
	}
	if ( icon.markup ) {
		return <span className="flexa-icon-slot" dangerouslySetInnerHTML={ { __html: icon.markup } } />;
	}
	return null;
};

/**
 * Info Box edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< InfoBoxAttributes > ): JSX.Element {
	const {
		iconPosition, stackOn, showMedia, mediaType, icon, image,
		showPrefix, prefix, title, titleTag, showSeparator, description,
		showButton, buttonText, buttonIcon, className, responsiveVisibility,
	} = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const type = mediaType || 'icon';
	const buttonIconCfg = buttonIcon || {};
	const buttonIconEl = renderIconPreview( buttonIconCfg );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-info-box',
			`flexa-info-box--icon-${ iconPosition || 'top' }`,
			( iconPosition || 'top' ) === 'left' && stackOn && stackOn !== 'none' && `flexa-info-box--stack-${ stackOn }`,
			blockId && `flexa-info-box-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real card.
	if ( ( attributes as unknown as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="info-box" />
			</div>
		);
	}

	const iconStyle = buildIconStyle( attributes, device );
	const imageStyle = buildImageStyle( attributes, device );
	const mediaStyle = buildMediaStyle( attributes, device );

	// Hover state — inline styles can't express `:hover`, so mirror the generator's
	// CTA button hover rules in a scoped <style> (light values, only what's set).
	const buttonHover = attributes.buttonHover;
	const hoverCss = blockId
		? editorCss( [
				{ selector: `.flexa-info-box-${ blockId } .flexa-info-box__button:hover`, prop: 'color', value: buttonHover?.text?.light },
				{ selector: `.flexa-info-box-${ blockId } .flexa-info-box__button:hover`, prop: 'background-color', value: buttonHover?.background?.light },
		  ] )
		: '';

	const mediaEl = showMedia !== false && (
		<div className="flexa-info-box__media" style={ mediaStyle }>
			{ type === 'icon'
				? icon?.markup && <span className="flexa-info-box__icon" style={ iconStyle } dangerouslySetInnerHTML={ { __html: icon.markup } } />
				: image?.url && <img className="flexa-info-box__image" src={ image.url } alt={ image.alt || '' } style={ imageStyle } /> }
		</div>
	);

	const TitleTag = ( titleTag || 'h3' ) as keyof JSX.IntrinsicElements;

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-info-box-inspector">
					<InspectorTabs
						layout={
							<>
								<InfoBoxLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<InfoBoxMediaPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								{ showPrefix && <InfoBoxPrefixPanel attributes={ attributes } setAttributes={ setAttributes } /> }
								<InfoBoxTitlePanel attributes={ attributes } setAttributes={ setAttributes } />
								{ showSeparator && <InfoBoxSeparatorPanel attributes={ attributes } setAttributes={ setAttributes } /> }
								<InfoBoxDescriptionPanel attributes={ attributes } setAttributes={ setAttributes } />
								{ showButton && <InfoBoxButtonPanel attributes={ attributes } setAttributes={ setAttributes } /> }
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
				{ mediaEl }
				<div className="flexa-info-box__content" style={ buildContentStyle( attributes, device ) }>
					{ showPrefix && (
						<RichText
							tagName="span"
							className="flexa-info-box__prefix"
							style={ buildTextStyle( rawDevice( attributes.prefixTypography, device ), attributes.prefixColor ) }
							value={ prefix || '' }
							allowedFormats={ [ 'core/bold', 'core/italic' ] }
							placeholder={ __( 'Prefix…', 'flexa-block' ) }
							onChange={ ( v: string ) => setAttributes( { prefix: v } ) }
						/>
					) }
					<RichText
						tagName={ TitleTag }
						className="flexa-info-box__title"
						style={ buildTextStyle( rawDevice( attributes.titleTypography, device ), attributes.titleColor ) }
						value={ title || '' }
						allowedFormats={ [ 'core/bold', 'core/italic' ] }
						placeholder={ __( 'Info box title…', 'flexa-block' ) }
						onChange={ ( v: string ) => setAttributes( { title: v } ) }
					/>
					{ showSeparator && <div className="flexa-info-box__separator" style={ buildSeparatorStyle( attributes ) } aria-hidden="true" /> }
					<RichText
						tagName="p"
						className="flexa-info-box__description"
						style={ buildTextStyle( rawDevice( attributes.descriptionTypography, device ), attributes.descriptionColor ) }
						value={ description || '' }
						allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
						placeholder={ __( 'Add a short description…', 'flexa-block' ) }
						onChange={ ( v: string ) => setAttributes( { description: v } ) }
					/>
					{ showButton && (
						<div className="flexa-info-box__cta">
							<span className="wp-element-button flexa-info-box__button" style={ buildButtonStyle( attributes ) }>
								{ buttonIconEl && buttonIconCfg.position === 'before' && buttonIconEl }
								<RichText
									tagName="span"
									className="flexa-info-box__button-text"
									value={ buttonText || '' }
									allowedFormats={ [ 'core/bold', 'core/italic' ] }
									placeholder={ __( 'Button text…', 'flexa-block' ) }
									onChange={ ( v: string ) => setAttributes( { buttonText: v } ) }
								/>
								{ buttonIconEl && buttonIconCfg.position !== 'before' && buttonIconEl }
							</span>
						</div>
					) }
				</div>
			</div>
		</>
	);
}
