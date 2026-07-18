<?php
/**
 * Breadcrumb block — server-side render.
 *
 * CSS is generated at save time by Breadcrumb_CSS and printed inline on the front
 * end. This file outputs the trail markup: an <ol> of crumbs separated by the
 * chosen glyph, where the last crumb is the current page. In Automatic mode the
 * trail is built from the current page/post context (Home → ancestors → current);
 * in Manual mode it uses the hand-listed items. When enabled, a BreadcrumbList
 * JSON-LD block is printed once for rich results. Text inherits the theme's
 * typography unless the user overrode it.
 *
 * @package Flexa\Block
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Save content (unused — dynamic block).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $attributes provided by WP block API.

use Flexa\Block\HTML_Helpers;

$block_id  = $attributes['blockId'] ?? '';
$anchor    = $attributes['anchor'] ?? '';
$html_tag  = HTML_Helpers::get_html_tag( $attributes, 'nav' );

$source        = 'manual' === ( $attributes['source'] ?? 'auto' ) ? 'manual' : 'auto';
$home_text     = (string) ( $attributes['homeText'] ?? '' );
$home_text     = '' !== $home_text ? $home_text : __( 'Home', 'flexa-block' );
$home_icon     = ! empty( $attributes['homeIcon'] );
$show_current  = false !== ( $attributes['showCurrent'] ?? true );
$current_link  = ! empty( $attributes['currentIsLink'] );
$enable_schema = ! empty( $attributes['enableSchema'] );

/**
 * The literal glyph printed between crumbs for the chosen separator token.
 *
 * @param array $separator Separator attribute { type, custom }.
 * @return string
 */
$separator_char = static function ( $separator ) {
	$type = is_array( $separator ) ? ( $separator['type'] ?? 'slash' ) : 'slash';
	switch ( $type ) {
		case 'chevron':
			return '›';
		case 'raquo':
			return '»';
		case 'dash':
			return '-';
		case 'custom':
			$custom = is_array( $separator ) ? (string) ( $separator['custom'] ?? '' ) : '';
			return '' !== $custom ? $custom : '/';
		default:
			return '/';
	}
};

/**
 * Build the automatic trail from the current query context. Each entry is
 * [ 'label' => string, 'url' => string ]; the last entry is the current page.
 * Falls back to a small sample trail when there is no page context (e.g. the
 * block is rendered outside the main query / in a REST preview).
 *
 * @param string $home_text Label for the leading Home crumb.
 * @return array
 */
$build_auto_trail = static function ( $home_text ) {
	$trail = [ [ 'label' => $home_text, 'url' => home_url( '/' ) ] ];

	if ( is_front_page() ) {
		return [ [ 'label' => $home_text, 'url' => '' ] ];
	}

	if ( is_singular() ) {
		$post_id   = get_queried_object_id();
		$post_type = get_post_type( $post_id );

		// A custom post type: link its archive first (if it has one).
		if ( $post_type && 'post' !== $post_type && 'page' !== $post_type ) {
			$pt_obj = get_post_type_object( $post_type );
			if ( $pt_obj && ! empty( $pt_obj->has_archive ) ) {
				$archive = get_post_type_archive_link( $post_type );
				if ( $archive ) {
					$trail[] = [ 'label' => $pt_obj->labels->name ?? $post_type, 'url' => $archive ];
				}
			}
		}

		// Page/post ancestors, from the top down.
		$ancestors = array_reverse( (array) get_post_ancestors( $post_id ) );
		foreach ( $ancestors as $ancestor_id ) {
			$trail[] = [ 'label' => get_the_title( $ancestor_id ), 'url' => (string) get_permalink( $ancestor_id ) ];
		}

		$trail[] = [ 'label' => get_the_title( $post_id ), 'url' => (string) get_permalink( $post_id ) ];
		return $trail;
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof \WP_Term ) {
			$ancestors = array_reverse( (array) get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) );
			foreach ( $ancestors as $ancestor_id ) {
				$parent = get_term( $ancestor_id, $term->taxonomy );
				if ( $parent instanceof \WP_Term ) {
					$trail[] = [ 'label' => $parent->name, 'url' => (string) get_term_link( $parent ) ];
				}
			}
			$trail[] = [ 'label' => $term->name, 'url' => (string) get_term_link( $term ) ];
			return $trail;
		}
	}

	if ( is_post_type_archive() ) {
		$trail[] = [ 'label' => post_type_archive_title( '', false ), 'url' => '' ];
		return $trail;
	}

	if ( is_author() ) {
		$trail[] = [ 'label' => (string) get_the_author_meta( 'display_name', (int) get_queried_object_id() ), 'url' => '' ];
		return $trail;
	}

	if ( is_search() ) {
		/* translators: %s: search query. */
		$trail[] = [ 'label' => sprintf( __( 'Search: %s', 'flexa-block' ), get_search_query() ), 'url' => '' ];
		return $trail;
	}

	if ( is_404() ) {
		$trail[] = [ 'label' => __( 'Not found', 'flexa-block' ), 'url' => '' ];
		return $trail;
	}

	// No usable context (e.g. editor/REST preview) → a representative sample.
	if ( count( $trail ) < 2 ) {
		$trail[] = [ 'label' => __( 'Category', 'flexa-block' ), 'url' => home_url( '/' ) ];
		$trail[] = [ 'label' => __( 'Current page', 'flexa-block' ), 'url' => '' ];
	}
	return $trail;
};

