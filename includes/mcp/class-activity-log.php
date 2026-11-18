<?php
declare(strict_types=1);
/**
 * A short record of what agents did through this module.
 *
 * The question this answers is "what happened on my site", asked after the
 * fact by the person who owns it. An agent acts as a WordPress user, and a
 * page it drafted looks like a page anyone drafted; without a record there is
 * nothing to tell the two apart, and nothing to look at when a client
 * misbehaves.
 *
 * **What it records**: the time, the user, the ability, the post involved, the
 * outcome, and a request id that ties together the rows of one HTTP request.
 * **What it does not**: no content, no slot values, no titles, no prompt, no
 * token, no IP address, no user agent. Every call here is authenticated, so
 * the user id already says who; the rest would be a copy of the site's own
 * content sitting in an option, and a privacy question nobody asked for.
 *
 * It is not a rollback mechanism and must not be described as one. A row says
 * a draft was created; it holds nothing that could recreate or undo it.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Records and keeps the module's activity rows.
 *
 * **Why it is not a duplicate of the adapter's telemetry.** MCP Adapter 0.7
 * records one `mcp.request` event per request, with the method, the transport,
 * the argument *keys*, and a duration. Three things keep that from answering
 * this question. Its default handler is the null one, so unless a site wired
 * up its own, nothing is recorded at all. The tool name it records is
 * `mcp-adapter-execute-ability`, the meta-tool, so it cannot say which ability
 * ran. And it only sees calls that came through MCP, while an ability is also
 * reachable over the core abilities REST route and from PHP.
 *
 * **Storage is an option, not a table.** This plugin has no table and no
 * migration machinery, and adding both for a log of a few hundred rows would
 * be the largest structural change in the module. The cost is honest: the
 * option is read, modified and written, so two MCP requests landing in the
 * same instant can lose a row, and a site that needs a guaranteed trail
 * should hook {@see Activity_Log::HOOK} and write its own. The ceiling on
 * entries and the retention sweep keep the option from growing without end.
 *
 * **Where this class lives is a decision, not an accident.** Recording only
 * happens when the module is on, because the abilities only exist then, and
 * {@see Activity_Log::watch()} is called from inside that gate. Storage,
 * retention and reading sit outside it, loaded next to the panel, because the
 * moment someone most wants to read this log is right after they switched the
 * module off, and because rows left behind by a module that is now off still
 * have to age out.
 */
class Activity_Log {

	/** Option holding the rows, newest last. Never autoloaded. */
	const OPTION = 'flexa_block_mcp_log';

	/**
	 * Action fired once per recorded call, with the entry as its argument.
	 *
	 * Storage is a listener on this, not a method call, so a site can send the
	 * same events somewhere durable (a table, syslog, a webhook) by adding its
	 * own listener, and can switch ours off with the filter below while
	 * keeping its own.
	 */
	const HOOK = 'flexa_block_mcp_activity';

	/** Filter that turns the option-backed storage off, leaving the hook live. */
	const ENABLED_FILTER = 'flexa_block_mcp_log_enabled';

	/** Filter over how many days of rows are kept. */
	const RETENTION_FILTER = 'flexa_block_mcp_log_retention_days';

	/** Daily cleanup event. */
	const GC_HOOK = 'flexa_block_mcp_log_gc';

	/**
	 * Hard ceiling on rows, enforced on every write.
	 *
	 * The limits in {@see Request_Limits} put a caller's ceiling at 130 calls
	 * an hour, so this is a few days of one busy client and a long time for an
	 * ordinary site. It is a ceiling on the option's size as much as on its
	 * age: 500 rows is around 60 KB of serialised data, which is a reasonable
	 * thing to read on an admin screen and a poor thing to let grow.
	 */
	const MAX_ENTRIES = 500;

	/** Days of rows kept by the daily sweep. */
	const RETENTION_DAYS = 30;

	/** The call ran and returned a result. */
	const OUTCOME_OK = 'ok';

	/** This module refused the call, and `code` says which refusal it was. */
	const OUTCOME_REFUSED = 'refused';

	/** The caller lacked the capability the ability asks for. */
	const OUTCOME_DENIED = 'denied';

	/** The call ended without a result this module recognises. */
	const OUTCOME_ERROR = 'error';

