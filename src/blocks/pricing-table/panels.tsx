/**
 * Pricing Table block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) plus the shared typography controls come from @components; these
 * cover the parts unique to the pricing table: the width mode and column grid,
 * the plans (edited through the shared ItemListPanel), the optional
 * monthly/yearly billing toggle, the plan-name / price / period / feature
 * styling, the highlighted-plan accent, the "most popular" badge and the CTA
 * button. Following the "prefer theme styles" rule nothing is styled by default
 * — only the values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl, TextareaControl, RangeControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	Dimensions,
	DualColor,
	ColorGradientControl,
	TypographyControls,
	ItemListPanel,
	FieldHead,
	CONTAINER_WIDTH_OPTIONS,
	useDevice,
} from '@components';
import {
	rawDevice,
	patchDevice,
	ALIGN_OPTIONS_COLUMN,
	BORDER_STYLE_OPTIONS,
	HTML_TAGS,
	WIDTH_UNITS,
	LENGTH_UNITS,
	SPACING_UNITS,
} from '@utils';
import type {
	BorderDevice,
	BoxShadowAttr,
	BoxValue,
	LengthValue,
	PanelProps,
	PricingPlan,
	PricingTableAttributes,
	TypographyDevice,
} from '../../types';

type PtPanelProps = PanelProps< PricingTableAttributes >;

/**
 * Border fields for a single part (non-responsive — one border for every plan
 * card). Mirrors the subscribe-form input-border controls: a style select, one
 * width, a light/dark colour and one radius, all applied to the four sides.
 */
const BorderFields = ( { value, onChange }: { value: BorderDevice; onChange: ( patch: Partial< BorderDevice > ) => void } ): JSX.Element => (
	<>
		<SelectControl
			__nextHasNoMarginBottom
			label={ __( 'Style', 'flexa-block' ) }
			value={ value.style || '' }
			options={ BORDER_STYLE_OPTIONS }
			onChange={ ( v: string ) => onChange( { style: v } ) }
		/>
		<SliderUnit
			label={ __( 'Width', 'flexa-block' ) }
			value={ { value: value.width?.top, unit: value.width?.unit || 'px' } }
			units={ LENGTH_UNITS }
			defaultUnit="px"
			max={ { px: 20, em: 2, rem: 2, '%': 100 } }
			onChange={ ( v: LengthValue ) => onChange( { width: { top: v.value ?? '', right: v.value ?? '', bottom: v.value ?? '', left: v.value ?? '', unit: v.unit || 'px' } } ) }
		/>
		<DualColor label={ __( 'Colour', 'flexa-block' ) } value={ value.color || {} } onChange={ ( v ) => onChange( { color: v } ) } />
		<SliderUnit
			label={ __( 'Corner radius', 'flexa-block' ) }
			value={ { value: value.radius?.topLeft, unit: value.radius?.unit || 'px' } }
			units={ LENGTH_UNITS }
			defaultUnit="px"
			max={ { px: 100, em: 10, rem: 10, '%': 100 } }
			onChange={ ( v: LengthValue ) => onChange( { radius: { topLeft: v.value ?? '', topRight: v.value ?? '', bottomRight: v.value ?? '', bottomLeft: v.value ?? '', unit: v.unit || 'px' } } ) }
		/>
	</>
);

/** Box-shadow offset fields — same set the shared ShadowPanel offers. */
const SHADOW_FIELDS: Array< { k: 'horizontal' | 'vertical' | 'blur' | 'spread'; l: string } > = [
	{ k: 'horizontal', l: __( 'X offset', 'flexa-block' ) },
	{ k: 'vertical', l: __( 'Y offset', 'flexa-block' ) },
	{ k: 'blur', l: __( 'Blur', 'flexa-block' ) },
	{ k: 'spread', l: __( 'Spread', 'flexa-block' ) },
];

/**
 * Box-shadow fields for a single part. The shared ShadowPanel is hard-wired to
 * the wrapper's `boxShadow` attribute, so the plan card carries its own copy.
 */
