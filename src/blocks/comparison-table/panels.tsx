/**
 * Comparison Table block — block-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) plus the shared typography controls come from @components; these
 * cover the parts unique to the comparison table: the width mode, the plan
 * columns and feature rows (both edited through the shared ItemListPanel), the
 * header / cell styling, the highlighted-column accent, the check / cross marks
 * and the dividers + zebra striping. Following the "prefer theme styles" rule
 * nothing is styled by default — only the values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, TextControl, BaseControl, Button, Flex } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import {
	Segmented,
	SliderUnit,
	Dimensions,
	DualColor,
	ColorGradientControl,
	TypographyControls,
	ItemListPanel,
	CONTENT_ALIGN_OPTIONS,
	CONTAINER_WIDTH_OPTIONS,
	useDevice,
} from '@components';
import {
	rawDevice,
	patchDevice,
	HTML_TAGS,
	WIDTH_UNITS,
	LENGTH_UNITS,
	SPACING_UNITS,
	WEIGHT_UNITS,
} from '@utils';
import type {
	BoxValue,
	ComparisonColumn,
	ComparisonRow,
	ComparisonTableAttributes,
	LengthValue,
	PanelProps,
	TypographyDevice,
} from '../../types';

type CtPanelProps = PanelProps< ComparisonTableAttributes >;

/** Check-mark glyph choices (few values → SelectControl). */
const CHECK_ICON_OPTIONS = [
	{ value: 'check', label: __( 'Checkmark', 'flexa-block' ) },
	{ value: 'star', label: __( 'Star', 'flexa-block' ) },
	{ value: 'dot', label: __( 'Dot', 'flexa-block' ) },
];

/** Cross-mark glyph choices (few values → SelectControl). */
const CROSS_ICON_OPTIONS = [
	{ value: 'cross', label: __( 'Cross', 'flexa-block' ) },
	{ value: 'dash', label: __( 'Dash', 'flexa-block' ) },
	{ value: 'minus', label: __( 'Minus', 'flexa-block' ) },
];

/** A fresh column, used when adding one. */
const blankColumn = (): ComparisonColumn => ( {
	title: '',
	subtitle: '',
	badge: '',
	highlighted: false,
	ctaText: '',
	ctaUrl: '',
	imageUrl: '',
	imageId: null,
} );

/** A fresh feature row, used when adding one. */
const blankRow = (): ComparisonRow => ( { label: '', values: [] } );

/**
 * Width panel — boxed vs full-width table, its width and the wrapper tag.
 */
