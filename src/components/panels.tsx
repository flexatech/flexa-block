/**
 * Reusable inspector panels (shared across blocks).
 *
 * Each panel takes { attributes, setAttributes } and reads/writes a shared
 * attribute by name. Responsive panels follow the active editor device.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	ToggleControl,
	BaseControl,
	Flex,
	Button,
	TextControl,
} from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';

import {
	rawDevice,
	patchDevice,
	DIRECTION_OPTIONS,
	JUSTIFY_OPTIONS_ROW,
	JUSTIFY_OPTIONS_COLUMN,
	ALIGN_OPTIONS_ROW,
	ALIGN_OPTIONS_COLUMN,
	WRAP_OPTIONS,
	SPACING_UNITS,
	LENGTH_UNITS,
	OVERFLOW_OPTIONS,
	POSITION_OPTIONS,
	BORDER_STYLE_OPTIONS,
	AlignLeftIcon,
	AlignCenterIcon,
	AlignRightIcon,
	AlignJustifyIcon,
} from '@utils';
import { Segmented, SliderUnit, Dimensions, DualColor, GradientControl, ColorGradientControl, FieldHead, useDevice } from './controls';
import type {
	AdvancedLayoutDevice,
	AnimationAttr,
	BackgroundAttr,
	BorderDevice,
	BoxShadowAttr,
	BoxValue,
	ColorPair,
	ControlOption,
	GridSpanDevice,
	LayoutDevice,
	LengthValue,
	PanelProps,
	RadiusValue,
	ResponsiveValue,
	ResponsiveVisibilityAttr,
	SpacingDevice,
	TextShadowAttr,
	TextStrokeAttr,
	TypographyDevice,
} from '../types';

/* ---------------------------------------------------------------------------
 * Shared option sets — define once here so every block's panels reuse the same
 * value/label lists instead of copy-pasting them. Options with many values are
 * meant to be rendered with a <SelectControl>; short icon sets with <Segmented>.
 * ------------------------------------------------------------------------- */

/** Heading / text element tags (many values → use a SelectControl). */
export const TEXT_TAG_OPTIONS: ControlOption[] = [
	{ value: 'h1', label: 'H1' },
	{ value: 'h2', label: 'H2' },
	{ value: 'h3', label: 'H3' },
	{ value: 'h4', label: 'H4' },
	{ value: 'h5', label: 'H5' },
	{ value: 'h6', label: 'H6' },
	{ value: 'p', label: 'P' },
	{ value: 'span', label: 'Span' },
	{ value: 'div', label: 'Div' },
];

/** Font weights (many values → use a SelectControl). */
export const FONT_WEIGHT_OPTIONS: ControlOption[] = [
	{ value: '', label: __( 'Default', 'flexa-block' ) },
	{ value: '300', label: __( 'Light (300)', 'flexa-block' ) },
	{ value: '400', label: __( 'Normal (400)', 'flexa-block' ) },
	{ value: '500', label: __( 'Medium (500)', 'flexa-block' ) },
	{ value: '600', label: __( 'Semibold (600)', 'flexa-block' ) },
	{ value: '700', label: __( 'Bold (700)', 'flexa-block' ) },
	{ value: '800', label: __( 'Extra bold (800)', 'flexa-block' ) },
	{ value: '900', label: __( 'Black (900)', 'flexa-block' ) },
];

/** Text transform (many values → use a SelectControl). */
export const TEXT_TRANSFORM_OPTIONS: ControlOption[] = [
	{ value: '', label: __( 'Default', 'flexa-block' ) },
	{ value: 'none', label: __( 'None', 'flexa-block' ) },
	{ value: 'uppercase', label: __( 'Uppercase', 'flexa-block' ) },
	{ value: 'lowercase', label: __( 'Lowercase', 'flexa-block' ) },
	{ value: 'capitalize', label: __( 'Capitalize', 'flexa-block' ) },
];

