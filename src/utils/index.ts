/**
 * Shared utilities: responsive cascade, value formatting, control option sets.
 *
 * @package Flexa\Block
 */

import {
	RowIcon,
	ColumnIcon,
	VStartIcon,
	VCenterIcon,
	VEndIcon,
	VBetweenIcon,
	VAroundIcon,
	HStartIcon,
	HCenterIcon,
	HEndIcon,
	HBetweenIcon,
	HAroundIcon,
} from './icons';
import type {
	BackgroundAttr,
	BorderDevice,
	BoxShadowAttr,
	BoxValue,
	ColorPair,
	ControlOption,
	DeviceKey,
	RadiusValue,
	ResponsiveValue,
	ResponsiveVisibilityAttr,
	TextShadowAttr,
	TextStrokeAttr,
	TypographyDevice,
} from '../types';

export * from './icons';
export * from './social-platforms';
export type * from '../types';

const SIDES = [ 'top', 'right', 'bottom', 'left' ] as const;
const CORNERS = [ 'topLeft', 'topRight', 'bottomRight', 'bottomLeft' ] as const;

/**
 * Effective (cascaded) device object: desktop <- tablet <- mobile.
 *
 * @param group  Responsive group attribute.
 * @param device Active device key.
 * @return Merged values for the device.
 */
export const effective = < T >(
	group: ResponsiveValue< T > | undefined = {},
	device: DeviceKey = 'desktop'
): Partial< T > => {
	const d = ( group?.desktop || {} ) as Partial< T >;
	const t = ( group?.tablet || {} ) as Partial< T >;
	const m = ( group?.mobile || {} ) as Partial< T >;
	if ( device === 'tablet' ) {
		return { ...d, ...t };
	}
	if ( device === 'mobile' ) {
		return { ...d, ...t, ...m };
	}
	return { ...d };
};

/**
 * Raw value for the active device (no cascade) — empty means "inherit".
 *
 * @param group  Responsive group.
 * @param device Device key.
 * @return Raw device object.
 */
export const rawDevice = < T >(
	group: ResponsiveValue< T > | undefined = {},
	device: DeviceKey = 'desktop'
): Partial< T > => ( group?.[ device ] || {} ) as Partial< T >;

/**
 * Merge a patch into a responsive group's active device and return the new group.
 *
 * @param group  Current group attribute.
 * @param device Device key.
 * @param patch  Partial values to merge.
 * @return New group.
 */
export const patchDevice = < T >(
	group: ResponsiveValue< T > | undefined = {},
	device: DeviceKey = 'desktop',
	patch: Partial< T > = {} as Partial< T >
): ResponsiveValue< T > => ( {
	...group,
	[ device ]: { ...( group?.[ device ] || {} ), ...patch },
} );

/**
 * Append a unit unless value is empty / auto / none / already has one.
 *
 * @param value Value.
 * @param unit  Unit.
 * @return Formatted value.
 */
export const withUnit = ( value: string | number | null | undefined, unit = 'px' ): string => {
	const v = String( value ?? '' );
	if ( v === '' ) {
		return '';
	}
	if ( v === 'auto' || v === 'none' || /[a-z%)]$/i.test( v ) ) {
		return v;
	}
	return `${ v }${ unit }`;
};

/**
 * Split a CSS length string into its numeric value and unit.
 *
 * @param str          Raw value, e.g. "10px".
 * @param fallbackUnit Unit to use when none is present.
 * @return Parsed value + unit.
 */
export const parseUnit = (
	str: string | number | null | undefined,
	fallbackUnit = 'px'
): { value: string; unit: string } => {
	const match = String( str ?? '' ).match( /^([\d.\-]+)([a-z%]*)$/i );
	if ( ! match ) {
		return { value: '', unit: fallbackUnit };
	}
	return { value: match[ 1 ], unit: match[ 2 ] || fallbackUnit };
};

/**
 * Build a 4-side shorthand from a box object, or undefined when empty.
 *
 * @param box Box with top/right/bottom/left/unit.
 * @return Shorthand.
 */
export const spacingShorthand = ( box: BoxValue = {} ): string | undefined => {
	const unit = box.unit || 'px';
	let hasAny = false;
	const values = SIDES.map( ( side ) => {
		const raw = box[ side ] ?? '';
		if ( String( raw ) !== '' ) {
			hasAny = true;
		}
		return raw === '' ? '0' : withUnit( raw, unit );
	} );
	return hasAny ? values.join( ' ' ) : undefined;
};

/**
 * Build a border-radius shorthand from a corner object, or undefined when empty.
 *
 * @param radius Corner object.
 * @return Shorthand.
 */
