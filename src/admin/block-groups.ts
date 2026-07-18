/**
 * Block groups — a finer taxonomy than the raw `layout` / `design` category.
 *
 * The block catalog only carries two categories, which makes for two large,
 * unhelpful filters. This maps each block slug to a purpose-based group so the
 * dashboard can offer smaller, meaningful filter pills. Each group also carries
 * a colour pair used to tint the block thumbnails, giving the grid visual
 * variety. Unknown slugs fall back to their catalog category (or "other").
 */

import { __ } from '@wordpress/i18n';

export interface GroupMeta {
	/** Stable group key (also the filter value). */
	key: string;
	/** Translated, human-facing label. */
	label: string;
	/** Thumbnail gradient start colour. */
	from: string;
	/** Thumbnail gradient end colour. */
	to: string;
	/** Icon / chip accent colour. */
	accent: string;
}

/** Slug → group key. Every top-level block should appear here. */
const SLUG_GROUP: Record< string, string > = {
	// Layout
	container: 'layout',
	grid: 'layout',
	slides: 'layout',

	// Content & typography
	heading: 'content',
	text: 'content',
	separator: 'content',
	button: 'content',
	'table-of-content': 'content',
	breadcrumb: 'content',

	// Posts — everything that pulls in, filters or links out to post content.
	'post-grid': 'posts',
	'post-filter': 'posts',
	taxonomy: 'posts',
	rss: 'posts',

	// Media
	image: 'media',
	'before-after': 'media',
	'images-gallery': 'media',
	'video-popup': 'media',
	'google-map': 'media',

	// Interactive
	faq: 'interactive',
	tabs: 'interactive',
	steps: 'interactive',
	countdown: 'interactive',
	counter: 'interactive',
	'process-bar': 'interactive',
	timeline: 'interactive',
	modal: 'interactive',
	lottie: 'interactive',

	// Elements — small building blocks that sit inside other blocks.
	icon: 'elements',
	'icon-list': 'elements',
	'star-rating': 'elements',
	notice: 'elements',
	'data-table': 'elements',

	// Marketing
	banner: 'marketing',
	cta: 'marketing',
	'info-box': 'marketing',
	testimonial: 'marketing',
	'comparison-table': 'marketing',
	'pricing-table': 'marketing',
	'team-member': 'marketing',

	// Social
	'social-icon': 'social',
	'social-share': 'social',
	'facebook-feed': 'social',
	'instagram-feed': 'social',

	// Forms
	'subscribe-form': 'forms',

	// WooCommerce (single-product blocks)
	'product-name': 'woocommerce',
	'product-price': 'woocommerce',
	'product-rating': 'woocommerce',
	'product-detail': 'woocommerce',
	'product-image': 'woocommerce',
};

/** Group key → display + colour metadata. */
export const GROUP_META: Record< string, GroupMeta > = {
	layout: {
		key: 'layout',
		label: __( 'Layout', 'flexa-block' ),
		from: '#6366f1',
		to: '#8b5cf6',
		accent: '#4f46e5',
	},
	content: {
		key: 'content',
		label: __( 'Content', 'flexa-block' ),
		from: '#3b82f6',
		to: '#06b6d4',
		accent: '#2563eb',
	},
	posts: {
		key: 'posts',
		label: __( 'Posts', 'flexa-block' ),
		from: '#0ea5e9',
		to: '#6366f1',
		accent: '#0284c7',
	},
	elements: {
		key: 'elements',
		label: __( 'Elements', 'flexa-block' ),
		from: '#22c55e',
		to: '#84cc16',
		accent: '#16a34a',
	},
	media: {
		key: 'media',
		label: __( 'Media', 'flexa-block' ),
		from: '#8b5cf6',
		to: '#d946ef',
		accent: '#7c3aed',
	},
	interactive: {
		key: 'interactive',
		label: __( 'Interactive', 'flexa-block' ),
		from: '#14b8a6',
		to: '#10b981',
		accent: '#0d9488',
	},
	marketing: {
		key: 'marketing',
		label: __( 'Marketing', 'flexa-block' ),
		from: '#f59e0b',
		to: '#f97316',
		accent: '#d97706',
	},
	social: {
		key: 'social',
		label: __( 'Social', 'flexa-block' ),
		from: '#ec4899',
		to: '#f43f5e',
		accent: '#db2777',
	},
	forms: {
		key: 'forms',
		label: __( 'Forms', 'flexa-block' ),
		from: '#06b6d4',
		to: '#3b82f6',
		accent: '#0891b2',
	},
	woocommerce: {
		key: 'woocommerce',
		label: __( 'WooCommerce', 'flexa-block' ),
		from: '#7f54b3',
		to: '#a46bce',
		accent: '#7f54b3',
	},
	other: {
		key: 'other',
		label: __( 'Other', 'flexa-block' ),
		from: '#64748b',
		to: '#94a3b8',
		accent: '#475569',
	},
};

/**
 * Preferred filter order (groups with no visible blocks are dropped later — which
 * is why "Other" no longer shows: every block now has a real home. It stays here as
 * the landing spot for a block someone adds to the catalog and forgets to map).
 */
export const GROUP_ORDER: string[] = [
	'layout',
	'content',
	'posts',
	'media',
	'interactive',
	'elements',
	'marketing',
	'social',
	'forms',
	'woocommerce',
	'other',
];

/** Resolve a block's group key, falling back to its catalog category. */
export function groupOf( block: FlexaBlockAdminBlock ): string {
	const mapped = SLUG_GROUP[ block.slug ];
	if ( mapped ) {
		return mapped;
	}
	if ( 'woocommerce' === block.category ) {
		return 'woocommerce';
	}
	return block.category === 'layout' ? 'layout' : 'other';
}

/** Colour + label metadata for a group key (never returns undefined). */
export function groupMeta( key: string ): GroupMeta {
	return GROUP_META[ key ] || GROUP_META.other;
}
