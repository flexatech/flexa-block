/**
 * Data Table block — editor component.
 *
 * Assembles the data-table-specific panels (./panels) with the shared inspector
 * panels (@components) for spacing / background / border / shadow / position /
 * visibility. The columns and rows are edited live in the canvas: each header
 * and body cell is a plain input, with add / remove buttons for columns and
 * rows. The preview mirrors the PHP CSS generator with inline styles. Nothing is
 * styled by default so the table inherits the theme until the user picks a value.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';

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
	rawDevice,
	withUnit,
	spacingShorthand,
	applyTypography,
	applyBackgroundPreview,
	applyBorderPreview,
	boxShadowPreview,
	editorCss,
	type CssProps,
} from '@utils';
import {
	DataTableOptionsPanel,
	DataTableHeaderPanel,
	DataTableCellPanel,
} from './panels';
import type { DataTableAttributes, DataTableColumn, DataTableRow, DeviceKey, EditProps } from '../../types';

/** One of the three valid text alignments (falls back to left). */
const safeAlign = ( align?: string ): 'left' | 'center' | 'right' =>
	align === 'center' || align === 'right' ? align : 'left';

/** Wrapper preview: spacing, background, border, shadow. */
const buildWrapperStyle = ( attributes: DataTableAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = {};
	const sp = rawDevice( attributes.spacing, device );
	const padding = spacingShorthand( sp.padding );
	if ( padding ) s.padding = padding;
	const margin = spacingShorthand( sp.margin );
	if ( margin ) s.margin = margin;

	applyBackgroundPreview( s, attributes.background );
	applyBorderPreview( s, rawDevice( attributes.border, device ) );
	const shadow = boxShadowPreview( attributes.boxShadow );
	if ( shadow ) s.boxShadow = shadow;
	if ( attributes.maxWidth?.value ) s.maxWidth = withUnit( attributes.maxWidth.value, attributes.maxWidth.unit || 'px' );
	return s;
};

/** The cell-border shorthand shared by header + body cells (light preview). */
const cellBorder = ( attributes: DataTableAttributes ): CssProps => {
	const s: CssProps = {};
	if ( attributes.showCellBorders === false ) return s;
	const color = attributes.cellBorderColor?.light;
	const w = attributes.cellBorderWidth?.value;
	if ( ! color && ! w ) return s;
	s.borderStyle = 'solid';
	s.borderWidth = w ? withUnit( w, attributes.cellBorderWidth?.unit || 'px' ) : '1px';
	if ( color ) s.borderColor = color;
	return s;
};

/** Header-cell preview: typography, colours, padding, borders. */
const buildHeaderStyle = ( attributes: DataTableAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = { ...cellBorder( attributes ) };
	applyTypography( s, rawDevice( attributes.headerTypography, device ) );
	if ( attributes.headerColor?.light ) s.color = attributes.headerColor.light;
	if ( attributes.headerBackground?.light ) s.backgroundColor = attributes.headerBackground.light;
	const padding = spacingShorthand( rawDevice( attributes.cellPadding, device ) );
	if ( padding ) s.padding = padding;
	return s;
};

/** Body-cell preview: typography, colour, padding, borders. */
const buildCellStyle = ( attributes: DataTableAttributes, device: DeviceKey ): CssProps => {
	const s: CssProps = { ...cellBorder( attributes ) };
	applyTypography( s, rawDevice( attributes.cellTypography, device ) );
	if ( attributes.cellColor?.light ) s.color = attributes.cellColor.light;
	const padding = spacingShorthand( rawDevice( attributes.cellPadding, device ) );
	if ( padding ) s.padding = padding;
	return s;
};

/**
 * Data Table edit component.
 */
