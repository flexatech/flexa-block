/**
 * Taxonomy block — editor component.
 *
 * Assembles the taxonomy-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility / animation. The canvas previews the chosen taxonomy's real terms
 * (read live via `getEntityRecords`) in the chosen display — list, inline row,
 * dropdown or grid — with inline styles that mirror the PHP CSS generator, so
 * what the editor shows matches the front end. Nothing is coloured by default so
 * the block inherits the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';

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
import {
	cn,
	visibilityClasses,
	effective,
	withUnit,
	spacingShorthand,
	applyTypography,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	CONTENT_ALIGN_TO_FLEX,
	type CssProps,
} from '@utils';
import {
	TaxonomyGeneralPanel,
	TaxonomyAffixesPanel,
	TaxonomyLayoutPanel,
	TaxonomyTypographyPanel,
	TaxonomyColorsPanel,
} from './panels';
import type { DeviceKey, EditProps, IconValue, TaxonomyAttributes } from '../../types';

/** A preview term (subset of the REST taxonomy record we display). */
interface PreviewTerm {
	id: number;
	name: string;
	count: number;
	link: string;
	parent: number;
}

/** Representative terms shown before a real taxonomy resolves. */
const SAMPLE_TERMS: PreviewTerm[] = [
	{ id: 1, name: __( 'Design', 'flexa-block' ), count: 12, link: '#', parent: 0 },
	{ id: 2, name: __( 'Development', 'flexa-block' ), count: 8, link: '#', parent: 0 },
	{ id: 3, name: __( 'Marketing', 'flexa-block' ), count: 5, link: '#', parent: 0 },
];

/** Wrapper preview: spacing + background (border/shadow live on each chip). */
const buildWrapperStyle = ( attributes: TaxonomyAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const sp = effective( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;
	applyBackgroundPreview( s, attributes.background );
	return s;
};

/** List preview: alignment, gap and (grid display) the column track count. */
const buildListStyle = ( attributes: TaxonomyAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const a = attributes.alignment || {};
	const align = ( device === 'mobile' ? a.mobile || a.tablet || a.desktop : device === 'tablet' ? a.tablet || a.desktop : a.desktop ) || '';
	const mapped = align ? CONTENT_ALIGN_TO_FLEX[ align ] : '';
	if ( mapped ) s.justifyContent = mapped;
	if ( align ) s.textAlign = align as CssProps[ 'textAlign' ];
	const gap = effective( attributes.gap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	if ( ( attributes.displayStyle || 'list' ) === 'grid' ) {
		const col = effective( attributes.columns, device );
		const n = Math.max( 1, parseInt( String( col.value || '1' ), 10 ) || 1 );
		s.gridTemplateColumns = `repeat(${ n }, minmax(0, 1fr))`;
	}
	return s;
};

/** Link preview: typography, item padding, term colour. */
const buildLinkStyle = ( attributes: TaxonomyAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.typography, device ) );
	const padding = spacingShorthand( effective( attributes.itemPadding, device ) );
	if ( padding ) s.padding = padding;
	if ( attributes.itemColor?.light ) s.color = attributes.itemColor.light;
	return s;
};

