/**
 * Team Member block — editor component.
 *
 * Assembles the team-member-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / visibility.
 * The canvas renders the card (photo + name / role / bio / social links) with
 * inline styles that mirror the PHP CSS generator; nothing is styled by default
 * so the card inherits the theme until the user picks a value. The name / role /
 * bio are edited inline with RichText; the photo shape and layout come from class
 * modifiers styled in style.scss.
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
	TeamMemberLayoutPanel,
	TeamMemberPhotoPanel,
	TeamMemberNamePanel,
	TeamMemberRolePanel,
	TeamMemberBioPanel,
	TeamMemberSocialPanel,
} from './panels';
import type { ColorPair, DeviceKey, EditProps, TeamMemberAttributes, TeamSocialItem, TypographyDevice } from '../../types';

/** The active device's content alignment, cascading tablet/mobile back to desktop. */
const alignFor = ( attributes: TeamMemberAttributes, device: DeviceKey ): string => {
	const a = attributes.alignment || {};
	return a[ device ] || a.desktop || '';
};

/** Card preview: layout direction, alignment, photo gap, max width, spacing, background, border, shadow. */
const buildWrapperStyle = ( attributes: TeamMemberAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const position = attributes.imagePosition || 'top';
	const isTop = position === 'top';
	s.flexDirection = isTop ? 'column' : position === 'right' ? 'row-reverse' : 'row';

	const align = alignFor( attributes, device );
	if ( align ) {
		s.textAlign = align;
		// The wrapper's cross-axis placement only follows the alignment in the
		// column (photo-top) layout; the row layouts keep the photo pinned to the top.
		if ( isTop ) {
			s.alignItems = CONTENT_ALIGN_TO_FLEX[ align ] || 'center';
		}
	}

	const gap = effective( attributes.mediaGap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );

	const width = effective( attributes.maxWidth, device );
	if ( width.value ) s.maxWidth = withUnit( width.value, width.unit || 'px' );

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

/** Content column preview: alignment of the inner elements. */
const buildContentStyle = ( attributes: TeamMemberAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const align = alignFor( attributes, device );
	if ( align ) s.alignItems = CONTENT_ALIGN_TO_FLEX[ align ] || 'center';
	return s;
};

/** Photo preview: width. */
const buildImageStyle = ( attributes: TeamMemberAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const width = effective( attributes.imageWidth, device );
	if ( width.value ) s.width = withUnit( width.value, width.unit || 'px' );
	return s;
};

/** Text element preview (name / role / bio): typography + colour + spacing below. */
const buildTextStyle = (
	typo: Partial< TypographyDevice >,
	color: ColorPair | undefined,
	spacing: { value?: string; unit?: string }
): CssProps => {
	const s: CssProps = {};
	applyTypography( s, typo );
	if ( color?.light ) s.color = color.light;
	if ( spacing?.value ) s.marginBottom = withUnit( spacing.value, spacing.unit || 'px' );
	return s;
};

/** Social list preview: gap between icons. */
const buildSocialStyle = ( attributes: TeamMemberAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const gap = effective( attributes.socialGap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** Social link preview: size (font-size drives the 1em glyph) + colour. */
const buildSocialLinkStyle = ( attributes: TeamMemberAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.socialSize, device );
	if ( size.value ) s.fontSize = withUnit( size.value, size.unit || 'px' );
	if ( attributes.socialColor?.light ) s.color = attributes.socialColor.light;
	return s;
};

/**
 * Hover CSS for the editor — inline styles can't express `:hover`, so mirror the
 * generator's social-link hover rule in a scoped <style>. Light value only (the
 * editor previews light mode); selector mirrors Team_Member_CSS exactly.
 */
const buildHoverCss = ( attributes: TeamMemberAttributes, blockId?: string ): string => {
	if ( ! blockId ) {
		return '';
	}
	const base = `.flexa-team-member-${ blockId }`;
	return editorCss( [
		{ selector: `${ base } .flexa-team-member__social-link:hover`, prop: 'color', value: attributes.socialHoverColor?.light },
	] );
};

/** Render one social item's icon in the editor preview from its stored markup / url. */
const renderSocialIcon = ( item: TeamSocialItem ): JSX.Element | null => {
	const icon = item.icon;
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
 * Team Member edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< TeamMemberAttributes > ): JSX.Element {
	const {
		imagePosition, imageShape, stackOn, image, name, nameTag,
		role, bio, showSocial, items, className, responsiveVisibility,
	} = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const position = imagePosition || 'top';
	const list: TeamSocialItem[] = Array.isArray( items ) ? items : [];

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-team-member',
			`flexa-team-member--pos-${ position }`,
			`flexa-team-member--shape-${ imageShape || 'circle' }`,
			position !== 'top' && stackOn && stackOn !== 'none' && `flexa-team-member--stack-${ stackOn }`,
			blockId && `flexa-team-member-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real card.
	if ( ( attributes as unknown as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="testimonial" />
			</div>
		);
	}

	const NameTag = ( nameTag || 'h3' ) as keyof JSX.IntrinsicElements;
	const imageStyle = buildImageStyle( attributes, device );
	const hoverCss = buildHoverCss( attributes, blockId );

	const photoEl = image?.url && (
		<div className="flexa-team-member__photo">
			<img className="flexa-team-member__image" src={ image.url } alt={ image.alt || '' } style={ imageStyle } />
		</div>
	);

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-team-member-inspector">
					<InspectorTabs
						layout={
							<>
								<TeamMemberLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TeamMemberPhotoPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<TeamMemberNamePanel attributes={ attributes } setAttributes={ setAttributes } />
								<TeamMemberRolePanel attributes={ attributes } setAttributes={ setAttributes } />
								<TeamMemberBioPanel attributes={ attributes } setAttributes={ setAttributes } />
								{ showSocial && <TeamMemberSocialPanel attributes={ attributes } setAttributes={ setAttributes } /> }
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
				{ photoEl }
				<div className="flexa-team-member__content" style={ buildContentStyle( attributes, device ) }>
					<RichText
						tagName={ NameTag }
						className="flexa-team-member__name"
						style={ buildTextStyle( rawDevice( attributes.nameTypography, device ), attributes.nameColor, effective( attributes.nameSpacing, device ) ) }
						value={ name || '' }
						allowedFormats={ [ 'core/bold', 'core/italic' ] }
						placeholder={ __( 'Name…', 'flexa-block' ) }
						onChange={ ( v: string ) => setAttributes( { name: v } ) }
					/>
					<RichText
						tagName="span"
						className="flexa-team-member__role"
						style={ buildTextStyle( rawDevice( attributes.roleTypography, device ), attributes.roleColor, effective( attributes.roleSpacing, device ) ) }
						value={ role || '' }
						allowedFormats={ [ 'core/bold', 'core/italic' ] }
						placeholder={ __( 'Role…', 'flexa-block' ) }
						onChange={ ( v: string ) => setAttributes( { role: v } ) }
					/>
					<RichText
						tagName="p"
						className="flexa-team-member__bio"
						style={ buildTextStyle( rawDevice( attributes.bioTypography, device ), attributes.bioColor, effective( attributes.bioSpacing, device ) ) }
						value={ bio || '' }
						allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
						placeholder={ __( 'Add a short bio…', 'flexa-block' ) }
						onChange={ ( v: string ) => setAttributes( { bio: v } ) }
					/>
					{ showSocial && list.length > 0 && (
						<div className="flexa-team-member__social" style={ buildSocialStyle( attributes, device ) }>
							{ list.map( ( item, index ) => {
								const iconEl = renderSocialIcon( item );
								if ( ! iconEl ) {
									return null;
								}
								return (
									<span key={ index } className="flexa-team-member__social-link" style={ buildSocialLinkStyle( attributes, device ) }>
										{ iconEl }
									</span>
								);
							} ) }
						</div>
					) }
				</div>
			</div>
		</>
	);
}
