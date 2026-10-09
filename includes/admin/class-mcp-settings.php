<?php
declare(strict_types=1);
/**
 * MCP module settings: the switch, its guard rails, and its REST route.
 *
 * Deliberately kept out of `flexa_block_settings`. Three concrete reasons, each
 * of them something that would misbehave if this rode along in the main option:
 *
 *  - `Admin::save_settings()` drops every post's cached CSS on save. A switch
 *    that has nothing to do with CSS should not cost a site-wide regeneration.
 *  - The dashboard auto-saves the main settings on a 700ms debounce. Opening a
 *    path that external agents can reach has to be a deliberate act, not a side
 *    effect of nudging a slider two panels away.
 *  - That handler rewrites the whole option array, so a payload which did not
 *    know about an MCP key could silently flip it either way.
 *
 * Loaded for every request that can read or change the switch (admin screens,
 * the REST API, WP-CLI) and for no other. A plain page view on a site that
 * never opted in loads neither this file nor anything under `includes/mcp/`.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores and gates the MCP module switch.
 */
class MCP_Settings {

	/** Option holding the module's own settings. */
	const OPTION_NAME = 'flexa_block_mcp';

	/** REST namespace, shared with the rest of the plugin. */
	const REST_NS = 'flexa-block/v1';

	/**
	 * Lowest WordPress version on which the module may be switched on.
	 *
	 * The Abilities API itself landed in 6.9. The floor here is higher on
	 * purpose: 7.0 is the first release where it is a settled, documented part
	 * of core rather than something a site might have in a half-finished state,
	 * and supporting the 6.9 shape as well buys nobody anything.
	 */
	const MIN_WP = '7.0';

	/** WP.org directory slug of the plugin that owns the MCP server. */
	const ADAPTER_SLUG = 'mcp-adapter';

	/** Server ID the adapter gives the server it builds for itself. */
	const ADAPTER_DEFAULT_SERVER = 'mcp-adapter-default-server';

	/**
	 * Route of that server, as the adapter's 0.7.x defaults set it.
	 *
	 * Namespace and route, the two halves `DefaultServerFactory` passes to
	 * `register_rest_route()`. Only a fallback: see `endpoint()`.
	 */
	const ADAPTER_DEFAULT_ROUTE = 'mcp/mcp-adapter-default-server';

	/**
	 * Default settings. Off, and read-only when it is first switched on.
	 *
	 * @var array<string, bool>
	 */
	const DEFAULTS = [
		'enabled' => false,
		'read'    => true,
		'write'   => false,
	];

	/**
	 * Register hooks.
	 *
	 * The REST branch covers being loaded from inside `rest_api_init` itself:
	 * adding a callback to the hook that is currently firing is not something
	 * to rely on, so in that case the routes go up directly.
	 */
	public static function init(): void {
		add_filter( 'pre_update_option_' . self::OPTION_NAME, [ __CLASS__, 'guard_write' ], 10, 2 );

		if ( doing_action( 'rest_api_init' ) ) {
			self::register_routes();
		} else {
			add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
		}
	}

	/* ---------------------------------------------------------------------
	 * State
	 * ------------------------------------------------------------------ */

	/**
	 * Whether this WordPress is new enough for the module to be switched on.
	 *
	 * Checks for the API as well as the version. A site can sit on a release
	 * numbered high enough while something has removed or replaced the
	 * functions, and in that case the module has nothing to register into.
	 *
	 * @return bool
	 */
	public static function is_supported(): bool {
		return version_compare( (string) get_bloginfo( 'version' ), self::MIN_WP, '>=' )
			&& function_exists( 'wp_register_ability' );
	}

	/**
	 * Stored settings, merged over the defaults.
	 *
	 * Returns what is stored, not what is in force: `enabled` here can be true
	 * on a site that has since moved to an older WordPress. Use is_enabled()
	 * for the question "is the module running".
	 *
	 * @return array{enabled: bool, read: bool, write: bool}
	 */
	public static function get_settings(): array {
		$stored = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		return [
			'enabled' => isset( $stored['enabled'] ) ? (bool) $stored['enabled'] : self::DEFAULTS['enabled'],
			'read'    => isset( $stored['read'] ) ? (bool) $stored['read'] : self::DEFAULTS['read'],
			'write'   => isset( $stored['write'] ) ? (bool) $stored['write'] : self::DEFAULTS['write'],
		];
	}

