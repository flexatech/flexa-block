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
				[ 'attr' => 'content', 'selector' => '.flexa-heading__title', 'allowed' => 'inline', 'link' => [ 'attr' => 'url', 'target' => 'linkTarget', 'anchor' => '.flexa-heading__link' ] ],
				[ 'attr' => 'subheadingContent', 'selector' => '.flexa-heading__subheading', 'allowed' => 'inline' ],
			],
		],
		'flexa/button'  => [
			'fields' => [
				[ 'attr' => 'text', 'selector' => '.flexa-button__text', 'allowed' => 'minimal', 'link' => [ 'attr' => 'url', 'target' => 'linkTarget', 'anchor' => '.flexa-button__link' ] ],
			],
		],
		// Composite blocks — several top-level string attributes each. The two
		// promo button labels share a class, so they are scoped by the button
		// modifier class.
		'flexa/banner'  => [
			'fields' => [
				[ 'attr' => 'heading', 'selector' => '.flexa-promo__heading', 'allowed' => 'minimal' ],
				[ 'attr' => 'description', 'selector' => '.flexa-promo__description', 'allowed' => 'minimal-link' ],
				[ 'attr' => 'primaryText', 'selector' => '.flexa-promo__button--primary .flexa-promo__button-text', 'allowed' => 'minimal', 'link' => [ 'attr' => 'primaryUrl', 'target' => 'primaryTarget', 'anchor' => '.flexa-promo__button--primary' ] ],
				[ 'attr' => 'secondaryText', 'selector' => '.flexa-promo__button--secondary .flexa-promo__button-text', 'allowed' => 'minimal', 'link' => [ 'attr' => 'secondaryUrl', 'target' => 'secondaryTarget', 'anchor' => '.flexa-promo__button--secondary' ] ],
			],
		],
		'flexa/cta'     => [
			'fields' => [
				[ 'attr' => 'heading', 'selector' => '.flexa-promo__heading', 'allowed' => 'minimal' ],
				[ 'attr' => 'description', 'selector' => '.flexa-promo__description', 'allowed' => 'minimal-link' ],
				[ 'attr' => 'primaryText', 'selector' => '.flexa-promo__button--primary .flexa-promo__button-text', 'allowed' => 'minimal', 'link' => [ 'attr' => 'primaryUrl', 'target' => 'primaryTarget', 'anchor' => '.flexa-promo__button--primary' ] ],
				[ 'attr' => 'secondaryText', 'selector' => '.flexa-promo__button--secondary .flexa-promo__button-text', 'allowed' => 'minimal', 'link' => [ 'attr' => 'secondaryUrl', 'target' => 'secondaryTarget', 'anchor' => '.flexa-promo__button--secondary' ] ],
			],
		],
		'flexa/info-box' => [
			'fields' => [
				[ 'attr' => 'prefix', 'selector' => '.flexa-info-box__prefix', 'allowed' => 'minimal' ],
				[ 'attr' => 'title', 'selector' => '.flexa-info-box__title', 'allowed' => 'minimal' ],
				[ 'attr' => 'description', 'selector' => '.flexa-info-box__description', 'allowed' => 'minimal-link' ],
				[ 'attr' => 'buttonText', 'selector' => '.flexa-info-box__button-text', 'allowed' => 'minimal', 'link' => [ 'attr' => 'buttonUrl', 'target' => 'buttonTarget', 'anchor' => '.flexa-info-box__button' ] ],
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
	 * Memoized blockIds of the queried post's own content.
	 *
	 * @var array<string, true>|null
	 */
	private static $own_ids = null;

	/**
	 * Where the block being rendered right now is stored, innermost last.
	 *
	 * A page is assembled from several stores: the post itself, template parts
	 * (header, footer), synced patterns, other posts in a query loop. Each of
	 * those arrives through a block whose render wraps the blocks it contains,
	 * so a stack pushed in `pre_render_block` and popped in `render_block` of
	 * that wrapper always knows which store an inner block came from.
	 *
	 * @var list<array{block: string, type: string, post: int, ids: array<string, true>}>
	 */
	private static $scopes = [];

	/**
	 * Memoized content versions, keyed by post ID. See store_version().
	 *
	 * @var array<int, string>
	 */
	private static $versions = [];

	/** Wrapper blocks that switch the store their inner blocks come from. */
	const SCOPE_BLOCKS = [ 'core/template-part', 'core/block', 'core/post-content' ];

	/**
	 * Memoized registry() result for the current request.
	 *
	 * @var array<string, array{fields: list<array<string, mixed>>}>|null
	 */
	private static $registry = null;

	/**
	 * The editable registry, open to add-on plugins.
	 *
	 * `EDITABLE` above describes this plugin's own blocks. An add-on (Flexa Block
	 * Pro) is the only place that knows its blocks' render markup and attribute
	 * shapes, so it must be able to describe them itself rather than have this
	 * file carry knowledge of blocks it does not ship. Same extension model as the
	 * `flexa_block_blocks` catalog filter, and the reason the whole pipeline —
	 * annotation, the front-end module, the REST write — is reused instead of
	 * duplicated per add-on.
	 *
	 * Entries are shape-checked rather than trusted: a malformed one would
	 * otherwise surface as a fatal deep inside annotate() or handle_save(), far
	 * from the add-on that caused it. Unknown keys are kept — a newer add-on may
	 * carry field flags an older copy of this plugin does not read yet.
	 *
	 * @return array<string, array{fields: list<array<string, mixed>>}>
	 */
	public static function registry(): array {
		if ( null !== self::$registry ) {
			return self::$registry;
		}

		/**
		 * Filters the blocks and fields that can be edited from the front end.
		 *
		 * Standard filter semantics: add-ons append their own block names. The
		 * array is keyed by block name; each entry is `[ 'fields' => [ … ] ]` in
		 * the shape documented on Inline_Editor::EDITABLE.
		 *
		 * @param array<string, array{fields: list<array<string, mixed>>}> $registry Block name → editable fields.
		 */
		$filtered = apply_filters( 'flexa_block_inline_editable', self::EDITABLE );

		$registry = [];
		if ( is_array( $filtered ) ) {
			foreach ( $filtered as $name => $def ) {
				if ( ! is_string( $name ) || '' === $name ) {
					continue;
				}
				$fields = self::valid_fields( $def );
				if ( $fields ) {
					$registry[ $name ] = [ 'fields' => $fields ];
				}
			}
		}

		self::$registry = $registry;
		return $registry;
	}

	/**
	 * The well-formed fields of a registry entry (empty when unusable).
	 *
	 * A field is only usable if it names both a block attribute and a rendered
	 * element to click. Repeater / object / matrix-cell fields additionally need
	 * `key`, without which find_field() could never match them and every save
	 * would be rejected as an invalid field. `allowed` may be omitted — allowed_html()
	 * falls back to the most restrictive inline allowlist.
	 *
	 * @param mixed $def Registry entry.
	 * @return list<array<string, mixed>>
	 */
	private static function valid_fields( $def ): array {
		if ( ! is_array( $def ) || ! isset( $def['fields'] ) || ! is_array( $def['fields'] ) ) {
			return [];
		}

		$out = [];
		foreach ( $def['fields'] as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			if ( ! isset( $field['attr'] ) || ! is_string( $field['attr'] ) || '' === $field['attr'] ) {
				continue;
			}
			if ( ! isset( $field['selector'] ) || ! is_string( $field['selector'] ) || '' === $field['selector'] ) {
				continue;
			}
			$needs_key = ! empty( $field['repeat'] ) || ! empty( $field['object'] ) || ! empty( $field['cell'] );
			if ( $needs_key && ( ! isset( $field['key'] ) || ! is_string( $field['key'] ) || '' === $field['key'] ) ) {
				continue;
			}
			$out[] = $field;
		}

		return $out;
	}

	/**
	 * The settings slug for a block name — the part after the namespace.
	 *
	 * `flexa/faq` and `flexa-pro/flip-box` both reduce to their bare slug, which
	 * is what the Editing screen stores in `disabled_blocks`. Deriving it by
	 * trimming a literal `flexa/` (as three call sites used to) left an add-on
	 * name's slash intact, and the `sanitize_key()` the settings screen runs
	 * downstream then silently turned `flexa-pro/flip-box` into
	 * `flexa-proflip-box` — a slug that matches nothing, so an add-on block could
	 * never be switched off.
	 *
	 * @param string $name Block name.
	 * @return string
	 */
	public static function slug_for( string $name ): string {
		$pos = strrpos( $name, '/' );
		return ( false === $pos ) ? $name : substr( $name, $pos + 1 );
	}

	/**
	 * Register hooks.
	 */
	public static function init(): void {
		add_filter( 'pre_render_block', [ __CLASS__, 'enter_scope' ], PHP_INT_MAX, 2 );
		add_filter( 'render_block', [ __CLASS__, 'leave_scope' ], 1, 2 );
		add_filter( 'render_block', [ __CLASS__, 'annotate' ], 20, 2 );
		add_filter( 'body_class', [ __CLASS__, 'body_class' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ], 25 );
		add_action( 'wp_footer', [ __CLASS__, 'render_toolbar' ] );
		add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
	}

	/**
	 * Mark the page while the inline editor is live.
	 *
	 * Some editable text is decorative in its resting state — click-through so a
	 * control underneath stays the hit target, or hidden until its parent is
	 * hovered. Such text can be bound and still never receive a click, which
	 * reads as "this field is not editable". A block needs a way to relax that
	 * for editors only, and `.flexa-fee-editable` cannot do it: the front-end
	 * module puts that class on the text element itself, whereas the rule usually
	 * has to reach an ANCESTOR (a tooltip's `pointer-events`, say). A body-level
	 * flag is the signal that works in both directions, and it costs a visitor
	 * nothing because it is only ever printed for a user who may edit the post.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		if ( is_array( $classes ) && self::should_render() ) {
			$classes[] = 'flexa-fee-on';
		}
		return $classes;
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
		return array_keys( self::registry() );
	}

	/**
	 * The editable fields for a block (empty if none).
	 *
	 * @param string $name Block name.
	 * @return list<array<string, mixed>>
	 */
	private static function fields_for( string $name ): array {
		$registry = self::registry();
		return isset( $registry[ $name ] ) ? $registry[ $name ]['fields'] : [];
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
	 * The blockIds stored in the queried post's own content.
	 *
	 * Read once per request: `annotate()` runs for every block on the page.
	 *
	 * @return array<string, true>
	 */
	private static function own_block_ids(): array {
		if ( null === self::$own_ids ) {
			$post          = get_post( get_queried_object_id() );
			self::$own_ids = $post instanceof \WP_Post ? Block_Patcher::block_ids( (string) $post->post_content ) : [];
		}
		return self::$own_ids;
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
		return in_array( self::slug_for( $block_name ), self::settings()['disabled_blocks'], true );
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

		$name     = $block['blockName'] ?? '';
		$registry = self::registry();
		if ( ! isset( $registry[ $name ] ) || self::is_block_disabled( $name ) ) {
			return $content;
		}

		$block_id = (string) ( $block['attrs']['blockId'] ?? '' );
		if ( '' === $block_id ) {
			return $content;
		}

		// A block is only offered for editing when it can be saved back to where
		// it is stored. See target_for().
		$target = self::target_for( $block_id );
		if ( null === $target ) {
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
		if ( $target['post'] > 0 ) {
			$processor->set_attribute( 'data-flexa-edit-target', (string) $target['post'] );
			$processor->set_attribute( 'data-flexa-edit-scope', $target['scope'] );
		}
		// The version of the store this block was read from, sent back with a
		// save so a change made since (in the editor, another tab, by another
		// person) is refused instead of silently overwritten.
		$store_id = $target['post'] > 0 ? $target['post'] : (int) get_queried_object_id();
		$processor->set_attribute( 'data-flexa-edit-version', self::store_version( $store_id ) );

		return $processor->get_updated_html();
	}

	/**
	 * Where an edit to this block would be saved, or null when it cannot be.
	 *
	 * - Stored in the post being viewed: editable, saved to that post. This is
	 *   the case `post` 0 stands for, and the only one before shared stores.
	 * - Stored in a template part (header, footer…) that has been customised,
	 *   so it lives in the database: editable by someone who may edit the
	 *   theme's templates, saved to that template part, for every page.
	 * - Stored in a synced pattern: editable by someone who may edit that
	 *   pattern, saved to it, for everywhere the pattern is used.
	 * - Anywhere else: not editable. That covers another post shown by a query
	 *   loop, blocks written straight into the page template, and a template
	 *   part that still comes from the theme's own file. Saving that last one
	 *   would mean creating its database copy, which the Site Editor does and
	 *   front-end editing does not.
	 *
	 * @param string $block_id Block's blockId.
	 * @return array{post: int, scope: string}|null `post` 0 means the viewed post.
	 */
	private static function target_for( string $block_id ): ?array {
		$scope = self::$scopes ? self::$scopes[ count( self::$scopes ) - 1 ] : null;

		if ( null === $scope || 'post' === $scope['type'] ) {
			$viewed = get_queried_object_id();
			if ( null !== $scope && $scope['post'] !== $viewed ) {
				return null;
			}
			return isset( self::own_block_ids()[ $block_id ] ) ? [
				'post'  => 0,
				'scope' => '',
			] : null;
		}

		if ( $scope['post'] < 1 || ! isset( $scope['ids'][ $block_id ] ) ) {
			return null;
		}

		$allowed = 'wp_template_part' === $scope['type']
			? current_user_can( 'edit_theme_options' )
			: current_user_can( 'edit_post', $scope['post'] );

		return $allowed ? [
			'post'  => $scope['post'],
			'scope' => 'wp_template_part' === $scope['type'] ? 'template-part' : 'pattern',
		] : null;
	}

	/**
	 * A token for the exact stored content of a post: changes when anything in
	 * it changes, and only then.
	 *
	 * Used instead of `post_modified_gmt`, which has one-second resolution (two
	 * saves inside a second look the same) and moves on writes that never touch
	 * the content. Comparing what the page shows against what is stored is not
	 * an option either: rendering runs kses, entities and block markup over the
	 * stored value, so the two differ even when nothing has changed.
	 *
	 * @param int $post_id Post ID of the store.
	 * @return string
	 */
	private static function store_version( int $post_id ): string {
		if ( ! isset( self::$versions[ $post_id ] ) ) {
			$post                       = $post_id > 0 ? get_post( $post_id ) : null;
			self::$versions[ $post_id ] = $post instanceof \WP_Post ? md5( (string) $post->post_content ) : '';
		}
		return self::$versions[ $post_id ];
	}

	/**
	 * Note which store the blocks about to render come from.
	 *
	 * Runs last on `pre_render_block` and only when no other filter has
	 * short-circuited the render: a block whose render is replaced never
	 * reaches `render_block`, and a push without its pop would misattribute
	 * every block after it.
	 *
	 * @param string|null $pre          Short-circuit value from earlier filters.
	 * @param array       $parsed_block Block about to render.
	 * @return string|null The short-circuit value, untouched.
	 */
	public static function enter_scope( $pre, $parsed_block ) {
		if ( null !== $pre || ! is_array( $parsed_block ) || ! self::should_render() ) {
			return $pre;
		}

		$name  = (string) ( $parsed_block['blockName'] ?? '' );
		$attrs = is_array( $parsed_block['attrs'] ?? null ) ? $parsed_block['attrs'] : [];

		if ( 'core/post-content' === $name ) {
			self::$scopes[] = [
				'block' => $name,
				'type'  => 'post',
				'post'  => (int) get_the_ID(),
				'ids'   => [],
			];
		} elseif ( 'core/block' === $name ) {
			$ref            = (int) ( $attrs['ref'] ?? 0 );
			$pattern        = $ref > 0 ? get_post( $ref ) : null;
			self::$scopes[] = [
				'block' => $name,
				'type'  => 'wp_block',
				'post'  => $pattern instanceof \WP_Post && 'wp_block' === $pattern->post_type ? $ref : 0,
				'ids'   => $pattern instanceof \WP_Post ? Block_Patcher::block_ids( (string) $pattern->post_content ) : [],
			];
		} elseif ( 'core/template-part' === $name ) {
			$part           = self::template_part_post( $attrs );
			self::$scopes[] = [
				'block' => $name,
				'type'  => 'wp_template_part',
				'post'  => $part instanceof \WP_Post ? (int) $part->ID : 0,
				'ids'   => $part instanceof \WP_Post ? Block_Patcher::block_ids( (string) $part->post_content ) : [],
			];
		}

		return $pre;
	}

	/**
	 * Leave the store a wrapper block opened, once it has rendered.
	 *
	 * @param string $content      Rendered block HTML.
	 * @param mixed  $parsed_block Block that rendered.
	 * @return string The HTML, untouched.
	 */
	public static function leave_scope( $content, $parsed_block ) {
		$name = is_array( $parsed_block ) ? (string) ( $parsed_block['blockName'] ?? '' ) : '';

		if ( self::$scopes && in_array( $name, self::SCOPE_BLOCKS, true ) && self::$scopes[ count( self::$scopes ) - 1 ]['block'] === $name ) {
			array_pop( self::$scopes );
		}

		return $content;
	}

	/**
	 * The database post behind a template-part block, if it has one.
	 *
	 * Only a template part that has been customised (in the Site Editor, say)
	 * has one; until then it is read from the theme's file and there is no post
	 * to save into.
	 *
	 * @param array<string, mixed> $attrs Template-part block attributes.
	 * @return \WP_Post|null
	 */
	private static function template_part_post( array $attrs ): ?\WP_Post {
		$slug = isset( $attrs['slug'] ) ? sanitize_title( (string) $attrs['slug'] ) : '';
		if ( '' === $slug || ! function_exists( 'get_block_template' ) ) {
			return null;
		}

		$theme    = isset( $attrs['theme'] ) && '' !== $attrs['theme'] ? (string) $attrs['theme'] : get_stylesheet();
		$template = get_block_template( $theme . '//' . $slug, 'wp_template_part' );
		$post_id  = $template instanceof \WP_Block_Template && $template->wp_id ? (int) $template->wp_id : 0;
		$post     = $post_id > 0 ? get_post( $post_id ) : null;

		return $post instanceof \WP_Post && 'wp_template_part' === $post->post_type ? $post : null;
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
					'undo'    => __( 'Undo', 'flexa-block' ),
					'undone'  => __( 'Change undone', 'flexa-block' ),
					'linkEdit'    => __( 'Edit link', 'flexa-block' ),
					'linkApply'   => __( 'Apply', 'flexa-block' ),
					'linkInvalid' => __( 'Links can start with http://, https://, mailto: or tel:, or be a path such as /contact.', 'flexa-block' ),
					'saving'  => __( 'Saving…', 'flexa-block' ),
					'error'   => __( 'Could not save', 'flexa-block' ),
					'unsaved' => __( 'You have unsaved changes. Save them before you go?', 'flexa-block' ),
					'discard' => __( 'Discard the changes you made to this text?', 'flexa-block' ),
					'scope'   => [
						'template-part' => __( 'This text is part of the site template (header, footer…). Saving changes it on every page that uses this part.', 'flexa-block' ),
						'pattern'       => __( 'This text is part of a synced pattern. Saving changes it everywhere the pattern is used.', 'flexa-block' ),
					],
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
		foreach ( self::registry() as $name => $def ) {
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
				// The link that wraps this text, so the toolbar can offer "Edit link".
				if ( isset( $field['link'] ) && is_array( $field['link'] ) && ! empty( $field['link']['attr'] ) && ! empty( $field['link']['anchor'] ) ) {
					$entry['link'] = [
						'attr'   => (string) $field['link']['attr'],
						'target' => (string) ( $field['link']['target'] ?? '' ),
						'anchor' => (string) $field['link']['anchor'],
					];
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
				<button type="button" class="flexa-fee-btn flexa-fee-btn--undo" hidden><?php echo esc_html__( 'Undo', 'flexa-block' ); ?></button>
				<button type="button" class="flexa-fee-btn flexa-fee-btn--link" hidden><?php echo esc_html__( 'Edit link', 'flexa-block' ); ?></button>
			</div>
		</div>
		<?php
		/*
		 * Modal shown before an in-page action would leave an unsaved edit.
		 * Reloading or closing the tab still uses the browser's native dialog.
		 */
		?>
		<div
			class="flexa-fee-toolbar__prompt"
			role="alertdialog"
			aria-modal="true"
			aria-labelledby="flexa-fee-unsaved-title"
			aria-describedby="flexa-fee-unsaved-message"
			hidden
		>
			<div class="flexa-fee-toolbar__prompt-dialog" role="document">
				<div class="flexa-fee-toolbar__prompt-content">
					<span class="flexa-fee-toolbar__prompt-icon" aria-hidden="true">!</span>
					<div>
						<h2 id="flexa-fee-unsaved-title" class="flexa-fee-toolbar__prompt-title"><?php echo esc_html__( 'Unsaved changes', 'flexa-block' ); ?></h2>
						<p id="flexa-fee-unsaved-message" class="flexa-fee-toolbar__prompt-msg"></p>
					</div>
				</div>
				<div class="flexa-fee-toolbar__prompt-actions">
					<button type="button" class="flexa-fee-btn flexa-fee-btn--cancel" data-choice="stay"><?php echo esc_html__( 'Keep editing', 'flexa-block' ); ?></button>
					<button type="button" class="flexa-fee-btn flexa-fee-btn--discard" data-choice="discard"><?php echo esc_html__( 'Discard changes', 'flexa-block' ); ?></button>
					<button type="button" class="flexa-fee-btn flexa-fee-btn--save" data-choice="save"><?php echo esc_html__( 'Save', 'flexa-block' ); ?></button>
				</div>
			</div>
		</div>
		<?php
		/*
		 * The link editor: where a button's, heading's or promo button's address and
		 * "open in a new tab" are changed. A dialog of its own rather than part of
		 * the toolbar, so it works the same whether or not text is being edited.
		 */
		?>
		<div
			class="flexa-fee-toolbar__prompt flexa-fee-linkdialog"
			role="dialog"
			aria-modal="true"
			aria-labelledby="flexa-fee-link-title"
			hidden
		>
			<form class="flexa-fee-toolbar__prompt-dialog" novalidate>
				<div class="flexa-fee-toolbar__prompt-content">
					<div class="flexa-fee-linkdialog__body">
						<h2 id="flexa-fee-link-title" class="flexa-fee-toolbar__prompt-title"><?php echo esc_html__( 'Edit link', 'flexa-block' ); ?></h2>
						<label class="flexa-fee-linkdialog__label" for="flexa-fee-link-url"><?php echo esc_html__( 'Web address', 'flexa-block' ); ?></label>
						<input id="flexa-fee-link-url" class="flexa-fee-linkdialog__input" type="text" inputmode="url" autocomplete="off" spellcheck="false" placeholder="https://" />
						<label class="flexa-fee-linkdialog__check">
							<input id="flexa-fee-link-tab" type="checkbox" />
							<span><?php echo esc_html__( 'Open in a new tab', 'flexa-block' ); ?></span>
						</label>
						<p class="flexa-fee-linkdialog__hint"><?php echo esc_html__( 'Leave the address empty to remove the link.', 'flexa-block' ); ?></p>
						<p class="flexa-fee-linkdialog__error" role="alert" hidden></p>
					</div>
				</div>
				<div class="flexa-fee-toolbar__prompt-actions">
					<button type="button" class="flexa-fee-btn flexa-fee-btn--cancel" data-link="cancel"><?php echo esc_html__( 'Cancel', 'flexa-block' ); ?></button>
					<button type="submit" class="flexa-fee-btn flexa-fee-btn--save" data-link="apply"><?php echo esc_html__( 'Apply', 'flexa-block' ); ?></button>
				</div>
			</form>
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
					// The template part or synced pattern the block is stored in,
					// when it is not the viewed post. See target_for().
					'targetId'  => [
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					// Content version the page was rendered from. See
					// store_version(). Optional so a script cached from before
					// it existed still saves, without the check.
					'version'   => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
					// ID of the log entry this save reverses (the Undo button). Only
					// a label for the log: the save itself is checked like any other.
					'undoOf'    => [
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
					// A link edit: `attr` names the URL attribute and `value` is
					// JSON {"url": "...", "newTab": true|false}. See with_link().
					'link'      => [
						'type'    => 'boolean',
						'default' => false,
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
		$target_id = absint( $request->get_param( 'targetId' ) );
		if ( $target_id > 0 ) {
			$target = self::shared_target( $target_id );
			if ( is_wp_error( $target ) ) {
				return $target;
			}
		}
		return true;
	}

	/**
	 * A shared store an edit may be saved to, if the current user may change it.
	 *
	 * Template parts need `edit_theme_options`, the same as editing them in the
	 * Site Editor, because a change applies to every page that uses the part.
	 * Synced patterns need the right to edit that pattern.
	 *
	 * @param int $target_id Post ID of the template part or synced pattern.
	 * @return \WP_Post|\WP_Error
	 */
	private static function shared_target( int $target_id ) {
		$target = get_post( $target_id );

		if ( ! $target instanceof \WP_Post || ! in_array( $target->post_type, [ 'wp_template_part', 'wp_block' ], true ) ) {
			return new \WP_Error( 'flexa_bad_target', __( 'This part of the page could not be found. Reload the page and try again.', 'flexa-block' ), [ 'status' => 404 ] );
		}

		$allowed = 'wp_template_part' === $target->post_type
			? current_user_can( 'edit_theme_options' )
			: current_user_can( 'edit_post', $target_id );

		if ( ! $allowed ) {
			return new \WP_Error(
				'flexa_forbidden',
				'wp_template_part' === $target->post_type
					? __( 'This text is part of the site template, and your account cannot edit templates.', 'flexa-block' )
					: __( 'This text is part of a synced pattern you do not have permission to edit.', 'flexa-block' ),
				[ 'status' => 403 ]
			);
		}

		return $target;
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
					self::slug_for( $name )
				),
				[ 'status' => 403 ]
			);
		}

		$is_link = (bool) rest_sanitize_boolean( $request->get_param( 'link' ) );
		if ( $is_link ) {
			// A link is not a text field and never goes through kses: its value is
			// checked by Link_Url and only ever lands in the URL attribute.
			$link  = self::find_link( $name, $attr );
			$field = [];
			if ( null === $link ) {
				return new \WP_Error( 'flexa_invalid_field', __( 'This link cannot be edited from the front end.', 'flexa-block' ), [ 'status' => 400 ] );
			}
		} else {
			$link      = null;
			$has_index = ( null !== $index_raw );
			$field     = self::find_field( $name, $attr, $key, $has_index );
			if ( null === $field ) {
				return new \WP_Error( 'flexa_invalid_field', __( 'This text cannot be edited from the front end.', 'flexa-block' ), [ 'status' => 400 ] );
			}
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'flexa_not_found', __( 'The post could not be found. Try reloading the page.', 'flexa-block' ), [ 'status' => 404 ] );
		}

		// Where the block is stored: the viewed post, or a template part or
		// synced pattern shown on it. Permission for a shared store was already
		// checked in rest_permission(); asked again here because this is the
		// object that gets written.
		$store     = $post;
		$target_id = absint( $request->get_param( 'targetId' ) );
		if ( $target_id > 0 ) {
			$store = self::shared_target( $target_id );
			if ( is_wp_error( $store ) ) {
				return $store;
			}
		}

		// Someone has this post open in the block editor. Their next save would
		// overwrite this edit (the editor holds its own copy of the content), so
		// say so now instead. The editor's lock is the signal WordPress itself
		// uses for "someone else is editing"; a lock held by this same user is
		// not reported by wp_check_post_lock().
		if ( ! function_exists( 'wp_check_post_lock' ) ) {
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}
		$holder = wp_check_post_lock( (int) $store->ID );
		if ( $holder ) {
			$user = get_userdata( (int) $holder );

			// How long the lock outlives a closed editor, as a range a person can
			// act on. WordPress keeps the lock for this window after the last
			// heartbeat (150 seconds by default, so "2–3 minutes"); while the
			// editor stays open the heartbeat keeps renewing it, which is why
			// this is phrased as "once they close it" and not as a countdown.
			$window = max( 60, (int) apply_filters( 'wp_check_post_lock_window', 150 ) );
			$low    = (int) floor( $window / MINUTE_IN_SECONDS );
			$high   = (int) ceil( $window / MINUTE_IN_SECONDS );
			$wait   = $low === $high
				? sprintf(
					/* translators: %s: number of minutes */
					_n( 'about %s minute', 'about %s minutes', $low, 'flexa-block' ),
					number_format_i18n( $low )
				)
				: sprintf(
					/* translators: 1: lower number of minutes, 2: upper number of minutes */
					__( 'about %1$s–%2$s minutes', 'flexa-block' ),
					number_format_i18n( $low ),
					number_format_i18n( $high )
				);

			return new \WP_Error(
				'flexa_inline_locked',
				sprintf(
					/* translators: 1: display name of the person editing, 2: how long to wait, for example "about 2–3 minutes" */
					__( '%1$s is editing this in the block editor right now, and their next save would overwrite yours. Once they close the editor, you can save again in %2$s. Your text stays here until then.', 'flexa-block' ),
					$user ? $user->display_name : __( 'Someone', 'flexa-block' ),
					$wait
				),
				[ 'status' => 409 ]
			);
		}

		// The content changed after this page was rendered: saved in the editor,
		// in another tab, or by another person. Writing now would be based on a
		// page the person is no longer looking at.
		$version = (string) $request->get_param( 'version' );
		if ( '' !== $version && ! hash_equals( md5( (string) $store->post_content ), $version ) ) {
			return new \WP_Error(
				'flexa_inline_stale',
				__( 'This content has changed since the page was loaded, so your edit was not saved. Copy your text if you need it, reload the page, and edit again.', 'flexa-block' ),
				[ 'status' => 409 ]
			);
		}

		// Find the one block delimiter this edit is about and rewrite only that.
		// Parsing the whole post and serializing it back would also re-encode
		// every other block, and PHP arrays cannot tell `{}` from `[]`, so a
		// one-word edit could change empty objects anywhere on the page. See
		// Block_Patcher.
		$content = (string) $store->post_content;
		$token   = Block_Patcher::locate( $content, $block_id );

		if ( is_wp_error( $token ) && 'flexa_block_duplicate_block' === $token->get_error_code() ) {
			return new \WP_Error( 'flexa_block_duplicate', __( 'Two blocks on this page share the same id, so this edit could not be matched to one of them. Open the page in the block editor and save it once, then try again.', 'flexa-block' ), [ 'status' => 409 ] );
		}
		if ( is_wp_error( $token ) || $token['name'] !== $name ) {
			return new \WP_Error( 'flexa_block_not_found', __( 'This block is no longer in the post — it may have been moved or removed in the editor. Reload the page and try again.', 'flexa-block' ), [ 'status' => 404 ] );
		}

		// Decoded as objects, so `{}` and `[]` inside the attribute keep their
		// shape. An attribute still equal to its block.json default is absent
		// from the markup (Gutenberg only serializes changed attributes), so an
		// edit to it starts from the registered default.
		$attrs = json_decode( (string) wp_json_encode( $token['attrs'] ) );
		$attrs = $attrs instanceof \stdClass ? $attrs : new \stdClass();

		// What the log shows, and (for a link) the exact state Undo has to send back.
		$after_json = null;

		if ( $is_link ) {
			$changed = self::with_link( $attrs, $name, (array) $link, $value );
			if ( is_wp_error( $changed ) ) {
				return $changed;
			}
			$old        = $changed['old'];
			$clean      = $changed['summary'];
			$log_before = $changed['old_summary'];
			$after_json = $changed['after'];
		} else {
			$clean   = wp_kses( $value, self::allowed_html( (string) $field['allowed'] ) );
			$current = property_exists( $attrs, $attr ) ? $attrs->{$attr} : self::default_attr( $name, $attr );

			$changed = self::with_field(
				$current,
				$field,
				null === $index_raw ? null : absint( $index_raw ),
				null === $sub_raw ? null : absint( $sub_raw ),
				$clean
			);
			if ( is_wp_error( $changed ) ) {
				return $changed;
			}

			$old            = $changed['old'];
			$log_before     = $old;
			$attrs->{$attr} = $changed['value'];
		}

		$updated = Block_Patcher::replace( $content, $token, $attrs );
		if ( is_wp_error( $updated ) ) {
			return $updated;
		}
		if ( $updated === $content ) {
			return rest_ensure_response(
				[
					'success' => true,
					'version' => md5( $content ),
				]
			);
		}

		// KSES is held off for this one write. The only new data in $updated is
		// $clean, which already went through this field's own allowlist above,
		// and it sits inside one block's JSON encoded the way core encodes it.
		// Everything else is the post's stored content, unchanged. Left on, the
		// post KSES filter (for a user without unfiltered_html) runs
		// filter_block_content(), which parses and re-serializes the whole post:
		// exactly the round trip Block_Patcher exists to avoid, applied to every
		// other block on the page. Core's custom-CSS stripping filter stays on;
		// it is surgical and only touches blocks that carry `style.css`.
		//
		// wp_slash: wp_update_post runs wp_unslash on the content, so pre-slash it
		// to keep backslashes/quotes in the serialized block markup intact.
		kses_remove_filters();
		try {
			$result = Inline_Revision_Session::update_post(
				$store,
				[
					'ID'           => (int) $store->ID,
					'post_content' => wp_slash( $updated ),
				]
			);
		} finally {
			// kses_init(), not kses_init_filters(): put back exactly what this user
			// had, which for one with unfiltered_html is no filters at all. A finally
			// also restores it if a save hook throws.
			kses_init();
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Logged on the post that stores the text (a pattern's history shows who
		// edited it from any page). The old note comment is opt-in below.
		$user     = wp_get_current_user();
		$entry_id = wp_generate_uuid4();
		$undo_of  = (string) $request->get_param( 'undoOf' );
		$is_undo  = Inline_Edit_Log::has_entry( (int) $store->ID, $undo_of );
		Inline_Edit_Log::record(
			(int) $store->ID,
			[
				'entry_id'    => $entry_id,
				'action'      => $is_undo ? 'undo' : 'edit',
				'undo_of'     => $is_undo ? $undo_of : '',
				'time_gmt'    => gmdate( 'Y-m-d H:i:s' ),
				'user_id'     => (int) $user->ID,
				'user_name'   => (string) $user->display_name,
				'user_role'   => (string) ( $user->roles ? reset( $user->roles ) : '' ),
				'block_name'  => $name,
				'block_id'    => $block_id,
				'attr'        => $attr,
				'index'       => null === $index_raw ? null : absint( $index_raw ),
				'subindex'    => null === $sub_raw ? null : absint( $sub_raw ),
				'key'         => $key,
				'store_id'    => (int) $store->ID,
				'store_type'  => (string) $store->post_type,
				'post_id'     => $post_id,
				'kind'        => $is_link ? 'link' : 'text',
				'before'      => $log_before,
				'after'       => $clean,
				'revision_id' => Inline_Revision_Session::baseline_id( (int) $store->ID ),
			]
		);

		/**
		 * Filters whether each front-end edit also leaves a "note" comment.
		 *
		 * Off by default: the edit history in the block editor replaces it, and
		 * comments inflate the count shown in the admin bar.
		 *
		 * @param bool $enabled Default false.
		 */
		if ( apply_filters( 'flexa_block_inline_edit_log_as_comment', false ) ) {
			self::log_edit( $post_id, $name, $log_before, $clean );
		}

		// The new version, read back from what was stored rather than hashed from
		// what was sent: save filters may have changed it, and the next edit on
		// this page is checked against the database, not against this request.
		clean_post_cache( (int) $store->ID );
		$saved = get_post( (int) $store->ID );

		// `before` and `logId` are what the Undo button needs: the value to put
		// back, and the entry it will say it reverses.
		$response = [
			'success' => true,
			'version' => $saved instanceof \WP_Post ? md5( (string) $saved->post_content ) : '',
			'before'  => (string) $old,
			'logId'   => $entry_id,
		];
		// For a link, the address as stored (cleaned), so the page shows what was saved.
		if ( null !== $after_json ) {
			$response['after'] = $after_json;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * The link descriptor of a text field, by the name of its URL attribute.
	 *
	 * @param string $name Block name.
	 * @param string $attr URL attribute name.
	 * @return array<string, mixed>|null
	 */
	private static function find_link( string $name, string $attr ): ?array {
		foreach ( self::fields_for( $name ) as $field ) {
			if ( isset( $field['link']['attr'] ) && (string) $field['link']['attr'] === $attr ) {
				return (array) $field['link'];
			}
		}
		return null;
	}

	/**
	 * Apply a link edit to a block's attributes.
	 *
	 * Writes the URL attribute and, when the block has one, the target attribute
	 * in the same pass, so changing the address and "open in a new tab" is a single
	 * save, a single log entry and a single Undo. An attribute that ends up equal
	 * to its block.json default is removed rather than stored, as Gutenberg does.
	 *
	 * @param \stdClass          $attrs Block attributes, changed in place.
	 * @param string             $name  Block name.
	 * @param array<string,mixed> $link  Descriptor from find_link().
	 * @param string             $value JSON: {"url": "...", "newTab": bool}.
	 * @return array{old:string,after:string,old_summary:string,summary:string}|\WP_Error
	 */
	private static function with_link( \stdClass $attrs, string $name, array $link, string $value ) {
		$parsed = json_decode( $value, true );
		if ( ! is_array( $parsed ) ) {
			return new \WP_Error( 'flexa_invalid_link', __( 'The link could not be read. Reload the page and try again.', 'flexa-block' ), [ 'status' => 400 ] );
		}

		$url = Link_Url::clean( is_string( $parsed['url'] ?? null ) ? $parsed['url'] : '' );
		if ( is_wp_error( $url ) ) {
			return $url;
		}

		$url_attr    = (string) $link['attr'];
		$target_attr = (string) ( $link['target'] ?? '' );
		$new_tab     = ! empty( $parsed['newTab'] ) && '' !== $target_attr;

		$read = static function ( string $attr ) use ( $attrs, $name ): string {
			$value = property_exists( $attrs, $attr ) ? $attrs->{$attr} : self::default_attr( $name, $attr );
			return is_string( $value ) ? $value : '';
		};

		$before = [
			'url'    => $read( $url_attr ),
			'newTab' => '' !== $target_attr && '_blank' === $read( $target_attr ),
		];
		$after  = [
			'url'    => $url,
			'newTab' => $new_tab,
		];

		self::put_link_attr( $attrs, $name, $url_attr, $url );
		if ( '' !== $target_attr ) {
			self::put_link_attr( $attrs, $name, $target_attr, $new_tab ? '_blank' : '' );
		}

		return [
			'old'         => (string) wp_json_encode( $before ),
			'after'       => (string) wp_json_encode( $after ),
			'old_summary' => self::link_summary( $before ),
			'summary'     => self::link_summary( $after ),
		];
	}

	/**
	 * Set a string attribute, or drop it when it equals its default.
	 *
	 * @param \stdClass $attrs Attributes.
	 * @param string   $name  Block name.
	 * @param string   $attr  Attribute.
	 * @param string   $value New value.
	 */
	private static function put_link_attr( \stdClass $attrs, string $name, string $attr, string $value ): void {
		$default = self::default_attr( $name, $attr );
		if ( $value === ( is_string( $default ) ? $default : '' ) ) {
			unset( $attrs->{$attr} );
			return;
		}
		$attrs->{$attr} = $value;
	}

	/**
	 * A link as one line of text, for the edit log.
	 *
	 * @param array{url:string,newTab:bool} $state Link state.
	 */
	private static function link_summary( array $state ): string {
		return $state['url'] . ( $state['newTab'] ? ' (new tab)' : '' );
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
	 * The block.json default for an attribute, decoded the way stored ones are.
	 *
	 * Blocks omit attributes that still equal their default from the serialized
	 * markup, so an edit to a still-default value must start from this. Passed
	 * through JSON so maps become objects and lists stay lists, the same shape
	 * `Block_Patcher` decodes a stored attribute into.
	 *
	 * @param string $name Block name.
	 * @param string $attr Attribute name.
	 * @return mixed The default, or null when the block declares none.
	 */
	private static function default_attr( string $name, string $attr ) {
		if ( ! class_exists( '\WP_Block_Type_Registry' ) ) {
			return null;
		}
		$type = \WP_Block_Type_Registry::get_instance()->get_registered( $name );
		if ( ! $type || ! is_array( $type->attributes ) ) {
			return null;
		}
		$schema = $type->attributes[ $attr ] ?? null;
		if ( ! is_array( $schema ) || ! array_key_exists( 'default', $schema ) ) {
			return null;
		}
		return json_decode( (string) wp_json_encode( $schema['default'] ) );
	}

	/**
	 * An attribute's value with one registry field set, and what it held before.
	 *
	 * Works on decoded JSON (objects as stdClass, lists as arrays) and returns a
	 * new value rather than changing the one passed in. The four field shapes
	 * mirror the registry: a cell of a 2-axis table, one key of a list item, one
	 * key of an object attribute, or the attribute itself. Anything that does not
	 * line up with the stored structure is refused rather than created, so an
	 * edit can never invent a list item or leave a gap that would turn a list
	 * into an object when it is encoded.
	 *
	 * @param mixed                $current Current attribute value, or its default.
	 * @param array<string, mixed> $field   Registry field.
	 * @param int|null             $index   List index (row for a cell), when sent.
	 * @param int|null             $sub     Column index for a cell, when sent.
	 * @param string               $clean   New value, already filtered.
	 * @return array{value: mixed, old: string}|\WP_Error
	 */
	private static function with_field( $current, array $field, ?int $index, ?int $sub, string $clean ) {
		$bad_index = new \WP_Error( 'flexa_bad_index', __( 'This list item could not be matched. Reload the page and try again.', 'flexa-block' ), [ 'status' => 400 ] );

		if ( ! empty( $field['cell'] ) ) {
			// 2-axis matrix cell: attr[row].values[col] (comparison table).
			if ( null === $index || null === $sub || ! is_array( $current ) || ! isset( $current[ $index ] ) || ! $current[ $index ] instanceof \stdClass ) {
				return $bad_index;
			}
			$row    = clone $current[ $index ];
			$values = isset( $row->values ) && is_array( $row->values ) ? $row->values : [];
			// The next free column may be filled; anything past it would leave a gap.
			if ( $sub > count( $values ) ) {
				return $bad_index;
			}
			$old               = self::as_text( $values[ $sub ] ?? '' );
			$values[ $sub ]    = $clean;
			$row->values       = array_values( $values );
			$current[ $index ] = $row;

			return [
				'value' => $current,
				'old'   => $old,
			];
		}

		if ( ! empty( $field['repeat'] ) ) {
			if ( null === $index || ! is_array( $current ) || ! isset( $current[ $index ] ) || ! $current[ $index ] instanceof \stdClass ) {
				return $bad_index;
			}
			$key               = (string) $field['key'];
			$item              = clone $current[ $index ];
			$old               = self::as_text( $item->{$key} ?? '' );
			$item->{$key}      = $clean;
			$current[ $index ] = $item;

			return [
				'value' => $current,
				'old'   => $old,
			];
		}

		if ( ! empty( $field['object'] ) ) {
			// Absent, or a `{}` default that PHP handed back as `[]`: start from an
			// empty object, as the previous array-based code started from an
			// empty array.
			if ( ! $current instanceof \stdClass ) {
				$current = new \stdClass();
			}
			$key            = (string) $field['key'];
			$object         = clone $current;
			$old            = self::as_text( $object->{$key} ?? '' );
			$object->{$key} = $clean;

			return [
				'value' => $object,
				'old'   => $old,
			];
		}

		return [
			'value' => $clean,
			'old'   => self::as_text( $current ),
		];
	}

	/**
	 * A stored value as text, for the edit log.
	 *
	 * @param mixed $value Stored value.
	 * @return string
	 */
	private static function as_text( $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
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
		$type  = self::slug_for( $name );
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
