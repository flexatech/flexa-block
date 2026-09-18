/**
 * Social Share block variations.
 *
 * One block, two inserter entries. "Social Share" posts whatever page it sits
 * on; "Product Share" posts the WooCommerce product resolved for the request —
 * the same buttons, the same styling surface, only `shareSource` differs. The
 * product entry is withheld when WooCommerce is inactive so the inserter never
 * offers something that cannot resolve a product.
 *
 * @package Flexa\Block
 */

import { __ } from '@wordpress/i18n';

import { BLOCK_ICONS } from '@shared/block-icons';
import type { SocialShareAttributes } from '../../types';

/** A registered block variation (inserter entry + preset). */
export interface ShareVariation {
	name: string;
	title: string;
	description: string;
	icon?: unknown;
	isDefault?: boolean;
	attributes: Partial< SocialShareAttributes >;
	isActive: ( attrs: Partial< SocialShareAttributes > ) => boolean;
	scope: string[];
}

const variations: ShareVariation[] = [
	{
		name: 'page-share',
		title: __( 'Social Share', 'flexa-block' ),
		description: __( 'Share buttons that post the page they sit on.', 'flexa-block' ),
		icon: BLOCK_ICONS[ 'social-share' ],
		isDefault: true,
		scope: [ 'inserter', 'transform' ],
		attributes: {},
		// Anything that is not the product preset falls back to this entry, so
		// the block never shows up nameless in the breadcrumb.
		isActive: ( attrs ) => attrs.shareSource !== 'product',
	},
	{
		name: 'product-share',
		title: __( 'Product Share', 'flexa-block' ),
		description: __(
			'Share buttons that post the current WooCommerce product — its own title and featured image travel with the link.',
			'flexa-block'
		),
		icon: BLOCK_ICONS[ 'product-share' ],
		scope: [ 'inserter', 'transform' ],
		attributes: {
			shareSource: 'product',
			shape: 'circle',
			items: [ { network: 'facebook' }, { network: 'x' }, { network: 'pinterest' }, { network: 'whatsapp' } ],
		},
		isActive: ( attrs ) => attrs.shareSource === 'product',
	},
];

/** Drop the product entry when WooCommerce is not running. */
export default variations.filter(
	( variation ) => 'product-share' !== variation.name || window.flexaBlockEditor?.wooActive === true
);
