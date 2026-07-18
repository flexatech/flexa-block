/**
 * Comparison Table block — editor component.
 *
 * Assembles the comparison-table-specific panels (./panels) with the shared
 * inspector panels (@components) for spacing / background / border / shadow /
 * position / visibility. The canvas previews the table with inline styles that
 * mirror the PHP CSS generator; the columns and feature rows are edited in the
 * Columns / Rows panels (the shared ItemListPanel). Nothing is styled by default
 * so the table inherits the theme until the user picks a value.
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
import {
	cn,
	visibilityClasses,
	effective,
	rawDevice,
	withUnit,
	spacingShorthand,
	applyTypography,
	applyBgFill,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	type CssProps,
} from '@utils';
import {
	ComparisonWidthPanel,
	ComparisonColumnsPanel,
	ComparisonRowsPanel,
	ComparisonOptionsPanel,
	ComparisonHeaderPanel,
	ComparisonBadgePanel,
	ComparisonCellPanel,
	ComparisonMarksPanel,
	ComparisonDividersPanel,
	ComparisonButtonPanel,
} from './panels';
import type { ComparisonColumn, ComparisonRow, ComparisonTableAttributes, DeviceKey, EditProps } from '../../types';

/** The active device's cell alignment, cascading tablet/mobile back to desktop. */
const alignFor = ( attributes: ComparisonTableAttributes, device: DeviceKey ): string => {
	const a = attributes.cellAlign || {};
	return ( device === 'mobile' ? a.mobile || a.tablet || a.desktop : device === 'tablet' ? a.tablet || a.desktop : a.desktop ) || '';
};

