<?php
declare(strict_types=1);
/**
 * CSS Generator Service — orchestrates save-time CSS generation for a post.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses post content and produces combined CSS stored in post meta.
 */
class CSS_Generator_Service {

	const CSS_META_KEY     = '_flexa_block_css';
	const VERSION_META_KEY = '_flexa_block_css_version';

	/**
	 * Cached block-name => generator-class map.
	 *
	 * @var array<string, string>|null
	 */
	private static $generators_cache = null;

	/**
	 * Build (once) the map of block name => CSS generator class.
	 *
	 * The map is derived from the block catalog so adding a block only means
	 * declaring its `generator` in Block_Manager. Add-ons can register a
	 * generator for their own block via the `flexa_block_css_generators` filter.
	 *
	 * @return array<string, string>
	 */
	public static function get_generators(): array {
		if ( null === self::$generators_cache ) {
			$map = [];
			foreach ( Block_Manager::get_block_catalog() as $block ) {
				if ( ! is_array( $block ) ) {
					continue;
				}
				$name      = (string) ( $block['name'] ?? '' );
				$generator = (string) ( $block['generator'] ?? '' );
				if ( '' !== $name && '' !== $generator ) {
					$map[ $name ] = $generator;
				}
			}

			/**
			 * Filter the block-name => CSS-generator-class map.
			 *
			 * @param array<string, string> $map Block name => generator FQCN.
			 */
			self::$generators_cache = apply_filters( 'flexa_block_css_generators', $map );
		}
		return self::$generators_cache ?? [];
	}

	/**
	 * Clear the cached generator map (e.g. after blocks change, or in tests).
	 */
	public static function reset_generators(): void {
		self::$generators_cache = null;
	}

	/**
	 * Cached block.json defaults per block type.
	 *
	 * @var array
	 */
	private static $defaults_cache = [];

	/**
	 * Generate and store CSS for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function generate_for_post( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || empty( $post->post_content ) ) {
			self::clear_cache( $post_id );
			return '';
		}

		$blocks = parse_blocks( $post->post_content );
		$css    = self::generate_for_blocks( $blocks );

		if ( '' !== $css ) {
			update_post_meta( $post_id, self::CSS_META_KEY, $css );
			update_post_meta( $post_id, self::VERSION_META_KEY, FLEXA_BLOCK_VER );
		} else {
			self::clear_cache( $post_id );
		}

		return $css;
	}

	/**
	 * Generate CSS for an array of parsed blocks.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return string
	 */
	public static function generate_for_blocks( $blocks ) {
		$builder = new CSS_Builder();
		self::process_blocks( $blocks, $builder );
		return $builder->get_output();
	}

	/**
	 * Recursively process blocks.
	 *
	 * @param array       $blocks  Blocks.
	 * @param CSS_Builder $builder Builder.
	 */
	private static function process_blocks( $blocks, $builder ) {
		$generators = self::get_generators();

		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			if ( isset( $generators[ $block['blockName'] ] ) ) {
				$attrs = self::merge_defaults( $block['blockName'], $block['attrs'] ?? [] );
				if ( ! empty( $attrs['blockId'] ) ) {
					$class = $generators[ $block['blockName'] ];
					if ( class_exists( $class ) ) {
						// Isolate each block: a failing generator must not abort CSS
						// for the rest of the post (nor break the post save itself).
						try {
							$class::generate( $attrs, $builder );
						} catch ( \Throwable $e ) {
							self::log_generation_error( (string) $block['blockName'], $e );
						}
					}
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				self::process_blocks( $block['innerBlocks'], $builder );
			}
		}
	}

	/**
	 * Merge saved attributes over block.json defaults (deep for objects).
	 *
	 * @param string $block_name Block name.
	 * @param array  $attrs      Saved attributes.
	 * @return array
	 */
	private static function merge_defaults( $block_name, $attrs ) {
		$defaults = self::get_block_defaults( $block_name );
		if ( empty( $defaults ) ) {
			return $attrs;
		}
		return self::deep_merge( $defaults, $attrs );
	}

	/**
	 * Get default attribute values for a registered block.
	 *
	 * @param string $block_name Block name.
	 * @return array
	 */
	private static function get_block_defaults( $block_name ) {
		if ( isset( self::$defaults_cache[ $block_name ] ) ) {
			return self::$defaults_cache[ $block_name ];
		}

		$registry = \WP_Block_Type_Registry::get_instance();
		$type     = $registry->get_registered( $block_name );

		$defaults = [];
		if ( $type && ! empty( $type->attributes ) ) {
			foreach ( $type->attributes as $key => $config ) {
				if ( isset( $config['default'] ) ) {
					$defaults[ $key ] = $config['default'];
				}
			}
		}

		self::$defaults_cache[ $block_name ] = $defaults;
		return $defaults;
	}

	/**
	 * Deep-merge override into base. Indexed (list) arrays replace; assoc merge.
	 *
	 * @param array $base     Defaults.
	 * @param array $override Saved attrs.
	 * @return array
	 */
	private static function deep_merge( $base, $override ) {
		foreach ( $override as $key => $value ) {
			if ( ! array_key_exists( $key, $base ) ) {
				$base[ $key ] = $value;
				continue;
			}
			if ( is_array( $base[ $key ] ) && is_array( $value ) ) {
				if ( ! empty( $value ) && isset( $value[0] ) ) {
					$base[ $key ] = $value;
				} else {
					$base[ $key ] = self::deep_merge( $base[ $key ], $value );
				}
			} else {
				$base[ $key ] = $value;
			}
		}
		return $base;
	}

	/**
	 * Get stored CSS for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function get_post_css( $post_id ) {
		$css = get_post_meta( $post_id, self::CSS_META_KEY, true );
		return is_string( $css ) ? $css : '';
	}

	/**
	 * Whether stored CSS is missing or from an older plugin version.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function needs_regeneration( $post_id ) {
		$version = get_post_meta( $post_id, self::VERSION_META_KEY, true );
		return empty( $version ) || $version !== FLEXA_BLOCK_VER;
	}

	/**
	 * Whether a post contains any supported block.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public static function has_blocks( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || empty( $post->post_content ) ) {
			return false;
		}
		foreach ( array_keys( self::get_generators() ) as $name ) {
			if ( has_block( $name, $post ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Clear stored CSS.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function clear_cache( $post_id ) {
		delete_post_meta( $post_id, self::CSS_META_KEY );
		delete_post_meta( $post_id, self::VERSION_META_KEY );
	}

	/**
	 * Flush cached CSS for every post (forces regeneration on next view).
	 *
	 * Used when global settings change (e.g. dark mode toggled) so already-saved
	 * posts don't keep serving CSS generated under the old settings.
	 */
	public static function flush_all_cache(): void {
		delete_post_meta_by_key( self::CSS_META_KEY );
		delete_post_meta_by_key( self::VERSION_META_KEY );
	}

	/**
	 * Log a CSS-generation failure for one block.
	 *
	 * Writes only when WP_DEBUG is on, so production stays quiet while developers
	 * (and anyone cloning this reference block) still get a clear diagnostic.
	 *
	 * @param string     $block_name Block that failed.
	 * @param \Throwable $error      The caught error.
	 */
	private static function log_generation_error( string $block_name, \Throwable $error ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log(
			sprintf(
				'[Flexa Block] CSS generation failed for block "%s": %s in %s:%d',
				$block_name,
				$error->getMessage(),
				$error->getFile(),
				$error->getLine()
			)
		);
	}
}
