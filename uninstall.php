<?php
/**
 * Flexa Block uninstall cleanup.
 *
 * @package Flexa\Block
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'flexa_block_settings' );
delete_option( 'flexa_block_formflow_notice_dismissed' );
// The shared 'flexa_formflow_promo_dismissed' flag is left in place on purpose:
// other Flexa plugins read it, and removing this one must not bring back a
// suggestion the user already turned down.
delete_option( 'flexa_block_feed_tokens' );
delete_option( 'flexa_block_item_style_migrated' );
delete_option( 'flexa_block_item_style_migration_cursor' );

// MCP module. Its own option, kept apart from flexa_block_settings so that
// switching the module on never rides along with an unrelated settings save.
delete_option( 'flexa_block_mcp' );
delete_option( 'flexa_block_mcp_log' );

// The module's transients: one per rate-limit window, one per idempotency
// record, both named after a hash, so there is no list of keys to walk. The
// options table is where a site without a persistent object cache keeps them.
// A site with one keeps them in memory instead, where nothing can enumerate
// them; those expire on their own within a day, which is the shortest honest
// answer available here.
global $wpdb;

foreach ( [ 'flexa_mcp_rate_', 'flexa_mcp_idem_' ] as $flexa_block_prefix ) {
	$flexa_block_like = $wpdb->esc_like( $flexa_block_prefix ) . '%';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'_transient_' . $flexa_block_like,
			'_transient_timeout_' . $flexa_block_like
		)
	);
}
