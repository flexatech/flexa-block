/**
 * Pricing Table block — editor component.
 *
 * Assembles the pricing-table-specific panels (./panels) with the shared
 * inspector panels (@components) for spacing / background / border / shadow /
 * position / visibility. The canvas previews the plan cards with inline styles
 * that mirror the PHP CSS generator; the plans are edited in the Plans panel
 * (the shared ItemListPanel). Nothing is styled by default so the cards inherit
 * the theme until the user picks a value. The monthly/yearly billing toggle is
 * previewed statically here (monthly) — its interactivity is wired on the front
 * end by view.ts.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
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
	applyBgFill,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	type CssProps,
} from '@utils';
import {
	PricingWidthPanel,
	PricingPlansPanel,
	PricingOptionsPanel,
	PricingBillingStylePanel,
	PricingNamePanel,
	PricingPricePanel,
	PricingFeaturePanel,
	PricingPlanPanel,
	PricingHighlightPanel,
	PricingBadgePanel,
	PricingButtonPanel,
} from './panels';
import type { BorderDevice, DeviceKey, EditProps, PricingPlan, PricingTableAttributes } from '../../types';

/** Split a plan's `features` (string, one per line, or string[]) into lines. */
const featureLines = ( features: PricingPlan[ 'features' ] ): string[] => {
	if ( Array.isArray( features ) ) {
		return features.map( ( f ) => String( f ) ).filter( ( f ) => f.trim() !== '' );
	}
	return String( features ?? '' ).split( /\r?\n/ ).map( ( f ) => f.trim() ).filter( ( f ) => f !== '' );
};

/** Wrapper preview: width, spacing, background, border, shadow. */
const buildWrapperStyle = ( attributes: PricingTableAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const isBoxed = attributes.containerType === 'boxed';
	const w = effective( isBoxed ? attributes.widthBoxed : attributes.widthFullWidth, device );
	if ( w.value ) s[ isBoxed ? 'maxWidth' : 'width' ] = withUnit( w.value, w.unit || ( isBoxed ? 'px' : '%' ) );
	if ( isBoxed ) {
		s.marginLeft = 'auto';
		s.marginRight = 'auto';
	}

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

/** Grid preview: column count + gap. */
const buildGridStyle = ( attributes: PricingTableAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const cols = rawDevice( attributes.columns, device ) as { value?: string };
	if ( cols.value ) {
		const count = Math.max( 1, parseInt( cols.value, 10 ) || 1 );
		s.gridTemplateColumns = `repeat(${ count }, minmax(0, 1fr))`;
	}
	const gap = rawDevice( attributes.gap, device ) as { value?: string; unit?: string };
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/**
 * The highlighted card's border: the plan-card border with any highlight
 * override laid on top, field by field. Mirrors Pricing_Table_CSS::merge_border.
 */
const mergeBorder = ( base: BorderDevice = {}, over: BorderDevice = {} ): BorderDevice => ( {
	style: over.style || base.style,
	width: over.width?.top ? over.width : base.width,
	color: over.color?.light ? over.color : base.color,
	radius: over.radius?.topLeft ? over.radius : base.radius,
} );

/** Plan-card preview: border + shadow; the highlighted card adds its overrides. */
const buildPlanStyle = ( attributes: PricingTableAttributes, highlighted: boolean ): CssProps => {
	const s: CssProps = {};

	const border = highlighted ? mergeBorder( attributes.planBorder, attributes.highlightBorder ) : ( attributes.planBorder || {} );
	applyBorderPreview( s, border );
	// The accent colour, when set, always paints the highlighted card's border.
	if ( highlighted && attributes.highlightColor?.light ) s.borderColor = attributes.highlightColor.light;

	const shadowAttr = highlighted && attributes.highlightBoxShadow?.enabled ? attributes.highlightBoxShadow : attributes.planBoxShadow;
	const shadow = boxShadowPreview( shadowAttr || {} );
	if ( shadow ) s.boxShadow = shadow;
	return s;
};

/** Billing-switch preview: placement, track fill and radius. */
const buildBillingStyle = ( attributes: PricingTableAttributes ): CssProps => {
	const s: CssProps = {};
	s.alignSelf = attributes.billingAlign || 'center';
	if ( attributes.billingBackground?.light ) s.backgroundColor = attributes.billingBackground.light;
	if ( attributes.billingRadius?.value ) s.borderRadius = withUnit( attributes.billingRadius.value, attributes.billingRadius.unit || 'px' );
	return s;
};

/** Badge preview: typography, colours (background inherits the accent), padding. */
const buildBadgeStyle = ( attributes: PricingTableAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.badgeTypography, device ) );
	const bg = attributes.badgeBackground?.light || attributes.highlightColor?.light;
	if ( bg ) s.backgroundColor = bg;
	if ( attributes.badgeColor?.light ) s.color = attributes.badgeColor.light;
	return s;
};

