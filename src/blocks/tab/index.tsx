/**
 * Tab block registration (child of flexa/tabs).
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

const icon = BLOCK_ICONS[ 'tabs' ];

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	// Each tab's extra blocks are serialized so the parent render can place them
	// below the tab's default text.
	save: () => <InnerBlocks.Content />,
} );
