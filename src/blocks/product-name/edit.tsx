/**
 * Product Name block — editor component.
 *
 * Assembles the product-name panels (content layout + link) with the shared
 * inspector panels (@components) for typography, colours, effects, spacing,
 * background, border, shadow, position and visibility. There is no product in
 * the editor, so the canvas renders a placeholder title in the chosen tag with
 * an inline preview mirroring the PHP CSS generator; nothing is styled by
 * default so the title inherits the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

import {
	InspectorTabs,
	TypographyPanel,
	TextColorsPanel,
	EffectsPanel,
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
	applyTextFill,
	applyBorderPreview,
	applyBackgroundPreview,
	boxShadowPreview,
	applyTextStroke,
	applyTextShadow,
	editorCss,
} from '@utils';
import type { CssProps } from '@utils';
import { ProductNameContentPanel } from './panels';
import type { DeviceKey, EditProps, ProductNameAttributes } from '../../types';

/** Wrapper preview: alignment, spacing, background, border, shadow, overflow. */
const buildWrapperStyle = ( attributes: ProductNameAttributes, device: DeviceKey ): CssProps => {
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

/** Title preview: typography, colour/gradient, stroke, shadow, blend. */
const buildTitleStyle = ( attributes: ProductNameAttributes, device: DeviceKey ): CssProps => {
	const { typography, textType, textColor, textGradient, textStroke, textShadow, blendMode } = attributes;
	const s: CssProps = {};

	applyTypography( s, effective( typography, device ) );
	applyTextFill( s, textType, textColor, textGradient );
	applyTextStroke( s, textStroke );
	applyTextShadow( s, textShadow );
	if ( blendMode ) s.mixBlendMode = blendMode;

	return s;
};

/**
 * Product Name edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ProductNameAttributes > ): JSX.Element {
	const { htmlTag, className, responsiveVisibility, linkToProduct } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, attributes.blockId, setAttributes );

	const blockId = attributes.blockId;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any -- dynamic tag name.
	const Tag: any = htmlTag || 'h2';

	const blockProps = useBlockProps( {
		className: cn( 'flexa-product-name', blockId && `flexa-product-name-${ blockId }`, className, ...visibilityClasses( responsiveVisibility ) ),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Hover colour can't be expressed inline; mirror the generator's `:hover`
	// rule in a scoped <style> so the editor previews it (light value).
	const hoverCss = blockId
		? editorCss( [
				{ selector: `.flexa-product-name-${ blockId } .flexa-product-name__title:hover`, prop: 'color', value: attributes.textColorHover?.light },
		  ] )
		: '';

	// Inserter hover-preview → faint skeleton mock-up instead of the real title.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="heading" />
			</div>
		);
	}

	// No product in the editor: show a representative placeholder title.
	const placeholder = __( 'Sample Product Name', 'flexa-block' );
	const titleStyle = buildTitleStyle( attributes, device );
	const title = linkToProduct ? (
		<a className="flexa-product-name__link" href="#" onClick={ ( e ) => e.preventDefault() }>
			{ placeholder }
		</a>
	) : (
		placeholder
	);

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-product-name-inspector">
					<InspectorTabs
						layout={
							<>
								<ProductNameContentPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TypographyPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<TextColorsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<EffectsPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				<Tag className="flexa-product-name__title" style={ titleStyle }>
					{ title }
				</Tag>
			</div>
		</>
	);
}
