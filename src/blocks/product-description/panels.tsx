/**
 * Product Description block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility / animation) come from @components; these cover the parts unique to
 * a product description: which description to read, the optional heading, the
 * clamp + Read more toggle, and the styling of the body plus the elements the
 * shop owner nested *inside* the stored description (headings, links, lists,
 * tables). Following the "prefer theme styles" rule nothing is styled by
 * default — only the values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl } from '@wordpress/components';

import { Segmented, SliderUnit, DualColor, Dimensions, TypographyControls, CONTENT_ALIGN_OPTIONS, useDevice } from '@components';
import { rawDevice, patchDevice, LENGTH_UNITS, SPACING_UNITS } from '@utils';
import type { BoxValue, ControlOption, LengthValue, PanelProps, ProductDescriptionAttributes, TypographyDevice } from '../../types';

type PdPanelProps = PanelProps< ProductDescriptionAttributes >;

/** HTML tag choices for the optional heading above the description. */
const TITLE_TAG_OPTIONS: ControlOption[] = [
	{ value: 'h2', label: 'H2' },
	{ value: 'h3', label: 'H3' },
	{ value: 'h4', label: 'H4' },
	{ value: 'h5', label: 'H5' },
	{ value: 'h6', label: 'H6' },
	{ value: 'p', label: 'P' },
];

/** List marker styles; the empty value leaves the theme's marker alone. */
const LIST_STYLE_OPTIONS: ControlOption[] = [
	{ value: '', label: __( 'Theme default', 'flexa-block' ) },
	{ value: 'disc', label: __( 'Disc', 'flexa-block' ) },
	{ value: 'circle', label: __( 'Circle', 'flexa-block' ) },
	{ value: 'square', label: __( 'Square', 'flexa-block' ) },
	{ value: 'decimal', label: __( 'Decimal', 'flexa-block' ) },
	{ value: 'none', label: __( 'None', 'flexa-block' ) },
];

/**
 * Settings panel — alignment, the optional heading and the line clamp with its
 * two toggle labels. There is no source picker: this block prints the long
 * description, and the short one belongs to Product Excerpt.
 */
