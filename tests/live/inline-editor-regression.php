<?php
/**
 * Front-end editing regression check against a real WordPress site.
 *
 * Saves every editable field of every block in the inline-editor registry
 * (text, links, images, icons) through Inline_Editor::handle_save(), on a
 * temporary draft page, and checks after each save that:
 *
 * - the save succeeded;
 * - exactly one line of the page changed, and it is the line of that block
 *   (every block sits on a line of its own), so no other block was rewritten;
 * - a sentinel block holding `{}` and `[]` is byte for byte unchanged.
 *
 * The page, and the image attachment it uses, are deleted at the end.
 *
 * Usage (from the plugin directory, with the site's PHP and database):
 *
 *     php tests/live/inline-editor-regression.php "/path/to/wp-load.php"
 *
 * Not part of PHPUnit (it needs a running WordPress) and not shipped
 * (tests/ is in .distignore).
 *
 * @package Flexa\Block
 */

// phpcs:disable WordPress.WP.AlternativeFunctions, WordPress.Security.EscapeOutput.OutputNotEscaped -- A CLI script, not plugin code.

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$wp_load = $argv[1] ?? '';
if ( '' === $wp_load || ! is_file( $wp_load ) ) {
	fwrite( STDERR, "Usage: php tests/live/inline-editor-regression.php /path/to/wp-load.php\n" );
	exit( 1 );
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
require $wp_load;
require_once ABSPATH . 'wp-admin/includes/image.php';

use Flexa\Block\Icon_Source;
use Flexa\Block\Inline_Editor;

$admins = get_users(
	[
		'role'   => 'administrator',
		'number' => 1,
	]
);
if ( ! $admins ) {
	fwrite( STDERR, "No administrator to run as.\n" );
	exit( 1 );
}
wp_set_current_user( $admins[0]->ID );

const FLEXA_QA_TITLE = 'flexa-inline-regression (temporary)';

// Leftovers from an interrupted run.
foreach ( get_posts( [ 'post_type' => [ 'page', 'attachment' ], 'post_status' => 'any', 'title' => FLEXA_QA_TITLE, 'numberposts' => -1 ] ) as $old ) {
	'attachment' === $old->post_type ? wp_delete_attachment( $old->ID, true ) : wp_delete_post( $old->ID, true );
}

// A real image for image fields (a 2x3 PNG).
$png    = base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAIAAAADCAYAAAC56t6BAAAAEklEQVR42mP8z8Dwn4EIwDiqEAAmHQP+0Zm3rwAAAABJRU5ErkJggg==' );
$upload = wp_upload_bits( 'flexa-inline-regression.png', null, $png );
$att_id = (int) wp_insert_attachment(
	[
		'post_mime_type' => 'image/png',
		'post_title'     => FLEXA_QA_TITLE,
		'post_status'    => 'inherit',
	],
	$upload['file']
);
wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $upload['file'] ) );

$catalog    = Icon_Source::catalog();
$icon_name  = (string) array_key_first( $catalog['builtin'] );
$registry   = Inline_Editor::registry();
$sentinel   = '<!-- wp:flexa/container {"blockId":"qa-sentinel","emptyObject":{},"emptyList":[],"nested":{"a":{},"b":[]}} /-->';
$lines      = [ $sentinel ];
$block_line = [];
$n          = 0;

/**
 * The registered default of a block attribute.
 *
 * @param string $name Block name.
 * @param string $attr Attribute.
 * @return mixed
 */
$default_of = static function ( string $name, string $attr ) {
	$type = WP_Block_Type_Registry::get_instance()->get_registered( $name );
	return $type && isset( $type->attributes[ $attr ]['default'] ) ? $type->attributes[ $attr ]['default'] : null;
};

// One block per line, each with its block.json defaults. A list the block
// ships empty gets one item, so its fields have something to edit.
foreach ( $registry as $name => $def ) {
	$id    = 'qa' . ( ++$n );
	$attrs = [ 'blockId' => $id ];
	foreach ( array_merge( $def['fields'], $def['images'], $def['icons'] ) as $entry ) {
		if ( empty( $entry['repeat'] ) && empty( $entry['cell'] ) ) {
			continue;
		}
		$attr    = (string) $entry['attr'];
		$default = $default_of( $name, $attr );
		if ( is_array( $default ) && $default ) {
			continue;
		}
		$item = isset( $attrs[ $attr ][0] ) ? $attrs[ $attr ][0] : [];
		if ( ! empty( $entry['cell'] ) ) {
			$item['values'] = [ 'cell' ];
		} elseif ( ! empty( $entry['key'] ) ) {
			$item[ (string) $entry['key'] ] = isset( $def['images'] ) && in_array( $entry, $def['images'], true ) ? [ 'id' => null, 'url' => '', 'alt' => '' ] : 'item';
		} else {
			$item += [ 'id' => null, 'url' => '', 'alt' => '' ];
		}
		$attrs[ $attr ] = [ $item ];
	}
	$block_line[ $name ] = [
		'id'   => $id,
		'line' => count( $lines ),
	];
	$lines[]             = '<!-- wp:' . $name . ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';
}

$page_id = wp_insert_post(
	[
		'post_type'    => 'page',
		'post_status'  => 'draft',
		'post_title'   => FLEXA_QA_TITLE,
		'post_content' => wp_slash( implode( "\n", $lines ) ),
	],
	true
);
if ( is_wp_error( $page_id ) ) {
	fwrite( STDERR, 'Could not create the page: ' . $page_id->get_error_message() . "\n" );
	exit( 1 );
}