	/**
	 * Correlation id for this request, generated on first use.
	 *
	 * @var string
	 */
	private static $request = '';

	/**
	 * Invocations seen but not yet resolved, innermost last.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private static $open = [];

	/**
	 * Register storage, retention and cleanup.
	 *
	 * Deliberately not gated on the module being on. See the file docblock.
	 */
	public static function init(): void {
		add_action( self::HOOK, [ __CLASS__, 'store' ], 10, 1 );
		add_action( self::GC_HOOK, [ __CLASS__, 'sweep' ] );
	}

	/**
	 * Start watching ability calls.
	 *
	 * Called from the gated runtime. Four core hooks rather than wrappers
	 * around our own callbacks, because the callbacks are only one of the
	 * outcomes: an input that fails its schema and a caller without the
	 * capability both never reach them, and those are the two outcomes most
	 * worth having a record of.
	 *
	 * One row per invocation. The first of the three resolving hooks to fire
	 * wins, and `wp_ability_invoked` is what tells us an invocation is in
	 * flight at all, which is how a permission check during a plain listing is
	 * told apart from one during a real call.
	 */
	public static function watch(): void {
		add_action( 'wp_ability_invoked', [ __CLASS__, 'on_invoked' ], 10, 2 );
		add_filter( 'wp_ability_validate_input', [ __CLASS__, 'on_validated' ], 10, 3 );
		add_filter( 'wp_ability_permission_result', [ __CLASS__, 'on_permission' ], 10, 2 );
		add_filter( 'wp_ability_execute_result', [ __CLASS__, 'on_result' ], 10, 2 );
		add_action( 'shutdown', [ __CLASS__, 'flush' ] );
	}

	/**
	 * Note that one of our abilities was invoked.
	 *
	 * @param string $name  Ability name.
	 * @param mixed  $input Raw input, before normalisation.
	 * @return void
	 */
	public static function on_invoked( $name, $input = null ): void {
		if ( ! is_string( $name ) || ! MCP_Manager::is_own_name( $name ) ) {
			return;
		}

		self::$open[] = [
			'ability' => $name,
			'post'    => self::post_from( $input ),
		];
	}

	/**
	 * Record an input that failed its schema.
	 *
	 * @param mixed  $validity True, or a WP_Error describing the failure.
	 * @param mixed  $input    Normalised input.
	 * @param string $name     Ability name.
	 * @return mixed The validity, untouched.
	 */
	public static function on_validated( $validity, $input = null, $name = '' ) {
		if ( is_wp_error( $validity ) && is_string( $name ) ) {
			self::resolve( $name, self::OUTCOME_REFUSED, $validity->get_error_code(), self::post_from( $input ) );
		}

		return $validity;
	}

	/**
	 * Record a call refused for want of a capability.
	 *
	 * This filter also runs when something merely lists the abilities a user
	 * may call, which is not an attempt at anything and must not fill the log.
	 * Only a check that happens inside an invocation is recorded.
	 *
	 * @param mixed  $permission Permission result.
	 * @param string $name       Ability name.
	 * @return mixed The permission result, untouched.
	 */
	public static function on_permission( $permission, $name = '' ) {
		if ( true !== $permission && is_string( $name ) && self::is_open( $name ) ) {
			$code = is_wp_error( $permission ) ? $permission->get_error_code() : 'ability_invalid_permissions';
			self::resolve( $name, self::OUTCOME_DENIED, $code );
		}

		return $permission;
	}

	/**
	 * Record what the ability's own callback returned.
	 *
	 * Output validation runs after this filter, so a response the client never
	 * received because it failed its own output schema is recorded here as a
	 * result. That is the honest reading: the callback did produce one.
	 *
	 * @param mixed  $result Callback result.
	 * @param string $name   Ability name.
	 * @return mixed The result, untouched.
	 */
	public static function on_result( $result, $name = '' ) {
		if ( ! is_string( $name ) || ! self::is_open( $name ) ) {
			return $result;
		}

		if ( is_wp_error( $result ) ) {
			self::resolve( $name, self::OUTCOME_REFUSED, $result->get_error_code() );

			return $result;
		}

		$post = is_array( $result ) && isset( $result['post_id'] ) ? (int) $result['post_id'] : 0;
		self::resolve( $name, self::OUTCOME_OK, '', $post );

		return $result;
	}

