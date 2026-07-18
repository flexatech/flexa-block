/**
 * Heading block — heading-specific inspector panels.
 *
 * The shared panels (text colours / effects / spacing / background / border /
 * shadow / visibility) plus the shared option sets and typography controls come
 * from @components; these cover the parts unique to the heading: content,
 * subheading and separator. Following the "prefer theme styles" rule nothing is
 * styled by default — only values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, TextControl, ToggleControl, SelectControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	DualColor,
	TypographyControls,
	useDevice,
	TEXT_TAG_OPTIONS,
	TEXT_ALIGN_OPTIONS,
} from '@components';
import { rawDevice, patchDevice, LENGTH_UNITS, SPACING_UNITS, WEIGHT_UNITS, BORDER_STYLE_OPTIONS } from '@utils';
import type { ControlOption, HeadingAttributes, LengthValue, PanelProps, TypographyDevice } from '../../types';

type HeadingPanelProps = PanelProps< HeadingAttributes >;

/** Top / bottom placement — shared by the subheading and separator positions. */
const POSITION_OPTIONS: ControlOption[] = [
	{ value: 'top', label: __( 'Top', 'flexa-block' ) },
	{ value: 'bottom', label: __( 'Bottom', 'flexa-block' ) },
];

/** Add or remove a token from a space-separated `rel` string. */
const toggleRel = ( rel: string | undefined, token: string, on: boolean ): string => {
	const parts = ( rel || '' ).split( /\s+/ ).filter( Boolean ).filter( ( t ) => t !== token );
	if ( on ) {
		parts.push( token );
	}
	return parts.join( ' ' );
};

const relHas = ( rel: string | undefined, token: string ): boolean => ( rel || '' ).split( /\s+/ ).includes( token );

// Note: the Typography panel is generic (reads the shared `typography`
// attribute) so it lives in @components (TypographyPanel) and is reused as-is by
// both this block and Button. Only the heading-specific panels live here.

/**
 * Content panel — heading tag, link, alignment, gap and the element toggles.
 */
export const HeadingContentPanel = ( { attributes, setAttributes }: HeadingPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { tag, url, linkTarget, rel, alignment, gap, showSubheading, showSeparator } = attributes;
	const align = alignment?.[ device ] || ( device === 'desktop' ? 'left' : '' );

	return (
		<PanelBody title={ __( 'Heading', 'flexa-block' ) } initialOpen={ true }>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ tag || 'h2' }
				options={ TEXT_TAG_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { tag: v } ) }
			/>

			<div className="flexa-field">
				<Segmented
					label={ __( 'Alignment', 'flexa-block' ) }
					value={ align }
					responsive
					onChange={ ( v ) => setAttributes( { alignment: { ...alignment, [ device ]: v } } ) }
					options={ TEXT_ALIGN_OPTIONS }
				/>
			</div>

			<SliderUnit
				label={ __( 'Gap', 'flexa-block' ) }
				showDevice={ false }
				value={ gap || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 10, rem: 10, '%': 100, vh: 50, vw: 50 } }
				onChange={ ( v: LengthValue ) => setAttributes( { gap: v } ) }
			/>

			<TextControl
				__nextHasNoMarginBottom
				label={ __( 'Link URL', 'flexa-block' ) }
				type="url"
				value={ url || '' }
				placeholder="https://"
				onChange={ ( v: string ) => setAttributes( { url: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Open in new tab', 'flexa-block' ) }
				checked={ linkTarget === '_blank' }
				onChange={ ( v: boolean ) =>
					setAttributes( {
						linkTarget: v ? '_blank' : '',
						rel: toggleRel( toggleRel( rel, 'noopener', v ), 'noreferrer', v ),
					} )
				}
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Add nofollow', 'flexa-block' ) }
				checked={ relHas( rel, 'nofollow' ) }
				onChange={ ( v: boolean ) => setAttributes( { rel: toggleRel( rel, 'nofollow', v ) } ) }
			/>

			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show subheading', 'flexa-block' ) }
				checked={ !! showSubheading }
				onChange={ ( v: boolean ) => setAttributes( { showSubheading: v } ) }
			/>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show separator', 'flexa-block' ) }
				checked={ !! showSeparator }
				onChange={ ( v: boolean ) => setAttributes( { showSeparator: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Subheading panel — position, tag, typography and colour. Rendered only when
 * the subheading is enabled.
 */
export const SubheadingPanel = ( { attributes, setAttributes }: HeadingPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { subheadingPosition, subheadingTag, subheadingColor } = attributes;
	const typo = rawDevice( attributes.subheadingTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { subheadingTypography: patchDevice( attributes.subheadingTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Subheading', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Position', 'flexa-block' ) }
				value={ subheadingPosition || 'bottom' }
				onChange={ ( v ) => setAttributes( { subheadingPosition: v as HeadingAttributes[ 'subheadingPosition' ] } ) }
				options={ POSITION_OPTIONS }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ subheadingTag || 'span' }
				options={ TEXT_TAG_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { subheadingTag: v } ) }
			/>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ subheadingColor || {} } onChange={ ( v ) => setAttributes( { subheadingColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Separator panel — position, width, weight, style, colour and spacing.
 * Rendered only when the separator is enabled.
 */
export const SeparatorPanel = ( { attributes, setAttributes }: HeadingPanelProps ): JSX.Element => {
	const { separatorPosition, separatorWidth, separatorWeight, separatorStyle, separatorColor, separatorSpacing } = attributes;

	return (
		<PanelBody title={ __( 'Separator', 'flexa-block' ) } initialOpen={ false }>
			<Segmented
				label={ __( 'Position', 'flexa-block' ) }
				value={ separatorPosition || 'bottom' }
				onChange={ ( v ) => setAttributes( { separatorPosition: v as HeadingAttributes[ 'separatorPosition' ] } ) }
				options={ POSITION_OPTIONS }
			/>
			<SliderUnit
				label={ __( 'Width', 'flexa-block' ) }
				showDevice={ false }
				value={ separatorWidth || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 600, '%': 100, em: 40, rem: 40, vw: 100, vh: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { separatorWidth: v } ) }
			/>
			<SliderUnit
				label={ __( 'Weight', 'flexa-block' ) }
				showDevice={ false }
				value={ separatorWeight || {} }
				units={ WEIGHT_UNITS }
				defaultUnit="px"
				min={ 1 }
				max={ { px: 20 } }
				onChange={ ( v: LengthValue ) => setAttributes( { separatorWeight: v } ) }
			/>
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'Style', 'flexa-block' ) }
				value={ separatorStyle || 'solid' }
				options={ BORDER_STYLE_OPTIONS }
				onChange={ ( v: string ) => setAttributes( { separatorStyle: v } ) }
			/>
			<DualColor label={ __( 'Color', 'flexa-block' ) } value={ separatorColor || {} } onChange={ ( v ) => setAttributes( { separatorColor: v } ) } />
			<SliderUnit
				label={ __( 'Spacing', 'flexa-block' ) }
				showDevice={ false }
				value={ separatorSpacing || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 100, em: 10, rem: 10, '%': 100, vh: 50, vw: 50 } }
				onChange={ ( v: LengthValue ) => setAttributes( { separatorSpacing: v } ) }
			/>
		</PanelBody>
	);
};