/** Mix-blend-mode (many values → use a SelectControl). */
export const BLEND_MODE_OPTIONS: ControlOption[] = [
	{ value: '', label: __( 'Normal', 'flexa-block' ) },
	{ value: 'multiply', label: __( 'Multiply', 'flexa-block' ) },
	{ value: 'screen', label: __( 'Screen', 'flexa-block' ) },
	{ value: 'overlay', label: __( 'Overlay', 'flexa-block' ) },
	{ value: 'darken', label: __( 'Darken', 'flexa-block' ) },
	{ value: 'lighten', label: __( 'Lighten', 'flexa-block' ) },
	{ value: 'color-dodge', label: __( 'Color Dodge', 'flexa-block' ) },
	{ value: 'color-burn', label: __( 'Color Burn', 'flexa-block' ) },
	{ value: 'hard-light', label: __( 'Hard Light', 'flexa-block' ) },
	{ value: 'soft-light', label: __( 'Soft Light', 'flexa-block' ) },
	{ value: 'difference', label: __( 'Difference', 'flexa-block' ) },
	{ value: 'exclusion', label: __( 'Exclusion', 'flexa-block' ) },
];

/** Text alignment (short icon set → use a Segmented control). */
export const TEXT_ALIGN_OPTIONS: ControlOption[] = [
	{ value: 'left', label: __( 'Left', 'flexa-block' ), icon: AlignLeftIcon },
	{ value: 'center', label: __( 'Center', 'flexa-block' ), icon: AlignCenterIcon },
	{ value: 'right', label: __( 'Right', 'flexa-block' ), icon: AlignRightIcon },
	{ value: 'justify', label: __( 'Justify', 'flexa-block' ), icon: AlignJustifyIcon },
];

/**
 * Content alignment (left / center / right) — the same short icon set as
 * TEXT_ALIGN_OPTIONS but without `justify`, for blocks that align inner content
 * where justify is meaningless (image, testimonial, social-icon, separator,
 * counter, …). Declared once here so those blocks import it instead of each
 * re-declaring the identical array (guide §4.4 / §6.4a).
 */
export const CONTENT_ALIGN_OPTIONS: ControlOption[] = [
	{ value: 'left', label: __( 'Left', 'flexa-block' ), icon: AlignLeftIcon },
	{ value: 'center', label: __( 'Center', 'flexa-block' ), icon: AlignCenterIcon },
	{ value: 'right', label: __( 'Right', 'flexa-block' ), icon: AlignRightIcon },
];

/**
 * Content-width mode (boxed vs full-width) — shared by the section wrappers
 * (container, grid) so the same Segmented choice + labels aren't re-declared.
 */
export const CONTAINER_WIDTH_OPTIONS: ControlOption[] = [
	{ value: 'boxed', label: __( 'Boxed', 'flexa-block' ) },
	{ value: 'full-width', label: __( 'Full Width', 'flexa-block' ) },
];

/**
 * `object-fit` values for media that fills a fixed box (image, before/after).
 * Many values → render with a SelectControl. Shared so image-family blocks don't
 * each re-declare the identical list (guide §4.2 / §6.4a).
 */
export const OBJECT_FIT_OPTIONS: ControlOption[] = [
	{ value: 'cover', label: __( 'Cover', 'flexa-block' ) },
	{ value: 'contain', label: __( 'Contain', 'flexa-block' ) },
	{ value: 'fill', label: __( 'Fill', 'flexa-block' ) },
	{ value: 'none', label: __( 'None', 'flexa-block' ) },
	{ value: 'scale-down', label: __( 'Scale down', 'flexa-block' ) },
];

/**
 * Common CSS aspect ratios for a locked media box. Shared by the image and
 * before/after blocks (guide §4.2 / §6.4a).
 */
export const ASPECT_RATIO_OPTIONS: ControlOption[] = [
	{ value: '1/1', label: __( 'Square 1:1', 'flexa-block' ) },
	{ value: '4/3', label: __( 'Landscape 4:3', 'flexa-block' ) },
	{ value: '3/2', label: __( 'Landscape 3:2', 'flexa-block' ) },
	{ value: '16/9', label: __( 'Widescreen 16:9', 'flexa-block' ) },
	{ value: '21/9', label: __( 'Cinema 21:9', 'flexa-block' ) },
	{ value: '3/4', label: __( 'Portrait 3:4', 'flexa-block' ) },
	{ value: '2/3', label: __( 'Portrait 2:3', 'flexa-block' ) },
	{ value: '9/16', label: __( 'Portrait 9:16', 'flexa-block' ) },
];

/**
 * Typography field group (font size / weight / letter spacing / transform /
 * line height) on one device's TypographyDevice object. Shared by any block
 * that exposes typography (button, heading, …) so the controls stay identical.
 */
