/**
 * Tabs block registration.
 *
 * @package Flexa\Block
 */

import { registerBlockType, createBlock } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import metadata from './block.json';
import { BLOCK_ICONS } from '@shared/block-icons';
import Edit from './edit';
import './style.scss';
import './editor.scss';
import type { TabItem } from '../../types';

const icon = BLOCK_ICONS[ 'tabs' ];

/**
 * Legacy tabs stored their panels in a `tabs` attribute (label + plain-text
 * content). Now each panel is a `flexa/tab` child block. This deprecation
 * migrates old content: it turns each legacy tab into a child block (keeping the
 * label + icon, and the text as the tab's default text) the first time the block
 * is opened in the editor. The front end also renders legacy content directly
 * (see render.php), so nothing breaks before migration runs.
 */
const v1 = {
	attributes: metadata.attributes,
	supports: metadata.supports,
	// Old dynamic block saved nothing.
	save: () => null,
	isEligible( attrs: { tabs?: TabItem[] }, innerBlocks: unknown[] ) {
		return Array.isArray( attrs.tabs ) && attrs.tabs.length > 0 && ( ! innerBlocks || innerBlocks.length === 0 );
	},
	migrate( attrs: { tabs?: TabItem[] } & Record< string, unknown > ) {
		const tabs = Array.isArray( attrs.tabs ) ? attrs.tabs : [];
		const inner = tabs.map( ( t ) =>
			createBlock( 'flexa/tab', {
				label: t.label || '',
				icon: t.icon || { source: 'none', name: '', markup: '', url: '', id: null },
				text: t.content || '',
			} )
		);
		const next = { ...attrs, tabs: [] };
		return [ next, inner ];
	},
};

registerBlockType( metadata.name, {
	icon,
	edit: Edit,
	// Dynamic block — render.php builds the front-end markup. The child tab blocks
	// are serialized (InnerBlocks.Content) so their content persists.
	save: () => <InnerBlocks.Content />,
	deprecated: [ v1 ],
} );
