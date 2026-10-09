<?php
/**
 * Plugin Name:       Flexa Block – Blocks & Page Builder
 * Description:       A collection of lightweight, customizable blocks for building modern WordPress websites with the block editor.
 * Version:           1.0.16
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Flexa Tech
 * Author URI:        https://flexablock.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       flexa-block
 * Domain Path:       /languages
 *
 * @package Flexa\Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FLEXA_BLOCK_VER', '1.0.16' );
define( 'FLEXA_BLOCK_DIR', plugin_dir_path( __FILE__ ) );
define( 'FLEXA_BLOCK_URL', plugin_dir_url( __FILE__ ) );
define( 'FLEXA_BLOCK_BASENAME', plugin_basename( __FILE__ ) );
define( 'FLEXA_BLOCK_MIN_WP', '6.4' );

// Deactivation Intelligence SDK. Offered here, at file-load time, rather than
// from flexa_block_init(): the SDK is a single global class shared by every
// Flexa plugin on the site, and its loader picks the newest bundled copy at
// `plugins_loaded`, so a copy registered later than that has already lost the
// vote. The survey's own config is set up in Deactivation_Survey::init().
require_once FLEXA_BLOCK_DIR . 'includes/class-deactivation-survey.php';
Flexa\Block\Deactivation_Survey::preload();

/**
 * Boot the plugin.
 */
function flexa_block_init() {
	if ( version_compare( get_bloginfo( 'version' ), FLEXA_BLOCK_MIN_WP, '<' ) ) {
		add_action( 'admin_notices', 'flexa_block_wp_version_notice' );
		return;
	}

	// Deactivation feedback survey (admin-only; never blocks deactivation). The
	// class file is already loaded above, where it offers its copy of the SDK.
	Flexa\Block\Deactivation_Survey::init();

	// Core services.
	require_once FLEXA_BLOCK_DIR . 'includes/class-css-builder.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-css-helpers.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-html-helpers.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-woo-helpers.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-social-catalog.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-dark-mode-settings.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-global-styles.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-animations.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-css-generator-service.php';
	require_once FLEXA_BLOCK_DIR . 'includes/css-generators/index.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-block-manager.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-addon-blocks.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-asset-loader.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-block-locator.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-post-query.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-filter-fields.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-post-filter-rest.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-form-handler.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-inline-editor.php';
	// TODO(remove in vNEXT): item-style migration.
	require_once FLEXA_BLOCK_DIR . 'includes/class-item-style-migration.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-uploads.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-rss-feed.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-remote-feed.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-feed-tokens.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-facebook-feed.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-instagram-feed.php';
	require_once FLEXA_BLOCK_DIR . 'includes/import/class-import-manager.php';
	require_once FLEXA_BLOCK_DIR . 'includes/admin/class-admin.php';
	require_once FLEXA_BLOCK_DIR . 'includes/admin/class-formflow-promo.php';
	require_once FLEXA_BLOCK_DIR . 'includes/admin/class-formflow-notice.php';

	Flexa\Block\Block_Manager::init();
	Flexa\Block\Addon_Blocks::init();
	Flexa\Block\Animations::init();
	Flexa\Block\Asset_Loader::init();
	Flexa\Block\Form_Handler::init();
	Flexa\Block\Post_Filter_REST::init();
	Flexa\Block\Inline_Editor::init();
	Flexa\Block\Item_Style_Migration::init(); // TODO(remove in vNEXT): item-style migration.
	Flexa\Block\Uploads::init();
	Flexa\Block\Rss_Feed::init();
	Flexa\Block\Feed_Tokens::init();
	Flexa\Block\Facebook_Feed::init();
	Flexa\Block\Instagram_Feed::init();
	Flexa\Block\Import\Import_Manager::init();
	Flexa\Block\Admin\Admin::init();

	if ( is_admin() ) {
		Flexa\Block\Admin\FormFlow_Notice::init();
	}

	// MCP module switch. Needed by every request that can show or change it:
	// admin screens, WP-CLI (`wp option update`), and the REST API, where the
	// panel's own route lives. A plain page view needs none of it, so on the
	// front end the file is loaded lazily and only if a REST request arrives.
	if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
		flexa_block_boot_mcp_settings();
	} else {
		add_action( 'rest_api_init', 'flexa_block_boot_mcp_settings', 5 );
	}

	// MCP runtime. Separate from the switch above, and gated on it.
	if ( flexa_block_mcp_runtime_enabled() ) {
		// The runtime needs the settings class even on a front-end request: the
		// read and write toggles decide which abilities get registered, and
		// asking their owner is better than repeating the option key a third
		// time. Loading it here is a no-op on the requests that already did.
		flexa_block_boot_mcp_settings();

		require_once FLEXA_BLOCK_DIR . 'includes/mcp/class-mcp-manager.php';
		require_once FLEXA_BLOCK_DIR . 'includes/mcp/class-ability-support.php';
		require_once FLEXA_BLOCK_DIR . 'includes/mcp/class-request-limits.php';
		require_once FLEXA_BLOCK_DIR . 'includes/mcp/class-read-abilities.php';
		require_once FLEXA_BLOCK_DIR . 'includes/mcp/class-draft-writer.php';
		require_once FLEXA_BLOCK_DIR . 'includes/mcp/class-write-abilities.php';
		Flexa\Block\MCP\MCP_Manager::init();
		Flexa\Block\MCP\Read_Abilities::init();
		Flexa\Block\MCP\Write_Abilities::init();
	}
}
add_action( 'plugins_loaded', 'flexa_block_init' );

/**
 * Load the MCP settings store.
 *
 * Reached from three places that can all be true of one request, so it keeps
 * its own flag: `init()` adds filters, and adding them twice would run the
 * write guard twice on every save.
 */
function flexa_block_boot_mcp_settings() {
	static $booted = false;

	if ( $booted ) {
		return;
	}
	$booted = true;

	require_once FLEXA_BLOCK_DIR . 'includes/admin/class-mcp-settings.php';
	Flexa\Block\Admin\MCP_Settings::init();
}

/**
 * Whether the MCP runtime may load on this request.
 *
 * Reads the stored switch straight from the option rather than through
 * MCP_Settings, because this gate also runs on the front end, where that class
 * is not loaded. MCP_Settings owns the option and its shape; the key is
 * repeated here and nowhere else.
 *
 * The WordPress version is re-checked at load, every request. A stored flag is
 * not evidence that the Abilities API is still present: a site can switch the
 * module on under 7.0 and then restore a 6.9 backup, and what loads has to
 * follow the site as it is now, not as it was when someone flipped a switch.
 *
 * @return bool
 */
function flexa_block_mcp_runtime_enabled() {
	if ( ! function_exists( 'wp_register_ability' )
		|| version_compare( get_bloginfo( 'version' ), '7.0', '<' ) ) {
		return false;
	}

	$stored = get_option( 'flexa_block_mcp', [] );

	return is_array( $stored ) && ! empty( $stored['enabled'] );
}

/**
 * Admin notice for unsupported WordPress version.
 */
function flexa_block_wp_version_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php
			printf(
				/* translators: %s: minimum WordPress version */
				esc_html__( 'Flexa Block requires WordPress %s or higher.', 'flexa-block' ),
				esc_html( FLEXA_BLOCK_MIN_WP )
			);
			?>
		</p>
	</div>
	<?php
}