export const TypographyControls = ( { value, onChange }: { value: Partial< TypographyDevice >; onChange: ( patch: Partial< TypographyDevice > ) => void } ): JSX.Element => (
	<>
		<SliderUnit
			label={ __( 'Font Size', 'flexa-block' ) }
			value={ value.fontSize || {} }
			units={ LENGTH_UNITS }
			defaultUnit="px"
			max={ { px: 200, rem: 12, em: 12, '%': 300, vw: 30, vh: 30 } }
			onChange={ ( v: LengthValue ) => onChange( { fontSize: v } ) }
		/>
		<SelectControl
			__nextHasNoMarginBottom
			label={ __( 'Font Weight', 'flexa-block' ) }
			value={ value.fontWeight || '' }
			options={ FONT_WEIGHT_OPTIONS }
			onChange={ ( v: string ) => onChange( { fontWeight: v } ) }
		/>
		<SliderUnit
			label={ __( 'Letter Spacing', 'flexa-block' ) }
			value={ value.letterSpacing || {} }
			units={ [ { value: 'px', label: 'px' }, { value: 'em', label: 'em' }, { value: 'rem', label: 'rem' } ] }
			defaultUnit="px"
			min={ -5 }
			max={ { px: 20, em: 2, rem: 2 } }
			onChange={ ( v: LengthValue ) => onChange( { letterSpacing: v } ) }
		/>
		<SelectControl
			__nextHasNoMarginBottom
			label={ __( 'Text Transform', 'flexa-block' ) }
			value={ value.textTransform || '' }
			options={ TEXT_TRANSFORM_OPTIONS }
			onChange={ ( v: string ) => onChange( { textTransform: v } ) }
		/>
		<TextControl
			__nextHasNoMarginBottom
			label={ __( 'Line Height', 'flexa-block' ) }
			type="number"
			step={ 0.1 }
			value={ value.lineHeight ?? '' }
			onChange={ ( v: string ) => onChange( { lineHeight: v } ) }
		/>
	</>
);

/**
 * Typography panel — per-device typography on the block's `typography`
 * attribute. Shared by any block exposing a title/label typography group
 * (button, heading, …) so the panel title, device handling and controls match.
 */
