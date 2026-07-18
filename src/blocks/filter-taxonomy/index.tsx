/**
 * Filter: Taxonomy — registration (child of flexa/post-filter).
 *
 * Registered once, surfaced in the inserter as "Category filter" and "Tag filter"
 * via variations — one block's worth of code, two blocks' worth of discoverability,
 * and custom taxonomies come free.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import { variations } from './variations';

const icon = BLOCK_ICONS[ 'filter-taxonomy' ];

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save: () => null,
	variations,
} );
