/**
 * Grid block layout variations.
 *
 * Each variation seeds the grid's column tracks and drops in that many empty
 * Container cells, so picking a structure yields a ready-to-fill grid.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import type {
	ContainerAttributes,
	GridAttributes,
	GridLayoutDevice,
	LayoutDevice,
	LengthValue,
	ResponsiveValue,
} from '../../types';

/** A registered grid variation (structure choice). */
export interface GridVariation {
	name: string;
	title: string;
	description: string;
	isDefault?: boolean;
	attributes: Partial< GridAttributes >;
	innerBlocks?: Array< [ string, Partial< ContainerAttributes > ] >;
	scope: string[];
}

/** Flex-column layout for each Container cell dropped into the grid. */
const cellLayout: ResponsiveValue< LayoutDevice > = {
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
 * Build a grid layout with the given column (and optional row) tracks. The grid
 * collapses to a single column on mobile so cells stack on small screens.
 *
 * @param columns Column track definition.
 * @param rows    Optional row track definition (defaults to auto rows).
 * @return Responsive grid layout.
 */
const gridLayout = ( columns: LengthValue, rows: LengthValue = { value: '', unit: 'fr' } ): ResponsiveValue< GridLayoutDevice > => ( {
	desktop: {
		columns,
		rows,
		gap: { column: '20', row: '20', unit: 'px' },
		autoFlow: 'row',
		justifyItems: 'stretch',
		alignItems: 'stretch',
		justifyContent: '',
		alignContent: '',
	},
	tablet: {},
	mobile: { columns: { value: '1', unit: 'fr' } },
} );

/**
 * A nested Container cell — full-width, no own padding, ready for content.
 *
 * @return Cell attributes.
 */
const cell = (): Partial< ContainerAttributes > => ( {
	variationSelected: true,
	containerType: 'full-width',
	layout: cellLayout,
	spacing: {
		desktop: {
			padding: { top: '', right: '', bottom: '', left: '', unit: 'px' },
			margin: { top: '', right: '', bottom: '', left: '', unit: 'px' },
		},
		tablet: {},
		mobile: {},
	},
} );

/**
 * Repeat the Container cell `count` times.
 *
 * @param count Number of cells.
 * @return InnerBlocks tuples.
 */
const cells = ( count: number ): Array< [ string, Partial< ContainerAttributes > ] > =>
	Array.from( { length: count }, () => [ 'flexa/container', cell() ] as [ string, Partial< ContainerAttributes > ] );

const variations: GridVariation[] = [
	{
		name: 'single-column',
		title: __( 'Single Column', 'flexa-block' ),
		description: __( 'One full-width column; inner blocks stack top to bottom.', 'flexa-block' ),
		isDefault: true,
		attributes: { layout: gridLayout( { value: '1', unit: 'fr' } ) },
		scope: [ 'block' ],
	},
	{
		name: 'two-columns',
		title: __( 'Two Columns', 'flexa-block' ),
		description: __( 'Two equal-width columns.', 'flexa-block' ),
		attributes: { layout: gridLayout( { value: '2', unit: 'fr' } ) },
		innerBlocks: cells( 2 ),
		scope: [ 'block' ],
	},
	{
		name: 'three-columns',
		title: __( 'Three Columns', 'flexa-block' ),
		description: __( 'Three equal-width columns.', 'flexa-block' ),
		attributes: { layout: gridLayout( { value: '3', unit: 'fr' } ) },
		innerBlocks: cells( 3 ),
		scope: [ 'block' ],
	},
	{
		name: 'four-columns',
		title: __( 'Four Columns', 'flexa-block' ),
		description: __( 'Four equal-width columns.', 'flexa-block' ),
		attributes: { layout: gridLayout( { value: '4', unit: 'fr' } ) },
		innerBlocks: cells( 4 ),
		scope: [ 'block' ],
	},
	{
		name: 'two-rows',
		title: __( 'Two Rows', 'flexa-block' ),
		description: __( 'Two full-width rows stacked top to bottom.', 'flexa-block' ),
		attributes: { layout: gridLayout( { value: '1', unit: 'fr' }, { value: '2', unit: 'fr' } ) },
		innerBlocks: cells( 2 ),
		scope: [ 'block' ],
	},
	{
		name: 'three-rows',
		title: __( 'Three Rows', 'flexa-block' ),
		description: __( 'Three full-width rows stacked top to bottom.', 'flexa-block' ),
		attributes: { layout: gridLayout( { value: '1', unit: 'fr' }, { value: '3', unit: 'fr' } ) },
		innerBlocks: cells( 3 ),
		scope: [ 'block' ],
	},
	{
		name: 'one-two',
		title: __( 'Two Columns (1:2)', 'flexa-block' ),
		description: __( 'A narrow column beside a column twice as wide.', 'flexa-block' ),
		attributes: { layout: gridLayout( { value: '1fr 2fr', unit: 'custom' } ) },
		innerBlocks: cells( 2 ),
		scope: [ 'block' ],
	},
	{
		name: 'two-one',
		title: __( 'Two Columns (2:1)', 'flexa-block' ),
		description: __( 'A wide column beside a column half as wide.', 'flexa-block' ),
		attributes: { layout: gridLayout( { value: '2fr 1fr', unit: 'custom' } ) },
		innerBlocks: cells( 2 ),
		scope: [ 'block' ],
	},
];

export default variations;