export const TypographyPanel = ( { attributes, setAttributes, initialOpen = false }: PanelProps< { typography?: ResponsiveValue< TypographyDevice > } > ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes.typography, device );
	const set = ( patch: Partial< TypographyDevice > ) => setAttributes( { typography: patchDevice( attributes.typography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Typography', 'flexa-block' ) } initialOpen={ initialOpen }>
			<TypographyControls value={ value } onChange={ set } />
		</PanelBody>
	);
};

/** Attributes the shared TextColorsPanel reads/writes. */
interface TextColorsAttributes {
	textType?: 'color' | 'gradient';
	textColor?: ColorPair;
	textGradient?: ColorPair;
	textColorHover?: ColorPair;
	blendMode?: string;
}

/**
 * Text colours panel — text colour (colour|gradient tab), hover colour and blend
 * mode. Shared by any block whose text carries these (heading, text, …) so the
 * controls stay identical. Reads the shared `textType` / `textColor` /
 * `textGradient` / `textColorHover` / `blendMode` attributes.
 */
export const TextColorsPanel = ( { attributes, setAttributes, initialOpen = true }: PanelProps< TextColorsAttributes > ): JSX.Element => (
	<PanelBody title={ __( 'Colors', 'flexa-block' ) } initialOpen={ initialOpen }>
		<ColorGradientControl
			label={ __( 'Text', 'flexa-block' ) }
			type={ attributes.textType || 'color' }
			color={ attributes.textColor || {} }
			gradient={ attributes.textGradient || {} }
			onTypeChange={ ( v ) => setAttributes( { textType: v } ) }
			onColorChange={ ( v ) => setAttributes( { textColor: v } ) }
			onGradientChange={ ( v ) => setAttributes( { textGradient: v } ) }
		/>
		<DualColor
			label={ __( 'Hover Text', 'flexa-block' ) }
			value={ attributes.textColorHover || {} }
			onChange={ ( v ) => setAttributes( { textColorHover: v } ) }
		/>
		<SelectControl
			__nextHasNoMarginBottom
			label={ __( 'Blend Mode', 'flexa-block' ) }
			value={ attributes.blendMode || '' }
			options={ BLEND_MODE_OPTIONS }
			onChange={ ( v: string ) => setAttributes( { blendMode: v } ) }
		/>
	</PanelBody>
);

/** Attributes the shared EffectsPanel reads/writes. */
interface EffectsAttributes {
	textStroke?: TextStrokeAttr;
	textShadow?: TextShadowAttr;
}

/**
 * Text effects panel — text stroke + text shadow. Shared by any block whose text
 * carries these effects (heading, text, …) so the controls stay identical.
 */
export const EffectsPanel = ( { attributes, setAttributes, initialOpen = false }: PanelProps< EffectsAttributes > ): JSX.Element => {
	const stroke = attributes.textStroke || {};
	const shadow = attributes.textShadow || {};
	const setStroke = ( patch: Partial< TextStrokeAttr > ) => setAttributes( { textStroke: { ...stroke, ...patch } } );
	const setShadow = ( patch: Partial< TextShadowAttr > ) => setAttributes( { textShadow: { ...shadow, ...patch } } );

	return (
		<PanelBody title={ __( 'Text Effects', 'flexa-block' ) } initialOpen={ initialOpen }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Text stroke', 'flexa-block' ) }
				checked={ !! stroke.enabled }
				onChange={ ( v: boolean ) => setStroke( { enabled: v } ) }
			/>
			{ stroke.enabled && (
				<>
					<SliderUnit
						label={ __( 'Stroke Width', 'flexa-block' ) }
						showDevice={ false }
						value={ stroke.width || {} }
						units={ [ { value: 'px', label: 'px' }, { value: 'em', label: 'em' } ] }
						defaultUnit="px"
						max={ { px: 20, em: 2 } }
						onChange={ ( v: LengthValue ) => setStroke( { width: v } ) }
					/>
					<DualColor label={ __( 'Stroke Color', 'flexa-block' ) } value={ stroke.color || {} } onChange={ ( v ) => setStroke( { color: v } ) } />
				</>
			) }

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Text shadow', 'flexa-block' ) }
				checked={ !! shadow.enabled }
				onChange={ ( v: boolean ) => setShadow( { enabled: v } ) }
			/>
			{ shadow.enabled && (
				<>
					<RangeControl __nextHasNoMarginBottom label={ __( 'X offset', 'flexa-block' ) } value={ parseInt( String( shadow.horizontal ?? '0' ), 10 ) || 0 } min={ -50 } max={ 50 } onChange={ ( v: number ) => setShadow( { horizontal: String( v ) } ) } />
					<RangeControl __nextHasNoMarginBottom label={ __( 'Y offset', 'flexa-block' ) } value={ parseInt( String( shadow.vertical ?? '0' ), 10 ) || 0 } min={ -50 } max={ 50 } onChange={ ( v: number ) => setShadow( { vertical: String( v ) } ) } />
					<RangeControl __nextHasNoMarginBottom label={ __( 'Blur', 'flexa-block' ) } value={ parseInt( String( shadow.blur ?? '0' ), 10 ) || 0 } min={ 0 } max={ 100 } onChange={ ( v: number ) => setShadow( { blur: String( v ) } ) } />
					<DualColor label={ __( 'Shadow Color', 'flexa-block' ) } value={ shadow.color || {} } onChange={ ( v ) => setShadow( { color: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Layout panel — flex display, direction, alignment, gap.
 */
export const LayoutPanel = ( { attributes, setAttributes, initialOpen = true }: PanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes.layout, device );
	const set = ( patch: Partial< LayoutDevice > ) => setAttributes( { layout: patchDevice( attributes.layout, device, patch ) } );
	const display = value.display || 'flex';
	const gap = value.gap || {};
	const isRow = ( value.direction || 'column' ) === 'row';

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ initialOpen }>
			<Segmented
				label={ __( 'Display', 'flexa-block' ) }
				value={ display }
				responsive
				onChange={ ( v ) => set( { display: v } ) }
				options={ [
					{ value: 'flex', label: __( 'Flex', 'flexa-block' ) },
					{ value: 'block', label: __( 'Block', 'flexa-block' ) },
				] }
			/>

			{ display === 'flex' && (
				<>
					<Segmented label={ __( 'Direction', 'flexa-block' ) } responsive value={ value.direction || 'column' } onChange={ ( v ) => set( { direction: v } ) } options={ DIRECTION_OPTIONS } />
					<Segmented label={ __( 'Justify', 'flexa-block' ) } responsive value={ value.justifyContent || 'flex-start' } onChange={ ( v ) => set( { justifyContent: v } ) } options={ isRow ? JUSTIFY_OPTIONS_ROW : JUSTIFY_OPTIONS_COLUMN } />
					<Segmented label={ __( 'Align', 'flexa-block' ) } responsive value={ value.alignItems || 'stretch' } onChange={ ( v ) => set( { alignItems: v } ) } options={ isRow ? ALIGN_OPTIONS_ROW : ALIGN_OPTIONS_COLUMN } />
					<Segmented label={ __( 'Wrap', 'flexa-block' ) } responsive value={ value.wrap || 'nowrap' } onChange={ ( v ) => set( { wrap: v } ) } options={ WRAP_OPTIONS } />
					<div className="flexa-field">
						<FieldHead label={ __( 'Gap', 'flexa-block' ) } />
						<SliderUnit
							label={ __( 'Column', 'flexa-block' ) }
							showDevice={ false }
							value={ { value: gap.column, unit: gap.unit } }
							units={ SPACING_UNITS }
							max={ { px: 200, '%': 100, em: 20, rem: 20, vh: 100, vw: 100 } }
							onChange={ ( v ) => set( { gap: { ...gap, column: v.value ?? '', unit: v.unit || gap.unit || 'px' } } ) }
						/>
						<SliderUnit
							label={ __( 'Row', 'flexa-block' ) }
							showDevice={ false }
							value={ { value: gap.row, unit: gap.unit } }
							units={ SPACING_UNITS }
							max={ { px: 200, '%': 100, em: 20, rem: 20, vh: 100, vw: 100 } }
							onChange={ ( v ) => set( { gap: { ...gap, row: v.value ?? '', unit: v.unit || gap.unit || 'px' } } ) }
						/>
					</div>
				</>
			) }
		</PanelBody>
	);
};

/**
 * Grid Item panel — column/row span for a block that sits directly inside a
 * Grid. Rendered only when the block is a grid child (see the block edit files).
 * Empty value = span 1 (occupies a single cell).
 */
export const GridItemPanel = ( { attributes, setAttributes, initialOpen = true }: PanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes.gridSpan, device ) as GridSpanDevice;
	const set = ( patch: Partial< GridSpanDevice > ) => setAttributes( { gridSpan: patchDevice( attributes.gridSpan, device, patch ) } );

	return (
		<PanelBody title={ __( 'Grid Item', 'flexa-block' ) } initialOpen={ initialOpen }>
			<div className="flexa-field">
				<FieldHead label={ __( 'Column Span', 'flexa-block' ) } />
				<RangeControl
					__nextHasNoMarginBottom
					value={ value.column ? parseInt( value.column, 10 ) : undefined }
					min={ 1 }
					max={ 12 }
					allowReset
					onChange={ ( v?: number ) => set( { column: v ? String( v ) : '' } ) }
				/>
			</div>
			<div className="flexa-field">
				<FieldHead label={ __( 'Row Span', 'flexa-block' ) } />
				<RangeControl
					__nextHasNoMarginBottom
					value={ value.row ? parseInt( value.row, 10 ) : undefined }
					min={ 1 }
					max={ 12 }
					allowReset
					onChange={ ( v?: number ) => set( { row: v ? String( v ) : '' } ) }
				/>
			</div>
		</PanelBody>
	);
};

/**
 * Spacing panel — padding + margin.
 */
export const SpacingPanel = ( { attributes, setAttributes, initialOpen = false }: PanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes.spacing, device );
	const set = ( patch: Partial< SpacingDevice > ) => setAttributes( { spacing: patchDevice( attributes.spacing, device, patch ) } );

	return (
		<PanelBody title={ __( 'Spacing', 'flexa-block' ) } initialOpen={ initialOpen }>
			<Dimensions label={ __( 'Margin', 'flexa-block' ) } responsive value={ value.margin || {} } units={ SPACING_UNITS } onChange={ ( v ) => set( { margin: v } ) } />
			<Dimensions label={ __( 'Padding', 'flexa-block' ) } responsive value={ value.padding || {} } units={ SPACING_UNITS } onChange={ ( v ) => set( { padding: v } ) } />
		</PanelBody>
	);
};

/**
 * Background panel — color / gradient / image with light & dark colors.
 * Pass `allowImage={ false }` for blocks that only support colour/gradient.
 */
export const BackgroundPanel = ( { attributes, setAttributes, initialOpen = false, allowImage = true }: PanelProps & { allowImage?: boolean } ): JSX.Element => {
	const bg: BackgroundAttr = attributes.background || {};
	const set = ( patch: Partial< BackgroundAttr > ) => setAttributes( { background: { ...bg, ...patch } } );

	const typeOptions = [
		{ value: 'none', label: __( 'None', 'flexa-block' ) },
		{ value: 'classic', label: __( 'Color', 'flexa-block' ) },
		{ value: 'gradient', label: __( 'Gradient', 'flexa-block' ) },
		...( allowImage ? [ { value: 'image', label: __( 'Image', 'flexa-block' ) } ] : [] ),
	];

	return (
		<PanelBody title={ __( 'Background', 'flexa-block' ) } initialOpen={ initialOpen }>
			<Segmented
				label={ __( 'Type', 'flexa-block' ) }
				value={ bg.type || 'none' }
				onChange={ ( v ) => set( { type: v as BackgroundAttr[ 'type' ] } ) }
				options={ typeOptions }
			/>

			{ bg.type === 'classic' && (
				<DualColor label={ __( 'Color', 'flexa-block' ) } value={ bg.color || {} } onChange={ ( v ) => set( { color: v } ) } />
			) }

			{ bg.type === 'gradient' && (
				<GradientControl label={ __( 'Gradient', 'flexa-block' ) } value={ bg.gradient || {} } onChange={ ( v ) => set( { gradient: v } ) } />
			) }

			{ bg.type === 'image' && (
				<BaseControl __nextHasNoMarginBottom label={ __( 'Image', 'flexa-block' ) }>
					<MediaUploadCheck>
						<MediaUpload
							allowedTypes={ [ 'image' ] }
							value={ bg.image?.id }
							onSelect={ ( media: { id: number; url: string } ) => set( { image: { ...bg.image, id: media.id, url: media.url } } ) }
							render={ ( { open }: { open: () => void } ) => (
								<>
									{ bg.image?.url && (
										<button type="button" className="flexa-bg-preview" onClick={ open } aria-label={ __( 'Replace image', 'flexa-block' ) }>
											<img src={ bg.image.url } alt="" />
										</button>
									) }
									<Flex gap={ 2 } justify="flex-start">
										<Button variant="secondary" onClick={ open }>
											{ bg.image?.url ? __( 'Replace', 'flexa-block' ) : __( 'Select Image', 'flexa-block' ) }
										</Button>
										{ bg.image?.url && (
											<Button isDestructive variant="tertiary" onClick={ () => set( { image: { ...bg.image, id: null, url: '' } } ) }>
												{ __( 'Remove', 'flexa-block' ) }
											</Button>
										) }
									</Flex>
								</>
							) }
						/>
					</MediaUploadCheck>
				</BaseControl>
			) }

			{ bg.type === 'image' && bg.image?.url && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Lazy load image', 'flexa-block' ) }
					help={ __( 'Only fetch the background image when it nears the viewport.', 'flexa-block' ) }
					checked={ !! bg.lazyLoad }
					onChange={ ( v: boolean ) => set( { lazyLoad: v } ) }
				/>
			) }
		</PanelBody>
	);
};

// Radius uses corner keys; map to/from the 4-side Dimensions control.
const mapRadiusToBox = ( r: RadiusValue = {} ): BoxValue => ( { top: r.topLeft ?? '', right: r.topRight ?? '', bottom: r.bottomRight ?? '', left: r.bottomLeft ?? '', unit: r.unit || 'px' } );
const mapBoxToRadius = ( b: BoxValue = {} ): RadiusValue => ( { topLeft: b.top ?? '', topRight: b.right ?? '', bottomRight: b.bottom ?? '', bottomLeft: b.left ?? '', unit: b.unit || 'px' } );

/**
 * Border panel — style, width, color, radius (responsive width/radius).
 */
export const BorderPanel = ( { attributes, setAttributes, initialOpen = false }: PanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes.border, device );
	const set = ( patch: Partial< BorderDevice > ) => setAttributes( { border: patchDevice( attributes.border, device, patch ) } );

	return (
		<PanelBody title={ __( 'Border', 'flexa-block' ) } initialOpen={ initialOpen }>
			<SelectControl __nextHasNoMarginBottom label={ __( 'Style', 'flexa-block' ) } value={ value.style || '' } options={ BORDER_STYLE_OPTIONS } onChange={ ( v: string ) => set( { style: v } ) } />
			<Dimensions label={ __( 'Width', 'flexa-block' ) } responsive value={ value.width || {} } units={ SPACING_UNITS } onChange={ ( v ) => set( { width: v } ) } />
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ value.color || {} } onChange={ ( v ) => set( { color: v } ) } />
			<Dimensions label={ __( 'Radius', 'flexa-block' ) } responsive value={ mapRadiusToBox( value.radius || {} ) } units={ SPACING_UNITS } onChange={ ( v ) => set( { radius: mapBoxToRadius( v ) } ) } />
		</PanelBody>
	);
};