	/**
	 * Whether the module is on *and* this site can run it.
	 *
	 * The stored flag is never trusted on its own: a site can switch MCP on
	 * under 7.0 and then restore a 6.9 backup, which leaves a true flag and no
	 * Abilities API.
	 *
	 * @return bool
	 */
	public static function is_enabled(): bool {
		return self::is_supported() && self::get_settings()['enabled'];
	}

	/**
	 * Whether read abilities may be exposed.
	 *
	 * @return bool
	 */
	public static function allows_read(): bool {
		return self::is_enabled() && self::get_settings()['read'];
	}

	/**
	 * Whether write abilities may be exposed.
	 *
	 * @return bool
	 */
	public static function allows_write(): bool {
		return self::is_enabled() && self::get_settings()['write'];
	}

	/**
	 * Whether the MCP Adapter plugin is active, and which version.
	 *
	 * Detected from the active-plugins list by directory name rather than by
	 * sniffing a class: the WP.org slug is a published, stable contract, the
	 * adapter's internals are not, and a wrong class name would report "not
	 * installed" on a site that has it.
	 *
	 * @return array{active: bool, version: string}
	 */
	public static function adapter_state(): array {
		$file    = self::adapter_plugin_file();
		$version = '';

		if ( '' !== $file && function_exists( 'get_plugin_data' ) ) {
			$data    = get_plugin_data( WP_PLUGIN_DIR . '/' . $file, false, false );
			$version = (string) $data['Version'];
		}

		return [
			'active'  => '' !== $file,
			'version' => $version,
		];
	}

	/**
	 * Plugin file of the active MCP Adapter, or '' when it is not active.
	 *
	 * @return string
	 */
	private static function adapter_plugin_file(): string {
		$active = (array) get_option( 'active_plugins', [] );

		if ( is_multisite() ) {
			$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', [] ) ) );
		}

		foreach ( $active as $file ) {
			$file = (string) $file;
			if ( self::ADAPTER_SLUG === dirname( $file ) ) {
				return $file;
			}
		}

		return '';
	}

	/**
	 * Public MCP endpoint URL, or '' when the site has none to show.
	 *
	 * Which of two sources answers depends on what the request can see, and
	 * the difference is not cosmetic. Outside WP-CLI the adapter builds its
	 * servers on `rest_api_init` only, so during an ordinary admin page render
	 * no server object exists yet, and instantiating the adapter early just to
	 * read a route would be a side effect in the wrong request.
	 *
	 *  - Inside a REST request, once `mcp_adapter_init` has fired, the route is
	 *    read off the server itself. This is the only answer that stays true on
	 *    a site filtering `mcp_adapter_default_server_config`, and it correctly
	 *    reports nothing on one that turned the default server off through
	 *    `mcp_adapter_create_default_server`.
	 *  - Anywhere else, fall back to the route the adapter documents for 0.7.x.
	 *    The panel re-reads this payload from the REST response on every save,
	 *    so a site that moved its route sees the real value as soon as it
	 *    touches a toggle.
	 *
	 * Checked against mcp-adapter 0.7.0 on WordPress 7.1.3: the route is live
	 * at `/wp-json/mcp/mcp-adapter-default-server` and answers 401 to an
	 * unauthenticated request.
	 *
	 * @return string
	 */
	public static function endpoint(): string {
		$url = '';

		if ( self::adapter_state()['active'] ) {
			$url = esc_url_raw( rest_url( self::ADAPTER_DEFAULT_ROUTE ) );

			$server = self::adapter_default_server();
			if ( null !== $server ) {
				$url = esc_url_raw(
					rest_url( $server->get_server_route_namespace() . '/' . $server->get_server_route() )
				);
			} elseif ( did_action( 'mcp_adapter_init' ) ) {
				$url = '';
			}
		}

		/**
		 * Filters the MCP endpoint URL shown in the dashboard.
		 *
		 * @param string $endpoint Endpoint URL, or '' when there is none.
		 */
		return (string) apply_filters( 'flexa_block_mcp_endpoint', $url );
	}

