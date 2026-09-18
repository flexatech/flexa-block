/**
 * Product Description block — editor component.
 *
 * Assembles the block-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility / animation. There is no product in the editor, so the canvas
 * renders a couple of paragraphs of sample copy — one of them carrying a link,
 * so the link colours preview too — with inline styles mirroring the PHP CSS
 * generator. Nothing is styled by default, so the copy inherits the theme until
 * the user picks a value.
 *
 * @package Flexa\Block
 */

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
import { cn, visibilityClasses, rawDevice, applyTypography, wrapperPreviewStyle, editorCss, type CssProps } from '@utils';
import {
	ProductDescriptionSettingsPanel,
	ProductDescriptionContentPanel,
	ProductDescriptionTitlePanel,
	ProductDescriptionLinksPanel,
	ProductDescriptionNestedHeadingsPanel,
	ProductDescriptionListsPanel,
	ProductDescriptionTablesPanel,
	ProductDescriptionReadMorePanel,
} from './panels';
import type { DeviceKey, EditProps, ProductDescriptionAttributes } from '../../types';

/** Body preview: typography + colour, plus the clamp when it is switched on. */
const buildContentStyle = ( attributes: ProductDescriptionAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.typography, device ) );
	if ( attributes.textColor?.light ) s.color = attributes.textColor.light;
	// style.scss supplies the -webkit-box scaffolding; only the count is inline.
	if ( attributes.enableClamp ) s.WebkitLineClamp = String( attributes.clampLines ?? 4 );
	return s;
};

/** Heading preview: typography + colour. */
const buildTitleStyle = ( attributes: ProductDescriptionAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.titleTypography, device ) );
	if ( attributes.titleColor?.light ) s.color = attributes.titleColor.light;
	return s;
};

/**
 * Product Description edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ProductDescriptionAttributes > ): JSX.Element {
	const { className, responsiveVisibility, showTitle, titleText, titleTag, enableClamp, readMoreText, linkColor } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, attributes.blockId, setAttributes );

	const blockId = attributes.blockId;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any -- dynamic tag name.
	const Tag: any = titleTag || 'h3';

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-product-description',
			enableClamp && 'flexa-product-description--clamped',
			blockId && `flexa-product-description-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: wrapperPreviewStyle( attributes, device ),
	} );

	// Hover colour can't be expressed inline; mirror the generator's `:hover`
	// rule in a scoped <style> so the editor previews it (light value).
	const hoverCss = blockId
		? editorCss( [
				{
					selector: `.flexa-product-description-${ blockId } .flexa-product-description__content a:hover`,
					prop: 'color',
					value: attributes.linkColorHover?.light,
				},
		  ] )
		: '';

	// Inserter hover-preview → faint skeleton mock-up instead of the sample copy.
	if ( ( attributes as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="text" />
			</div>
		);
	}

	const contentStyle = buildContentStyle( attributes, device );
	const linkStyle: CssProps = linkColor?.light ? { color: linkColor.light } : {};
	const toggleStyle: CssProps = attributes.readMoreColor?.light ? { color: attributes.readMoreColor.light } : {};

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-product-description-inspector">
					<InspectorTabs
						layout={
							<>
								<ProductDescriptionSettingsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ProductDescriptionContentPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductDescriptionTitlePanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductDescriptionLinksPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductDescriptionNestedHeadingsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductDescriptionListsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductDescriptionTablesPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ProductDescriptionReadMorePanel attributes={ attributes } setAttributes={ setAttributes } />
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

				{ showTitle && !! titleText && (
					<Tag className="flexa-product-description__title" style={ buildTitleStyle( attributes, device ) }>
						{ titleText }
					</Tag>
				) }

				<div className="flexa-product-description__content" style={ contentStyle }>
					<p>{ __( 'This is where the product description from WooCommerce will appear. It keeps the shop owner’s own formatting — paragraphs, headings, lists and tables.', 'flexa-block' ) }</p>
					<p>
						{ __( 'Crafted from durable materials and finished by hand, it is built to last a lifetime of everyday use. ', 'flexa-block' ) }
						<a href="#" style={ linkStyle } onClick={ ( e ) => e.preventDefault() }>
							{ __( 'See the full specification', 'flexa-block' ) }
						</a>
						{ __( ' for sizes, weights and care instructions.', 'flexa-block' ) }
					</p>
				</div>

				{ enableClamp && (
					<button type="button" className="flexa-product-description__toggle" style={ toggleStyle } disabled>
						{ readMoreText || __( 'Read more', 'flexa-block' ) }
					</button>
				) }
			</div>
		</>
	);
}