/** Box shadow numeric offset fields. */
const SHADOW_FIELDS: Array< { k: 'horizontal' | 'vertical' | 'blur' | 'spread'; l: string } > = [
	{ k: 'horizontal', l: __( 'X offset', 'flexa-block' ) },
	{ k: 'vertical', l: __( 'Y offset', 'flexa-block' ) },
	{ k: 'blur', l: __( 'Blur', 'flexa-block' ) },
	{ k: 'spread', l: __( 'Spread', 'flexa-block' ) },
];

/**
 * Box shadow panel.
 */
export const ShadowPanel = ( { attributes, setAttributes, initialOpen = false }: PanelProps ): JSX.Element => {
	const shadow: BoxShadowAttr = attributes.boxShadow || {};
	const set = ( patch: Partial< BoxShadowAttr > ) => setAttributes( { boxShadow: { ...shadow, ...patch } } );

	return (
		<PanelBody title={ __( 'Box Shadow', 'flexa-block' ) } initialOpen={ initialOpen }>
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Enable', 'flexa-block' ) } checked={ !! shadow.enabled } onChange={ ( v: boolean ) => set( { enabled: v } ) } />
			{ shadow.enabled && (
				<>
					{ SHADOW_FIELDS.map( ( f ) => (
						<RangeControl key={ f.k } __nextHasNoMarginBottom label={ f.l } value={ parseInt( String( shadow[ f.k ] ?? '' ), 10 ) || 0 } min={ -100 } max={ 100 } onChange={ ( v: number ) => set( { [ f.k ]: String( v ) } ) } />
					) ) }
					<DualColor label={ __( 'Color', 'flexa-block' ) } value={ shadow.color || {} } onChange={ ( v ) => set( { color: v } ) } />
					<ToggleControl __nextHasNoMarginBottom label={ __( 'Inset', 'flexa-block' ) } checked={ !! shadow.inset } onChange={ ( v: boolean ) => set( { inset: v } ) } />
				</>
			) }
		</PanelBody>
	);
};

