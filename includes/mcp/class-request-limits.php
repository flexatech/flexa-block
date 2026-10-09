<?php
declare(strict_types=1);
/**
 * Per-user ceilings on how often the module's abilities may be called.
 *
 * The transport in front of the abilities has no rate limit of its own, and an
 * agent is a program: a loop that retries on every error will retry as fast as
 * the network allows. Without a ceiling, one confused client can fill a site's
 * page list and its uploads table before anyone notices.
 *
 * Counted per user rather than per site. A rate limit shared across users would
 * let one editor's runaway client lock every other editor out, which turns a
 * safety measure into a denial of service with extra steps.
 *
 * The window is fixed, not sliding: a counter per clock hour, per user, per
 * ability. The known cost of that is a burst spanning two windows, so a caller
 * who starts at 10:59 can in theory get twice the hourly figure inside two
 * minutes. A sliding window would need the timestamp of every call kept
 * somewhere, and for a ceiling whose job is to stop a runaway loop rather than
 * to meter a paid API, the simpler structure is worth the looser edge.
 *
 * Counters live in transients, so on a site with a persistent object cache they
 * can be evicted under memory pressure. Eviction loses a count, which loosens
 * the limit; it never tightens it, and it never blocks a caller who is inside
 * the ceiling.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\MCP;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Counts ability calls against an hourly ceiling.
 */
class Request_Limits {

	/**
	 * Drafts one user may have this module create in an hour.
	 *
	 * Ten. A person laying out a site with an agent's help works a page at a
	 * time, looks at the result, and asks for the next one; ten an hour is more
	 * than that pace and far less than a loop's. The number is deliberately a
	 * figure a human workflow never reaches rather than a generous allowance:
	 * the point is to stop a client that has stopped making sense.
	 */
	const DRAFTS_PER_HOUR = 10;

	/**
	 * Discovery calls one user may make in an hour.
	 *
	 * Listing presets and reading a preset's schema cost a glob of the samples
	 * directory and nothing else, so this is set well above any sane client's
	 * needs. It exists because "cheap" is not "free", and a client stuck in a
	 * discovery loop should still hit something.
	 */
	const DISCOVERY_PER_HOUR = 120;

	/** Length of one counting window, in seconds. */
	const WINDOW = HOUR_IN_SECONDS;

	/** Transient prefix for a counter. */
	const KEY_PREFIX = 'flexa_mcp_rate_';

	/**
	 * Count one call, and say whether it was over the ceiling.
	 *
	 * The call is counted before it is run, not after it succeeds. An ability
	 * that fails has still cost the site the work of finding that out, and a
	 * limiter that only counts successes is no limiter at all against a client
	 * whose every call fails.
	 *
	 * @param string $ability Ability name, so one ceiling never spends another's.
	 * @param int    $limit   Calls allowed in one window.
	 * @return \WP_Error|null Error when the ceiling is reached, null otherwise.
	 */
	public static function tap( string $ability, int $limit ) {
		$key  = self::key( $ability );
		$used = (int) get_transient( $key );

		if ( $used >= $limit ) {
			$retry = self::window_ends() - time();

			return new \WP_Error(
				'flexa_mcp_rate_limited',
				sprintf(
					/* translators: 1: number of calls allowed, 2: number of minutes to wait */
					__( 'This has reached its limit of %1$s calls an hour. Try again in %2$s minutes.', 'flexa-block' ),
					number_format_i18n( $limit ),
					number_format_i18n( (int) ceil( max( 1, $retry ) / MINUTE_IN_SECONDS ) )
				),
				[
					'status'      => 429,
					'limit'       => $limit,
					'retry_after' => max( 1, $retry ),
				]
			);
		}

		// The remaining window, not a full one: a counter that renewed its own
		// expiry on every call would never reset for a caller who keeps
		// calling, and the ceiling would stop being hourly.
		set_transient( $key, $used + 1, max( 1, self::window_ends() - time() ) );

		return null;
	}

	/**
	 * Calls already counted against one ceiling in the current window.
	 *
	 * Reported to callers so a client can pace itself instead of discovering
	 * the ceiling by hitting it.
	 *
	 * @param string $ability Ability name.
	 * @return int
	 */
	public static function used( string $ability ): int {
		return (int) get_transient( self::key( $ability ) );
	}

	/**
	 * Transient name for one user's counter on one ability in this window.
	 *
	 * User ID 0 is unreachable in practice: every ability here requires a
	 * capability no logged-out visitor has, so a counter is only ever read
	 * after that check passed. If some future caller did arrive without a user,
	 * the shared bucket it would land in is the conservative direction.
	 *
	 * @param string $ability Ability name.
	 * @return string
	 */
	private static function key( string $ability ): string {
		$window = (int) floor( time() / self::WINDOW );

		return self::KEY_PREFIX . md5( get_current_user_id() . '|' . $ability . '|' . $window );
	}

	/**
	 * Unix time at which the current window closes.
	 *
	 * @return int
	 */
	private static function window_ends(): int {
		return ( (int) floor( time() / self::WINDOW ) + 1 ) * self::WINDOW;
	}
}
