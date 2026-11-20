<?php
/**
 * Empties page caches when shared content changes.
 *
 * @package Flexa\Block
 */

declare(strict_types=1);

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tells page-cache plugins that every page may have changed.
 *
 * Saving a page fires the post hooks cache plugins listen to, and they clear
 * that page. A template part (header, footer) or a synced pattern is different:
 * it is not a public post, and no cache plugin knows which pages show it, so
 * the pages keep being served with the old text. After such a save the whole
 * cache is emptied, through each known plugin's own call when it is running,
 * and through an action any other cache plugin can listen to.
 */
final class Inline_Cache {

	/**
	 * Empty the page cache of every known cache plugin that is active.
	 *
	 * @param \WP_Post $store The shared post that changed (for listeners).
	 */
	public static function purge_all( \WP_Post $store ): void {
		// Flexa Cache.
		if ( has_action( 'flexa_cache/purge' ) ) {
			do_action( 'flexa_cache/purge', 'all', null ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.ValidHookName.UseUnderscores -- The cache plugin's own hook.
		}
		// WP Fastest Cache.
		if ( function_exists( 'wpfc_clear_all_cache' ) ) {
			wpfc_clear_all_cache( true );
		}
		// WP Rocket.
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		// LiteSpeed Cache.
		if ( has_action( 'litespeed_purge_all' ) ) {
			do_action( 'litespeed_purge_all' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.ValidHookName.UseUnderscores -- The cache plugin's own hook.
		}
		// W3 Total Cache.
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		// WP Super Cache.
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		// SiteGround Optimizer.
		if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
			sg_cachepress_purge_cache();
		}
		// Cache Enabler.
		if ( has_action( 'cache_enabler_clear_complete_cache' ) ) {
			do_action( 'cache_enabler_clear_complete_cache' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.ValidHookName.UseUnderscores -- The cache plugin's own hook.
		}
		// Breeze.
		if ( has_action( 'breeze_clear_all_cache' ) ) {
			do_action( 'breeze_clear_all_cache' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.ValidHookName.UseUnderscores -- The cache plugin's own hook.
		}

		/**
		 * Fires after a front-end edit changed content shown on many pages (a
		 * template part or a synced pattern), so any page cache should be
		 * emptied.
		 *
		 * @param \WP_Post $store The template part or pattern that changed.
		 */
		do_action( 'flexa_block_inline_purge_all', $store );
	}
}
