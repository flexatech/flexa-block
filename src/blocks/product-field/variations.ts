/**
 * Product Field block variations.
 *
 * One block, three inserter entries. The block is a single line of product data
 * and `field` decides which; these presets are what "Product SKU", "Product
 * Categories" and "Product Tags" ever were, so the linking, escaping, separator
 * and badge rules have exactly one implementation.
 *
 * The generic block itself is not offered in the inserter — nobody looks for
 * "Product Field", they look for a SKU — so every entry here is a preset.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';

import { BLOCK_ICONS } from '@shared/block-icons';
import type { ProductFieldAttributes } from '../../types';

/** A registered block variation (inserter entry + preset). */
export interface FieldVariation {
	name: string;
	title: string;
	description: string;
	/** Extra words the inserter search should match — a user hunting "product
	 *  code" must land on the SKU entry without knowing the word "SKU". */
	keywords?: string[];
	icon?: unknown;
	isDefault?: boolean;
	attributes: Partial< ProductFieldAttributes >;
	isActive: ( attrs: Partial< ProductFieldAttributes > ) => boolean;
	scope: string[];
}

const variations: FieldVariation[] = [
	{
		name: 'product-sku',
		title: __( 'Product SKU', 'flexa-block' ),
		description: __( 'The product’s SKU, with an optional copy button.', 'flexa-block' ),
		keywords: [ __( 'sku', 'flexa-block' ), __( 'code', 'flexa-block' ), __( 'product code', 'flexa-block' ), __( 'reference', 'flexa-block' ), __( 'copy', 'flexa-block' ) ],
		icon: BLOCK_ICONS[ 'product-sku' ],
		isDefault: true,
		scope: [ 'inserter', 'transform' ],
		attributes: { field: 'sku', label: 'SKU:' },
		isActive: ( attrs ) => 'sku' === ( attrs.field || 'sku' ),
	},
	{
		name: 'product-categories',
		title: __( 'Product Categories', 'flexa-block' ),
		description: __( 'The product’s categories, each linking to its archive.', 'flexa-block' ),
		keywords: [ __( 'categories', 'flexa-block' ), __( 'category', 'flexa-block' ), __( 'taxonomy', 'flexa-block' ), __( 'terms', 'flexa-block' ) ],
		icon: BLOCK_ICONS[ 'product-categories' ],
		scope: [ 'inserter', 'transform' ],
		attributes: { field: 'categories', label: 'Categories:' },
		isActive: ( attrs ) => 'categories' === attrs.field,
	},
	{
		name: 'product-tags',
		title: __( 'Product Tags', 'flexa-block' ),
		description: __( 'The product’s tags, each linking to its archive.', 'flexa-block' ),
		keywords: [ __( 'tags', 'flexa-block' ), __( 'tag', 'flexa-block' ), __( 'taxonomy', 'flexa-block' ), __( 'terms', 'flexa-block' ) ],
		icon: BLOCK_ICONS[ 'product-tags' ],
		scope: [ 'inserter', 'transform' ],
		attributes: { field: 'tags', label: 'Tags:' },
		isActive: ( attrs ) => 'tags' === attrs.field,
	},
];

export default variations;
