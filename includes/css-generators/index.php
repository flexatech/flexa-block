<?php
declare(strict_types=1);
/**
 * Auto-load every block CSS generator in this directory.
 *
 * Drop a new `class-*-css.php` file here and it is picked up automatically —
 * no manual require list to keep in sync.
 *
 * @package Flexa\Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

(static function () {
	$files = glob( __DIR__ . '/class-*.php' );
	if ( is_array( $files ) ) {
		foreach ( $files as $file ) {
			require_once $file;
		}
	}
})();
