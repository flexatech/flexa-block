<?php
declare(strict_types=1);
/**
 * WooCommerce Helpers — shared by the product-* block render.php files.
 *
 * The product blocks (product-image, product-price, product-detail,
 * product-name, product-rating) are dynamic: they read the *current* product
 * on a single-product page rather than user-entered content. This helper
 * resolves that product once, so every block renders the same way and outputs
 * nothing when there is no product in context (i.e. off a single-product page).
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static WooCommerce helpers.
 */
final class Woo_Helpers {

	/**
	 * Whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Resolve the product for the current context.
	 *
	 * Prefers the global `$product` set by WooCommerce inside the single-product
	 * loop; falls back to the queried post. Returns null when WooCommerce is
	 * inactive or there is no product in context — callers should then render
	 * nothing, which keeps these blocks scoped to single-product pages.
	 *
	 * Duck-typed against `WC_Product` by name so this file carries no hard
	 * dependency on the WooCommerce classes (they are absent without the plugin).
	 *
	 * @return object|null A WC_Product instance, or null.
	 */
	public static function current_product() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return null;
		}

		$product = $GLOBALS['product'] ?? null;
		if ( is_object( $product ) && is_a( $product, 'WC_Product' ) ) {
			return $product;
		}

		$id = function_exists( 'get_the_ID' ) ? get_the_ID() : 0;
		if ( $id ) {
			$resolved = wc_get_product( $id );
			if ( is_object( $resolved ) && is_a( $resolved, 'WC_Product' ) ) {
				return $resolved;
			}
		}

		return null;
	}
}
