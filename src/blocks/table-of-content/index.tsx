/**
 * Table of Contents block registration.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import './style.scss';
import './editor.scss';

const icon = BLOCK_ICONS[ 'table-of-content' ];

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	// Dynamic block — the list is produced by render.php + view.ts; nothing is saved.
	save: () => null,
} );
