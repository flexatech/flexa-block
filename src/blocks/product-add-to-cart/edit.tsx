/**
 * Add to Cart block — editor component.
 *
 * WooCommerce's add-to-cart template cannot run in the editor (there is no
 * product, and the template needs the front-end request), so the canvas shows an
 * inert mock built from the SAME class names core prints — `form.cart` becomes a
 * `div.cart`, the quantity field is a `.quantity` wrapping `input.qty`, the
 * button carries `single_add_to_cart_button wp-element-button`. That way every
 * inline preview style here matches the selector the PHP generator will target.
 *
 * The cart icon is NOT drawn as an element: style.scss paints it as a
 * ::before/::after on the button, in the editor exactly as on the front end.
 * Rendering an SVG here as well is what used to show two icons in the canvas.
 * Its size and gap can only be mirrored through the scoped <style> below,
 * because a pseudo-element takes no inline styles.
 *
 * @package Flexa\Block
 */

import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

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
	effective,
	rawDevice,
	withUnit,
	applyTypography,
	wrapperPreviewStyle,
	buttonPreviewStyle,
	editorCss,
	CONTENT_ALIGN_TO_FLEX,
	type CssProps,
} from '@utils';
import {
	ProductAddToCartSettingsPanel,
	ProductAddToCartButtonPanel,
	ProductAddToCartQuantityPanel,
	ProductAddToCartVariationsPanel,
	quantityShown,
} from './panels';
import type { DeviceKey, EditProps, ProductAddToCartAttributes } from '../../types';

/** Form row preview: direction and gap — mirrors the generator's `form.cart`. */
const buildFormStyle = ( attributes: ProductAddToCartAttributes, device: DeviceKey ): CssProps => {
	const stacked = 'stacked' === attributes.cartLayout;
	const s: CssProps = {
		display: 'flex',
		flexWrap: 'wrap',
		alignItems: stacked ? 'flex-start' : 'center',
	};

	if ( stacked ) s.flexDirection = 'column';

	// `text-align` moves nothing in a flex row, so alignment travels on
	// justify-content — and on align-items too once the form stacks.
	const align = CONTENT_ALIGN_TO_FLEX[ attributes.alignment?.[ device ] || '' ];
	if ( align ) {
		s.justifyContent = align;
		if ( stacked ) s.alignItems = align;
	}

	const gap = effective( attributes.gap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );

	return s;
};

/**
 * Quantity group preview — the bordered box around minus / input / plus. The
 * stepper turns the field into a group, so the border, radius, height and
 * background the author picked belong to the group, not to the input.
 */
const buildQuantityGroupStyle = ( attributes: ProductAddToCartAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};

	const height = effective( attributes.quantityHeight, device );
	if ( height.value ) s.height = withUnit( height.value, height.unit || 'px' );

	if ( attributes.quantityColor?.light ) s.color = attributes.quantityColor.light;
	if ( attributes.quantityBackground?.light ) s.backgroundColor = attributes.quantityBackground.light;

	const borderWidth = effective( attributes.quantityBorderWidth, device );
	if ( borderWidth.value ) {
		s.borderStyle = 'solid';
		s.borderWidth = withUnit( borderWidth.value, borderWidth.unit || 'px' );
	}
	if ( attributes.quantityBorderColor?.light ) s.borderColor = attributes.quantityBorderColor.light;

	const radius = effective( attributes.quantityRadius, device );
	if ( radius.value ) s.borderRadius = withUnit( radius.value, radius.unit || 'px' );

	return s;
};

/** Quantity input preview: the width and the text inside the group. */
const buildQuantityStyle = ( attributes: ProductAddToCartAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = { boxSizing: 'border-box' };

	const width = effective( attributes.quantityWidth, device );
	if ( width.value ) s.width = withUnit( width.value, width.unit || 'px' );

	applyTypography( s, rawDevice( attributes.quantityTypography, device ) );

	if ( attributes.quantityColor?.light ) s.color = attributes.quantityColor.light;

	return s;
};

