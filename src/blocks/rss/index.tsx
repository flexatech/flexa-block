/**
 * RSS block registration.
 *
 * Dynamic block — the feed items are fetched and rendered server-side in
 * render.php (WordPress `fetch_feed()`, cached); nothing is saved. The editor
 * shows a layout preview built from placeholder cards.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import './style.scss';
import './editor.scss';

/** Inserter glyph — the classic RSS broadcast mark. */
const icon = {
	src: (
		<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
			<circle cx="6" cy="18" r="2" fill="currentColor" />
			<path d="M4 11a9 9 0 0 1 9 9" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
			<path d="M4 5a15 15 0 0 1 15 15" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" />
		</svg>
	),
	foreground: '#2563eb',
};

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	// Dynamic block — the feed is produced by render.php (fetch_feed); nothing is saved.
	save: () => null,
} );
