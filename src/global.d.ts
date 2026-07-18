/**
 * Ambient declarations for the Flexa Block TypeScript setup.
 *
 * The @wordpress/* packages ship without bundled type definitions in this
 * install, so we treat them as loosely typed. Our own data shapes (see
 * types.ts) are the source of truth and ARE fully type-checked — that is where
 * the long-term safety (catching wrong attribute names, safe refactors) comes
 * from. WordPress component props are intentionally left as `any`.
 */

/* WordPress packages — loose. */
declare module '@wordpress/*';

/* Style + asset imports are handled by webpack, not the type system. */
declare module '*.scss';
declare module '*.css';
declare module '*.svg';

/* Swiper stylesheet sub-paths (e.g. 'swiper/css', 'swiper/css/navigation')
 * are bare specifiers, so the '*.css' pattern above does not match them. */
declare module 'swiper/css';
declare module 'swiper/css/*';

/* Globals injected from PHP (Asset_Loader::editor_settings / Admin::enqueue). */
interface FlexaBlockEditorData {
	darkModeEnabled?: boolean;
}

interface FlexaBlockAdminBlock {
	slug: string;
	name?: string;
	title?: string;
	description?: string;
	category?: string;
	is_core?: boolean;
	is_child?: boolean;
}

interface FlexaBlockAdminRole {
	slug: string;
	name: string;
}

interface FlexaBlockAdminData {
	nonce?: string;
	restUrl?: string;
	version?: string;
	settings?: Record< string, any >;
	blocks?: FlexaBlockAdminBlock[];
	editableBlocks?: string[];
	roles?: FlexaBlockAdminRole[];
}

interface Window {
	flexaBlockEditor?: FlexaBlockEditorData;
	flexaBlockAdmin?: FlexaBlockAdminData;
}

/*
 * JSX is transformed by Babel (@wordpress/babel-preset-default). tsc only needs
 * a permissive JSX shape because the React/WordPress element types are not
 * installed; type-checking value lives in our data types, not in JSX nodes.
 */
declare namespace JSX {
	type Element = any;
	interface IntrinsicElements {
		[ name: string ]: any;
	}
}
