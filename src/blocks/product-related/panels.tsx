/**
 * Related Products block — block-specific inspector panels.
 *
 * The foundation panels (spacing / background / border / shadow / position /
 * visibility / animation) and the whole button panel come from @components; this
 * file covers the parts unique to this block: the query + grid settings, the
 * section heading, which card parts are shown, the card box, and the card text
 * colours. One PanelBody per subject (guide §6.4d), ordered most-used first.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	DualColor,
	Dimensions,
	TypographyControls,
	HoverEffectControl,
	useDevice,
	ASPECT_RATIO_OPTIONS,
	CONTENT_ALIGN_OPTIONS,
} from '@components';
import { rawDevice, patchDevice, LENGTH_UNITS, SPACING_UNITS } from '@utils';
import type {
	BoxShadowAttr,
	BoxValue,
	ControlOption,
	LengthValue,
	PanelProps,
	ProductRelatedAttributes,
	TypographyDevice,
} from '../../types';

type RelatedPanelProps = PanelProps< ProductRelatedAttributes >;

/** Grid of cards, or one card per row. */
const LAYOUT_OPTIONS: ControlOption[] = [
	{ value: 'grid', label: __( 'Grid', 'flexa-block' ) },
	{ value: 'list', label: __( 'List', 'flexa-block' ) },
];

/** How WooCommerce's related products are sorted before they are printed. */
const ORDER_BY_OPTIONS: ControlOption[] = [
	{ value: 'rand', label: __( 'Random', 'flexa-block' ) },
	{ value: 'date', label: __( 'Newest', 'flexa-block' ) },
	{ value: 'price', label: __( 'Price', 'flexa-block' ) },
	{ value: 'popularity', label: __( 'Popularity', 'flexa-block' ) },
	{ value: 'rating', label: __( 'Rating', 'flexa-block' ) },
];

/** Tags the section heading may be printed as (whitelisted in render.php too). */
const HEADING_TAG_OPTIONS: ControlOption[] = [
	{ value: 'h2', label: 'H2' },
	{ value: 'h3', label: 'H3' },
	{ value: 'h4', label: 'H4' },
	{ value: 'h5', label: 'H5' },
	{ value: 'h6', label: 'H6' },
	{ value: 'p', label: 'P' },
	{ value: 'div', label: 'Div' },
];

/** Card box-shadow offsets — same four fields the shared shadow panels expose. */
const SHADOW_FIELDS: Array< { k: 'horizontal' | 'vertical' | 'blur' | 'spread'; l: string } > = [
	{ k: 'horizontal', l: __( 'X offset', 'flexa-block' ) },
	{ k: 'vertical', l: __( 'Y offset', 'flexa-block' ) },
	{ k: 'blur', l: __( 'Blur', 'flexa-block' ) },
	{ k: 'spread', l: __( 'Spread', 'flexa-block' ) },
];

/**
 * Settings panel — the query, the layout and the grid geometry.
 */
