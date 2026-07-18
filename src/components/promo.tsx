/**
 * Shared "promo content" panels + canvas component.
 *
 * The Banner and CTA blocks share the same content core: a heading, a
 * description, a primary and an optional secondary call-to-action button, plus a
 * content alignment / gap / max-width. Rather than copy that between the two
 * blocks (guide §4.5), the panels, the editor preview markup and the small
 * preview-style builders live here once and both blocks import them. Only the
 * wrapper (banner overlay / min-height, CTA centred-vs-split) is block-specific.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl } from '@wordpress/components';
import { RichText } from '@wordpress/block-editor';

import {
	applyTypography,
	withUnit,
	CONTENT_ALIGN_TO_FLEX,
	type CssProps,
} from '@utils';
import { Segmented, SliderUnit, DualColor, FieldHead, useDevice } from './controls';
import { IconPicker } from './IconPicker';
import { CONTENT_ALIGN_OPTIONS, TEXT_TAG_OPTIONS, TypographyControls } from './panels';
import type {
	ButtonIconAttr,
	ColorPair,
	LengthValue,
	PanelProps,
	PromoButtonHoverAttr,
	PromoContentAttributes,
	TypographyDevice,
} from '../types';

type PromoPanelProps = PanelProps< PromoContentAttributes >;

/* ---------------------------------------------------------------------------
 * Link rel helpers (shared with the button panels).
 * ------------------------------------------------------------------------- */

/** Add or remove a token from a space-separated `rel` string. */
const toggleRel = ( rel: string | undefined, token: string, on: boolean ): string => {
	const parts = ( rel || '' ).split( /\s+/ ).filter( Boolean ).filter( ( t ) => t !== token );
	if ( on ) {
		parts.push( token );
	}
	return parts.join( ' ' );
};

const relHas = ( rel: string | undefined, token: string ): boolean => ( rel || '' ).split( /\s+/ ).includes( token );

/* ---------------------------------------------------------------------------
 * Panels.
 * ------------------------------------------------------------------------- */

/**
 * Content panel (layout tab) — alignment, gap between the text and buttons, and
 * the content max-width. Shared by the Banner and CTA blocks.
 */