/** Item preview: chip background, corner radius, border and box-shadow. */
const buildItemStyle = ( attributes: TaxonomyAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	if ( attributes.itemBackground?.light ) s.background = attributes.itemBackground.light;
	const r = attributes.itemBorderRadius;
	if ( r?.value ) s.borderRadius = withUnit( r.value, r.unit || 'px' );
	applyBorderPreview( s, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	return s;
};

/**
 * Hover CSS for the editor — inline styles can't express `:hover`, so mirror the
 * generator's hover rules in a scoped <style> so the editor matches the front end.
 * Uses the light values (the editor previews light mode) and only what the user set.
 */
const buildHoverCss = ( attributes: TaxonomyAttributes, blockId?: string ): string => {
	if ( ! blockId ) {
		return '';
	}
	const base = `.flexa-taxonomy-${ blockId }`;
	return editorCss( [
		{ selector: `${ base } .flexa-taxonomy__item:hover`, prop: 'color', value: attributes.itemHoverColor?.light },
		{ selector: `${ base } .flexa-taxonomy__link:hover`, prop: 'color', value: attributes.itemHoverColor?.light },
		{ selector: `${ base } .flexa-taxonomy__item:hover`, prop: 'background', value: attributes.itemHoverBackground?.light },
		{ selector: `${ base } .flexa-taxonomy__item:hover .flexa-taxonomy__prefix`, prop: 'color', value: attributes.iconHoverColor?.light },
		{ selector: `${ base } .flexa-taxonomy__item:hover .flexa-taxonomy__suffix`, prop: 'color', value: attributes.iconHoverColor?.light },
	] );
};

/** An affix icon-size inline style for one device. */
const affixSizeStyle = ( size: TaxonomyAttributes[ 'prefixIconSize' ], device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const v = effective( size, device );
	if ( v.value ) {
		const withU = withUnit( v.value, v.unit || 'px' );
		s.fontSize = withU;
		s.width = withU;
	}
	return s;
};

/**
 * Taxonomy edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< TaxonomyAttributes > ): JSX.Element {
	const {
		taxonomy, displayStyle, showCount, hierarchical, limit,
		prefixType, prefixIcon, prefixText, suffixType, suffixIcon, suffixText,
		showSeparator, separator, className, responsiveVisibility, htmlTag,
	} = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	// The chosen taxonomy's terms, read live so the preview matches the site.
	const records = useSelect(
		( select: ( store: string ) => any ) => {
			const tax = taxonomy || 'category';
			const per_page = limit && limit > 0 ? limit : 100;
			const res = select( 'core' ).getEntityRecords( 'taxonomy', tax, { per_page, context: 'view' } );
			return Array.isArray( res )
				? res.map( ( t: any ): PreviewTerm => ( { id: t.id, name: t.name || String( t.id ), count: t.count ?? 0, link: t.link || '#', parent: t.parent ?? 0 } ) )
				: null;
		},
		[ taxonomy, limit ]
	);

	const terms: PreviewTerm[] = records && records.length ? records : SAMPLE_TERMS;
	const display = displayStyle || 'list';

	const wrapperStyle = buildWrapperStyle( attributes, device );
	const listStyle = buildListStyle( attributes, device );
	const linkStyle = buildLinkStyle( attributes, device );
	const itemStyle = buildItemStyle( attributes, device );
	const countStyle: CssProps = attributes.countColor?.light ? { color: attributes.countColor.light } : {};
	const prefixStyle: CssProps = { ...affixSizeStyle( attributes.prefixIconSize, device ), ...( attributes.prefixColor?.light ? { color: attributes.prefixColor.light } : {} ) };
	const suffixStyle: CssProps = { ...affixSizeStyle( attributes.suffixIconSize, device ), ...( attributes.suffixColor?.light ? { color: attributes.suffixColor.light } : {} ) };
	const separatorStyle: CssProps = attributes.separatorColor?.light ? { color: attributes.separatorColor.light } : {};
	const hoverCss = buildHoverCss( attributes, blockId );

	/** Render an affix (icon markup / uploaded image / text) for a slot. */
	const renderAffix = ( type: string | undefined, icon: IconValue | undefined, text: string | undefined, cls: string, style: CssProps ): JSX.Element | null => {
		if ( 'icon' === type ) {
			if ( icon?.markup ) {
				return <span className={ cls } style={ style } dangerouslySetInnerHTML={ { __html: icon.markup } } />;
			}
			if ( icon?.url ) {
				return <img className={ cls } style={ style } src={ icon.url } alt="" />;
			}
			return null;
		}
		if ( 'text' === type && text ) {
			return <span className={ cls } style={ style }>{ text }</span>;
		}
		return null;
	};

	const prefixEl = renderAffix( prefixType, prefixIcon, prefixText, 'flexa-taxonomy__prefix', prefixStyle );
	const suffixEl = renderAffix( suffixType, suffixIcon, suffixText, 'flexa-taxonomy__suffix', suffixStyle );

	/** One term chip (link with prefix, name, count and suffix). */
	const renderLink = ( term: PreviewTerm ): JSX.Element => (
		<a className="flexa-taxonomy__link" style={ linkStyle } href={ term.link } onClick={ ( e ) => e.preventDefault() }>
			{ prefixEl }
			<span className="flexa-taxonomy__name">{ term.name }</span>
			{ showCount !== false && <span className="flexa-taxonomy__count" style={ countStyle }>{ ` (${ term.count })` }</span> }
			{ suffixEl }
		</a>
	);

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-taxonomy',
			`flexa-taxonomy--${ display }`,
			blockId && `flexa-taxonomy-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: wrapperStyle,
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real terms.
	if ( ( attributes as unknown as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="container" />
			</div>
		);
	}

	// Only top-level terms nest their children in list display when hierarchy is on.
	const roots = terms.filter( ( t ) => ! terms.some( ( p ) => p.id === t.parent ) || t.parent === 0 );
	const childrenOf = ( id: number ): PreviewTerm[] => terms.filter( ( t ) => t.parent === id && t.parent !== 0 );
	const useTree = hierarchical && display === 'list';

	const renderItem = ( term: PreviewTerm, index: number, list: PreviewTerm[] ): JSX.Element => {
		const kids = useTree ? childrenOf( term.id ) : [];
		const showSep = display === 'inline' && !! showSeparator && index < list.length - 1;
		return (
			<li key={ term.id } className="flexa-taxonomy__item" style={ itemStyle }>
				{ renderLink( term ) }
				{ showSep && <span className="flexa-taxonomy__separator" style={ separatorStyle } aria-hidden="true">{ separator || '/' }</span> }
				{ kids.length > 0 && (
					<ul className="flexa-taxonomy__list">
						{ kids.map( ( k, i ) => renderItem( k, i, kids ) ) }
					</ul>
				) }
			</li>
		);
	};

	const topLevel = useTree ? roots : terms;

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-taxonomy-inspector">
					<InspectorTabs
						layout={
							<>
								<TaxonomyGeneralPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TaxonomyAffixesPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TaxonomyLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<TaxonomyColorsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TaxonomyTypographyPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				{ display === 'dropdown' ? (
					<select className="flexa-taxonomy__select" style={ listStyle } disabled aria-label={ __( 'Taxonomy terms', 'flexa-block' ) }>
						<option value="">{ __( 'Select…', 'flexa-block' ) }</option>
						{ terms.map( ( t ) => (
							<option key={ t.id } value={ t.link }>{ showCount !== false ? `${ t.name } (${ t.count })` : t.name }</option>
						) ) }
					</select>
				) : (
					<ul className="flexa-taxonomy__list" style={ listStyle }>
						{ topLevel.map( ( t, i ) => renderItem( t, i, topLevel ) ) }
					</ul>
				) }
			</Tag>
		</>
	);
}
