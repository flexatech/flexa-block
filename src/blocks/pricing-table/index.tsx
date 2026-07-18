/**
 * Pricing Table block registration.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import './style.scss';
import './editor.scss';

// The shared block-icons catalog owns every glyph; fall back to a local pricing
// icon until the "pricing-table" entry is added there so the block always
// registers with an icon.
const icon = BLOCK_ICONS[ 'pricing-table' ] ?? {
	src: (
		<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
			<rect x="3" y="4" width="7" height="16" rx="1.5" fill="none" stroke="currentColor" strokeWidth="1.6" />
			<rect x="14" y="4" width="7" height="16" rx="1.5" fill="none" stroke="currentColor" strokeWidth="1.6" />
			<line x1="4.5" y1="9" x2="8.5" y2="9" stroke="currentColor" strokeWidth="1.6" />
			<line x1="15.5" y1="9" x2="19.5" y2="9" stroke="currentColor" strokeWidth="1.6" />
		</svg>
	),
	foreground: '#2563eb',
};

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save: () => null,
} );
