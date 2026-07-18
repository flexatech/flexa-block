/**
 * Text block — editor component.
 *
 * Assembles the text-specific panels (content layout + colours) with the shared
 * inspector panels (@components) for typography, effects, spacing, background,
 * border, shadow, position and visibility. The canvas renders the rich text with
 * an inline preview mirroring the PHP CSS generator; nothing is styled by default
 * so the text inherits the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';

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
import { TextContentPanel } from './panels';
import type { DeviceKey, EditProps, TextAttributes } from '../../types';

/** Wrapper preview: alignment, spacing, background, border, shadow, overflow. */
const buildWrapperStyle = ( attributes: TextAttributes, device: DeviceKey ): CssProps => {
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

/** Content preview: typography, colour/gradient, stroke, shadow, blend. */
const buildContentStyle = ( attributes: TextAttributes, device: DeviceKey ): CssProps => {
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
 * Text edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< TextAttributes > ): JSX.Element {
	const { content, htmlTag, className, responsiveVisibility, dropCap } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, attributes.blockId, setAttributes );

	const blockId = attributes.blockId;
	const tagName = htmlTag || 'p';

	const blockProps = useBlockProps( {
		className: cn( 'flexa-text', blockId && `flexa-text-${ blockId }`, className, ...visibilityClasses( responsiveVisibility ) ),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Hover colour can't be expressed inline; mirror the generator's `:hover`
	// rule in a scoped <style> so the editor previews it (light value).
	const hoverCss = blockId
		? editorCss( [
				{ selector: `.flexa-text-${ blockId } .flexa-text__content:hover`, prop: 'color', value: attributes.textColorHover?.light },
		  ] )
		: '';

	// Inserter hover-preview → faint skeleton mock-up instead of the real text.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="text" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-text-inspector">
					<InspectorTabs
						layout={
							<>
								<TextContentPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				<RichText
					tagName={ tagName }
					className={ cn( 'flexa-text__content', dropCap && 'has-drop-cap' ) }
					style={ buildContentStyle( attributes, device ) }
					value={ content || '' }
					allowedFormats={ [ 'core/bold', 'core/italic', 'core/link', 'core/text-color' ] }
					placeholder={ __( 'Enter text…', 'flexa-block' ) }
					onChange={ ( v: string ) => setAttributes( { content: v } ) }
				/>
			</div>
		</>
	);
}
