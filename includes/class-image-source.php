<?php
/**
 * Turns a media library ID into the image data a block stores.
 *
 * @package Flexa\Block
 */

declare(strict_types=1);

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The one place that decides whether an attachment may be used as a block's
 * image, and what the block stores for it.
 *
 * The front-end editor only ever receives an attachment ID. Everything else a
 * block keeps about an image (its address, its size, its alt text) is read from
 * the attachment here, never taken from the browser, so a person who can pick
 * an image cannot make a block point anywhere else.
 */
final class Image_Source {

	/**
	 * Check an attachment and read what a block stores about it.
	 *
	 * The same data the block editor stores when someone picks the image: the
	 * full-size address, the full-size width and height, and the attachment's
	 * own alt text.
	 *
	 * @param int $id Attachment ID.
	 * @return array{id:int,url:string,alt:string,width:?int,height:?int}|\WP_Error
	 */
	public static function describe( int $id ) {
		if ( $id < 1 || 'attachment' !== get_post_type( $id ) || ! wp_attachment_is_image( $id ) ) {
			return self::refuse( 'flexa_image_invalid', __( 'That is not an image in the media library.', 'flexa-block' ), 400 );
		}

		// Choosing from the library is part of uploading: the picker itself is
		// closed to anyone without this, and a request that skips it is too.
		if ( ! current_user_can( 'upload_files' ) ) {
			return self::refuse( 'flexa_image_forbidden', __( 'You are not allowed to choose images from the media library.', 'flexa-block' ), 403 );
		}

		if ( ! current_user_can( 'read_post', $id ) ) {
			return self::refuse( 'flexa_image_forbidden', __( 'You are not allowed to use that image.', 'flexa-block' ), 403 );
		}

		$url = wp_get_attachment_url( $id );
		if ( ! is_string( $url ) || '' === $url ) {
			return self::refuse( 'flexa_image_invalid', __( 'That image has no file.', 'flexa-block' ), 400 );
		}

		$meta   = wp_get_attachment_metadata( $id );
		$width  = is_array( $meta ) && ! empty( $meta['width'] ) ? (int) $meta['width'] : null;
		$height = is_array( $meta ) && ! empty( $meta['height'] ) ? (int) $meta['height'] : null;

		return [
			'id'     => $id,
			'url'    => $url,
			'alt'    => trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ),
			'width'  => $width,
			'height' => $height,
		];
	}

	/**
	 * Plain alt text from whatever was typed.
	 *
	 * Alt text is attribute text, never markup: tags are stripped and line
	 * breaks become spaces.
	 *
	 * @param string $raw Text as typed.
	 */
	public static function clean_alt( string $raw ): string {
		$text = sanitize_text_field( $raw );
		return mb_substr( $text, 0, 500 );
	}

	/**
	 * Build the error.
	 *
	 * @param string $code    Error code.
	 * @param string $message Message for the person.
	 * @param int    $status  HTTP status.
	 */
	private static function refuse( string $code, string $message, int $status ): \WP_Error {
		return new \WP_Error( $code, $message, [ 'status' => $status ] );
	}
}