	/**
	 * Record anything invoked that none of the three hooks above resolved.
	 *
	 * An input core refuses before validation, a short circuit from another
	 * plugin, or a fatal further down all end up here. A row saying a call was
	 * made and did not finish is worth more than no row, and this is also what
	 * keeps a half-recorded invocation from attaching itself to the next one.
	 *
	 * @return void
	 */
	public static function flush(): void {
		while ( self::$open ) {
			$open = array_pop( self::$open );
			self::write(
				(string) $open['ability'],
				self::OUTCOME_ERROR,
				'no_result',
				(int) $open['post']
			);
		}
	}

	/**
	 * Store one entry, unless the site turned storage off.
	 *
	 * Nothing here may interrupt the call being recorded, so every failure is
	 * swallowed: a log that cannot be written is a worse outcome than a log
	 * that is missing a row, and much better than a draft that was not saved.
	 *
	 * @param mixed $entry Entry from {@see self::HOOK}.
	 * @return void
	 */
	public static function store( $entry ): void {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- Prefixed name, held in a constant.
		if ( ! is_array( $entry ) || ! (bool) apply_filters( self::ENABLED_FILTER, true, $entry ) ) {
			return;
		}

		try {
			$rows   = self::rows();
			$rows[] = $entry;

			update_option( self::OPTION, self::trim( $rows ), false );

			// The sweep is scheduled by the first row rather than at boot. A
			// site that never turns the module on has nothing to sweep, and an
			// update that leaves a daily event behind on such a site is exactly
			// the kind of change an update is not supposed to make.
			self::ensure_scheduled();
		} catch ( \Throwable $e ) {
			return;
		}
	}

	/**
	 * Rows, newest first, for a screen that shows them.
	 *
	 * @param int $limit Most rows to return.
	 * @return array<int, array<string, mixed>>
	 */
	public static function entries( int $limit = 50 ): array {
		$rows = array_reverse( self::rows() );

		return $limit > 0 ? array_slice( $rows, 0, $limit ) : $rows;
	}

	/**
	 * Cron entry point for the sweep.
	 *
	 * Thin, because `collect()` reports how many rows it dropped and an action
	 * callback is expected to return nothing.
	 *
	 * @return void
	 */
	public static function sweep(): void {
		self::collect();
	}

	/**
	 * Drop rows past the retention window.
	 *
	 * @return int Rows removed.
	 */
	public static function collect(): int {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- Prefixed name, held in a constant.
		$days = (int) apply_filters( self::RETENTION_FILTER, self::RETENTION_DAYS );
		$rows = self::rows();

		if ( $days <= 0 || ! $rows ) {
			return 0;
		}

		$cutoff = time() - ( $days * DAY_IN_SECONDS );
		$kept   = array_values(
			array_filter(
				$rows,
				static function ( $row ) use ( $cutoff ) {
					return isset( $row['time'] ) && (int) $row['time'] >= $cutoff;
				}
			)
		);

		$removed = count( $rows ) - count( $kept );
		if ( $removed > 0 ) {
			update_option( self::OPTION, $kept, false );
		}

		// Nothing left to sweep. The event goes away with the last row, which
		// is what lets the log outlive the switch without the event outliving
		// the log. A later call schedules it again.
		if ( ! $kept ) {
			self::unschedule();
		}

		return $removed;
	}

	/**
	 * Schedule the daily sweep if it is not already scheduled.
	 *
	 * Five minutes out rather than now, so a sweep never lands on the same
	 * request that is still booting the module.
	 *
	 * Called when a row is written, not when the module boots, so the event
	 * exists only on a site that has something to sweep.
	 *
	 * @return void
	 */
	public static function ensure_scheduled(): void {
		if ( ! wp_next_scheduled( self::GC_HOOK ) ) {
			wp_schedule_event( time() + 300, 'daily', self::GC_HOOK );
		}
	}

	/** Remove the daily sweep. Used on deactivation and once the log is empty. */
	public static function unschedule(): void {
		$next = wp_next_scheduled( self::GC_HOOK );
		if ( $next ) {
			wp_unschedule_event( (int) $next, self::GC_HOOK );
		}
	}

