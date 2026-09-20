<?php
declare(strict_types=1);
/**
 * Media importer.
 *
 * Sideloads a media file (a plugin-bundled local file, or a remote URL) into the
 * Media Library as an attachment the site owns, and remembers what it created so
 * a second import of the same file re-uses the existing attachment instead of
 * piling up duplicates.
 *
 * The free plugin's own Sample Data ships no media (it stays WordPress.org-clean
 * with zero bundled third-party images). This class exists so Pro Examples and
 * Starter Templates — which do carry imagery — reuse one media pipeline rather
 * than writing their own.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block\Import;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sideloads and de-duplicates imported media.
 */
class Media_Importer {

	/** Attachment meta storing the dedup fingerprint of the source file. */
	const HASH_META = '_flexa_import_media_hash';

	/** Attachment meta linking the media back to its import for cleanup. */
	const SOURCE_META = '_flexa_import_source';

	/**
	 * Sideload one file, reusing a prior import when the bytes match.
	 *
	 * Remote URLs are only fetched when the host is explicitly allowed, so an
	 * untrusted definition cannot turn an import into an SSRF probe. Bundled
	 * local files (shipped inside the plugin) are always trusted.
	 *
	 * @param string $file        Absolute local path, or an https URL.
	 * @param string $alt         Alt text for the attachment.
	 * @param string $source_key  Owning source key (stored for cleanup).
	 * @return array{id: int, url: string}|\WP_Error
	 */
	public static function sideload( string $file, string $alt, string $source_key ) {
		$local = self::resolve_to_temp( $file );
		if ( is_wp_error( $local ) ) {
			return $local;
		}

		$hash    = (string) hash_file( 'sha256', $local );
		$existing = self::find_by_hash( $hash );
		if ( $existing > 0 ) {
			wp_delete_file( $local );
			return [
				'id'  => $existing,
				'url' => (string) wp_get_attachment_url( $existing ),
			];
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$file_array = [
			'name'     => basename( wp_parse_url( $file, PHP_URL_PATH ) ?: $file ),
			'tmp_name' => $local,
		];

		$id = media_handle_sideload( $file_array, 0 );
		if ( is_wp_error( $id ) ) {
			wp_delete_file( $local );
			return $id;
		}

		update_post_meta( $id, self::HASH_META, $hash );
		update_post_meta( $id, self::SOURCE_META, $source_key );
		if ( '' !== $alt ) {
			update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
		}

		return [
			'id'  => (int) $id,
			'url' => (string) wp_get_attachment_url( $id ),
		];
	}

	/**
	 * Copy/download the source into a temp file media_handle_sideload can consume.
	 *
	 * @param string $file Absolute local path or https URL.
	 * @return string|\WP_Error Temp file path.
	 */
	private static function resolve_to_temp( string $file ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$scheme = wp_parse_url( $file, PHP_URL_SCHEME );

		if ( 'http' === $scheme || 'https' === $scheme ) {
			if ( ! self::host_allowed( $file ) ) {
				return new \WP_Error( 'flexa_media_host', __( 'Remote media host is not allowed.', 'flexa-block' ) );
			}
			$tmp = download_url( $file, 30 );
			return $tmp; // string path or WP_Error.
		}

		if ( ! is_string( $file ) || '' === $file || ! is_readable( $file ) ) {
			return new \WP_Error( 'flexa_media_missing', __( 'Bundled media file was not found.', 'flexa-block' ) );
		}

		// Copy so the bundled original is never moved out of the plugin.
		$tmp = wp_tempnam( basename( $file ) );
		if ( ! $tmp || ! copy( $file, $tmp ) ) {
			return new \WP_Error( 'flexa_media_copy', __( 'Could not stage the media file for import.', 'flexa-block' ) );
		}
		return $tmp;
	}

	/**
	 * Whether a remote media host is permitted.
	 *
	 * Empty by default (bundled media only). Add-ons opt specific hosts in via
	 * the filter — nothing remote is fetched otherwise.
	 *
	 * @param string $url Candidate URL.
	 * @return bool
	 */
	private static function host_allowed( string $url ): bool {
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		if ( '' === $host ) {
			return false;
		}
		/**
		 * Allow-list of hosts imported media may be fetched from.
		 *
		 * @param list<string> $hosts Allowed hostnames.
		 */
		$hosts = (array) apply_filters( 'flexa_block_import_media_hosts', [] );
		return in_array( strtolower( $host ), array_map( 'strtolower', $hosts ), true );
	}

	/**
	 * Find an attachment previously imported from the same bytes.
	 *
	 * @param string $hash sha256 of the file.
	 * @return int Attachment ID, or 0.
	 */
	private static function find_by_hash( string $hash ): int {
		$found = get_posts(
			[
				'post_type'              => 'attachment',
				'post_status'            => 'inherit',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Admin-only import dedupe: finds an already-imported attachment by its content hash; there is no non-meta way to match by file contents, and it runs once per imported file, not on the front end.
				'meta_key'               => self::HASH_META,
				'meta_value'             => $hash,
				// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			]
		);
		return $found ? (int) $found[0] : 0;
	}
}
