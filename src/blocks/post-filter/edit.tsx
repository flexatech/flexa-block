/**
 * Post Filter block — editor component (parent of the filter children).
 *
 * Renders as a plain <div>, never a real <form>: a form on the editor canvas would
 * swallow Enter and submit buttons inside the block editor's own UI. The children
 * preview their controls disabled — filtering itself only happens on the front end.
 *
 * The children carry no styling attributes, so the CSS the panels here produce is
 * mirrored into a scoped <style> to make their previews look like the front end
 * (the PHP generator only runs at save time).
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

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
	effective,
	rawDevice,
	spacingShorthand,
	radiusShorthand,
	withUnit,
	visibilityClasses,
	applyTypography,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
} from '@utils';
import type { DeviceKey, EditProps, PostFilterAttributes } from '../../types';
import {
	PostFilterTargetPanel,
	PostFilterLayoutPanel,
	PostFilterLabelPanel,
	PostFilterControlPanel,
	PostFilterControlBorderPanel,
	PostFilterButtonPanel,
} from './panels';

type CssProps = Record< string, string >;

/** The controls a filter bar can hold. Nothing else: the front end wraps these in a
 *  <form>, and a stray button or link inside it would submit the filter. */
const ALLOWED_BLOCKS = [ 'flexa/filter-search', 'flexa/filter-taxonomy', 'flexa/filter-reset' ];

/** A fresh bar shows the three controls most filters need. */
const TEMPLATE: Array< [ string, Record< string, unknown > ] | [ string ] > = [
	[ 'flexa/filter-search' ],
	[ 'flexa/filter-taxonomy', { taxonomy: 'category', label: __( 'Category', 'flexa-block' ) } ],
	[ 'flexa/filter-reset' ],
];

/** The bar's own box style for the active device. */
const buildBarStyle = ( attributes: PostFilterAttributes, device: DeviceKey ): CssProps => {
	const { spacing, border, background, boxShadow, advancedLayout, direction, align, gap } = attributes;
	const sp = effective( spacing, device );
	const adv = effective( advancedLayout, device );
	const gapValue = rawDevice( gap, device );
	const style: CssProps = {};

	const padding = spacingShorthand( sp.padding );
	if ( padding ) style.padding = padding;
	applyBorderPreview( style, effective( border, device ) );
	applyBackgroundPreview( style, background );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) style.boxShadow = shadow;
	if ( adv.overflow ) style.overflow = adv.overflow;

	style.display = 'flex';
	style.flexDirection = 'column' === direction ? 'column' : 'row';
	style.flexWrap = 'wrap';
	style.alignItems = align || 'flex-start';
	if ( gapValue?.value ) {
		style.gap = withUnit( gapValue.value, gapValue.unit || 'px' );
	}

	return style;
};

/**
 * Mirror the front-end generator's label / control / button CSS as a scoped
 * <style>, so the children (which have no styling of their own) preview the way
 * they will render. Light values only — the editor canvas previews light mode.
 */
const buildControlsCss = ( a: PostFilterAttributes, blockId: string, device: DeviceKey ): string => {
	if ( ! blockId ) {
		return '';
	}
	const root = `.flexa-post-filter-${ blockId }`;
	const rules: string[] = [];
	const push = ( selector: string, decls: CssProps ): void => {
		const body = Object.keys( decls )
			.filter( ( key ) => decls[ key ] )
			.map( ( key ) => `${ key.replace( /[A-Z]/g, ( c ) => '-' + c.toLowerCase() ) }:${ decls[ key ] }` );
		if ( body.length ) {
			rules.push( `${ selector }{${ body.join( ';' ) }}` );
		}
	};

	const label: CssProps = {};
	applyTypography( label, rawDevice( a.labelTypography, device ) );
	if ( a.labelColor?.light ) label.color = a.labelColor.light;
	push( `${ root } .flexa-filter-field__label`, label );

	const control: CssProps = {};
	applyTypography( control, rawDevice( a.controlTypography, device ) );
	if ( a.controlColor?.light ) control.color = a.controlColor.light;
	if ( a.controlBackground?.light ) control.backgroundColor = a.controlBackground.light;
	applyBorderPreview( control, a.controlBorder || {} );
	const padding = spacingShorthand( a.controlPadding );
	if ( padding ) control.padding = padding;
	const radius = radiusShorthand( a.controlBorder?.radius );
	if ( radius ) control.borderRadius = radius;
	push( `${ root } .flexa-filter-field__control`, control );

	// The author's padding lands on EVERY control, search box included — and this rule
	// outranks the 42px left inset style.scss gives the search input, so the text slides
	// back under the magnifier. The generator compensates on the front end; mirror it, or
	// the canvas is the only place the icon sits on top of the placeholder.
	const left = a.controlPadding?.left;
	if ( padding && left ) {
		const start = withUnit( left, a.controlPadding?.unit || 'px' );
		push( `${ root } .flexa-filter-field__search-wrap .flexa-filter-field__control`, {
			paddingLeft: `calc(${ start } + 26px)`,
		} );
		push( `${ root } .flexa-filter-field__search-icon`, { left: start } );
	}

	if ( a.controlPlaceholderColor?.light ) {
		push( `${ root } .flexa-filter-field__control::placeholder`, { color: a.controlPlaceholderColor.light } );
		push( `${ root } .flexa-filter-field__search-icon`, { color: a.controlPlaceholderColor.light } );
	}

	// A pill is a control at rest — same colours and border as an input, but never the
	// input's corner radius: a chip with a 4px radius is a button, not a chip.
	const pills = `${ root } .flexa-filter-field__options--pills`;
	const pill: CssProps = { ...control };
	delete pill.padding;
	delete pill.borderRadius;
	push( `${ pills } .flexa-filter-field__option-text`, pill );

	// ...and a button when chosen. This is the pair the "Background (hover)" control
	// actually paints, so it has to be mirrored here or the panel looks inert.
	const button: CssProps = {};
	if ( a.buttonColor?.light ) button.color = a.buttonColor.light;
	if ( a.buttonBackground?.light ) button.backgroundColor = a.buttonBackground.light;
	const buttonPadding = spacingShorthand( a.buttonPadding );
	if ( buttonPadding ) button.padding = buttonPadding;
	if ( a.buttonRadius?.value ) button.borderRadius = withUnit( a.buttonRadius.value, a.buttonRadius.unit || 'px' );

	push( `${ pills } .flexa-filter-field__option-input:checked + .flexa-filter-field__option-text`, {
		color: button.color,
		backgroundColor: button.backgroundColor,
	} );

	// The submit button and the BUTTON variant of reset take the fill. The LINK
	// variant takes the accent as ink — filling it turns "clear filters" into a slab.
	push( `${ root } .flexa-post-filter__submit, ${ root } .flexa-filter-reset__control.wp-element-button`, button );
	if ( a.buttonBackground?.light ) {
		push( `${ root } .flexa-filter-reset__control--link`, { color: a.buttonBackground.light } );
	}

	return rules.join( '' );
};