export const ComparisonWidthPanel = ( { attributes, setAttributes }: CtPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { containerType, widthBoxed, widthFullWidth, htmlTag } = attributes;
	const isBoxed = containerType === 'boxed';
	const widthGroup: 'widthBoxed' | 'widthFullWidth' = isBoxed ? 'widthBoxed' : 'widthFullWidth';
	const widthVal = ( isBoxed ? widthBoxed : widthFullWidth )?.[ device ] || {};

	const setWidth = ( val: LengthValue ) => {
		const next: Partial< ComparisonTableAttributes > = {};
		next[ widthGroup ] = { ...attributes[ widthGroup ], [ device ]: { value: val.value ?? '', unit: val.unit || ( isBoxed ? 'px' : '%' ) } };
		setAttributes( next );
	};

	return (
		<PanelBody title={ __( 'Table', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Content Width', 'flexa-block' ) }
				value={ containerType || 'full-width' }
				onChange={ ( v ) => setAttributes( { containerType: v as ComparisonTableAttributes[ 'containerType' ] } ) }
				options={ CONTAINER_WIDTH_OPTIONS }
			/>
			<SliderUnit
				label={ isBoxed ? __( 'Max Width', 'flexa-block' ) : __( 'Width', 'flexa-block' ) }
				value={ widthVal }
				units={ WIDTH_UNITS }
				defaultUnit={ isBoxed ? 'px' : '%' }
				max={ { px: 3000, '%': 100, vw: 100, rem: 100 } }
				onChange={ setWidth }
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

/** Body controls for one column: logo, title, subtitle, spotlight, badge, CTA. */
const ColumnBody = ( { item, update }: { item: ComparisonColumn; update: ( patch: Partial< ComparisonColumn > ) => void } ): JSX.Element => (
	<>
		<BaseControl __nextHasNoMarginBottom label={ __( 'Logo', 'flexa-block' ) }>
			<MediaUploadCheck>
				<MediaUpload
					allowedTypes={ [ 'image' ] }
					value={ item.imageId ?? undefined }
					onSelect={ ( media: { id: number; url: string } ) => update( { imageId: media.id, imageUrl: media.url } ) }
					render={ ( { open }: { open: () => void } ) => (
						<>
							{ item.imageUrl && (
								<button type="button" className="flexa-bg-preview" onClick={ open } aria-label={ __( 'Replace logo', 'flexa-block' ) }>
									<img src={ item.imageUrl } alt="" />
								</button>
							) }
							<Flex gap={ 2 } justify="flex-start">
								<Button variant="secondary" onClick={ open }>
									{ item.imageUrl ? __( 'Replace', 'flexa-block' ) : __( 'Select logo', 'flexa-block' ) }
								</Button>
								{ item.imageUrl && (
									<Button isDestructive variant="tertiary" onClick={ () => update( { imageId: null, imageUrl: '' } ) }>
										{ __( 'Remove', 'flexa-block' ) }
									</Button>
								) }
							</Flex>
						</>
					) }
				/>
			</MediaUploadCheck>
		</BaseControl>
		<TextControl __nextHasNoMarginBottom label={ __( 'Title', 'flexa-block' ) } value={ item.title ?? '' } onChange={ ( v: string ) => update( { title: v } ) } />
		<TextControl __nextHasNoMarginBottom label={ __( 'Subtitle', 'flexa-block' ) } value={ item.subtitle ?? '' } onChange={ ( v: string ) => update( { subtitle: v } ) } />
		<TextControl __nextHasNoMarginBottom label={ __( 'Badge', 'flexa-block' ) } help={ __( 'Small ribbon shown above the title, e.g. "Popular".', 'flexa-block' ) } value={ item.badge ?? '' } onChange={ ( v: string ) => update( { badge: v } ) } />
		<ToggleControl __nextHasNoMarginBottom label={ __( 'Spotlight this column', 'flexa-block' ) } checked={ !! item.highlighted } onChange={ ( v: boolean ) => update( { highlighted: v } ) } />
		<TextControl __nextHasNoMarginBottom label={ __( 'Button text', 'flexa-block' ) } value={ item.ctaText ?? '' } onChange={ ( v: string ) => update( { ctaText: v } ) } />
		<TextControl __nextHasNoMarginBottom label={ __( 'Button link', 'flexa-block' ) } value={ item.ctaUrl ?? '' } onChange={ ( v: string ) => update( { ctaUrl: v } ) } />
	</>
);

/**
 * Columns panel — the plans, via the shared ItemListPanel (accordion cards +
 * reorder + add/remove).
 */
export const ComparisonColumnsPanel = ( { attributes, setAttributes }: CtPanelProps ): JSX.Element => {
	const columns: ComparisonColumn[] = Array.isArray( attributes.columns ) ? attributes.columns : [];

	return (
		<ItemListPanel< ComparisonColumn >
			title={ __( 'Columns', 'flexa-block' ) }
			addLabel={ __( 'Add column', 'flexa-block' ) }
			items={ columns }
			onChange={ ( next ) => setAttributes( { columns: next } ) }
			newItem={ blankColumn }
			minItems={ 1 }
			renderHeader={ ( item, index ) => ( {
				preview: item.imageUrl ? <img src={ item.imageUrl } alt="" /> : undefined,
				title: ( item.title || '' ).trim() || __( 'Column', 'flexa-block' ) + ' ' + ( index + 1 ),
			} ) }
			renderBody={ ( item, _index, update ) => <ColumnBody item={ item } update={ update } /> }
		/>
	);
};

/** Body controls for one feature row: its label plus one cell per column. */
const RowBody = ( { item, columns, update }: { item: ComparisonRow; columns: ComparisonColumn[]; update: ( patch: Partial< ComparisonRow > ) => void } ): JSX.Element => {
	const values: string[] = Array.isArray( item.values ) ? item.values : [];
	const setValue = ( col: number, v: string ) => {
		const next = columns.map( ( _c, i ) => ( i === col ? v : values[ i ] ?? '' ) );
		update( { values: next } );
	};

	return (
		<>
			<TextControl __nextHasNoMarginBottom label={ __( 'Feature', 'flexa-block' ) } value={ item.label ?? '' } onChange={ ( v: string ) => update( { label: v } ) } />
			{ columns.map( ( col, i ) => (
				<TextControl
					key={ i }
					__nextHasNoMarginBottom
					label={ ( col.title || __( 'Column', 'flexa-block' ) + ' ' + ( i + 1 ) ) }
					help={ 0 === i ? __( 'Type "true" for a check or "false" for a cross; anything else shows as text.', 'flexa-block' ) : undefined }
					value={ values[ i ] ?? '' }
					onChange={ ( v: string ) => setValue( i, v ) }
				/>
			) ) }
		</>
	);
};

/**
 * Rows panel — the feature rows, via the shared ItemListPanel. Each row edits
 * its feature label and one cell per existing column.
 */
export const ComparisonRowsPanel = ( { attributes, setAttributes }: CtPanelProps ): JSX.Element => {
	const rows: ComparisonRow[] = Array.isArray( attributes.rows ) ? attributes.rows : [];
	const columns: ComparisonColumn[] = Array.isArray( attributes.columns ) ? attributes.columns : [];

	return (
		<ItemListPanel< ComparisonRow >
			title={ __( 'Rows', 'flexa-block' ) }
			addLabel={ __( 'Add row', 'flexa-block' ) }
			items={ rows }
			onChange={ ( next ) => setAttributes( { rows: next } ) }
			newItem={ blankRow }
			initialOpen={ false }
			renderHeader={ ( item, index ) => ( {
				title: ( item.label || '' ).trim() || __( 'Row', 'flexa-block' ) + ' ' + ( index + 1 ),
			} ) }
			renderBody={ ( item, _index, update ) => <RowBody item={ item } columns={ columns } update={ update } /> }
		/>
	);
};

/**
 * Options panel — sticky header, zebra striping and cell alignment.
 */
export const ComparisonOptionsPanel = ( { attributes, setAttributes }: CtPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { stickyHeader, zebra, cellAlign } = attributes;

	return (
		<PanelBody title={ __( 'Options', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Sticky header', 'flexa-block' ) } help={ __( 'Keep the column headers visible while scrolling the page.', 'flexa-block' ) } checked={ !! stickyHeader } onChange={ ( v: boolean ) => setAttributes( { stickyHeader: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Striped rows', 'flexa-block' ) } checked={ zebra !== false } onChange={ ( v: boolean ) => setAttributes( { zebra: v } ) } />
			<Segmented
				label={ __( 'Cell alignment', 'flexa-block' ) }
				responsive
				value={ cellAlign?.[ device ] || '' || 'left' }
				onChange={ ( v ) => setAttributes( { cellAlign: { ...cellAlign, [ device ]: v } } ) }
				options={ CONTENT_ALIGN_OPTIONS }
			/>
		</PanelBody>
	);
};

/**
 * Header panel — the column-header typography, text and background colours plus
 * the accent used to spotlight a column.
 */
export const ComparisonHeaderPanel = ( { attributes, setAttributes }: CtPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.headerTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { headerTypography: patchDevice( attributes.headerTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Header', 'flexa-block' ) } initialOpen={ true }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.headerColor || {} } onChange={ ( v ) => setAttributes( { headerColor: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ attributes.headerBackground || {} } onChange={ ( v ) => setAttributes( { headerBackground: v } ) } />
			<DualColor label={ __( 'Spotlight accent', 'flexa-block' ) } value={ attributes.highlightColor || {} } onChange={ ( v ) => setAttributes( { highlightColor: v } ) } />
			<DualColor label={ __( 'Accent bar colour', 'flexa-block' ) } value={ attributes.spotlightBarColor || {} } onChange={ ( v ) => setAttributes( { spotlightBarColor: v } ) } />
			<SliderUnit
				label={ __( 'Accent bar thickness', 'flexa-block' ) }
				value={ attributes.spotlightBarWidth || {} }
				units={ WEIGHT_UNITS }
				defaultUnit="px"
				max={ { px: 20 } }
				onChange={ ( v: LengthValue ) => setAttributes( { spotlightBarWidth: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Badge panel — the "Popular"-style ribbon shown above a spotlighted column's
 * title. Its background falls back to the Spotlight accent when left empty, so an
 * untouched badge looks exactly as before; everything else is opt-in.
 */
export const ComparisonBadgePanel = ( { attributes, setAttributes }: CtPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.badgeTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { badgeTypography: patchDevice( attributes.badgeTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Badge', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.badgeColor || {} } onChange={ ( v ) => setAttributes( { badgeColor: v } ) } />
			{ /* Background left empty inherits the Spotlight accent (see CSS generator). */ }
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ attributes.badgeBackground || {} } onChange={ ( v ) => setAttributes( { badgeBackground: v } ) } />
			<SliderUnit
				label={ __( 'Corner radius', 'flexa-block' ) }
				value={ attributes.badgeRadius || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 10, rem: 10, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { badgeRadius: v } ) }
			/>
			<Dimensions
				label={ __( 'Padding', 'flexa-block' ) }
				value={ ( attributes.badgePadding || {} ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { badgePadding: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Cell panel — the body-cell typography, text colour and padding.
 */
export const ComparisonCellPanel = ( { attributes, setAttributes }: CtPanelProps ): JSX.Element => {
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
		</PanelBody>
	);
};

/**
 * Marks panel — the check / cross glyphs used for boolean cells and their colours.
 */
export const ComparisonMarksPanel = ( { attributes, setAttributes }: CtPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Check & Cross', 'flexa-block' ) } initialOpen={ false }>
		<SelectControl
			__nextHasNoMarginBottom
			label={ __( 'Check icon', 'flexa-block' ) }
			value={ attributes.checkIcon || 'check' }
			options={ CHECK_ICON_OPTIONS }
			onChange={ ( v: string ) => setAttributes( { checkIcon: v as ComparisonTableAttributes[ 'checkIcon' ] } ) }
		/>
		<DualColor label={ __( 'Check colour', 'flexa-block' ) } value={ attributes.checkColor || {} } onChange={ ( v ) => setAttributes( { checkColor: v } ) } />
		<SelectControl
			__nextHasNoMarginBottom
			label={ __( 'Cross icon', 'flexa-block' ) }
			value={ attributes.crossIcon || 'cross' }
			options={ CROSS_ICON_OPTIONS }
			onChange={ ( v: string ) => setAttributes( { crossIcon: v as ComparisonTableAttributes[ 'crossIcon' ] } ) }
		/>
		<DualColor label={ __( 'Cross colour', 'flexa-block' ) } value={ attributes.crossColor || {} } onChange={ ( v ) => setAttributes( { crossColor: v } ) } />
	</PanelBody>
);

/**
 * Dividers panel — row / column separator lines (colour + thickness) plus the
 * zebra-stripe colour.
 */
export const ComparisonDividersPanel = ( { attributes, setAttributes }: CtPanelProps ): JSX.Element => {
	const { showRowDivider, showColumnDivider, dividerColor, dividerWidth, zebraColor } = attributes;

	return (
		<PanelBody title={ __( 'Dividers', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Row dividers', 'flexa-block' ) } checked={ showRowDivider !== false } onChange={ ( v: boolean ) => setAttributes( { showRowDivider: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Column dividers', 'flexa-block' ) } checked={ !! showColumnDivider } onChange={ ( v: boolean ) => setAttributes( { showColumnDivider: v } ) } />
			<DualColor label={ __( 'Divider colour', 'flexa-block' ) } value={ dividerColor || {} } onChange={ ( v ) => setAttributes( { dividerColor: v } ) } />
			<SliderUnit
				label={ __( 'Divider thickness', 'flexa-block' ) }
				value={ dividerWidth || {} }
				units={ WEIGHT_UNITS }
				defaultUnit="px"
				max={ { px: 12 } }
				onChange={ ( v: LengthValue ) => setAttributes( { dividerWidth: v } ) }
			/>
			<DualColor label={ __( 'Striped-row colour', 'flexa-block' ) } value={ zebraColor || {} } onChange={ ( v ) => setAttributes( { zebraColor: v } ) } />
		</PanelBody>
	);
};

/** Button width mode (few values → Segmented). */
const BUTTON_WIDTH_OPTIONS = [
	{ value: 'auto', label: __( 'Auto', 'flexa-block' ) },
	{ value: 'full', label: __( 'Full width', 'flexa-block' ) },
];

/**
 * Button panel — one appearance for every column's call-to-action button. Empty
 * values inherit the theme's button styling (the CTA keeps `wp-element-button`);
 * only what the user sets produces CSS.
 */
export const ComparisonButtonPanel = ( { attributes, setAttributes }: CtPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Button', 'flexa-block' ) } initialOpen={ false }>
		<Segmented
			label={ __( 'Width', 'flexa-block' ) }
			value={ attributes.buttonWidth || 'auto' }
			onChange={ ( v ) => setAttributes( { buttonWidth: v as ComparisonTableAttributes[ 'buttonWidth' ] } ) }
			options={ BUTTON_WIDTH_OPTIONS }
		/>
		<DualColor label={ __( 'Text colour', 'flexa-block' ) } value={ attributes.buttonTextColor || {} } onChange={ ( v ) => setAttributes( { buttonTextColor: v } ) } />
		<DualColor label={ __( 'Text colour (hover)', 'flexa-block' ) } value={ attributes.buttonTextColorHover || {} } onChange={ ( v ) => setAttributes( { buttonTextColorHover: v } ) } />
		<ColorGradientControl
			label={ __( 'Background', 'flexa-block' ) }
			type={ attributes.buttonBackgroundType || 'color' }
			color={ attributes.buttonBackground || {} }
			gradient={ attributes.buttonBackgroundGradient || {} }
			onTypeChange={ ( v ) => setAttributes( { buttonBackgroundType: v } ) }
			onColorChange={ ( v ) => setAttributes( { buttonBackground: v } ) }
			onGradientChange={ ( v ) => setAttributes( { buttonBackgroundGradient: v } ) }
		/>
		<ColorGradientControl
			label={ __( 'Background (hover)', 'flexa-block' ) }
			type={ attributes.buttonBackgroundHoverType || 'color' }
			color={ attributes.buttonBackgroundHover || {} }
			gradient={ attributes.buttonBackgroundHoverGradient || {} }
			onTypeChange={ ( v ) => setAttributes( { buttonBackgroundHoverType: v } ) }
			onColorChange={ ( v ) => setAttributes( { buttonBackgroundHover: v } ) }
			onGradientChange={ ( v ) => setAttributes( { buttonBackgroundHoverGradient: v } ) }
		/>
		<SliderUnit
			label={ __( 'Corner radius', 'flexa-block' ) }
			value={ attributes.buttonRadius || {} }
			units={ LENGTH_UNITS }
			defaultUnit="px"
			max={ { px: 100, em: 10, rem: 10, '%': 100 } }
			onChange={ ( v: LengthValue ) => setAttributes( { buttonRadius: v } ) }
		/>
		<Dimensions
			label={ __( 'Padding', 'flexa-block' ) }
			value={ ( attributes.buttonPadding || {} ) as BoxValue }
			units={ SPACING_UNITS }
			onChange={ ( v: BoxValue ) => setAttributes( { buttonPadding: v } ) }
		/>
	</PanelBody>
);
