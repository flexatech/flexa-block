/**
 * Breadcrumb block — editor component.
 *
 * Assembles the breadcrumb-specific panels (./panels) with the shared inspector
 * panels (@components) for typography / spacing / background / border / position
 * / visibility. The canvas previews the trail with inline styles that mirror the
 * PHP CSS generator: in Automatic mode it shows a representative sample trail
 * (Home → Category → Current), in Manual mode the hand-listed crumbs. Nothing is
 * coloured by default so the breadcrumb inherits the theme until the user picks a
 * value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

import {
	InspectorTabs,
	TypographyPanel,
	SpacingPanel,
	BackgroundPanel,
	BorderPanel,
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
	editorCss,
	CONTENT_ALIGN_TO_FLEX,
	type CssProps,
} from '@utils';
import {
	BreadcrumbSourcePanel,
	BreadcrumbItemsPanel,
	BreadcrumbLayoutPanel,
	BreadcrumbColorsPanel,
	BreadcrumbSchemaPanel,
} from './panels';
import type { BreadcrumbAttributes, BreadcrumbItem, BreadcrumbSeparatorAttr, DeviceKey, EditProps } from '../../types';

/** The literal glyph for a separator token (mirror of the PHP `separator_char`). */
const separatorChar = ( sep: BreadcrumbSeparatorAttr = {} ): string => {
	switch ( sep.type || 'slash' ) {
		case 'chevron':
			return '›';
		case 'raquo':
			return '»';
		case 'dash':
			return '-';
		case 'custom':
			return sep.custom || '/';
		default:
			return '/';
	}
};

/** A house glyph used when the home icon is on but no custom icon was chosen. */
const DefaultHouse = (
	<svg className="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
		<path d="M3 11l9-7 9 7" />
		<path d="M5 10v10h14V10" />
	</svg>
);

/** Wrapper preview: typography, max width, spacing, background, border. */
const buildWrapperStyle = ( attributes: BreadcrumbAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, effective( attributes.typography, device ) );

	const w = effective( attributes.maxWidth, device );
	if ( w.value ) s.maxWidth = withUnit( w.value, w.unit || 'px' );

	const sp = effective( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, attributes.background );
	applyBorderPreview( s, effective( attributes.border, device ) );
	return s;
};

/** List preview: alignment + the gap between crumbs. */
const buildListStyle = ( attributes: BreadcrumbAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const a = attributes.alignment || {};
	const align = ( device === 'mobile' ? a.mobile || a.tablet || a.desktop : device === 'tablet' ? a.tablet || a.desktop : a.desktop ) || '';
	const mapped = align ? CONTENT_ALIGN_TO_FLEX[ align ] : '';
	if ( mapped ) s.justifyContent = mapped;
	const gap = effective( attributes.itemGap, device );
	if ( gap.value ) s.gap = withUnit( gap.value, gap.unit || 'px' );
	return s;
};

