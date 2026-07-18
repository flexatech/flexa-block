<?php
declare(strict_types=1);
/**
 * Remote Feed — cached JSON fetch shared by the social-feed blocks.
 *
 * The Facebook Feed and Instagram Feed blocks both fetch a JSON payload from an
 * external HTTP API and cache it in a transient so every page view does not hit
 * the network. That "wp_remote_get → decode JSON → cache" loop is identical for
 * both, so it lives here once (guide §4.5: the second block that needs it hoists
 * it rather than copying). Unlike Rss_Feed (which uses SimplePie via fetch_feed),
 * these APIs return JSON, so a plain wp_remote_get is the right tool.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cached remote-JSON helper.
 */
final class Remote_Feed {

	/**
	 * Fetch a URL and decode its JSON body, caching the decoded array in a
	 * transient for the requested number of minutes.
	 *
	 * @param string $url       Fully-built request URL (query string included).
	 * @param string $cache_key Transient key (caller derives it from the request).
	 * @param int    $cache_min Cache lifetime in minutes (clamped 1–1440).
	 * @return array<string, mixed>|\WP_Error Decoded body, or a WP_Error on failure.
	 */
	public static function get_json( string $url, string $cache_key, int $cache_min ) {
		$url = esc_url_raw( trim( $url ) );
		if ( '' === $url || ! preg_match( '#^https://#i', $url ) ) {
			return new \WP_Error( 'flexa_bad_url', __( 'The request URL is invalid.', 'flexa-block' ) );
		}

		$cache_min = max( 1, min( 1440, $cache_min ) );

		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			$url,
			[
				'timeout' => 15,
				'headers' => [ 'Accept' => 'application/json' ],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return new \WP_Error( 'flexa_bad_json', __( 'The service returned an unexpected response.', 'flexa-block' ) );
		}

		if ( 200 !== $code ) {
			// Surface the API's own error message when present (e.g. an expired token).
			$message = isset( $data['error']['message'] ) ? (string) $data['error']['message'] : __( 'The service returned an error.', 'flexa-block' );
			return new \WP_Error( 'flexa_http_error', $message, [ 'status' => $code ] );
		}

		set_transient( $cache_key, $data, $cache_min * MINUTE_IN_SECONDS );
		return $data;
	}

	/**
	 * Truncate a caption/message to a word limit, keeping it plain text.
	 *
	 * @param string $text  Raw text.
	 * @param int    $limit Max words (0 = unlimited).
	 * @return string
	 */
	public static function trim_words( string $text, int $limit ): string {
		$text = wp_strip_all_tags( html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) );
		if ( $limit <= 0 ) {
			return trim( $text );
		}
		return wp_trim_words( $text, $limit, '…' );
	}
}
