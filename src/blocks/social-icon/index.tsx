/**
 * Social Icons block registration.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import './style.scss';
import './editor.scss';

const icon = BLOCK_ICONS[ 'social-icon' ];

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	// Dynamic block — markup is produced by render.php; nothing is saved.
	save: () => null,
} );
