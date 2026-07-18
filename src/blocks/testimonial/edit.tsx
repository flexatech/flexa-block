/**
 * Testimonial block — editor component.
 *
 * Assembles the testimonial-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility. The canvas renders the card with inline styles that mirror the PHP
 * CSS generator, and nothing is styled by default so the card inherits the theme
 * until the user picks a value. Title / quote / name / role are edited inline
 * with RichText; the star count comes from the Rating panel.
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
	PositionPanel,
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
	CONTENT_ALIGN_TO_FLEX,
	type CssProps,
} from '@utils';
import {
	TestimonialLayoutPanel,
	TestimonialRatingPanel,
	TestimonialAuthorPanel,
	TestimonialTitlePanel,
	TestimonialQuotePanel,
	TestimonialNamePanel,
	TestimonialJobPanel,
} from './panels';
import type { DeviceKey, EditProps, TestimonialAttributes } from '../../types';

/** The active device's alignment, cascading tablet/mobile back to desktop. */
const alignFor = ( attributes: TestimonialAttributes, device: DeviceKey ): string => {
	const a = attributes.alignment || {};
	return a[ device ] || a.desktop || 'left';
};

/** Card preview: alignment, min height, spacing, background, border, shadow. */
const buildCardStyle = ( attributes: TestimonialAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const align = alignFor( attributes, device );
	s.textAlign = align;
	s.alignItems = CONTENT_ALIGN_TO_FLEX[ align ] || 'flex-start';

	const sp = effective( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	const min = rawDevice( attributes.size, device ).minHeight || {};
	if ( min.value ) s.minHeight = withUnit( min.value, min.unit || 'px' );

	applyBackgroundPreview( s, attributes.background );
	applyBorderPreview( s, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	return s;
};

/** Rating row preview: colour + spacing below (star size is per-star). */
const buildRatingStyle = ( attributes: TestimonialAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	if ( attributes.ratingColor?.light ) s.color = attributes.ratingColor.light;
	const gap = effective( attributes.ratingSpacing, device );
	if ( gap.value ) s.marginBottom = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** One star's size preview. */
const buildStarStyle = ( attributes: TestimonialAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.ratingSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		s.width = v;
		s.height = v;
	}
	return s;
};

/** Text-block preview (title / quote): typography, colour, spacing below. */
const buildTextStyle = (
	typo: Parameters< typeof applyTypography >[ 1 ],
	color: { light?: string } | undefined,
	spacing: { value?: string; unit?: string }
): CssProps => {
	const s: CssProps = {};
	applyTypography( s, typo );
	if ( color?.light ) s.color = color.light;
	if ( spacing.value ) s.marginBottom = withUnit( spacing.value, spacing.unit || 'px' );
	return s;
};

/** Author row preview: layout direction + gap. */
const buildAuthorStyle = ( attributes: TestimonialAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	s.flexDirection = attributes.authorLayout === 'stacked' ? 'column' : 'row';
	const gap = effective( attributes.authorGap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** Avatar preview: size. */
const buildAvatarStyle = ( attributes: TestimonialAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.authorImageSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		s.width = v;
		s.height = v;
	}
	return s;
};

/** A star glyph (filled or faint outline). */
const Star = ( { filled, style }: { filled: boolean; style: CssProps } ): JSX.Element => (
	<svg
		className={ cn( 'flexa-testimonial__star', ! filled && 'flexa-testimonial__star--empty' ) }
		style={ style }
		viewBox="0 0 24 24"
		xmlns="http://www.w3.org/2000/svg"
		aria-hidden="true"
	>
		<path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L3.6 9.4l6.5-.9L12 2.6Z" fill="currentColor" />
	</svg>
);

/**
 * Testimonial edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< TestimonialAttributes > ): JSX.Element {
	const {
		title, quote, authorName, authorJob, rating, showRating, showAvatar, authorImage,
		authorLayout, className, responsiveVisibility, htmlTag,
	} = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-testimonial',
			`flexa-testimonial--author-${ authorLayout || 'inline' }`,
			blockId && `flexa-testimonial-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildCardStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real card.
	if ( ( attributes as unknown as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<Tag { ...blockProps }>
				<ExamplePreviewSkeleton kind="testimonial" />
			</Tag>
		);
	}

	const stars = Math.max( 0, Math.min( 5, Math.round( typeof rating === 'number' ? rating : 5 ) ) );
	const starStyle = buildStarStyle( attributes, device );
	const titleStyle = buildTextStyle( rawDevice( attributes.titleTypography, device ), attributes.titleColor, effective( attributes.titleSpacing, device ) );
	const quoteStyle = buildTextStyle( rawDevice( attributes.quoteTypography, device ), attributes.quoteColor, effective( attributes.quoteSpacing, device ) );
	const nameStyle = buildTextStyle( rawDevice( attributes.nameTypography, device ), attributes.nameColor, {} );
	const jobStyle = buildTextStyle( rawDevice( attributes.jobTypography, device ), attributes.jobColor, {} );

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-testimonial-inspector">
					<InspectorTabs
						layout={
							<>
								<TestimonialLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TestimonialRatingPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TestimonialAuthorPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<TestimonialTitlePanel attributes={ attributes } setAttributes={ setAttributes } />
								<TestimonialQuotePanel attributes={ attributes } setAttributes={ setAttributes } />
								<TestimonialNamePanel attributes={ attributes } setAttributes={ setAttributes } />
								<TestimonialJobPanel attributes={ attributes } setAttributes={ setAttributes } />
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

			<Tag { ...blockProps }>
				{ showRating !== false && (
					<div className="flexa-testimonial__rating" style={ buildRatingStyle( attributes, device ) } aria-hidden="true">
						{ [ 0, 1, 2, 3, 4 ].map( ( i ) => (
							<Star key={ i } filled={ i < stars } style={ starStyle } />
						) ) }
					</div>
				) }

				<RichText
					tagName="p"
					className="flexa-testimonial__title"
					style={ titleStyle }
					value={ title || '' }
					allowedFormats={ [ 'core/bold', 'core/italic' ] }
					placeholder={ __( 'Testimonial title…', 'flexa-block' ) }
					onChange={ ( v: string ) => setAttributes( { title: v } ) }
				/>

				<RichText
					tagName="blockquote"
					className="flexa-testimonial__quote"
					style={ quoteStyle }
					value={ quote || '' }
					allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
					placeholder={ __( 'What did they say?', 'flexa-block' ) }
					onChange={ ( v: string ) => setAttributes( { quote: v } ) }
				/>

				<div className="flexa-testimonial__author" style={ buildAuthorStyle( attributes, device ) }>
					{ showAvatar !== false && (
						authorImage?.url ? (
							<img className="flexa-testimonial__avatar" src={ authorImage.url } alt={ authorImage.alt || '' } style={ buildAvatarStyle( attributes, device ) } />
						) : (
							<span className="flexa-testimonial__avatar flexa-testimonial__avatar--placeholder" style={ buildAvatarStyle( attributes, device ) } aria-hidden="true" />
						)
					) }
					<div className="flexa-testimonial__author-text">
						<RichText
							tagName="span"
							className="flexa-testimonial__name"
							style={ nameStyle }
							value={ authorName || '' }
							allowedFormats={ [ 'core/bold', 'core/italic' ] }
							placeholder={ __( 'Author name', 'flexa-block' ) }
							onChange={ ( v: string ) => setAttributes( { authorName: v } ) }
						/>
						<RichText
							tagName="span"
							className="flexa-testimonial__job"
							style={ jobStyle }
							value={ authorJob || '' }
							allowedFormats={ [ 'core/bold', 'core/italic' ] }
							placeholder={ __( 'Role, company', 'flexa-block' ) }
							onChange={ ( v: string ) => setAttributes( { authorJob: v } ) }
						/>
					</div>
				</div>
			</Tag>
		</>
	);
}
