<?php
declare(strict_types=1);
/**
 * FormFlow suggestion on the WordPress Dashboard.
 *
 * A one-time introduction to Flexa FormFlow, our free form builder. It shows
 * only while FormFlow is not installed and only until the user turns it down;
 * the choice is stored, so the notice never comes back. Visibility and URLs
 * come from FormFlow_Promo, which the in-plugin banner reads as well.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and retires the Dashboard promo notice.
 */
final class FormFlow_Notice {

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'admin_init', [ __CLASS__, 'maybe_dismiss' ] );
		add_action( 'admin_notices', [ __CLASS__, 'render' ] );
	}

	/**
	 * Handle the dismissal link, then drop the action out of the address bar.
	 */
	public static function maybe_dismiss(): void {
		if ( empty( $_GET[ FormFlow_Promo::DISMISS_ACTION ] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, FormFlow_Promo::DISMISS_ACTION ) ) {
			return;
		}

		FormFlow_Promo::dismiss();

		wp_safe_redirect( admin_url( 'index.php' ) );
		exit;
	}

	/**
	 * Print the notice on the Dashboard.
	 */
	public static function render(): void {
		if ( ! self::should_render() ) {
			return;
		}

		$dismiss_url = FormFlow_Promo::dismiss_url( admin_url( 'index.php' ) );
		$install_url = FormFlow_Promo::install_url();

		self::print_styles();
		?>
		<div class="notice notice-info is-dismissible flexa-block-promo" data-dismiss-url="<?php echo esc_url( $dismiss_url ); ?>">
			<div class="flexa-block-promo__inner">
				<div class="flexa-block-promo__icon" aria-hidden="true">
					<img src="<?php echo esc_url( FormFlow_Promo::icon_url() ); ?>" width="64" height="64" alt="" decoding="async" />
				</div>
				<div class="flexa-block-promo__body">
					<h3 class="flexa-block-promo__title">
						<?php esc_html_e( 'Need a form on the pages you build with Flexa Block?', 'flexa-block' ); ?>
					</h3>
					<p class="flexa-block-promo__text">
						<?php esc_html_e( 'Flexa FormFlow is our free form builder, with a visual email designer and a workflow engine built in. Drag fields onto the canvas, design the emails each submission sends, then add conditional steps that run after it. Drop the finished form into any Flexa Block layout.', 'flexa-block' ); ?>
					</p>
					<p class="flexa-block-promo__actions">
						<a href="<?php echo esc_url( $dismiss_url ); ?>" class="button">
							<?php esc_html_e( 'Not interested', 'flexa-block' ); ?>
						</a>
						<a href="<?php echo esc_url( FormFlow_Promo::WPORG_URL ); ?>" class="button" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Learn more', 'flexa-block' ); ?>
						</a>
						<?php if ( '' !== $install_url ) : ?>
							<a href="<?php echo esc_url( $install_url ); ?>" class="button button-primary">
								<?php esc_html_e( 'Install Now', 'flexa-block' ); ?>
							</a>
						<?php endif; ?>
					</p>
				</div>
			</div>
		</div>
		<?php
		// Core's common.js injects the "x" button after this markup, so the
		// listener is delegated from the notice and matched with closest().
		wp_print_inline_script_tag(
			"( function () {
				var notice = document.querySelector( '.flexa-block-promo' );
				if ( ! notice ) {
					return;
				}
				notice.addEventListener( 'click', function ( event ) {
					if ( ! event.target.closest( '.notice-dismiss' ) ) {
						return;
					}
					var url = notice.getAttribute( 'data-dismiss-url' );
					if ( url ) {
						window.fetch( url, { credentials: 'same-origin' } );
					}
				} );
			} )();"
		);

		/**
		 * Claim the Dashboard slot so a sibling Flexa plugin running later on
		 * this same hook does not print a second copy of the pitch.
		 */
		do_action( FormFlow_Promo::RENDERED_ACTION );
	}

	/**
	 * Dashboard only, once per page load, on top of the shared visibility rules.
	 */
	private static function should_render(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen instanceof \WP_Screen || 'dashboard' !== $screen->id ) {
			return false;
		}

		// Another Flexa plugin already showed it on this page load.
		if ( did_action( FormFlow_Promo::RENDERED_ACTION ) > 0 ) {
			return false;
		}

		return FormFlow_Promo::should_show();
	}

	/**
	 * Inline styles. One notice does not justify a stylesheet.
	 */
	private static function print_styles(): void {
		?>
		<style>
			.flexa-block-promo { border-left-color: #0891b2; padding: 4px 12px; }
			.flexa-block-promo__inner { display: flex; gap: 16px; align-items: flex-start; padding: 12px 4px; }
			.flexa-block-promo__icon { flex: 0 0 auto; line-height: 0; }
			.flexa-block-promo__icon img { display: block; width: 64px; height: 64px; border-radius: 10px; }
			.flexa-block-promo__body { flex: 1 1 auto; min-width: 0; }
			.flexa-block-promo__title { margin: 0 0 6px; font-size: 15px; line-height: 1.4; }
			.flexa-block-promo__text { margin: 0 0 12px; max-width: 70em; }
			.flexa-block-promo__actions { margin: 0; display: flex; flex-wrap: wrap; gap: 8px; }
			@media screen and (max-width: 782px) {
				.flexa-block-promo__inner { gap: 12px; }
				.flexa-block-promo__icon img { width: 44px; height: 44px; }
			}
		</style>
		<?php
	}
}
