<?php
/**
 * Subscribe Form — Hidden field render (child of flexa/subscribe-form).
 *
 * @package Flexa\Block
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    InnerBlocks content (unused).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $attributes provided by WP block API.

use Flexa\Block\HTML_Helpers;

echo HTML_Helpers::subscribe_hidden_field( $attributes, 'source' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output escaped inside the helper.