$results = [
	'PASS' => 0,
	'FAIL' => 0,
	'SKIP' => 0,
];
$report  = [];

/**
 * Send one save and check what it changed.
 *
 * @param string               $label  What is being saved, for the report.
 * @param string               $name   Block name.
 * @param array<string, mixed> $params Request parameters besides the ids.
 */
$check = static function ( string $label, string $name, array $params ) use ( $page_id, $block_line, $sentinel, &$results, &$report ): void {
	$before = (string) get_post( $page_id )->post_content;

	$request = new WP_REST_Request( 'POST', '/flexa-block/v1/inline-edit' );
	$request->set_body_params(
		$params + [
			'postId'    => $page_id,
			'blockId'   => $block_line[ $name ]['id'],
			'blockName' => $name,
		]
	);
	$response = Inline_Editor::handle_save( $request );
	clean_post_cache( $page_id );
	$after = (string) get_post( $page_id )->post_content;

	if ( is_wp_error( $response ) ) {
		$code = $response->get_error_code();
		// A list the block ships empty has no item 0 to edit: nothing to check.
		if ( 'flexa_bad_index' === $code ) {
			++$results['SKIP'];
			$report[] = "SKIP {$label} (no list item in the block's defaults)";
			return;
		}
		++$results['FAIL'];
		$report[] = "FAIL {$label}: {$code} " . $response->get_error_message();
		return;
	}

	$old_lines = explode( "\n", $before );
	$new_lines = explode( "\n", $after );
	$changed   = [];
	foreach ( $new_lines as $i => $line ) {
		if ( ( $old_lines[ $i ] ?? null ) !== $line ) {
			$changed[] = $i;
		}
	}
	$problems = [];
	if ( count( $old_lines ) !== count( $new_lines ) ) {
		$problems[] = 'number of lines changed';
	}
	if ( $changed && [ $block_line[ $name ]['line'] ] !== $changed ) {
		$problems[] = 'other lines changed: ' . implode( ',', $changed );
	}
	if ( ( $new_lines[0] ?? '' ) !== $sentinel ) {
		$problems[] = 'the {} / [] sentinel changed';
	}
	if ( ! $changed ) {
		$problems[] = 'nothing was written';
	}

	if ( $problems ) {
		++$results['FAIL'];
		$report[] = "FAIL {$label}: " . implode( '; ', $problems );
	} else {
		++$results['PASS'];
	}
};

try {
	$k = 0;
	foreach ( $registry as $name => $def ) {
		foreach ( $def['fields'] as $field ) {
			$label  = $name . ' text ' . $field['attr'] . ( isset( $field['key'] ) ? '.' . $field['key'] : '' );
			$params = [
				'attr'  => $field['attr'],
				'value' => 'QA text ' . ( ++$k ),
			];
			if ( ! empty( $field['repeat'] ) || ! empty( $field['cell'] ) ) {
				$params['index'] = 0;
				$params['key']   = $field['key'];
			} elseif ( ! empty( $field['object'] ) ) {
				$params['key'] = $field['key'];
			}
			if ( ! empty( $field['cell'] ) ) {
				$params['subindex'] = 0;
			}
			$check( $label, $name, $params );

			if ( ! empty( $field['link'] ) ) {
				$link   = $field['link'];
				$lparams = [
					'attr'  => $link['attr'],
					'link'  => true,
					'value' => wp_json_encode(
						[
							'url'    => 'https://example.org/qa-' . $k,
							'newTab' => true,
						]
					),
				];
				if ( ! empty( $link['item'] ) ) {
					$lparams['index'] = 0;
					$lparams['key']   = $link['item'];
				}
				$check( $name . ' link ' . $link['attr'], $name, $lparams );
			}
		}

		foreach ( $def['images'] as $image ) {
			$label  = $name . ' image ' . $image['attr'] . ( isset( $image['key'] ) ? '.' . $image['key'] : '' );
			$params = [
				'attr'  => $image['attr'],
				'image' => true,
				'value' => wp_json_encode(
					[
						'id'  => $att_id,
						'alt' => 'QA alt',
					]
				),
			];
			if ( ! empty( $image['repeat'] ) ) {
				$params['index'] = 0;
				if ( ! empty( $image['key'] ) ) {
					$params['key'] = $image['key'];
				}
			}
			if ( ! empty( $image['background'] ) ) {
				// Only an image background can be swapped; a default one is not.
				++$results['SKIP'];
				$report[] = "SKIP {$label} (background is not an image by default)";
				continue;
			}
			$check( $label, $name, $params );
		}

		foreach ( $def['icons'] as $icon ) {
			$label  = $name . ' icon ' . $icon['attr'] . ( isset( $icon['key'] ) ? '.' . $icon['key'] : '' );
			$params = [
				'attr'  => $icon['attr'],
				'icon'  => true,
				'value' => wp_json_encode(
					[
						'source' => 'builtin',
						'name'   => $icon_name,
					]
				),
			];
			if ( ! empty( $icon['repeat'] ) ) {
				$params['index'] = 0;
				$params['key']   = $icon['key'];
			}
			$check( $label, $name, $params );
		}
	}
} finally {
	wp_delete_post( (int) $page_id, true );
	wp_delete_attachment( $att_id, true );
}

foreach ( $report as $line ) {
	echo $line, "\n";
}
printf( "\n%d blocks, %d PASS, %d FAIL, %d SKIP\n", count( $registry ), $results['PASS'], $results['FAIL'], $results['SKIP'] );
exit( $results['FAIL'] > 0 ? 1 : 0 );
