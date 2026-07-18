/**
 * Post Filter block — registration (parent of the filter children).
 *
 * @package Flexa\Block
 */

import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import './style.scss';
import './editor.scss';

const icon = BLOCK_ICONS[ 'post-filter' ];

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	// A dynamic block still has to serialize its children. With `save: () => null`
	// Gutenberg writes `<!-- wp:flexa/post-filter /-->` — a self-closing block — and
	// the search / taxonomy / reset children are DROPPED on every save. That is what
	// kept emptying the filter bar. render.php reads them back as $content.
	save: () => <InnerBlocks.Content />,
} );
