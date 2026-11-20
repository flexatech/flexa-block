<?php
/**
 * Dark Mode Settings helper.
 *
 * @package Flexa\Block
 */

declare(strict_types=1);

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads dark-mode configuration from options (cached).
 */
class Dark_Mode_Settings {

	/**
	 * Cached settings.
	 *
	 * @var array|null
	 */
	private static $cache = null;

	/**
	 * Whether dark mode output is enabled and at least one method active.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		$settings = self::get();
		if ( empty( $settings['enabled'] ) ) {
			return false;
		}
		return self::use_color_scheme() || self::use_data_theme();
	}

	/**
	 * Whether the prefers-color-scheme method is active.
	 *
	 * @return bool
	 */
	public static function use_color_scheme(): bool {
		$settings = self::get();
		return ! empty( $settings['enabled'] ) && ! empty( $settings['colorScheme'] );
	}

	/**
	 * Whether the [data-theme="dark"] method is active.
	 *
	 * @return bool
	 */
	public static function use_data_theme(): bool {
		$settings = self::get();
		return ! empty( $settings['enabled'] ) && ! empty( $settings['dataTheme'] );
	}

	/** Option: the install-defaults step this site has been through. */
	const DEFAULTS_OPTION = 'flexa_block_defaults_version';

	/** The current install-defaults step. */
	const DEFAULTS_VERSION = 2;

	/**
	 * Keep a site's dark mode exactly as it was, once, when it updates.
	 *
	 * Dark mode used to be on unless switched off. New sites now start with it
	 * off (see on_activate()), but a site that was running the plugin before
	 * this version must see no change. Until now, missing settings were read as:
	 * no dark_mode group at all = on (with prefers-color-scheme); a group with a
	 * key missing = that key off. So whatever a site has not written down is
	 * written now with the value it was effectively running on, and from then on
	 * nothing depends on the default. Runs once (the version is recorded) and
	 * writes only what was missing.
	 */
	public static function apply_install_defaults(): void {
		if ( (int) get_option( self::DEFAULTS_OPTION, 0 ) >= self::DEFAULTS_VERSION ) {
			return;
		}

		$stored   = get_option( 'flexa_block_settings', null );
		$settings = is_array( $stored ) ? $stored : [];

		if ( ! is_array( $settings['dark_mode'] ?? null ) ) {
			// No group: the code's default applied, which was on.
			$settings['dark_mode'] = [
				'enabled'     => true,
				'colorScheme' => true,
				'dataTheme'   => false,
			];
			update_option( 'flexa_block_settings', $settings );
		} else {
			// A group with a key missing: that key was read as off.
			$dark   = $settings['dark_mode'];
			$filled = $dark + [
				'enabled'     => false,
				'colorScheme' => false,
				'dataTheme'   => false,
			];
			if ( $filled !== $dark ) {
				$settings['dark_mode'] = $filled;
				update_option( 'flexa_block_settings', $settings );
			}
		}

		self::$cache = null;
		update_option( self::DEFAULTS_OPTION, self::DEFAULTS_VERSION );
	}

	/**
	 * A new site starts with dark mode off.
	 *
	 * Called on activation. A site counts as new only when the plugin has left
	 * no trace there: no saved settings and no Flexa block CSS on any post. A
	 * site updating keeps running and never comes through here, so its dark
	 * mode is settled by apply_install_defaults() instead.
	 */
	public static function on_activate(): void {
		if ( (int) get_option( self::DEFAULTS_OPTION, 0 ) >= self::DEFAULTS_VERSION ) {
			return;
		}
		if ( null !== get_option( 'flexa_block_settings', null ) || self::has_generated_css() ) {
			// Used here before: the next load keeps what it had.
			return;
		}

		update_option(
			'flexa_block_settings',
			[
				'dark_mode' => [
					'enabled'     => false,
					'colorScheme' => true,
					'dataTheme'   => false,
				],
			]
		);
		self::$cache = null;
		update_option( self::DEFAULTS_OPTION, self::DEFAULTS_VERSION );
	}

	/**
	 * Whether any post has Flexa block CSS, i.e. the plugin has been in use here.
	 */
	private static function has_generated_css(): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One existence check, on activation.
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$wpdb->postmeta} WHERE meta_key = %s LIMIT 1", '_flexa_block_css' ) );
	}

	/**
	 * Get dark-mode settings.
	 *
	 * @return array
	 */
	private static function get(): array {
		if ( null === self::$cache ) {
			$all         = get_option( 'flexa_block_settings', [] );
			self::$cache = $all['dark_mode'] ?? [
				'enabled'     => true,
				'colorScheme' => true,
				'dataTheme'   => false,
			];
		}
		return self::$cache;
	}
}
