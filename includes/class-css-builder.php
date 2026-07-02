<?php
declare(strict_types=1);
/**
 * CSS Builder - fluent generator with responsive media queries and minification.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds CSS grouped by media query.
 */
class CSS_Builder {

	/**
	 * Cached specificity-boost setting (filtered once per request).
	 *
	 * @var bool|null
	 */
	private static $specificity_boost = null;

	/**
	 * Rules grouped by media query: [ media => [ selector => [ prop => value ] ] ].
	 *
	 * @var array
	 */
	private $rules = [];

	/**
	 * Current selector.
	 *
	 * @var string
	 */
	private $current_selector = '';

	/**
	 * Current media context (null = base).
	 *
	 * @var string|null
	 */
	private $current_media = null;

	/**
	 * Breakpoints (max-width in px).
	 *
	 * @var array
	 */
	private $breakpoints = [
		'tablet' => 1024,
		'mobile' => 767,
	];

	/**
	 * Set the active selector.
	 *
	 * @param string $selector CSS selector.
	 * @return self
	 */
	public function set_selector( $selector ) {
		// Resolve the specificity-boost setting once per request.
		if ( null === self::$specificity_boost ) {
			/**
			 * Filter whether to prepend `body ` to every selector for a small
			 * specificity boost so generated CSS reliably overrides theme styles.
			 *
			 * @param bool $boost Default false.
			 */
			self::$specificity_boost = (bool) apply_filters( 'flexa_block_css_specificity_boost', false );
		}

		if ( self::$specificity_boost && ! preg_match( '/^body[\s.\[]/', $selector ) ) {
			$selector = 'body ' . $selector;
		}

		$this->current_selector = $selector;
		return $this;
	}

	/**
	 * Add a property to the active selector. Empty values (except 0) are skipped.
	 *
	 * @param string $property Property name.
	 * @param mixed  $value    Value.
	 * @return self
	 */
	public function add_property( $property, $value ) {
		if ( '' === $value || null === $value ) {
			return $this;
		}

		// Defensive: CSS values never legitimately contain these. Stripping them
		// prevents breaking out of the inline <style> block (e.g. "</style>")
		// or out of the declaration/rule (e.g. "red;} body{display:none").
		$value = str_replace( [ '<', '>', '{', '}', ';' ], '', (string) $value );
		if ( '' === $value ) {
			return $this;
		}

		$media = $this->current_media ?? 'base';

		if ( ! isset( $this->rules[ $media ] ) ) {
			$this->rules[ $media ] = [];
		}
		if ( ! isset( $this->rules[ $media ][ $this->current_selector ] ) ) {
			$this->rules[ $media ][ $this->current_selector ] = [];
		}

		$this->rules[ $media ][ $this->current_selector ][ $property ] = $value;

		return $this;
	}

	/**
	 * Open a media query block for a device or custom query.
	 *
	 * @param string $device 'desktop' | 'tablet' | 'mobile' | raw query.
	 * @return self
	 */
	public function start_media_query( $device ) {
		if ( 'desktop' === $device ) {
			$this->current_media = '@media (min-width: ' . ( $this->breakpoints['tablet'] + 1 ) . 'px)';
		} elseif ( isset( $this->breakpoints[ $device ] ) ) {
			$this->current_media = '@media (max-width: ' . $this->breakpoints[ $device ] . 'px)';
		} else {
			$this->current_media = $device;
		}
		return $this;
	}

	/**
	 * Close the current media query block.
	 *
	 * @return self
	 */
	public function end_media_query() {
		$this->current_media = null;
		return $this;
	}

	/**
	 * Render the collected rules as minified CSS.
	 *
	 * @return string
	 */
	public function get_output() {
		$css = '';

		if ( isset( $this->rules['base'] ) ) {
			$css .= $this->render_rules( $this->rules['base'] );
		}

		foreach ( $this->rules as $media => $selectors ) {
			if ( 'base' === $media ) {
				continue;
			}
			$inner = $this->render_rules( $selectors );
			if ( '' !== $inner ) {
				$css .= $media . '{' . $inner . '}';
			}
		}

		return $css;
	}

	/**
	 * Render a selector => properties map.
	 *
	 * @param array $selectors Selector map.
	 * @return string
	 */
	private function render_rules( $selectors ) {
		$css = '';
		foreach ( $selectors as $selector => $properties ) {
			if ( empty( $properties ) ) {
				continue;
			}
			$declarations = '';
			foreach ( $properties as $property => $value ) {
				$declarations .= $property . ':' . $value . ';';
			}
			$css .= $selector . '{' . $declarations . '}';
		}
		return $css;
	}
}
