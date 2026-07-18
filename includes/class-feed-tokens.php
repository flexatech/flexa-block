<?php
declare(strict_types=1);
/**
 * Feed Tokens — secure server-side storage for the social-feed access tokens.
 *
 * Facebook / Instagram access tokens are secrets. Storing them in a block
 * attribute would persist them in `post_content` — visible to editors, in the DB,
 * in revisions and in exports. Instead they live in a single, NON-autoloaded site
 * option, settable only by administrators (`manage_options`) through a REST route.
 * The token never travels to the browser: the editor only ever learns whether a
 * service is "configured" (a boolean), and render.php / the preview endpoint read
 * the raw token server-side. So the token never reaches the front end, the block
 * markup, the REST content or an export.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Access-token store + admin REST for the social feeds.
 */
final class Feed_Tokens {

	const OPTION  = 'flexa_block_feed_tokens';
	const REST_NS = 'flexa-block/v1';

	/**
	 * Supported services.
	 *
	 * @var array<int, string>
	 */
	const SERVICES = [ 'facebook', 'instagram' ];

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'localize' ], 20 );
	}

	/**
	 * Read a service's stored token (server-side only — never sent to the browser).
	 *
	 * @param string $service Service key.
	 * @return string The token, or '' when unset / unknown service.
	 */
	public static function get( string $service ): string {
		if ( ! in_array( $service, self::SERVICES, true ) ) {
			return '';
		}
		$all = get_option( self::OPTION, [] );
		return is_array( $all ) && isset( $all[ $service ] ) ? (string) $all[ $service ] : '';
	}

	/**
	 * Whether a service has a token configured.
	 *
	 * @param string $service Service key.
	 * @return bool
	 */
	public static function is_configured( string $service ): bool {
		return '' !== self::get( $service );
	}

	/**
	 * Store (or, with an empty token, clear) a service's token. Not autoloaded.
	 *
	 * @param string $service Service key.
	 * @param string $token   Raw token ('' clears it).
	 */
	public static function set( string $service, string $token ): void {
		if ( ! in_array( $service, self::SERVICES, true ) ) {
			return;
		}
		$all = get_option( self::OPTION, [] );
		if ( ! is_array( $all ) ) {
			$all = [];
		}
		$token = trim( $token );
		if ( '' === $token ) {
			unset( $all[ $service ] );
		} else {
			$all[ $service ] = $token;
		}
		update_option( self::OPTION, $all, false );
	}

	/**
	 * Expose the configured status + capability to the editor (never the token).
	 */
	public static function localize(): void {
		$data = [
			'canManage'  => current_user_can( 'manage_options' ),
			'configured' => [
				'facebook'  => self::is_configured( 'facebook' ),
				'instagram' => self::is_configured( 'instagram' ),
			],
		];
		wp_add_inline_script( 'wp-blocks', 'window.flexaBlockFeedTokens = ' . wp_json_encode( $data ) . ';', 'before' );
	}

	/**
	 * Register the status (any editor) + save (admins only) routes.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NS,
			'/feed-token',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ __CLASS__, 'rest_status' ],
					'permission_callback' => static function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => [
						'service' => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ],
					],
				],
				[
					'methods'             => 'POST',
					'callback'            => [ __CLASS__, 'rest_save' ],
					'permission_callback' => static function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => [
						'service' => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ],
						// Sanitized in the handler (kept out of the schema so it can't be logged verbatim).
						'token'   => [ 'required' => true, 'type' => 'string' ],
					],
				],
			]
		);
	}

	/**
	 * Return only whether a service is configured — never the token itself.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_status( $request ) {
		$service = (string) $request->get_param( 'service' );
		if ( ! in_array( $service, self::SERVICES, true ) ) {
			return new \WP_Error( 'flexa_bad_service', __( 'Unknown feed service.', 'flexa-block' ), [ 'status' => 400 ] );
		}
		return rest_ensure_response( [ 'configured' => self::is_configured( $service ) ] );
	}

	/**
	 * Save (or clear) a service's token — administrators only.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_save( $request ) {
		$service = (string) $request->get_param( 'service' );
		if ( ! in_array( $service, self::SERVICES, true ) ) {
			return new \WP_Error( 'flexa_bad_service', __( 'Unknown feed service.', 'flexa-block' ), [ 'status' => 400 ] );
		}
		$token = sanitize_text_field( (string) $request->get_param( 'token' ) );
		self::set( $service, $token );
		return rest_ensure_response( [ 'configured' => self::is_configured( $service ) ] );
	}
}