const ShadowFields = ( { value, onChange }: { value: BoxShadowAttr; onChange: ( patch: Partial< BoxShadowAttr > ) => void } ): JSX.Element => (
	<>
		<ToggleControl __nextHasNoMarginBottom label={ __( 'Enable', 'flexa-block' ) } checked={ !! value.enabled } onChange={ ( v: boolean ) => onChange( { enabled: v } ) } />
		{ value.enabled && (
			<>
				{ SHADOW_FIELDS.map( ( f ) => (
					<RangeControl
						key={ f.k }
						__nextHasNoMarginBottom
						label={ f.l }
						value={ parseInt( String( value[ f.k ] ?? '' ), 10 ) || 0 }
						min={ -100 }
						max={ 100 }
						onChange={ ( v?: number ) => onChange( { [ f.k ]: String( v ?? 0 ) } ) }
					/>
				) ) }
				<DualColor label={ __( 'Colour', 'flexa-block' ) } value={ value.color || {} } onChange={ ( v ) => onChange( { color: v } ) } />
				<ToggleControl __nextHasNoMarginBottom label={ __( 'Inset', 'flexa-block' ) } checked={ !! value.inset } onChange={ ( v: boolean ) => onChange( { inset: v } ) } />
			</>
		) }
	</>
);

/** A fresh plan, used when adding one. */
const blankPlan = (): PricingPlan => ( {
	name: '',
	priceMonthly: '',
	priceYearly: '',
	periodMonthly: '',
	periodYearly: '',
	features: '',
	ctaText: '',
	ctaUrl: '',
	highlighted: false,
	badge: '',
} );

/**
 * Width panel — boxed vs full-width, its width, the column grid and the tag.
 */
export const PricingWidthPanel = ( { attributes, setAttributes }: PtPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { containerType, widthBoxed, widthFullWidth, columns, gap, htmlTag } = attributes;
	const isBoxed = containerType === 'boxed';
	const widthGroup: 'widthBoxed' | 'widthFullWidth' = isBoxed ? 'widthBoxed' : 'widthFullWidth';
	const widthVal = ( isBoxed ? widthBoxed : widthFullWidth )?.[ device ] || {};

	const setWidth = ( val: LengthValue ) => {
		const next: Partial< PricingTableAttributes > = {};
		next[ widthGroup ] = { ...attributes[ widthGroup ], [ device ]: { value: val.value ?? '', unit: val.unit || ( isBoxed ? 'px' : '%' ) } };
		setAttributes( next );
	};

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Content Width', 'flexa-block' ) }
				value={ containerType || 'full-width' }
				onChange={ ( v ) => setAttributes( { containerType: v as PricingTableAttributes[ 'containerType' ] } ) }
				options={ CONTAINER_WIDTH_OPTIONS }
			/>
			<SliderUnit
				label={ isBoxed ? __( 'Max Width', 'flexa-block' ) : __( 'Width', 'flexa-block' ) }
				value={ widthVal }
				units={ WIDTH_UNITS }
				defaultUnit={ isBoxed ? 'px' : '%' }
				max={ { px: 3000, '%': 100, vw: 100, rem: 100 } }
				onChange={ setWidth }
			/>
			<SliderUnit
				label={ __( 'Plans per row', 'flexa-block' ) }
				showDevice
				value={ columns?.[ device ] || {} }
				units={ [ { value: '', label: '' } ] }
				defaultUnit=""
				min={ 1 }
				max={ { '': 12 } }
				onChange={ ( v: LengthValue ) => setAttributes( { columns: { ...columns, [ device ]: { value: v.value ?? '', unit: '' } } } ) }
			/>
			<SliderUnit
				label={ __( 'Gap between plans', 'flexa-block' ) }
				showDevice
				value={ gap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { gap: { ...gap, [ device ]: v } } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'div' }
				options={ HTML_TAGS.map( ( t ) => ( { label: t, value: t } ) ) }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>
		</PanelBody>
	);
};

