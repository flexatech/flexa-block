/**
 * Subscribe Form — Date field registration (child of flexa/subscribe-form).
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import './style.scss';
import './editor.scss';

const icon = BLOCK_ICONS[ 'subscribe-form-date' ];

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save: () => null,
} );