/** Wrapper preview: width, spacing, background, border, shadow. */
const buildWrapperStyle = ( attributes: ComparisonTableAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const isBoxed = attributes.containerType === 'boxed';
	const w = effective( isBoxed ? attributes.widthBoxed : attributes.widthFullWidth, device );
	if ( w.value ) s[ isBoxed ? 'maxWidth' : 'width' ] = withUnit( w.value, w.unit || ( isBoxed ? 'px' : '%' ) );

	const sp = effective( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, attributes.background );
	applyBorderPreview( s, effective( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	return s;
};

/** Row/column divider borders shared by every cell (light preview). */
const dividerBorders = ( attributes: ComparisonTableAttributes ): CssProps => {
	const s: CssProps = {};
	const color = attributes.dividerColor?.light;
	const width = attributes.dividerWidth?.value ? withUnit( attributes.dividerWidth.value, attributes.dividerWidth.unit || 'px' ) : '1px';
	if ( attributes.showRowDivider !== false && ( color || attributes.dividerWidth?.value ) ) {
		s.borderBottomStyle = 'solid';
		s.borderBottomWidth = width;
		if ( color ) s.borderBottomColor = color;
	}
	if ( attributes.showColumnDivider && ( color || attributes.dividerWidth?.value ) ) {
		s.borderRightStyle = 'solid';
		s.borderRightWidth = width;
		if ( color ) s.borderRightColor = color;
	}
	return s;
};

/** Header-cell preview: typography, colours, padding, alignment, dividers. */
const buildHeaderStyle = ( attributes: ComparisonTableAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = { ...dividerBorders( attributes ) };
	applyTypography( s, rawDevice( attributes.headerTypography, device ) );
	if ( attributes.headerColor?.light ) s.color = attributes.headerColor.light;
	if ( attributes.headerBackground?.light ) s.backgroundColor = attributes.headerBackground.light;
	const padding = spacingShorthand( rawDevice( attributes.cellPadding, device ) );
	if ( padding ) s.padding = padding;
	const align = alignFor( attributes, device );
	if ( align ) s.textAlign = align;
	return s;
};

/** Body-cell preview: typography, colour, padding, alignment, dividers. */
const buildCellStyle = ( attributes: ComparisonTableAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = { ...dividerBorders( attributes ) };
	applyTypography( s, rawDevice( attributes.cellTypography, device ) );
	if ( attributes.cellColor?.light ) s.color = attributes.cellColor.light;
	const padding = spacingShorthand( rawDevice( attributes.cellPadding, device ) );
	if ( padding ) s.padding = padding;
	const align = alignFor( attributes, device );
	if ( align ) s.textAlign = align;
	return s;
};

/** Badge preview: typography, colours (background inherits the spotlight accent), radius, padding. */
const buildBadgeStyle = ( attributes: ComparisonTableAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	applyTypography( s, rawDevice( attributes.badgeTypography, device ) );
	const bg = attributes.badgeBackground?.light || attributes.highlightColor?.light;
	if ( bg ) s.backgroundColor = bg;
	if ( attributes.badgeColor?.light ) s.color = attributes.badgeColor.light;
	if ( attributes.badgeRadius?.value ) s.borderRadius = withUnit( attributes.badgeRadius.value, attributes.badgeRadius.unit || 'px' );
	const padding = spacingShorthand( attributes.badgePadding );
	if ( padding ) s.padding = padding;
	return s;
};

/** CTA-button preview: text/background colour, radius, padding, width (light base). */
const buildButtonStyle = ( attributes: ComparisonTableAttributes ): CssProps => {
	const s: CssProps = {};
	if ( attributes.buttonTextColor?.light ) s.color = attributes.buttonTextColor.light;
	applyBgFill( s, attributes.buttonBackgroundType, attributes.buttonBackground, attributes.buttonBackgroundGradient );
	if ( attributes.buttonRadius?.value ) s.borderRadius = withUnit( attributes.buttonRadius.value, attributes.buttonRadius.unit || 'px' );
	const padding = spacingShorthand( attributes.buttonPadding );
	if ( padding ) s.padding = padding;
	if ( attributes.buttonWidth === 'full' ) {
		s.display = 'block';
		s.width = '100%';
		s.textAlign = 'center';
	}
	return s;
};

/** A check glyph for the chosen style. */
const CheckGlyph = ( { style, colour }: { style: string; colour?: string } ): JSX.Element => {
	const s: CssProps = colour ? { color: colour } : {};
	if ( style === 'star' ) {
		return (
			<span className="flexa-comparison-table__mark flexa-comparison-table__mark--yes" style={ s } aria-hidden="true">
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 3l2.7 5.9 6.3.6-4.7 4.3 1.3 6.2L12 17.8 6.1 20.9l1.3-6.2L2.7 9.5l6.3-.6z" fill="currentColor" /></svg>
			</span>
		);
	}
	if ( style === 'dot' ) {
		return (
			<span className="flexa-comparison-table__mark flexa-comparison-table__mark--yes" style={ s } aria-hidden="true">
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="6" fill="currentColor" /></svg>
			</span>
		);
	}
	return (
		<span className="flexa-comparison-table__mark flexa-comparison-table__mark--yes" style={ s } aria-hidden="true">
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M5 13l4 4L19 7" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" /></svg>
		</span>
	);
};

/** A cross glyph for the chosen style. */
const CrossGlyph = ( { style, colour }: { style: string; colour?: string } ): JSX.Element => {
	const s: CssProps = colour ? { color: colour } : {};
	if ( style === 'dash' || style === 'minus' ) {
		const w = style === 'minus' ? '3' : '2';
		return (
			<span className="flexa-comparison-table__mark flexa-comparison-table__mark--no" style={ s } aria-hidden="true">
				<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M6 12h12" fill="none" stroke="currentColor" strokeWidth={ w } strokeLinecap="round" /></svg>
			</span>
		);
	}
	return (
		<span className="flexa-comparison-table__mark flexa-comparison-table__mark--no" style={ s } aria-hidden="true">
			<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" /></svg>
		</span>
	);
};

/**
 * Comparison Table edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< ComparisonTableAttributes > ): JSX.Element {
	const { columns, rows, className, responsiveVisibility, htmlTag, containerType, stickyHeader, zebra, checkIcon, crossIcon } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const cols: ComparisonColumn[] = Array.isArray( columns ) ? columns : [];
	const featureRows: ComparisonRow[] = Array.isArray( rows ) ? rows : [];

	const headerStyle = buildHeaderStyle( attributes, device );
	const cellStyle = buildCellStyle( attributes, device );
	const buttonStyle = buildButtonStyle( attributes );
	const highlightStyle: CssProps = {};
	if ( attributes.highlightColor?.light ) highlightStyle.backgroundColor = attributes.highlightColor.light;
	{
		const barColor = attributes.spotlightBarColor?.light;
		const barWidthVal = attributes.spotlightBarWidth?.value;
		if ( barColor || barWidthVal ) {
			const barW = barWidthVal ? withUnit( barWidthVal, attributes.spotlightBarWidth?.unit || 'px' ) : '3px';
			highlightStyle.boxShadow = `inset 0 ${ barW } 0 0 ${ barColor || '#06b6d4' }`;
		}
	}
	const badgeStyle = buildBadgeStyle( attributes, device );
	const checkColour = attributes.checkColor?.light;
	const crossColour = attributes.crossColor?.light;

	// Column CTA button :hover — inline styles can't express `:hover`, so mirror the
	// generator's hover rules (Comparison_Table_CSS::add_button) in a scoped <style>
	// using the light values. Background is gradient-capable: a gradient hover fill
	// emits `background-image`, a solid one emits `background` (matching add_bg_fill).
	const hoverGradient = attributes.buttonBackgroundHoverType === 'gradient';
	const hoverCss = blockId
		? editorCss( [
			{ selector: `.flexa-comparison-table-${ blockId } .flexa-comparison-table__cta:hover`, prop: 'color', value: attributes.buttonTextColorHover?.light },
			{
				selector: `.flexa-comparison-table-${ blockId } .flexa-comparison-table__cta:hover`,
				prop: hoverGradient ? 'background-image' : 'background',
				value: hoverGradient ? attributes.buttonBackgroundHoverGradient?.light : attributes.buttonBackgroundHover?.light,
			},
		] )
		: '';

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-comparison-table',
			`flexa-comparison-table--${ containerType || 'full-width' }`,
			stickyHeader && 'flexa-comparison-table--sticky',
			zebra !== false && 'flexa-comparison-table--zebra',
			attributes.showRowDivider !== false && 'flexa-comparison-table--row-divider',
			attributes.showColumnDivider && 'flexa-comparison-table--col-divider',
			blockId && `flexa-comparison-table-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

	// Render one body cell: a boolean token becomes a mark, anything else is text.
	const renderCell = ( raw: string ): JSX.Element | string => {
		const token = String( raw ?? '' ).trim().toLowerCase();
		if ( token === 'true' ) {
			return <CheckGlyph style={ checkIcon || 'check' } colour={ checkColour } />;
		}
		if ( token === 'false' ) {
			return <CrossGlyph style={ crossIcon || 'cross' } colour={ crossColour } />;
		}
		return raw ?? '';
	};

	// Inserter hover-preview → faint skeleton mock-up instead of the real table.
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
				<div className="flexa-inspector flexa-comparison-table-inspector">
					<InspectorTabs
						layout={
							<>
								<ComparisonWidthPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ComparisonColumnsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ComparisonRowsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ComparisonOptionsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<ComparisonHeaderPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ComparisonBadgePanel attributes={ attributes } setAttributes={ setAttributes } />
								<ComparisonCellPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ComparisonMarksPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ComparisonDividersPanel attributes={ attributes } setAttributes={ setAttributes } />
								<ComparisonButtonPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				<div className="flexa-comparison-table__scroll">
					<table className="flexa-comparison-table__table">
						<thead>
							<tr className="flexa-comparison-table__head-row">
								<th className="flexa-comparison-table__corner" style={ headerStyle } />
								{ cols.map( ( col, i ) => (
									<th
										key={ i }
										className={ cn( 'flexa-comparison-table__col', col.highlighted && 'is-highlighted' ) }
										style={ col.highlighted ? { ...headerStyle, ...highlightStyle } : headerStyle }
										scope="col"
									>
										<div className="flexa-comparison-table__col-inner">
											{ col.badge ? <span className="flexa-comparison-table__badge" style={ badgeStyle }>{ col.badge }</span> : null }
											{ col.imageUrl ? <img className="flexa-comparison-table__logo" src={ col.imageUrl } alt="" /> : null }
											<span className="flexa-comparison-table__col-title">{ col.title || __( 'Column', 'flexa-block' ) + ' ' + ( i + 1 ) }</span>
											{ col.subtitle ? <span className="flexa-comparison-table__col-subtitle">{ col.subtitle }</span> : null }
											{ col.ctaText ? <span className="flexa-comparison-table__cta wp-element-button" style={ buttonStyle }>{ col.ctaText }</span> : null }
										</div>
									</th>
								) ) }
							</tr>
						</thead>
						<tbody>
							{ featureRows.map( ( row, r ) => (
								<tr key={ r } className="flexa-comparison-table__row">
									<th scope="row" className="flexa-comparison-table__label" style={ cellStyle }>
										{ row.label || '' }
									</th>
									{ cols.map( ( col, c ) => (
										<td
											key={ c }
											className={ cn( 'flexa-comparison-table__cell', col.highlighted && 'is-highlighted' ) }
											style={ cellStyle }
										>
											{ renderCell( ( row.values || [] )[ c ] ?? '' ) }
										</td>
									) ) }
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			</Tag>
		</>
	);
}
