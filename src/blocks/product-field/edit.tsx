/**
 * Product Field block — editor component.
 *
 * There is no product in the editor, so the canvas renders a sample value
 * through the same class names the PHP emits, with inline styles mirroring the
 * CSS generator; nothing is styled by default so the field inherits the theme
 * until the user picks a value.
 *
 * The preview shows only what this block sets — the defaults a surrounding
 * Product Meta contributes are not mirrored here, because they belong to the
 * parent's own preview and land on the front end through a descendant selector.
 *
 * @package Flexa\Block
 */

import { Fragment } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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
import { cn, visibilityClasses, effective, spacingShorthand, applyTypography, editorCss, wrapperPreviewStyle } from '@utils';
import type { CssProps } from '@utils';
import {
	ProductFieldSettingsPanel,
	ProductFieldTermsPanel,
	ProductFieldSkuPanel,
	ProductFieldLabelPanel,
	ProductFieldValuePanel,
	ProductFieldTermBoxPanel,
	isTermField,
} from './panels';
import type { DeviceKey, EditProps, LengthValue, ProductFieldAttributes } from '../../types';

/** A responsive length as a CSS string, or '' when the user left it alone. */
const lengthValue = ( value: LengthValue | undefined ): string => ( value?.value ? `${ value.value }${ value.unit || 'px' }` : '' );

/** Label preview: typography and colour. */
const buildLabelStyle = ( attributes: ProductFieldAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.labelTypography, device ) );
	if ( attributes.labelColor?.light ) s.color = attributes.labelColor.light;
	return s;
};

/** Value preview — the base the term chips start from. */
const buildValueStyle = ( attributes: ProductFieldAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.valueTypography, device ) );
	if ( attributes.valueColor?.light ) s.color = attributes.valueColor.light;
	return s;
};

/** Term preview: the value styling, then the chip's own colour and box. */
const buildTermStyle = ( attributes: ProductFieldAttributes, device: DeviceKey ): CssProps => {
	const { itemColor, itemBackground, itemPadding, itemRadius, itemBorderWidth, itemBorderColor } = attributes;
	const s = buildValueStyle( attributes, device );

	if ( itemColor?.light ) s.color = itemColor.light;
	if ( itemBackground?.light ) s.background = itemBackground.light;

	const padding = spacingShorthand( effective( itemPadding, device ) );
	if ( padding ) s.padding = padding;

	const radius = effective( itemRadius, device );
	if ( radius?.value ) s.borderRadius = `${ radius.value }${ radius.unit || 'px' }`;

	// A single width drives a solid outline — same rule the generator applies.
	const width = effective( itemBorderWidth, device );
	if ( width?.value ) {
		s.borderStyle = 'solid';
		s.borderWidth = `${ width.value }${ width.unit || 'px' }`;
	}
	if ( itemBorderColor?.light ) s.borderColor = itemBorderColor.light;

	return s;
};

/** No product in the editor: a representative value per field. */
const SAMPLE_TERMS: Record< string, string[] > = {
	categories: [ __( 'Accessories', 'flexa-block' ), __( 'Clothing', 'flexa-block' ), __( 'New arrivals', 'flexa-block' ) ],
	tags: [ __( 'sale', 'flexa-block' ), __( 'summer', 'flexa-block' ) ],
};

/**
 * Product Field edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ProductFieldAttributes > ): JSX.Element {
	const { className, responsiveVisibility, field, label, fieldLayout, termLayout, separator, maxTerms, showCopy } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, attributes.blockId, setAttributes );

	const blockId = attributes.blockId;
	const activeField = field || 'sku';
	const activeLayout = 'stacked' === fieldLayout ? 'stacked' : 'inline';
	const activeTermLayout = termLayout || 'inline';
	const isTerms = isTermField( attributes );

	const wrapperStyle = wrapperPreviewStyle( attributes, device );
	const labelGap = lengthValue( effective( attributes.labelGap, device ) );
	if ( labelGap ) wrapperStyle.columnGap = labelGap;

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-product-field',
			`flexa-product-field--${ activeField }`,
			`flexa-product-field--${ activeLayout }`,
			isTerms && `flexa-product-field--terms-${ activeTermLayout }`,
			blockId && `flexa-product-field-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: wrapperStyle,
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of a sample value.
	if ( attributes.isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="text" />
			</div>
		);
	}

	const labelStyle = buildLabelStyle( attributes, device );
	const valueStyle = buildValueStyle( attributes, device );
	const termStyle = buildTermStyle( attributes, device );
	const termGap = lengthValue( effective( attributes.termGap, device ) );

	if ( labelGap ) valueStyle.gap = labelGap;

	// Only the inline layout prints a separator — badges and lists space with flex.
	const sep = 'inline' === activeTermLayout ? separator ?? ', ' : '';

	// Hover colours can't be expressed inline; mirror the generator's `:hover`
	// rules in a scoped <style> so the editor previews them (light values).
	const hoverSelector = `.flexa-product-field-${ blockId } .flexa-product-field__term:hover`;
	const hoverCss = blockId
		? editorCss( [
				{ selector: hoverSelector, prop: 'color', value: attributes.itemColorHover?.light },
				{ selector: hoverSelector, prop: 'background', value: attributes.itemBackgroundHover?.light },
		  ] )
		: '';

	const allTerms = SAMPLE_TERMS[ activeField ] || [];
	const terms = maxTerms && maxTerms > 0 ? allTerms.slice( 0, maxTerms ) : allTerms;

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-product-field-inspector">
					<InspectorTabs
						layout={
							<>
								<ProductFieldSettingsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductFieldTermsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductFieldSkuPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ProductFieldLabelPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductFieldValuePanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductFieldTermBoxPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				{ !! ( label || '' ).trim() && (
					<span className="flexa-product-field__label" style={ labelStyle }>
						{ label }
					</span>
				) }
				<span className="flexa-product-field__value" style={ valueStyle }>
					{ isTerms ? (
						<span className="flexa-product-field__terms" style={ termGap ? { gap: termGap } : undefined }>
							{ terms.map( ( term, i ) => (
								<Fragment key={ term }>
									{ i > 0 && '' !== sep && <span className="flexa-product-field__sep">{ sep }</span> }
									<span className="flexa-product-field__term" style={ termStyle }>
										{ term }
									</span>
								</Fragment>
							) ) }
						</span>
					) : (
						<>
							{ `${ attributes.skuPrefix || '' }${ __( 'FLEXA-0001', 'flexa-block' ) }${ attributes.skuSuffix || '' }` }
							{ !! showCopy && (
								<button type="button" className="flexa-product-field__copy" aria-label={ __( 'Copy SKU', 'flexa-block' ) }>
									<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" focusable="false">
										<rect x="9" y="9" width="12" height="12" rx="2" />
										<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
									</svg>
								</button>
							) }
						</>
					) }
				</span>
			</div>
		</>
	);
}
