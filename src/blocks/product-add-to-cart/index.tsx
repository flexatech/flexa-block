/**
 * Add to Cart block registration.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import './style.scss';
import './editor.scss';

const icon = BLOCK_ICONS[ 'product-add-to-cart' ];

// Dynamic block: markup comes from render.php (WooCommerce's own add-to-cart
// template), so save persists no content.
registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save: () => null,
} );
