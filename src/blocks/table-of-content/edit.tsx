/**
 * Table of Contents block — editor component.
 *
 * Assembles the TOC-specific panels (./panels) with the shared inspector panels
 * (@components) for spacing / background / border / shadow / position /
 * visibility. The list is built from the page's heading blocks live via
 * useSelect, so the editor previews the real outline; the front end rebuilds it
 * from the rendered DOM (view.ts). Inline styles mirror the PHP CSS generator,
 * and nothing is styled by default so the block inherits the theme until the
 * user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';

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
import { cn, effective, withUnit, spacingShorthand, applyTypography, applyBackgroundPreview, applyBorderPreview, boxShadowPreview, editorCss } from '@utils';
import { TocStructurePanel, TocLayoutPanel, TocBehaviorPanel, TocTitlePanel, TocLinksPanel } from './panels';
import type { DeviceKey, EditProps, TableOfContentAttributes, TocHeadingsAttr } from '../../types';

type CssProps = Record< string, string >;

/** One collected heading with its level and plain-text label. */
interface HeadingEntry {
	level: number;
	text: string;
}

/** A heading entry plus its nested children (deeper levels). */
interface HeadingNode extends HeadingEntry {
	children: HeadingNode[];
}

/** Strip any inline HTML so a heading's rich content shows as plain link text. */
const stripHtml = ( html: string ): string => html.replace( /<[^>]+>/g, '' ).trim();

/** Build a nesting tree from a flat, document-ordered list of headings. */
const buildTree = ( items: HeadingEntry[] ): HeadingNode[] => {
	const roots: HeadingNode[] = [];
	const stack: HeadingNode[] = [];
	items.forEach( ( it ) => {
		const node: HeadingNode = { ...it, children: [] };
		while ( stack.length && stack[ stack.length - 1 ].level >= it.level ) {
			stack.pop();
		}
		if ( stack.length ) {
			stack[ stack.length - 1 ].children.push( node );
		} else {
			roots.push( node );
		}
		stack.push( node );
	} );
	return roots;
};

/** Recursively render a preview list (links are inert in the editor). */
const PreviewList = ( { nodes }: { nodes: HeadingNode[] } ): JSX.Element => (
	<ul className="flexa-toc__list">
		{ nodes.map( ( node, i ) => (
			<li key={ i } className="flexa-toc__item">
				<span className="flexa-toc__link">{ node.text || __( '(untitled heading)', 'flexa-block' ) }</span>
				{ node.children.length > 0 && <PreviewList nodes={ node.children } /> }
			</li>
		) ) }
	</ul>
);

/** Wrapper preview: max width, spacing, background, border, shadow + the gap /
 * indent CSS variables the list reads. */
const buildWrapperStyle = ( attributes: TableOfContentAttributes, device: DeviceKey ): CssProps => {
	const { maxWidth, spacing, background, itemGap, indent } = attributes;
	const s: CssProps = {};

	const w = effective( maxWidth, device );
	if ( w.value ) s.maxWidth = withUnit( w.value, w.unit || 'px' );

	const sp = effective( spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, background );
	applyBorderPreview( s, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;

	const gap = effective( itemGap, device );
	if ( gap.value ) s[ '--flexa-toc-gap' ] = withUnit( gap.value, gap.unit || 'px' );
	const ind = effective( indent, device );
	if ( ind.value ) s[ '--flexa-toc-indent' ] = withUnit( ind.value, ind.unit || 'px' );

	return s;
};

/** Body preview: cap the list height so it scrolls when it overflows. */
const buildBodyStyle = ( attributes: TableOfContentAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const h = effective( attributes.listMaxHeight, device );
	if ( h.value ) {
		s.maxHeight = withUnit( h.value, h.unit || 'px' );
		s.overflowY = 'auto';
	}
	return s;
};

/** Title preview: typography + colour. */
const buildTitleStyle = ( attributes: TableOfContentAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.titleTypography, device ) );
	if ( attributes.titleColor?.light ) s.color = attributes.titleColor.light;
	return s;
};

/** Link preview: typography + colour (hover/marker colours are CSS-only). */
const buildLinkStyle = ( attributes: TableOfContentAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.linkTypography, device ) );
	if ( attributes.linkColor?.light ) s.color = attributes.linkColor.light;
	return s;
};