export const ProductDescriptionSettingsPanel = ( { attributes, setAttributes }: PdPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { alignment, showTitle, titleText, titleTag, enableClamp, clampLines, readMoreText, readLessText } = attributes;

	return (
		<PanelBody title={ __( 'Settings', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Alignment', 'flexa-block' ) }
				responsive
				value={ alignment?.[ device ] || '' }
				onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show heading', 'flexa-block' ) }
				checked={ !! showTitle }
				onChange={ ( v: boolean ) => setAttributes( { showTitle: v } ) }
			/>
			{ showTitle && (
				<>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Heading text', 'flexa-block' ) }
						value={ titleText || '' }
						onChange={ ( v: string ) => setAttributes( { titleText: v } ) }
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Heading tag', 'flexa-block' ) }
						value={ titleTag || 'h3' }
						options={ TITLE_TAG_OPTIONS }
						onChange={ ( v: string ) => setAttributes( { titleTag: v } ) }
					/>
				</>
			) }

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Clamp to a number of lines', 'flexa-block' ) }
				help={ __( 'Cut the description off after a set number of lines, behind a Read more toggle.', 'flexa-block' ) }
				checked={ !! enableClamp }
				onChange={ ( v: boolean ) => setAttributes( { enableClamp: v } ) }
			/>
			{ enableClamp && (
				<>
					<RangeControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Lines', 'flexa-block' ) }
						value={ typeof clampLines === 'number' ? clampLines : 4 }
						min={ 1 }
						max={ 20 }
						onChange={ ( v?: number ) => setAttributes( { clampLines: v ?? 4 } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Read more label', 'flexa-block' ) }
						value={ readMoreText ?? 'Read more' }
						onChange={ ( v: string ) => setAttributes( { readMoreText: v } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Read less label', 'flexa-block' ) }
						value={ readLessText ?? 'Read less' }
						onChange={ ( v: string ) => setAttributes( { readLessText: v } ) }
					/>
				</>
			) }
		</PanelBody>
	);
};

/**
 * Content panel — the description body's colour and typography.
 */
export const ProductDescriptionContentPanel = ( { attributes, setAttributes }: PdPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.typography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { typography: patchDevice( attributes.typography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Content', 'flexa-block' ) } initialOpen={ true }>
			<DualColor label={ __( 'Text color', 'flexa-block' ) } value={ attributes.textColor || {} } onChange={ ( v ) => setAttributes( { textColor: v } ) } />
			<TypographyControls value={ typo } onChange={ setTypo } />
		</PanelBody>
	);
};

/**
 * Heading panel — the optional heading's colour and typography. Only meaningful
 * when the heading is shown, so it renders only then.
 */
export const ProductDescriptionTitlePanel = ( { attributes, setAttributes }: PdPanelProps ): JSX.Element | null => {
	const [ device ] = useDevice();

	if ( ! attributes.showTitle ) {
		return null;
	}

	const typo = rawDevice( attributes.titleTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { titleTypography: patchDevice( attributes.titleTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Heading', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Heading color', 'flexa-block' ) } value={ attributes.titleColor || {} } onChange={ ( v ) => setAttributes( { titleColor: v } ) } />
			<TypographyControls value={ typo } onChange={ setTypo } />
		</PanelBody>
	);
};

/**
 * Links panel — link colours for anchors inside the description.
 */
export const ProductDescriptionLinksPanel = ( { attributes, setAttributes }: PdPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Links', 'flexa-block' ) } initialOpen={ false }>
		<DualColor label={ __( 'Link color', 'flexa-block' ) } value={ attributes.linkColor || {} } onChange={ ( v ) => setAttributes( { linkColor: v } ) } />
		<DualColor label={ __( 'Link color (hover)', 'flexa-block' ) } value={ attributes.linkColorHover || {} } onChange={ ( v ) => setAttributes( { linkColorHover: v } ) } />
	</PanelBody>
);

/**
 * Nested headings panel — the h2–h4 the shop owner wrote inside the description.
 */
export const ProductDescriptionNestedHeadingsPanel = ( { attributes, setAttributes }: PdPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.innerHeadingTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) =>
		setAttributes( { innerHeadingTypography: patchDevice( attributes.innerHeadingTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Nested headings', 'flexa-block' ) } initialOpen={ false }>
			<DualColor
				label={ __( 'Heading color', 'flexa-block' ) }
				value={ attributes.innerHeadingColor || {} }
				onChange={ ( v ) => setAttributes( { innerHeadingColor: v } ) }
			/>
			<TypographyControls value={ typo } onChange={ setTypo } />
		</PanelBody>
	);
};

/**
 * Lists panel — marker style and indent for lists inside the description.
 */
export const ProductDescriptionListsPanel = ( { attributes, setAttributes }: PdPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { listStyle, listIndent } = attributes;

	return (
		<PanelBody title={ __( 'Lists', 'flexa-block' ) } initialOpen={ false }>
			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Marker style', 'flexa-block' ) }
				value={ listStyle || '' }
				options={ LIST_STYLE_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { listStyle: v } ) }
			/>
			<SliderUnit
				label={ __( 'List indent', 'flexa-block' ) }
				value={ listIndent?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { listIndent: { ...listIndent, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Tables panel — cell padding and the cell border colour.
 */
export const ProductDescriptionTablesPanel = ( { attributes, setAttributes }: PdPanelProps ): JSX.Element => {
	const [ device ] = useDevice();

	return (
		<PanelBody title={ __( 'Tables', 'flexa-block' ) } initialOpen={ false }>
			<DualColor
				label={ __( 'Cell border color', 'flexa-block' ) }
				value={ attributes.tableBorderColor || {} }
				onChange={ ( v ) => setAttributes( { tableBorderColor: v } ) }
			/>
			<Dimensions
				label={ __( 'Cell padding', 'flexa-block' ) }
				responsive
				value={ rawDevice( attributes.tableCellPadding, device ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { tableCellPadding: { ...attributes.tableCellPadding, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Read more panel — the toggle's colour. Only meaningful while the clamp is on,
 * so it renders only then.
 */
export const ProductDescriptionReadMorePanel = ( { attributes, setAttributes }: PdPanelProps ): JSX.Element | null => {
	if ( ! attributes.enableClamp ) {
		return null;
	}

	return (
		<PanelBody title={ __( 'Read more', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Toggle color', 'flexa-block' ) } value={ attributes.readMoreColor || {} } onChange={ ( v ) => setAttributes( { readMoreColor: v } ) } />
		</PanelBody>
	);
};
