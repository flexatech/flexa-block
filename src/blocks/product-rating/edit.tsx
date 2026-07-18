/**
 * Product Rating block — editor component.
 *
 * Assembles the block-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility / animation. The canvas renders a placeholder rating (4.5 out of 5,
 * 24 reviews) as a star row (an empty base with a width-clipped filled overlay,
 * mirroring the Star Rating block), an optional numeric score and an optional
 * review count, with inline styles that mirror the PHP CSS generator. Nothing is
 * coloured by default so the rating inherits the theme until the user picks a
 * value.
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
	type CssProps,
} from '@utils';
import { ProductRatingSettingsPanel, ProductRatingStarsPanel, ProductRatingNumberPanel, ProductRatingCountPanel } from './panels';
import type { DeviceKey, EditProps, ProductRatingAttributes } from '../../types';

/** Placeholder rating shown in the editor (WooCommerce reads the real value on the front end). */
const PREVIEW_AVERAGE = 4.5;
const PREVIEW_COUNT = 24;
const MAX_STARS = 5;

/** Square star glyph (viewBox 0 0 24 24) — reused for the base and fill rows. */
const STAR_PATH = 'M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L3.6 9.4l6.5-.9L12 2.6Z';

/** Format a rating without a trailing `.0` (e.g. 4, 4.5). */
const fmt = ( n: number ): string => String( Math.round( n * 10 ) / 10 );

/** Wrapper preview: alignment (text-align), spacing, background, border, shadow, advanced. */
const buildWrapperStyle = ( attributes: ProductRatingAttributes, device: DeviceKey ): CssProps => {
	const { alignment, spacing, background, border, boxShadow, advancedLayout } = attributes;
	const s: CssProps = {};

	const align = alignment?.[ device ] || '';
	if ( align ) s.textAlign = align;

	const sp = effective( spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, background );
	applyBorderPreview( s, effective( border, device ) );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	const adv = effective( advancedLayout, device );
	if ( adv.overflow ) s.overflow = adv.overflow;
	if ( adv.position ) s.position = adv.position;

	return s;
};

/** Stars row preview: gap between stars. */
const buildStarsStyle = ( attributes: ProductRatingAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const gap = effective( attributes.starGap, device );
	if ( gap.value ) s.columnGap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/** One star box preview: size only (colours live on the base/fill rows). */
const buildStarStyle = ( attributes: ProductRatingAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.starSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		s.width = v;
		s.height = v;
	}
	return s;
};

/** Count preview: colour + typography. */
const buildCountStyle = ( attributes: ProductRatingAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.countTypography, device ) );
	if ( attributes.countColor?.light ) s.color = attributes.countColor.light;
	return s;
};

/** Numeric-score preview: typography + colour. */
const buildNumberStyle = ( attributes: ProductRatingAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.numberTypography, device ) );
	if ( attributes.numberColor?.light ) s.color = attributes.numberColor.light;
	return s;
};

/** A single star svg (used identically in the base and fill rows). */
const StarGlyph = ( { style }: { style?: CssProps } ): JSX.Element => (
	<span className="flexa-product-rating__star" style={ style }>
		<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
			<path d={ STAR_PATH } fill="currentColor" />
		</svg>
	</span>
);

/**
 * Product Rating edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ProductRatingAttributes > ): JSX.Element {
	const { displayType, showReviewCount, className, responsiveVisibility } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-product-rating',
			blockId && `flexa-product-rating-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real rating.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="text" />
			</div>
		);
	}

	const type = displayType || 'stars';
	const showStars = type === 'stars' || type === 'stars-number';
	const showNumber = type === 'number' || type === 'stars-number';

	const starStyle = buildStarStyle( attributes, device );
	const fillPct = Math.max( 0, Math.min( 1, PREVIEW_AVERAGE / MAX_STARS ) ) * 100;

	const baseStyle: CssProps = {};
	if ( attributes.starEmptyColor?.light ) baseStyle.color = attributes.starEmptyColor.light;
	const fillRowStyle: CssProps = { width: `${ fillPct }%` };
	if ( attributes.starColor?.light ) fillRowStyle.color = attributes.starColor.light;

	const stars = showStars ? (
		<span className="flexa-product-rating__stars" style={ buildStarsStyle( attributes, device ) } aria-hidden="true">
			<span className="flexa-product-rating__stars-base" style={ baseStyle }>
				{ Array.from( { length: MAX_STARS }, ( _, i ) => (
					<StarGlyph key={ i } style={ starStyle } />
				) ) }
			</span>
			<span className="flexa-product-rating__stars-fill" style={ fillRowStyle }>
				{ Array.from( { length: MAX_STARS }, ( _, i ) => (
					<StarGlyph key={ i } style={ starStyle } />
				) ) }
			</span>
		</span>
	) : null;

	const number = showNumber ? (
		<span className="flexa-product-rating__number" style={ buildNumberStyle( attributes, device ) }>{ fmt( PREVIEW_AVERAGE ) }</span>
	) : null;

	const count = showReviewCount !== false ? (
		<span className="flexa-product-rating__count" style={ buildCountStyle( attributes, device ) }>
			{ `(${ PREVIEW_COUNT })` }
		</span>
	) : null;

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-product-rating-inspector">
					<InspectorTabs
						layout={
							<>
								<ProductRatingSettingsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ProductRatingStarsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductRatingNumberPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductRatingCountPanel attributes={ attributes } setAttributes={ setAttributes } />
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

			<div { ...blockProps }>
				{ stars }
				{ number }
				{ count }
			</div>
		</>
	);
}