	/**
	 * The adapter's own default server, or null when it is not there to ask.
	 *
	 * Null covers two different situations on purpose, and the caller tells
	 * them apart by whether `mcp_adapter_init` has fired: before it, the server
	 * is merely not built yet; after it, the site really has no default server.
	 *
	 * @return \WP\MCP\Core\McpServer|null
	 */
	private static function adapter_default_server() {
		if ( ! did_action( 'mcp_adapter_init' ) || ! class_exists( '\WP\MCP\Core\McpAdapter' ) ) {
			return null;
		}

		return \WP\MCP\Core\McpAdapter::instance()->get_server( self::ADAPTER_DEFAULT_SERVER );
	}

	/**
	 * Everything the dashboard panel needs, including when the module is off.
	 *
	 * @return array<string, mixed>
	 */
	public static function boot_payload(): array {
		$settings = self::get_settings();

		return [
			'supported'    => self::is_supported(),
			'minWp'        => self::MIN_WP,
			'enabled'      => self::is_enabled(),
			'read'         => $settings['read'],
			'write'        => $settings['write'],
			'adapter'      => self::adapter_state(),
			'endpoint'     => self::endpoint(),
			'restUrl'      => esc_url_raw( rest_url( self::REST_NS . '/mcp' ) ),
			'activity'     => self::activity_rows(),
			'activityDays' => self::activity_retention(),
		];
	}

	/**
	 * The module's activity log, ready for the panel to render.
	 *
	 * Sent whether the module is on or off. A log is most wanted right after
	 * someone switches the module off, and a panel that hid it then would be
	 * answering the question nobody asks.
	 *
	 * The user is resolved here rather than in the browser: a row keeps a user
	 * ID and a snapshot of the login, and turning that into a name someone
	 * recognises is a lookup, not a formatting choice. The timestamp becomes a
	 * string here too, so it follows the site's timezone and date format
	 * instead of the reader's browser. Everything else passes through as the
	 * log stored it.
	 *
	 * @param int $limit Rows to return, newest first.
	 * @return array<int, array<string, mixed>>
	 */
	public static function activity_rows( int $limit = 20 ): array {
		if ( ! class_exists( '\Flexa\Block\MCP\Activity_Log' ) ) {
			return [];
		}

		$format = get_option( 'date_format', 'Y-m-d' ) . ' ' . get_option( 'time_format', 'H:i' );
		$rows   = [];

		foreach ( \Flexa\Block\MCP\Activity_Log::entries( $limit ) as $row ) {
			$time = isset( $row['time'] ) ? (int) $row['time'] : 0;

			$rows[] = [
				'time'    => $time,
				'when'    => $time ? (string) wp_date( $format, $time ) : '',
				'user'    => self::activity_user( $row ),
				'ability' => (string) ( $row['ability'] ?? '' ),
				'post'    => (int) ( $row['post'] ?? 0 ),
				'outcome' => (string) ( $row['outcome'] ?? '' ),
				'code'    => (string) ( $row['code'] ?? '' ),
				'request' => (string) ( $row['request'] ?? '' ),
			];
		}

		return $rows;
	}

	/**
	 * How long a log row is kept, in days.
	 *
	 * @return int Days, or 0 when only the row limit drops them.
	 */
	public static function activity_retention(): int {
		return class_exists( '\Flexa\Block\MCP\Activity_Log' )
			? \Flexa\Block\MCP\Activity_Log::retention_days()
			: 0;
	}

	/**
	 * A name for whoever made one logged call.
	 *
	 * Three sources, in the order of how much they tell the reader: the
	 * account's current display name, the login snapshotted when the row was
	 * written (which survives the account being deleted), and the bare ID. A
	 * row with no user is a call that arrived without a signed-in account,
	 * which the abilities refuse; it is logged because a refusal is worth
	 * seeing.
	 *
	 * @param array<string, mixed> $row Stored row.
	 * @return string
	 */
	private static function activity_user( array $row ): string {
		$id = isset( $row['user'] ) ? (int) $row['user'] : 0;

		if ( $id < 1 ) {
			return __( 'Signed out', 'flexa-block' );
		}

		$user = get_userdata( $id );

		if ( $user && '' !== (string) $user->display_name ) {
			return (string) $user->display_name;
		}

		$login = isset( $row['login'] ) ? (string) $row['login'] : '';

		return '' !== $login ? $login : '#' . $id;
	}

	/* ---------------------------------------------------------------------
	 * Writing
	 * ------------------------------------------------------------------ */

