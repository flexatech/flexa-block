/**
 * Subscribe Form — Phone field registration (child of flexa/subscribe-form).
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import './style.scss';
import './editor.scss';

const icon = BLOCK_ICONS[ 'subscribe-form-phone' ];

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save: () => null,
} );
