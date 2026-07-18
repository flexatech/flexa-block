<?php
declare(strict_types=1);
/**
 * Inline Editor — front-end, click-to-edit text for Flexa blocks.
 *
 * Flexa blocks are dynamic (`save: () => null`); their editable text lives in the
 * block-comment JSON as an attribute, and render.php regenerates the HTML. So,
 * rather than touching innerHTML, this service:
 *   1. annotates each editable block's root element with `data-flexa-edit-id`
 *      (its unique blockId) + `data-flexa-edit-name` via WP_HTML_Tag_Processor on
 *      `render_block` — exactly like Animations::inject, whitelist-checked,
 *   2. ships a small front-end module (build/frontend/inline-editor.js) that turns
 *      the mapped child elements into contenteditable regions and POSTs edits,
 *   3. exposes a REST route that finds the block by blockId, rewrites the single
 *      attribute, re-serializes and saves — which regenerates the cached CSS via
 *      the existing save_post hook — and records the edit as a comment note.
 *
 * Editing UI and the REST write are gated by `should_render()` /
 * `user_can_edit_inline()`: the post must be editable by the user, inline editing
 * must be enabled, and the user's role must be allowed (admins always are).
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end inline text editor for Flexa blocks.
 */
final class Inline_Editor {

	const REST_NS     = 'flexa-block/v1';
	const OPTION_NAME = 'flexa_block_settings';