export const radiusShorthand = ( radius: RadiusValue = {} ): string | undefined => {
	const unit = radius.unit || 'px';
	let hasAny = false;
	const values = CORNERS.map( ( c ) => {
		const raw = radius[ c ] ?? '';
		if ( String( raw ) !== '' ) {
			hasAny = true;
		}
		return raw === '' ? '0' : withUnit( raw, unit );
	} );
	return hasAny ? values.join( ' ' ) : undefined;
};

/* ----------------------------------------------------------------------------
 * Control option sets.
 * ------------------------------------------------------------------------- */

export const DIRECTION_OPTIONS: ControlOption[] = [
	{ value: 'row', label: 'Row', icon: RowIcon },
	{ value: 'column', label: 'Column', icon: ColumnIcon },
];

/** Justify icons for a COLUMN container (main axis vertical → V bars). */
export const JUSTIFY_OPTIONS_COLUMN: ControlOption[] = [
	{ value: 'flex-start', label: 'Start', icon: VStartIcon },
	{ value: 'center', label: 'Center', icon: VCenterIcon },
	{ value: 'flex-end', label: 'End', icon: VEndIcon },
	{ value: 'space-between', label: 'Space between', icon: VBetweenIcon },
	{ value: 'space-around', label: 'Space around', icon: VAroundIcon },
];

/** Justify icons for a ROW container (main axis horizontal → H bars). */
export const JUSTIFY_OPTIONS_ROW: ControlOption[] = [
	{ value: 'flex-start', label: 'Start', icon: HStartIcon },
	{ value: 'center', label: 'Center', icon: HCenterIcon },
	{ value: 'flex-end', label: 'End', icon: HEndIcon },
	{ value: 'space-between', label: 'Space between', icon: HBetweenIcon },
	{ value: 'space-around', label: 'Space around', icon: HAroundIcon },
];

/** Default (column) — kept for backward compatibility. */
export const JUSTIFY_OPTIONS = JUSTIFY_OPTIONS_COLUMN;

/** Align icons for a COLUMN container (cross axis horizontal → H bars). */
export const ALIGN_OPTIONS_COLUMN: ControlOption[] = [
	{ value: 'flex-start', label: 'Start', icon: HStartIcon },
	{ value: 'center', label: 'Center', icon: HCenterIcon },
	{ value: 'flex-end', label: 'End', icon: HEndIcon },
	{ value: 'stretch', label: 'Stretch', icon: HAroundIcon },
];

/** Align icons for a ROW container (cross axis vertical → V bars). */
export const ALIGN_OPTIONS_ROW: ControlOption[] = [
	{ value: 'flex-start', label: 'Start', icon: VStartIcon },
	{ value: 'center', label: 'Center', icon: VCenterIcon },
	{ value: 'flex-end', label: 'End', icon: VEndIcon },
	{ value: 'stretch', label: 'Stretch', icon: VAroundIcon },
];

/** Default (column) — kept for backward compatibility. */
export const ALIGN_OPTIONS = ALIGN_OPTIONS_COLUMN;

export const WRAP_OPTIONS: ControlOption[] = [
	{ value: 'nowrap', label: 'No wrap' },
	{ value: 'wrap', label: 'Wrap' },
];

export const HTML_TAGS = [ 'div', 'section', 'article', 'aside', 'header', 'footer', 'main', 'nav' ];

/** The full unit set, shared by every length control. */
export const LENGTH_UNITS = [
	{ value: 'px', label: 'px' },
	{ value: '%', label: '%' },
	{ value: 'em', label: 'em' },
	{ value: 'rem', label: 'rem' },
	{ value: 'vh', label: 'vh' },
	{ value: 'vw', label: 'vw' },
];

export const SPACING_UNITS = LENGTH_UNITS;
export const WIDTH_UNITS = LENGTH_UNITS;
export const HEIGHT_UNITS = LENGTH_UNITS;

/** A px-only unit list — for hairline widths (border/line thickness) that don't
 *  scale in %/em. Shared so blocks don't re-inline `[ { value: 'px' } ]`. */
export const WEIGHT_UNITS = [ { value: 'px', label: 'px' } ];

export const OVERFLOW_OPTIONS = [
	{ value: '', label: 'Default' },
	{ value: 'visible', label: 'Visible' },
	{ value: 'hidden', label: 'Hidden' },
	{ value: 'auto', label: 'Auto' },
	{ value: 'scroll', label: 'Scroll' },
];

