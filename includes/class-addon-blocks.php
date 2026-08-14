<?php
declare(strict_types=1);
/**
 * Add-on teasers — blocks that exist in a paid add-on but not in this plugin.
 *
 * The Blocks dashboard lists whatever the catalog carries, and an add-on adds
 * its own entries through `flexa_block_blocks`. That works only while the add-on
 * is active: switch it off (or never install it) and its cards vanish, so a user
 * on the free plugin has no way to learn which blocks the paid version adds.
 *
 * This file is that list — name, blurb and filter pill for each add-on block,
 * carried by THIS plugin so the cards survive the add-on being absent. Entries
 * are marked `locked`, which means: show the card, never register the block,
 * never let it be switched on or off.
 *
 * Two things this deliberately is NOT:
 *   - It is not a registration. There is no `path`, no `block.json` and no
 *     `register_block_type()` for these slugs, so nothing here claims the free
 *     plugin ships those blocks.
 *   - It is not fetched from anywhere. No HTTP request, no phoning home — the
 *     price is that adding a block to the add-on means updating this list in the
 *     next release of this plugin. That is the trade every comparable plugin
 *     makes; see docs in the Pro repo (block-dashboard-pro-integration.md §8).
 *
 * When the add-on IS present it appends the real entry for the same slug first
 * (priority 10), and `filter_catalog()` runs later (priority 20) and skips any
 * slug it already sees. So the card silently upgrades itself: real glyph, real
 * description, working switch. No flag has to be flipped anywhere.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Catalog entries for blocks that only exist in a paid add-on.
 */
class Addon_Blocks {

	/**
	 * Blocks of the Flexa Block Pro add-on.
	 *
	 * Field shape is the catalog's, minus everything that implies executable
	 * code: no `path`, no `generator`. `group` must be a key of GROUP_META in
	 * src/admin/block-groups.ts, `badge` is the free-text origin label.
	 *
	 * Slugs must match `Flexa\Block\Pro\Pro_Manager::BLOCKS` exactly — that is
	 * what lets the real entry take over. A slug renamed on one side and not the
	 * other shows up as a locked ghost card that never unlocks.
	 *
	 * Child blocks (flip-face, form-step) are deliberately absent: the dashboard
	 * hides `is_child` entries anyway.
	 *
	 * Untranslated literals, matching Block_Manager::BASE_BLOCKS.
	 *
	 * @var array<int, array<string, mixed>>
	 */
	const TEASERS = [
		[
			'slug'        => 'flip-box',
			'name'        => 'flexa-pro/flip-box',
			'title'       => 'Flip Box',
			'description' => 'Two-sided card that flips in 3D on hover or tap, with a free content area on each face, a choice of flip direction, card shape and per-face colours.',
			'category'    => 'flexa',
			'group'       => 'interactive',
			'badge'       => 'Pro',
		],
		[
			'slug'        => 'image-hotspot',
			'name'        => 'flexa-pro/image-hotspot',
			'title'       => 'Image Hotspot',
			'description' => 'Interactive markers placed over an image, each pulsing to draw attention and revealing a tooltip with title, description and optional link on hover or tap.',
			'category'    => 'flexa',
			'group'       => 'media',
			'badge'       => 'Pro',
		],
		[
			'slug'        => 'client-logo-carousel',
			'name'        => 'flexa-pro/client-logo-carousel',
			'title'       => 'Client Logo Carousel',
			'description' => 'Partner or customer logos in a continuously scrolling marquee, grayscale until hover, pausing on hover and fading in when the block enters the viewport.',
			'category'    => 'flexa',
			'group'       => 'marketing',
			'badge'       => 'Pro',
		],
		[
			'slug'        => 'business-hours',
			'name'        => 'flexa-pro/business-hours',
			'title'       => 'Business Hours',
			'description' => 'Weekly opening hours with a live Open now / Closed status in your business timezone, today highlighted, holiday overrides and optional Call / Directions buttons.',
			'category'    => 'flexa',
			'group'       => 'elements',
			'badge'       => 'Pro',
		],
		[
			'slug'        => 'chart-block',
			'name'        => 'flexa-pro/chart-block',
			'title'       => 'Chart',
			'description' => 'Bar, line, area, pie, donut, radar or gauge chart from one Chart.js engine, with data entered by hand or imported from a CSV.',
			'category'    => 'flexa',
			'group'       => 'elements',
			'badge'       => 'Pro',
		],
		[
			'slug'        => 'fab',
			'name'        => 'flexa-pro/fab',
			'title'       => 'Floating Action Button',
			'description' => 'Round button pinned to a corner of the screen that expands into quick contact actions — Zalo, Messenger, a phone call, a link, back-to-top or a Modal block.',
			'category'    => 'flexa',
			'group'       => 'interactive',
			'badge'       => 'Pro',
		],
		[
			'slug'        => 'sticky-announcement-bar',
			'name'        => 'flexa-pro/sticky-announcement-bar',
			'title'       => 'Sticky Announcement Bar',
			'description' => 'Full-width bar pinned to the top or bottom of the screen with an optional icon, call-to-action and dismiss button, offsetting a fixed theme header and schedulable between two dates.',
			'category'    => 'flexa',
			'group'       => 'marketing',
			'badge'       => 'Pro',
		],
		[
			'slug'        => 'animated-headline',
			'name'        => 'flexa-pro/animated-headline',
			'title'       => 'Animated Headline',
			'description' => 'Headline whose middle word rotates by typing, flipping, fading or sliding — or a single phrase marked with an SVG stroke that draws itself on scroll.',
			'category'    => 'flexa',
			'group'       => 'content',
			'badge'       => 'Pro',
		],
		[
			'slug'        => 'circle-info',
			'name'        => 'flexa-pro/circle-info',
			'title'       => 'Circle Info',
			'description' => 'Items arranged as points around a circle with the selected one shown in the middle, with scroll-triggered autoplay and a vertical accordion fallback on small screens.',
			'category'    => 'flexa',
			'group'       => 'interactive',
			'badge'       => 'Pro',
		],
		[
			'slug'        => 'multi-step-form',
			'name'        => 'flexa-pro/multi-step-form',
			'title'       => 'Multi-Step Form',
			'description' => 'Long form split into short steps with a progress indicator, Back / Next navigation, per-step validation and an optional review step, built from the Subscribe Form field blocks.',
			'category'    => 'flexa',
			'group'       => 'forms',
			'badge'       => 'Pro',
		],
	];

