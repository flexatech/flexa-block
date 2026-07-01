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
	BoxValue,
	ControlOption,
	DeviceKey,
	RadiusValue,
	ResponsiveValue,
} from '../types';

export * from './icons';
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

export const OVERFLOW_OPTIONS = [
	{ value: '', label: 'Default' },
	{ value: 'visible', label: 'Visible' },
	{ value: 'hidden', label: 'Hidden' },
	{ value: 'auto', label: 'Auto' },
	{ value: 'scroll', label: 'Scroll' },
];

export const BORDER_STYLE_OPTIONS = [
	{ value: '', label: 'None' },
	{ value: 'solid', label: 'Solid' },
	{ value: 'dashed', label: 'Dashed' },
	{ value: 'dotted', label: 'Dotted' },
	{ value: 'double', label: 'Double' },
];

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
