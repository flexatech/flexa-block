/**
 * Filter: Search — registration (child of flexa/post-filter).
 *
 * No style import: the child's appearance is styled from the parent's CSS
 * generator, so it ships no stylesheet of its own.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';

const icon = BLOCK_ICONS[ 'filter-search' ];

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save: () => null,
} );