/** Body controls for one plan: name, prices, periods, features, CTA, badge. */
const PlanBody = ( { item, update }: { item: PricingPlan; update: ( patch: Partial< PricingPlan > ) => void } ): JSX.Element => (
	<>
		<TextControl __nextHasNoMarginBottom label={ __( 'Plan name', 'flexa-block' ) } value={ item.name ?? '' } onChange={ ( v: string ) => update( { name: v } ) } />
		<TextControl __nextHasNoMarginBottom label={ __( 'Monthly price', 'flexa-block' ) } value={ item.priceMonthly ?? '' } onChange={ ( v: string ) => update( { priceMonthly: v } ) } />
		<TextControl __nextHasNoMarginBottom label={ __( 'Monthly period', 'flexa-block' ) } help={ __( 'Shown next to the price, e.g. "/month".', 'flexa-block' ) } value={ item.periodMonthly ?? '' } onChange={ ( v: string ) => update( { periodMonthly: v } ) } />
		<TextControl __nextHasNoMarginBottom label={ __( 'Yearly price', 'flexa-block' ) } help={ __( 'Used when the monthly/yearly toggle is on.', 'flexa-block' ) } value={ item.priceYearly ?? '' } onChange={ ( v: string ) => update( { priceYearly: v } ) } />
		<TextControl __nextHasNoMarginBottom label={ __( 'Yearly period', 'flexa-block' ) } value={ item.periodYearly ?? '' } onChange={ ( v: string ) => update( { periodYearly: v } ) } />
		<TextareaControl __nextHasNoMarginBottom label={ __( 'Features', 'flexa-block' ) } help={ __( 'One feature per line.', 'flexa-block' ) } value={ typeof item.features === 'string' ? item.features : ( Array.isArray( item.features ) ? item.features.join( '\n' ) : '' ) } onChange={ ( v: string ) => update( { features: v } ) } />
		<ToggleControl __nextHasNoMarginBottom label={ __( 'Highlight this plan', 'flexa-block' ) } checked={ !! item.highlighted } onChange={ ( v: boolean ) => update( { highlighted: v } ) } />
		<TextControl __nextHasNoMarginBottom label={ __( 'Badge', 'flexa-block' ) } help={ __( 'Small ribbon shown above the name, e.g. "Most popular".', 'flexa-block' ) } value={ item.badge ?? '' } onChange={ ( v: string ) => update( { badge: v } ) } />
		<TextControl __nextHasNoMarginBottom label={ __( 'Button text', 'flexa-block' ) } value={ item.ctaText ?? '' } onChange={ ( v: string ) => update( { ctaText: v } ) } />
		<TextControl __nextHasNoMarginBottom label={ __( 'Button link', 'flexa-block' ) } value={ item.ctaUrl ?? '' } onChange={ ( v: string ) => update( { ctaUrl: v } ) } />
	</>
);

/**
 * Plans panel — the pricing plans, via the shared ItemListPanel (accordion cards
 * + reorder + add/remove).
 */
export const PricingPlansPanel = ( { attributes, setAttributes }: PtPanelProps ): JSX.Element => {
	const plans: PricingPlan[] = Array.isArray( attributes.items ) ? attributes.items : [];

	return (
		<ItemListPanel< PricingPlan >
			title={ __( 'Plans', 'flexa-block' ) }
			addLabel={ __( 'Add plan', 'flexa-block' ) }
			items={ plans }
			onChange={ ( next ) => setAttributes( { items: next } ) }
			newItem={ blankPlan }
			minItems={ 1 }
			renderHeader={ ( item, index ) => ( {
				title: ( item.name || '' ).trim() || __( 'Plan', 'flexa-block' ) + ' ' + ( index + 1 ),
			} ) }
			renderBody={ ( item, _index, update ) => <PlanBody item={ item } update={ update } /> }
		/>
	);
};

/** Billing-switch placement (no `stretch` — the switch hugs its labels). */
const BILLING_ALIGN_OPTIONS = ALIGN_OPTIONS_COLUMN.filter( ( o ) => o.value !== 'stretch' );

/** Which price the table opens on. */
const BILLING_DEFAULT_OPTIONS = [
	{ value: 'monthly', label: __( 'Monthly', 'flexa-block' ) },
	{ value: 'yearly', label: __( 'Yearly', 'flexa-block' ) },
];

/**
 * Options panel — the monthly/yearly billing toggle. Once it is on, its labels,
 * the period the table opens on and the switch placement become editable.
 */
export const PricingOptionsPanel = ( { attributes, setAttributes }: PtPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Options', 'flexa-block' ) } initialOpen={ false }>
		<ToggleControl
			__nextHasNoMarginBottom
			label={ __( 'Monthly / yearly toggle', 'flexa-block' ) }
			help={ __( 'Show a switch that flips every plan between its monthly and yearly price.', 'flexa-block' ) }
			checked={ !! attributes.billingToggle }
			onChange={ ( v: boolean ) => setAttributes( { billingToggle: v } ) }
		/>
		{ attributes.billingToggle && (
			<>
				<FieldHead label={ __( 'Billing switch', 'flexa-block' ) } />
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Monthly label', 'flexa-block' ) }
					placeholder={ __( 'Monthly', 'flexa-block' ) }
					value={ attributes.billingMonthlyLabel ?? '' }
					onChange={ ( v: string ) => setAttributes( { billingMonthlyLabel: v } ) }
				/>
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'Yearly label', 'flexa-block' ) }
					placeholder={ __( 'Yearly', 'flexa-block' ) }
					value={ attributes.billingYearlyLabel ?? '' }
					onChange={ ( v: string ) => setAttributes( { billingYearlyLabel: v } ) }
				/>
				<Segmented
					label={ __( 'Selected by default', 'flexa-block' ) }
					value={ attributes.billingDefault || 'monthly' }
					onChange={ ( v ) => setAttributes( { billingDefault: v as PricingTableAttributes[ 'billingDefault' ] } ) }
					options={ BILLING_DEFAULT_OPTIONS }
				/>
				<Segmented
					label={ __( 'Switch placement', 'flexa-block' ) }
					value={ attributes.billingAlign || 'center' }
					onChange={ ( v ) => setAttributes( { billingAlign: v as PricingTableAttributes[ 'billingAlign' ] } ) }
					options={ BILLING_ALIGN_OPTIONS }
				/>
			</>
		) }
	</PanelBody>
);