/**
 * Position panel — position + offsets + overflow + z-index (responsive).
 */
export const PositionPanel = ( { attributes, setAttributes, initialOpen = true }: PanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const value = rawDevice( attributes.advancedLayout, device );
	const set = ( patch: Partial< AdvancedLayoutDevice > ) => setAttributes( { advancedLayout: patchDevice( attributes.advancedLayout, device, patch ) } );

	// Offsets only affect positioned elements — show them once a real position is set.
	const positioned = [ 'relative', 'absolute', 'fixed', 'sticky' ].includes( value.position || '' );

	return (
		<PanelBody title={ __( 'Position & Overflow', 'flexa-block' ) } initialOpen={ initialOpen }>
			<SelectControl __nextHasNoMarginBottom label={ __( 'Position', 'flexa-block' ) } value={ value.position || '' } options={ POSITION_OPTIONS } onChange={ ( v: string ) => set( { position: v } ) } />
			{ positioned && (
				<Dimensions label={ __( 'Offset (T/R/B/L)', 'flexa-block' ) } responsive value={ value.inset || {} } units={ SPACING_UNITS } onChange={ ( v ) => set( { inset: v } ) } />
			) }
			<SelectControl __nextHasNoMarginBottom label={ __( 'Overflow', 'flexa-block' ) } value={ value.overflow || '' } options={ OVERFLOW_OPTIONS } onChange={ ( v: string ) => set( { overflow: v } ) } />
			<TextControl __nextHasNoMarginBottom type="number" label={ __( 'Z-Index', 'flexa-block' ) } value={ value.zIndex ?? '' } onChange={ ( v: string ) => set( { zIndex: v } ) } />
		</PanelBody>
	);
};

