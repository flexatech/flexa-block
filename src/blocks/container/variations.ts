/**
 * Container block layout variations.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import type {
	ContainerAttributes,
	LayoutDevice,
	ResponsiveValue,
	SpacingDevice,
} from '../../types';

/** A registered block variation (structure choice). */
export interface BlockVariation {
	name: string;
	title: string;
	description: string;
	isDefault?: boolean;
	attributes: Partial< ContainerAttributes >;
	innerBlocks?: Array< [ string, Partial< ContainerAttributes > ] >;
	scope: string[];
}

const parentSpacing: ResponsiveValue< SpacingDevice > = {
	desktop: {
		padding: { top: '20', right: '20', bottom: '20', left: '20', unit: 'px' },
		margin: { top: '', right: 'auto', bottom: '', left: 'auto', unit: 'px' },
	},
	tablet: {},
	mobile: {},
};

const rowLayout: ResponsiveValue< LayoutDevice > = {
	desktop: {
		display: 'flex',
		direction: 'row',
		justifyContent: 'flex-start',
		alignItems: 'stretch',
		wrap: 'nowrap',
		gap: { column: '20', row: '20', unit: 'px' },
	},
	tablet: {},
	mobile: { direction: 'column' },
};

const columnLayout: ResponsiveValue< LayoutDevice > = {
	desktop: {
		display: 'flex',
		direction: 'column',
		justifyContent: 'flex-start',
		alignItems: 'stretch',
		wrap: 'nowrap',
		gap: { column: '20', row: '20', unit: 'px' },
	},
	tablet: {},
	mobile: {},
};

/**
 * Build child container attributes (nested, full-width with % width).
 *
 * @param width Desktop width percentage.
 * @return Attributes.
 */
const child = ( width: string ): Partial< ContainerAttributes > => ( {
	variationSelected: true,
	containerType: 'full-width',
	layout: columnLayout,
	spacing: {
		desktop: {
			padding: { top: '', right: '', bottom: '', left: '', unit: 'px' },
			margin: { top: '', right: '', bottom: '', left: '', unit: 'px' },
		},
		tablet: {},
		mobile: {},
	},
	widthFullWidth: {
		desktop: { value: width, unit: '%' },
		tablet: {},
		mobile: { value: '100', unit: '%' },
	},
} );

const variations: BlockVariation[] = [
	{
		name: 'single-column',
		title: __( 'Single Column', 'flexa-block' ),
		description: __( 'One column; inner blocks stack top to bottom.', 'flexa-block' ),
		isDefault: true,
		attributes: { layout: columnLayout, spacing: parentSpacing },
		scope: [ 'block' ],
	},
	{
		name: 'two-columns',
		title: __( 'Two Columns (50/50)', 'flexa-block' ),
		description: __( 'Two side-by-side columns of equal width.', 'flexa-block' ),
		attributes: { layout: rowLayout, spacing: parentSpacing },
		innerBlocks: [
			[ 'flexa/container', child( '50' ) ],
			[ 'flexa/container', child( '50' ) ],
		],
		scope: [ 'block' ],
	},
	{
		name: 'three-columns',
		title: __( 'Three Columns (33/33/33)', 'flexa-block' ),
		description: __( 'Three side-by-side columns of equal width.', 'flexa-block' ),
		attributes: { layout: rowLayout, spacing: parentSpacing },
		innerBlocks: [
			[ 'flexa/container', child( '33.33' ) ],
			[ 'flexa/container', child( '33.33' ) ],
			[ 'flexa/container', child( '33.33' ) ],
		],
		scope: [ 'block' ],
	},
	{
		name: 'sidebar-left',
		title: __( 'Two Columns (25/75)', 'flexa-block' ),
		description: __( 'Slim left column beside a wide right column.', 'flexa-block' ),
		attributes: { layout: rowLayout, spacing: parentSpacing },
		innerBlocks: [
			[ 'flexa/container', child( '25' ) ],
			[ 'flexa/container', child( '75' ) ],
		],
		scope: [ 'block' ],
	},
	{
		name: 'sidebar-right',
		title: __( 'Two Columns (75/25)', 'flexa-block' ),
		description: __( 'Wide left column beside a slim right column.', 'flexa-block' ),
		attributes: { layout: rowLayout, spacing: parentSpacing },
		innerBlocks: [
			[ 'flexa/container', child( '75' ) ],
			[ 'flexa/container', child( '25' ) ],
		],
		scope: [ 'block' ],
	},
	{
		name: 'two-left-narrow',
		title: __( 'Two Columns (33/67)', 'flexa-block' ),
		description: __( 'One-third left, two-thirds right.', 'flexa-block' ),
		attributes: { layout: rowLayout, spacing: parentSpacing },
		innerBlocks: [
			[ 'flexa/container', child( '33.33' ) ],
			[ 'flexa/container', child( '66.67' ) ],
		],
		scope: [ 'block' ],
	},
	{
		name: 'two-right-narrow',
		title: __( 'Two Columns (67/33)', 'flexa-block' ),
		description: __( 'Two-thirds left, one-third right.', 'flexa-block' ),
		attributes: { layout: rowLayout, spacing: parentSpacing },
		innerBlocks: [
			[ 'flexa/container', child( '66.67' ) ],
			[ 'flexa/container', child( '33.33' ) ],
		],
		scope: [ 'block' ],
	},
	{
		name: 'three-center-wide',
		title: __( 'Three Columns (25/50/25)', 'flexa-block' ),
		description: __( 'Emphasised center column between two slim sides.', 'flexa-block' ),
		attributes: { layout: rowLayout, spacing: parentSpacing },
		innerBlocks: [
			[ 'flexa/container', child( '25' ) ],
			[ 'flexa/container', child( '50' ) ],
			[ 'flexa/container', child( '25' ) ],
		],
		scope: [ 'block' ],
	},
	{
		name: 'four-columns',
		title: __( 'Four Columns (25/25/25/25)', 'flexa-block' ),
		description: __( 'Four side-by-side columns of equal width.', 'flexa-block' ),
		attributes: { layout: rowLayout, spacing: parentSpacing },
		innerBlocks: [
			[ 'flexa/container', child( '25' ) ],
			[ 'flexa/container', child( '25' ) ],
			[ 'flexa/container', child( '25' ) ],
			[ 'flexa/container', child( '25' ) ],
		],
		scope: [ 'block' ],
	},
];

export default variations;
