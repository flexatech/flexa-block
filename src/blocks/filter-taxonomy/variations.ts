/**
 * Filter: Taxonomy — block variations.
 *
 * One block does category, tag and any custom taxonomy. But "Taxonomy filter" is not
 * what an author is looking for in the inserter, so these three variations are what
 * shows instead — each landing pre-configured, none of them a duplicate of another.
 *
 * The base block is hidden from the list by the `isDefault` below, and ONLY by it:
 * core omits the block's own inserter item as soon as one of its variations claims to
 * be the default (`if ( ! variations.some( ( { isDefault } ) => isDefault ) )` in
 * getInserterItems). It must not be done with `supports.inserter: false` — that drops
 * the block from the inserter's source list, and the variations go down with it,
 * which is exactly what happened: the whole taxonomy filter vanished from the editor.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';

export const variations = [
	{
		name: 'category-filter',
		title: __( 'Category filter', 'flexa-block' ),
		description: __( 'Let visitors narrow the Post Grid to one category.', 'flexa-block' ),
		attributes: { taxonomy: 'category', label: __( 'Category', 'flexa-block' ) },
		isDefault: true,
		isActive: ( blockAttributes: { taxonomy?: string } ) => 'category' === blockAttributes.taxonomy,
		scope: [ 'block', 'inserter', 'transform' ],
	},
	{
		name: 'tag-filter',
		title: __( 'Tag filter', 'flexa-block' ),
		description: __( 'Let visitors narrow the Post Grid to one tag.', 'flexa-block' ),
		attributes: { taxonomy: 'post_tag', label: __( 'Tag', 'flexa-block' ) },
		isActive: ( blockAttributes: { taxonomy?: string } ) => 'post_tag' === blockAttributes.taxonomy,
		scope: [ 'block', 'inserter', 'transform' ],
	},
	{
		name: 'custom-taxonomy-filter',
		title: __( 'Custom taxonomy filter', 'flexa-block' ),
		description: __( 'Filter by any other taxonomy — pick which one in the block settings.', 'flexa-block' ),
		// Deliberately empty: the author picks the taxonomy, and until they do the
		// block renders nothing rather than silently filtering by category.
		attributes: { taxonomy: '', label: '' },
		isActive: ( blockAttributes: { taxonomy?: string } ) =>
			! blockAttributes.taxonomy || ! [ 'category', 'post_tag' ].includes( blockAttributes.taxonomy ),
		scope: [ 'block', 'inserter', 'transform' ],
	},
];
