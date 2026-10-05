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