	/**
	 * How long rows are kept, for a screen that wants to say so.
	 *
	 * @return int Days, or 0 for no limit.
	 */
	public static function retention_days(): int {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- Prefixed name, held in a constant.
		return max( 0, (int) apply_filters( self::RETENTION_FILTER, self::RETENTION_DAYS ) );
	}

	/**
	 * This request's correlation id.
	 *
	 * Lazy, and cached for the request, which is all the scope it needs: each
	 * request is its own PHP process. Not the same thing as the JSON-RPC id
	 * the adapter logs, which is the client's numbering of its own messages.
	 *
	 * @return string
	 */
	public static function request_id(): string {
		if ( '' === self::$request ) {
			try {
				self::$request = bin2hex( random_bytes( 8 ) );
			} catch ( \Throwable $e ) {
				self::$request = substr( md5( (string) wp_rand() . microtime() ), 0, 16 );
			}
		}

		return self::$request;
	}

	/**
	 * Whether an invocation of this ability is in flight.
	 *
	 * @param string $name Ability name.
	 * @return bool
	 */
	private static function is_open( string $name ): bool {
		foreach ( self::$open as $open ) {
			if ( $open['ability'] === $name ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Close the innermost open invocation of an ability and record it.
	 *
	 * @param string $name    Ability name.
	 * @param string $outcome One of the OUTCOME_* constants.
	 * @param string $code    Error code, or empty.
	 * @param int    $post    Post involved, or 0.
	 * @return void
	 */
	private static function resolve( string $name, string $outcome, string $code = '', int $post = 0 ): void {
		for ( $i = count( self::$open ) - 1; $i >= 0; $i-- ) {
			if ( self::$open[ $i ]['ability'] !== $name ) {
				continue;
			}

			if ( 0 === $post ) {
				$post = (int) self::$open[ $i ]['post'];
			}

			array_splice( self::$open, $i, 1 );
			self::write( $name, $outcome, $code, $post );

			return;
		}
	}

	/**
	 * Build an entry and hand it to the hook.
	 *
	 * @param string $name    Ability name.
	 * @param string $outcome One of the OUTCOME_* constants.
	 * @param string $code    Error code, or empty.
	 * @param int    $post    Post involved, or 0.
	 * @return void
	 */
	private static function write( string $name, string $outcome, string $code, int $post ): void {
		$user = wp_get_current_user();

		/**
		 * Fires once for each ability call this module handled.
		 *
		 * @param array<string, mixed> $entry The recorded entry.
		 */
		do_action(
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- Prefixed name, held in a constant.
			self::HOOK,
			[
				'time'    => time(),
				'user'    => (int) $user->ID,
				// Snapshotted, so a row still names someone after the account
				// is deleted. The screen prefers a live display name and falls
				// back to this.
				'login'   => substr( (string) $user->user_login, 0, 60 ),
				'ability' => substr( $name, 0, 80 ),
				'post'    => $post,
				'outcome' => $outcome,
				'code'    => substr( $code, 0, 60 ),
				'request' => self::request_id(),
			]
		);
	}

	/**
	 * Stored rows, oldest first.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function rows(): array {
		$stored = get_option( self::OPTION, [] );

		return is_array( $stored ) ? array_values( array_filter( $stored, 'is_array' ) ) : [];
	}

	/**
	 * Apply both ceilings: the row count, and the retention window.
	 *
	 * Age is enforced here as well as in the daily sweep, because a site with
	 * cron disabled still deserves a log that forgets.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows, oldest first.
	 * @return array<int, array<string, mixed>>
	 */
	private static function trim( array $rows ): array {
		$days = self::retention_days();

		if ( $days > 0 ) {
			$cutoff = time() - ( $days * DAY_IN_SECONDS );
			$rows   = array_filter(
				$rows,
				static function ( $row ) use ( $cutoff ) {
					return isset( $row['time'] ) && (int) $row['time'] >= $cutoff;
				}
			);
		}

		$rows = array_values( $rows );

		return count( $rows ) > self::MAX_ENTRIES
			? array_slice( $rows, count( $rows ) - self::MAX_ENTRIES )
			: $rows;
	}

	/**
	 * The post an input is about, when it names one.
	 *
	 * @param mixed $input Ability input.
	 * @return int
	 */
	private static function post_from( $input ): int {
		return is_array( $input ) && isset( $input['post_id'] ) ? max( 0, (int) $input['post_id'] ) : 0;
	}
}
