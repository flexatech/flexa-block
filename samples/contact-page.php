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

	/* ---------------------------------------------------------------------------
	 * Intro.
	 * ------------------------------------------------------------------------- */
	$intro = $mk(
		'flexa/container',
		[ 'containerType' => 'boxed' ],
		$mk(
			'flexa/heading',
			[
				'content'   => __( 'Get in touch', 'flexa-block' ),
				'tag'       => 'h1',
				'alignment' => [ 'desktop' => 'center', 'tablet' => '', 'mobile' => '' ],
			]
		) . "\n" .
		$mk(
			'flexa/text',
			[
				'content'   => __( 'Have a question or want to work together? Send us a message and we will get back to you within one business day.', 'flexa-block' ),
				'htmlTag'   => 'p',
				'alignment' => [ 'desktop' => 'center', 'tablet' => '', 'mobile' => '' ],
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
		[
			'submitText'      => __( 'Send message', 'flexa-block' ),
			'toEmail'         => '',
			'emailSubject'    => __( 'New contact message', 'flexa-block' ),
			'successMessage'  => __( 'Thanks for reaching out. We will reply soon.', 'flexa-block' ),
			'errorMessage'    => __( 'Something went wrong. Please try again.', 'flexa-block' ),
			'confirmationType' => 'message',
			'buttonWidth'     => 'full',
		],
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
		[ 'containerType' => 'full-width' ],
		$mk(
			'flexa/heading',
			[ 'content' => __( 'Contact details', 'flexa-block' ), 'tag' => 'h2' ]
		) . "\n" .
		$mk(
			'flexa/icon-list',
			[
				'view'    => 'list',
				'columns' => [ 'desktop' => [ 'value' => '1', 'unit' => '' ], 'tablet' => [ 'value' => '1', 'unit' => '' ], 'mobile' => [ 'value' => '1', 'unit' => '' ] ],
				'items'   => [
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
			]
		)
	);

	/* ---------------------------------------------------------------------------
	 * Two-column body.
	 * ------------------------------------------------------------------------- */
	$body = $mk(
		'flexa/container',
		[ 'containerType' => 'boxed' ],
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
		[ 'containerType' => 'boxed' ],
		$mk(
			'flexa/heading',
			[ 'content' => __( 'Before you write', 'flexa-block' ), 'tag' => 'h2', 'alignment' => [ 'desktop' => 'center', 'tablet' => '', 'mobile' => '' ] ]
		) . "\n" .
		$mk(
			'flexa/faq',
			[
				'expandFirst' => true,
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