/** CTA-button preview: text/background colour, radius, padding (light base). */
const buildButtonStyle = ( attributes: PricingTableAttributes ): CssProps => {
	const s: CssProps = {};
	if ( attributes.buttonTextColor?.light ) s.color = attributes.buttonTextColor.light;
	applyBgFill( s, attributes.buttonBackgroundType, attributes.buttonBackground, attributes.buttonBackgroundGradient );
	if ( attributes.buttonRadius?.value ) s.borderRadius = withUnit( attributes.buttonRadius.value, attributes.buttonRadius.unit || 'px' );
	const padding = spacingShorthand( attributes.buttonPadding );
	if ( padding ) s.padding = padding;
	if ( attributes.buttonWidth === 'full' ) {
		s.display = 'block';
		s.width = '100%';
		s.textAlign = 'center';
	}
	return s;
};

/**
 * Pricing Table edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< PricingTableAttributes > ): JSX.Element {
	const { items, className, responsiveVisibility, htmlTag, containerType, billingToggle } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const plans: PricingPlan[] = Array.isArray( items ) ? items : [];

	// The switch's default period decides which price the canvas previews.
	const isYearly = !! billingToggle && attributes.billingDefault === 'yearly';
	const monthlyLabel = attributes.billingMonthlyLabel?.trim() || __( 'Monthly', 'flexa-block' );
	const yearlyLabel = attributes.billingYearlyLabel?.trim() || __( 'Yearly', 'flexa-block' );

	const wrapperStyle = buildWrapperStyle( attributes, device );
	const gridStyle = buildGridStyle( attributes, device );
	const badgeStyle = buildBadgeStyle( attributes, device );
	const buttonStyle = buildButtonStyle( attributes );
	const billingStyle = buildBillingStyle( attributes );

	const billingRadius = attributes.billingRadius?.value
		? withUnit( attributes.billingRadius.value, attributes.billingRadius.unit || 'px' )
		: undefined;

	const billingOptionStyle: CssProps = {};
	applyTypography( billingOptionStyle, rawDevice( attributes.billingTypography, device ) );
	if ( billingRadius ) billingOptionStyle.borderRadius = billingRadius;
	if ( attributes.billingColor?.light ) billingOptionStyle.color = attributes.billingColor.light;

	const billingActiveStyle: CssProps = { ...billingOptionStyle };
	if ( attributes.billingActiveColor?.light ) billingActiveStyle.color = attributes.billingActiveColor.light;

	// The pill: its fill is the "selected background". Positioned by the effect below.
	const billingIndicatorStyle: CssProps = {};
	if ( billingRadius ) billingIndicatorStyle.borderRadius = billingRadius;

	const nameStyle: CssProps = {};
	applyTypography( nameStyle, rawDevice( attributes.nameTypography, device ) );
	if ( attributes.nameColor?.light ) nameStyle.color = attributes.nameColor.light;

	const priceStyle: CssProps = {};
	applyTypography( priceStyle, rawDevice( attributes.priceTypography, device ) );
	if ( attributes.priceColor?.light ) priceStyle.color = attributes.priceColor.light;

	const periodStyle: CssProps = {};
	if ( attributes.periodColor?.light ) periodStyle.color = attributes.periodColor.light;

	const featureStyle: CssProps = {};
	applyTypography( featureStyle, rawDevice( attributes.featureTypography, device ) );
	if ( attributes.featureColor?.light ) featureStyle.color = attributes.featureColor.light;

	// Rules inline styles can't express — the CTA's `:hover` and the billing pill's
	// fill (which also has to paint the `.is-active` fallback the CSS shows until
	// the pill is measured). Mirrors the generator (Pricing_Table_CSS::add_button /
	// ::add_billing) with the light values. The CTA background is gradient-capable:
	// a gradient hover fill emits `background-image`, a solid one `background`.
	const hoverGradient = attributes.buttonBackgroundHoverType === 'gradient';
	const selectedFill = attributes.billingActiveBackground?.light;
	const scopedCss = blockId
		? editorCss( [
			{ selector: `.flexa-pricing-table-${ blockId } .flexa-pricing-table__cta:hover`, prop: 'color', value: attributes.buttonTextColorHover?.light },
			{
				selector: `.flexa-pricing-table-${ blockId } .flexa-pricing-table__cta:hover`,
				prop: hoverGradient ? 'background-image' : 'background',
				value: hoverGradient ? attributes.buttonBackgroundHoverGradient?.light : attributes.buttonBackgroundHover?.light,
			},
			{ selector: `.flexa-pricing-table-${ blockId } .flexa-pricing-table__billing-indicator`, prop: 'background-color', value: selectedFill },
			{
				selector: `.flexa-pricing-table-${ blockId } .flexa-pricing-table__billing:not(.is-indicator-ready) .flexa-pricing-table__billing-option.is-active`,
				prop: 'background-color',
				value: selectedFill,
			},
		] )
		: '';

	// Slide the pill onto the statically active option so the editor preview
	// matches the front end. Mirrors view.ts: measure the active option and feed
	// its offset + size to the CSS vars, then reveal the pill.
	const billingRef = useRef< HTMLDivElement >( null );
	useEffect( () => {
		// The canvas iframe often has not laid the switch out yet when the effect
		// first runs (the options measure 0 wide), so measure on the next frame —
		// otherwise the pill stays hidden and the CSS `.is-active` fallback is all
		// the user ever sees.
		const frame = window.requestAnimationFrame( () => {
			const billing = billingRef.current;
			if ( ! billing ) return;
			const indicator = billing.querySelector< HTMLElement >( '.flexa-pricing-table__billing-indicator' );
			const active = billing.querySelector< HTMLElement >( '.flexa-pricing-table__billing-option.is-active' );
			if ( ! indicator || ! active || active.offsetWidth === 0 ) {
				billing.classList.remove( 'is-indicator-ready' );
				return;
			}
			indicator.style.setProperty( '--flexa-billing-ind-x', active.offsetLeft + 'px' );
			indicator.style.setProperty( '--flexa-billing-ind-y', active.offsetTop + 'px' );
			indicator.style.setProperty( '--flexa-billing-ind-w', active.offsetWidth + 'px' );
			indicator.style.setProperty( '--flexa-billing-ind-h', active.offsetHeight + 'px' );
			billing.classList.add( 'is-indicator-ready' );
		} );
		return () => window.cancelAnimationFrame( frame );
	}, [ billingToggle, device, isYearly, monthlyLabel, yearlyLabel, attributes.billingAlign, attributes.billingTypography, attributes.billingRadius ] );

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-pricing-table',
			`flexa-pricing-table--${ containerType || 'full-width' }`,
			billingToggle && 'flexa-pricing-table--has-toggle',
			blockId && `flexa-pricing-table-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: wrapperStyle,
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real cards.
	if ( ( attributes as unknown as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="container" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-pricing-table-inspector">
					<InspectorTabs
						layout={
							<>
								<PricingWidthPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PricingPlansPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PricingOptionsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								{ billingToggle && <PricingBillingStylePanel attributes={ attributes } setAttributes={ setAttributes } /> }
								<PricingNamePanel attributes={ attributes } setAttributes={ setAttributes } />
								<PricingPricePanel attributes={ attributes } setAttributes={ setAttributes } />
								<PricingFeaturePanel attributes={ attributes } setAttributes={ setAttributes } />
								<PricingPlanPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PricingHighlightPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PricingBadgePanel attributes={ attributes } setAttributes={ setAttributes } />
								<PricingButtonPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				{ scopedCss && <style>{ scopedCss }</style> }
				{ billingToggle && (
					<div
						ref={ billingRef }
						className="flexa-pricing-table__billing"
						style={ billingStyle }
						data-billing={ isYearly ? 'yearly' : 'monthly' }
						role="group"
						aria-label={ __( 'Billing period', 'flexa-block' ) }
					>
						<span className="flexa-pricing-table__billing-indicator" style={ billingIndicatorStyle } aria-hidden="true"></span>
						<button
							type="button"
							className={ cn( 'flexa-pricing-table__billing-option', ! isYearly && 'is-active' ) }
							style={ isYearly ? billingOptionStyle : billingActiveStyle }
							aria-pressed={ ! isYearly }
						>
							{ monthlyLabel }
						</button>
						<button
							type="button"
							className={ cn( 'flexa-pricing-table__billing-option', isYearly && 'is-active' ) }
							style={ isYearly ? billingActiveStyle : billingOptionStyle }
							aria-pressed={ isYearly }
						>
							{ yearlyLabel }
						</button>
					</div>
				) }
				<div className="flexa-pricing-table__grid" style={ gridStyle }>
					{ plans.map( ( plan, i ) => {
						const lines = featureLines( plan.features );
						const amount = isYearly ? plan.priceYearly : plan.priceMonthly;
						const period = isYearly ? plan.periodYearly : plan.periodMonthly;
						return (
							<div
								key={ i }
								className={ cn( 'flexa-pricing-table__plan', plan.highlighted && 'is-highlighted' ) }
								style={ buildPlanStyle( attributes, !! plan.highlighted ) }
							>
								{ plan.badge ? <span className="flexa-pricing-table__badge" style={ badgeStyle }>{ plan.badge }</span> : null }
								<span className="flexa-pricing-table__name" style={ nameStyle }>{ plan.name || __( 'Plan', 'flexa-block' ) + ' ' + ( i + 1 ) }</span>
								<div className="flexa-pricing-table__price">
									<span className="flexa-pricing-table__amount" style={ priceStyle }>{ amount || '' }</span>
									{ period ? <span className="flexa-pricing-table__period" style={ periodStyle }>{ period }</span> : null }
								</div>
								{ lines.length > 0 && (
									<ul className="flexa-pricing-table__features">
										{ lines.map( ( line, li ) => (
											<li key={ li } className="flexa-pricing-table__feature" style={ featureStyle }>{ line }</li>
										) ) }
									</ul>
								) }
								{ plan.ctaText ? <span className="flexa-pricing-table__cta wp-element-button" style={ buttonStyle }>{ plan.ctaText }</span> : null }
							</div>
						);
					} ) }
				</div>
			</Tag>
		</>
	);
}