/**
 * Post Filter edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< PostFilterAttributes > ): JSX.Element {
	const { blockId, className, responsiveVisibility, spacing } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const margin = spacingShorthand( effective( spacing, device ).margin );
	const controlsCss = buildControlsCss( attributes, blockId || '', device );

	// :hover and :focus can't be expressed as inline React styles, so the generator's
	// rules for them are mirrored here — every surface the Button colours actually
	// paint, or the panel's hover controls look like they do nothing.
	const root = `.flexa-post-filter-${ blockId }`;
	const pills = `${ root } .flexa-filter-field__options--pills`;
	// Hover previews a chip you can still pick — never the one already chosen.
	const hoverPill = `${ pills } .flexa-filter-field__option:hover .flexa-filter-field__option-input:not(:checked) + .flexa-filter-field__option-text`;
	const accent = attributes.buttonBackground?.light;
	// The focus colour the author named, or the accent they already have. Mirrors the
	// same fallback in Post_Filter_CSS — the canvas has to preview the rule that ships.
	const focusColor =
		attributes.controlBorderFocus?.light || attributes.controlBorderFocus?.dark
			? attributes.controlBorderFocus?.light
			: accent;

	const hoverCss = blockId
		? editorCss( [
			{ selector: `${ root } .flexa-post-filter__submit:hover`, prop: 'color', value: attributes.buttonColorHover?.light },
			{ selector: `${ root } .flexa-post-filter__submit:hover`, prop: 'background-color', value: attributes.buttonBackgroundHover?.light },
			{ selector: `${ root } .flexa-filter-reset__control.wp-element-button:hover`, prop: 'color', value: attributes.buttonColorHover?.light },
			{ selector: `${ root } .flexa-filter-reset__control.wp-element-button:hover`, prop: 'background-color', value: attributes.buttonBackgroundHover?.light },
			{ selector: `${ root } .flexa-filter-reset__control--link:hover`, prop: 'color', value: attributes.buttonBackgroundHover?.light },
			{ selector: hoverPill, prop: 'color', value: attributes.buttonColorHover?.light },
			{ selector: hoverPill, prop: 'background-color', value: attributes.buttonBackgroundHover?.light },
			// The focused field borrows the accent, so it belongs to the page — as ONE
			// line: a coloured border plus a halo. An outline as well would sit outside
			// the border and read as a second frame.
			{ selector: `${ root } .flexa-filter-field__control:focus`, prop: 'border-color', value: focusColor },
			{ selector: `${ root } .flexa-filter-field__control:focus`, prop: 'outline', value: focusColor ? 'none' : undefined },
			{
				selector: `${ root } .flexa-filter-field__control:focus`,
				prop: 'box-shadow',
				value: focusColor ? `0 0 0 3px color-mix(in srgb, ${ focusColor } 22%, transparent)` : undefined,
			},
		] )
		: '';

	const barStyle = buildBarStyle( attributes, device );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-post-filter',
			'flexa-post-filter--editing',
			`flexa-post-filter--${ attributes.direction || 'row' }`,
			blockId && `flexa-post-filter-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: { ...barStyle, ...( margin ? { margin } : {} ) },
	} );

	// The fields sit in their own flex row so the submit button can be their sibling
	// rather than one more thing the layout has to flow around. Mirrors render.php.
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'flexa-post-filter__fields',
			style: {
				display: 'flex',
				flexDirection: barStyle.flexDirection,
				flexWrap: 'wrap',
				alignItems: barStyle.alignItems,
				gap: barStyle.gap,
				flex: '1 1 auto',
			},
		},
		{
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			templateLock: false,
			orientation: 'column' === attributes.direction ? 'vertical' : 'horizontal',
		}
	);

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-post-filter-inspector">
					<InspectorTabs
						layout={
							<>
								<PostFilterTargetPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PostFilterLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<PostFilterLabelPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PostFilterControlPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PostFilterControlBorderPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PostFilterButtonPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				{ controlsCss && <style>{ controlsCss }</style> }
				{ hoverCss && <style>{ hoverCss }</style> }
				{ /* No submit button on the canvas: the bar filters as the visitor types, and
				     the button only exists on the front end for visitors without JavaScript. */ }
				<div { ...innerBlocksProps } />
			</div>
		</>
	);
}
