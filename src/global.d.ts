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
	/** Whether WooCommerce is active — gates the product-only inspector options
	 *  on blocks that serve both a generic and a WooCommerce variation. */
	wooActive?: boolean;
}

interface FlexaBlockAdminBlock {
	slug: string;
	name?: string;
	title?: string;
	description?: string;
	category?: string;
	is_core?: boolean;
	is_child?: boolean;
	/** Filter-pill override, for blocks the slug→group table cannot know (add-ons). */
	group?: string;
	/** Origin label shown beside the card title, e.g. "Pro". Set by add-ons. */
	badge?: string;
	/**
	 * The block belongs to an add-on this site does not have. The card is shown
	 * so the blocks are discoverable, but it is not registered, cannot be
	 * switched, and must not count towards the enabled/total tally.
	 */
	locked?: boolean;
}

/**
 * Glyphs contributed by add-on plugins, keyed by block slug.
 *
 * An add-on's block icons live in its own bundle, so they cannot be reached
 * from BLOCK_ICONS here and cannot travel through PHP as JSX. Instead the
 * add-on enqueues a small script that fills this global before the dashboard
 * mounts, and BlockThumb falls back to it.
 */
interface FlexaBlockAddonIcons {
	[ slug: string ]: { src: any; foreground?: string } | undefined;
}

interface FlexaBlockAdminRole {
	slug: string;
	name: string;
}

/** FormFlow cross-promotion state, from Admin::formflow_promo(). */
interface FlexaBlockFormFlowPromo {
	/** Already accounts for the capability, the dismissal, and FormFlow being installed. */
	show: boolean;
	/** One-click install link, or '' when the user may not install plugins. */
	installUrl: string;
	learnMoreUrl: string;
	iconUrl: string;
	dismissUrl: string;
}

/**
 * MCP module state, from MCP_Settings::boot_payload().
 *
 * Sent on every dashboard load, whatever the module's state, so the panel can
 * describe an unsupported or switched-off site without a request. `enabled` is
 * the effective value: a site that switched the module on and then moved to an
 * older WordPress reports false here, with `supported` false beside it.
 */
interface FlexaBlockMcpData {
	supported: boolean;
	minWp: string;
	enabled: boolean;
	read: boolean;
	write: boolean;
	adapter: { active: boolean; version: string };
	/** '' until the adapter's transport URL is known; see MCP_Settings::endpoint(). */
	endpoint: string;
	restUrl: string;
	/** Newest first. Absent on older bundles; see MCP_Settings::activity_rows(). */
	activity?: FlexaBlockMcpActivityRow[];
	/** Days a row is kept. 0 means only the row limit removes one. */
	activityDays?: number;
}

/**
 * One call recorded by the module's activity log.
 *
 * Deliberately narrow. The log keeps no content, no slot values, no prompts
 * and no tokens, so there is nothing here to render beyond who called what,
 * on which page, and how it ended.
 */
interface FlexaBlockMcpActivityRow {
	/** Unix timestamp, for sorting and for a precise tooltip. */
	time: number;
	/** The same moment in the site's timezone and date format. */
	when: string;
	/** Display name, snapshotted login, or '#12'. */
	user: string;
	ability: string;
	/** 0 when the call was not about one page. */
	post: number;
	/** 'ok' | 'refused' | 'denied' | 'error'. */
	outcome: string;
	/** Error code behind a refusal; '' for a call that succeeded. */
	code: string;
	/** Correlates the rows written during one request. */
	request: string;
}

interface FlexaBlockAdminData {
	nonce?: string;
	restUrl?: string;
	version?: string;
	settings?: Record< string, any >;
	blocks?: FlexaBlockAdminBlock[];
	editableBlocks?: string[];
	roles?: FlexaBlockAdminRole[];
	/** Cross-promotion banner state; absent on older bundles. */
	formFlow?: FlexaBlockFormFlowPromo;
	/** MCP module state; absent on older bundles. */
	mcp?: FlexaBlockMcpData;
}

/** REST endpoints for the import engine (Sample Data), from Import_Manager. */
interface FlexaBlockImportsData {
	listUrl?: string;
	importUrl?: string;
	cleanupUrl?: string;
}

interface Window {
	flexaBlockEditor?: FlexaBlockEditorData;
	flexaBlockAdmin?: FlexaBlockAdminData;
	flexaBlockImports?: FlexaBlockImportsData;
	flexaBlockAddonIcons?: FlexaBlockAddonIcons;
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