/** Button preview: the shared button mirror plus this block's label typography. */
const buildButtonStyle = ( attributes: ProductAddToCartAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {
		display: 'inline-flex',
		alignItems: 'center',
		...buttonPreviewStyle( attributes ),
	};

	applyTypography( s, rawDevice( attributes.buttonTypography, device ) );

	return s;
};

/**
 * Cart icon preview. The glyph is a pseudo-element painted by style.scss, which
 * inline styles cannot reach, so its size and gap go into the scoped <style>.
 */
const buildIconCss = ( attributes: ProductAddToCartAttributes, device: DeviceKey, blockId?: string ): string => {
	if ( ! blockId || ! attributes.showIcon ) {
		return '';
	}

	const after = 'after' === attributes.iconPosition;
	const selector = `.flexa-product-add-to-cart-${ blockId } button.single_add_to_cart_button::${ after ? 'after' : 'before' }`;
	const rules: string[] = [];

	const size = effective( attributes.iconSize, device );
	if ( size.value ) {
		const v = withUnit( size.value, size.unit || 'px' );
		rules.push( `width:${ v };height:${ v };` );
	}

	const gap = effective( attributes.iconGap, device );
	if ( gap.value ) {
		rules.push( `${ after ? 'margin-left' : 'margin-right' }:${ withUnit( gap.value, gap.unit || 'px' ) };` );
	}

	return rules.length ? `${ selector }{${ rules.join( '' ) }}` : '';
};

/**
 * Add to Cart edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ProductAddToCartAttributes > ): JSX.Element {
	const { cartLayout, buttonText, showIcon, iconPosition, className, responsiveVisibility } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const showQty = quantityShown( attributes );
	const iconAfter = 'after' === iconPosition;

	// `CSS_Helpers::add_button` emits both hover pairs; inline styles cannot
	// express `:hover`, so mirror them in a scoped <style> (guide §6.7).
	const scopedCss = blockId
		? editorCss( [
				{
					selector: `.flexa-product-add-to-cart-${ blockId } button.single_add_to_cart_button:hover`,
					prop: 'color',
					value: attributes.buttonTextColorHover?.light,
				},
				{
					selector: `.flexa-product-add-to-cart-${ blockId } button.single_add_to_cart_button:hover`,
					prop: 'background',
					value: attributes.buttonBackgroundHover?.light,
				},
		  ] ) + buildIconCss( attributes, device, blockId )
		: '';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-product-add-to-cart',
			`flexa-product-add-to-cart--${ cartLayout || 'inline' }`,
			showIcon && `flexa-product-add-to-cart--icon-${ iconAfter ? 'after' : 'before' }`,
			blockId && `flexa-product-add-to-cart-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: wrapperPreviewStyle( attributes, device ),
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real form.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="button" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-product-add-to-cart-inspector">
					<InspectorTabs
						layout={
							<>
								<ProductAddToCartSettingsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ProductAddToCartButtonPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ButtonStylePanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductAddToCartQuantityPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductAddToCartVariationsPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				{ !! scopedCss && <style>{ scopedCss }</style> }
				<div className="cart" style={ buildFormStyle( attributes, device ) }>
					{ showQty && (
						/* `flexa-qty` is what view.js adds on the front end; the mock
						   carries it from the start so the canvas shows the stepper the
						   visitor will get. */
						<div className="quantity flexa-qty" style={ buildQuantityGroupStyle( attributes, device ) }>
							<span className="flexa-qty__btn" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" focusable="false">
									<path d="M5 12h14" />
								</svg>
							</span>
							<input
								className="qty"
								type="number"
								value="1"
								readOnly
								tabIndex={ -1 }
								aria-hidden="true"
								style={ buildQuantityStyle( attributes, device ) }
							/>
							<span className="flexa-qty__btn" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" focusable="false">
									<path d="M12 5v14M5 12h14" />
								</svg>
							</span>
						</div>
					) }
					<button
						type="button"
						className="single_add_to_cart_button button alt wp-element-button"
						style={ buildButtonStyle( attributes, device ) }
					>
						<span>{ buttonText || __( 'Add to cart', 'flexa-block' ) }</span>
					</button>
				</div>
			</div>
		</>
	);
}