/**
 * Billing switch panel (Style tab) — only useful once the toggle is on, so it is
 * rendered by edit.tsx only then. The active option's fill doubles as the colour
 * of the sliding pill, so one control covers both the JS and no-JS states.
 */
export const PricingBillingStylePanel = ( { attributes, setAttributes }: PtPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.billingTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { billingTypography: patchDevice( attributes.billingTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Billing switch', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.billingColor || {} } onChange={ ( v ) => setAttributes( { billingColor: v } ) } />
			<DualColor label={ __( 'Text colour (selected)', 'flexa-block' ) } value={ attributes.billingActiveColor || {} } onChange={ ( v ) => setAttributes( { billingActiveColor: v } ) } />
			<DualColor label={ __( 'Track background', 'flexa-block' ) } value={ attributes.billingBackground || {} } onChange={ ( v ) => setAttributes( { billingBackground: v } ) } />
			<DualColor label={ __( 'Selected background', 'flexa-block' ) } value={ attributes.billingActiveBackground || {} } onChange={ ( v ) => setAttributes( { billingActiveBackground: v } ) } />
			<SliderUnit
				label={ __( 'Corner radius', 'flexa-block' ) }
				value={ attributes.billingRadius || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 999, em: 10, rem: 10, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { billingRadius: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Plan name panel — the plan-name typography and colour.
 */
export const PricingNamePanel = ( { attributes, setAttributes }: PtPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.nameTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { nameTypography: patchDevice( attributes.nameTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Plan name', 'flexa-block' ) } initialOpen={ true }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.nameColor || {} } onChange={ ( v ) => setAttributes( { nameColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Price panel — the price typography and colour, plus the billing-period colour.
 */
export const PricingPricePanel = ( { attributes, setAttributes }: PtPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.priceTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { priceTypography: patchDevice( attributes.priceTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Price', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Price colour', 'flexa-block' ) } value={ attributes.priceColor || {} } onChange={ ( v ) => setAttributes( { priceColor: v } ) } />
			<DualColor label={ __( 'Period colour', 'flexa-block' ) } value={ attributes.periodColor || {} } onChange={ ( v ) => setAttributes( { periodColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Features panel — the feature-list typography and colour.
 */
export const PricingFeaturePanel = ( { attributes, setAttributes }: PtPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.featureTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { featureTypography: patchDevice( attributes.featureTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Features', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.featureColor || {} } onChange={ ( v ) => setAttributes( { featureColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Plan card panel — the border and shadow every card wears, highlighted or not.
 * The highlighted card inherits these unless the Highlighted plan panel below
 * overrides them.
 */
export const PricingPlanPanel = ( { attributes, setAttributes }: PtPanelProps ): JSX.Element => {
	const border: BorderDevice = attributes.planBorder || {};
	const shadow: BoxShadowAttr = attributes.planBoxShadow || {};

	return (
		<PanelBody title={ __( 'Plan card', 'flexa-block' ) } initialOpen={ false }>
			<FieldHead label={ __( 'Border', 'flexa-block' ) } />
			<BorderFields value={ border } onChange={ ( patch ) => setAttributes( { planBorder: { ...border, ...patch } } ) } />
			<FieldHead label={ __( 'Box shadow', 'flexa-block' ) } />
			<ShadowFields value={ shadow } onChange={ ( patch ) => setAttributes( { planBoxShadow: { ...shadow, ...patch } } ) } />
		</PanelBody>
	);
};

/**
 * Highlight panel — the accent used to spotlight the "most popular" plan, plus
 * the border / shadow that set it apart. Each border field left empty falls back
 * to the Plan card value; the accent colour, when set, always wins as the
 * highlighted card's border colour.
 */
export const PricingHighlightPanel = ( { attributes, setAttributes }: PtPanelProps ): JSX.Element => {
	const border: BorderDevice = attributes.highlightBorder || {};
	const shadow: BoxShadowAttr = attributes.highlightBoxShadow || {};

	return (
		<PanelBody title={ __( 'Highlighted plan', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Accent colour', 'flexa-block' ) } value={ attributes.highlightColor || {} } onChange={ ( v ) => setAttributes( { highlightColor: v } ) } />
			<FieldHead label={ __( 'Border', 'flexa-block' ) } />
			<BorderFields value={ border } onChange={ ( patch ) => setAttributes( { highlightBorder: { ...border, ...patch } } ) } />
			<FieldHead label={ __( 'Box shadow', 'flexa-block' ) } />
			<ShadowFields value={ shadow } onChange={ ( patch ) => setAttributes( { highlightBoxShadow: { ...shadow, ...patch } } ) } />
		</PanelBody>
	);
};

/**
 * Badge panel — the "Most popular"-style ribbon. Its background falls back to
 * the highlight accent when left empty, so an untouched badge just works.
 */
export const PricingBadgePanel = ( { attributes, setAttributes }: PtPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.badgeTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { badgeTypography: patchDevice( attributes.badgeTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Badge', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.badgeColor || {} } onChange={ ( v ) => setAttributes( { badgeColor: v } ) } />
			{ /* Background left empty inherits the highlight accent (see CSS generator). */ }
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ attributes.badgeBackground || {} } onChange={ ( v ) => setAttributes( { badgeBackground: v } ) } />
		</PanelBody>
	);
};

/** Button width mode (few values → Segmented). Mirrors comparison-table. */
const BUTTON_WIDTH_OPTIONS = [
	{ value: 'auto', label: __( 'Auto', 'flexa-block' ) },
	{ value: 'full', label: __( 'Full width', 'flexa-block' ) },
];

/**
 * Button panel — one appearance for every plan's call-to-action button. Empty
 * values inherit the theme's button styling (the CTA keeps `wp-element-button`);
 * only what the user sets produces CSS.
 */
export const PricingButtonPanel = ( { attributes, setAttributes }: PtPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Button', 'flexa-block' ) } initialOpen={ false }>
		<Segmented
			label={ __( 'Width', 'flexa-block' ) }
			value={ attributes.buttonWidth || 'auto' }
			onChange={ ( v ) => setAttributes( { buttonWidth: v as PricingTableAttributes[ 'buttonWidth' ] } ) }
			options={ BUTTON_WIDTH_OPTIONS }
		/>
		<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.buttonTextColor || {} } onChange={ ( v ) => setAttributes( { buttonTextColor: v } ) } />
		<DualColor label={ __( 'Text colour (hover)', 'flexa-block' ) } value={ attributes.buttonTextColorHover || {} } onChange={ ( v ) => setAttributes( { buttonTextColorHover: v } ) } />
		<ColorGradientControl
			label={ __( 'Background', 'flexa-block' ) }
			type={ attributes.buttonBackgroundType || 'color' }
			color={ attributes.buttonBackground || {} }
			gradient={ attributes.buttonBackgroundGradient || {} }
			onTypeChange={ ( v ) => setAttributes( { buttonBackgroundType: v } ) }
			onColorChange={ ( v ) => setAttributes( { buttonBackground: v } ) }
			onGradientChange={ ( v ) => setAttributes( { buttonBackgroundGradient: v } ) }
		/>
		<ColorGradientControl
			label={ __( 'Background (hover)', 'flexa-block' ) }
			type={ attributes.buttonBackgroundHoverType || 'color' }
			color={ attributes.buttonBackgroundHover || {} }
			gradient={ attributes.buttonBackgroundHoverGradient || {} }
			onTypeChange={ ( v ) => setAttributes( { buttonBackgroundHoverType: v } ) }
			onColorChange={ ( v ) => setAttributes( { buttonBackgroundHover: v } ) }
			onGradientChange={ ( v ) => setAttributes( { buttonBackgroundHoverGradient: v } ) }
		/>
		<SliderUnit
			label={ __( 'Corner radius', 'flexa-block' ) }
			value={ attributes.buttonRadius || {} }
			units={ LENGTH_UNITS }
			defaultUnit="px"
			max={ { px: 100, em: 10, rem: 10, '%': 100 } }
			onChange={ ( v: LengthValue ) => setAttributes( { buttonRadius: v } ) }
		/>
		<Dimensions
			label={ __( 'Padding', 'flexa-block' ) }
			value={ ( attributes.buttonPadding || {} ) as BoxValue }
			units={ SPACING_UNITS }
			onChange={ ( v: BoxValue ) => setAttributes( { buttonPadding: v } ) }
		/>
	</PanelBody>
);