/**
 * Table of Contents edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< TableOfContentAttributes > ): JSX.Element {
	const { title, showTitle, titleTag, headings, markerType, collapsible, emptyText, className, responsiveVisibility, htmlTag } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	// Collect the page's headings (in document order) from every heading block in
	// the editor, so the preview mirrors what the front end will build.
	const pageHeadings: HeadingEntry[] = useSelect(
		( select: ( store: string ) => any ) => {
			const be = select( 'core/block-editor' );
			const ids: string[] = be.getClientIdsWithDescendants();
			const out: HeadingEntry[] = [];
			ids.forEach( ( id ) => {
				const name: string = be.getBlockName( id );
				const attrs = be.getBlockAttributes( id ) || {};
				let level = 0;
				let text = '';
				if ( name === 'core/heading' ) {
					level = Number( attrs.level ) || 2;
					text = String( attrs.content || '' );
				} else if ( name === 'flexa/heading' ) {
					const match = /^h([1-6])$/.exec( String( attrs.tag || 'h2' ) );
					level = match ? Number( match[ 1 ] ) : 0;
					text = String( attrs.content || '' );
				}
				if ( level >= 1 && level <= 6 ) {
					out.push( { level, text: stripHtml( text ) } );
				}
			} );
			return out;
		},
		[]
	);

	const levels = headings || {};
	const selected = pageHeadings.filter( ( h ) => levels[ ( 'h' + h.level ) as keyof TocHeadingsAttr ] );
	const tree = buildTree( selected );

	const wrapperStyle = buildWrapperStyle( attributes, device );
	const bodyStyle = buildBodyStyle( attributes, device );
	const titleStyle = buildTitleStyle( attributes, device );
	const linkStyle = buildLinkStyle( attributes, device );

	// Hover colour can't be expressed inline; mirror the generator's `:hover`
	// rule in a scoped <style> so the editor previews it (light value).
	const hoverCss = blockId
		? editorCss( [
				{ selector: `.flexa-table-of-content-${ blockId } .flexa-toc__link:hover`, prop: 'color', value: attributes.linkHoverColor?.light },
		  ] )
		: '';

	const Tag: any = htmlTag || 'nav';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-toc',
			`flexa-toc--marker-${ markerType || 'bullet' }`,
			blockId && `flexa-table-of-content-${ blockId }`,
			className,
			responsiveVisibility?.hideOnDesktop && 'flexa-hide-desktop',
			responsiveVisibility?.hideOnTablet && 'flexa-hide-tablet',
			responsiveVisibility?.hideOnMobile && 'flexa-hide-mobile'
		),
		style: wrapperStyle,
	} );

	// Inserter hover-preview → faint skeleton mock-up.
	if ( ( attributes as any ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="table-of-content" />
			</div>
		);
	}

	const TitleTag: any = titleTag || 'h2';

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-toc-inspector">
					<InspectorTabs
						layout={
							<>
								<TocStructurePanel attributes={ attributes } setAttributes={ setAttributes } />
								<TocLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TocBehaviorPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<TocTitlePanel attributes={ attributes } setAttributes={ setAttributes } />
								<TocLinksPanel attributes={ attributes } setAttributes={ setAttributes } />
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

			<Tag { ...blockProps }>
				{ hoverCss && <style>{ hoverCss }</style> }
				{ showTitle !== false && (
					<div className="flexa-toc__header">
						<RichText
							tagName={ TitleTag }
							className="flexa-toc__title"
							style={ titleStyle }
							value={ title || '' }
							allowedFormats={ [] }
							placeholder={ __( 'Table of Contents', 'flexa-block' ) }
							onChange={ ( v: string ) => setAttributes( { title: v } ) }
						/>
						{ collapsible && (
							<span className="flexa-toc__icon" aria-hidden="true">
								<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
									<path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
								</svg>
							</span>
						) }
					</div>
				) }
				<div className="flexa-toc__body" style={ bodyStyle }>
					{ tree.length > 0 ? (
						<div className="flexa-toc__link-style" style={ linkStyle }>
							<PreviewList nodes={ tree } />
						</div>
					) : (
						<p className="flexa-toc__empty">{ emptyText || __( 'Add headings to the page to build the table of contents.', 'flexa-block' ) }</p>
					) }
				</div>
			</Tag>
		</>
	);
}
