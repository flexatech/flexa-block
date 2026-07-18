<?php
/**
 * Subscribe Form block — server-side render (parent of the field children).
 *
 * Outputs the <form> around the rendered field children plus the submit button,
 * a honeypot spam trap, a nonce and a message region. Submission is handled on
 * the front end by view.js, which POSTs to admin-ajax (action flexa_subscribe);
 * Form_Handler re-reads THIS block from the saved post to find the destination
 * email, so the recipient can never be tampered with from the browser.
 *
 * CSS is generated at save time by Subscribe_Form_CSS and printed inline.
 *
 * @package Flexa\Block
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    InnerBlocks content (the rendered field children).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $attributes, $content provided by WP block API.

use Flexa\Block\HTML_Helpers;

$block_id = $attributes['blockId'] ?? '';
$anchor   = $attributes['anchor'] ?? '';

$submit_text = (string) ( $attributes['submitText'] ?? '' );
if ( '' === trim( $submit_text ) ) {
	$submit_text = __( 'Subscribe', 'flexa-block' );
}

$confirmation = 'url' === ( $attributes['confirmationType'] ?? 'message' ) ? 'url' : 'message';
$redirect_url = 'url' === $confirmation ? esc_url( (string) ( $attributes['redirectUrl'] ?? '' ) ) : '';

$button_align = $attributes['buttonAlign'] ?? 'flex-start';
$button_align = in_array( $button_align, [ 'flex-start', 'center', 'flex-end' ], true ) ? $button_align : 'flex-start';
$button_full  = 'full' === ( $attributes['buttonWidth'] ?? 'auto' );

$success_message = (string) ( $attributes['successMessage'] ?? '' );
$error_message   = (string) ( $attributes['errorMessage'] ?? '' );

$classes = [ 'flexa-subscribe-form' ];
if ( '' !== $block_id ) {
	$classes[] = 'flexa-subscribe-form-' . sanitize_html_class( $block_id );
}
$classes = HTML_Helpers::build_wrapper_classes( $classes, $attributes );

$wrapper_args = [
	'class'              => implode( ' ', $classes ),
	// Turn off the browser's own validation UI — view.js validates and shows the
	// messages inline instead (otherwise a native required field blocks submit and
	// the submit event — and our handler — never fires).
	'novalidate'         => 'novalidate',
	'data-flexa-ajax'    => esc_url( admin_url( 'admin-ajax.php' ) ),
	'data-flexa-nonce'   => wp_create_nonce( 'flexa_subscribe' ),
	'data-flexa-post'    => (string) get_the_ID(),
	'data-flexa-block'   => sanitize_html_class( $block_id ),
	'data-flexa-confirm'  => $confirmation,
	'data-flexa-required' => __( 'This field is required.', 'flexa-block' ),
	'data-flexa-phone'    => __( 'Please enter a valid phone number.', 'flexa-block' ),
];
if ( '' !== $redirect_url ) {
	$wrapper_args['data-flexa-redirect'] = $redirect_url;
}
if ( '' !== trim( $success_message ) ) {
	$wrapper_args['data-flexa-success'] = $success_message;
}
if ( '' !== trim( $error_message ) ) {
	$wrapper_args['data-flexa-error'] = $error_message;
}
if ( $anchor ) {
	$wrapper_args['id'] = sanitize_html_class( $anchor );
}

$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );
$data_attrs         = HTML_Helpers::build_data_attrs( $attributes );

$actions_class = 'flexa-subscribe-form__actions flexa-subscribe-form__actions--' . $button_align;
$submit_class  = 'flexa-subscribe-form__submit wp-element-button' . ( $button_full ? ' flexa-subscribe-form__submit--full' : '' );

// Honeypot: a bot that fills this hidden field is silently treated as spam.
$honeypot = '<div class="flexa-subscribe-form__hp" aria-hidden="true">'
	. '<label>' . esc_html__( 'Leave this field empty', 'flexa-block' )
	. '<input type="text" name="flexa_hp" tabindex="-1" autocomplete="off" /></label></div>';

$submit = '<div class="' . esc_attr( $actions_class ) . '">'
	. '<button type="submit" class="' . esc_attr( $submit_class ) . '">' . esc_html( $submit_text ) . '</button>'
	. '</div>';

// The success / error feedback is a toast created by view.js on submit.
printf(
	'<form %1$s%2$s><div class="flexa-subscribe-form__fields">%3$s</div>%4$s%5$s</form>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via get_block_wrapper_attributes.
	$data_attrs,         // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- keys sanitized, values escaped in helper.
	$content,            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- InnerBlocks content already rendered/escaped by WP.
	$honeypot,           // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static literals + escaped __().
	$submit              // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values escaped above.
);
