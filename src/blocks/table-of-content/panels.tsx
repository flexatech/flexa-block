/**
 * Table of Contents block — TOC-specific inspector panels.
 *
 * The shared panels (spacing / background / border / shadow / position /
 * visibility) plus the shared typography controls come from @components; these
 * cover the parts unique to the TOC: which heading levels to collect, the
 * marker style, the scroll + collapse behaviour, and the title / link / marker
 * styling. Following the "prefer theme styles" rule nothing is styled by
 * default — only values the user picks produce CSS.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl, RangeControl } from '@wordpress/components';

import {
	Segmented,
	SliderUnit,
	DualColor,
	TypographyControls,
	TEXT_TAG_OPTIONS,
	useDevice,
} from '@components';
import {
	rawDevice,
	patchDevice,
	LENGTH_UNITS,
	SPACING_UNITS,
	HEIGHT_UNITS,
	HTML_TAGS,
} from '@utils';
import type { LengthValue, PanelProps, TableOfContentAttributes, TocHeadingsAttr, TypographyDevice } from '../../types';

type TocPanelProps = PanelProps< TableOfContentAttributes >;

/** Marker styles for each entry (few values → Segmented). */
const MARKER_OPTIONS = [
	{ value: 'bullet', label: __( 'Bullet', 'flexa-block' ) },
	{ value: 'number', label: __( 'Number', 'flexa-block' ) },
	{ value: 'none', label: __( 'None', 'flexa-block' ) },
];

/** The six heading levels, in document order. */
const HEADING_LEVELS: Array< keyof TocHeadingsAttr > = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];

/**
 * Structure panel — the title, which heading levels feed the list, and the
 * marker style. This is what shapes the TOC, so it opens first.
 */
export const TocStructurePanel = ( { attributes, setAttributes }: TocPanelProps ): JSX.Element => {
	const { showTitle, titleTag, headings, markerType } = attributes;
	const levels = headings || {};

	const toggleLevel = ( level: keyof TocHeadingsAttr, on: boolean ): void => {
		setAttributes( { headings: { ...levels, [ level ]: on } } );
	};

	return (
		<PanelBody title={ __( 'Structure', 'flexa-block' ) } initialOpen={ true }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show title', 'flexa-block' ) }
				checked={ showTitle !== false }
				onChange={ ( v: boolean ) => setAttributes( { showTitle: v } ) }
			/>
			{ showTitle !== false && (
				<SelectControl
					__nextHasNoMarginBottom
					label={ __( 'Title tag', 'flexa-block' ) }
					value={ titleTag || 'h2' }
					options={ TEXT_TAG_OPTIONS }
					onChange={ ( v: string ) => setAttributes( { titleTag: v } ) }
				/>
			) }
			<p className="flexa-toc-levels-label">{ __( 'Heading levels', 'flexa-block' ) }</p>
			{ HEADING_LEVELS.map( ( level ) => (
				<ToggleControl
					key={ level }
					__nextHasNoMarginBottom
					label={ level.toUpperCase() }
					checked={ !! levels[ level ] }
					onChange={ ( v: boolean ) => toggleLevel( level, v ) }
				/>
			) ) }
			<Segmented
				label={ __( 'Marker', 'flexa-block' ) }
				value={ markerType || 'bullet' }
				onChange={ ( v ) => setAttributes( { markerType: v as TableOfContentAttributes[ 'markerType' ] } ) }
				options={ MARKER_OPTIONS }
			/>
		</PanelBody>
	);
};

/**
 * Layout panel — max width, the gap between entries and the per-level indent.
 */
