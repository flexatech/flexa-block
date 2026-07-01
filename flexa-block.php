<?php
/**
 * Plugin Name:       Flexa Block
 * Description:       A collection of lightweight, customizable Gutenberg blocks for building modern WordPress websites.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Flexa Tech
 * Author URI:        https://flexa.vn
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       flexa-block
 * Domain Path:       /languages
 *
 * @package Flexa\Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FLEXA_BLOCK_VER', '1.0.0' );
define( 'FLEXA_BLOCK_DIR', plugin_dir_path( __FILE__ ) );
define( 'FLEXA_BLOCK_URL', plugin_dir_url( __FILE__ ) );
define( 'FLEXA_BLOCK_BASENAME', plugin_basename( __FILE__ ) );
define( 'FLEXA_BLOCK_MIN_WP', '6.4' );

/**
 * Boot the plugin.
 */
function flexa_block_init() {
	if ( version_compare( get_bloginfo( 'version' ), FLEXA_BLOCK_MIN_WP, '<' ) ) {
		add_action( 'admin_notices', 'flexa_block_wp_version_notice' );
		return;
	}

	// Core services.
	require_once FLEXA_BLOCK_DIR . 'includes/class-css-builder.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-css-helpers.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-html-helpers.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-dark-mode-settings.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-global-styles.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-css-generator-service.php';
	require_once FLEXA_BLOCK_DIR . 'includes/css-generators/index.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-block-manager.php';
	require_once FLEXA_BLOCK_DIR . 'includes/class-asset-loader.php';
	require_once FLEXA_BLOCK_DIR . 'includes/admin/class-admin.php';

	Flexa\Block\Block_Manager::init();
	Flexa\Block\Asset_Loader::init();
	Flexa\Block\Admin\Admin::init();
}
add_action( 'plugins_loaded', 'flexa_block_init' );

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