/**
 * Visibility panel — hide per device.
 */
export const VisibilityPanel = ( { attributes, setAttributes, initialOpen = false }: PanelProps ): JSX.Element => {
	const vis = attributes.responsiveVisibility || {};
	const setVis = ( patch: Partial< ResponsiveVisibilityAttr > ) => setAttributes( { responsiveVisibility: { ...vis, ...patch } } );

	return (
		<PanelBody title={ __( 'Visibility', 'flexa-block' ) } initialOpen={ initialOpen }>
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Hide on Desktop', 'flexa-block' ) } checked={ !! vis.hideOnDesktop } onChange={ ( v: boolean ) => setVis( { hideOnDesktop: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Hide on Tablet', 'flexa-block' ) } checked={ !! vis.hideOnTablet } onChange={ ( v: boolean ) => setVis( { hideOnTablet: v } ) } />
			<ToggleControl __nextHasNoMarginBottom label={ __( 'Hide on Mobile', 'flexa-block' ) } checked={ !! vis.hideOnMobile } onChange={ ( v: boolean ) => setVis( { hideOnMobile: v } ) } />
		</PanelBody>
	);
};

/**
 * Scroll-entrance animation options (Flatsome set + zoomIn/scaleIn). Kept here so
 * the value list is defined once and matches the effect names the front-end CSS
 * (`.flexa-anim--<value>`) and the render_block filter's whitelist expect.
 */