	/**
	 * Hook registration.
	 */
	public static function init(): void {
		// Priority 20: after add-ons have appended their real entries at the
		// default 10, so filter_catalog() can see them and stand aside.
		add_filter( 'flexa_block_blocks', [ __CLASS__, 'filter_catalog' ], 20 );

		// A locked entry describes a block this plugin does not have. Registering
		// it is impossible (no path) and would be wrong to attempt, so drop the
		// whole class of them before the registration loop rather than relying on
		// a missing file to stop it.
		add_filter( 'flexa_block_registerable_blocks', [ __CLASS__, 'drop_locked' ], 5 );
	}

	/**
	 * Append the teasers the add-on has not already provided.
	 *
	 * @param mixed $blocks Block catalog.
	 * @return array<int, array<string, mixed>>
	 */
	public static function filter_catalog( $blocks ): array {
		if ( ! is_array( $blocks ) ) {
			return [];
		}

		$present = [];
		foreach ( $blocks as $block ) {
			if ( is_array( $block ) && isset( $block['slug'] ) ) {
				$present[ (string) $block['slug'] ] = true;
			}
		}

		foreach ( self::TEASERS as $teaser ) {
			if ( isset( $present[ $teaser['slug'] ] ) ) {
				continue; // The add-on is here and owns this block.
			}
			$teaser['locked'] = true;
			$blocks[]         = $teaser;
		}

		return $blocks;
	}

	/**
	 * Keep locked entries out of the set that gets registered.
	 *
	 * @param mixed $blocks Block catalog.
	 * @return array<int, array<string, mixed>>
	 */
	public static function drop_locked( $blocks ): array {
		if ( ! is_array( $blocks ) ) {
			return [];
		}
		return array_values(
			array_filter(
				$blocks,
				static function ( $block ) {
					return empty( $block['locked'] );
				}
			)
		);
	}

	/**
	 * Slugs currently shown as locked.
	 *
	 * Used to refuse them as settings input: they are in the catalog, so the
	 * plain "is this a known slug" test would otherwise accept them and write a
	 * block the site does not own into `disabled_blocks`.
	 *
	 * @return array<int, string>
	 */
	public static function locked_slugs(): array {
		$out = [];
		foreach ( Block_Manager::get_block_catalog() as $block ) {
			if ( ! empty( $block['locked'] ) && isset( $block['slug'] ) ) {
				$out[] = (string) $block['slug'];
			}
		}
		return $out;
	}
}
