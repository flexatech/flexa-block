/**
 * Product Name block registration.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import './style.scss';
import './editor.scss';

const icon = BLOCK_ICONS[ 'product-name' ];

// Dynamic block: markup comes from render.php, so save persists no content.
registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save: () => null,
} );
