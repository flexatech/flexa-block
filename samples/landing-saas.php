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

	/** Vertical section padding (boxed, centred). */
	$pad = static function ( string $y ): array {
		return [
			'desktop' => [
				'padding' => [ 'top' => $y, 'right' => '32', 'bottom' => $y, 'left' => '32', 'unit' => 'px' ],
				'margin'  => [ 'top' => '', 'right' => 'auto', 'bottom' => '', 'left' => 'auto', 'unit' => 'px' ],
			],
			'tablet'  => [],
			'mobile'  => [],
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
	 * ------------------------------------------------------------------------- */
	$hero = $mk(
		'flexa/banner',
		[
			'heading'       => __( 'Build beautiful WordPress pages, faster', 'flexa-block' ),
			'headingTag'    => 'h1',
			'description'   => __( 'A powerful block library for the WordPress editor. Design landing pages and marketing sites without writing code.', 'flexa-block' ),
			'primaryText'   => __( 'Get started', 'flexa-block' ),
			'primaryUrl'    => '#',
			'showSecondary' => true,
			'secondaryText' => __( 'See features', 'flexa-block' ),
			'secondaryUrl'  => '#',
		]
	);

	/* ---------------------------------------------------------------------------
	 * Stats.
	 * ------------------------------------------------------------------------- */
	$stats = $mk(
		'flexa/counter',
		[
			'items' => [
				[
					'number' => [ 'value' => '12', 'prefix' => '', 'suffix' => 'k+' ],
					'label'  => __( 'Sites launched', 'flexa-block' ),
					'icon'   => $icon( 'rocket', '<path d="M12 3c3 2 5 5 5 9l-2 3H9l-2-3c0-4 2-7 5-9z"/><path d="M9 15l-3 3M15 15l3 3"/><circle cx="12" cy="9" r="1.5"/>' ),
				],
				[
					'number' => [ 'value' => '99.9', 'prefix' => '', 'suffix' => '%' ],
					'label'  => __( 'Uptime', 'flexa-block' ),
					'icon'   => $icon( 'shield', '<path d="M12 3l7 3v5c0 4-3 7-7 9-4-2-7-5-7-9V6l7-3z"/>' ),
				],
				[
					'number' => [ 'value' => '40', 'prefix' => '', 'suffix' => '+' ],
					'label'  => __( 'Blocks included', 'flexa-block' ),
					'icon'   => $icon( 'layers', '<path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/>' ),
				],
				[
					'number' => [ 'value' => '4.9', 'prefix' => '', 'suffix' => '/5' ],
					'label'  => __( 'Average rating', 'flexa-block' ),
					'icon'   => $icon( 'star', '<path d="M12 3l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 18.8 6.1 21.9l1.1-6.5L2.5 9.8l6.5-.9L12 3z"/>' ),
				],
			],
		]
	);
	$statsSection = $mk( 'flexa/container', [ 'containerType' => 'boxed', 'spacing' => $pad( '48' ) ], $stats );

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
			[
				'iconPosition' => 'top',
				'alignment'    => [ 'desktop' => 'center', 'tablet' => '', 'mobile' => '' ],
				'showMedia'    => true,
				'mediaType'    => 'icon',
				'icon'         => $icon( $f[0], $f[1] ),
				'title'        => $f[2],
				'titleTag'     => 'h3',
				'description'  => $f[3],
			]
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
	 * How it works (block defaults already describe a 3-step onboarding flow).
	 * ------------------------------------------------------------------------- */
	$stepsSection = $mk(
		'flexa/container',
		[ 'containerType' => 'boxed', 'spacing' => $pad( '56' ) ],
		$heading( __( 'How it works', 'flexa-block' ) ) . "\n" . $mk( 'flexa/steps' )
	);

	/* ---------------------------------------------------------------------------
	 * Pricing (block defaults ship a three-tier table).
	 * ------------------------------------------------------------------------- */
	$pricingSection = $mk(
		'flexa/container',
		[
			'containerType'  => 'boxed',
			'spacing'        => $pad( '56' ),
		],
		$heading( __( 'Simple, transparent pricing', 'flexa-block' ) ) . "\n" .
		$lead( __( 'Start free, upgrade when you grow. No hidden fees.', 'flexa-block' ) ) . "\n" .
		$mk( 'flexa/pricing-table', [ 'containerType' => 'full-width' ] )
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
			[
				'rating'       => $q[0],
				'showRating'   => true,
				'title'        => $q[1],
				'quote'        => $q[2],
				'showAvatar'   => false,
				'authorLayout' => 'stacked',
				'authorName'   => $q[3],
				'authorJob'    => $q[4],
			]
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
				'expandFirst'  => true,
				'enableSchema' => true,
				'items'        => [
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
	 * ------------------------------------------------------------------------- */
	$cta = $mk(
		'flexa/cta',
		[
			'heading'       => __( 'Ready to build something great?', 'flexa-block' ),
			'description'   => __( 'Install Flexa Block and ship your first page today.', 'flexa-block' ),
			'primaryText'   => __( 'Get started free', 'flexa-block' ),
			'primaryUrl'    => '#',
			'showSecondary' => true,
			'secondaryText' => __( 'Browse all blocks', 'flexa-block' ),
			'secondaryUrl'  => '#',
		]
	);

	$content = implode(
		"\n\n",
		[
			$hero,
			$statsSection,
			$featuresSection,
			$stepsSection,
			$mk( 'flexa/separator' ),
			$pricingSection,
			$testimonialsSection,
			$faqSection,
			$cta,
		]
	);

	return [
		'id'          => 'landing-saas',
		'title'       => __( 'Landing Page — SaaS / Product', 'flexa-block' ),
		'description' => __( 'A complete one-page layout: hero, stats, feature grid, how-it-works, pricing, testimonials, FAQ and a closing call to action. Media-free and ready to edit.', 'flexa-block' ),
		'category'    => __( 'Page', 'flexa-block' ),
		'blocks'      => [ 'flexa/banner', 'flexa/counter', 'flexa/container', 'flexa/grid', 'flexa/heading', 'flexa/text', 'flexa/info-box', 'flexa/steps', 'flexa/separator', 'flexa/pricing-table', 'flexa/testimonial', 'flexa/faq', 'flexa/cta' ],
		'version'     => '1.0.0',
		'content'     => $content,
	];
} )();
