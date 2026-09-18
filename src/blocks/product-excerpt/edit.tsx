/**
 * Product Excerpt block — editor component.
 *
 * Assembles the block-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility / animation. There is no product in the editor, so the canvas
 * renders one short paragraph of sample copy carrying a link — so the link
 * colors preview too — with inline styles mirroring the PHP CSS generator.
 * Nothing is styled by default, so the copy inherits the theme until the user
 * picks a value.
 *
 * The word cap applies to that sample as well: the front end trims the excerpt
 * tag-aware (Woo_Helpers::trim_words_html), and the canvas has to show the same
 * length or the control looks broken until the page is viewed.
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
import { ProductExcerptSettingsPanel, ProductExcerptContentPanel } from './panels';
import type { DeviceKey, EditProps, ProductExcerptAttributes } from '../../types';

/** Body preview: typography + colour, plus the clamp when it is switched on. */
const buildContentStyle = ( attributes: ProductExcerptAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.typography, device ) );
	if ( attributes.textColor?.light ) s.color = attributes.textColor.light;
	// style.scss supplies the -webkit-box scaffolding on the front end; the
	// editor canvas is not inside that stylesheet's markup, so mirror it here.
	if ( attributes.enableClamp ) {
		s.display = '-webkit-box';
		s.WebkitBoxOrient = 'vertical';
		s.overflow = 'hidden';
		s.WebkitLineClamp = String( attributes.clampLines ?? 3 );
	}
	return s;
};

/** The three runs of the canvas sample: plain copy, a link, more plain copy. */
interface SampleCopy {
	lead: string;
	link: string;
	tail: string;
}

/**
 * Apply the word cap to the sample copy the way the front end applies it to a
 * real excerpt: count words across the runs in order, keep the link only while
 * it still fits, and close with an ellipsis when anything was dropped.
 *
 * @param limit Word cap; zero or less shows the whole sample.
 * @return The runs to render.
 */
const trimSample = ( limit?: number ): SampleCopy => {
	const lead = __( 'A short summary of the product goes here — the few lines a shopper reads before deciding to scroll on. ', 'flexa-block' );
	const link = __( 'See the full details', 'flexa-block' );
	const tail = __( ' further down the page.', 'flexa-block' );

	const cap = Number( limit ) > 0 ? Number( limit ) : 0;
	if ( ! cap ) {
		return { lead, link, tail };
	}

	let left = cap;

	/** Take at most `left` words off a run, spending the budget as it goes. */
	const take = ( text: string ): string => {
		if ( left <= 0 ) {
			return '';
		}
		const words = text.split( /\s+/ ).filter( Boolean );
		if ( words.length <= left ) {
			left -= words.length;
			return text;
		}
		const kept = words.slice( 0, left ).join( ' ' );
		left = 0;
		return kept;
	};

	const out: SampleCopy = { lead: take( lead ), link: take( link ), tail: take( tail ) };

	// Something was dropped — say so where the copy now ends.
	if ( 0 === left ) {
		if ( out.tail ) {
			out.tail = out.tail + '…';
		} else if ( out.link ) {
			out.tail = '…';
		} else {
			out.lead = out.lead + '…';
		}
	}

	return out;
};

/**
 * Product Excerpt edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ProductExcerptAttributes > ): JSX.Element {
	const { className, responsiveVisibility, enableClamp, linkColor } = attributes;
	const [ device ] = useDevice();

	useBlockId( clientId, attributes.blockId, setAttributes );

	const blockId = attributes.blockId;

	const blockProps = useBlockProps( {
		className: cn(
			'flexa-product-excerpt',
			enableClamp && 'flexa-product-excerpt--clamped',
			blockId && `flexa-product-excerpt-${ blockId }`,
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
					selector: `.flexa-product-excerpt-${ blockId } .flexa-product-excerpt__content a:hover`,
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

	const linkStyle: CssProps = linkColor?.light ? { color: linkColor.light } : {};
	const sample = trimSample( attributes.wordLimit );

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-product-excerpt-inspector">
					<InspectorTabs
						layout={
							<>
								<ProductExcerptSettingsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ProductExcerptContentPanel attributes={ attributes } setAttributes={ setAttributes } />
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

				<div className="flexa-product-excerpt__content" style={ buildContentStyle( attributes, device ) }>
					<p>
						{ sample.lead }
						{ !! sample.link && (
							<a href="#" style={ linkStyle } onClick={ ( e ) => e.preventDefault() }>
								{ sample.link }
							</a>
						) }
						{ sample.tail }
					</p>
				</div>
			</div>
		</>
	);
}
