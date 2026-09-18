/**
 * Related Products block — editor component.
 *
 * Dynamic block: the real cards are produced server-side by render.php from
 * `wc_get_related_products()`. There is no product in the editor, so the canvas
 * draws sample cards (placeholder artwork from `demoImage()`, a sample name,
 * price and rating) through the same class names the front end prints, honouring
 * every toggle, the layout and the active device's column count. Every inline
 * style mirrors the PHP CSS generator, and nothing is styled by default so the
 * cards inherit the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

import {
	InspectorTabs,
	ButtonStylePanel,
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
	hoverEffectClasses,
	effective,
	withUnit,
	spacingShorthand,
	applyTypography,
	boxShadowPreview,
	buttonPreviewStyle,
	wrapperPreviewStyle,
	demoImage,
	editorCss,
} from '@utils';
import type { CssProps } from '@utils';
import {
	ProductRelatedSettingsPanel,
	ProductRelatedHeadingPanel,
	ProductRelatedPartsPanel,
	ProductRelatedCardPanel,
	ProductRelatedTitlePanel,
	ProductRelatedPricePanel,
	ProductRelatedRatingPanel,
} from './panels';
import type { DeviceKey, EditProps, ProductRelatedAttributes } from '../../types';

/** The canvas never draws more than this many sample cards. */
const MAX_SAMPLE_CARDS = 4;

/** Sample products — the editor has no query to read real ones from. */
const SAMPLE_CARDS: Array< { name: string; price: string; rating: number } > = [
	{ name: __( 'Sample Product One', 'flexa-block' ), price: '$24.00', rating: 4.5 },
	{ name: __( 'Sample Product Two', 'flexa-block' ), price: '$32.00', rating: 5 },
	{ name: __( 'Sample Product Three', 'flexa-block' ), price: '$18.50', rating: 4 },
	{ name: __( 'Sample Product Four', 'flexa-block' ), price: '$45.00', rating: 3.5 },
];

/** Resolve a responsive string (the column count) with the usual cascade. */
const effectiveString = ( group: ProductRelatedAttributes[ 'columns' ], device: DeviceKey ): string => {
	const g = group || {};
	if ( device === 'mobile' ) return g.mobile || g.tablet || g.desktop || '';
	if ( device === 'tablet' ) return g.tablet || g.desktop || '';
	return g.desktop || '';
};

/** List preview: the grid's columns and gaps, or the stacked list column. */
const buildListStyle = ( attributes: ProductRelatedAttributes, device: DeviceKey ): CssProps => {
	const { relatedLayout, columns, rowGap, columnGap } = attributes;
	const isGrid = ( relatedLayout || 'grid' ) === 'grid';
	const s: CssProps = isGrid ? { display: 'grid' } : { display: 'flex', flexDirection: 'column' };

	if ( isGrid ) {
		const raw = effectiveString( columns, device );
		if ( raw ) {
			const count = Math.max( 1, Math.min( 8, parseInt( raw, 10 ) || 1 ) );
			s.gridTemplateColumns = `repeat(${ count }, minmax(0, 1fr))`;
		}
		const cg = effective( columnGap, device );
		if ( cg.value ) s.columnGap = withUnit( cg.value, cg.unit || 'px' );
	}

	const rg = effective( rowGap, device );
	if ( rg.value ) s.rowGap = withUnit( rg.value, rg.unit || 'px' );

	return s;
};

/** Card preview: background, padding, radius, outline, shadow and alignment. */
const buildCardStyle = ( attributes: ProductRelatedAttributes, device: DeviceKey ): CssProps => {
	const { cardBackground, cardPadding, cardRadius, cardBorderWidth, cardBorderColor, cardShadow, contentAlign } = attributes;
	const s: CssProps = {};

	if ( cardBackground?.light ) s.background = cardBackground.light;

	const padding = spacingShorthand( effective( cardPadding, device ) );
	if ( padding ) s.padding = padding;

	const radius = effective( cardRadius, device );
	if ( radius?.value ) s.borderRadius = withUnit( radius.value, radius.unit || 'px' );

	const width = effective( cardBorderWidth, device );
	if ( width?.value ) {
		s.borderStyle = 'solid';
		s.borderWidth = withUnit( width.value, width.unit || 'px' );
	}
	if ( cardBorderColor?.light ) s.borderColor = cardBorderColor.light;

	const shadow = boxShadowPreview( cardShadow || {} );
	if ( shadow ) s.boxShadow = shadow;

	if ( contentAlign ) s.textAlign = contentAlign;

	return s;
};

/** Typography + colour preview for one text element. */
const buildTextStyle = (
	typography: ProductRelatedAttributes[ 'titleTypography' ],
	color: ProductRelatedAttributes[ 'titleColor' ],
	device: DeviceKey
): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( typography, device ) );
	if ( color?.light ) s.color = color.light;
	return s;
};