// --- Build the trail. ---
if ( 'manual' === $source ) {
	$items = $attributes['items'] ?? [];
	$trail = [];
	if ( is_array( $items ) ) {
		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$label = (string) ( $item['label'] ?? '' );
			if ( '' === trim( $label ) ) {
				continue;
			}
			$trail[] = [ 'label' => $label, 'url' => (string) ( $item['url'] ?? '' ) ];
		}
	}
} else {
	$trail = $build_auto_trail( $home_text );
}

$trail = array_values( $trail );
if ( empty( $trail ) ) {
	return;
}

// Drop the current (last) crumb when the user hid it.
$last_index = count( $trail ) - 1;
$visible    = $show_current ? $trail : array_slice( $trail, 0, $last_index );
if ( empty( $visible ) ) {
	return;
}

$sep_glyph = $separator_char( $attributes['separator'] ?? [] );

// A house glyph used when the home icon is on but no custom icon was chosen.
$default_house = '<svg class="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/></svg>';

// Resolve the home-icon markup once (safe: svg_kses'd or an <img> with esc_url).
$home_icon_html = '';
if ( $home_icon ) {
	$icon   = $attributes['homeIconValue'] ?? [];
	$markup = is_array( $icon ) ? (string) ( $icon['markup'] ?? '' ) : '';
	$url    = is_array( $icon ) ? (string) ( $icon['url'] ?? '' ) : '';
	if ( '' !== $markup ) {
		$svg = HTML_Helpers::svg_kses( $markup );
		$inner = '' !== $svg ? $svg : $default_house;
		$home_icon_html = '<span class="flexa-breadcrumb__home-icon">' . $inner . '</span>';
	} elseif ( '' !== $url ) {
		$home_icon_html = '<img class="flexa-breadcrumb__home-icon" src="' . esc_url( $url ) . '" alt="" />';
	} else {
		$home_icon_html = '<span class="flexa-breadcrumb__home-icon">' . $default_house . '</span>';
	}
}

// --- Build the list markup. ---
$sep_html   = '<span class="flexa-breadcrumb__separator" aria-hidden="true">' . esc_html( $sep_glyph ) . '</span>';
$items_html = '';
foreach ( $visible as $i => $crumb ) {
	$is_current = $i === $last_index;
	$label      = esc_html( (string) $crumb['label'] );
	$url        = (string) ( $crumb['url'] ?? '' );
	$icon_html  = 0 === $i ? $home_icon_html : '';

	if ( $is_current ) {
		if ( $current_link && '' !== $url ) {
			$crumb_html = '<a class="flexa-breadcrumb__current" href="' . esc_url( $url ) . '" aria-current="page">' . $icon_html . $label . '</a>';
		} else {
			$crumb_html = '<span class="flexa-breadcrumb__current" aria-current="page">' . $icon_html . $label . '</span>';
		}
	} elseif ( '' !== $url ) {
		$crumb_html = '<a class="flexa-breadcrumb__link" href="' . esc_url( $url ) . '">' . $icon_html . $label . '</a>';
	} else {
		$crumb_html = '<span class="flexa-breadcrumb__link">' . $icon_html . $label . '</span>';
	}

	$show_sep    = $i < count( $visible ) - 1;
	$item_class  = 'flexa-breadcrumb__item' . ( $is_current ? ' flexa-breadcrumb__item--current' : '' );
	$items_html .= '<li class="' . esc_attr( $item_class ) . '">' . $crumb_html . ( $show_sep ? $sep_html : '' ) . '</li>';
}

// --- Optional BreadcrumbList schema (built from the FULL trail, printed once). ---
$schema_html = '';
if ( $enable_schema ) {
	$elements = [];
	foreach ( $trail as $position => $crumb ) {
		$name = trim( wp_strip_all_tags( (string) $crumb['label'] ) );
		if ( '' === $name ) {
			continue;
		}
		$element = [
			'@type'    => 'ListItem',
			'position' => $position + 1,
			'name'     => $name,
		];
		$url = (string) ( $crumb['url'] ?? '' );
		if ( '' !== $url ) {
			$element['item'] = esc_url_raw( $url );
		}
		$elements[] = $element;
	}
	if ( ! empty( $elements ) ) {
		$schema = [
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $elements,
		];
		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false !== $json ) {
			$schema_html = '<script type="application/ld+json">' . $json . '</script>';
		}
	}
}

// --- Wrapper classes + attributes. ---
$classes = [ 'flexa-breadcrumb' ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-breadcrumb-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [
	'class'      => implode( ' ', $classes ),
	'aria-label' => __( 'Breadcrumb', 'flexa-block' ),
];
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}
$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

// Lazy background marker.
$background  = $attributes['background'] ?? [];
$is_lazy_bg  = ! empty( $background['lazyLoad'] ) && 'image' === ( $background['type'] ?? 'none' ) && '' !== ( $background['image']['url'] ?? '' );
$lazy_marker = $is_lazy_bg ? ' data-flexa-lazy-bg' : '';

printf(
	'<%1$s %2$s%3$s%4$s>%5$s<ol class="flexa-breadcrumb__list">%6$s</ol></%1$s>',
	esc_html( $html_tag ),
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$lazy_marker,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literal.
	$schema_html,        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output, script tag static.
	$items_html          // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- labels esc_html'd, urls esc_url'd, icon svg_kses'd above.
);