export const PromoContentPanel = ( { attributes, setAttributes }: PromoPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { contentAlign, contentGap, contentMaxWidth } = attributes;

	return (
		<PanelBody title={ __( 'Content', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ contentAlign?.[ device ] || ( device === 'desktop' ? 'center' : '' ) }
				onChange={ ( v ) => setAttributes( { contentAlign: { ...contentAlign, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Content gap', 'flexa-block' ) }
				value={ contentGap?.[ device ] || {} }
				units={ [ { value: 'px', label: 'px' }, { value: 'em', label: 'em' }, { value: 'rem', label: 'rem' } ] }
				defaultUnit="px"
				max={ { px: 100, em: 8, rem: 8 } }
				onChange={ ( v: LengthValue ) => setAttributes( { contentGap: { ...contentGap, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Content max width', 'flexa-block' ) }
				value={ contentMaxWidth?.[ device ] || {} }
				units={ [ { value: 'px', label: 'px' }, { value: '%', label: '%' }, { value: 'rem', label: 'rem' } ] }
				defaultUnit="px"
				max={ { px: 1200, '%': 100, rem: 80 } }
				onChange={ ( v: LengthValue ) => setAttributes( { contentMaxWidth: { ...contentMaxWidth, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Heading panel — tag, typography and colour on the shared `heading*` attrs.
 */
export const PromoHeadingPanel = ( { attributes, setAttributes }: PromoPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = attributes.headingTypography?.[ device ] || {};
	const setTypo = ( patch: Partial< TypographyDevice > ) =>
		setAttributes( { headingTypography: { ...attributes.headingTypography, [ device ]: { ...typo, ...patch } } } );

	return (
		<PanelBody title={ __( 'Heading', 'flexa-block' ) } initialOpen={ true }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ attributes.headingTag || 'h2' }
				options={ TEXT_TAG_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { headingTag: v } ) }
			/>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ attributes.headingColor || {} } onChange={ ( v ) => setAttributes( { headingColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Description panel — typography and colour on the shared `description*` attrs.
 */
export const PromoDescriptionPanel = ( { attributes, setAttributes }: PromoPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = attributes.descriptionTypography?.[ device ] || {};
	const setTypo = ( patch: Partial< TypographyDevice > ) =>
		setAttributes( { descriptionTypography: { ...attributes.descriptionTypography, [ device ]: { ...typo, ...patch } } } );

	return (
		<PanelBody title={ __( 'Description', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ attributes.descriptionColor || {} } onChange={ ( v ) => setAttributes( { descriptionColor: v } ) } />
		</PanelBody>
	);
};

/** One button's editable values (prefix-agnostic). */
interface PromoButtonValue {
	url?: string;
	target?: string;
	rel?: string;
	icon?: ButtonIconAttr;
	textColor?: ColorPair;
	bgColor?: ColorPair;
	hover?: PromoButtonHoverAttr;
}

/**
 * The shared field set for a single CTA button — link, new-tab / nofollow, icon
 * and its position, plus text / background / hover colours. Prefix-agnostic: the
 * panel wires it to the primary* or secondary* attributes.
 */
const PromoButtonControls = ( { value, onChange }: { value: PromoButtonValue; onChange: ( patch: Partial< PromoButtonValue > ) => void } ): JSX.Element => {
	const icon: ButtonIconAttr = value.icon || {};
	const hover = value.hover || {};

	return (
		<>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Link URL', 'flexa-block' ) }
				type="url"
				value={ value.url || '' }
				placeholder="https://"
				onChange={ ( v: string ) => onChange( { url: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Open in new tab', 'flexa-block' ) }
				checked={ value.target === '_blank' }
				onChange={ ( v: boolean ) =>
					onChange( {
						target: v ? '_blank' : '',
						rel: toggleRel( toggleRel( value.rel, 'noopener', v ), 'noreferrer', v ),
					} )
				}
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Add nofollow', 'flexa-block' ) }
				checked={ relHas( value.rel, 'nofollow' ) }
				onChange={ ( v: boolean ) => onChange( { rel: toggleRel( value.rel, 'nofollow', v ) } ) }
			/>
			<IconPicker label={ __( 'Icon', 'flexa-block' ) } value={ icon } onChange={ ( v ) => onChange( { icon: { ...icon, ...v } } ) } />
			{ icon.source && icon.source !== 'none' && (
				<Segmented
					label={ __( 'Icon position', 'flexa-block' ) }
					value={ icon.position || 'after' }
					onChange={ ( v ) => onChange( { icon: { ...icon, position: v as 'before' | 'after' } } ) }
					options={ [
						{ value: 'before', label: __( 'Before', 'flexa-block' ) },
						{ value: 'after', label: __( 'After', 'flexa-block' ) },
					] }
				/>
			) }
			<DualColor label={ __( 'Text', 'flexa-block' ) } value={ value.textColor || {} } onChange={ ( v ) => onChange( { textColor: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ value.bgColor || {} } onChange={ ( v ) => onChange( { bgColor: v } ) } />
			<DualColor label={ __( 'Hover text', 'flexa-block' ) } value={ hover.text || {} } onChange={ ( v ) => onChange( { hover: { ...hover, text: v } } ) } />
			<DualColor label={ __( 'Hover background', 'flexa-block' ) } value={ hover.background || {} } onChange={ ( v ) => onChange( { hover: { ...hover, background: v } } ) } />
		</>
	);
};

/**
 * Buttons panel — the primary CTA and an optional secondary CTA, sharing the
 * same field set. Reads/writes the shared primary* / secondary* attributes.
 */
export const PromoButtonsPanel = ( { attributes, setAttributes }: PromoPanelProps ): JSX.Element => {
	const setPrimary = ( patch: Partial< PromoButtonValue > ) =>
		setAttributes( {
			primaryUrl: patch.url ?? attributes.primaryUrl,
			primaryTarget: patch.target ?? attributes.primaryTarget,
			primaryRel: patch.rel ?? attributes.primaryRel,
			primaryIcon: patch.icon ?? attributes.primaryIcon,
			primaryTextColor: patch.textColor ?? attributes.primaryTextColor,
			primaryBgColor: patch.bgColor ?? attributes.primaryBgColor,
			primaryHover: patch.hover ?? attributes.primaryHover,
		} );
	const setSecondary = ( patch: Partial< PromoButtonValue > ) =>
		setAttributes( {
			secondaryUrl: patch.url ?? attributes.secondaryUrl,
			secondaryTarget: patch.target ?? attributes.secondaryTarget,
			secondaryRel: patch.rel ?? attributes.secondaryRel,
			secondaryIcon: patch.icon ?? attributes.secondaryIcon,
			secondaryTextColor: patch.textColor ?? attributes.secondaryTextColor,
			secondaryBgColor: patch.bgColor ?? attributes.secondaryBgColor,
			secondaryHover: patch.hover ?? attributes.secondaryHover,
		} );

	return (
		<PanelBody title={ __( 'Buttons', 'flexa-block' ) } initialOpen={ false }>
			<FieldHead label={ __( 'Primary button', 'flexa-block' ) } />
			<PromoButtonControls
				value={ {
					url: attributes.primaryUrl,
					target: attributes.primaryTarget,
					rel: attributes.primaryRel,
					icon: attributes.primaryIcon,
					textColor: attributes.primaryTextColor,
					bgColor: attributes.primaryBgColor,
					hover: attributes.primaryHover,
				} }
				onChange={ setPrimary }
			/>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show secondary button', 'flexa-block' ) }
				checked={ !! attributes.showSecondary }
				onChange={ ( v: boolean ) => setAttributes( { showSecondary: v } ) }
			/>
			{ attributes.showSecondary && (
				<>
					<FieldHead label={ __( 'Secondary button', 'flexa-block' ) } />
					<PromoButtonControls
						value={ {
							url: attributes.secondaryUrl,
							target: attributes.secondaryTarget,
							rel: attributes.secondaryRel,
							icon: attributes.secondaryIcon,
							textColor: attributes.secondaryTextColor,
							bgColor: attributes.secondaryBgColor,
							hover: attributes.secondaryHover,
						} }
						onChange={ setSecondary }
					/>
				</>
			) }
		</PanelBody>
	);
};

/* ---------------------------------------------------------------------------
 * Canvas preview.
 * ------------------------------------------------------------------------- */

/** Text element preview (heading / description): typography + colour (light). */
const textStyle = ( typo: Partial< TypographyDevice > = {}, color?: ColorPair ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, typo );
	if ( color?.light ) s.color = color.light;
	return s;
};

/** Button preview: text + background colour (radius/padding inherit the theme). */
const buttonStyle = ( textColor?: ColorPair, bgColor?: ColorPair ): CssProps => {
	const s: CssProps = {};
	if ( textColor?.light ) s.color = textColor.light;
	if ( bgColor?.light ) s.backgroundColor = bgColor.light;
	return s;
};

/** Render a chosen icon in the editor preview from its stored markup / url. */
const iconPreview = ( icon?: ButtonIconAttr ): JSX.Element | null => {
	if ( ! icon ) {
		return null;
	}
	if ( icon.source === 'upload' && icon.url ) {
		return <img className="flexa-icon" src={ icon.url } alt="" />;
	}
	if ( icon.markup && icon.source && icon.source !== 'none' ) {
		return <span className="flexa-icon" dangerouslySetInnerHTML={ { __html: icon.markup } } />;
	}
	return null;
};

/** One button in the canvas — icon (before/after) around an editable label. */
const PromoButtonPreview = ( {
	kind,
	text,
	icon,
	style,
	onChange,
	placeholder,
}: {
	kind: 'primary' | 'secondary';
	text?: string;
	icon?: ButtonIconAttr;
	style: CssProps;
	onChange: ( v: string ) => void;
	placeholder: string;
} ): JSX.Element => {
	const iconEl = iconPreview( icon );
	return (
		<span className={ `wp-element-button flexa-promo__button flexa-promo__button--${ kind }` } style={ style }>
			{ iconEl && icon?.position === 'before' && iconEl }
			<RichText
				tagName="span"
				className="flexa-promo__button-text"
				value={ text || '' }
				allowedFormats={ [ 'core/bold', 'core/italic' ] }
				placeholder={ placeholder }
				onChange={ onChange }
			/>
			{ iconEl && icon?.position !== 'before' && iconEl }
		</span>
	);
};

/**
 * The shared promo content markup for the canvas — a heading, a description and
 * the primary + optional secondary CTA buttons, each edited inline with RichText
 * and previewed with inline styles that mirror the PHP CSS generator. `applyAlign`
 * lets the CTA "split" layout opt out of the content alignment (its wrapper CSS
 * places the text and buttons instead).
 */
export const PromoContent = ( {
	attributes,
	setAttributes,
	applyAlign = true,
}: {
	attributes: PromoContentAttributes;
	setAttributes: ( attrs: Partial< PromoContentAttributes > ) => void;
	applyAlign?: boolean;
} ): JSX.Element => {
	const [ device ] = useDevice();

	const align = attributes.contentAlign?.[ device ] || attributes.contentAlign?.desktop || '';
	const gap = attributes.contentGap?.[ device ] || {};
	const maxWidth = attributes.contentMaxWidth?.[ device ] || {};

	const contentStyle: CssProps = {};
	if ( applyAlign && align ) {
		contentStyle.textAlign = align;
		contentStyle.alignItems = CONTENT_ALIGN_TO_FLEX[ align ] || 'center';
	}
	if ( gap.value ) contentStyle.gap = withUnit( gap.value, gap.unit || 'px' );
	if ( maxWidth.value ) contentStyle.maxWidth = withUnit( maxWidth.value, maxWidth.unit || 'px' );

	const HeadingTag = ( attributes.headingTag || 'h2' ) as keyof JSX.IntrinsicElements;

	return (
		<div className="flexa-promo__content" style={ contentStyle }>
			<div className="flexa-promo__text">
				<RichText
					tagName={ HeadingTag }
					className="flexa-promo__heading"
					style={ textStyle( attributes.headingTypography?.[ device ], attributes.headingColor ) }
					value={ attributes.heading || '' }
					allowedFormats={ [ 'core/bold', 'core/italic' ] }
					placeholder={ __( 'Add a heading…', 'flexa-block' ) }
					onChange={ ( v: string ) => setAttributes( { heading: v } ) }
				/>
				<RichText
					tagName="p"
					className="flexa-promo__description"
					style={ textStyle( attributes.descriptionTypography?.[ device ], attributes.descriptionColor ) }
					value={ attributes.description || '' }
					allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
					placeholder={ __( 'Add a supporting description…', 'flexa-block' ) }
					onChange={ ( v: string ) => setAttributes( { description: v } ) }
				/>
			</div>
			<div className="flexa-promo__buttons">
				<PromoButtonPreview
					kind="primary"
					text={ attributes.primaryText }
					icon={ attributes.primaryIcon }
					style={ buttonStyle( attributes.primaryTextColor, attributes.primaryBgColor ) }
					placeholder={ __( 'Button…', 'flexa-block' ) }
					onChange={ ( v: string ) => setAttributes( { primaryText: v } ) }
				/>
				{ attributes.showSecondary && (
					<PromoButtonPreview
						kind="secondary"
						text={ attributes.secondaryText }
						icon={ attributes.secondaryIcon }
						style={ buttonStyle( attributes.secondaryTextColor, attributes.secondaryBgColor ) }
						placeholder={ __( 'Button…', 'flexa-block' ) }
						onChange={ ( v: string ) => setAttributes( { secondaryText: v } ) }
					/>
				) }
			</div>
		</div>
	);
};
