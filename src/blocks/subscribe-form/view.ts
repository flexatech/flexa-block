/**
 * Subscribe Form front-end view script.
 *
 * Handles submission for every `.flexa-subscribe-form` on the page: it collects
 * the named field values, POSTs them to admin-ajax (action `flexa_subscribe`)
 * with the nonce carried in the form's data attributes, then either shows the
 * configured success / error message or redirects. A filled honeypot field is
 * treated as spam and silently "succeeds". The destination email is never sent
 * from here — the server re-reads it from the saved block.
 */

export {}; // Treat as a module so top-level names don't collide with other view scripts.

interface FormConfig {
	ajax: string;
	nonce: string;
	post: string;
	block: string;
	confirm: string;
	redirect: string;
	success: string;
	error: string;
	required: string;
	phone: string;
}

/** Names of controls that must not be sent as user data. */
const SKIP_NAMES = [ 'flexa_hp', '_wp_http_referer' ];

/** Read the form's configuration off its data attributes. */
function readConfig( form: HTMLFormElement ): FormConfig {
	const d = form.dataset;
	return {
		ajax: d.flexaAjax || '',
		nonce: d.flexaNonce || '',
		post: d.flexaPost || '',
		block: d.flexaBlock || '',
		confirm: d.flexaConfirm || 'message',
		redirect: d.flexaRedirect || '',
		success: d.flexaSuccess || 'Thanks! Your subscription has been received.',
		error: d.flexaError || 'Something went wrong. Please try again.',
		required: d.flexaRequired || 'This field is required.',
		phone: d.flexaPhone || 'Please enter a valid phone number.',
	};
}

/** Collect the named field values into a plain object for the email body. */
function collectData( form: HTMLFormElement ): Record< string, string > {
	const data: Record< string, string > = {};
	const controls = form.querySelectorAll< HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement >( 'input, textarea, select' );
	controls.forEach( ( el ) => {
		const name = el.name;
		if ( ! name || SKIP_NAMES.includes( name ) ) {
			return;
		}
		// Files are transferred separately as multipart data, not in the JSON.
		if ( el instanceof HTMLInputElement && el.type === 'file' ) {
			return;
		}
		if ( el instanceof HTMLInputElement && ( el.type === 'checkbox' || el.type === 'radio' ) ) {
			if ( ! el.checked ) {
				return;
			}
			const value = el.value && el.value !== 'on' ? el.value : 'Yes';
			// A checkbox group shares one name — keep every checked value.
			if ( el.type === 'checkbox' && data[ name ] !== undefined ) {
				data[ name ] = `${ data[ name ] }, ${ value }`;
			} else {
				data[ name ] = value;
			}
			return;
		}
		data[ name ] = el.value;
	} );
	return data;
}

/**
 * Show a floating, auto-dismissing toast for the form's result. Appended inside
 * the form so the per-instance message colours (scoped under the form's blockId)
 * still apply; it is `position: fixed`, so its DOM parent doesn't affect placement.
 */
function showToast( form: HTMLFormElement, text: string, kind: 'success' | 'error' ): void {
	const existing = form.querySelector( '.flexa-toast' );
	if ( existing ) {
		existing.remove();
	}

	const toast = document.createElement( 'div' );
	toast.className = `flexa-toast flexa-subscribe-form__message flexa-subscribe-form__message--${ kind }`;
	toast.setAttribute( 'role', 'status' );
	toast.setAttribute( 'aria-live', 'polite' );
	toast.textContent = text;
	form.appendChild( toast );

	// Next frame → add the class so the enter transition plays.
	requestAnimationFrame( () => toast.classList.add( 'flexa-toast--show' ) );

	const remove = (): void => {
		toast.classList.remove( 'flexa-toast--show' );
		window.setTimeout( () => toast.remove(), 250 );
	};
	const timer = window.setTimeout( remove, 4500 );
	toast.addEventListener( 'click', () => {
		window.clearTimeout( timer );
		remove();
	} );
}

type FieldControl = HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement;

/** Lenient phone check: only phone characters, and at least 6 digits. */
function isValidPhone( value: string ): boolean {
	return /^[\d+()\-.\s]{6,}$/.test( value ) && value.replace( /\D/g, '' ).length >= 6;
}

/** Remove all inline field errors in the form. */
function clearErrors( form: HTMLFormElement ): void {
	form.querySelectorAll( '.flexa-field__error' ).forEach( ( el ) => el.remove() );
	form.querySelectorAll( '.flexa-field--invalid' ).forEach( ( el ) => el.classList.remove( 'flexa-field--invalid' ) );
}

/** Show one error message under the field that holds `el`. */
function setError( el: Element, message: string ): void {
	const field = el.closest( '.flexa-field' );
	if ( ! field ) {
		return;
	}
	field.classList.add( 'flexa-field--invalid' );
	if ( field.querySelector( '.flexa-field__error' ) ) {
		return; // One message per field.
	}
	const note = document.createElement( 'span' );
	note.className = 'flexa-field__error';
	note.textContent = message;
	field.appendChild( note );
}

/**
 * Validate the whole form, rendering a message beneath each invalid field.
 * Single-line inputs use the native constraints (required / email / url), phones
 * use a custom check, and radio / checkbox groups need at least one choice.
 * Returns true when everything passes.
 */
