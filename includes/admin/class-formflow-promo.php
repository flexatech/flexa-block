<?php
declare(strict_types=1);
/**
 * FormFlow cross-promotion state.
 *
 * One place that answers "should we suggest Flexa FormFlow right now, and
 * where do the buttons point?". Both promo surfaces (the Dashboard notice and
 * the banner on the Flexa Block screen) read this class, so a user who says no
 * on one of them never meets the pitch on the other.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detection, dismissal and URLs for the FormFlow suggestion.
 */
final class FormFlow_Promo {

	/** WordPress.org slug of the promoted plugin. */
	const SLUG = 'flexa-formflow';

	/** Listing page used by the "Learn more" button. */
	const WPORG_URL = 'https://wordpress.org/plugins/flexa-formflow/';

	/** Query arg + nonce action for the Dashboard dismissal link. */
	const DISMISS_ACTION = 'flexa_block_dismiss_formflow';

	/** Per-plugin "not interested" choice, kept so older installs stay quiet. */
	const DISMISSED_OPTION = 'flexa_block_formflow_notice_dismissed';

	/**
	 * Dismissal flag shared by every Flexa plugin, which is why it carries no
	 * plugin namespace. One "no" retires the FormFlow pitch site-wide. It is
	 * deliberately left behind on uninstall: removing one Flexa plugin must not
	 * resurrect a suggestion the user already turned down elsewhere.
	 */
	const SHARED_DISMISSED_OPTION = 'flexa_formflow_promo_dismissed';

	/**
	 * Fired by whichever Flexa plugin prints the Dashboard notice first; the
	 * others check it and stay quiet, so two installed Flexa plugins never
	 * stack two near-identical cards. Shared verbatim between plugins.
	 */
	const RENDERED_ACTION = 'flexa_formflow_promo/rendered';

	/**
	 * Whether either promo surface may render.
	 *
	 * Computed here only, so the React banner can trust one boolean instead of
	 * re-deriving capability, dismissal and installation on its own.
	 */
	public static function should_show(): bool {
		if ( ! apply_filters( 'flexa-block/formflow_promo/enabled', true ) ) {
			return false;
		}
		if ( ! current_user_can( 'manage_options' ) || self::is_dismissed() ) {
			return false;
		}

		return ! self::is_installed();
	}

	/**
	 * Whether the suggestion has already been turned down, here or in a sibling
	 * Flexa plugin.
	 */
	public static function is_dismissed(): bool {
		return (bool) get_option( self::SHARED_DISMISSED_OPTION )
			|| (bool) get_option( self::DISMISSED_OPTION );
	}

	/**
	 * Record the "not interested" choice. Write-once, never reset.
	 *
	 * Both keys are written: the shared one so sibling Flexa plugins honour the
	 * choice, the per-plugin one so a downgrade still reads it.
	 */
	public static function dismiss(): void {
		update_option( self::SHARED_DISMISSED_OPTION, 1 );
		update_option( self::DISMISSED_OPTION, 1 );
	}

	/**
	 * Whether FormFlow's files are present, active or not.
	 *
	 * Someone who already has the plugin does not need to be told about it, so
	 * this deliberately checks installation rather than activation. The
	 * trailing slash keeps `flexa-formflow-pro/` from counting as a match.
	 */
	public static function is_installed(): bool {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( array_keys( get_plugins() ) as $basename ) {
			if ( 0 === strpos( (string) $basename, self::SLUG . '/' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * One-click install link, or an empty string when the user may not install
	 * plugins. The surfaces drop the install button and keep "Learn more".
	 */
	public static function install_url(): string {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return '';
		}

		return wp_nonce_url(
			self_admin_url( 'update.php?action=install-plugin&plugin=' . self::SLUG ),
			'install-plugin_' . self::SLUG
		);
	}

	/**
	 * FormFlow's logo, shipped with this plugin so the promo works offline.
	 */
	public static function icon_url(): string {
		return FLEXA_BLOCK_URL . 'assets/images/flexa-formflow.png';
	}

	/**
	 * Nonce'd dismissal link that returns to the given screen.
	 *
	 * @param string $redirect_to Absolute admin URL to come back to.
	 */
	public static function dismiss_url( string $redirect_to ): string {
		return wp_nonce_url(
			add_query_arg( self::DISMISS_ACTION, '1', $redirect_to ),
			self::DISMISS_ACTION
		);
	}
}
