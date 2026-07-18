/**
 * Product Price block — editor component.
 *
 * Assembles the product-price-specific panels (settings + regular / sale
 * styling) with the shared foundation panels (@components) for spacing,
 * background, border, shadow, position, visibility and animation. The canvas
 * renders a placeholder price ($39.00 / $29.00) with an inline preview mirroring
 * the PHP CSS generator; on the front end render.php substitutes the current
 * product's real price, sale price and currency straight from WooCommerce — the
 * user never types the amount, only styles it. Nothing is styled by default so
 * the amounts inherit the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { useBlockProps, InspectorControls } from '@wordpress/block-editor';

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
	spacingShorthand,
	applyTypography,
	applyBorderPreview,
	applyBackgroundPreview,
	boxShadowPreview,
} from '@utils';
import type { CssProps } from '@utils';
import {
	ProductPriceSettingsPanel,
	ProductPriceRegularPanel,
	ProductPriceSalePanel,
} from './panels';
import type { DeviceKey, EditProps, ProductPriceAttributes } from '../../types';

/** Wrapper preview: alignment, spacing, background, border, shadow, overflow. */
const buildWrapperStyle = ( attributes: ProductPriceAttributes, device: DeviceKey ): CssProps => {
	const { alignment, spacing, background, border, boxShadow, advancedLayout } = attributes;
	const sp = effective( spacing, device );
	const adv = effective( advancedLayout, device );
	const s: CssProps = {};

	const align = alignment?.[ device ] || '';
	if ( align ) s.textAlign = align;

	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, background );
	applyBorderPreview( s, effective( border, device ) );

	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	if ( adv.overflow ) s.overflow = adv.overflow;

	return s;
};

/** Regular amount preview: typography + colour + strike-line appearance. */
const buildRegularStyle = ( attributes: ProductPriceAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.regularTypography, device ) );
	const color = attributes.regularColor?.light || '';
	if ( color ) s.color = color;
	const strikeColor = attributes.strikeColor?.light || '';
	if ( strikeColor ) s.textDecorationColor = strikeColor;
	const thickness = attributes.strikeThickness ?? 0;
	if ( thickness > 0 ) s.textDecorationThickness = `${ thickness }px`;
	return s;
};

/** Sale amount preview: typography + colour. */
const buildSaleStyle = ( attributes: ProductPriceAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.saleTypography, device ) );
	const color = attributes.saleColor?.light || '';
	if ( color ) s.color = color;
	return s;
};

/**
 * Product Price edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ProductPriceAttributes > ): JSX.Element {
	const { className, responsiveVisibility, salePricePosition, strikethrough } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, attributes.blockId, setAttributes );

	const blockId = attributes.blockId;

	const blockProps = useBlockProps( {
		// The editor always previews the on-sale case ($39 → $29), so mirror the
		// front-end strike modifier (unless the user turned strike-through off).
		className: cn( 'flexa-product-price', strikethrough !== false && 'flexa-product-price--strike', blockId && `flexa-product-price-${ blockId }`, className, ...visibilityClasses( responsiveVisibility ) ),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real price.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="text" />
			</div>
		);
	}

	const regular = (
		<span className="flexa-product-price__regular" style={ buildRegularStyle( attributes, device ) }>
			$39.00
		</span>
	);
	const sale = (
		<span className="flexa-product-price__sale" style={ buildSaleStyle( attributes, device ) }>
			$29.00
		</span>
	);

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-product-price-inspector">
					<InspectorTabs
						layout={
							<>
								<ProductPriceSettingsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ProductPriceRegularPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductPriceSalePanel attributes={ attributes } setAttributes={ setAttributes } />
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
				<span className="flexa-product-price__inner">
					{ salePricePosition === 'before' ? (
						<>
							{ sale }
							{ regular }
						</>
					) : (
						<>
							{ regular }
							{ sale }
						</>
					) }
				</span>
			</div>
		</>
	);
}
