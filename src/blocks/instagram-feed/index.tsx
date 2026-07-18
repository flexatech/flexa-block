/**
 * Instagram Feed block registration.
 *
 * Dynamic block — the media list is fetched and rendered server-side in
 * render.php (Instagram Graph `me/media`, cached); nothing is saved. The editor
 * previews the real feed live through an editor-only REST proxy.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import './style.scss';
import './editor.scss';

const icon = BLOCK_ICONS[ 'instagram-feed' ];

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	// Dynamic block — markup comes from render.php (Instagram_Feed::media); nothing is saved.
	save: () => null,
} );
