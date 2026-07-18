/**
 * Container structure picker — shown for a new top-level container.
 *
 * Renders the registered block variations as a grid of structure choices.
 * Picking one applies the variation attributes and inserts its child containers.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import { useDispatch } from '@wordpress/data';
import { Button } from '@wordpress/components';

import variations, { type BlockVariation } from '../variations';
import type { ContainerAttributes } from '../../../types';

/**
 * Compute relative column/row weights for a variation glyph.
 *
 * @param variation Variation definition.
 * @return Weights (empty for arrow glyphs).
 */
const getWeights = ( variation: BlockVariation ): number[] => {
	if ( ! variation.innerBlocks || ! variation.innerBlocks.length ) {
		return [];
	}
	return variation.innerBlocks.map( ( ib ) => parseFloat( String( ib[ 1 ]?.widthFullWidth?.desktop?.value ?? '' ) ) || 1 );
};

/**
 * SVG glyph representing a structure.
 */
const Glyph = ( { variation }: { variation: BlockVariation } ): JSX.Element => {
	const direction = variation.attributes?.layout?.desktop?.direction || 'column';
	const weights = getWeights( variation );
	const W = 46;
	const H = 30;
	const pad = 3;
	const gap = 3;

	// No children: show a direction arrow.
	if ( ! weights.length ) {
		const isRow = direction === 'row';
		return (
			<svg width={ W } height={ H } viewBox={ `0 0 ${ W } ${ H }` } fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round">
				<rect x={ pad } y={ pad } width={ W - pad * 2 } height={ H - pad * 2 } rx="2" />
				{ isRow ? (
					<>
						<line x1="16" y1="15" x2="30" y2="15" />
						<path d="M26 11 L30 15 L26 19" />
					</>
				) : (
					<>
						<line x1="23" y1="9" x2="23" y2="21" />
						<path d="M19 17 L23 21 L27 17" />
					</>
				) }
			</svg>
		);
	}

	const total = weights.reduce( ( a, b ) => a + b, 0 );
	const isRow = direction === 'row';
	const rects: JSX.Element[] = [];

	if ( isRow ) {
		const usable = W - pad * 2 - gap * ( weights.length - 1 );
		let x = pad;
		weights.forEach( ( w, i ) => {
			const width = ( usable * w ) / total;
			rects.push( <rect key={ i } x={ x } y={ pad } width={ width } height={ H - pad * 2 } rx="1.5" /> );
			x += width + gap;
		} );
	} else {
		const usable = H - pad * 2 - gap * ( weights.length - 1 );
		let y = pad;
		weights.forEach( ( w, i ) => {
			const height = usable / weights.length;
			rects.push( <rect key={ i } x={ pad } y={ y } width={ W - pad * 2 } height={ height } rx="1.5" /> );
			y += height + gap;
		} );
	}

	return (
		<svg width={ W } height={ H } viewBox={ `0 0 ${ W } ${ H }` } fill="currentColor" opacity="0.85">
			{ rects }
		</svg>
	);
};

interface StructurePickerProps {
	clientId: string;
	setAttributes: ( attrs: Partial< ContainerAttributes > ) => void;
}

/**
 * Structure picker placeholder.
 */
const StructurePicker = ( { clientId, setAttributes }: StructurePickerProps ): JSX.Element => {
	const { replaceInnerBlocks } = useDispatch( 'core/block-editor' );

	const choose = ( variation: BlockVariation ) => {
		setAttributes( { ...( variation.attributes || {} ), variationSelected: true } );

		if ( variation.innerBlocks && variation.innerBlocks.length ) {
			const blocks = variation.innerBlocks.map( ( [ name, attrs ] ) => createBlock( name, attrs ) );
			replaceInnerBlocks( clientId, blocks, false );
		}
	};

	return (
		<div className="flexa-structure">
			<div className="flexa-structure__title">{ __( 'Choose a starting layout', 'flexa-block' ) }</div>
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

export default StructurePicker;
