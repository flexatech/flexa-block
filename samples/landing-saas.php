<?php
declare(strict_types=1);
/**
 * Sample: Landing Page (SaaS / product).
 *
 * A complete, media-free landing page built from Flexa blocks: hero banner,
 * stat counters, a feature grid, a how-it-works step list, pricing, testimonials,
 * an FAQ and a closing call to action. Everything is icon-driven (inline SVG in
 * attributes) so the free plugin ships no bundled images and stays
 * WordPress.org-clean.
 *
 * Markup is assembled from PHP arrays through wp_json_encode rather than
 * hand-written JSON, so block attributes stay readable and correctly escaped.
 * Every `blockId` is a placeholder regenerated to a unique id on import.
 *
 * @package Flexa\Block
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return ( static function (): array {
	/**
	 * Serialise one block. Self-closing when it has no inner markup.
	 *
	 * @param string               $name  Block name, e.g. 'flexa/heading'.
	 * @param array<string, mixed> $attrs Block attributes.
	 * @param string               $inner Inner block markup (for InnerBlocks hosts).
	 * @return string
	 */
	$mk = static function ( string $name, array $attrs = [], string $inner = '' ): string {
		$attrs = array_merge( [ 'blockId' => 'fx-00000000' ], $attrs );
		$json  = (string) wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( '' === $inner ) {
			return '<!-- wp:' . $name . ' ' . $json . ' /-->';
		}
		return '<!-- wp:' . $name . ' ' . $json . " -->\n" . $inner . "\n<!-- /wp:" . $name . ' -->';
	};

	/**
	 * Build a builtin icon object (inline SVG stored in attributes, no media).
	 *
	 * @param string $name  Icon name.
	 * @param string $paths SVG path/shape markup.
	 * @return array<string, mixed>
	 */
	$icon = static function ( string $name, string $paths ): array {
		return [
			'source' => 'builtin',
			'name'   => $name,
			'markup' => '<svg class="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>',
			'url'    => '',
			'id'     => null,
		];
	};

	/** A {light,dark} colour background object. */
	$bg = static function ( string $light, string $dark ): array {
		return [
			'type'     => 'color',
			'color'    => [ 'light' => $light, 'dark' => $dark ],
			'gradient' => [ 'light' => '', 'dark' => '' ],
			'image'    => [ 'url' => '', 'id' => null, 'position' => 'center center', 'repeat' => 'no-repeat', 'size' => 'cover', 'attachment' => 'scroll' ],
			'lazyLoad' => false,
		];
	};

	/** A {light,dark} gradient background object. */
	$bgGradient = static function ( string $light, string $dark ): array {
		return [
			'type'     => 'gradient',
			'color'    => [ 'light' => '', 'dark' => '' ],
			'gradient' => [ 'light' => $light, 'dark' => $dark ],
			'image'    => [ 'url' => '', 'id' => null, 'position' => 'center center', 'repeat' => 'no-repeat', 'size' => 'cover', 'attachment' => 'scroll' ],
			'lazyLoad' => false,
		];
	};

	/** Uniform corner radius object. */
	$radius = static function ( string $r ): array {
		return [
			'desktop' => [
				'style'  => '',
				'width'  => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ],
				'color'  => [ 'light' => '', 'dark' => '' ],
				'radius' => [ 'topLeft' => $r, 'topRight' => $r, 'bottomRight' => $r, 'bottomLeft' => $r, 'unit' => 'px' ],
			],
			'tablet'  => [],
			'mobile'  => [],
		];
	};

	/**
	 * Vertical section padding (boxed, centred).
	 *
	 * A boxed container paints its background, radius and padding on the INNER
	 * element, so this padding sits inside the panel. On desktop the inner's
	 * 1200px max-width keeps the panel clear of the viewport; below that there is
	 * no max-width left to do it, and a rounded panel would run flush into both
	 * screen edges. The mobile override therefore adds an OUTER margin — which
	 * the generator puts on the wrapper, outside the painted panel — and trims
	 * the inner side padding so the text does not end up doubly inset.
	 */
	$pad = static function ( string $y ): array {
		return [
			'desktop' => [
				'padding' => [ 'top' => $y, 'right' => '32', 'bottom' => $y, 'left' => '32', 'unit' => 'px' ],
				'margin'  => [ 'top' => '', 'right' => 'auto', 'bottom' => '', 'left' => 'auto', 'unit' => 'px' ],
			],
			'tablet'  => [],
			'mobile'  => [
				'padding' => [ 'top' => $y, 'right' => '20', 'bottom' => $y, 'left' => '20', 'unit' => 'px' ],
				'margin'  => [ 'top' => '', 'right' => '16', 'bottom' => '', 'left' => '16', 'unit' => 'px' ],
			],
		];
	};

	/*
	 * Section palette, named once. Every entry is a {light,dark} pair because
	 * that is the shape every colour attribute in the plugin takes; the dark half
	 * is what makes the sample survive a site with dark mode switched on.
	 *
	 * These are all values from the editor's own DEFAULT_PALETTE, so each one
	 * shows up as an already-selected swatch in the block sidebar — a sample is
	 * there to be opened and fiddled with, and an off-palette hex would leave the
	 * picker looking blank.
	 */
	$ink    = [ 'light' => '#111827', 'dark' => '#f9fafb' ];
	$muted  = [ 'light' => '#6b7280', 'dark' => '#9ca3af' ];
	$accent = [ 'light' => '#2563eb', 'dark' => '#60a5fa' ];
	$line   = [ 'light' => '#e5e7eb', 'dark' => '#374151' ];

	/** Tinted chip behind an accent glyph (feature icons, step markers). */
	$chipBg = [ 'light' => '#eff6ff', 'dark' => 'rgba(59,130,246,0.14)' ];

	/** Rating stars — the one accent that is deliberately not the brand blue. */
	$star = [ 'light' => '#f59e0b', 'dark' => '#fbbf24' ];

	/*
	 * Text and button colours for the page's two DARK surfaces: the hero and the
	 * closing CTA. The dark half of each pair is empty on purpose — these
	 * surfaces are already dark in light mode, so they need no dark-mode variant
	 * and emit no second set of rules.
	 *
	 * The promo buttons expose colours only (no border, radius or padding), so an
	 * outline secondary is not available; a translucent white fill carries the
	 * primary/secondary split instead.
	 */
	$onDark = [
		'heading'      => [ 'light' => '#ffffff', 'dark' => '' ],
		'body'         => [ 'light' => '#cbd5e1', 'dark' => '' ],
		'btnText'      => [ 'light' => '#ffffff', 'dark' => '' ],
		'btnFill'      => [ 'light' => '#2563eb', 'dark' => '' ],
		'btnFillHover' => [ 'light' => '#3b82f6', 'dark' => '' ],
		'ghostFill'    => [ 'light' => 'rgba(255,255,255,0.10)', 'dark' => '' ],
		'ghostHover'   => [ 'light' => 'rgba(255,255,255,0.18)', 'dark' => '' ],
	];

	/*
	 * The card lift, kept as its own value because not every block nests this
	 * object the same way: Counter / Info Box / Steps take it as `boxShadow`,
	 * while Pricing Table takes the identical shape as `planBoxShadow`. Only one
	 * shadow layer is available (the attribute is a single h/v/blur/spread/colour
	 * set), so it is one deliberately firm layer instead of the stacked pair a
	 * design system would normally use. The negative spread pulls the shadow in
	 * tighter than the card before it blurs, which reads as a lift rather than a
	 * grey halo on all four sides. `enabled` gates the whole thing — every block
	 * ships the shape switched off, so it has to be flipped here.
	 */
	$cardShadow = [
		'horizontal' => '0',
		'vertical'   => '10',
		'blur'       => '28',
		'spread'     => '-6',
		'color'      => [ 'light' => 'rgba(17,24,39,0.16)', 'dark' => 'rgba(0,0,0,0.55)' ],
		'inset'      => false,
		'enabled'    => true,
	];

	/**
	 * The shared card surface: white panel, hairline border, lifted shadow.
	 *
	 * Returned as a PARTIAL attribute set to array_merge() into any block whose
	 * wrapper is a card (the Counter stats, the Info Box features), so the page
	 * carries one card language instead of the same numbers copied per section.
	 *
	 * The lift itself lives in $cardShadow, which the Pricing Table reuses under
	 * a different attribute name.
	 */
	$cardStyle = static function () use ( $bg, $line, $cardShadow ): array {
		return [
			'background' => $bg( '#ffffff', '#111827' ),
			'border'     => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => $line,
					'radius' => [ 'topLeft' => '16', 'topRight' => '16', 'bottomRight' => '16', 'bottomLeft' => '16', 'unit' => 'px' ],
				],
				'tablet'  => [],
				'mobile'  => [],
			],
			'boxShadow'  => $cardShadow,
			'spacing'    => [
				'desktop' => [
					'padding' => [ 'top' => '28', 'right' => '24', 'bottom' => '28', 'left' => '24', 'unit' => 'px' ],
					'margin'  => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ],
				],
				'tablet'  => [],
				'mobile'  => [ 'padding' => [ 'top' => '22', 'right' => '18', 'bottom' => '22', 'left' => '18', 'unit' => 'px' ] ],
			],
		];
	};

	/** Centred section heading. */
	$heading = static function ( string $text ) use ( $mk ): string {
		return $mk(
			'flexa/heading',
			[
				'content'   => $text,
				'tag'       => 'h2',
				'alignment' => [ 'desktop' => 'center', 'tablet' => '', 'mobile' => '' ],
			]
		);
	};

	/** Centred lead paragraph. */
	$lead = static function ( string $text ) use ( $mk ): string {
		return $mk(
			'flexa/text',
			[
				'content'   => $text,
				'htmlTag'   => 'p',
				'alignment' => [ 'desktop' => 'center', 'tablet' => '', 'mobile' => '' ],
			]
		);
	};

	/* ---------------------------------------------------------------------------
	 * Hero.
	 *
	 * The eyebrow is a Text block in the banner's InnerBlocks strip (`__top`),
	 * which sits above the promo fields and inherits the banner's centring — so a
	 * background plus a 999px radius turn it into a centred pill with no extra CSS.
	 * The promo buttons expose colours only (no radius/padding), so their shape
	 * stays the theme's and the primary/secondary split is carried by fill alone.
	 * ------------------------------------------------------------------------- */
	$eyebrow = $mk(
		'flexa/text',
		[
			'content'    => __( '40+ blocks · No page builder', 'flexa-block' ),
			'htmlTag'    => 'p',
			'alignment'  => [ 'desktop' => 'center', 'tablet' => '', 'mobile' => '' ],
			'typography' => [
				'desktop' => [
					'fontSize'      => [ 'value' => '13', 'unit' => 'px' ],
					'fontWeight'    => '600',
					'letterSpacing' => [ 'value' => '0.08', 'unit' => 'em' ],
					'textTransform' => 'uppercase',
					'lineHeight'    => '1',
				],
				'tablet'  => [],
				'mobile'  => [],
			],
			// A translucent chip rather than a solid one, so it sits on the dark
			// hero without punching a bright hole in it.
			'textColor'  => [ 'light' => '#93c5fd', 'dark' => '' ],
			'background' => $bg( 'rgba(255,255,255,0.08)', '' ),
			'border'     => [
				'desktop' => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => [ 'light' => 'rgba(255,255,255,0.18)', 'dark' => '' ],
					'radius' => [ 'topLeft' => '999', 'topRight' => '999', 'bottomRight' => '999', 'bottomLeft' => '999', 'unit' => 'px' ],
				],
				'tablet'  => [],
				'mobile'  => [],
			],
			// All four padding sides are written on purpose: spacing_shorthand()
			// emits `0` for any side left blank, so a partial box would wipe the
			// block's own padding on the other three.
			'spacing'    => [
				'desktop' => [
					'padding' => [ 'top' => '7', 'right' => '16', 'bottom' => '7', 'left' => '16', 'unit' => 'px' ],
					'margin'  => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ],
				],
				'tablet'  => [],
				'mobile'  => [],
			],
		]
	);

	$hero = $mk(
		'flexa/banner',
		[
			'heading'       => __( 'Build beautiful WordPress pages, faster', 'flexa-block' ),
			'headingTag'    => 'h1',
			'description'   => __( 'A powerful block library for the WordPress editor. Design landing pages and marketing sites without writing code.', 'flexa-block' ),
			'primaryText'   => __( 'Get started', 'flexa-block' ),
			'primaryUrl'    => '#',
			'primaryIcon'   => array_merge(
				$icon( 'arrow-right', '<path d="M5 12h14M13 6l6 6-6 6"/>' ),
				[ 'position' => 'after' ]
			),
			'showSecondary' => true,
			'secondaryText' => __( 'See features', 'flexa-block' ),
			'secondaryUrl'  => '#',

			/*
			 * Media-free depth: two soft radial glows over a dark linear base,
			 * which reads like an abstract hero image without shipping one byte
			 * of media (the free plugin's samples carry no files — imagery is a
			 * Pro Examples / Starter Templates concern). Layered gradients are a
			 * single background-image value, so they pass sanitize_gradient() as
			 * one gradient string. The user can still switch Background to Image
			 * and drop in their own; this is only the default.
			 *
			 * Deliberately DARK: a hero is the first section on the page, and a
			 * theme with a transparent overlay header draws its nav over it in
			 * light text. A sample cannot see or style that header — the only
			 * thing it controls is how light this area is — so it takes the side
			 * that overlay headers assume. A theme with a solid header is
			 * unaffected.
			 *
			 * The dark half of every pair below is left empty on purpose: this
			 * hero is already dark, so it needs no dark-mode variant and emits no
			 * second set of rules.
			 */
			'background'            => $bgGradient(
				'radial-gradient(1080px 620px at 14% 12%, rgba(59,130,246,0.32) 0%, rgba(59,130,246,0) 58%), '
				. 'radial-gradient(880px 540px at 86% 16%, rgba(139,92,246,0.26) 0%, rgba(139,92,246,0) 60%), '
				. 'linear-gradient(170deg, #0b1120 0%, #111827 55%, #17143a 100%)',
				''
			),
			'size'                  => [
				'desktop' => [ 'minHeight' => [ 'value' => '580', 'unit' => 'px' ] ],
				'tablet'  => [ 'minHeight' => [ 'value' => '460', 'unit' => 'px' ] ],
				'mobile'  => [ 'minHeight' => [ 'value' => '400', 'unit' => 'px' ] ],
			],
			'spacing'               => [
				'desktop' => [
					'padding' => [ 'top' => '104', 'right' => '32', 'bottom' => '104', 'left' => '32', 'unit' => 'px' ],
					'margin'  => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ],
				],
				'tablet'  => [ 'padding' => [ 'top' => '80', 'right' => '24', 'bottom' => '80', 'left' => '24', 'unit' => 'px' ] ],
				'mobile'  => [ 'padding' => [ 'top' => '64', 'right' => '20', 'bottom' => '64', 'left' => '20', 'unit' => 'px' ] ],
			],
			// The box keeps the content on the site grid while the wash stays
			// full-bleed; the narrower content max-width gives the heading a
			// deliberate two-line measure instead of one very long line.
			'contentBoxWidth'       => [ 'desktop' => [ 'value' => '1200', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
			'contentMaxWidth'       => [ 'desktop' => [ 'value' => '700', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
			'contentGap'            => [ 'desktop' => [ 'value' => '32', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [ 'value' => '24', 'unit' => 'px' ] ],
			'contentAlign'          => [ 'desktop' => 'center', 'tablet' => '', 'mobile' => '' ],
			'headingTypography'     => [
				'desktop' => [
					'fontSize'      => [ 'value' => '56', 'unit' => 'px' ],
					'fontWeight'    => '700',
					'letterSpacing' => [ 'value' => '-1.2', 'unit' => 'px' ],
					'lineHeight'    => '1.08',
				],
				'tablet'  => [
					'fontSize'      => [ 'value' => '40', 'unit' => 'px' ],
					'letterSpacing' => [ 'value' => '-0.8', 'unit' => 'px' ],
				],
				'mobile'  => [
					'fontSize'      => [ 'value' => '32', 'unit' => 'px' ],
					'letterSpacing' => [ 'value' => '-0.5', 'unit' => 'px' ],
					'lineHeight'    => '1.15',
				],
			],
			'headingColor'          => $onDark['heading'],
			'descriptionTypography' => [
				'desktop' => [ 'fontSize' => [ 'value' => '19', 'unit' => 'px' ], 'lineHeight' => '1.65' ],
				'tablet'  => [],
				'mobile'  => [ 'fontSize' => [ 'value' => '17', 'unit' => 'px' ] ],
			],
			'descriptionColor'      => $onDark['body'],
			'primaryTextColor'      => $onDark['btnText'],
			'primaryBgColor'        => $onDark['btnFill'],
			'primaryHover'          => [
				'text'       => $onDark['btnText'],
				'background' => $onDark['btnFillHover'],
			],
			'secondaryTextColor'    => $onDark['btnText'],
			'secondaryBgColor'      => $onDark['ghostFill'],
			'secondaryHover'        => [
				'text'       => $onDark['btnText'],
				'background' => $onDark['ghostHover'],
			],
		],
		$eyebrow
	);

	/* ---------------------------------------------------------------------------
	 * Stats.
	 * ------------------------------------------------------------------------- */
	/*
	 * One Counter block PER stat, laid out by a Grid — not a single Counter
	 * holding three items. Counter styles its items with itemBackground /
	 * itemPadding / itemBorderRadius only; its `border` and `boxShadow` paint
	 * the block WRAPPER, so three items in one Counter can never be three
	 * bordered, shadowed cards — they would share one. Splitting them makes each
	 * stat its own wrapper, and the count-up animation is per block, so it still
	 * runs on all three.
	 */
	$statCards = [
		[
			[ 'value' => '12', 'prefix' => '', 'suffix' => 'k+' ],
			__( 'Sites launched', 'flexa-block' ),
			$icon( 'rocket', '<path d="M12 3c3 2 5 5 5 9l-2 3H9l-2-3c0-4 2-7 5-9z"/><path d="M9 15l-3 3M15 15l3 3"/><circle cx="12" cy="9" r="1.5"/>' ),
		],
		[
			[ 'value' => '99.9', 'prefix' => '', 'suffix' => '%' ],
			__( 'Uptime', 'flexa-block' ),
			$icon( 'shield', '<path d="M12 3l7 3v5c0 4-3 7-7 9-4-2-7-5-7-9V6l7-3z"/>' ),
		],
		[
			[ 'value' => '40', 'prefix' => '', 'suffix' => '+' ],
			__( 'Blocks included', 'flexa-block' ),
			$icon( 'layers', '<path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/>' ),
		],
	];

	$stats = '';
	foreach ( $statCards as $stat ) {
		$stats .= $mk(
			'flexa/counter',
			array_merge(
				[
					'items'             => [
						[
							'number' => $stat[0],
							'label'  => $stat[1],
							'icon'   => $stat[2],
						],
					],
					// Every device is pinned to one column: the block default is 3/2/1,
					// and a single item in a 2-column track would sit at half width.
					'columns'           => [
						'desktop' => [ 'value' => '1', 'unit' => '' ],
						'tablet'  => [ 'value' => '1', 'unit' => '' ],
						'mobile'  => [ 'value' => '1', 'unit' => '' ],
					],
					'contentGap'        => [ 'desktop' => [ 'value' => '10', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
					'iconSize'          => [ 'desktop' => [ 'value' => '32', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
					'iconColor'         => $accent,
					'numberTypography'  => [
						'desktop' => [
							'fontSize'      => [ 'value' => '34', 'unit' => 'px' ],
							'fontWeight'    => '700',
							'letterSpacing' => [ 'value' => '-0.5', 'unit' => 'px' ],
							'lineHeight'    => '1.1',
						],
						'tablet'  => [],
						'mobile'  => [ 'fontSize' => [ 'value' => '28', 'unit' => 'px' ] ],
					],
					'numberColor'       => $ink,
					'labelTypography'   => [
						'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ], 'lineHeight' => '1.5' ],
						'tablet'  => [],
						'mobile'  => [],
					],
					'labelColor'        => $muted,
				],
				// The card is the block wrapper, and there is one wrapper per stat.
				$cardStyle()
			)
		);
	}

	$statsGrid = $mk(
		'flexa/grid',
		[
			'containerType' => 'full-width',
			'layout'        => [
				'desktop' => [ 'columns' => [ 'value' => '3', 'unit' => 'fr' ], 'rows' => [ 'value' => '', 'unit' => 'fr' ], 'gap' => [ 'column' => '20', 'row' => '20', 'unit' => 'px' ], 'autoFlow' => 'row', 'justifyItems' => 'stretch', 'alignItems' => 'stretch', 'justifyContent' => '', 'alignContent' => '' ],
				'tablet'  => [ 'columns' => [ 'value' => '3', 'unit' => 'fr' ] ],
				'mobile'  => [ 'columns' => [ 'value' => '1', 'unit' => 'fr' ] ],
			],
			'spacing'       => [ 'desktop' => [ 'padding' => [ 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'unit' => 'px' ], 'margin' => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ] ], 'tablet' => [], 'mobile' => [] ],
		],
		$stats
	);

	$statsSection = $mk( 'flexa/container', [ 'containerType' => 'boxed', 'spacing' => $pad( '48' ) ], $statsGrid );

	/* ---------------------------------------------------------------------------
	 * Features.
	 * ------------------------------------------------------------------------- */
	$features = [
		[ 'zap', '<path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/>', __( 'Lightning fast', 'flexa-block' ), __( 'Blocks output clean, render-ready markup so your pages load in milliseconds.', 'flexa-block' ) ],
		[ 'layers', '<path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/>', __( 'Composable blocks', 'flexa-block' ), __( 'Mix and match blocks to build any layout, from a hero to a full pricing page.', 'flexa-block' ) ],
		[ 'globe', '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>', __( 'Responsive by design', 'flexa-block' ), __( 'Every block adapts to desktop, tablet and mobile with per-device controls.', 'flexa-block' ) ],
		[ 'shield', '<path d="M12 3l7 3v5c0 4-3 7-7 9-4-2-7-5-7-9V6l7-3z"/>', __( 'Private by default', 'flexa-block' ), __( 'No tracking and no third-party requests. Your visitors data stays on your site.', 'flexa-block' ) ],
		[ 'clock', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>', __( 'Ship faster', 'flexa-block' ), __( 'Sensible defaults and reusable patterns cut build time down to an afternoon.', 'flexa-block' ) ],
		[ 'check', '<path d="M5 12l4 4L19 6"/>', __( 'Accessible', 'flexa-block' ), __( 'Keyboard navigation, focus states and ARIA are baked into every block.', 'flexa-block' ) ],
	];
	$featureBoxes = '';
	foreach ( $features as $f ) {
		$featureBoxes .= $mk(
			'flexa/info-box',
			array_merge(
				[
					'iconPosition'          => 'top',
					'alignment'             => [ 'desktop' => 'center', 'tablet' => '', 'mobile' => '' ],
					'showMedia'             => true,
					'mediaType'             => 'icon',
					'icon'                  => $icon( $f[0], $f[1] ),
					'title'                 => $f[2],
					'titleTag'              => 'h3',
					'description'           => $f[3],
					'contentGap'            => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],

					// The icon sits in a tinted chip: `__media` is inline-flex with
					// flex:0 0 auto, so a background plus padding wraps the glyph
					// tightly instead of banding the whole card width.
					'iconSize'              => [ 'desktop' => [ 'value' => '24', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
					'iconColor'             => $accent,
					'mediaBackground'       => $chipBg,
					'mediaPadding'          => [
						'desktop' => [ 'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'unit' => 'px' ],
						'tablet'  => [],
						'mobile'  => [],
					],
					'mediaRadius'           => [ 'desktop' => [ 'value' => '12', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
					'mediaGap'              => [ 'desktop' => [ 'value' => '16', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],

					'titleTypography'       => [
						'desktop' => [ 'fontSize' => [ 'value' => '17', 'unit' => 'px' ], 'fontWeight' => '600', 'lineHeight' => '1.35' ],
						'tablet'  => [],
						'mobile'  => [],
					],
					'titleColor'            => $ink,
					'descriptionTypography' => [
						'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ], 'lineHeight' => '1.6' ],
						'tablet'  => [],
						'mobile'  => [],
					],
					'descriptionColor'      => $muted,
				],
				$cardStyle()
			)
		);
	}
	$featuresGrid = $mk(
		'flexa/grid',
		[
			'containerType' => 'full-width',
			'layout'        => [
				'desktop' => [ 'columns' => [ 'value' => '3', 'unit' => 'fr' ], 'rows' => [ 'value' => '', 'unit' => 'fr' ], 'gap' => [ 'column' => '24', 'row' => '24', 'unit' => 'px' ], 'autoFlow' => 'row', 'justifyItems' => 'stretch', 'alignItems' => 'stretch', 'justifyContent' => '', 'alignContent' => '' ],
				'tablet'  => [ 'columns' => [ 'value' => '2', 'unit' => 'fr' ] ],
				'mobile'  => [ 'columns' => [ 'value' => '1', 'unit' => 'fr' ] ],
			],
			'spacing'       => [ 'desktop' => [ 'padding' => [ 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'unit' => 'px' ], 'margin' => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ] ], 'tablet' => [], 'mobile' => [] ],
		],
		$featureBoxes
	);
	$featuresSection = $mk(
		'flexa/container',
		[
			'containerType' => 'boxed',
			'background'    => $bg( '#f8fafc', '#111827' ),
			'border'        => $radius( '20' ),
			'spacing'       => $pad( '56' ),
		],
		$heading( __( 'Everything you need to ship faster', 'flexa-block' ) ) . "\n" .
		$lead( __( 'A focused set of blocks that work together, so you can go from blank page to launch without a page builder.', 'flexa-block' ) ) . "\n" .
		$featuresGrid
	);

	/* ---------------------------------------------------------------------------
	 * How it works (the block's default items already describe a 3-step flow).
	 *
	 * The list is narrowed and centred rather than left to span the full 1200px
	 * container, where three short steps read as stranded against a centred
	 * heading. `maxWidth` alone only caps the width — the generator emits no
	 * auto margins — so the centring comes from spacing.margin left/right.
	 * ------------------------------------------------------------------------- */
	$steps = $mk(
		'flexa/steps',
		array_merge(
			$cardStyle(),
			[
				'maxWidth'              => [ 'desktop' => [ 'value' => '680', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
				// Overrides the card recipe's padding: a single wide card holding a
				// list wants more room than a compact stat tile, and it carries the
				// auto margins that centre it.
				'spacing'               => [
					'desktop' => [
						'padding' => [ 'top' => '32', 'right' => '32', 'bottom' => '32', 'left' => '32', 'unit' => 'px' ],
						'margin'  => [ 'top' => '', 'right' => 'auto', 'bottom' => '', 'left' => 'auto', 'unit' => 'px' ],
					],
					'tablet'  => [],
					'mobile'  => [
						'padding' => [ 'top' => '24', 'right' => '20', 'bottom' => '24', 'left' => '20', 'unit' => 'px' ],
					],
				],
				/*
				 * The marker reuses the feature cards' chip language: a tinted disc
				 * with an accent numeral. Its ring is `border: 2px solid
				 * currentColor` in the block's own CSS and has no attribute of its
				 * own, so it follows markerTextColor — which is exactly why the
				 * numeral is the accent rather than white on a solid fill.
				 */
				'markerColor'           => $chipBg,
				'markerTextColor'       => $accent,
				'connectorColor'        => $line,
				'titleTypography'       => [
					'desktop' => [ 'fontSize' => [ 'value' => '17', 'unit' => 'px' ], 'fontWeight' => '600', 'lineHeight' => '1.35' ],
					'tablet'  => [],
					'mobile'  => [],
				],
				'titleColor'            => $ink,
				'descriptionTypography' => [
					'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ], 'lineHeight' => '1.6' ],
					'tablet'  => [],
					'mobile'  => [],
				],
				'descriptionColor'      => $muted,
			]
		)
	);

	$stepsSection = $mk(
		'flexa/container',
		[ 'containerType' => 'boxed', 'spacing' => $pad( '56' ) ],
		$heading( __( 'How it works', 'flexa-block' ) ) . "\n" . $steps
	);

	/* ---------------------------------------------------------------------------
	 * Pricing (the block's default items ship a three-tier table).
	 *
	 * Plan chrome is NOT the responsive {desktop,tablet,mobile} shape the rest of
	 * the page uses: `planBorder` / `planBoxShadow` / `highlightBorder` /
	 * `highlightBoxShadow` are flat objects, so they are written un-nested here
	 * while still drawing their values from the same palette. `highlightBorder`
	 * is merged field-by-field over `planBorder`, so it only needs the fields it
	 * changes; `highlightColor` then overrides the highlighted card's border
	 * colour AND becomes the badge fill unless `badgeBackground` says otherwise.
	 * ------------------------------------------------------------------------- */
	$pricing = $mk(
		'flexa/pricing-table',
		[
			'containerType'     => 'full-width',
			'gap'               => [ 'desktop' => [ 'value' => '20', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],

			// Every plan card wears the page's card chrome.
			'planBorder'        => [
				'style'  => 'solid',
				'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
				'color'  => $line,
				'radius' => [ 'topLeft' => '16', 'topRight' => '16', 'bottomRight' => '16', 'bottomLeft' => '16', 'unit' => 'px' ],
			],
			'planBoxShadow'     => $cardShadow,

			// The popular plan: a 2px accent outline and a deeper, accent-tinted
			// lift, so it reads as raised above its neighbours.
			'highlightBorder'   => [
				'style'  => 'solid',
				'width'  => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2', 'unit' => 'px' ],
				'color'  => [ 'light' => '', 'dark' => '' ],
				'radius' => [ 'topLeft' => '', 'topRight' => '', 'bottomRight' => '', 'bottomLeft' => '', 'unit' => 'px' ],
			],
			'highlightColor'    => $accent,
			'highlightBoxShadow' => [
				'horizontal' => '0',
				'vertical'   => '18',
				'blur'       => '44',
				'spread'     => '-12',
				'color'      => [ 'light' => 'rgba(37,99,235,0.30)', 'dark' => 'rgba(37,99,235,0.45)' ],
				'inset'      => false,
				'enabled'    => true,
			],
			'badgeColor'        => [ 'light' => '#ffffff', 'dark' => '#ffffff' ],
			'badgeTypography'   => [
				'desktop' => [
					'fontSize'      => [ 'value' => '11', 'unit' => 'px' ],
					'fontWeight'    => '700',
					'letterSpacing' => [ 'value' => '0.06', 'unit' => 'em' ],
					'textTransform' => 'uppercase',
					'lineHeight'    => '1',
				],
				'tablet'  => [],
				'mobile'  => [],
			],

			// Plan name as a small muted label so the price carries the weight.
			'nameTypography'    => [
				'desktop' => [
					'fontSize'      => [ 'value' => '13', 'unit' => 'px' ],
					'fontWeight'    => '600',
					'letterSpacing' => [ 'value' => '0.06', 'unit' => 'em' ],
					'textTransform' => 'uppercase',
					'lineHeight'    => '1.2',
				],
				'tablet'  => [],
				'mobile'  => [],
			],
			'nameColor'         => $muted,
			'priceTypography'   => [
				'desktop' => [
					'fontSize'      => [ 'value' => '40', 'unit' => 'px' ],
					'fontWeight'    => '700',
					'letterSpacing' => [ 'value' => '-1', 'unit' => 'px' ],
					'lineHeight'    => '1.1',
				],
				'tablet'  => [],
				'mobile'  => [ 'fontSize' => [ 'value' => '34', 'unit' => 'px' ] ],
			],
			'priceColor'        => $ink,
			'periodColor'       => $muted,
			// The tick / cross glyph is drawn in currentColor and has no colour
			// attribute of its own, so it follows the feature text: a readable
			// grey here rather than the accent.
			'featureTypography' => [
				'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ], 'lineHeight' => '1.6' ],
				'tablet'  => [],
				'mobile'  => [],
			],
			'featureColor'      => [ 'light' => '#374151', 'dark' => '#d1d5db' ],

			// Full-width CTAs line the card footers up with each other; the radius
			// is left alone so the buttons keep the theme's shape, exactly like the
			// hero's.
			'buttonWidth'       => 'full',
			'buttonTextColor'      => [ 'light' => '#ffffff', 'dark' => '#ffffff' ],
			'buttonTextColorHover' => [ 'light' => '#ffffff', 'dark' => '#ffffff' ],
			'buttonBackground'     => $accent,
			'buttonBackgroundHover' => [ 'light' => '#1d4ed8', 'dark' => '#3b82f6' ],
		]
	);

	$pricingSection = $mk(
		'flexa/container',
		[
			'containerType'  => 'boxed',
			'spacing'        => $pad( '56' ),
		],
		$heading( __( 'Simple, transparent pricing', 'flexa-block' ) ) . "\n" .
		$lead( __( 'Start free, upgrade when you grow. No hidden fees.', 'flexa-block' ) ) . "\n" .
		$pricing
	);

	/* ---------------------------------------------------------------------------
	 * Testimonials.
	 * ------------------------------------------------------------------------- */
	$quotes = [
		[ 5, __( 'A joy to build with', 'flexa-block' ), __( 'We rebuilt our marketing site in a weekend. The blocks are flexible and the output is clean.', 'flexa-block' ), __( 'Maya Chen', 'flexa-block' ), __( 'Head of Growth, Northwind', 'flexa-block' ) ],
		[ 5, __( 'Exactly what we needed', 'flexa-block' ), __( 'No page-builder bloat and no tracking. Fast pages our whole team can edit with confidence.', 'flexa-block' ), __( 'David Osei', 'flexa-block' ), __( 'Founder, Lumen Studio', 'flexa-block' ) ],
	];
	$quoteCards = '';
	foreach ( $quotes as $q ) {
		$quoteCards .= $mk(
			'flexa/testimonial',
			array_merge(
				[
					'rating'                => $q[0],
					'showRating'            => true,
					'title'                 => $q[1],
					'quote'                 => $q[2],
					'showAvatar'            => false,
					/*
					 * `inline`, not `stacked`. Stacked exists for a centred card:
					 * style.scss gives `--author-stacked .__author` text-align:center,
					 * and in a left-aligned card that centres the name over the longer
					 * job line, so the name reads as randomly indented. With the avatar
					 * off, inline leaves just the name/job column, flush left.
					 */
					'authorLayout'          => 'inline',
					'authorName'            => $q[3],
					'authorJob'             => $q[4],

					'ratingSize'            => [ 'desktop' => [ 'value' => '18', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
					'ratingSpacing'         => [ 'desktop' => [ 'value' => '14', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
					'ratingColor'           => $star,

					'titleTypography'       => [
						'desktop' => [ 'fontSize' => [ 'value' => '17', 'unit' => 'px' ], 'fontWeight' => '600', 'lineHeight' => '1.35' ],
						'tablet'  => [],
						'mobile'  => [],
					],
					'titleColor'            => $ink,
					'titleSpacing'          => [ 'desktop' => [ 'value' => '10', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],

					// The quote is the card's body copy, so it stays darker than the
					// job line beneath it rather than sharing the muted grey.
					'quoteTypography'       => [
						'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ], 'lineHeight' => '1.7' ],
						'tablet'  => [],
						'mobile'  => [],
					],
					'quoteColor'            => [ 'light' => '#374151', 'dark' => '#d1d5db' ],
					'quoteSpacing'          => [ 'desktop' => [ 'value' => '20', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],

					'nameTypography'        => [
						'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ], 'fontWeight' => '600', 'lineHeight' => '1.4' ],
						'tablet'  => [],
						'mobile'  => [],
					],
					'nameColor'             => $ink,
					'jobTypography'         => [
						'desktop' => [ 'fontSize' => [ 'value' => '13', 'unit' => 'px' ], 'lineHeight' => '1.5' ],
						'tablet'  => [],
						'mobile'  => [],
					],
					'jobColor'              => $muted,
				],
				$cardStyle()
			)
		);
	}
	$quotesGrid = $mk(
		'flexa/grid',
		[
			'containerType' => 'full-width',
			'layout'        => [
				'desktop' => [ 'columns' => [ 'value' => '2', 'unit' => 'fr' ], 'rows' => [ 'value' => '', 'unit' => 'fr' ], 'gap' => [ 'column' => '24', 'row' => '24', 'unit' => 'px' ], 'autoFlow' => 'row', 'justifyItems' => 'stretch', 'alignItems' => 'stretch', 'justifyContent' => '', 'alignContent' => '' ],
				'tablet'  => [],
				'mobile'  => [ 'columns' => [ 'value' => '1', 'unit' => 'fr' ] ],
			],
			'spacing'       => [ 'desktop' => [ 'padding' => [ 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'unit' => 'px' ], 'margin' => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ] ], 'tablet' => [], 'mobile' => [] ],
		],
		$quoteCards
	);
	$testimonialsSection = $mk(
		'flexa/container',
		[
			'containerType' => 'boxed',
			'background'    => $bg( '#f8fafc', '#111827' ),
			'border'        => $radius( '20' ),
			'spacing'       => $pad( '56' ),
		],
		$heading( __( 'Loved by teams that ship', 'flexa-block' ) ) . "\n" . $quotesGrid
	);

	/* ---------------------------------------------------------------------------
	 * FAQ.
	 * ------------------------------------------------------------------------- */
	$faqSection = $mk(
		'flexa/container',
		[ 'containerType' => 'boxed', 'spacing' => $pad( '56' ) ],
		$heading( __( 'Frequently asked questions', 'flexa-block' ) ) . "\n" .
		$mk(
			'flexa/faq',
			[
				'expandFirst'       => true,
				'enableSchema'      => true,
				/*
				 * REQUIRED, not cosmetic. FAQ is one of the seven collection blocks
				 * in the item-style split, and its generator gates on this flag: with
				 * it false it paints each item from the WRAPPER `border`/`boxShadow`
				 * (the legacy shape) and ignores `itemBorder`/`itemBoxShadow`
				 * entirely, so the card chrome below would silently do nothing.
				 * TODO(remove in vNEXT): drops out with the item-style migration.
				 */
				'itemStyleMigrated' => true,

				// Narrowed and centred: at the full 1200px the toggle icon ends up
				// marooned a screen away from its own question. `maxWidth` only caps
				// the width, so the centring comes from spacing.margin.
				'maxWidth'          => [ 'desktop' => [ 'value' => '780', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
				'spacing'           => [
					'desktop' => [
						'padding' => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ],
						'margin'  => [ 'top' => '', 'right' => 'auto', 'bottom' => '', 'left' => 'auto', 'unit' => 'px' ],
					],
					'tablet'  => [],
					'mobile'  => [],
				],
				'rowGap'            => [ 'desktop' => [ 'value' => '10', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],

				// Per-item card. The shadow is much softer than $cardShadow on
				// purpose: four stacked rows each wearing the full lift reads as
				// muddy rather than layered.
				'itemBackground'    => [ 'light' => '#ffffff', 'dark' => '#111827' ],
				'itemBorder'        => [
					'desktop' => [
						'style'  => 'solid',
						'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
						'color'  => $line,
						'radius' => [ 'topLeft' => '12', 'topRight' => '12', 'bottomRight' => '12', 'bottomLeft' => '12', 'unit' => 'px' ],
					],
					'tablet'  => [],
					'mobile'  => [],
				],
				'itemBoxShadow'     => [
					'horizontal' => '0',
					'vertical'   => '2',
					'blur'       => '8',
					'spread'     => '-2',
					'color'      => [ 'light' => 'rgba(17,24,39,0.06)', 'dark' => 'rgba(0,0,0,0.35)' ],
					'inset'      => false,
					'enabled'    => true,
				],
				'questionPadding'   => [
					'desktop' => [ 'top' => '18', 'right' => '20', 'bottom' => '18', 'left' => '20', 'unit' => 'px' ],
					'tablet'  => [],
					'mobile'  => [ 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px' ],
				],
				// No top padding: the question's own bottom padding already
				// separates the two.
				'answerPadding'     => [
					'desktop' => [ 'top' => '0', 'right' => '20', 'bottom' => '18', 'left' => '20', 'unit' => 'px' ],
					'tablet'  => [],
					'mobile'  => [ 'top' => '0', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px' ],
				],
				'questionTypography' => [
					'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ], 'fontWeight' => '600', 'lineHeight' => '1.45' ],
					'tablet'  => [],
					'mobile'  => [],
				],
				'questionColor'      => $ink,
				// The open row turns accent, so the toggle state is legible without
				// relying on the icon alone.
				'questionActiveColor' => $accent,
				'answerTypography'   => [
					'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ], 'lineHeight' => '1.65' ],
					'tablet'  => [],
					'mobile'  => [],
				],
				'answerColor'        => $muted,
				'iconSize'           => [ 'desktop' => [ 'value' => '18', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
				'iconColor'          => $muted,
				'iconActiveColor'    => $accent,
				'items'             => [
					[ 'question' => __( 'Do I need to know how to code?', 'flexa-block' ), 'answer' => __( 'No. Every block is configured visually in the WordPress editor. If you can edit a page, you can build with Flexa.', 'flexa-block' ) ],
					[ 'question' => __( 'Will it slow down my site?', 'flexa-block' ), 'answer' => __( 'No. Blocks only load the CSS and JavaScript they actually use, so pages stay fast.', 'flexa-block' ) ],
					[ 'question' => __( 'Does it work with my theme?', 'flexa-block' ), 'answer' => __( 'Flexa blocks inherit your theme colours and fonts and work with any block-ready theme.', 'flexa-block' ) ],
					[ 'question' => __( 'Is my visitors data tracked?', 'flexa-block' ), 'answer' => __( 'Never. Flexa collects no personal data and makes no third-party requests.', 'flexa-block' ) ],
				],
			]
		)
	);

	/* ---------------------------------------------------------------------------
	 * Closing CTA.
	 *
	 * A dark rounded panel that answers the hero, so the page opens and closes on
	 * the same surface instead of trailing off into white. The block is `boxed`,
	 * so the background and radius land on its inner element at widthBoxed — a
	 * panel on the site grid rather than a full-bleed band.
	 * ------------------------------------------------------------------------- */
	$cta = $mk(
		'flexa/cta',
		[
			'heading'       => __( 'Ready to build something great?', 'flexa-block' ),
			'description'   => __( 'Install Flexa Block and ship your first page today.', 'flexa-block' ),
			'primaryText'   => __( 'Get started free', 'flexa-block' ),
			'primaryUrl'    => '#',
			'primaryIcon'   => array_merge(
				$icon( 'arrow-right', '<path d="M5 12h14M13 6l6 6-6 6"/>' ),
				[ 'position' => 'after' ]
			),
			'showSecondary' => true,
			'secondaryText' => __( 'Browse all blocks', 'flexa-block' ),
			'secondaryUrl'  => '#',

			// Tighter glow geometry than the hero's: the panel is a third of the
			// height, so the hero's radii would crop to a flat wash.
			'background'            => $bgGradient(
				'radial-gradient(620px 320px at 16% 18%, rgba(59,130,246,0.34) 0%, rgba(59,130,246,0) 60%), '
				. 'radial-gradient(520px 280px at 84% 22%, rgba(139,92,246,0.28) 0%, rgba(139,92,246,0) 62%), '
				. 'linear-gradient(165deg, #0b1120 0%, #111827 55%, #17143a 100%)',
				''
			),
			'border'                => $radius( '20' ),
			// Padding only. The panel's width and centring come from its own
			// `boxed` max-width plus the `.flexa-cta--boxed` auto margins in the
			// block's stylesheet; see the wrapping $ctaSection for why that needs a
			// block-layout parent.
			
			'spacing'               => [
				'desktop' => [
					'padding' => [ 'top' => '64', 'right' => '40', 'bottom' => '64', 'left' => '40', 'unit' => 'px' ],
					'margin'  => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ],
				],
				'tablet'  => [],
				'mobile'  => [ 'padding' => [ 'top' => '48', 'right' => '24', 'bottom' => '48', 'left' => '24', 'unit' => 'px' ] ],
			],
			'contentMaxWidth'       => [ 'desktop' => [ 'value' => '620', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
			'contentGap'            => [ 'desktop' => [ 'value' => '28', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [ 'value' => '22', 'unit' => 'px' ] ],
			'contentAlign'          => [ 'desktop' => 'center', 'tablet' => '', 'mobile' => '' ],
			'headingTypography'     => [
				'desktop' => [
					'fontSize'      => [ 'value' => '34', 'unit' => 'px' ],
					'fontWeight'    => '700',
					'letterSpacing' => [ 'value' => '-0.6', 'unit' => 'px' ],
					'lineHeight'    => '1.2',
				],
				'tablet'  => [ 'fontSize' => [ 'value' => '28', 'unit' => 'px' ] ],
				'mobile'  => [ 'fontSize' => [ 'value' => '25', 'unit' => 'px' ] ],
			],
			'headingColor'          => $onDark['heading'],
			'descriptionTypography' => [
				'desktop' => [ 'fontSize' => [ 'value' => '16', 'unit' => 'px' ], 'lineHeight' => '1.65' ],
				'tablet'  => [],
				'mobile'  => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ] ],
			],
			'descriptionColor'      => $onDark['body'],
			'primaryTextColor'      => $onDark['btnText'],
			'primaryBgColor'        => $onDark['btnFill'],
			'primaryHover'          => [
				'text'       => $onDark['btnText'],
				'background' => $onDark['btnFillHover'],
			],
			'secondaryTextColor'    => $onDark['btnText'],
			'secondaryBgColor'      => $onDark['ghostFill'],
			'secondaryHover'        => [
				'text'       => $onDark['btnText'],
				'background' => $onDark['ghostHover'],
			],
		]
	);

	/*
	 * The CTA is wrapped like every other section, and for a second reason: the
	 * page's last block needs a gap before whatever the theme renders next (a
	 * footer, usually in its own colour) and a bottom MARGIN cannot provide it
	 * reliably. WordPress's global stylesheet emits
	 * `:root :where(.is-layout-constrained) > * { margin-block-end: 0 }`, which
	 * matches every direct child of the post content — at the same specificity
	 * as a `.flexa-cta-<id>` rule, so the winner comes down to print order, and
	 * global styles are enqueued on `wp_footer` as well as `wp_enqueue_scripts`.
	 * Padding is subject to neither that rule nor margin collapsing.
	 *
	 * Horizontal padding is zero so the dark panel keeps the same 1200px width as
	 * the section panels above it.
	 */
	$ctaSection = $mk(
		'flexa/container',
		[
			'containerType' => 'boxed',
			/*
			 * `block`, not the container's default flex column. The CTA block's own
			 * style.scss gives `.flexa-cta--boxed { margin-inline: auto }`, which no
			 * attribute can switch off — and per the flexbox spec a cross-axis
			 * `auto` margin cancels `align-items: stretch`, leaving the panel at
			 * shrink-to-fit. As a plain block child it instead fills the inner up to
			 * its own 1200px max-width, matching the section panels above, which is
			 * exactly how it behaved before it was wrapped.
			 */
			'layout'        => [
				'desktop' => [
					'display'        => 'block',
					'direction'      => '',
					'justifyContent' => '',
					'alignItems'     => '',
					'wrap'           => '',
					'gap'            => [ 'column' => '', 'row' => '', 'unit' => 'px' ],
				],
				'tablet'  => [],
				'mobile'  => [],
			],
			'spacing'       => [
				'desktop' => [
					'padding' => [ 'top' => '0', 'right' => '0', 'bottom' => '64', 'left' => '0', 'unit' => 'px' ],
					'margin'  => [ 'top' => '', 'right' => 'auto', 'bottom' => '', 'left' => 'auto', 'unit' => 'px' ],
				],
				'tablet'  => [],
				// Side padding is zero on desktop so the panel matches the 1200px
				// section panels; on a phone that same zero would run the panel into
				// both screen edges, so it comes back here.
				'mobile'  => [ 'padding' => [ 'top' => '0', 'right' => '16', 'bottom' => '48', 'left' => '16', 'unit' => 'px' ] ],
			],
		],
		$cta
	);

	$content = implode(
		"\n\n",
		[
			$hero,
			$statsSection,
			$featuresSection,
			$stepsSection,
			$pricingSection,
			$testimonialsSection,
			$faqSection,
			$ctaSection,
		]
	);

	return [
		'id'          => 'landing-saas',
		'title'       => __( 'Landing Page — SaaS / Product', 'flexa-block' ),
		'description' => __( 'A complete one-page layout: hero, stats, feature grid, how-it-works, pricing, testimonials, FAQ and a closing call to action. Media-free and ready to edit.', 'flexa-block' ),
		'category'    => __( 'Page', 'flexa-block' ),
		'blocks'      => [ 'flexa/banner', 'flexa/counter', 'flexa/container', 'flexa/grid', 'flexa/heading', 'flexa/text', 'flexa/info-box', 'flexa/steps', 'flexa/pricing-table', 'flexa/testimonial', 'flexa/faq', 'flexa/cta' ],
		'version'     => '1.0.0',
		'content'     => $content,
	];
} )();
