/**
 * Product Meta block registration.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import './style.scss';
import './editor.scss';

const icon = BLOCK_ICONS[ 'product-meta' ];

// Dynamic block, but a container: render.php wraps whatever the inner blocks
// produced, so save must persist the inner blocks' own markup.
registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save: () => <InnerBlocks.Content />,
} );