export default function Edit( { attributes, setAttributes, clientId }: EditProps< DataTableAttributes > ): JSX.Element {
	const { columns, rows, className, responsiveVisibility, htmlTag, showHeader, striped } = attributes;
	const blockId = attributes.blockId;
	const [ device ] = useDevice();

	useBlockId( clientId, blockId, setAttributes );

	const cols: DataTableColumn[] = Array.isArray( columns ) ? columns : [];
	const bodyRows: DataTableRow[] = Array.isArray( rows ) ? rows : [];

	const headerStyle = buildHeaderStyle( attributes, device );
	const cellStyle = buildCellStyle( attributes, device );
	const stripedBg = striped !== false ? attributes.stripedColor?.light : '';
	const firstColBg = attributes.firstColumnHighlight ? attributes.firstColumnBackground?.light : '';
	const firstColColor = attributes.firstColumnHighlight ? attributes.firstColumnColor?.light : '';

	// Row-hover highlight (background + cell text colour) can't be inline styles —
	// mirror the generator's `:hover` rules in a scoped <style> so the editor
	// matches the front end (see guide §6.7).
	const hoverOn = attributes.hoverHighlight !== false;
	const hoverBg = hoverOn ? attributes.hoverColor?.light : '';
	const hoverText = hoverOn ? attributes.cellColorHover?.light : '';
	const hoverCss = blockId
		? editorCss( [
			{ selector: `.flexa-data-table-${ blockId } .flexa-data-table__row:hover`, prop: 'background-color', value: hoverBg },
			{ selector: `.flexa-data-table-${ blockId } .flexa-data-table__row:hover .flexa-data-table__cell`, prop: 'color', value: hoverText },
			{ selector: `.flexa-data-table-${ blockId } .flexa-data-table__row:hover .flexa-data-table__th`, prop: 'color', value: attributes.headerColorHover?.light },
		] )
		: '';

	// --- Column / row mutations. ---
	const commit = ( nextCols: DataTableColumn[], nextRows: DataTableRow[] ) => setAttributes( { columns: nextCols, rows: nextRows } );

	const setColLabel = ( ci: number, label: string ) => {
		const next = cols.map( ( c, i ) => ( i === ci ? { ...c, label } : c ) );
		setAttributes( { columns: next } );
	};
	const setColAlign = ( ci: number, align: string ) => {
		const next = cols.map( ( c, i ) => ( i === ci ? { ...c, align: safeAlign( align ) } : c ) );
		setAttributes( { columns: next } );
	};
	const addColumn = () => {
		const nextCols = [ ...cols, { label: '', align: 'left' as const } ];
		const nextRows = bodyRows.map( ( r ) => ( { values: [ ...( Array.isArray( r.values ) ? r.values : [] ), '' ] } ) );
		commit( nextCols, nextRows );
	};
	const removeColumn = ( ci: number ) => {
		const nextCols = cols.filter( ( _c, i ) => i !== ci );
		const nextRows = bodyRows.map( ( r ) => ( { values: ( Array.isArray( r.values ) ? r.values : [] ).filter( ( _v, i ) => i !== ci ) } ) );
		commit( nextCols, nextRows );
	};
	const setCell = ( ri: number, ci: number, v: string ) => {
		const next = bodyRows.map( ( r, i ) => {
			if ( i !== ri ) return r;
			const values = cols.map( ( _c, ci2 ) => ( ci2 === ci ? v : ( r.values || [] )[ ci2 ] ?? '' ) );
			return { values };
		} );
		setAttributes( { rows: next } );
	};
	const addRow = () => setAttributes( { rows: [ ...bodyRows, { values: cols.map( () => '' ) } ] } );
	const removeRow = ( ri: number ) => setAttributes( { rows: bodyRows.filter( ( _r, i ) => i !== ri ) } );

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const Tag: any = htmlTag || 'div';
	const blockProps = useBlockProps( {
		className: cn(
			'flexa-data-table',
			blockId && `flexa-data-table-${ blockId }`,
			className,
			...visibilityClasses( responsiveVisibility )
		),
		style: buildWrapperStyle( attributes, device ),
	} );

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
				<div className="flexa-inspector flexa-data-table-inspector">
					<InspectorTabs
						layout={
							<>
								<DataTableOptionsPanel attributes={ attributes } setAttributes={ setAttributes } />
								<SpacingPanel attributes={ attributes } setAttributes={ setAttributes } />
							</>
						}
						style={
							<>
								<DataTableHeaderPanel attributes={ attributes } setAttributes={ setAttributes } />
								<DataTableCellPanel attributes={ attributes } setAttributes={ setAttributes } />
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
				<div className="flexa-data-table__scroll">
					<table className="flexa-data-table__table">
						{ showHeader !== false && (
							<thead className="flexa-data-table__thead">
								<tr className="flexa-data-table__row">
									{ cols.map( ( col, ci ) => (
										<th
											key={ ci }
											className="flexa-data-table__th"
											style={ { ...headerStyle, textAlign: safeAlign( col.align ), ...( ci === 0 && firstColBg ? { backgroundColor: firstColBg } : {} ), ...( ci === 0 && firstColColor ? { color: firstColColor } : {} ) } }
										>
											<div className="flexa-data-table__cell-edit">
												<input
													type="text"
													className="flexa-data-table__input flexa-data-table__col-label"
													value={ col.label ?? '' }
													placeholder={ __( 'Column', 'flexa-block' ) + ' ' + ( ci + 1 ) }
													onChange={ ( e ) => setColLabel( ci, e.target.value ) }
													aria-label={ __( 'Column label', 'flexa-block' ) }
												/>
												<select
													className="flexa-data-table__align"
													value={ safeAlign( col.align ) }
													onChange={ ( e ) => setColAlign( ci, e.target.value ) }
													aria-label={ __( 'Column alignment', 'flexa-block' ) }
												>
													<option value="left">{ __( 'Left', 'flexa-block' ) }</option>
													<option value="center">{ __( 'Center', 'flexa-block' ) }</option>
													<option value="right">{ __( 'Right', 'flexa-block' ) }</option>
												</select>
												{ cols.length > 1 && (
													<button
														type="button"
														className="flexa-data-table__remove"
														onClick={ () => removeColumn( ci ) }
														aria-label={ __( 'Remove column', 'flexa-block' ) }
													>
														×
													</button>
												) }
											</div>
										</th>
									) ) }
								</tr>
							</thead>
						) }
						<tbody className="flexa-data-table__tbody">
							{ bodyRows.map( ( row, ri ) => (
								<tr key={ ri } className="flexa-data-table__row">
									{ cols.map( ( col, ci ) => (
										<td
											key={ ci }
											className="flexa-data-table__cell"
											style={ {
												...cellStyle,
												textAlign: safeAlign( col.align ),
												...( stripedBg && ri % 2 === 1 ? { backgroundColor: stripedBg } : {} ),
												...( ci === 0 && firstColBg ? { backgroundColor: firstColBg } : {} ),
												...( ci === 0 && firstColColor ? { color: firstColColor } : {} ),
											} }
										>
											<div className="flexa-data-table__cell-edit">
												<input
													type="text"
													className="flexa-data-table__input"
													value={ ( row.values || [] )[ ci ] ?? '' }
													onChange={ ( e ) => setCell( ri, ci, e.target.value ) }
													aria-label={ __( 'Cell value', 'flexa-block' ) }
												/>
												{ ci === cols.length - 1 && (
													<button
														type="button"
														className="flexa-data-table__remove"
														onClick={ () => removeRow( ri ) }
														aria-label={ __( 'Remove row', 'flexa-block' ) }
													>
														×
													</button>
												) }
											</div>
										</td>
									) ) }
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
				<div className="flexa-data-table__controls">
					<Button variant="secondary" onClick={ addRow }>
						{ __( 'Add row', 'flexa-block' ) }
					</Button>
					<Button variant="secondary" onClick={ addColumn }>
						{ __( 'Add column', 'flexa-block' ) }
					</Button>
				</div>
			</Tag>
		</>
	);
}