export const POSITION_OPTIONS = [
	{ value: '', label: 'Default' },
	{ value: 'relative', label: 'Relative' },
	{ value: 'absolute', label: 'Absolute' },
	{ value: 'fixed', label: 'Fixed' },
	{ value: 'sticky', label: 'Sticky' },
	{ value: 'static', label: 'Static' },
];

export const BORDER_STYLE_OPTIONS = [
	{ value: '', label: 'None' },
	{ value: 'solid', label: 'Solid' },
	{ value: 'dashed', label: 'Dashed' },
	{ value: 'dotted', label: 'Dotted' },
	{ value: 'double', label: 'Double' },
];

/** Line styles = the border styles minus the empty "None" entry (a line always
 *  has a style). Shared so blocks don't re-derive the same `.filter()`. */
export const LINE_STYLE_OPTIONS = BORDER_STYLE_OPTIONS.filter( ( o ) => o.value !== '' );

/** Map a content-alignment keyword (left/center/right) to its flex main/cross
 *  axis value. Shared by every block that previews content alignment with flex
 *  in edit.tsx, so the identical map isn't copied per block. Blocks that also
 *  support `justify` spread this and add their own justify target. */
export const CONTENT_ALIGN_TO_FLEX: Record< string, string > = {
	left: 'flex-start',
	center: 'center',
	right: 'flex-end',
};

export const DEFAULT_PALETTE = [
	{ name: 'Black', color: '#111827' },
	{ name: 'Gray 700', color: '#374151' },
	{ name: 'Gray 400', color: '#9ca3af' },
	{ name: 'Gray 100', color: '#f3f4f6' },
	{ name: 'White', color: '#ffffff' },
	{ name: 'Blue', color: '#2563eb' },
	{ name: 'Indigo', color: '#4f46e5' },
	{ name: 'Green', color: '#16a34a' },
	{ name: 'Amber', color: '#f59e0b' },
	{ name: 'Red', color: '#dc2626' },
];

/* ----------------------------------------------------------------------------
 * Editor preview-style builders — shared by every block's edit.tsx.
 *
 * Each helper mirrors its PHP counterpart (applyTypography ↔
 * CSS_Helpers::add_typography, applyTextFill/applyBgFill ↔ add_text_fill/
 * add_bg_fill, applyBorderPreview ↔ add_border, applyBackgroundPreview ↔
 * add_background, boxShadowPreview ↔ box_shadow) so the editor preview and the
 * front-end CSS stay in sync by construction. The editor previews LIGHT values
 * only — the dark branch is a front-end concern.
 * ------------------------------------------------------------------------- */

/** Inline-style object used by the preview builders. */
export type CssProps = Record< string, string >;

/** Join truthy class names. */
export const cn = ( ...parts: Array< string | false | null | undefined > ): string =>
	parts.filter( Boolean ).join( ' ' );

/** The wrapper's responsive-visibility helper classes (flexa-hide-*). */
export const visibilityClasses = ( visibility: ResponsiveVisibilityAttr = {} ): string[] =>
	[
		visibility.hideOnDesktop ? 'flexa-hide-desktop' : '',
		visibility.hideOnTablet ? 'flexa-hide-tablet' : '',
		visibility.hideOnMobile ? 'flexa-hide-mobile' : '',
	].filter( Boolean );

/** Apply one device's typography onto a preview style object. */
export const applyTypography = ( s: CssProps, typo: Partial< TypographyDevice > = {} ): void => {
	if ( typo.fontSize?.value ) s.fontSize = withUnit( typo.fontSize.value, typo.fontSize.unit || 'px' );
	if ( typo.fontWeight ) s.fontWeight = typo.fontWeight;
	if ( typo.letterSpacing?.value ) s.letterSpacing = withUnit( typo.letterSpacing.value, typo.letterSpacing.unit || 'px' );
	if ( typo.textTransform ) s.textTransform = typo.textTransform;
	if ( typo.lineHeight ) s.lineHeight = typo.lineHeight;
};

/** Apply a text fill (solid colour, or a gradient painted through the text). */
export const applyTextFill = ( s: CssProps, type?: string, color?: ColorPair, gradient?: ColorPair ): void => {
	if ( type === 'gradient' ) {
		if ( gradient?.light ) {
			s.backgroundImage = gradient.light;
			s.WebkitBackgroundClip = 'text';
			s.backgroundClip = 'text';
			s.WebkitTextFillColor = 'transparent';
			s.color = 'transparent';
		}
	} else if ( color?.light ) {
		s.color = color.light;
	}
};

