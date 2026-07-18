/**
 * Grid structure picker — shown for a new, empty top-level grid.
 *
 * Renders the registered grid variations as a row of column-layout glyphs.
 * Picking one seeds the column tracks and inserts that many Container cells.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { useDispatch } from '@wordpress/data';
import { Button } from '@wordpress/components';

import variations, { type GridVariation } from '../variations';
import type { GridAttributes } from '../../../types';

/**
 * Relative track weights for a variation glyph, from a column/row track.
 * `custom` tracks parse each `<n>fr` token; a plain count yields equal weights;
 * an empty track is a single track.
 *
 * @param track Column or row track definition.
 * @return Track weights.
 */
const getWeights = ( track?: { value?: string; unit?: string } ): number[] => {
	const value = String( track?.value ?? '' ).trim();
	if ( '' === value ) {
		return [ 1 ];
	}
	if ( track?.unit === 'custom' ) {
		return value.split( /\s+/ ).map( ( token ) => parseFloat( token ) || 1 );
	}
	const count = parseInt( value, 10 );
	return Array.from( { length: count > 0 ? count : 1 }, () => 1 );
};

/**
 * SVG glyph of the grid structure — a rows × columns matrix of cells.
 */
const Glyph = ( { variation }: { variation: GridVariation } ): JSX.Element => {
	const layout = variation.attributes?.layout?.desktop;
	const colWeights = getWeights( layout?.columns );
	const rowWeights = getWeights( layout?.rows );
	const colTotal = colWeights.reduce( ( a, b ) => a + b, 0 );
	const rowTotal = rowWeights.reduce( ( a, b ) => a + b, 0 );
	const W = 46;
	const H = 30;
	const pad = 3;
	const gap = 3;
	const usableW = W - pad * 2 - gap * ( colWeights.length - 1 );
	const usableH = H - pad * 2 - gap * ( rowWeights.length - 1 );

	const rects: JSX.Element[] = [];
	let y = pad;
	rowWeights.forEach( ( rw, r ) => {
		const height = ( usableH * rw ) / rowTotal;
		let x = pad;
		colWeights.forEach( ( cw, c ) => {
			const width = ( usableW * cw ) / colTotal;
			rects.push( <rect key={ `${ r }-${ c }` } x={ x } y={ y } width={ width } height={ height } rx="1.5" /> );
			x += width + gap;
		} );
		y += height + gap;
	} );

	return (
		<svg width={ W } height={ H } viewBox={ `0 0 ${ W } ${ H }` } fill="currentColor" opacity="0.85">
			{ rects }
		</svg>
	);
};

interface GridStructurePickerProps {
	clientId: string;
	setAttributes: ( attrs: Partial< GridAttributes > ) => void;
}

/**
 * Grid structure picker placeholder.
 */
const GridStructurePicker = ( { clientId, setAttributes }: GridStructurePickerProps ): JSX.Element => {
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	const choose = ( variation: GridVariation ) => {
		setAttributes( { ...( variation.attributes || {} ), variationSelected: true } );

		if ( variation.innerBlocks && variation.innerBlocks.length ) {
			const blocks = variation.innerBlocks.map( ( [ name, attrs ] ) => createBlock( name, attrs ) );
			replaceInnerBlocks( clientId, blocks, false );
		}
	};

	return (
		<div className="flexa-structure">
			<div className="flexa-structure__title">{ __( 'Choose a grid layout', 'flexa-block' ) }</div>
			<div className="flexa-structure__grid">
				{ variations.map( ( variation ) => (
					<Button
						key={ variation.name }
						className="flexa-structure__item"
						onClick={ () => choose( variation ) }
						label={ variation.title }
						showTooltip
					>
						<Glyph variation={ variation } />
					</Button>
				) ) }
			</div>
			<Button className="flexa-structure__skip" variant="link" onClick={ () => setAttributes( { variationSelected: true } ) }>
				{ __( 'Skip', 'flexa-block' ) }
			</Button>
		</div>
	);
};

export default GridStructurePicker;
