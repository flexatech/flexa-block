/**
 * Star Rating block — editor component.
 *
 * Assembles the block-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility / animation. The canvas renders the stars (plus an optional title)
 * with inline styles that mirror the PHP CSS generator, and nothing is coloured
 * by default so the rating inherits the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

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
	useBlockId,
	useDevice,
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
import { StarRatingRatingPanel, StarRatingLayoutPanel, StarRatingSeoPanel } from './panels';
import type { DeviceKey, EditProps, StarRatingAttributes } from '../../types';

/** Square star glyph (viewBox 0 0 24 24) — reused for both the base and fill layers. */
const STAR_PATH = 'M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L3.6 9.4l6.5-.9L12 2.6Z';

/** The active device's alignment, cascading tablet/mobile back to desktop. */
const alignFor = ( attributes: StarRatingAttributes, device: DeviceKey ): string => {
	const a = attributes.alignment || {};
	return a[ device ] || a.desktop || 'left';
};

/** Wrapper preview: alignment, title gap, spacing, background, border, shadow, advanced. */
const buildWrapperStyle = ( attributes: StarRatingAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const align = alignFor( attributes, device );
	const flex = CONTENT_ALIGN_TO_FLEX[ align ] || 'flex-start';
	if ( attributes.ratingLayout === 'stacked' ) {
		s.alignItems = flex;
	} else {
		s.justifyContent = flex;
	}

	const gap = effective( attributes.titleGap, device );
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

	const adv = effective( attributes.advancedLayout, device );
	if ( adv.overflow ) s.overflow = adv.overflow;
	if ( adv.position ) s.position = adv.position;

	return s;
};

/** Stars row preview: gap between stars. */
const buildStarsStyle = ( attributes: StarRatingAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const gap = effective( attributes.gap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** One star box preview: size only (colours live on the base/fill layers). */
const buildStarStyle = ( attributes: StarRatingAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.starSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		s.width = v;
		s.height = v;
	}
	return s;
};

/** Title preview: typography + colour. */
const buildTitleStyle = ( attributes: StarRatingAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.titleTypography, device ) );
	if ( attributes.titleColor?.light ) s.color = attributes.titleColor.light;
	return s;
};

/** A single star: an unmarked base with a width-clipped marked overlay on top. */
const Star = (
	{ fill, style, baseColor, fillColor }: { fill: number; style: CssProps; baseColor?: string; fillColor?: string }
): JSX.Element => {
	const baseStyle: CssProps = {};
	if ( baseColor ) baseStyle.color = baseColor;
	const fillStyle: CssProps = { width: `${ Math.max( 0, Math.min( 1, fill ) ) * 100 }%` };
	if ( fillColor ) fillStyle.color = fillColor;
	return (
		<span className="flexa-star-rating__star" style={ style }>
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style={ baseStyle }>
				<path d={ STAR_PATH } fill="currentColor" />
			</svg>
			<span className="flexa-star-rating__star-fill" style={ fillStyle }>
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<path d={ STAR_PATH } fill="currentColor" />
				</svg>
			</span>
		</span>
	);
};

/**
 * Star Rating edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< StarRatingAttributes > ): JSX.Element {
	const { rating, maxRating, ratingLayout, showTitle, title, titlePosition, className, responsiveVisibility, htmlTag } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const max = typeof maxRating === 'number' && maxRating > 0 ? Math.round( maxRating ) : 5;
	const value = typeof rating === 'number' ? rating : 4;

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-star-rating',
			`flexa-star-rating--${ ratingLayout || 'inline' }`,
			blockId && `flexa-star-rating-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	const starStyle = buildStarStyle( attributes, device );
	const fillColor = attributes.color?.light;
	const baseColor = attributes.unmarkedColor?.light;
	const stars = (
		<span className="flexa-star-rating__stars" style={ buildStarsStyle( attributes, device ) } aria-hidden="true">
			{ Array.from( { length: max }, ( _, i ) => (
				<Star key={ i } fill={ value - i } style={ starStyle } baseColor={ baseColor } fillColor={ fillColor } />
			) ) }
		</span>
	);

	const titleEl = showTitle && ( title || '' ).trim() !== '' ? (
		<span className="flexa-star-rating__title" style={ buildTitleStyle( attributes, device ) }>
			{ title }
		</span>
	) : null;

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-star-rating-inspector">
					<InspectorTabs
						layout={
							<>
								<StarRatingRatingPanel attributes={ attributes } setAttributes={ setAttributes } />
								<StarRatingLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<StarRatingSeoPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
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
				{ titlePosition === 'before' && titleEl }
				{ stars }
				{ titlePosition !== 'before' && titleEl }
			</Tag>
		</>
	);
}
