<?php
/**
 * PHPStan bootstrap — declares plugin constants so static analysis of includes/
 * does not report them as undefined. Not loaded at runtime.
 *
 * @package Flexa\Block
 */

define( 'FLEXA_BLOCK_VER', '1.0.0' );
define( 'FLEXA_BLOCK_DIR', __DIR__ . '/../' );
define( 'FLEXA_BLOCK_URL', 'https://example.com/wp-content/plugins/flexa-block/' );
define( 'FLEXA_BLOCK_BASENAME', 'flexa-block/flexa-block.php' );
define( 'FLEXA_BLOCK_MIN_WP', '6.4' );