/** Apply a background fill (solid colour, or a gradient background-image). */
export const applyBgFill = ( s: CssProps, type?: string, color?: ColorPair, gradient?: ColorPair ): void => {
	if ( type === 'gradient' ) {
		if ( gradient?.light ) s.background = gradient.light;
	} else if ( color?.light ) {
		s.background = color.light;
	}
};

/** Apply the shared Border device (style / width / colour / radius). */
export const applyBorderPreview = ( s: CssProps, border: Partial< BorderDevice > = {} ): void => {
	if ( border.style ) s.borderStyle = border.style;
	const width = spacingShorthand( border.width );
	if ( width ) s.borderWidth = width;
	if ( border.color?.light ) s.borderColor = border.color.light;
	const radius = radiusShorthand( border.radius );
	if ( radius ) s.borderRadius = radius;
};

/** Apply the shared Background attribute (classic colour / gradient / image). */
export const applyBackgroundPreview = ( s: CssProps, background: BackgroundAttr = {} ): void => {
	if ( background.type === 'classic' && background.color?.light ) {
		s.backgroundColor = background.color.light;
	} else if ( background.type === 'gradient' && background.gradient?.light ) {
		s.backgroundImage = background.gradient.light;
	} else if ( background.type === 'image' && background.image?.url ) {
		s.backgroundImage = `url(${ background.image.url })`;
		s.backgroundSize = background.image.size || 'cover';
		s.backgroundPosition = background.image.position || 'center center';
		s.backgroundRepeat = background.image.repeat || 'no-repeat';
	}
};

/** Apply a text stroke (width + colour) — mirror of CSS_Helpers::add_text_stroke. */
export const applyTextStroke = ( s: CssProps, stroke: TextStrokeAttr = {} ): void => {
	if ( ! stroke.enabled || ! stroke.width?.value ) {
		return;
	}
	const width = withUnit( stroke.width.value, stroke.width.unit || 'px' );
	const colour = stroke.color?.light || 'currentColor';
	s.WebkitTextStroke = `${ width } ${ colour }`;
};

/** Apply a text shadow (offset + blur + colour) — mirror of CSS_Helpers::text_shadow. */
export const applyTextShadow = ( s: CssProps, shadow: TextShadowAttr = {} ): void => {
	if ( ! shadow.enabled ) {
		return;
	}
	const colour = shadow.color?.light || 'rgba(0,0,0,0.3)';
	s.textShadow = `${ withUnit( shadow.horizontal ?? '0' ) } ${ withUnit( shadow.vertical ?? '0' ) } ${ withUnit( shadow.blur ?? '0' ) } ${ colour }`;
};

/** Build the shared Box Shadow attribute's value, or '' when disabled. */
export const boxShadowPreview = ( shadow: BoxShadowAttr = {} ): string => {
	if ( ! shadow.enabled ) {
		return '';
	}
	const color = shadow.color?.light || 'rgba(0,0,0,0.1)';
	return `${ shadow.inset ? 'inset ' : '' }${ withUnit( shadow.horizontal ) } ${ withUnit( shadow.vertical ) } ${ withUnit( shadow.blur ) } ${ withUnit( shadow.spread ) } ${ color }`;
};

/** One editor CSS rule: a full selector, a property and its resolved value. */
export interface EditorCssRule {
	selector: string;
	prop: string;
	value?: string;
}

/**
 * Build a CSS string from editor rules, dropping any rule whose value is empty.
 *
 * Inline React styles can't express pseudo-classes like `:hover`, so blocks that
 * style a hover (or active / focus) state on the front end must mirror those rules
 * in a scoped `<style>` for the editor to match. Each `selector` is a FULL selector
 * (scope it with the block's `.flexa-<block>-<blockId>` class), so the same helper
 * handles descendant, self and multi-declaration rules. Use the light values (the
 * editor previews light mode). Returns '' when nothing is set, so callers render
 * `{ css && <style>{ css }</style> }`.
 *
 * Each declaration is emitted `!important`: the editor renders the BASE colour /
 * background as an inline style (highest normal priority), so a plain stylesheet
 * `:hover` rule would lose to it and never show. `!important` lets the hover state
 * win in the editor. The front end doesn't use this (its base styles aren't inline),
 * so it stays `!important`-free and wins by specificity there.
 *
 * @param rules Editor CSS rules (empty-value rules are skipped).
 * @return A CSS string, or ''.
 */
export const editorCss = ( rules: EditorCssRule[] ): string =>
	rules
		.filter( ( r ) => r.value && '' !== r.value )
		.map( ( r ) => `${ r.selector }{${ r.prop }:${ r.value } !important}` )
		.join( '' );