export const ProductRelatedSettingsPanel = ( { attributes, setAttributes }: RelatedPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { relatedLayout, postsPerPage, orderBy, columns, rowGap, columnGap, contentAlign } = attributes;
	const isGrid = ( relatedLayout || 'grid' ) === 'grid';

	return (
		<PanelBody title={ __( 'Settings', 'flexa-block' ) } initialOpen={ true }>
			<Segmented
				label={ __( 'Layout', 'flexa-block' ) }
				value={ relatedLayout || 'grid' }
				options={ LAYOUT_OPTIONS }
				onChange={ ( v ) => setAttributes( { relatedLayout: v as ProductRelatedAttributes[ 'relatedLayout' ] } ) }
			/>
			<RangeControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Products', 'flexa-block' ) }
				value={ typeof postsPerPage === 'number' ? postsPerPage : 4 }
				min={ 1 }
				max={ 12 }
				onChange={ ( v?: number ) => setAttributes( { postsPerPage: v ?? 4 } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Order by', 'flexa-block' ) }
				value={ orderBy || 'rand' }
				options={ ORDER_BY_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { orderBy: v as ProductRelatedAttributes[ 'orderBy' ] } ) }
			/>
			{ isGrid && (
				<SelectControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Columns', 'flexa-block' ) }
					value={ columns?.[ device ] || '' }
					options={ [
						{ value: '', label: __( 'Default', 'flexa-block' ) },
						...[ 1, 2, 3, 4, 5, 6, 7, 8 ].map( ( n ) => ( { value: String( n ), label: String( n ) } ) ),
					] }
					onChange={ ( v: string ) => setAttributes( { columns: { ...columns, [ device ]: v } } ) }
				/>
			) }
			<SliderUnit
				label={ __( 'Row gap', 'flexa-block' ) }
				value={ rowGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { rowGap: { ...rowGap, [ device ]: v } } ) }
			/>
			{ isGrid && (
				<SliderUnit
					label={ __( 'Column gap', 'flexa-block' ) }
					value={ columnGap?.[ device ] || {} }
					units={ SPACING_UNITS }
					defaultUnit="px"
					max={ { px: 120, em: 12, rem: 12, '%': 100 } }
					onChange={ ( v: LengthValue ) => setAttributes( { columnGap: { ...columnGap, [ device ]: v } } ) }
				/>
			) }
			<Segmented
				label={ __( 'Card alignment', 'flexa-block' ) }
				value={ contentAlign || '' }
				options={ CONTENT_ALIGN_OPTIONS }
				onChange={ ( v ) => setAttributes( { contentAlign: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Heading panel — the optional section heading above the list, plus its own
 * typography and colour (all one subject, so one panel).
 */
export const ProductRelatedHeadingPanel = ( { attributes, setAttributes }: RelatedPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { showHeading, headingText, headingTag, headingColor } = attributes;
	const typo = rawDevice( attributes.headingTypography, device );

	return (
		<PanelBody title={ __( 'Heading', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show heading', 'flexa-block' ) }
				checked={ showHeading !== false }
				onChange={ ( v: boolean ) => setAttributes( { showHeading: v } ) }
			/>
			{ showHeading !== false && (
				<>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Heading text', 'flexa-block' ) }
						value={ headingText ?? '' }
						onChange={ ( v: string ) => setAttributes( { headingText: v } ) }
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'HTML Tag', 'flexa-block' ) }
						value={ headingTag || 'h2' }
						options={ HEADING_TAG_OPTIONS }
						onChange={ ( v: string ) => setAttributes( { headingTag: v } ) }
					/>
					<TypographyControls
						value={ typo }
						onChange={ ( patch: Partial< TypographyDevice > ) =>
							setAttributes( { headingTypography: patchDevice( attributes.headingTypography, device, patch ) } )
						}
					/>
					<DualColor
						label={ __( 'Color', 'flexa-block' ) }
						value={ headingColor || {} }
						onChange={ ( v ) => setAttributes( { headingColor: v } ) }
					/>
				</>
			) }
		</PanelBody>
	);
};

/**
 * Card parts panel — which pieces of a card are printed, plus the image ratio
 * and hover motion that only matter once the image is shown.
 */
export const ProductRelatedPartsPanel = ( { attributes, setAttributes }: RelatedPanelProps ): JSX.Element => {
	const { showImage, showTitle, showPrice, showRating, showButton, imageRatio, hoverEffect } = attributes;

	return (
		<PanelBody title={ __( 'Card parts', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Image', 'flexa-block' ) }
				checked={ showImage !== false }
				onChange={ ( v: boolean ) => setAttributes( { showImage: v } ) }
			/>
			{ showImage !== false && (
				<>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Image ratio', 'flexa-block' ) }
						value={ imageRatio || '' }
						options={ [ { value: '', label: __( 'Original', 'flexa-block' ) }, ...ASPECT_RATIO_OPTIONS ] }
						onChange={ ( v: string ) => setAttributes( { imageRatio: v } ) }
					/>
					<HoverEffectControl value={ hoverEffect } onChange={ ( v ) => setAttributes( { hoverEffect: v } ) } />
				</>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Title', 'flexa-block' ) }
				checked={ showTitle !== false }
				onChange={ ( v: boolean ) => setAttributes( { showTitle: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Price', 'flexa-block' ) }
				checked={ showPrice !== false }
				onChange={ ( v: boolean ) => setAttributes( { showPrice: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Rating', 'flexa-block' ) }
				checked={ !! showRating }
				onChange={ ( v: boolean ) => setAttributes( { showRating: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Add to cart button', 'flexa-block' ) }
				checked={ !! showButton }
				onChange={ ( v: boolean ) => setAttributes( { showButton: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Card box panel — the card's own background, padding, radius, outline and
 * shadow (the wrapper's equivalents live in the shared foundation panels).
 */
export const ProductRelatedCardPanel = ( { attributes, setAttributes }: RelatedPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { cardBackground, cardPadding, cardRadius, cardBorderWidth, cardBorderColor } = attributes;
	const shadow: BoxShadowAttr = attributes.cardShadow || {};
	const setShadow = ( patch: Partial< BoxShadowAttr > ) => setAttributes( { cardShadow: { ...shadow, ...patch } } );

	return (
		<PanelBody title={ __( 'Card box', 'flexa-block' ) } initialOpen={ false }>
			<DualColor
				label={ __( 'Background', 'flexa-block' ) }
				value={ cardBackground || {} }
				onChange={ ( v ) => setAttributes( { cardBackground: v } ) }
			/>
			<Dimensions
				label={ __( 'Card padding', 'flexa-block' ) }
				responsive
				value={ ( cardPadding?.[ device ] || {} ) as BoxValue }
				units={ SPACING_UNITS }
				onChange={ ( v: BoxValue ) => setAttributes( { cardPadding: { ...cardPadding, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Corner radius', 'flexa-block' ) }
				value={ cardRadius?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 8, rem: 8, '%': 50 } }
				onChange={ ( v: LengthValue ) => setAttributes( { cardRadius: { ...cardRadius, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Border width', 'flexa-block' ) }
				value={ cardBorderWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 20, em: 2, rem: 2, '%': 10 } }
				onChange={ ( v: LengthValue ) => setAttributes( { cardBorderWidth: { ...cardBorderWidth, [ device ]: v } } ) }
			/>
			<DualColor
				label={ __( 'Border color', 'flexa-block' ) }
				value={ cardBorderColor || {} }
				onChange={ ( v ) => setAttributes( { cardBorderColor: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Card shadow', 'flexa-block' ) }
				checked={ !! shadow.enabled }
				onChange={ ( v: boolean ) => setShadow( { enabled: v } ) }
			/>
			{ shadow.enabled && (
				<>
					{ SHADOW_FIELDS.map( ( f ) => (
						<RangeControl
							key={ f.k }
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ f.l }
							value={ parseInt( String( shadow[ f.k ] ?? '' ), 10 ) || 0 }
							min={ -100 }
							max={ 100 }
							onChange={ ( v?: number ) => setShadow( { [ f.k ]: String( v ?? 0 ) } ) }
						/>
					) ) }
					<DualColor label={ __( 'Shadow color', 'flexa-block' ) } value={ shadow.color || {} } onChange={ ( v ) => setShadow( { color: v } ) } />
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Inset', 'flexa-block' ) }
						checked={ !! shadow.inset }
						onChange={ ( v: boolean ) => setShadow( { inset: v } ) }
					/>
				</>
			) }
		</PanelBody>
	);
};

/**
 * Title panel — the product name's typography and colour.
 */
export const ProductRelatedTitlePanel = ( { attributes, setAttributes }: RelatedPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.titleTypography, device );

	return (
		<PanelBody title={ __( 'Title', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls
				value={ typo }
				onChange={ ( patch: Partial< TypographyDevice > ) =>
					setAttributes( { titleTypography: patchDevice( attributes.titleTypography, device, patch ) } )
				}
			/>
			<DualColor
				label={ __( 'Color', 'flexa-block' ) }
				value={ attributes.titleColor || {} }
				onChange={ ( v ) => setAttributes( { titleColor: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Price panel — the price line's typography and colour.
 */
export const ProductRelatedPricePanel = ( { attributes, setAttributes }: RelatedPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.priceTypography, device );

	return (
		<PanelBody title={ __( 'Price', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls
				value={ typo }
				onChange={ ( patch: Partial< TypographyDevice > ) =>
					setAttributes( { priceTypography: patchDevice( attributes.priceTypography, device, patch ) } )
				}
			/>
			<DualColor
				label={ __( 'Color', 'flexa-block' ) }
				value={ attributes.priceColor || {} }
				onChange={ ( v ) => setAttributes( { priceColor: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Rating panel — the star colour (the empty row is the same colour, faded).
 */
export const ProductRelatedRatingPanel = ( { attributes, setAttributes }: RelatedPanelProps ): JSX.Element => (
	<PanelBody title={ __( 'Rating', 'flexa-block' ) } initialOpen={ false }>
		<DualColor
			label={ __( 'Star color', 'flexa-block' ) }
			value={ attributes.starColor || {} }
			onChange={ ( v ) => setAttributes( { starColor: v } ) }
		/>
	</PanelBody>
);