	/**
	 * Editable registry: block name → editable fields.
	 *
	 * Each field: `attr` (block attribute), `selector` (rendered element class to
	 * click), `allowed` (wp_kses allowlist key). Repeater fields add `repeat` =>
	 * true and `key` — the Nth matched element maps to `items[N][key]`. This is the
	 * single source of truth for annotation, REST validation and JS click-targeting.
	 *
	 * @var array<string, array{fields: list<array<string, mixed>>}>
	 */
	const EDITABLE = [
		'flexa/text'    => [
			'fields' => [
				[ 'attr' => 'content', 'selector' => '.flexa-text__content', 'allowed' => 'rich' ],
			],
		],
		'flexa/heading' => [
			'fields' => [
				[ 'attr' => 'content', 'selector' => '.flexa-heading__title', 'allowed' => 'inline' ],
				[ 'attr' => 'subheadingContent', 'selector' => '.flexa-heading__subheading', 'allowed' => 'inline' ],
			],
		],
		'flexa/button'  => [
			'fields' => [
				[ 'attr' => 'text', 'selector' => '.flexa-button__text', 'allowed' => 'minimal' ],
			],
		],
		// Composite blocks — several top-level string attributes each. The two
		// promo button labels share a class, so they are scoped by the button
		// modifier class.
		'flexa/banner'  => [
			'fields' => [
				[ 'attr' => 'heading', 'selector' => '.flexa-promo__heading', 'allowed' => 'minimal' ],
				[ 'attr' => 'description', 'selector' => '.flexa-promo__description', 'allowed' => 'minimal-link' ],
				[ 'attr' => 'primaryText', 'selector' => '.flexa-promo__button--primary .flexa-promo__button-text', 'allowed' => 'minimal' ],
				[ 'attr' => 'secondaryText', 'selector' => '.flexa-promo__button--secondary .flexa-promo__button-text', 'allowed' => 'minimal' ],
			],
		],
		'flexa/cta'     => [
			'fields' => [
				[ 'attr' => 'heading', 'selector' => '.flexa-promo__heading', 'allowed' => 'minimal' ],
				[ 'attr' => 'description', 'selector' => '.flexa-promo__description', 'allowed' => 'minimal-link' ],
				[ 'attr' => 'primaryText', 'selector' => '.flexa-promo__button--primary .flexa-promo__button-text', 'allowed' => 'minimal' ],
				[ 'attr' => 'secondaryText', 'selector' => '.flexa-promo__button--secondary .flexa-promo__button-text', 'allowed' => 'minimal' ],
			],
		],
		'flexa/info-box' => [
			'fields' => [
				[ 'attr' => 'prefix', 'selector' => '.flexa-info-box__prefix', 'allowed' => 'minimal' ],
				[ 'attr' => 'title', 'selector' => '.flexa-info-box__title', 'allowed' => 'minimal' ],
				[ 'attr' => 'description', 'selector' => '.flexa-info-box__description', 'allowed' => 'minimal-link' ],
				[ 'attr' => 'buttonText', 'selector' => '.flexa-info-box__button-text', 'allowed' => 'minimal' ],
			],
		],
		'flexa/testimonial' => [
			'fields' => [
				[ 'attr' => 'title', 'selector' => '.flexa-testimonial__title', 'allowed' => 'minimal' ],
				[ 'attr' => 'quote', 'selector' => '.flexa-testimonial__quote', 'allowed' => 'minimal-link' ],
				[ 'attr' => 'authorName', 'selector' => '.flexa-testimonial__name', 'allowed' => 'minimal' ],
				[ 'attr' => 'authorJob', 'selector' => '.flexa-testimonial__job', 'allowed' => 'minimal' ],
			],
		],
		'flexa/team-member' => [
			'fields' => [
				[ 'attr' => 'name', 'selector' => '.flexa-team-member__name', 'allowed' => 'minimal' ],
				[ 'attr' => 'role', 'selector' => '.flexa-team-member__role', 'allowed' => 'minimal' ],
				[ 'attr' => 'bio', 'selector' => '.flexa-team-member__bio', 'allowed' => 'minimal-link' ],
			],
		],
		// Simple single-string blocks (plain text).
		'flexa/table-of-content' => [
			'fields' => [
				[ 'attr' => 'title', 'selector' => '.flexa-toc__title', 'allowed' => 'plain' ],
			],
		],
		'flexa/process-bar' => [
			'fields' => [
				[ 'attr' => 'title', 'selector' => '.flexa-process-bar__title', 'allowed' => 'plain' ],
			],
		],
		'flexa/before-after' => [
			'fields' => [
				[ 'attr' => 'beforeLabel', 'selector' => '.flexa-before-after__label--before', 'allowed' => 'plain' ],
				[ 'attr' => 'afterLabel', 'selector' => '.flexa-before-after__label--after', 'allowed' => 'plain' ],
			],
		],
		// Countdown unit labels live in a `labels` object attribute; each label
		// element is scoped by its unit modifier class.
		'flexa/countdown' => [
			'fields' => [
				[ 'attr' => 'labels', 'key' => 'days', 'object' => true, 'selector' => '.flexa-countdown__unit--days .flexa-countdown__label', 'allowed' => 'plain' ],
				[ 'attr' => 'labels', 'key' => 'hours', 'object' => true, 'selector' => '.flexa-countdown__unit--hours .flexa-countdown__label', 'allowed' => 'plain' ],
				[ 'attr' => 'labels', 'key' => 'minutes', 'object' => true, 'selector' => '.flexa-countdown__unit--minutes .flexa-countdown__label', 'allowed' => 'plain' ],
				[ 'attr' => 'labels', 'key' => 'seconds', 'object' => true, 'selector' => '.flexa-countdown__unit--seconds .flexa-countdown__label', 'allowed' => 'plain' ],
			],
		],
		// Subscribe-form field labels (each field is its own block). The trailing
		// required-marker span is stripped client-side before saving.
		'flexa/subscribe-form-name'     => [ 'fields' => [ [ 'attr' => 'label', 'selector' => '.flexa-field__label', 'allowed' => 'plain' ] ] ],
		'flexa/subscribe-form-email'    => [ 'fields' => [ [ 'attr' => 'label', 'selector' => '.flexa-field__label', 'allowed' => 'plain' ] ] ],
		'flexa/subscribe-form-phone'    => [ 'fields' => [ [ 'attr' => 'label', 'selector' => '.flexa-field__label', 'allowed' => 'plain' ] ] ],
		'flexa/subscribe-form-textarea' => [ 'fields' => [ [ 'attr' => 'label', 'selector' => '.flexa-field__label', 'allowed' => 'plain' ] ] ],
		'flexa/subscribe-form-url'      => [ 'fields' => [ [ 'attr' => 'label', 'selector' => '.flexa-field__label', 'allowed' => 'plain' ] ] ],
		'flexa/subscribe-form-date'     => [ 'fields' => [ [ 'attr' => 'label', 'selector' => '.flexa-field__label', 'allowed' => 'plain' ] ] ],
		'flexa/subscribe-form-select'   => [ 'fields' => [ [ 'attr' => 'label', 'selector' => '.flexa-field__label', 'allowed' => 'plain' ] ] ],
		'flexa/subscribe-form-radio'    => [ 'fields' => [ [ 'attr' => 'label', 'selector' => '.flexa-field__label', 'allowed' => 'plain' ] ] ],
		'flexa/subscribe-form-checkbox' => [ 'fields' => [ [ 'attr' => 'label', 'selector' => '.flexa-field__label', 'allowed' => 'plain' ] ] ],
		'flexa/subscribe-form-toggle'   => [ 'fields' => [ [ 'attr' => 'label', 'selector' => '.flexa-field__label', 'allowed' => 'plain' ] ] ],
		'flexa/subscribe-form-upload'   => [ 'fields' => [ [ 'attr' => 'label', 'selector' => '.flexa-field__label', 'allowed' => 'plain' ] ] ],
		// Repeater blocks — text inside an array attribute. The Nth rendered item
		// carries `data-flexa-item="<source index>"` (stamped in render.php), so
		// the front-end maps it back to the exact array entry.
		'flexa/faq' => [
			'fields' => [
				[ 'attr' => 'items', 'key' => 'question', 'repeat' => true, 'selector' => '.flexa-faq__question-text', 'allowed' => 'minimal' ],
				[ 'attr' => 'items', 'key' => 'answer', 'repeat' => true, 'selector' => '.flexa-faq__answer-content', 'allowed' => 'post' ],
			],
		],
		'flexa/tabs' => [
			'fields' => [
				[ 'attr' => 'tabs', 'key' => 'label', 'repeat' => true, 'selector' => '.flexa-tabs__label', 'allowed' => 'plain' ],
				[ 'attr' => 'tabs', 'key' => 'content', 'repeat' => true, 'selector' => '.flexa-tabs__panel', 'allowed' => 'post' ],
			],
		],
		'flexa/steps' => [
			'fields' => [
				[ 'attr' => 'items', 'key' => 'title', 'repeat' => true, 'selector' => '.flexa-steps__title', 'allowed' => 'minimal' ],
				[ 'attr' => 'items', 'key' => 'description', 'repeat' => true, 'selector' => '.flexa-steps__description', 'allowed' => 'minimal' ],
			],
		],
		'flexa/timeline' => [
			'fields' => [
				[ 'attr' => 'items', 'key' => 'date', 'repeat' => true, 'selector' => '.flexa-timeline__date', 'allowed' => 'minimal' ],
				[ 'attr' => 'items', 'key' => 'title', 'repeat' => true, 'selector' => '.flexa-timeline__title', 'allowed' => 'minimal' ],
				[ 'attr' => 'items', 'key' => 'description', 'repeat' => true, 'selector' => '.flexa-timeline__description', 'allowed' => 'minimal' ],
			],
		],
		'flexa/pricing-table' => [
			'fields' => [
				[ 'attr' => 'items', 'key' => 'name', 'repeat' => true, 'selector' => '.flexa-pricing-table__name', 'allowed' => 'plain' ],
				[ 'attr' => 'items', 'key' => 'badge', 'repeat' => true, 'selector' => '.flexa-pricing-table__badge', 'allowed' => 'plain' ],
				[ 'attr' => 'items', 'key' => 'priceMonthly', 'repeat' => true, 'selector' => '.flexa-pricing-table__price-group--monthly .flexa-pricing-table__amount', 'allowed' => 'plain' ],
				[ 'attr' => 'items', 'key' => 'periodMonthly', 'repeat' => true, 'selector' => '.flexa-pricing-table__price-group--monthly .flexa-pricing-table__period', 'allowed' => 'plain' ],
				[ 'attr' => 'items', 'key' => 'priceYearly', 'repeat' => true, 'selector' => '.flexa-pricing-table__price-group--yearly .flexa-pricing-table__amount', 'allowed' => 'plain' ],
				[ 'attr' => 'items', 'key' => 'periodYearly', 'repeat' => true, 'selector' => '.flexa-pricing-table__price-group--yearly .flexa-pricing-table__period', 'allowed' => 'plain' ],
				[ 'attr' => 'items', 'key' => 'features', 'repeat' => true, 'join' => "\n", 'selector' => '.flexa-pricing-table__feature', 'allowed' => 'plain' ],
				[ 'attr' => 'items', 'key' => 'ctaText', 'repeat' => true, 'selector' => '.flexa-pricing-table__cta', 'allowed' => 'plain' ],
			],
		],
		'flexa/counter' => [
			'fields' => [
				[ 'attr' => 'items', 'key' => 'label', 'repeat' => true, 'selector' => '.flexa-counter__label', 'allowed' => 'minimal' ],
			],
		],
		'flexa/icon-list' => [
			'fields' => [
				[ 'attr' => 'items', 'key' => 'text', 'repeat' => true, 'selector' => '.flexa-icon-list__text', 'allowed' => 'minimal' ],
			],
		],
		'flexa/star-rating' => [
			'fields' => [
				[ 'attr' => 'title', 'selector' => '.flexa-star-rating__title', 'allowed' => 'minimal' ],
			],
		],
		// The trigger text lives inside the trigger <button>; only the button/text
		// trigger types render it (icon/image triggers have no text element). The
		// label is plain text (esc_html in render.php).
		'flexa/modal' => [
			'fields' => [
				[ 'attr' => 'triggerText', 'selector' => '.flexa-modal__trigger-text', 'allowed' => 'plain' ],
			],
		],
		// Comparison table — 2-axis. Columns + row labels are single-axis
		// repeaters; feature cells write rows[row].values[col] (the `cell` flag),
		// with the column index carried on each editable cell's data-flexa-cell.
		'flexa/comparison-table' => [
			'fields' => [
				[ 'attr' => 'columns', 'key' => 'title', 'repeat' => true, 'selector' => '.flexa-comparison-table__col-title', 'allowed' => 'plain' ],
				[ 'attr' => 'columns', 'key' => 'subtitle', 'repeat' => true, 'selector' => '.flexa-comparison-table__col-subtitle', 'allowed' => 'plain' ],
				[ 'attr' => 'columns', 'key' => 'badge', 'repeat' => true, 'selector' => '.flexa-comparison-table__badge', 'allowed' => 'plain' ],
				[ 'attr' => 'columns', 'key' => 'ctaText', 'repeat' => true, 'selector' => '.flexa-comparison-table__cta', 'allowed' => 'plain' ],
				[ 'attr' => 'rows', 'key' => 'label', 'repeat' => true, 'selector' => '.flexa-comparison-table__label', 'allowed' => 'plain' ],
				[ 'attr' => 'rows', 'key' => 'values', 'repeat' => true, 'cell' => true, 'selector' => '.flexa-comparison-table__cell', 'allowed' => 'plain' ],
			],
		],
		// Notice — a title + message the user types (single-string attributes).
		'flexa/notice' => [
			'fields' => [
				[ 'attr' => 'title', 'selector' => '.flexa-notice__title', 'allowed' => 'minimal' ],
				[ 'attr' => 'content', 'selector' => '.flexa-notice__content', 'allowed' => 'rich' ],
			],
		],
		// Data Table — 2-axis like the comparison table: column header labels are a
		// single-axis repeater; body cells write rows[row].values[col] (the `cell`
		// flag), with the column index carried on each cell's data-flexa-cell.
		'flexa/data-table' => [
			'fields' => [
				[ 'attr' => 'columns', 'key' => 'label', 'repeat' => true, 'selector' => '.flexa-data-table__col-label', 'allowed' => 'plain' ],
				[ 'attr' => 'rows', 'key' => 'values', 'repeat' => true, 'cell' => true, 'selector' => '.flexa-data-table__cell', 'allowed' => 'plain' ],
			],
		],
	];

