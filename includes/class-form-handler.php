<?php
declare(strict_types=1);
/**
 * Form Handler — server side of the Subscribe Form submission.
 *
 * Fields are collected on the front end (view.js) and POSTed to admin-ajax
 * (action `flexa_subscribe`). This class verifies the nonce, drops obvious spam
 * (honeypot), re-reads the form block from the saved post to find the trusted
 * destination email (so the recipient can never be set from the browser), builds
 * an HTML email from the submitted values and sends it with wp_mail(). No data
 * is stored in the database.
 *
 * @package Flexa\Block
 */

namespace Flexa\Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX submission handler for flexa/subscribe-form.
 */
class Form_Handler {

	/**
	 * Max number of fields accepted from one submission.
	 *
	 * @var int
	 */
	const MAX_FIELDS = 50;

	/**
	 * Max characters kept per submitted value.
	 *
	 * @var int
	 */
	const MAX_VALUE_LENGTH = 5000;

	/**
	 * Max size (bytes) accepted per uploaded file.
	 *
	 * @var int
	 */
	const MAX_UPLOAD_BYTES = 10485760; // 10 MB.

	/**
	 * Max number of files attached to one submission.
	 *
	 * @var int
	 */
	const MAX_UPLOAD_FILES = 10;

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'wp_ajax_flexa_subscribe', [ __CLASS__, 'process' ] );
		add_action( 'wp_ajax_nopriv_flexa_subscribe', [ __CLASS__, 'process' ] );
	}

	/**
	 * Handle a subscribe-form submission.
	 */
	public static function process() {
		check_ajax_referer( 'flexa_subscribe', 'nonce' );

		// Honeypot: a bot filled the hidden field — accept silently, send nothing.
		if ( ! empty( $_POST['flexa_hp'] ) ) {
			wp_send_json_success();
		}

		$post_id  = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
		$block_id = isset( $_POST['block_id'] ) ? sanitize_html_class( wp_unslash( $_POST['block_id'] ) ) : '';

		$raw_data  = isset( $_POST['form_data'] ) ? json_decode( wp_unslash( $_POST['form_data'] ), true ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded values are sanitized below.
		$form_data = self::sanitize_form_data( is_array( $raw_data ) ? $raw_data : [] );

		// Uploaded files: validated + moved to temp copies, their names folded into
		// the email body, the copies attached and cleaned up after sending.
		$uploads = self::collect_uploads();
		foreach ( $uploads['names'] as $key => $names ) {
			$form_data[ $key ] = $names;
		}

		if ( empty( $form_data ) ) {
			self::cleanup_temp( $uploads['temp'] );
			wp_send_json_error( [ 'message' => 'empty' ] );
		}

		$config = self::resolve_form_config( $post_id, $block_id );

		$to = $config['to'];
		if ( '' === $to || ! is_email( $to ) ) {
			self::cleanup_temp( $uploads['temp'] );
			wp_send_json_error( [ 'message' => 'no_recipient' ] );
		}

		$sent = wp_mail(
			$to,
			$config['subject'],
			self::build_body( $form_data ),
			self::build_headers( $form_data ),
			$uploads['attachments']
		);

		self::cleanup_temp( $uploads['temp'] );

		if ( $sent ) {
			do_action( 'flexa_block_subscribe_submitted', $form_data, $config, $post_id );
			wp_send_json_success();
		}

		wp_send_json_error( [ 'message' => 'send_failed' ] );
	}

	/**
	 * Validate and stage uploaded files: check each against WordPress's allowed
	 * MIME types and the size cap, move it to a temp copy, and collect the copy
	 * paths (for attachment), the temp paths (for cleanup) and per-field name
	 * summaries (for the email body).
	 *
	 * @return array{attachments:array<int,string>,temp:array<int,string>,names:array<string,string>}
	 */
	private static function collect_uploads() {
		$result = [
			'attachments' => [],
			'temp'        => [],
			'names'       => [],
		];

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in process() via check_ajax_referer() before this method runs.
		if ( empty( $_FILES ) || ! is_array( $_FILES ) ) {
			return $result;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Missing -- each field/value is validated below; nonce verified in process().
		foreach ( $_FILES as $field => $data ) {
			$field_key = sanitize_key( (string) $field );
			if ( '' === $field_key ) {
				continue;
			}

			$collected = [];
			foreach ( self::normalize_files( $data ) as $file ) {
				if ( count( $result['attachments'] ) >= self::MAX_UPLOAD_FILES ) {
					break 2;
				}
				if ( ! isset( $file['error'] ) || UPLOAD_ERR_OK !== (int) $file['error'] ) {
					continue;
				}
				$tmp = (string) ( $file['tmp_name'] ?? '' );
				if ( '' === $tmp || ! is_uploaded_file( $tmp ) ) {
					continue;
				}
				if ( (int) ( $file['size'] ?? 0 ) > self::MAX_UPLOAD_BYTES ) {
					continue;
				}

				$filename = sanitize_file_name( (string) ( $file['name'] ?? '' ) );
				if ( '' === $filename ) {
					continue;
				}
				// WordPress's own allow-list keeps out executables (php, exe, …).
				$check = wp_check_filetype( $filename );
				if ( empty( $check['ext'] ) ) {
					continue;
				}

				$dest = wp_tempnam( $filename );
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Generic.PHP.ForbiddenFunctions.Found -- is_uploaded_file() is verified above; move_uploaded_file() is the correct primitive for staging a raw HTTP upload.
				if ( ! $dest || ! @move_uploaded_file( $tmp, $dest ) ) {
					if ( $dest ) {
						wp_delete_file( $dest );
					}
					continue;
				}

				$result['attachments'][] = $dest;
				$result['temp'][]        = $dest;
				$collected[]             = $filename;
			}

			if ( ! empty( $collected ) ) {
				$result['names'][ $field_key ] = implode( ', ', $collected );
			}
		}

		return $result;
	}

	/**
	 * Normalise a single `$_FILES` entry into a list of per-file arrays, covering
	 * both the single-file and multiple-file (`name[]`) shapes.
	 *
	 * @param mixed $data One `$_FILES` entry.
	 * @return array<int,array<string,mixed>>
	 */
	private static function normalize_files( $data ) {
		if ( ! is_array( $data ) || ! isset( $data['name'] ) ) {
			return [];
		}
		if ( is_array( $data['name'] ) ) {
			$out   = [];
			$count = count( $data['name'] );
			for ( $i = 0; $i < $count; $i++ ) {
				$out[] = [
					'name'     => $data['name'][ $i ] ?? '',
					'tmp_name' => $data['tmp_name'][ $i ] ?? '',
					'error'    => $data['error'][ $i ] ?? UPLOAD_ERR_NO_FILE,
					'size'     => $data['size'][ $i ] ?? 0,
				];
			}
			return $out;
		}
		return [ $data ];
	}

	/**
	 * Delete the temp upload copies made in collect_uploads().
	 *
	 * @param array<int,string> $paths Temp file paths.
	 */
	private static function cleanup_temp( $paths ) {
		foreach ( $paths as $path ) {
			if ( is_string( $path ) && '' !== $path && file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
	}

	/**
	 * Sanitise the decoded form data: cap the field count, keys to a safe slug,
	 * values to text with a length cap.
	 *
	 * @param array $data Raw decoded data.
	 * @return array<string,string>
	 */
	private static function sanitize_form_data( $data ) {
		$clean = [];
		$count = 0;
		foreach ( $data as $key => $value ) {
			if ( $count >= self::MAX_FIELDS ) {
				break;
			}
			$key = sanitize_key( (string) $key );
			if ( '' === $key ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'strval', $value ) );
			}
			$value = sanitize_textarea_field( (string) $value );
			if ( '' === $value ) {
				continue;
			}
			if ( strlen( $value ) > self::MAX_VALUE_LENGTH ) {
				$value = substr( $value, 0, self::MAX_VALUE_LENGTH );
			}
			$clean[ $key ] = $value;
			++$count;
		}
		return $clean;
	}

	/**
	 * Read the destination email + subject from the saved form block (via
	 * Block_Locator, which the post filter uses for the same reason). Falls back to
	 * the site admin email + a default subject when the block or its settings can't
	 * be resolved, so the recipient is always a value the site owner controls —
	 * never one supplied by the request.
	 *
	 * @param int    $post_id  Post the form lives on.
	 * @param string $block_id The form's blockId.
	 * @return array{to:string,subject:string}
	 */
	private static function resolve_form_config( $post_id, $block_id ) {
		$admin_email = (string) get_option( 'admin_email' );
		$config      = [
			'to'      => $admin_email,
			'subject' => __( 'New subscription', 'flexa-block' ),
		];

		if ( ! $post_id || '' === $block_id ) {
			return $config;
		}

		$attrs = Block_Locator::find_attrs( 'flexa/subscribe-form', $block_id, (int) $post_id );
		if ( null === $attrs ) {
			return $config;
		}

		$to = trim( (string) ( $attrs['toEmail'] ?? '' ) );
		if ( '' !== $to ) {
			$config['to'] = sanitize_email( $to );
		}
		$subject = trim( (string) ( $attrs['emailSubject'] ?? '' ) );
		if ( '' !== $subject ) {
			$config['subject'] = sanitize_text_field( $subject );
		}

		return $config;
	}

	/**
	 * Build the HTML email body from the submitted fields.
	 *
	 * @param array<string,string> $form_data Sanitised field data.
	 * @return string
	 */
	private static function build_body( $form_data ) {
		$rows = '';
		foreach ( $form_data as $key => $value ) {
			$label = ucwords( str_replace( '_', ' ', $key ) );
			$rows .= '<tr>'
				. '<td style="padding:6px 12px;font-weight:bold;vertical-align:top;">' . esc_html( $label ) . '</td>'
				. '<td style="padding:6px 12px;">' . nl2br( esc_html( $value ) ) . '</td>'
				. '</tr>';
		}

		$intro = sprintf(
			/* translators: %s: site name. */
			esc_html__( 'A new subscription was submitted on %s.', 'flexa-block' ),
			esc_html( (string) get_bloginfo( 'name' ) )
		);

		return '<div style="font-family:sans-serif;max-width:600px;">'
			. '<p>' . $intro . '</p>'
			. '<table style="border-collapse:collapse;width:100%;">' . $rows . '</table>'
			. '</div>';
	}

	/**
	 * Build the wp_mail headers (HTML + a Reply-To when the entry carries an email).
	 *
	 * @param array<string,string> $form_data Sanitised field data.
	 * @return array<int,string>
	 */
	private static function build_headers( $form_data ) {
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		foreach ( $form_data as $value ) {
			if ( is_email( $value ) ) {
				$headers[] = 'Reply-To: ' . sanitize_email( $value );
				break;
			}
		}

		return $headers;
	}
}