export const ANIMATION_OPTIONS: ControlOption[] = [
	{ value: 'none', label: __( 'None', 'flexa-block' ) },
	{ value: 'fadeIn', label: __( 'Fade In', 'flexa-block' ) },
	{ value: 'fadeInUp', label: __( 'Fade In Up', 'flexa-block' ) },
	{ value: 'fadeInDown', label: __( 'Fade In Down', 'flexa-block' ) },
	{ value: 'fadeInLeft', label: __( 'Fade In Left', 'flexa-block' ) },
	{ value: 'fadeInRight', label: __( 'Fade In Right', 'flexa-block' ) },
	{ value: 'zoomIn', label: __( 'Zoom In', 'flexa-block' ) },
	{ value: 'scaleIn', label: __( 'Scale In', 'flexa-block' ) },
	{ value: 'blurIn', label: __( 'Blur In', 'flexa-block' ) },
	{ value: 'bounceIn', label: __( 'Bounce In', 'flexa-block' ) },
	{ value: 'bounceInUp', label: __( 'Bounce In Up', 'flexa-block' ) },
	{ value: 'bounceInDown', label: __( 'Bounce In Down', 'flexa-block' ) },
	{ value: 'bounceInLeft', label: __( 'Bounce In Left', 'flexa-block' ) },
	{ value: 'bounceInRight', label: __( 'Bounce In Right', 'flexa-block' ) },
	{ value: 'flipInX', label: __( 'Flip In X', 'flexa-block' ) },
	{ value: 'flipInY', label: __( 'Flip In Y', 'flexa-block' ) },
];

/** Animation speed presets — map to fixed durations in the shared CSS. */
export const ANIMATION_DURATION_OPTIONS: ControlOption[] = [
	{ value: 'slow', label: __( 'Slow', 'flexa-block' ) },
	{ value: 'normal', label: __( 'Normal', 'flexa-block' ) },
	{ value: 'fast', label: __( 'Fast', 'flexa-block' ) },
];

/**
 * Animation panel — a scroll-entrance effect that plays once when the block
 * enters the viewport. Reads/writes the shared `animation` attribute; the effect
 * itself is applied on the front end (shared render_block filter + observer), so
 * this panel only stores the choice.
 */
export const AnimationPanel = ( { attributes, setAttributes, initialOpen = false }: PanelProps ): JSX.Element => {
	const anim = attributes.animation || {};
	const set = ( patch: Partial< AnimationAttr > ) => setAttributes( { animation: { ...anim, ...patch } } );
	const enabled = ( anim.type || 'none' ) !== 'none';
	const delay = parseInt( anim.delay || '0', 10 ) || 0;

	return (
		<PanelBody title={ __( 'Animation', 'flexa-block' ) } initialOpen={ initialOpen }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Effect', 'flexa-block' ) }
				value={ anim.type || 'none' }
				options={ ANIMATION_OPTIONS }
				onChange={ ( v: string ) => set( { type: v } ) }
			/>
			{ enabled && (
				<>
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Speed', 'flexa-block' ) }
						value={ anim.duration || 'normal' }
						options={ ANIMATION_DURATION_OPTIONS }
						onChange={ ( v: string ) => set( { duration: v as AnimationAttr[ 'duration' ] } ) }
					/>
					<RangeControl
						__nextHasNoMarginBottom
						label={ __( 'Delay (ms)', 'flexa-block' ) }
						value={ delay }
						min={ 0 }
						max={ 10000 }
						step={ 100 }
						onChange={ ( v?: number ) => set( { delay: v ? String( v ) : '' } ) }
					/>
				</>
			) }
		</PanelBody>
	);
};
