<?php
declare(strict_types=1);
/**
 * Uploads — allow the Lottie block's animation files into the media library.
 *
 * WordPress ships no `.json` / `.lottie` MIME entry, so those uploads fail with
 * "This file cannot be processed by the web server." The Lottie block needs both
 * (a bare Lottie animation is JSON; a dotLottie bundle is a ZIP with a `.lottie`
 * extension), so this service:
 *   1. adds the two MIME types — only for users who can already upload files, so
 *      the allowed-type surface is not widened for anonymous/lower contexts, and
 *   2. pins the ext/type in `wp_check_filetype_and_ext` so WP's real-MIME sniff
 *      (which reports `.json` as text/plain and `.lottie` as application/zip)
 *      doesn't reject the file on an ext/type mismatch.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Media-library MIME support for Lottie animation files.
 */
final class Uploads {

	/**
	 * Extension => MIME type for the Lottie animation files.
	 *
	 * `.lottie` is a ZIP container (dotLottie); a plain Lottie is JSON.
	 *
	 * @var array<string, string>
	 */
	const MIMES = [
		'json'   => 'application/json',
		'lottie' => 'application/zip',
	];

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_filter( 'upload_mimes', [ __CLASS__, 'add_mimes' ] );
		add_filter( 'wp_check_filetype_and_ext', [ __CLASS__, 'fix_filetype' ], 10, 4 );
	}

	/**
	 * Allow the Lottie MIME types for users who can upload files.
	 *
	 * @param array<string, string> $mimes Allowed MIME types.
	 * @return array<string, string>
	 */
	public static function add_mimes( $mimes ) {
		if ( ! is_array( $mimes ) || ! current_user_can( 'upload_files' ) ) {
			return $mimes;
		}
		foreach ( self::MIMES as $ext => $type ) {
			$mimes[ $ext ] = $type;
		}
		return $mimes;
	}

	/**
	 * Pin ext/type for `.json` / `.lottie` so the real-MIME sniff doesn't reject
	 * them (a JSON file sniffs as text/plain; a dotLottie as application/zip).
	 *
	 * @param array{ext: string, type: string, proper_filename: string|false} $data     File data.
	 * @param string                                                           $file     Full path to the file.
	 * @param string                                                           $filename The name of the file.
	 * @param array<string, string>|null                                       $mimes    Allowed MIME types.
	 * @return array{ext: string, type: string, proper_filename: string|false}
	 */
	public static function fix_filetype( $data, $file, $filename, $mimes ) {
		if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
			return $data;
		}
		if ( ! current_user_can( 'upload_files' ) ) {
			return $data;
		}
		$ext = strtolower( (string) pathinfo( (string) $filename, PATHINFO_EXTENSION ) );
		if ( isset( self::MIMES[ $ext ] ) ) {
			$data['ext']  = $ext;
			$data['type'] = self::MIMES[ $ext ];
		}
		return $data;
	}
}
