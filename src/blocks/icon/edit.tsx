/**
 * Icon block — editor component.
 *
 * Assembles the icon-specific panels with the shared inspector panels
 * (@components) for spacing / background / border / shadow / position /
 * visibility. The canvas renders the chosen glyph (via the shared IconMarkup)
 * inside a frame span with inline styles that mirror the PHP CSS generator;
 * nothing is styled by default so the icon inherits the theme until the user
 * picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

import {
	InspectorTabs,
	Segmented,
	SliderUnit,
	DualColor,
	IconPicker,
	IconMarkup,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
	ShadowPanel,
	PositionPanel,
	VisibilityPanel,
	AnimationPanel,
	CONTENT_ALIGN_OPTIONS,
	useBlockId,
	useDevice,
} from '@components';
import {
	cn,
	visibilityClasses,
	effective,
	withUnit,
	spacingShorthand,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	LENGTH_UNITS,
	type CssProps,
} from '@utils';
import type { DeviceKey, EditProps, IconAttributes, LinkAttr, PanelProps } from '../../types';

type IconPanelProps = PanelProps< IconAttributes >;

/** Border-radius produced by each frame shape (mirrors Icon_CSS::$shape_radius). */
const SHAPE_RADIUS: Record< string, string > = { rounded: '8px', circle: '50%' };

/** A dashed placeholder glyph shown in the editor until an icon is chosen. */
const PLACEHOLDER = (
	<svg className="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" strokeWidth="1.5" aria-hidden="true" focusable="false">
		<rect x="3" y="3" width="18" height="18" rx="2" strokeDasharray="3 3" />
		<path d="M12 8v8M8 12h8" strokeLinecap="round" />
	</svg>
);

/** Add or remove a token from a space-separated `rel` string. */
const toggleRel = ( rel: string | undefined, token: string, on: boolean ): string => {
	const parts = ( rel || '' ).split( /\s+/ ).filter( Boolean ).filter( ( t ) => t !== token );
	if ( on ) {
		parts.push( token );
	}
	return parts.join( ' ' );
};

const relHas = ( rel: string | undefined, token: string ): boolean => ( rel || '' ).split( /\s+/ ).includes( token );

/**
 * Icon panel — the glyph, its size, alignment, shape and an optional link.
 */
