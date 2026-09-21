<?php
declare(strict_types=1);
/**
 * Sample: Contact page.
 *
 * A two-column contact layout: a working subscribe/contact form (name, email,
 * phone, message) beside contact details and an embedded map. Media-free; the
 * icons are inline SVG stored in attributes. The form recipient is left blank so
 * the site owner sets their own address after import.
 *
 * Markup is assembled from PHP arrays through wp_json_encode; every `blockId` is
 * a placeholder regenerated to a unique id on import.
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
	 * @param string               $name  Block name.
	 * @param array<string, mixed> $attrs Attributes.
	 * @param string               $inner Inner block markup.
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

	/** A builtin icon object (inline SVG, no media). */
	$icon = static function ( string $name, string $paths ): array {
		return [
			'source' => 'builtin',
			'name'   => $name,
			'markup' => '<svg class="flexa-icon" viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>',
			'url'    => '',
			'id'     => null,
		];
	};

	/*
	 * Section palette, named once. Every entry is a {light,dark} pair because that
	 * is the shape every colour attribute in the plugin takes; the dark half is
	 * what makes the sample survive a site with dark mode switched on.
	 *
	 * These are values from the editor's own DEFAULT_PALETTE, so each one shows up
	 * as an already-selected swatch in the block sidebar — a sample is there to be
	 * opened and fiddled with, and an off-palette hex would leave the picker
	 * looking blank.
	 *
	 * Deliberately a copy of the landing-page sample's set rather than a shared
	 * include: each sample owns its own look so it can be retuned on its own.
	 */
	$ink    = [ 'light' => '#111827', 'dark' => '#f9fafb' ];
	$muted  = [ 'light' => '#6b7280', 'dark' => '#9ca3af' ];
	$accent = [ 'light' => '#2563eb', 'dark' => '#60a5fa' ];
	$line   = [ 'light' => '#e5e7eb', 'dark' => '#374151' ];

	/** Tinted chip behind an accent glyph. */
	$chipBg = [ 'light' => '#eff6ff', 'dark' => 'rgba(59,130,246,0.14)' ];

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

	/*
	 * The card lift. Only one shadow layer is available (the attribute is a single
	 * h/v/blur/spread/colour set), so it is one deliberately firm layer instead of
	 * the stacked pair a design system would normally use. The negative spread
	 * pulls the shadow in tighter than the card before it blurs, which reads as a
	 * lift rather than a grey halo on all four sides. `enabled` gates the whole
	 * thing — every block ships the shape switched off.
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
	 * wrapper is a card (the form, the details column), so both columns carry one
	 * card language instead of the same numbers written twice.
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
					'padding' => [ 'top' => '28', 'right' => '28', 'bottom' => '28', 'left' => '28', 'unit' => 'px' ],
					'margin'  => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ],
				],
				'tablet'  => [],
				'mobile'  => [ 'padding' => [ 'top' => '22', 'right' => '18', 'bottom' => '22', 'left' => '18', 'unit' => 'px' ] ],
			],
		];
	};

	/**
	 * Vertical section padding (boxed, centred).
	 *
	 * `$bottom` defaults to `$y`; pass it when a section needs to sit closer to
	 * the one it introduces than to the one after it.
	 *
	 * A boxed container paints its background, radius and padding on the INNER
	 * element, so this padding sits inside the panel. On desktop the inner's
	 * max-width keeps the panel clear of the viewport; below that there is no
	 * max-width left to do it, so the mobile override adds an OUTER margin — which
	 * the generator puts on the wrapper, outside the painted panel — and trims the
	 * inner side padding so the text is not doubly inset.
	 */
	$pad = static function ( string $y, ?string $bottom = null ): array {
		$b = $bottom ?? $y;
		return [
			'desktop' => [
				'padding' => [ 'top' => $y, 'right' => '32', 'bottom' => $b, 'left' => '32', 'unit' => 'px' ],
				'margin'  => [ 'top' => '', 'right' => 'auto', 'bottom' => '', 'left' => 'auto', 'unit' => 'px' ],
			],
			'tablet'  => [],
			'mobile'  => [
				'padding' => [ 'top' => $y, 'right' => '20', 'bottom' => $b, 'left' => '20', 'unit' => 'px' ],
				'margin'  => [ 'top' => '', 'right' => '16', 'bottom' => '', 'left' => '16', 'unit' => 'px' ],
			],
		];
	};

	/* ---------------------------------------------------------------------------
	 * Intro.
	 * ------------------------------------------------------------------------- */
	$intro = $mk(
		'flexa/container',
		[
			'containerType' => 'boxed',
			// Narrower than the body below it: the Text block has no max-width of
			// its own, so the measure for the lead has to come from its container.
			'widthBoxed'    => [ 'desktop' => [ 'value' => '760', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
			// A smaller bottom than top, so the heading stays attached to the form
			// it introduces rather than floating midway between two sections.
			'spacing'       => $pad( '64', '32' ),
		],
		$mk(
			'flexa/heading',
			[
				'content'    => __( 'Get in touch', 'flexa-block' ),
				'tag'        => 'h1',
				'alignment'  => [ 'desktop' => 'center', 'tablet' => '', 'mobile' => '' ],
				'typography' => [
					'desktop' => [
						'fontSize'      => [ 'value' => '42', 'unit' => 'px' ],
						'fontWeight'    => '700',
						'letterSpacing' => [ 'value' => '-0.8', 'unit' => 'px' ],
						'lineHeight'    => '1.15',
					],
					'tablet'  => [ 'fontSize' => [ 'value' => '34', 'unit' => 'px' ] ],
					'mobile'  => [ 'fontSize' => [ 'value' => '30', 'unit' => 'px' ] ],
				],
				'textColor'  => $ink,
			]
		) . "\n" .
		$mk(
			'flexa/text',
			[
				'content'    => __( 'Have a question or want to work together? Send us a message and we will get back to you within one business day.', 'flexa-block' ),
				'htmlTag'    => 'p',
				'alignment'  => [ 'desktop' => 'center', 'tablet' => '', 'mobile' => '' ],
				'typography' => [
					'desktop' => [ 'fontSize' => [ 'value' => '17', 'unit' => 'px' ], 'lineHeight' => '1.65' ],
					'tablet'  => [],
					'mobile'  => [ 'fontSize' => [ 'value' => '16', 'unit' => 'px' ] ],
				],
				'textColor'  => $muted,
			]
		)
	);

	/* ---------------------------------------------------------------------------
	 * Form column (subscribe-form hosts its field children).
	 * ------------------------------------------------------------------------- */
	$field = static function ( string $type, string $label, string $name, string $placeholder, bool $required, array $extra = [] ) use ( $mk ): string {
		return $mk(
			'flexa/subscribe-form-' . $type,
			array_merge(
				[
					'label'       => $label,
					'fieldName'   => $name,
					'placeholder' => $placeholder,
					'required'    => $required,
					'showLabel'   => true,
					'width'       => '100',
				],
				$extra
			)
		);
	};

	$form = $mk(
		'flexa/subscribe-form',
		array_merge(
			[
				'submitText'        => __( 'Send message', 'flexa-block' ),
				'toEmail'           => '',
				'emailSubject'      => __( 'New contact message', 'flexa-block' ),
				'successMessage'    => __( 'Thanks for reaching out. We will reply soon.', 'flexa-block' ),
				'errorMessage'      => __( 'Something went wrong. Please try again.', 'flexa-block' ),
				'confirmationType'  => 'message',
				'buttonWidth'       => 'full',

				'fieldGap'          => [ 'desktop' => [ 'value' => '18', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
				'labelColor'        => $ink,
				// inputBorder and inputPadding are FLAT objects, not the responsive
				// {desktop,tablet,mobile} shape the rest of the page uses.
				'inputBorder'       => [
					'style'  => 'solid',
					'width'  => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'unit' => 'px' ],
					'color'  => $line,
					'radius' => [ 'topLeft' => '10', 'topRight' => '10', 'bottomRight' => '10', 'bottomLeft' => '10', 'unit' => 'px' ],
				],
				'inputPadding'      => [ 'top' => '12', 'right' => '14', 'bottom' => '12', 'left' => '14', 'unit' => 'px' ],
				'inputBackground'   => [ 'light' => '#ffffff', 'dark' => '#0b1120' ],
				'inputTextColor'    => $ink,
				'inputPlaceholderColor' => [ 'light' => '#9ca3af', 'dark' => '#6b7280' ],

				'submitBackground'      => $accent,
				'submitBackgroundHover' => [ 'light' => '#1d4ed8', 'dark' => '#3b82f6' ],
				'submitTextColor'       => [ 'light' => '#ffffff', 'dark' => '#ffffff' ],
				'submitTextColorHover'  => [ 'light' => '#ffffff', 'dark' => '#ffffff' ],
				'submitRadius'          => [ 'value' => '10', 'unit' => 'px' ],
				'submitPadding'         => [ 'top' => '14', 'right' => '24', 'bottom' => '14', 'left' => '24', 'unit' => 'px' ],
			],
			$cardStyle()
		),
		$field( 'name', __( 'Name', 'flexa-block' ), 'name', __( 'Your name', 'flexa-block' ), true ) . "\n" .
		$field( 'email', __( 'Email', 'flexa-block' ), 'email', __( 'you@example.com', 'flexa-block' ), true ) . "\n" .
		$field( 'phone', __( 'Phone', 'flexa-block' ), 'phone', __( 'Optional', 'flexa-block' ), false ) . "\n" .
		$field( 'textarea', __( 'Message', 'flexa-block' ), 'message', __( 'How can we help?', 'flexa-block' ), true, [ 'rows' => 5 ] )
	);

	/* ---------------------------------------------------------------------------
	 * Details column (contact list + map).
	 * ------------------------------------------------------------------------- */
	$details = $mk(
		'flexa/container',
		array_merge(
			[ 'containerType' => 'full-width' ],
			$cardStyle()
		),
		$mk(
			'flexa/heading',
			[
				'content'    => __( 'Contact details', 'flexa-block' ),
				'tag'        => 'h2',
				'typography' => [
					'desktop' => [ 'fontSize' => [ 'value' => '19', 'unit' => 'px' ], 'fontWeight' => '600', 'lineHeight' => '1.3' ],
					'tablet'  => [],
					'mobile'  => [],
				],
				'textColor'  => $ink,
			]
		) . "\n" .
		$mk(
			'flexa/icon-list',
			[
				'view'       => 'list',
				'columns'    => [ 'desktop' => [ 'value' => '1', 'unit' => '' ], 'tablet' => [ 'value' => '1', 'unit' => '' ], 'mobile' => [ 'value' => '1', 'unit' => '' ] ],

				/*
				 * Icon chips. `iconShape` is only honoured once `iconView` leaves
				 * 'default' — the generator skips the frame radius entirely for a
				 * plain-glyph list — so 'stacked' is what turns the background plus
				 * padding into a rounded chip rather than a square block of colour.
				 */
				'iconView'   => 'stacked',
				'iconShape'  => 'rounded',
				'iconSize'   => [ 'desktop' => [ 'value' => '18', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
				'iconColor'  => $accent,
				'iconBackground' => $chipBg,
				'iconPadding' => [
					'desktop' => [ 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'unit' => 'px' ],
					'tablet'  => [],
					'mobile'  => [],
				],
				'gap'        => [ 'desktop' => [ 'value' => '14', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
				'iconGap'    => [ 'desktop' => [ 'value' => '14', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
				'typography' => [
					'desktop' => [ 'fontSize' => [ 'value' => '15', 'unit' => 'px' ], 'lineHeight' => '1.5' ],
					'tablet'  => [],
					'mobile'  => [],
				],
				'textColor'  => [ 'light' => '#374151', 'dark' => '#d1d5db' ],
				'items'      => [
					[
						'id'   => 'contact-email',
						'text' => 'hello@example.com',
						'link' => [ 'url' => 'mailto:hello@example.com', 'target' => '', 'rel' => '' ],
						'icon' => $icon( 'mail', '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>' ),
					],
					[
						'id'   => 'contact-phone',
						'text' => '+1 (555) 000-1234',
						'link' => [ 'url' => 'tel:+15550001234', 'target' => '', 'rel' => '' ],
						'icon' => $icon( 'phone', '<path d="M5 4h4l2 5-3 2a12 12 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>' ),
					],
					[
						'id'   => 'contact-address',
						'text' => __( '123 Market Street, San Francisco, CA', 'flexa-block' ),
						'link' => [ 'url' => '', 'target' => '', 'rel' => '' ],
						'icon' => $icon( 'pin', '<path d="M12 21s-7-6-7-11a7 7 0 0 1 14 0c0 5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>' ),
					],
				],
			]
		) . "\n" .
		$mk(
			'flexa/google-map',
			[
				'location'      => 'San Francisco, CA',
				'zoom'          => 13,
				'containerType' => 'full-width',
				'height'        => [ 'desktop' => [ 'value' => '320', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
				// The block's own stylesheet sets overflow:hidden on the frame, so
				// the radius clips the iframe rather than being drawn behind it.
				'border'        => [
					'desktop' => [
						'style'  => '',
						'width'  => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ],
						'color'  => [ 'light' => '', 'dark' => '' ],
						'radius' => [ 'topLeft' => '12', 'topRight' => '12', 'bottomRight' => '12', 'bottomLeft' => '12', 'unit' => 'px' ],
					],
					'tablet'  => [],
					'mobile'  => [],
				],
			]
		)
	);

	/* ---------------------------------------------------------------------------
	 * Two-column body.
	 * ------------------------------------------------------------------------- */
	$body = $mk(
		'flexa/container',
		[
			'containerType' => 'boxed',
			// No top padding: the intro above already supplies the gap.
			'spacing'       => $pad( '0', '64' ),
		],
		$mk(
			'flexa/grid',
			[
				'containerType' => 'full-width',
				'layout'        => [
					'desktop' => [ 'columns' => [ 'value' => '2', 'unit' => 'fr' ], 'rows' => [ 'value' => '', 'unit' => 'fr' ], 'gap' => [ 'column' => '40', 'row' => '40', 'unit' => 'px' ], 'autoFlow' => 'row', 'justifyItems' => 'stretch', 'alignItems' => 'start', 'justifyContent' => '', 'alignContent' => '' ],
					'tablet'  => [],
					'mobile'  => [ 'columns' => [ 'value' => '1', 'unit' => 'fr' ] ],
				],
				'spacing'       => [ 'desktop' => [ 'padding' => [ 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'unit' => 'px' ], 'margin' => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ] ], 'tablet' => [], 'mobile' => [] ],
			],
			$form . "\n" . $details
		)
	);

	/* ---------------------------------------------------------------------------
	 * Support FAQ.
	 * ------------------------------------------------------------------------- */
	$faq = $mk(
		'flexa/container',
		[
			'containerType' => 'boxed',
			'spacing'       => $pad( '0', '72' ),
		],
		$mk(
			'flexa/heading',
			[
				'content'    => __( 'Before you write', 'flexa-block' ),
				'tag'        => 'h2',
				'alignment'  => [ 'desktop' => 'center', 'tablet' => '', 'mobile' => '' ],
				'typography' => [
					'desktop' => [
						'fontSize'      => [ 'value' => '28', 'unit' => 'px' ],
						'fontWeight'    => '700',
						'letterSpacing' => [ 'value' => '-0.4', 'unit' => 'px' ],
						'lineHeight'    => '1.25',
					],
					'tablet'  => [],
					'mobile'  => [ 'fontSize' => [ 'value' => '24', 'unit' => 'px' ] ],
				],
				'textColor'  => $ink,
				'spacing'    => [
					'desktop' => [
						'padding' => [ 'top' => '', 'right' => '', 'bottom' => '', 'left' => '', 'unit' => 'px' ],
						'margin'  => [ 'top' => '', 'right' => '', 'bottom' => '24', 'left' => '', 'unit' => 'px' ],
					],
					'tablet'  => [],
					'mobile'  => [],
				],
			]
		) . "\n" .
		$mk(
			'flexa/faq',
			[
				'expandFirst'       => true,
				/*
				 * REQUIRED, not cosmetic. FAQ is one of the seven collection blocks
				 * in the item-style split, and its generator gates on this flag: with
				 * it false it paints each item from the WRAPPER `border`/`boxShadow`
				 * (the legacy shape) and ignores `itemBorder`/`itemBoxShadow`
				 * entirely, so the card chrome below would silently do nothing.
				 * TODO(remove in vNEXT): drops out with the item-style migration.
				 */
				'itemStyleMigrated' => true,

				// Narrowed and centred: at full container width the toggle icon ends
				// up marooned a screen away from its own question. `maxWidth` only
				// caps the width, so the centring comes from spacing.margin.
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

				// Per-item card, with a much softer shadow than the columns above:
				// three stacked rows each wearing the full lift reads as muddy.
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
				'questionColor'       => $ink,
				'questionActiveColor' => $accent,
				'answerTypography'    => [
					'desktop' => [ 'fontSize' => [ 'value' => '14', 'unit' => 'px' ], 'lineHeight' => '1.65' ],
					'tablet'  => [],
					'mobile'  => [],
				],
				'answerColor'         => $muted,
				'iconSize'            => [ 'desktop' => [ 'value' => '18', 'unit' => 'px' ], 'tablet' => [], 'mobile' => [] ],
				'iconColor'           => $muted,
				'iconActiveColor'     => $accent,
				'items'       => [
					[ 'question' => __( 'What are your support hours?', 'flexa-block' ), 'answer' => __( 'We reply Monday to Friday, 9am to 6pm. Messages sent on weekends are answered the next business day.', 'flexa-block' ) ],
					[ 'question' => __( 'How quickly will I hear back?', 'flexa-block' ), 'answer' => __( 'Most messages get a reply within one business day.', 'flexa-block' ) ],
					[ 'question' => __( 'Can I request a call?', 'flexa-block' ), 'answer' => __( 'Yes. Mention it in your message and include your phone number and we will arrange a time.', 'flexa-block' ) ],
				],
			]
		)
	);

	$content = implode( "\n\n", [ $intro, $body, $faq ] );

	return [
		'id'          => 'contact-page',
		'title'       => __( 'Contact Page', 'flexa-block' ),
		'description' => __( 'A two-column contact layout with a working message form, contact details and an embedded map. Set the form recipient email after importing.', 'flexa-block' ),
		'category'    => __( 'Page', 'flexa-block' ),
		'blocks'      => [ 'flexa/container', 'flexa/grid', 'flexa/heading', 'flexa/text', 'flexa/subscribe-form', 'flexa/icon-list', 'flexa/google-map', 'flexa/faq' ],
		'version'     => '1.0.0',
		'content'     => $content,
	];
} )();