	/**
	 * Default inline-editor settings (merged under the stored option).
	 *
	 * @var array{enabled: bool, disabled_blocks: list<string>, roles: list<string>}
	 */
	const DEFAULTS = [
		'enabled'         => true,
		'disabled_blocks' => [],
		'roles'           => [ 'editor' ],
	];

	/**
	 * Memoized should_render() result for the current request.
	 *
	 * @var bool|null
	 */
	private static $should = null;

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_filter( 'render_block', [ __CLASS__, 'annotate' ], 20, 2 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ], 25 );
		add_action( 'wp_footer', [ __CLASS__, 'render_toolbar' ] );
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/* ---------------------------------------------------------------------
	 * Settings + gating
	 * ------------------------------------------------------------------ */

	/**
	 * Inline-editor settings merged over defaults.
	 *
	 * @return array{enabled: bool, disabled_blocks: list<string>, roles: list<string>}
	 */
	public static function settings(): array {
		$stored = get_option( self::OPTION_NAME, [] );
		$ie     = is_array( $stored ) && isset( $stored['inline_editor'] ) && is_array( $stored['inline_editor'] )
			? $stored['inline_editor']
			: [];

		return [
			'enabled'         => isset( $ie['enabled'] ) ? (bool) $ie['enabled'] : self::DEFAULTS['enabled'],
			'disabled_blocks' => isset( $ie['disabled_blocks'] ) && is_array( $ie['disabled_blocks'] )
				? array_values( array_map( 'strval', $ie['disabled_blocks'] ) )
				: self::DEFAULTS['disabled_blocks'],
			'roles'           => isset( $ie['roles'] ) && is_array( $ie['roles'] )
				? array_values( array_map( 'strval', $ie['roles'] ) )
				: self::DEFAULTS['roles'],
		];
	}

	/**
	 * Whether inline editing is enabled at all.
	 */
	public static function is_enabled(): bool {
		return self::settings()['enabled'];
	}

	/**
	 * Block names that carry at least one editable field.
	 *
	 * @return list<string>
	 */
	public static function editable_block_names(): array {
		return array_keys( self::EDITABLE );
	}

	/**
	 * The editable fields for a block (empty if none).
	 *
	 * @param string $name Block name.
	 * @return list<array<string, mixed>>
	 */
	private static function fields_for( string $name ): array {
		return isset( self::EDITABLE[ $name ] ) ? self::EDITABLE[ $name ]['fields'] : [];
	}

	/**
	 * Whether the current user's role is allowed to edit inline.
	 *
	 * Administrators are always allowed; every other role must be listed in the
	 * `roles` setting. This is an additional gate on top of the per-post
	 * `edit_post` capability checked elsewhere.
	 */
	public static function user_can_edit_inline(): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}
		$roles = self::settings()['roles'];
		if ( empty( $roles ) ) {
			return false;
		}
		$user = wp_get_current_user();
		return ! empty( array_intersect( (array) $user->roles, $roles ) );
	}

	/**
	 * Whether the editing UI should render on the current request.
	 */
	public static function should_render(): bool {
		if ( null !== self::$should ) {
			return self::$should;
		}

		$should = false;
		if ( is_singular() && is_user_logged_in() && self::is_enabled() ) {
			$post_id = get_queried_object_id();
			if ( $post_id && current_user_can( 'edit_post', $post_id ) && self::user_can_edit_inline() ) {
				$should = true;
			}
		}

		self::$should = $should;
		return $should;
	}

	/**
	 * A `data-flexa-item` marker for a repeater item's wrapper element.
	 *
	 * Repeater render.php loops call this with the ORIGINAL array index so the
	 * front-end can map a rendered item back to its exact `items[N]` entry even
	 * when empty items are skipped. Returns '' (nothing) for non-editors, so it
	 * never bloats the public markup.
	 *
	 * @param int $index Original array index of the item.
	 * @return string Leading-space attribute string, or ''.
	 */
	public static function item_attr( int $index ): string {
		if ( ! self::should_render() ) {
			return '';
		}
		return ' data-flexa-item="' . $index . '"';
	}

	/**
	 * A `data-flexa-cell` marker for a matrix cell's column index.
	 *
	 * Used for 2-axis blocks (comparison table): the row index comes from the
	 * row's `data-flexa-item`, the column index from this. Only text cells get
	 * one (boolean check/cross cells are not text-editable).
	 *
	 * @param int $index Column index within the row's values array.
	 * @return string Leading-space attribute string, or ''.
	 */
	public static function cell_attr( int $index ): string {
		if ( ! self::should_render() ) {
			return '';
		}
		return ' data-flexa-cell="' . $index . '"';
	}

	/**
	 * Whether a block slug has been disabled in settings.
	 */
	private static function is_block_disabled( string $block_name ): bool {
		$slug = str_replace( 'flexa/', '', $block_name );
		return in_array( $slug, self::settings()['disabled_blocks'], true );
	}

	/* ---------------------------------------------------------------------
	 * Render-time annotation
	 * ------------------------------------------------------------------ */

	/**
	 * Mark an editable block's root element with its blockId + name.
	 *
	 * @param string $content Rendered block HTML.
	 * @param array  $block   Parsed block (name + attrs).
	 * @return string
	 */
	public static function annotate( $content, $block ) {
		if ( ! self::should_render() ) {
			return $content;
		}
		if ( ! is_string( $content ) || '' === trim( $content ) ) {
			return $content;
		}

		$name = $block['blockName'] ?? '';
		if ( ! isset( self::EDITABLE[ $name ] ) || self::is_block_disabled( $name ) ) {
			return $content;
		}

		$block_id = (string) ( $block['attrs']['blockId'] ?? '' );
		if ( '' === $block_id ) {
			return $content;
		}

		if ( ! class_exists( '\WP_HTML_Tag_Processor' ) ) {
			return $content;
		}

		$processor = new \WP_HTML_Tag_Processor( $content );
		if ( ! $processor->next_tag() ) {
			return $content;
		}

		$processor->set_attribute( 'data-flexa-edit-id', $block_id );
		$processor->set_attribute( 'data-flexa-edit-name', $name );

		return $processor->get_updated_html();
	}

	/* ---------------------------------------------------------------------
	 * Front-end assets
	 * ------------------------------------------------------------------ */

	/**
	 * Enqueue the front-end module + styles and expose the bootstrap object.
	 */
	public static function enqueue(): void {
		if ( ! self::should_render() ) {
			return;
		}

		$rel = 'build/frontend/inline-editor.js';
		if ( ! file_exists( FLEXA_BLOCK_DIR . $rel ) ) {
			return;
		}

		$asset_path = FLEXA_BLOCK_DIR . 'build/frontend/inline-editor.asset.php';
		$asset      = file_exists( $asset_path ) ? require $asset_path : [ 'dependencies' => [], 'version' => FLEXA_BLOCK_VER ];
		$version    = $asset['version'] ?? FLEXA_BLOCK_VER;

		wp_enqueue_script(
			'flexa-block-inline-editor',
			FLEXA_BLOCK_URL . $rel,
			$asset['dependencies'] ?? [],
			$version,
			true
		);

		$css_rel = 'build/frontend/inline-editor.css';
		if ( file_exists( FLEXA_BLOCK_DIR . $css_rel ) ) {
			wp_enqueue_style( 'flexa-block-inline-editor', FLEXA_BLOCK_URL . $css_rel, [], $version );
		}

		wp_set_script_translations( 'flexa-block-inline-editor', 'flexa-block', FLEXA_BLOCK_DIR . 'languages' );

		wp_localize_script(
			'flexa-block-inline-editor',
			'flexaBlockInlineEditor',
			[
				'postId'   => get_queried_object_id(),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'endpoint' => esc_url_raw( rest_url( self::REST_NS . '/inline-edit' ) ),
				'fields'   => self::js_fields(),
				'i18n'     => [
					'editing' => __( 'Editing', 'flexa-block' ),
					'save'    => __( 'Save', 'flexa-block' ),
					'cancel'  => __( 'Cancel', 'flexa-block' ),
					'saved'   => __( 'Saved', 'flexa-block' ),
					'saving'  => __( 'Saving…', 'flexa-block' ),
					'error'   => __( 'Could not save', 'flexa-block' ),
				],
			]
		);
	}

	/**
	 * The JS-facing slice of the registry (no server-only allowlist keys).
	 *
	 * @return array<string, list<array<string, mixed>>>
	 */
	private static function js_fields(): array {
		$out = [];
		foreach ( self::EDITABLE as $name => $def ) {
			if ( self::is_block_disabled( $name ) ) {
				continue;
			}
			$fields = [];
			foreach ( self::fields_for( $name ) as $field ) {
				$entry = [
					'selector' => $field['selector'],
					'attr'     => $field['attr'],
				];
				if ( ! empty( $field['repeat'] ) ) {
					$entry['repeat'] = true;
					$entry['key']    = $field['key'];
					if ( ! empty( $field['join'] ) ) {
						$entry['join'] = (string) $field['join'];
					}
					if ( ! empty( $field['cell'] ) ) {
						$entry['cell'] = true;
					}
				} elseif ( ! empty( $field['object'] ) ) {
					$entry['object'] = true;
					$entry['key']    = $field['key'];
				}
				$fields[] = $entry;
			}
			$out[ $name ] = $fields;
		}
		return $out;
	}

	/**
	 * Print the editing toolbar (hidden until a block is being edited).
	 */
	public static function render_toolbar(): void {
		if ( ! self::should_render() ) {
			return;
		}
		?>
		<div class="flexa-fee-toolbar" hidden>
			<div class="flexa-fee-toolbar__inner">
				<span class="flexa-fee-toolbar__label"><?php echo esc_html__( 'Editing', 'flexa-block' ); ?></span>
				<button type="button" class="flexa-fee-btn flexa-fee-btn--save"><?php echo esc_html__( 'Save', 'flexa-block' ); ?></button>
				<button type="button" class="flexa-fee-btn flexa-fee-btn--cancel"><?php echo esc_html__( 'Cancel', 'flexa-block' ); ?></button>
				<span class="flexa-fee-toolbar__msg" aria-live="polite"></span>
			</div>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * REST
	 * ------------------------------------------------------------------ */

	/**
	 * Register the inline-edit route.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NS,
			'/inline-edit',
			[
				'methods'             => 'POST',
				'callback'            => [ __CLASS__, 'handle_save' ],
				'permission_callback' => [ __CLASS__, 'rest_permission' ],
				'args'                => [
					'postId'    => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'blockId'   => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'blockName' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'attr'      => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'index'     => [
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'subindex'  => [
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'key'       => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					// Sanitized in the handler against the field's own allowlist.
					'value'     => [
						'required' => true,
						'type'     => 'string',
					],
				],
			]
		);
	}

	/**
	 * Only users who may edit the target post AND pass the role gate.
	 *
	 * Returns a descriptive WP_Error (not just false) so the front-end toolbar
	 * can tell the user exactly why a save was refused.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return bool|\WP_Error
	 */
	public static function rest_permission( $request ) {
		if ( ! self::is_enabled() ) {
			return new \WP_Error( 'flexa_disabled', __( 'Front-end editing is turned off. Enable it under Flexa Block → Editing.', 'flexa-block' ), [ 'status' => 403 ] );
		}
		$post_id = absint( $request->get_param( 'postId' ) );
		if ( $post_id < 1 || ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'flexa_forbidden', __( 'You do not have permission to edit this content.', 'flexa-block' ), [ 'status' => 403 ] );
		}
		if ( ! self::user_can_edit_inline() ) {
			return new \WP_Error( 'flexa_role_denied', __( 'Your user role is not allowed to edit on the front end.', 'flexa-block' ), [ 'status' => 403 ] );
		}
		return true;
	}

	/**
	 * Apply a single-attribute edit to a block and save the post.
	 *
	 * @param \WP_REST_Request $request Incoming request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function handle_save( $request ) {
		$post_id   = (int) $request->get_param( 'postId' );
		$block_id  = (string) $request->get_param( 'blockId' );
		$name      = (string) $request->get_param( 'blockName' );
		$attr      = (string) $request->get_param( 'attr' );
		$index_raw = $request->get_param( 'index' );
		$sub_raw   = $request->get_param( 'subindex' );
		$key       = $request->get_param( 'key' );
		$key       = ( null === $key ) ? null : (string) $key;
		$value     = (string) $request->get_param( 'value' );

		if ( self::is_block_disabled( $name ) ) {
			return new \WP_Error(
				'flexa_disabled',
				sprintf(
					/* translators: %s: block type slug. */
					__( 'Front-end editing is turned off for the “%s” block. Turn it on under Flexa Block → Editing.', 'flexa-block' ),
					str_replace( 'flexa/', '', $name )
				),
				[ 'status' => 403 ]
			);
		}

		$has_index = ( null !== $index_raw );
		$field     = self::find_field( $name, $attr, $key, $has_index );
		if ( null === $field ) {
			return new \WP_Error( 'flexa_invalid_field', __( 'This text cannot be edited from the front end.', 'flexa-block' ), [ 'status' => 400 ] );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'flexa_not_found', __( 'The post could not be found. Try reloading the page.', 'flexa-block' ), [ 'status' => 404 ] );
		}

		$blocks = parse_blocks( $post->post_content );
		$flat   = [];
		self::flatten( $blocks, $flat );

		$ref = null;
		foreach ( $flat as &$candidate ) {
			if ( ( $candidate['blockName'] ?? '' ) === $name
				&& (string) ( $candidate['attrs']['blockId'] ?? '' ) === $block_id ) {
				$ref = &$candidate;
				break;
			}
		}
		unset( $candidate );

		if ( null === $ref ) {
			return new \WP_Error( 'flexa_block_not_found', __( 'This block is no longer in the post — it may have been moved or removed in the editor. Reload the page and try again.', 'flexa-block' ), [ 'status' => 404 ] );
		}

		$clean = wp_kses( $value, self::allowed_html( (string) $field['allowed'] ) );

		if ( ! empty( $field['cell'] ) ) {
			// 2-axis matrix cell: attr[row]['values'][col] (comparison table).
			$row_i = absint( $index_raw );
			$col_i = absint( $sub_raw );
			if ( ! isset( $ref['attrs'][ $attr ] ) || ! is_array( $ref['attrs'][ $attr ] ) ) {
				$ref['attrs'][ $attr ] = self::default_attr_array( $name, $attr );
			}
			if ( ! isset( $ref['attrs'][ $attr ][ $row_i ] ) || ! is_array( $ref['attrs'][ $attr ][ $row_i ] ) ) {
				return new \WP_Error( 'flexa_bad_index', __( 'This list item could not be matched. Reload the page and try again.', 'flexa-block' ), [ 'status' => 400 ] );
			}
			if ( ! isset( $ref['attrs'][ $attr ][ $row_i ]['values'] ) || ! is_array( $ref['attrs'][ $attr ][ $row_i ]['values'] ) ) {
				$ref['attrs'][ $attr ][ $row_i ]['values'] = [];
			}
			$old                                             = (string) ( $ref['attrs'][ $attr ][ $row_i ]['values'][ $col_i ] ?? '' );
			$ref['attrs'][ $attr ][ $row_i ]['values'][ $col_i ] = $clean;
		} elseif ( ! empty( $field['repeat'] ) ) {
			$i        = absint( $index_raw );
			$item_key = (string) $field['key'];
			// The array attribute is omitted from the block markup while it still
			// equals the block.json default (Gutenberg only serializes changed
			// attributes), so seed it from the registered default before indexing.
			if ( ! isset( $ref['attrs'][ $attr ] ) || ! is_array( $ref['attrs'][ $attr ] ) ) {
				$ref['attrs'][ $attr ] = self::default_attr_array( $name, $attr );
			}
			if ( ! isset( $ref['attrs'][ $attr ][ $i ] ) || ! is_array( $ref['attrs'][ $attr ][ $i ] ) ) {
				return new \WP_Error( 'flexa_bad_index', __( 'This list item could not be matched. Reload the page and try again.', 'flexa-block' ), [ 'status' => 400 ] );
			}
			$old                                      = (string) ( $ref['attrs'][ $attr ][ $i ][ $item_key ] ?? '' );
			$ref['attrs'][ $attr ][ $i ][ $item_key ] = $clean;
		} elseif ( ! empty( $field['object'] ) ) {
			$obj_key = (string) $field['key'];
			if ( ! isset( $ref['attrs'][ $attr ] ) || ! is_array( $ref['attrs'][ $attr ] ) ) {
				$ref['attrs'][ $attr ] = self::default_attr_array( $name, $attr );
			}
			$old                               = (string) ( $ref['attrs'][ $attr ][ $obj_key ] ?? '' );
			$ref['attrs'][ $attr ][ $obj_key ] = $clean;
		} else {
			$old                   = (string) ( $ref['attrs'][ $attr ] ?? '' );
			$ref['attrs'][ $attr ] = $clean;
		}

		$updated = serialize_blocks( $blocks );

		// wp_slash: wp_update_post runs wp_unslash on the content, so pre-slash it
		// to keep backslashes/quotes in the serialized block markup intact.
		$result = wp_update_post(
			[
				'ID'           => $post_id,
				'post_content' => wp_slash( $updated ),
			],
			true
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		self::log_edit( $post_id, $name, $old, $clean );

		return rest_ensure_response( [ 'success' => true ] );
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	/**
	 * Resolve a registry field for a block by attribute (+ repeater key).
	 *
	 * @param string      $name Block name.
	 * @param string      $attr Attribute name.
	 * @param string|null $key  Repeater item key, or null for a top-level field.
	 * @return array<string, mixed>|null
	 */
	private static function find_field( string $name, string $attr, ?string $key, bool $has_index ): ?array {
		$key = ( null === $key || '' === $key ) ? null : $key;

		foreach ( self::fields_for( $name ) as $field ) {
			if ( ( $field['attr'] ?? '' ) !== $attr ) {
				continue;
			}
			$fkey = isset( $field['key'] ) ? (string) $field['key'] : null;

			if ( ! empty( $field['repeat'] ) ) {
				// Array item: items[index][key].
				if ( $has_index && null !== $key && $fkey === $key ) {
					return $field;
				}
			} elseif ( ! empty( $field['object'] ) ) {
				// Object sub-key: attr[key].
				if ( ! $has_index && null !== $key && $fkey === $key ) {
					return $field;
				}
			} elseif ( ! $has_index && null === $key ) {
				// Plain top-level attribute.
				return $field;
			}
		}
		return null;
	}

	/**
	 * The block.json default for an array/object attribute (empty array if none).
	 *
	 * Blocks omit attributes that still equal their default from the serialized
	 * markup, so an edit to a still-default list/object must rebuild from this.
	 *
	 * @param string $name Block name.
	 * @param string $attr Attribute name.
	 * @return array<int|string, mixed>
	 */
	private static function default_attr_array( string $name, string $attr ): array {
		if ( ! class_exists( '\WP_Block_Type_Registry' ) ) {
			return [];
		}
		$type = \WP_Block_Type_Registry::get_instance()->get_registered( $name );
		if ( ! $type || ! is_array( $type->attributes ) ) {
			return [];
		}
		$schema  = $type->attributes[ $attr ] ?? null;
		$default = is_array( $schema ) ? ( $schema['default'] ?? null ) : null;
		return is_array( $default ) ? $default : [];
	}

	/**
	 * Flatten a parsed block tree into a list of references (depth-first).
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks (by ref).
	 * @param array<int, array<string, mixed>> $flat   Accumulator (by ref).
	 */
	private static function flatten( array &$blocks, array &$flat ): void {
		foreach ( $blocks as &$block ) {
			$flat[] = &$block;
			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				self::flatten( $block['innerBlocks'], $flat );
			}
		}
		unset( $block );
	}

	/**
	 * The wp_kses allowlist for a field, mirroring the block's render.php.
	 *
	 * @param string $key One of: rich (text), inline (heading), minimal (button).
	 * @return array<string, array<string, bool|array<int, string>>>
	 */
	public static function allowed_html( string $key ): array {
		$inline = [
			'strong' => [],
			'em'     => [],
			'b'      => [],
			'i'      => [],
			'br'     => [],
			'span'   => [ 'style' => true, 'class' => true ],
			'mark'   => [ 'style' => true, 'class' => true ],
			'sub'    => [],
			'sup'    => [],
		];

		$minimal = [
			'strong' => [],
			'em'     => [],
			'b'      => [],
			'i'      => [],
			'br'     => [],
			'span'   => [],
		];

		switch ( $key ) {
			case 'plain':
				// Plain text only — strips every tag, keeps the text.
				return [];
			case 'post':
				// The full post allowlist (e.g. FAQ answers use wp_kses_post).
				return wp_kses_allowed_html( 'post' );
			case 'rich':
				$inline['a'] = [ 'href' => [], 'target' => [], 'rel' => [] ];
				return $inline;
			case 'minimal':
				return $minimal;
			case 'minimal-link':
				$minimal['a'] = [ 'href' => [], 'target' => [], 'rel' => [] ];
				return $minimal;
			case 'inline':
			default:
				return $inline;
		}
	}

	/**
	 * Record an edit as a WordPress comment note (audit trail).
	 *
	 * @param int    $post_id Post ID.
	 * @param string $name    Block name.
	 * @param string $old     Previous value.
	 * @param string $new     New value.
	 */
	private static function log_edit( int $post_id, string $name, string $old, string $new ): void {
		$user  = wp_get_current_user();
		$type  = str_replace( 'flexa/', '', $name );
		$old_s = mb_strimwidth( wp_strip_all_tags( $old ), 0, 80, '…' );
		$new_s = mb_strimwidth( wp_strip_all_tags( $new ), 0, 80, '…' );

		$note = sprintf(
			/* translators: 1: block type, 2: old text, 3: new text. */
			__( 'Edited %1$s from front end: "%2$s" → "%3$s"', 'flexa-block' ),
			$type,
			$old_s,
			$new_s
		);

		wp_insert_comment(
			[
				'comment_post_ID'      => $post_id,
				'comment_content'      => $note,
				'comment_type'         => 'note',
				'user_id'              => $user->ID,
				'comment_author'       => $user->display_name,
				'comment_author_email' => $user->user_email,
				'comment_approved'     => 1,
			]
		);
	}
}
