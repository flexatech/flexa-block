<?php
declare(strict_types=1);
/**
 * Dark Mode Settings helper.
 *
 * @package Flexa\Block
 */

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