function validateForm( form: HTMLFormElement, cfg: FormConfig ): boolean {
	clearErrors( form );
	let firstInvalid: FieldControl | null = null;

	form.querySelectorAll< FieldControl >( 'input, textarea, select' ).forEach( ( el ) => {
		if ( ! el.name || SKIP_NAMES.includes( el.name ) ) {
			return;
		}
		// Groups (radio / checkbox) are validated separately below.
		if ( el instanceof HTMLInputElement && ( el.type === 'checkbox' || el.type === 'radio' ) ) {
			return;
		}
		let message = '';
		if ( el instanceof HTMLInputElement && el.type === 'tel' && el.value.trim() !== '' ) {
			if ( ! isValidPhone( el.value ) ) {
				message = cfg.phone;
			}
		}
		if ( ! message && typeof el.checkValidity === 'function' && ! el.checkValidity() ) {
			message = el.validationMessage || cfg.required;
		}
		if ( message ) {
			setError( el, message );
			if ( ! firstInvalid ) {
				firstInvalid = el;
			}
		}
	} );

	form.querySelectorAll< HTMLElement >( '.flexa-field__options[data-required="1"]' ).forEach( ( group ) => {
		const boxes = group.querySelectorAll< HTMLInputElement >( 'input[type="checkbox"], input[type="radio"]' );
		const anyChecked = Array.from( boxes ).some( ( box ) => box.checked );
		if ( ! anyChecked && boxes[ 0 ] ) {
			setError( boxes[ 0 ], cfg.required );
			if ( ! firstInvalid ) {
				firstInvalid = boxes[ 0 ];
			}
		}
	} );

	if ( firstInvalid ) {
		( firstInvalid as FieldControl ).focus();
		return false;
	}
	return true;
}

/**
 * Toggle the submit button's loading state: dim it, keep the label, and append
 * three dots that blink in sequence.
 */
function setLoading( button: HTMLButtonElement | null, on: boolean ): void {
	if ( ! button ) {
		return;
	}
	button.disabled = on;
	button.classList.toggle( 'is-loading', on );

	let dots = button.querySelector( '.flexa-subscribe-form__dots' );
	if ( on ) {
		if ( ! dots ) {
			dots = document.createElement( 'span' );
			dots.className = 'flexa-subscribe-form__dots';
			dots.setAttribute( 'aria-hidden', 'true' );
			dots.innerHTML = '<span>.</span><span>.</span><span>.</span>';
			button.appendChild( dots );
		}
	} else if ( dots ) {
		dots.remove();
	}
}

/** Submit one form over AJAX. */
async function submitForm( form: HTMLFormElement ): Promise< void > {
	const cfg = readConfig( form );

	if ( ! validateForm( form, cfg ) ) {
		return;
	}

	// Honeypot: a bot filled the hidden field — pretend everything is fine.
	const honeypot = form.querySelector< HTMLInputElement >( '[name="flexa_hp"]' );
	if ( honeypot && honeypot.value ) {
		showToast( form, cfg.success, 'success' );
		form.reset();
		return;
	}

	const button = form.querySelector< HTMLButtonElement >( '.flexa-subscribe-form__submit' );
	setLoading( button, true );

	const fields: Record< string, string > = {
		action: 'flexa_subscribe',
		nonce: cfg.nonce,
		post_id: cfg.post,
		block_id: cfg.block,
		flexa_hp: honeypot ? honeypot.value : '',
		form_data: JSON.stringify( collectData( form ) ),
	};

	// If the form carries any chosen files, send multipart so the files come
	// through; otherwise a plain urlencoded body is enough.
	const fileInputs = Array.from( form.querySelectorAll< HTMLInputElement >( 'input[type="file"]' ) );
	const hasFiles = fileInputs.some( ( input ) => input.files && input.files.length > 0 );

	let body: FormData | string;
	let headers: Record< string, string > | undefined;
	if ( hasFiles ) {
		const fd = new FormData();
		Object.entries( fields ).forEach( ( [ key, val ] ) => fd.append( key, val ) );
		fileInputs.forEach( ( input ) => {
			if ( ! input.name || ! input.files ) {
				return;
			}
			Array.from( input.files ).forEach( ( file ) => fd.append( input.name, file, file.name ) );
		} );
		body = fd; // Let the browser set the multipart Content-Type + boundary.
	} else {
		const params = new URLSearchParams();
		Object.entries( fields ).forEach( ( [ key, val ] ) => params.set( key, val ) );
		body = params.toString();
		headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
	}

	try {
		const res = await fetch( cfg.ajax, {
			method: 'POST',
			credentials: 'same-origin',
			headers,
			body,
		} );
		const json = ( await res.json() ) as { success?: boolean };

		if ( res.ok && json && json.success ) {
			if ( cfg.confirm === 'url' && cfg.redirect ) {
				window.location.href = cfg.redirect;
				return;
			}
			showToast( form, cfg.success, 'success' );
			form.reset();
		} else {
			showToast( form, cfg.error, 'error' );
		}
	} catch ( e ) {
		showToast( form, cfg.error, 'error' );
	} finally {
		setLoading( button, false );
	}
}

/** Wire submit handlers for every subscribe form on the page. */
function init(): void {
	const forms = document.querySelectorAll< HTMLFormElement >( '.flexa-subscribe-form' );
	forms.forEach( ( form ) => {
		if ( form.dataset.flexaInit ) {
			return;
		}
		form.dataset.flexaInit = '1';
		form.addEventListener( 'submit', ( e ) => {
			e.preventDefault();
			void submitForm( form );
		} );
		// Clear a field's error as soon as the visitor edits it.
		const clearOnEdit = ( e: Event ): void => {
			const target = e.target as HTMLElement | null;
			const field = target && target.closest ? target.closest( '.flexa-field' ) : null;
			if ( field ) {
				field.classList.remove( 'flexa-field--invalid' );
				const note = field.querySelector( '.flexa-field__error' );
				if ( note ) {
					note.remove();
				}
			}
		};
		form.addEventListener( 'input', clearOnEdit );
		form.addEventListener( 'change', clearOnEdit );
	} );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
