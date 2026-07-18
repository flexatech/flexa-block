/**
 * Container block registration.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import variations from './variations';
import './style.scss';
import './editor.scss';

const icon = BLOCK_ICONS[ 'container' ];

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save: () => <InnerBlocks.Content />,
	variations,
} );