export const TocLayoutPanel = ( { attributes, setAttributes }: TocPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const { maxWidth, listMaxHeight, itemGap, indent } = attributes;

	return (
		<PanelBody title={ __( 'Layout', 'flexa-block' ) } initialOpen={ false }>
			<SliderUnit
				label={ __( 'Max width', 'flexa-block' ) }
				value={ maxWidth?.[ device ] || {} }
				units={ LENGTH_UNITS }
				defaultUnit="px"
				max={ { px: 1200, '%': 100, vw: 100, rem: 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { maxWidth: { ...maxWidth, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'List max height', 'flexa-block' ) }
				value={ listMaxHeight?.[ device ] || {} }
				units={ HEIGHT_UNITS }
				defaultUnit="px"
				max={ { px: 1000, vh: 100, rem: 100, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { listMaxHeight: { ...listMaxHeight, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Item gap', 'flexa-block' ) }
				value={ itemGap?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 80, em: 8, rem: 8, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { itemGap: { ...itemGap, [ device ]: v } } ) }
			/>
			<SliderUnit
				label={ __( 'Sub-level indent', 'flexa-block' ) }
				value={ indent?.[ device ] || {} }
				units={ SPACING_UNITS }
				defaultUnit="px"
				max={ { px: 120, em: 12, rem: 12, '%': 100 } }
				onChange={ ( v: LengthValue ) => setAttributes( { indent: { ...indent, [ device ]: v } } ) }
			/>
		</PanelBody>
	);
};

/**
 * Behaviour panel — smooth scroll (+ offset for sticky headers), the collapse
 * toggle and the wrapper tag. These feed view.ts; they emit no CSS.
 */
export const TocBehaviorPanel = ( { attributes, setAttributes }: TocPanelProps ): JSX.Element => {
	const { smoothScroll, scrollOffset, collapsible, initialCollapsed, htmlTag } = attributes;

	return (
		<PanelBody title={ __( 'Behaviour', 'flexa-block' ) } initialOpen={ false }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Smooth scroll', 'flexa-block' ) }
				checked={ smoothScroll !== false }
				onChange={ ( v: boolean ) => setAttributes( { smoothScroll: v } ) }
			/>
			{ smoothScroll !== false && (
				<RangeControl
					__nextHasNoMarginBottom
					label={ __( 'Scroll offset (px)', 'flexa-block' ) }
					help={ __( 'Space to leave for a sticky header when jumping to a heading.', 'flexa-block' ) }
					value={ scrollOffset ?? 0 }
					min={ 0 }
					max={ 300 }
					onChange={ ( v?: number ) => setAttributes( { scrollOffset: v ?? 0 } ) }
				/>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Collapsible', 'flexa-block' ) }
				help={ __( 'Add a toggle to show or hide the list.', 'flexa-block' ) }
				checked={ !! collapsible }
				onChange={ ( v: boolean ) => setAttributes( { collapsible: v } ) }
			/>
			{ collapsible && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Start collapsed', 'flexa-block' ) }
					checked={ !! initialCollapsed }
					onChange={ ( v: boolean ) => setAttributes( { initialCollapsed: v } ) }
				/>
			) }
			<SelectControl
				__nextHasNoMarginBottom
				label={ __( 'HTML Tag', 'flexa-block' ) }
				value={ htmlTag || 'nav' }
				options={ HTML_TAGS.map( ( t ) => ( { label: t, value: t } ) ) }
				onChange={ ( v: string ) => setAttributes( { htmlTag: v } ) }
			/>
		</PanelBody>
	);
};

/**
 * Title panel — typography + colour of the TOC heading.
 */
export const TocTitlePanel = ( { attributes, setAttributes }: TocPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.titleTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { titleTypography: patchDevice( attributes.titleTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Title', 'flexa-block' ) } initialOpen={ true }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Title colour', 'flexa-block' ) } value={ attributes.titleColor || {} } onChange={ ( v ) => setAttributes( { titleColor: v } ) } />
		</PanelBody>
	);
};

/**
 * Links panel — typography + colours of the entry links and their markers.
 */
export const TocLinksPanel = ( { attributes, setAttributes }: TocPanelProps ): JSX.Element => {
	const [ device ] = useDevice();
	const typo = rawDevice( attributes.linkTypography, device );
	const setTypo = ( patch: Partial< TypographyDevice > ) => setAttributes( { linkTypography: patchDevice( attributes.linkTypography, device, patch ) } );

	return (
		<PanelBody title={ __( 'Links', 'flexa-block' ) } initialOpen={ false }>
			<TypographyControls value={ typo } onChange={ setTypo } />
			<DualColor label={ __( 'Link colour', 'flexa-block' ) } value={ attributes.linkColor || {} } onChange={ ( v ) => setAttributes( { linkColor: v } ) } />
			<DualColor label={ __( 'Link colour (hover)', 'flexa-block' ) } value={ attributes.linkHoverColor || {} } onChange={ ( v ) => setAttributes( { linkHoverColor: v } ) } />
			<DualColor label={ __( 'Marker colour', 'flexa-block' ) } value={ attributes.markerColor || {} } onChange={ ( v ) => setAttributes( { markerColor: v } ) } />
		</PanelBody>
	);
};
