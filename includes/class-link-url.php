<?php
/**
 * The one rule for what counts as an acceptable link address.
 *
 * @package Flexa\Block
 */

declare(strict_types=1);

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates a web address typed by a person or sent by an agent.
 *
 * Shared by the front-end editor and the MCP module, so a link is accepted or
 * refused by the same rule wherever it comes from. Accepted: http, https,
 * mailto and tel addresses, and addresses with no scheme at all (/contact,
 * #section, ?page=2, //cdn.example.com/x). Refused: every other scheme
 * (javascript:, data:, vbscript:, file:, ...), however it is disguised.
 */
final class Link_Url {

	const MAX_LENGTH = 2048;
	const PROTOCOLS  = [ 'http', 'https', 'mailto', 'tel' ];

	/**
	 * Check and normalise a link address.
	 *
	 * @param string $raw Address as typed.
	 * @return string|\WP_Error The address to store ('' for "no link"), or an error saying why not.
	 */
	public static function clean( string $raw ) {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '';
		}

		if ( mb_strlen( $raw ) > self::MAX_LENGTH ) {
			return self::refuse( 'flexa_link_too_long', __( 'That web address is too long.', 'flexa-block' ) );
		}

		// Look at it the way a browser would. Browsers drop tabs and newlines
		// inside a scheme and read character references, so `java&#x0A;script:`
		// and `jav ascript:` are `javascript:` to them. Decode first (twice: an
		// entity can encode another), then judge what is left.
		$seen = html_entity_decode( html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		if ( preg_match( '/[\x00-\x1F\x7F]/', $seen ) ) {
			return self::refuse( 'flexa_link_invalid', __( 'That web address contains characters that are not allowed.', 'flexa-block' ) );
		}

		// A colon before the first / ? or # makes what precedes it a scheme, to a
		// browser and to WordPress alike. Only the four allowed ones may be there:
		// that also catches schemes with a space in them ("java script:") and a
		// bare "host:port", which a person should write with http://.
		if ( preg_match( '~^[^/?#]*:~', $seen ) && ! preg_match( '~^(?:https?|mailto|tel):~i', $seen ) ) {
			return self::refuse(
				'flexa_link_scheme',
				__( 'Links can start with http://, https://, mailto: or tel:, or be a path such as /contact.', 'flexa-block' )
			);
		}

		$url = esc_url_raw( $raw, self::PROTOCOLS );
		if ( '' === $url ) {
			return self::refuse( 'flexa_link_invalid', __( 'That does not look like a web address.', 'flexa-block' ) );
		}

		return $url;
	}

	/**
	 * Build the error.
	 *
	 * @param string $code    Error code.
	 * @param string $message Message for the person.
	 */
	private static function refuse( string $code, string $message ): \WP_Error {
		return new \WP_Error( $code, $message, [ 'status' => 400 ] );
	}
}
