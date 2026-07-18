/**
 * Post Filter block — block-specific inspector panels.
 *
 * The shared foundational panels (spacing / background / border / shadow /
 * position / visibility / animation) come from @components; these cover what is
 * unique to the filter bar: WHICH grid it drives, how the controls are laid out,
 * and how the labels, inputs and button look. The children carry no styling of
 * their own — everything a visitor sees is styled from here, so there is one place
 * to make the bar match the theme.
 *
 * @package Flexa\Block
 */

import { __, sprintf } from '@wordpress/i18n';
import { PanelBody, SelectControl, TextControl, ToggleControl, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

import {
	Segmented,
	SliderUnit,
	Dimensions,
	DualColor,
	TypographyControls,
	useDevice,
} from '@components';
import { LENGTH_UNITS, SPACING_UNITS, LINE_STYLE_OPTIONS, rawDevice, patchDevice } from '@utils';
import type { BorderDevice, LengthValue, PanelProps, PostFilterAttributes, TypographyDevice } from '../../types';

type FilterPanelProps = PanelProps< PostFilterAttributes >;

/** One entry per Post Grid found in the editor. */
interface GridOption {
	value: string;
	label: string;
}

/**
 * Every Post Grid in the current editor, so the bar can be pointed at one by name
 * instead of by hand-copied id.
 *
 * A grid living in an FSE template while the filter lives in post content is in a
 * DIFFERENT editor store and cannot be enumerated from here — that is what the
 * manual "Grid ID" field below exists for.
 */
const useGridOptions = (): GridOption[] =>
	useSelect( ( select: ( store: string ) => any ) => {
		const editor = select( 'core/block-editor' );
		if ( ! editor?.getClientIdsWithDescendants ) {
			return [] as GridOption[];
		}
		return editor
			.getClientIdsWithDescendants()
			.filter( ( clientId: string ) => 'flexa/post-grid' === editor.getBlockName( clientId ) )
			.map( ( clientId: string, index: number ) => {
				const attrs = editor.getBlockAttributes( clientId ) || {};
				return {
					value: String( attrs.blockId || '' ),
					/* translators: 1: grid number on the page, 2: the grid's post type. */
					label: `${ __( 'Post Grid', 'flexa-block' ) } ${ index + 1 } — ${ attrs.postType || 'post' }`,
				};
			} )
			.filter( ( option: GridOption ) => '' !== option.value );
	}, [] );

/**
 * Filter panel — the grid this bar drives, plus how it submits.
 */
export const PostFilterTargetPanel = ( { attributes, setAttributes }: FilterPanelProps ): JSX.Element => {
	const { targetGridId, formLabel, submitText } = attributes;
	const grids = useGridOptions();

	// Set, but no grid on this page carries that id. Either it points at a grid in a
	// template (legitimate) or at one that was deleted / mistyped (a dead filter that
	// silently does nothing on the front end). We can't tell which from here — so say
	// so plainly instead of quietly showing "Auto", which would be a lie: the saved
	// value still wins, and the bar would keep filtering a grid that isn't there.
	const isDetached = !! targetGridId && ! grids.some( ( grid ) => grid.value === targetGridId );

	return (
		<PanelBody title={ __( 'Filter', 'flexa-block' ) } initialOpen={ true }>
			{ ! grids.length && ! targetGridId && (
				<Notice status="warning" isDismissible={ false }>
					{ __( 'No Post Grid on this page yet — add one and this bar will filter it.', 'flexa-block' ) }
				</Notice>
			) }
			{ isDetached && (
				<Notice status="warning" isDismissible={ false }>
					{ sprintf(
						/* translators: %s: the blockId the filter bar is pointing at. */
						__( 'No Post Grid on this page has the id "%s". If the grid lives in a template this is fine — otherwise the filters will do nothing. Pick a grid below, or choose Auto.', 'flexa-block' ),
						targetGridId
					) }
				</Notice>
			) }
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Post Grid to filter', 'flexa-block' ) }
				help={ __( 'The grid can sit anywhere on the page — a sidebar filter driving a grid in the main column works fine.', 'flexa-block' ) }
				value={ targetGridId || '' }
				options={ [
					{ value: '', label: __( 'Auto — first Post Grid on the page', 'flexa-block' ) },
					...grids,
					// Keep the unknown id selectable, so opening the panel can't silently
					// rewrite a deliberate template target back to Auto.
					...( isDetached ? [ { value: targetGridId as string, label: sprintf( __( 'Not on this page — %s', 'flexa-block' ), targetGridId ) } ] : [] ),
				] }
				onChange={ ( v: string ) => setAttributes( { targetGridId: v } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Grid ID', 'flexa-block' ) }
				help={ __( 'Only needed when the grid lives in a template rather than in this content.', 'flexa-block' ) }
				value={ targetGridId || '' }
				onChange={ ( v: string ) => setAttributes( { targetGridId: v.trim() } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Button text', 'flexa-block' ) }
				value={ submitText ?? '' }
				onChange={ ( v: string ) => setAttributes( { submitText: v } ) }
			/>
			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Accessible name', 'flexa-block' ) }
				help={ __( 'Announced to screen readers as the name of this search region.', 'flexa-block' ) }
				value={ formLabel ?? '' }
				onChange={ ( v: string ) => setAttributes( { formLabel: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Layout panel — direction, gap and vertical alignment of the controls.
 */
export const PostFilterLayoutPanel = ( { attributes, setAttributes }: FilterPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { direction, gap, align } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Direction', 'flexa-block' ) }
				value={ direction || 'row' }
				onChange={ ( v ) => setAttributes( { direction: v as PostFilterAttributes[ 'direction' ] } ) }
				options={ [
					{ value: 'row', label: __( 'Row', 'flexa-block' ) },
					{ value: 'column', label: __( 'Column', 'flexa-block' ) },
				] }
			/>
			<SliderUnit
				label={ __( 'Gap', 'flexa-block' ) }
				value={ rawDevice( gap, device ) }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, rem: 5, em: 5 } }
				onChange={ ( v: LengthValue ) => setAttributes( { gap: patchDevice( gap, device, v ) } ) }
			/>
			<Segmented
				label={ __( 'Align', 'flexa-block' ) }
				value={ align || 'flex-start' }
				onChange={ ( v ) => setAttributes( { align: v as PostFilterAttributes[ 'align' ] } ) }
				options={ [
					{ value: 'flex-start', label: __( 'Top', 'flexa-block' ) },
					{ value: 'center', label: __( 'Middle', 'flexa-block' ) },
					{ value: 'flex-end', label: __( 'Bottom', 'flexa-block' ) },
					{ value: 'stretch', label: __( 'Stretch', 'flexa-block' ) },
				] }
			/>
		</PanelBody>
	);
};

/**
 * Label panel — the small caption above each control.
 *
 * Label / Control / Control border are three panels rather than one, even though
 * they all style the same children: the bar carries TWO typography groups, and a
 * single panel holding both plus a full border group is a scroll, not a panel —
 * you cannot see the label settings and the control settings at the same time
 * anyway, so collapsing the one you are not editing is pure gain.
 */
export const PostFilterLabelPanel = ( { attributes, setAttributes }: FilterPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { labelTypography, labelColor } = attributes;

	return (
		<PanelBody title={ __( 'Label', 'flexa-block' ) } initialOpen={ true }>
			<TypographyControls
				value={ rawDevice( labelTypography, device ) }
				onChange={ ( patch: Partial< TypographyDevice > ) => setAttributes( { labelTypography: patchDevice( labelTypography, device, patch ) } ) }
			/>
			<DualColor label={ __( 'Label color', 'flexa-block' ) } value={ labelColor || {} } onChange={ ( v ) => setAttributes( { labelColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Control panel — the search box, dropdown, checkbox and pill. One set of controls
 * styles every one of them, so the bar stays internally consistent by construction.
 */
export const PostFilterControlPanel = ( { attributes, setAttributes }: FilterPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { controlTypography, controlColor, controlPlaceholderColor, controlBackground, controlPadding } = attributes;

	return (
		<PanelBody title={ __( 'Control', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls
				value={ rawDevice( controlTypography, device ) }
				onChange={ ( patch: Partial< TypographyDevice > ) => setAttributes( { controlTypography: patchDevice( controlTypography, device, patch ) } ) }
			/>
			<DualColor label={ __( 'Text color', 'flexa-block' ) } value={ controlColor || {} } onChange={ ( v ) => setAttributes( { controlColor: v } ) } />
			<DualColor label={ __( 'Placeholder color', 'flexa-block' ) } value={ controlPlaceholderColor || {} } onChange={ ( v ) => setAttributes( { controlPlaceholderColor: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ controlBackground || {} } onChange={ ( v ) => setAttributes( { controlBackground: v } ) } />
			<Dimensions label={ __( 'Padding', 'flexa-block' ) } value={ controlPadding || {} } units={ SPACING_UNITS } onChange={ ( v ) => setAttributes( { controlPadding: v } ) } />
		</PanelBody>
	);
};

/**
 * Control border panel.
 *
 * Titled "Control border", NOT "Border": the Style tab already carries the shared
 * BorderPanel, which draws the border around the BAR. Two panels both called
 * "Border" styling different boxes is the kind of thing you only discover by
 * changing the wrong one.
 */
export const PostFilterControlBorderPanel = ( { attributes, setAttributes }: FilterPanelProps ): JSX.Element => {
	const border = attributes.controlBorder || {};
	const setBorder = ( patch: Partial< BorderDevice > ) => setAttributes( { controlBorder: { ...border, ...patch } } );

	return (
		<PanelBody title={ __( 'Control border', 'flexa-block' ) } initialOpen={ false }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Style', 'flexa-block' ) }
				value={ border.style || '' }
				options={ [ { value: '', label: __( 'None', 'flexa-block' ) }, ...LINE_STYLE_OPTIONS ] }
				onChange={ ( v: string ) => setBorder( { style: v } ) }
			/>
			<SliderUnit
				label={ __( 'Width', 'flexa-block' ) }
				showDevice={ false }
				value={ { value: border.width?.top, unit: border.width?.unit || 'px' } }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 10, rem: 1, em: 1 } }
				onChange={ ( v: LengthValue ) => setBorder( { width: { top: v.value ?? '', right: v.value ?? '', bottom: v.value ?? '', left: v.value ?? '', unit: v.unit || 'px' } } ) }
			/>
			<DualColor label={ __( 'Border color', 'flexa-block' ) } value={ border.color || {} } onChange={ ( v ) => setBorder( { color: v } ) } />
			{ /* Focus is the one state a keyboard visitor navigates by, so it gets its own
			     colour rather than only ever borrowing the Button's. Left empty it still
			     does borrow it — the accent is the right default, just not the only option. */ }
			<DualColor
				label={ __( 'Border color (focus)', 'flexa-block' ) }
				value={ attributes.controlBorderFocus || {} }
				onChange={ ( v ) => setAttributes( { controlBorderFocus: v } ) }
			/>
			<SliderUnit
				label={ __( 'Radius', 'flexa-block' ) }
				showDevice={ false }
				value={ { value: border.radius?.topLeft, unit: border.radius?.unit || 'px' } }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 40, rem: 3, em: 3 } }
				onChange={ ( v: LengthValue ) => setBorder( { radius: { topLeft: v.value ?? '', topRight: v.value ?? '', bottomRight: v.value ?? '', bottomLeft: v.value ?? '', unit: v.unit || 'px' } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Button panel — the submit button and the reset link.
 */
export const PostFilterButtonPanel = ( { attributes, setAttributes }: FilterPanelProps ): JSX.Element => {
	const { buttonColor, buttonBackground, buttonColorHover, buttonBackgroundHover, buttonPadding, buttonRadius } = attributes;

	return (
		<PanelBody title={ __( 'Button', 'flexa-block' ) } initialOpen={ false }>
			<DualColor label={ __( 'Text color', 'flexa-block' ) } value={ buttonColor || {} } onChange={ ( v ) => setAttributes( { buttonColor: v } ) } />
			<DualColor label={ __( 'Background', 'flexa-block' ) } value={ buttonBackground || {} } onChange={ ( v ) => setAttributes( { buttonBackground: v } ) } />
			<DualColor label={ __( 'Text color (hover)', 'flexa-block' ) } value={ buttonColorHover || {} } onChange={ ( v ) => setAttributes( { buttonColorHover: v } ) } />
			<DualColor label={ __( 'Background (hover)', 'flexa-block' ) } value={ buttonBackgroundHover || {} } onChange={ ( v ) => setAttributes( { buttonBackgroundHover: v } ) } />
			<Dimensions label={ __( 'Padding', 'flexa-block' ) } value={ buttonPadding || {} } units={ SPACING_UNITS } onChange={ ( v ) => setAttributes( { buttonPadding: v } ) } />
			<SliderUnit
				label={ __( 'Border radius', 'flexa-block' ) }
				showDevice={ false }
				value={ buttonRadius || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 60, rem: 4, em: 4, '%': 50 } }
				onChange={ ( v: LengthValue ) => setAttributes( { buttonRadius: v } ) }
			/>
		</PanelBody>
	);
};
