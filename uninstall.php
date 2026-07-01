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
