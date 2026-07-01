<?php
declare(strict_types=1);
/**
 * Global Styles - shared design tokens emitted as CSS custom properties.
 *
 * Provides one set of `--flexa-*` variables (colors, spacing, typography,
 * radius) so every block references the SAME design language instead of each
 * block hard-coding its own values. Dark-mode overrides reuse the plugin's
 * existing dark-mode strategy (prefers-color-scheme / [data-theme="dark"]).
 *
 * Override any token with the `flexa_block_design_tokens` filter.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `:root` design-token stylesheet.
 */
class Global_Styles {

	/**
	 * Light (default) token map: token name => value. Emitted as `--flexa-<name>`.
	 *
	 * @var array<string, string>
	 */
	const LIGHT_TOKENS = [
		'color-primary'          => '#2563eb',
		'color-primary-contrast' => '#ffffff',
		'color-text'             => '#1f2937',
		'color-heading'          => '#111827',
		'color-background'       => '#ffffff',
		'color-surface'          => '#f9fafb',
		'color-muted'            => '#6b7280',
		'color-border'           => '#e5e7eb',
		'space-xs'               => '4px',
		'space-sm'               => '8px',
		'space-md'               => '16px',
		'space-lg'               => '24px',
		'space-xl'               => '40px',
		'radius-sm'              => '4px',
		'radius-md'              => '8px',
		'radius-lg'              => '16px',
		'font-size-sm'           => '0.875rem',
		'font-size-base'         => '1rem',
		'font-size-lg'           => '1.25rem',
		'font-size-xl'           => '1.5rem',
		'font-size-2xl'          => '2rem',
		'line-height'            => '1.6',
	];

	/**
	 * Dark-mode token overrides (only the tokens that change).
	 *
	 * @var array<string, string>
	 */
	const DARK_TOKENS = [
		'color-text'       => '#e5e7eb',
		'color-heading'    => '#f9fafb',
		'color-background' => '#0b0f19',
		'color-surface'    => '#111827',
		'color-muted'      => '#9ca3af',
		'color-border'     => '#374151',
	];

	/**
	 * Get the active light tokens (filterable).
	 *
	 * @return array<string, string>
	 */
	public static function get_tokens(): array {
		/**
		 * Filter the global design tokens (light set).
		 *
		 * @param array<string, string> $tokens token-name => value.
		 */
		return apply_filters( 'flexa_block_design_tokens', self::LIGHT_TOKENS );
	}

	/**
	 * Get the active dark-mode token overrides (filterable).
	 *
	 * @return array<string, string>
	 */
	public static function get_dark_tokens(): array {
		/**
		 * Filter the global design tokens (dark overrides).
		 *
		 * @param array<string, string> $tokens token-name => value.
		 */
		return apply_filters( 'flexa_block_design_tokens_dark', self::DARK_TOKENS );
	}

	/**
	 * Build the design-token stylesheet (minified).
	 *
	 * @return string
	 */
	public static function css(): string {
		$css = new CSS_Builder();

		// Light tokens on :root.
		$css->set_selector( ':root' );
		self::emit_tokens( $css, self::get_tokens() );

		// Dark overrides via the configured dark-mode method(s).
		$dark = self::get_dark_tokens();
		if ( ! empty( $dark ) && Dark_Mode_Settings::is_enabled() ) {
			if ( Dark_Mode_Settings::use_data_theme() ) {
				$css->set_selector( '[data-theme="dark"]' );
				self::emit_tokens( $css, $dark );
			}
			if ( Dark_Mode_Settings::use_color_scheme() ) {
				$css->start_media_query( '@media (prefers-color-scheme: dark)' );
				$css->set_selector( ':root' );
				self::emit_tokens( $css, $dark );
				$css->end_media_query();
			}
		}

		return $css->get_output();
	}

	/**
	 * Emit a token map as `--flexa-<name>` custom properties on the active selector.
	 *
	 * @param CSS_Builder           $css    Builder (selector already set).
	 * @param array<string, string> $tokens Token map.
	 */
	private static function emit_tokens( CSS_Builder $css, array $tokens ): void {
		foreach ( $tokens as $name => $value ) {
			$css->add_property( '--flexa-' . sanitize_key( (string) $name ), (string) $value );
		}
	}
}
