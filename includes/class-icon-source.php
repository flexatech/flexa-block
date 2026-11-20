<?php
/**
 * Turns a chosen icon into the icon data a block stores.
 *
 * @package Flexa\Block
 */

declare(strict_types=1);

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The one place that decides what a block stores for an icon chosen from the
 * front end.
 *
 * The block editor's picker writes the SVG markup in the browser. The front-end
 * editor never accepts SVG from the browser: it receives a source and a name
 * (or an attachment ID for an upload) and the markup is looked up in the
 * catalog generated at build time from the very same icons
 * (includes/icons/catalog.php), so a person can only ever put an icon from the
 * catalog, or a file from the media library, into a block.
 */
final class Icon_Source {

	/**
	 * The generated catalog, loaded on first use.
	 *
	 * @var array{builtin: array<string,string>, library: array<string,string>}|null
	 */
	private static $catalog = null;

	/**
	 * The catalog: source => name => markup.
	 *
	 * @return array{builtin: array<string,string>, library: array<string,string>}
	 */
	public static function catalog(): array {
		if ( null === self::$catalog ) {
			$file = __DIR__ . '/icons/catalog.php';
			$data = is_readable( $file ) ? require $file : [];

			/**
			 * Filters the icons the front-end editor offers and accepts.
			 *
			 * Each source maps an icon name to its SVG markup. Markup added here
			 * is trusted, so only add icons from code you control.
			 *
			 * @param array{builtin: array<string,string>, library: array<string,string>} $catalog Catalog.
			 */
			$data = apply_filters( 'flexa_block_inline_icon_catalog', is_array( $data ) ? $data : [] );

			self::$catalog = [
				'builtin' => is_array( $data['builtin'] ?? null ) ? $data['builtin'] : [],
				'library' => is_array( $data['library'] ?? null ) ? $data['library'] : [],
			];
		}
		return self::$catalog;
	}

	/**
	 * Forget the loaded catalog (tests).
	 */
	public static function reset(): void {
		self::$catalog = null;
	}

	/**
	 * Whether an icon in a block can be stored again from its source and name.
	 *
	 * @param string $source Icon source.
	 * @param string $name   Icon name.
	 * @param int    $id     Attachment ID, for an upload.
	 */
	public static function can_restore( string $source, string $name, int $id ): bool {
		if ( 'none' === $source || '' === $source ) {
			return true;
		}
		if ( 'upload' === $source ) {
			return $id > 0;
		}
		return isset( self::catalog()[ $source ][ $name ] );
	}

	/**
	 * Check a chosen icon and build what the block stores for it.
	 *
	 * @param string $source "builtin", "library", "upload" or "none".
	 * @param string $name   Icon name (builtin / library).
	 * @param int    $id     Attachment ID (upload).
	 * @return array{source:string,name:string,markup:string,url:string,id:?int}|\WP_Error
	 */
	public static function describe( string $source, string $name, int $id ) {
		if ( 'none' === $source ) {
			return [
				'source' => 'none',
				'name'   => '',
				'markup' => '',
				'url'    => '',
				'id'     => null,
			];
		}

		if ( 'builtin' === $source || 'library' === $source ) {
			$markup = self::catalog()[ $source ][ $name ] ?? null;
			if ( ! is_string( $markup ) || '' === $markup ) {
				return self::refuse( 'flexa_icon_invalid', __( 'That icon is not in the icon library.', 'flexa-block' ), 400 );
			}
			return [
				'source' => $source,
				'name'   => $name,
				'markup' => $markup,
				'url'    => '',
				'id'     => null,
			];
		}

		if ( 'upload' === $source ) {
			// Any image type, SVG included: wp_attachment_is_image() leaves SVG out,
			// and an uploaded SVG is the usual reason to upload an icon at all.
			if ( $id < 1 || 'attachment' !== get_post_type( $id ) || 0 !== strpos( (string) get_post_mime_type( $id ), 'image/' ) ) {
				return self::refuse( 'flexa_icon_invalid', __( 'That is not an image in the media library.', 'flexa-block' ), 400 );
			}
			if ( ! current_user_can( 'upload_files' ) ) {
				return self::refuse( 'flexa_icon_forbidden', __( 'You are not allowed to choose files from the media library.', 'flexa-block' ), 403 );
			}
			if ( ! current_user_can( 'read_post', $id ) ) {
				return self::refuse( 'flexa_icon_forbidden', __( 'You are not allowed to use that file.', 'flexa-block' ), 403 );
			}
			$url = wp_get_attachment_url( $id );
			if ( ! is_string( $url ) || '' === $url ) {
				return self::refuse( 'flexa_icon_invalid', __( 'That file has no address.', 'flexa-block' ), 400 );
			}
			return [
				'source' => 'upload',
				'name'   => '',
				'markup' => '',
				'url'    => $url,
				'id'     => $id,
			];
		}

		return self::refuse( 'flexa_icon_invalid', __( 'That icon could not be read. Reload the page and try again.', 'flexa-block' ), 400 );
	}

	/**
	 * Build the error.
	 *
	 * @param string $code    Error code.
	 * @param string $message Message for the person.
	 * @param int    $status  HTTP status.
	 */
	private static function refuse( string $code, string $message, int $status ): \WP_Error {
		return new \WP_Error( $code, $message, [ 'status' => $status ] );
	}
}
