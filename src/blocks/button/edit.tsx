/**
 * Button block — editor component.
 *
 * Reuses the shared inspector panels (@components) for spacing / border / shadow
 * / visibility and adds button-specific panels (content, typography, colours,
 * hover). Following the "prefer theme styles" rule, nothing is styled by default:
 * the button inherits the theme's `wp-element-button` look until the user picks a
 * value, and only chosen values produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, ToggleControl } from '@wordpress/components';

import {
	InspectorTabs,
	Segmented,
	DualColor,
	GradientControl,
	IconPicker,
	TypographyPanel,
	SpacingPanel,
	BorderPanel,
	ShadowPanel,
	VisibilityPanel,
	AnimationPanel,
	CONTENT_ALIGN_OPTIONS,
	useDevice,
	ExamplePreviewSkeleton,
} from '@components';
import {
	cn,
	effective,
	spacingShorthand,
	applyTypography,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
} from '@utils';
import type {
	BackgroundAttr,
	ButtonAttributes,
	ButtonHoverAttr,
	ButtonHoverTransform,
	ButtonIconAttr,
	ColorPair,
	DeviceKey,
	EditProps,
	PanelProps,
} from '../../types';

/** Render a chosen icon in the editor preview (from its stored markup / url). */
const renderIconPreview = ( icon?: ButtonIconAttr ): JSX.Element | null => {
	if ( ! icon ) {
		return null;
	}
	if ( icon.source === 'upload' && icon.url ) {
		return <img className="flexa-icon" src={ icon.url } alt="" />;
	}
	if ( icon.markup ) {
		return <span className="flexa-icon-slot" dangerouslySetInnerHTML={ { __html: icon.markup } } />;
	}
	return null;
};

type CssProps = Record< string, string >;
type ButtonPanelProps = PanelProps< ButtonAttributes >;

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
 * Content panel — link, target, variant, size, alignment, full width, icon.
 */
