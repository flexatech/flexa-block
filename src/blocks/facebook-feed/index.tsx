/**
 * Facebook Feed block registration.
 *
 * Dynamic block — the posts are fetched and rendered server-side in render.php
 * (Facebook Graph API via the cached Facebook_Feed helper); nothing is saved. The
 * editor previews the SAME feed live via the editor-only REST proxy.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import './style.scss';
import './editor.scss';

const icon = BLOCK_ICONS[ 'facebook-feed' ];

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	// Dynamic block — posts are produced by render.php (Graph API); nothing is saved.
	save: () => null,
} );
