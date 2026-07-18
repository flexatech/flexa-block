/**
 * Team Member block registration.
 *
 * The inserter glyph is defined inline here (rather than pulled from the shared
 * BLOCK_ICONS map) so this block stays self-contained.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import './style.scss';
import './editor.scss';

const icon = {
	src: (
		<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
			<circle cx="12" cy="8" r="3.4" fill="none" stroke="currentColor" strokeWidth="1.6" />
			<path
				d="M5 20c0-3.6 3.1-6 7-6s7 2.4 7 6"
				fill="none"
				stroke="currentColor"
				strokeWidth="1.6"
				strokeLinecap="round"
			/>
		</svg>
	),
	foreground: '#2563eb',
};

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save: () => null,
} );