const ButtonContentPanel = ( { attributes, setAttributes }: ButtonPanelProps ): JSX.Element => {
	const { url, linkTarget, rel, variant, sizePreset, align, fullWidth, icon } = attributes;
	const iconCfg = icon || {};

	return (
		<PanelBody title={ __( 'Button', 'flexa-block' ) } initialOpen={ true }>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Link URL', 'flexa-block' ) }
				type="url"
				value={ url || '' }
				placeholder="https://"
				onChange={ ( v: string ) => setAttributes( { url: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Open in new tab', 'flexa-block' ) }
				checked={ linkTarget === '_blank' }
				onChange={ ( v: boolean ) =>
					setAttributes( {
						linkTarget: v ? '_blank' : '',
						rel: toggleRel( toggleRel( rel, 'noopener', v ), 'noreferrer', v ),
					} )
				}
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Add nofollow', 'flexa-block' ) }
				checked={ relHas( rel, 'nofollow' ) }
				onChange={ ( v: boolean ) => setAttributes( { rel: toggleRel( rel, 'nofollow', v ) } ) }
			/>

			<Segmented
				label={ __( 'Variant', 'flexa-block' ) }
				value={ variant || 'fill' }
				onChange={ ( v ) => setAttributes( { variant: v as ButtonAttributes[ 'variant' ] } ) }
				options={ [
					{ value: 'fill', label: __( 'Fill', 'flexa-block' ) },
					{ value: 'outline', label: __( 'Outline', 'flexa-block' ) },
					{ value: 'ghost', label: __( 'Ghost', 'flexa-block' ) },
				] }
			/>
			<Segmented
				label={ __( 'Size', 'flexa-block' ) }
				value={ sizePreset || 'md' }
				onChange={ ( v ) => setAttributes( { sizePreset: v as ButtonAttributes[ 'sizePreset' ] } ) }
				options={ [
					{ value: 'sm', label: __( 'S', 'flexa-block' ) },
					{ value: 'md', label: __( 'M', 'flexa-block' ) },
					{ value: 'lg', label: __( 'L', 'flexa-block' ) },
				] }
			/>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				value={ align || 'left' }
				onChange={ ( v ) => setAttributes( { align: v as ButtonAttributes[ 'align' ] } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Full width', 'flexa-block' ) }
				checked={ !! fullWidth }
				onChange={ ( v: boolean ) => setAttributes( { fullWidth: v } ) }
			/>

			<IconPicker
				label={ __( 'Icon', 'flexa-block' ) }
				value={ iconCfg }
				onChange={ ( v ) => setAttributes( { icon: { ...iconCfg, ...v } } ) }
			/>
			{ iconCfg.source && iconCfg.source !== 'none' && (
				<Segmented
					label={ __( 'Icon position', 'flexa-block' ) }
					value={ iconCfg.position || 'after' }
					onChange={ ( v ) => setAttributes( { icon: { ...iconCfg, position: v as 'before' | 'after' } } ) }
					options={ [
						{ value: 'before', label: __( 'Before', 'flexa-block' ) },
						{ value: 'after', label: __( 'After', 'flexa-block' ) },
					] }
				/>
			) }
		</PanelBody>
	);
};

/**
 * Colours panel — text colour + background (colour / gradient), light & dark.
 */
const ColorsPanel = ( { attributes, setAttributes }: ButtonPanelProps ): JSX.Element => {
	const bg: BackgroundAttr = attributes.background || {};
	const setBg = ( patch: Partial< BackgroundAttr > ) => setAttributes( { background: { ...bg, ...patch } } );

	return (
		<PanelBody title={ __( 'Colors', 'flexa-block' ) } initialOpen={ true }>
			<DualColor
				label={ __( 'Text', 'flexa-block' ) }
				value={ attributes.textColor || {} }
				onChange={ ( v: ColorPair ) => setAttributes( { textColor: v } ) }
			/>
			<Segmented
				label={ __( 'Background', 'flexa-block' ) }
				value={ bg.type || 'none' }
				onChange={ ( v ) => setBg( { type: v as BackgroundAttr[ 'type' ] } ) }
				options={ [
					{ value: 'none', label: __( 'None', 'flexa-block' ) },
					{ value: 'classic', label: __( 'Color', 'flexa-block' ) },
					{ value: 'gradient', label: __( 'Gradient', 'flexa-block' ) },
				] }
			/>
			{ bg.type === 'classic' && (
				<DualColor label={ __( 'Color', 'flexa-block' ) } value={ bg.color || {} } onChange={ ( v: ColorPair ) => setBg( { color: v } ) } />
			) }
			{ bg.type === 'gradient' && (
				<GradientControl label={ __( 'Gradient', 'flexa-block' ) } value={ bg.gradient || {} } onChange={ ( v: ColorPair ) => setBg( { gradient: v } ) } />
			) }
		</PanelBody>
	);
};

/**
 * Hover panel — text / background / border colours applied on `:hover`.
 */
const HoverPanel = ( { attributes, setAttributes }: ButtonPanelProps ): JSX.Element => {
	const hover: ButtonHoverAttr = attributes.hover || {};
	const set = ( patch: Partial< ButtonHoverAttr > ) => setAttributes( { hover: { ...hover, ...patch } } );

	return (
		<PanelBody title={ __( 'Hover', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Text', 'flexa-block' ) } value={ hover.text || {} } onChange={ ( v: ColorPair ) => set( { text: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ hover.background || {} } onChange={ ( v: ColorPair ) => set( { background: v } ) } />
			<DualColor label={ __( 'Border', 'flexa-block' ) } value={ hover.border || {} } onChange={ ( v: ColorPair ) => set( { border: v } ) } />
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Transform', 'flexa-block' ) }
				value={ hover.transform || 'none' }
				onChange={ ( v: string ) => set( { transform: v as ButtonHoverTransform } ) }
				options={ [
					{ value: 'none', label: __( 'None', 'flexa-block' ) },
					{ value: 'grow', label: __( 'Grow', 'flexa-block' ) },
					{ value: 'shrink', label: __( 'Shrink', 'flexa-block' ) },
					{ value: 'lift', label: __( 'Lift', 'flexa-block' ) },
					{ value: 'push', label: __( 'Push', 'flexa-block' ) },
				] }
			/>
		</PanelBody>
	);
};

/**
 * Build the inline preview style for the button link on the active device.
 *
 * Mirrors the PHP generator: size preset first, then explicit typography /
 * spacing / colour / border overrides win.
 */
const buildLinkStyle = ( attributes: ButtonAttributes, device: DeviceKey ): CssProps => {
	const { typography, spacing, border, background, boxShadow, textColor, sizePreset } = attributes;
	const typo = effective( typography, device );
	const sp = effective( spacing, device );
	const b = effective( border, device );
	const s: CssProps = {};

	// Fallback values matter: the shared --flexa-* tokens are only injected on the
	// front end (Global_Styles), not inside the editor iframe. Without a fallback
	// the padding var resolves to nothing there, making S and L look identical.
	if ( sizePreset === 'sm' ) {
		s.padding = 'var(--flexa-space-sm, 8px) var(--flexa-space-md, 16px)';
		s.fontSize = 'var(--flexa-font-size-sm, 0.875rem)';
	} else if ( sizePreset === 'lg' ) {
		s.padding = 'var(--flexa-space-md, 16px) var(--flexa-space-xl, 40px)';
		s.fontSize = 'var(--flexa-font-size-lg, 1.25rem)';
	}

	applyTypography( s, typo );

	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;

	if ( textColor?.light ) s.color = textColor.light;

	applyBackgroundPreview( s, background );
	applyBorderPreview( s, b );
	const shadow = boxShadowPreview( boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	return s;
};

/**
 * Button edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ButtonAttributes > ): JSX.Element {
	const { text, variant, sizePreset, align, fullWidth, icon, className, responsiveVisibility, spacing, hover } = attributes;
	const hoverTransform = hover?.transform && hover.transform !== 'none' ? hover.transform : '';
	const blockId = attributes.blockId;
	const [ device ] = useDevice();
	const iconCfg = icon || {};

	useEffect( () => {
		const expected = clientId.replace( /[^a-z0-9]/gi, '' ).slice( 0, 8 );
		if ( ! blockId || blockId !== expected ) {
			setAttributes( { blockId: expected } );
		}
	}, [ blockId, clientId, setAttributes ] );

	const linkStyle = buildLinkStyle( attributes, device );
	const margin = spacingShorthand( effective( spacing, device ).margin );

	// Hover state — inline styles can't express `:hover`, so mirror the generator's
	// hover rules on the button link in a scoped <style> (light values, only what's set).
	const hoverCss = blockId
		? editorCss( [
				{ selector: `.flexa-button-${ blockId } .wp-element-button:hover`, prop: 'color', value: hover?.text?.light },
				{ selector: `.flexa-button-${ blockId } .wp-element-button:hover`, prop: 'background-color', value: hover?.background?.light },
				{ selector: `.flexa-button-${ blockId } .wp-element-button:hover`, prop: 'border-color', value: hover?.border?.light },
		  ] )
		: '';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-button',
			`flexa-button--${ variant || 'fill' }`,
			`flexa-button--${ sizePreset || 'md' }`,
			( align || 'left' ) !== 'left' && `flexa-button--${ align }`,
			fullWidth && 'flexa-button--full',
			hoverTransform && `flexa-button--hover-${ hoverTransform }`,
			blockId && `flexa-button-${ blockId }`,
			className,
			responsiveVisibility?.hideOnDesktop && 'flexa-hide-desktop',
			responsiveVisibility?.hideOnTablet && 'flexa-hide-tablet',
			responsiveVisibility?.hideOnMobile && 'flexa-hide-mobile'
		),
		style: margin ? { margin } : {},
	} );

	const iconEl = renderIconPreview( iconCfg );

	// Inserter hover-preview → faint skeleton mock-up instead of the real button.
	if ( ( attributes as any ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="button" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-button-inspector">
					<InspectorTabs
						layout={
							<>
								<ButtonContentPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TypographyPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ColorsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<HoverPanel attributes={ attributes } setAttributes={ setAttributes } />
								<BorderPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ShadowPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						advanced={
							<>
								<VisibilityPanel attributes={ attributes } setAttributes={ setAttributes } />
								<AnimationPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
					/>
				</div>
			</InspectorControls>

			<div { ...blockProps }>
				{ hoverCss && <style>{ hoverCss }</style> }
				<span className="wp-element-button flexa-button__link" style={ linkStyle }>
					{ iconEl && iconCfg.position === 'before' && iconEl }
					<RichText
						tagName="span"
						className="flexa-button__text"
						value={ text || '' }
						allowedFormats={ [ 'core/bold', 'core/italic' ] }
						placeholder={ __( 'Add text…', 'flexa-block' ) }
						onChange={ ( v: string ) => setAttributes( { text: v } ) }
					/>
					{ iconEl && iconCfg.position !== 'before' && iconEl }
				</span>
			</div>
		</>
	);
}
