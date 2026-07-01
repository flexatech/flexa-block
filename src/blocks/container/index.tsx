/**
 * Container block registration.
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import metadata from './block.json';
import Edit from './edit';
import variations from './variations';
import './style.scss';
import './editor.scss';

const icon = {
	src: (
		<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
			<rect
				x="3"
				y="4"
				width="18"
				height="16"
				rx="2"
				fill="none"
				stroke="currentColor"
				strokeWidth="1.6"
			/>
			<line x1="3" y1="9" x2="21" y2="9" stroke="currentColor" strokeWidth="1.6" />
		</svg>
	),
	foreground: '#2563eb',
};

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	save: () => <InnerBlocks.Content />,
	variations,
} );
