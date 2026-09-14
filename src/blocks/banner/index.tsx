/**
 * Banner block registration.
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

const icon = BLOCK_ICONS[ 'banner' ];

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	// Dynamic block (render.php), but the optional top InnerBlocks region
	// (breadcrumb / eyebrow / meta) is serialized so render.php gets it as $content.
	save: () => <InnerBlocks.Content />,
} );
