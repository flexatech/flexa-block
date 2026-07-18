/**
 * Before / After front-end view script.
 *
 * Each slider renders with `data-orientation` (horizontal|vertical),
 * `data-interaction` (drag|hover) and a starting `data-position` (0–100). The
 * split is driven entirely by the `--flexa-ba-pos` custom property on the frame,
 * so this script's only job is to update that value: on pointer drag, on hover
 * movement, or with the arrow keys when the handle has focus. No layout is read
 * or written beyond the frame's bounding box.
 */

export {}; // Module scope so names don't collide with other view scripts.

/**
 * Wire one before/after slider.
 *
 * @param root The `.flexa-before-after` wrapper.
 */
function initSlider( root: HTMLElement ): void {
	const frame = root.querySelector< HTMLElement >( '.flexa-before-after__frame' );
	const handle = root.querySelector< HTMLElement >( '.flexa-before-after__handle' );
	if ( ! frame || ! handle ) {
		return;
	}

	const beforeLabel = root.querySelector< HTMLElement >( '.flexa-before-after__label--before' );
	const afterLabel = root.querySelector< HTMLElement >( '.flexa-before-after__label--after' );

	const isVertical = root.getAttribute( 'data-orientation' ) === 'vertical';
	const onHover = root.getAttribute( 'data-interaction' ) === 'hover';
	const start = Number( root.getAttribute( 'data-position' ) );

	let pos = Number.isFinite( start ) ? start : 50;

	const clamp = ( n: number ): number => Math.max( 0, Math.min( 100, n ) );
	const clamp01 = ( n: number ): number => Math.max( 0, Math.min( 1, n ) );

	const apply = ( next: number ): void => {
		pos = clamp( next );
		frame.style.setProperty( '--flexa-ba-pos', `${ pos }%` );
		handle.setAttribute( 'aria-valuenow', String( Math.round( pos ) ) );
		// Fade each label out as its own image shrinks toward zero: the "before"
		// image occupies pos% of the frame, the "after" image the remainder.
		if ( beforeLabel ) {
			beforeLabel.style.opacity = String( clamp01( ( pos - 3 ) / 10 ) );
		}
		if ( afterLabel ) {
			afterLabel.style.opacity = String( clamp01( ( 97 - pos ) / 10 ) );
		}
	};

	// Position (0–100) from a pointer's page coordinates against the frame box.
	const fromPointer = ( clientX: number, clientY: number ): number => {
		const rect = frame.getBoundingClientRect();
		if ( isVertical ) {
			return rect.height ? ( ( clientY - rect.top ) / rect.height ) * 100 : 50;
		}
		return rect.width ? ( ( clientX - rect.left ) / rect.width ) * 100 : 50;
	};

	apply( pos );

	if ( onHover ) {
		frame.addEventListener( 'pointermove', ( e: PointerEvent ) => apply( fromPointer( e.clientX, e.clientY ) ) );
	} else {
		let dragging = false;
		const move = ( e: PointerEvent ): void => {
			if ( ! dragging ) {
				return;
			}
			apply( fromPointer( e.clientX, e.clientY ) );
		};
		const up = (): void => {
			dragging = false;
		};
		frame.addEventListener( 'pointerdown', ( e: PointerEvent ) => {
			dragging = true;
			apply( fromPointer( e.clientX, e.clientY ) );
			e.preventDefault();
		} );
		window.addEventListener( 'pointermove', move );
		window.addEventListener( 'pointerup', up );
		window.addEventListener( 'pointercancel', up );
	}

	// Keyboard: arrows nudge by 1, Home/End jump to the ends (handle is focusable).
	handle.addEventListener( 'keydown', ( e: KeyboardEvent ) => {
		let handled = true;
		switch ( e.key ) {
			case 'ArrowLeft':
			case 'ArrowDown':
				apply( pos - 1 );
				break;
			case 'ArrowRight':
			case 'ArrowUp':
				apply( pos + 1 );
				break;
			case 'Home':
				apply( 0 );
				break;
			case 'End':
				apply( 100 );
				break;
			default:
				handled = false;
		}
		if ( handled ) {
			e.preventDefault();
		}
	} );
}

/**
 * Wire every before/after slider on the page.
 */
function init(): void {
	document.querySelectorAll< HTMLElement >( '.flexa-before-after' ).forEach( initSlider );
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