/**
 * Breadcrumb edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< BreadcrumbAttributes > ): JSX.Element {
	const { source, items, homeIcon, homeIconValue, homeText, separator, showCurrent, currentIsLink, className, responsiveVisibility, htmlTag } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const isManual = source === 'manual';
	const sample: BreadcrumbItem[] = [
		{ label: homeText || __( 'Home', 'flexa-block' ), url: '#' },
		{ label: __( 'Category', 'flexa-block' ), url: '#' },
		{ label: __( 'Current page', 'flexa-block' ), url: '' },
	];
	const trail: BreadcrumbItem[] = isManual ? ( Array.isArray( items ) ? items : [] ) : sample;
	const visible = showCurrent === false ? trail.slice( 0, -1 ) : trail;

	const wrapperStyle = buildWrapperStyle( attributes, device );
	const listStyle = buildListStyle( attributes, device );
	const gapStyle: CssProps = listStyle.gap ? { gap: listStyle.gap } : {};
	const linkStyle: CssProps = attributes.linkColor?.light ? { color: attributes.linkColor.light } : {};
	const currentStyle: CssProps = attributes.currentColor?.light ? { color: attributes.currentColor.light } : {};
	const separatorStyle: CssProps = attributes.separatorColor?.light ? { color: attributes.separatorColor.light } : {};

	// Hover colours can't be expressed inline; mirror the generator's `:hover`
	// rules in a scoped <style> so the editor previews them (light values).
	const base = `.flexa-breadcrumb-${ blockId }`;
	const hoverCss = blockId
		? editorCss( [
				{ selector: `${ base } .flexa-breadcrumb__link:hover`, prop: 'color', value: attributes.linkColorHover?.light },
				{ selector: `${ base } .flexa-breadcrumb__current:hover`, prop: 'color', value: attributes.currentColorHover?.light },
				{ selector: `${ base } .flexa-breadcrumb__separator:hover`, prop: 'color', value: attributes.separatorColorHover?.light },
		  ] )
		: '';

	const homeGlyph = homeIcon ? (
		homeIconValue?.markup ? (
			<span className="flexa-breadcrumb__home-icon" dangerouslySetInnerHTML={ { __html: homeIconValue.markup } } />
		) : homeIconValue?.url ? (
			<img className="flexa-breadcrumb__home-icon" src={ homeIconValue.url } alt="" />
		) : (
			<span className="flexa-breadcrumb__home-icon">{ DefaultHouse }</span>
		)
	) : null;

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'nav';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-breadcrumb',
			blockId && `flexa-breadcrumb-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: wrapperStyle,
	} );

	// Inserter hover-preview → faint skeleton mock-up instead of the real trail.
	if ( ( attributes as unknown as { isExamplePreview?: boolean } ).isExamplePreview ) {
		return (
			<div { ...blockProps }>
				<ExamplePreviewSkeleton kind="container" />
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<div className="flexa-inspector flexa-breadcrumb-inspector">
					<InspectorTabs
						layout={
							<>
								<BreadcrumbSourcePanel attributes={ attributes } setAttributes={ setAttributes } />
								{ isManual && <BreadcrumbItemsPanel attributes={ attributes } setAttributes={ setAttributes } /> }
								<BreadcrumbLayoutPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<BreadcrumbColorsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<TypographyPanel attributes={ attributes } setAttributes={ setAttributes } />
								<BackgroundPanel attributes={ attributes } setAttributes={ setAttributes } />
								<BorderPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						advanced={
							<>
								<BreadcrumbSchemaPanel attributes={ attributes } setAttributes={ setAttributes } />
								<PositionPanel attributes={ attributes } setAttributes={ setAttributes } />
								<VisibilityPanel attributes={ attributes } setAttributes={ setAttributes } />
								<AnimationPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
					/>
				</div>
			</InspectorControls>

			<Tag { ...blockProps } aria-label={ __( 'Breadcrumb', 'flexa-block' ) }>
				{ hoverCss && <style>{ hoverCss }</style> }
				<ol className="flexa-breadcrumb__list" style={ listStyle }>
					{ visible.map( ( crumb, i ) => {
						const isCurrent = i === trail.length - 1;
						const label = crumb.label || '';
						const showSep = i < visible.length - 1;
						const inner = isCurrent ? (
							currentIsLink && crumb.url ? (
								<a className="flexa-breadcrumb__current" style={ currentStyle } href={ crumb.url } aria-current="page">
									{ i === 0 && homeGlyph }
									{ label }
								</a>
							) : (
								<span className="flexa-breadcrumb__current" style={ currentStyle } aria-current="page">
									{ i === 0 && homeGlyph }
									{ label }
								</span>
							)
						) : crumb.url ? (
							<a className="flexa-breadcrumb__link" style={ linkStyle } href={ crumb.url }>
								{ i === 0 && homeGlyph }
								{ label }
							</a>
						) : (
							<span className="flexa-breadcrumb__link" style={ linkStyle }>
								{ i === 0 && homeGlyph }
								{ label }
							</span>
						);
						return (
							<li key={ i } className={ cn( 'flexa-breadcrumb__item', isCurrent && 'flexa-breadcrumb__item--current' ) } style={ gapStyle }>
								{ inner }
								{ showSep && (
									<span className="flexa-breadcrumb__separator" style={ separatorStyle } aria-hidden="true">
										{ separatorChar( separator ) }
									</span>
								) }
							</li>
						);
					} ) }
				</ol>
			</Tag>
		</>
	);
}
