/**
 * Filter: Reset — registration (child of flexa/post-filter).
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';

const icon = BLOCK_ICONS[ 'filter-reset' ];

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save: () => null,
} );
