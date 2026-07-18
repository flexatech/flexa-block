/**
 * Data Table block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) plus the shared typography controls come from @components; these
 * cover the parts unique to the data table: the table behaviour toggles
 * (header, striping, hover, cell borders, first-column highlight) and the
 * responsive mode, the header styling, and the cell styling (typography,
 * colour, padding, striped/hover/border/first-column colours). The columns and
 * rows themselves are edited live in the canvas (see edit.tsx). Following the
 * "prefer theme styles" rule nothing is styled by default — only the values the
 * user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

import {
	SliderUnit,
	Dimensions,
	DualColor,
	TypographyControls,
	useDevice,
} from '@components';
import {
	rawDevice,
	patchDevice,
	HTML_TAGS,
	LENGTH_UNITS,
	SPACING_UNITS,
	WEIGHT_UNITS,
} from '@utils';
import type {
	BoxValue,
	DataTableAttributes,
	LengthValue,
	PanelProps,
	TypographyDevice,
} from '../../types';

type DtPanelProps = PanelProps< DataTableAttributes >;

/**
 * Table panel — the behaviour toggles (header, striping, hover, cell borders,
 * first-column highlight), the table max width (wider content scrolls
 * horizontally) and the wrapper HTML tag.
 */
export const DataTableOptionsPanel = ( { attributes, setAttributes }: DtPanelProps ): JSX.Element => {
	const { showHeader, striped, hoverHighlight, showCellBorders, firstColumnHighlight, maxWidth, htmlTag } = attributes;

	return (
		<PanelBody title={ __( 'Table', 'flexa-block' ) } initialOpen={ true }>
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Show header', 'flexa-block' ) } checked={ showHeader !== false } onChange={ ( v: boolean ) => setAttributes( { showHeader: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Striped rows', 'flexa-block' ) } checked={ striped !== false } onChange={ ( v: boolean ) => setAttributes( { striped: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Highlight row on hover', 'flexa-block' ) } checked={ hoverHighlight !== false } onChange={ ( v: boolean ) => setAttributes( { hoverHighlight: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Cell borders', 'flexa-block' ) } checked={ showCellBorders !== false } onChange={ ( v: boolean ) => setAttributes( { showCellBorders: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Highlight first column', 'flexa-block' ) } checked={ !! firstColumnHighlight } onChange={ ( v: boolean ) => setAttributes( { firstColumnHighlight: v } ) } />
			<SliderUnit
				label={ __( 'Max width', 'flexa-block' ) }
				value={ maxWidth || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 3000, '%': 100, vw: 100, rem: 200 } }
				onChange={ ( v: LengthValue ) => setAttributes( { maxWidth: v } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'div' }
				options={ HTML_TAGS.map( ( t ) => ( { label: t, value: t } ) ) }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Header panel — the column-header typography plus text and background colours.
 */
export const DataTableHeaderPanel = ( { attributes, setAttributes }: DtPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.headerTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { headerTypography: patchDevice( attributes.headerTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Header', 'flexa-block' ) } initialOpen={ true }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.headerColor || {} } onChange={ ( v ) => setAttributes( { headerColor: v } ) } />
			<DualColor label={ __( 'Text colour (hover)', 'flexa-block' ) } value={ attributes.headerColorHover || {} } onChange={ ( v ) => setAttributes( { headerColorHover: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ attributes.headerBackground || {} } onChange={ ( v ) => setAttributes( { headerBackground: v } ) } />
		</PanelBody>
	);
};

/**
 * Cells panel — the body-cell typography, text colour and padding, plus the
 * striped-row / hover / cell-border / first-column colours (each gated on its
 * matching toggle so the panel only shows options that apply).
 */
export const DataTableCellPanel = ( { attributes, setAttributes }: DtPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.cellTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { cellTypography: patchDevice( attributes.cellTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Cells', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.cellColor || {} } onChange={ ( v ) => setAttributes( { cellColor: v } ) } />
			<Dimensions
				label={ __( 'Cell padding', 'flexa-block' ) }
				responsive
				value={ rawDevice( attributes.cellPadding, device ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { cellPadding: { ...attributes.cellPadding, [ device ]: v } } ) }
			/>
			{ attributes.striped !== false && (
				<DualColor label={ __( 'Striped-row colour', 'flexa-block' ) } value={ attributes.stripedColor || {} } onChange={ ( v ) => setAttributes( { stripedColor: v } ) } />
			) }
			{ attributes.hoverHighlight !== false && (
				<>
					<DualColor label={ __( 'Row hover colour', 'flexa-block' ) } value={ attributes.hoverColor || {} } onChange={ ( v ) => setAttributes( { hoverColor: v } ) } />
					<DualColor label={ __( 'Row hover text colour', 'flexa-block' ) } value={ attributes.cellColorHover || {} } onChange={ ( v ) => setAttributes( { cellColorHover: v } ) } />
				</>
			) }
			{ attributes.showCellBorders !== false && (
				<>
					<DualColor label={ __( 'Cell border colour', 'flexa-block' ) } value={ attributes.cellBorderColor || {} } onChange={ ( v ) => setAttributes( { cellBorderColor: v } ) } />
					<SliderUnit
						label={ __( 'Cell border width', 'flexa-block' ) }
						value={ attributes.cellBorderWidth || {} }
						units={ WEIGHT_UNITS }
						defaultUnit="px"
						max={ { px: 12 } }
						onChange={ ( v: LengthValue ) => setAttributes( { cellBorderWidth: v } ) }
					/>
				</>
			) }
			{ !! attributes.firstColumnHighlight && (
				<>
					<DualColor label={ __( 'First-column background', 'flexa-block' ) } value={ attributes.firstColumnBackground || {} } onChange={ ( v ) => setAttributes( { firstColumnBackground: v } ) } />
					<DualColor label={ __( 'First-column text colour', 'flexa-block' ) } value={ attributes.firstColumnColor || {} } onChange={ ( v ) => setAttributes( { firstColumnColor: v } ) } />
				</>
			) }
		</PanelBody>
	);
};