const IconPanel = ( { attributes, setAttributes }: IconPanelProps ): JSX.Element => {
	const { icon, iconSize, alignment, shape, link } = attributes;
	const [ device ] = useDevice();
	const iconCfg = icon || {};
	const linkCfg: LinkAttr = link || {};
	const setLink = ( patch: Partial< LinkAttr > ) => setAttributes( { link: { ...linkCfg, ...patch } } );

	return (
		<PanelBody title={ __( 'Icon', 'flexa-block' ) } initialOpen={ true }>
			<IconPicker
				label={ __( 'Icon', 'flexa-block' ) }
				value={ iconCfg }
				onChange={ ( v ) => setAttributes( { icon: { ...iconCfg, ...v } } ) }
			/>
			<SliderUnit
				label={ __( 'Size', 'flexa-block' ) }
				value={ effective( iconSize, device ) }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 300, rem: 20, '%': 100, vw: 100 } }
				onChange={ ( v ) => setAttributes( { iconSize: { ...iconSize, [ device ]: v } } ) }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				value={ alignment?.[ device ] || '' }
				responsive
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<Segmented
				label={ __( 'Shape', 'flexa-block' ) }
				value={ shape || 'square' }
				onChange={ ( v ) => setAttributes( { shape: v as IconAttributes[ 'shape' ] } ) }
				options={ [
					{ value: 'square', label: __( 'Square', 'flexa-block' ) },
					{ value: 'rounded', label: __( 'Rounded', 'flexa-block' ) },
					{ value: 'circle', label: __( 'Circle', 'flexa-block' ) },
				] }
			/>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Link URL', 'flexa-block' ) }
				type="url"
				value={ linkCfg.url || '' }
				placeholder="https://"
				onChange={ ( v: string ) => setLink( { url: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Open in new tab', 'flexa-block' ) }
				checked={ linkCfg.target === '_blank' }
				onChange={ ( v: boolean ) =>
					setLink( {
						target: v ? '_blank' : '',
						rel: toggleRel( toggleRel( linkCfg.rel, 'noopener', v ), 'noreferrer', v ),
					} )
				}
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Add nofollow', 'flexa-block' ) }
				checked={ relHas( linkCfg.rel, 'nofollow' ) }
				onChange={ ( v: boolean ) => setLink( { rel: toggleRel( linkCfg.rel, 'nofollow', v ) } ) }
			/>
		</PanelBody>
	);
};

/**
 * Icon colours panel — glyph / frame colours (with hover) plus the frame's
 * border width and inner padding.
 */
const IconColorsPanel = ( { attributes, setAttributes }: IconPanelProps ): JSX.Element => {
	const { iconColor, iconColorHover, iconBackground, iconBackgroundHover, iconBorderColor, iconBorderColorHover, iconBorderWidth, iconPadding } = attributes;
	const [ device ] = useDevice();

	return (
		<PanelBody title={ __( 'Icon Colors', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Icon color', 'flexa-block' ) } value={ iconColor || {} } onChange={ ( v ) => setAttributes( { iconColor: v } ) } />
			<DualColor label={ __( 'Icon color (hover)', 'flexa-block' ) } value={ iconColorHover || {} } onChange={ ( v ) => setAttributes( { iconColorHover: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ iconBackground || {} } onChange={ ( v ) => setAttributes( { iconBackground: v } ) } />
			<DualColor label={ __( 'Background (hover)', 'flexa-block' ) } value={ iconBackgroundHover || {} } onChange={ ( v ) => setAttributes( { iconBackgroundHover: v } ) } />
			<DualColor label={ __( 'Border color', 'flexa-block' ) } value={ iconBorderColor || {} } onChange={ ( v ) => setAttributes( { iconBorderColor: v } ) } />
			<DualColor label={ __( 'Border color (hover)', 'flexa-block' ) } value={ iconBorderColorHover || {} } onChange={ ( v ) => setAttributes( { iconBorderColorHover: v } ) } />
			<SliderUnit
				label={ __( 'Border width', 'flexa-block' ) }
				value={ effective( iconBorderWidth, device ) }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 20, rem: 2 } }
				onChange={ ( v ) => setAttributes( { iconBorderWidth: { ...iconBorderWidth, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Padding', 'flexa-block' ) }
				value={ effective( iconPadding, device ) }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, rem: 8, '%': 50 } }
				onChange={ ( v ) => setAttributes( { iconPadding: { ...iconPadding, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/** Wrapper preview: alignment + foundational spacing / background / border / shadow / position. */
const buildWrapperStyle = ( attributes: IconAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};

	const sp = effective( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	// Alignment via auto margins — the wrapper is shrink-to-fit (width: fit-content),
	// so text-align has no effect; auto margins slide the whole frame within the
	// column. Emitted after the foundational `margin` shorthand so these longhands
	// win the cascade when both are set.
	const align = attributes.alignment?.[ device ] || attributes.alignment?.desktop || '';
	if ( 'center' === align ) {
		s.marginLeft = 'auto';
		s.marginRight = 'auto';
	} else if ( 'right' === align ) {
		s.marginLeft = 'auto';
		s.marginRight = '0';
	} else if ( 'left' === align ) {
		s.marginLeft = '0';
		s.marginRight = 'auto';
	}

	applyBackgroundPreview( s, attributes.background );
	applyBorderPreview( s, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	const adv = effective( attributes.advancedLayout, device );
	if ( adv.overflow ) s.overflow = adv.overflow;
	if ( adv.position ) s.position = adv.position;

	return s;
};

/** Frame preview: glyph size (font-size drives the 1em glyph), colours, border, padding, shape. */
const buildInnerStyle = ( attributes: IconAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const size = effective( attributes.iconSize, device );
	if ( size.value ) s.fontSize = withUnit( size.value, size.unit || 'px' );
	if ( attributes.iconColor?.light ) s.color = attributes.iconColor.light;
	if ( attributes.iconBackground?.light ) s.background = attributes.iconBackground.light;

	const bw = effective( attributes.iconBorderWidth, device );
	if ( bw.value ) {
		s.borderStyle = 'solid';
		s.borderWidth = withUnit( bw.value, bw.unit || 'px' );
	}
	if ( attributes.iconBorderColor?.light ) s.borderColor = attributes.iconBorderColor.light;

	const pad = effective( attributes.iconPadding, device );
	if ( pad.value ) s.padding = withUnit( pad.value, pad.unit || 'px' );

	const radius = SHAPE_RADIUS[ attributes.shape || 'square' ];
	if ( radius ) s.borderRadius = radius;

	return s;
};

/**
 * Hover CSS for the editor — inline styles can't express `:hover`, so mirror the
 * generator's hover rules in a scoped <style>. Light values only (the editor
 * previews light mode); selectors mirror Icon_CSS exactly.
 */
const buildHoverCss = ( attributes: IconAttributes, blockId?: string ): string => {
	if ( ! blockId ) {
		return '';
	}
	const base = `.flexa-icon-${ blockId }`;
	return editorCss( [
		{ selector: `${ base }:hover .flexa-icon`, prop: 'color', value: attributes.iconColorHover?.light },
		{ selector: `${ base }:hover .flexa-icon__inner`, prop: 'background', value: attributes.iconBackgroundHover?.light },
		{ selector: `${ base }:hover .flexa-icon__inner`, prop: 'border-color', value: attributes.iconBorderColorHover?.light },
	] );
};

/**
 * Icon edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< IconAttributes > ): JSX.Element {
	const { icon, shape, className, responsiveVisibility } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-icon',
			`flexa-icon--shape-${ shape || 'square' }`,
			blockId && `flexa-icon-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	const hasGlyph = !! ( icon?.markup || icon?.url );
	const hoverCss = buildHoverCss( attributes, blockId );

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-icon-inspector">
					<InspectorTabs
						layout={
							<>
								<IconPanel attributes={ attributes } setAttributes={ setAttributes } />
								<IconColorsPanel attributes={ attributes } setAttributes={ setAttributes } />
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

			<div { ...blockProps }>
				{ hoverCss && <style>{ hoverCss }</style> }
				<span className="flexa-icon__inner" style={ buildInnerStyle( attributes, device ) }>
					{ hasGlyph ? <IconMarkup icon={ icon } /> : PLACEHOLDER }
				</span>
			</div>
		</>
	);
}