	/**
	 * Sanitize and store a partial update.
	 *
	 * Absent keys keep their stored value, so saving one toggle never clears
	 * the others. No CSS cache is touched: nothing here changes generated CSS.
	 *
	 * @param array<string, mixed> $input Raw settings.
	 * @return array{enabled: bool, read: bool, write: bool} The stored settings.
	 */
	public static function save_settings( array $input ): array {
		$current = self::get_settings();

		$sanitized = [
			'enabled' => array_key_exists( 'enabled', $input ) ? (bool) $input['enabled'] : $current['enabled'],
			'read'    => array_key_exists( 'read', $input ) ? (bool) $input['read'] : $current['read'],
			'write'   => array_key_exists( 'write', $input ) ? (bool) $input['write'] : $current['write'],
		];

		update_option( self::OPTION_NAME, $sanitized );

		// Read back rather than returning what we sent: guard_write() may have
		// turned `enabled` down, and the caller should hear about that.
		return self::get_settings();
	}

	/**
	 * Refuse to store an enabled flag on a WordPress without the Abilities API.
	 *
	 * This sits on the option's own write filter rather than inside
	 * save_settings(). A sanitizer only sees what comes through the one
	 * function; this filter sees every writer: our REST route, `wp option
	 * update`, a migration script, a settings payload restored from another
	 * site. Whatever the caller sends, `enabled` cannot become true below the
	 * floor, and the value stored stays consistent with what the runtime gate
	 * in `flexa-block.php` will decide on the next request.
	 *
	 * @param mixed $value     Incoming option value.
	 * @param mixed $old_value Stored option value.
	 * @return mixed
	 */
	public static function guard_write( $value, $old_value ) {
		unset( $old_value );

		if ( ! is_array( $value ) || empty( $value['enabled'] ) || self::is_supported() ) {
			return $value;
		}

		$value['enabled'] = false;

		return $value;
	}

	/* ---------------------------------------------------------------------
	 * REST API
	 * ------------------------------------------------------------------ */

	/**
	 * Register the module's own route.
	 */
	public static function register_routes(): void {
		$args = [
			'enabled' => [
				'type'              => 'boolean',
				'required'          => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			'read'    => [
				'type'              => 'boolean',
				'required'          => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
			'write'   => [
				'type'              => 'boolean',
				'required'          => false,
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
		];

		register_rest_route(
			self::REST_NS,
			'/mcp',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ __CLASS__, 'rest_get' ],
					'permission_callback' => [ __CLASS__, 'rest_permission' ],
				],
				[
					'methods'             => 'POST',
					'callback'            => [ __CLASS__, 'rest_save' ],
					'permission_callback' => [ __CLASS__, 'rest_permission' ],
					'args'                => $args,
				],
			]
		);
	}

	/**
	 * Only administrators may read or change the switch.
	 *
	 * Written out here rather than delegating to `Admin::rest_permission()`.
	 * The two happen to agree today, and if the MCP module ever moves to a
	 * plugin of its own this file travels without having to be edited.
	 *
	 * @return bool
	 */
	public static function rest_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET handler.
	 *
	 * @return \WP_REST_Response
	 */
	public static function rest_get(): \WP_REST_Response {
		return rest_ensure_response( [ 'mcp' => self::boot_payload() ] );
	}

	/**
	 * POST handler.
	 *
	 * Turning the module on below the floor is an error the caller hears
	 * about, rather than a save that quietly stores something else.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_save( \WP_REST_Request $request ) {
		$input = [];
		foreach ( [ 'enabled', 'read', 'write' ] as $key ) {
			if ( null !== $request->get_param( $key ) ) {
				$input[ $key ] = (bool) $request->get_param( $key );
			}
		}

		if ( ! empty( $input['enabled'] ) && ! self::is_supported() ) {
			return new \WP_Error(
				'flexa_block_mcp_unsupported',
				sprintf(
					/* translators: %s: minimum WordPress version */
					__( 'The MCP module needs WordPress %s or newer, which provides the Abilities API.', 'flexa-block' ),
					self::MIN_WP
				),
				[ 'status' => 400 ]
			);
		}

		self::save_settings( $input );

		return rest_ensure_response(
			[
				'saved' => true,
				'mcp'   => self::boot_payload(),
			]
		);
	}
}
