/**
 * Post Grid block registration.
 *
 * Dynamic block — the grid of posts is produced by a WP_Query in render.php;
 * nothing is saved. The editor previews the query live via getEntityRecords.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import './style.scss';
import './editor.scss';

/** Inserter glyph — a 2×2 card grid. */
const icon = {
	src: (
		<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
			<rect x="3" y="4" width="8" height="7" rx="1.4" fill="none" stroke="currentColor" strokeWidth="1.6" />
			<rect x="13" y="4" width="8" height="7" rx="1.4" fill="none" stroke="currentColor" strokeWidth="1.6" />
			<rect x="3" y="13" width="8" height="7" rx="1.4" fill="none" stroke="currentColor" strokeWidth="1.6" />
			<rect x="13" y="13" width="8" height="7" rx="1.4" fill="none" stroke="currentColor" strokeWidth="1.6" />
			<line x1="4.5" y1="9" x2="9.5" y2="9" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
			<line x1="14.5" y1="9" x2="19.5" y2="9" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
		</svg>
	),
	foreground: '#2563eb',
};

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	// Dynamic block — the grid is produced by render.php (WP_Query); nothing is saved.
	save: () => null,
} );