/** Five stars with a clipped fill overlay — same structure as render.php. */
const Stars = ( { rating }: { rating: number } ): JSX.Element => {
	const glyph = (
		<span className="flexa-product-related__star">
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
				<path d="M12 2.6l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L3.6 9.4l6.5-.9L12 2.6Z" fill="currentColor" />
			</svg>
		</span>
	);
	const row = [ 0, 1, 2, 3, 4 ].map( ( i ) => <span key={ i }>{ glyph }</span> );

	return (
		<span className="flexa-product-related__stars">
			<span className="flexa-product-related__stars-base">{ row }</span>
			<span className="flexa-product-related__stars-fill" style={ { width: `${ ( rating / 5 ) * 100 }%` } }>
				{ row }
			</span>
		</span>
	);
};

/**
 * Related Products edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ProductRelatedAttributes > ): JSX.Element {
	const {
		className, responsiveVisibility, relatedLayout, postsPerPage,
		showImage, showTitle, showPrice, showRating, showButton,
		showHeading, headingText, headingTag, imageRatio, hoverEffect,
	} = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, attributes.blockId, setAttributes );

	const blockId = attributes.blockId;
	const layout = relatedLayout || 'grid';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-product-related',
			`flexa-product-related--${ layout }`,
			blockId && `flexa-product-related-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: wrapperPreviewStyle( attributes, device ),
	} );

	// The generator's `add_button()` emits `:hover` rules, which inline styles
	// can't express — mirror them in a scoped <style> (light values, guide §6.7).
	const hoverCss = blockId && showButton
		? editorCss( [
				{ selector: `.flexa-product-related-${ blockId } .flexa-product-related__button:hover`, prop: 'color', value: attributes.buttonTextColorHover?.light },
				{ selector: `.flexa-product-related-${ blockId } .flexa-product-related__button:hover`, prop: 'background', value: attributes.buttonBackgroundHover?.light },
		  ] )
		: '';

	// Inserter hover-preview → faint skeleton mock-up instead of sample cards.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="grid" />
			</div>
		);
	}

	const count = Math.max( 1, Math.min( MAX_SAMPLE_CARDS, Number( postsPerPage ) || MAX_SAMPLE_CARDS ) );
	const cards = SAMPLE_CARDS.slice( 0, count );

	const listStyle = buildListStyle( attributes, device );
	const cardStyle = buildCardStyle( attributes, device );
	const headingStyle = buildTextStyle( attributes.headingTypography, attributes.headingColor, device );
	const titleStyle = buildTextStyle( attributes.titleTypography, attributes.titleColor, device );
	const priceStyle = buildTextStyle( attributes.priceTypography, attributes.priceColor, device );

	const ratingStyle: CssProps = attributes.starColor?.light ? { color: attributes.starColor.light } : {};

	const imageStyle: CssProps = {};
	if ( imageRatio ) {
		imageStyle.aspectRatio = imageRatio.replace( '/', ' / ' );
		imageStyle.objectFit = 'cover';
	}

	const buttonStyle: CssProps = buttonPreviewStyle( attributes );
	applyTypography( buttonStyle, effective( attributes.buttonTypography, device ) );

	// eslint-disable-next-line @typescript-eslint/no-explicit-any -- dynamic tag name.
	const HeadingTag: any = headingTag || 'h2';

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-product-related-inspector">
					<InspectorTabs
						layout={
							<>
								<ProductRelatedSettingsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductRelatedHeadingPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductRelatedPartsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ProductRelatedCardPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductRelatedTitlePanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductRelatedPricePanel attributes={ attributes } setAttributes={ setAttributes } />
								{ showRating && <ProductRelatedRatingPanel attributes={ attributes } setAttributes={ setAttributes } /> }
								{ showButton && <ButtonStylePanel attributes={ attributes } setAttributes={ setAttributes } /> }
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
				{ hoverCss && <style>{ hoverCss }</style> }
				{ showHeading !== false && !! ( headingText || '' ).trim() && (
					<HeadingTag className="flexa-product-related__heading" style={ headingStyle }>
						{ headingText }
					</HeadingTag>
				) }
				<div className="flexa-product-related__list" style={ listStyle }>
					{ cards.map( ( card ) => (
						<article key={ card.name } className="flexa-product-related__item" style={ cardStyle }>
							{ showImage !== false && (
								<span className={ cn( 'flexa-product-related__image', ...hoverEffectClasses( hoverEffect ) ) }>
									<img src={ demoImage( card.name ) } alt="" style={ imageStyle } />
								</span>
							) }
							{ showTitle !== false && (
								<h3 className="flexa-product-related__title" style={ titleStyle }>
									{ card.name }
								</h3>
							) }
							{ showRating && (
								<div className="flexa-product-related__rating" style={ ratingStyle }>
									<Stars rating={ card.rating } />
								</div>
							) }
							{ showPrice !== false && (
								<div className="flexa-product-related__price" style={ priceStyle }>
									{ card.price }
								</div>
							) }
							{ showButton && (
								<span className="flexa-product-related__button wp-element-button" style={ buttonStyle }>
									{ __( 'Add to cart', 'flexa-block' ) }
								</span>
							) }
						</article>
					) ) }
				</div>
			</div>
		</>
	);
}
