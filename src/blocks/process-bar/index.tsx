/**
 * Progress Bar block registration.
 *
 * The glyph lives inline here (rather than in the shared block-icons map) so the
 * block is self-contained; it matches the blue stroke used by the other Flexa
 * block icons.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';
import './style.scss';
import './editor.scss';

const icon = {
	foreground: '#2563eb',
	src: (
		<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
			<rect x="3" y="9" width="18" height="6" rx="3" fill="none" stroke="currentColor" strokeWidth="1.6" />
			<rect x="3" y="9" width="11" height="6" rx="3" fill="currentColor" opacity="0.35" />
			<line x1="14" y1="9" x2="14" y2="15" stroke="currentColor" strokeWidth="1.6" />
		</svg>
	),
};

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	// Dynamic block — markup is produced by render.php; nothing is saved.
	save: () => null,
} );
